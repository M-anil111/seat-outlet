# Moving the live deploy from github.sh to pull-deploy.sh

Server: CloudPanel, site user `seatoutlet-beta`. Today a cron line runs `/bin/bash /home/seatoutlet-beta/htdocs/github.sh main` every
2 minutes; its log (`/home/seatoutlet-beta/htdocs/github-auto-deploy.log`) shows `ERROR: Git fetch failed` on roughly four runs in ten
and, at times, two entries in the same minute. The site's health page (`/ajax/health.php`) reports `commit: null, commitMarker: missing`,
so nothing writes `.deployed-commit` into the folder nginx serves.

This runbook replaces that job with `deploy/pull-deploy.sh` without touching the live site until the new job has been proven against a
scratch folder. Every step is a copy-paste command for the `seatoutlet-beta` shell. Nothing here changes the live site, the secrets file,
`cache/`, the database, the hourly Bulk API cron or `TN_BULK_*` flags. The two steps that do change production, the migration run and the
cron switch, are marked **needs approval** and are not run until the owner says so.

## What the new script does differently (4 Nov 2026)

| Problem on the server | Fix in `deploy/pull-deploy.sh` |
|---|---|
| `git fetch` fails intermittently | three attempts (0 s, 5 s, 15 s apart); the run stops before anything is copied if all three fail, and the next cron run retries |
| SSH to GitHub depends on the shell's agent or an interactive prompt | `DEPLOY_SSH_KEY` sets `GIT_SSH_COMMAND` with `IdentitiesOnly`, `BatchMode=yes` and a 20 s connect timeout: cron never waits on a prompt |
| two runs in the same minute | one `flock` on `~/deploy/.lock`; the second run logs `another deploy is running, skipping` and exits 0 |
| the checkout and the served folder are the same directory, so no marker is ever written where PHP reads it | the script refuses to run (exit 2, `REFUSED:` in the log) when `DEPLOY_WORKDIR` equals the web root, is inside it, or contains it |
| server paths set in the cron line or by editing the script | `~/deploy/pull-deploy.conf` (plain `KEY=value`, no secrets) next to the script; a variable already in the environment wins over the file |
| a failed deploy could leave files that a later commit deletes | deletions are computed against the last copied commit as well as the last successful marker |
| rollback | `DEPLOY_REF=<commit or tag>` deploys that commit; the next run without it redeploys the tip of `main` |

Verified in a local simulation (bare repository as origin, a web root holding `inc/env.local.php`, `cache/runtime.json` and a hand-placed
file, MariaDB 10.11 with the 34 migrations applied): first deploy, silent no-op rerun, marker written `0644`, health page reads the commit,
a commit that changes and deletes files, two runs started in the same second (one deploys, one skips), rollback to the base commit and
forward again, the three path-guard refusals, a scratch-folder run with `DEPLOY_MIGRATE=0`, a fetch failure (three attempts, 20 s, marker
untouched), a broken migration (`.deploy-error` written, marker untouched, health page shows `deployError`) and the fix that follows
(marker advances, error cleared, the broken file removed from the web root). The secrets file, `cache/` and the hand-placed file survived
every run; `TN_BULK_ENABLED=1` and `TN_BULK_READ=0` stayed as written.

## Step 1. Audit (read-only, 5 minutes)

```bash
# 1a. Which folder does nginx serve? This is DEPLOY_WEBROOT. Expect .../htdocs/beta.seatoutlet.com or .../beta.seatoutlet.com/seat-outlet
grep -rn 'root ' /etc/nginx/sites-enabled/*seatoutlet* 2>/dev/null || grep -rn 'root ' /home/seatoutlet-beta/htdocs/*/.cloudpanel 2>/dev/null
ls -la /home/seatoutlet-beta/htdocs/beta.seatoutlet.com | head -20

# 1b. The current job and the script it runs (paste both outputs back; the script header tells where it checks out and copies)
crontab -l
head -60 /home/seatoutlet-beta/htdocs/github.sh

# 1c. Is the served folder a git checkout? (a .git here means github.sh pulls straight into the live site)
for d in /home/seatoutlet-beta/htdocs/beta.seatoutlet.com /home/seatoutlet-beta/htdocs/beta.seatoutlet.com/seat-outlet; do
  printf '%s: ' "$d"; [ -d "$d/.git" ] && (cd "$d" && git rev-parse --short HEAD && git remote -v | head -1) || echo "not a checkout"
done

# 1d. Where the secrets and the runtime cache live (never moved or edited by this plan)
ls -la /home/seatoutlet-beta/htdocs/beta.seatoutlet.com/inc/env.local.php /home/seatoutlet-beta/htdocs/beta.seatoutlet.com/seat-outlet/inc/env.local.php 2>/dev/null
du -sh /home/seatoutlet-beta/htdocs/beta.seatoutlet.com/cache /home/seatoutlet-beta/htdocs/beta.seatoutlet.com/seat-outlet/cache 2>/dev/null

# 1e. The failing fetch: what does GitHub answer from cron's point of view? (no agent, no prompt)
ssh -o BatchMode=yes -o IdentitiesOnly=yes -i ~/.ssh/seatoutlet_deploy -T git@github.com 2>&1 | head -2
ls -la ~/.ssh; grep -A3 'github.com' ~/.ssh/config 2>/dev/null
tail -5 /home/seatoutlet-beta/htdocs/github-auto-deploy.log
```

Reading the result:

- `1a` names the live web root. Everything below uses `WEBROOT=` with that value; the examples assume `/home/seatoutlet-beta/htdocs/beta.seatoutlet.com/seat-outlet`, which is the checkout the current job pulls into. If `1a` prints the parent folder instead, use that.
- `1e` must print `Hi M-anil111/seat-outlet! You've successfully authenticated` for the deploy key. `Permission denied (publickey)` means the key is not on the repository (GitHub, repository Settings, Deploy keys, read-only); `Connection timed out` or `Connection reset` means the fetch failures are network, and the retry handles them. A key with a passphrase cannot be used from cron: create a new one without (`ssh-keygen -t ed25519 -N "" -f ~/.ssh/seatoutlet_deploy`).

## Step 2. Install the new job beside the old one (nothing live changes)

```bash
mkdir -p ~/deploy
# fresh checkout for the deploy job (NOT the served folder)
git clone --quiet --branch main --single-branch git@github.com:M-anil111/seat-outlet.git ~/deploy/checkout
cp ~/deploy/checkout/deploy/pull-deploy.sh ~/deploy/pull-deploy.sh && chmod 0755 ~/deploy/pull-deploy.sh
cat > ~/deploy/pull-deploy.conf <<'CONF'
DEPLOY_WEBROOT=/home/seatoutlet-beta/htdocs/beta.seatoutlet.com/seat-outlet
DEPLOY_WORKDIR=/home/seatoutlet-beta/deploy/checkout
DEPLOY_SSH_KEY=/home/seatoutlet-beta/.ssh/seatoutlet_deploy
DEPLOY_MIGRATE=1
CONF
chmod 0640 ~/deploy/pull-deploy.conf
```

Put the web root from step 1a in `DEPLOY_WEBROOT`. The file holds paths only, never a password or key material.

## Step 3. Validate against a scratch folder (nothing live changes)

```bash
DEPLOY_WEBROOT=/home/seatoutlet-beta/deploy/validate-webroot DEPLOY_MIGRATE=0 ~/deploy/pull-deploy.sh; echo "exit=$?"
cat ~/deploy/validate-webroot/.deployed-commit; ls ~/deploy/validate-webroot | head
DEPLOY_WEBROOT=/home/seatoutlet-beta/deploy/validate-webroot DEPLOY_MIGRATE=0 ~/deploy/pull-deploy.sh; echo "exit=$? (second run: no output, no change)"
# the path guard, on purpose: both must print REFUSED and exit 2
DEPLOY_WORKDIR=/home/seatoutlet-beta/htdocs/beta.seatoutlet.com/seat-outlet ~/deploy/pull-deploy.sh; echo "exit=$?"
# the live comparison: what the switch would change. Expect only files that differ between the served folder and main.
diff -rq --exclude=.git --exclude=cache --exclude=env.local.php --exclude=.deployed-commit ~/deploy/checkout /home/seatoutlet-beta/htdocs/beta.seatoutlet.com/seat-outlet | grep -v '^Only in /home/seatoutlet-beta/deploy/checkout: \(deploy\|docs\|\.github\|CONTRIBUTING.md\|composer\)' | head -40
# the migration check without applying anything: pending files are listed on the health page
curl -s https://beta.seatoutlet.com/ajax/health.php | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo "pending migrations: ", json_encode($j["migrations"]["pendingNames"] ?? null), "\n";'
rm -rf ~/deploy/validate-webroot
```

Pass criteria: first run prints `deploying none -> <sha> into .../validate-webroot` and `deployed <sha>`, the marker holds the 40-character
commit of `main`, the second run prints nothing, both guard runs print `REFUSED` with exit 2, the `diff` lists nothing unexpected.

## Step 4. First deploy into the live web root (**needs approval**: this is the first time the new job touches the site)

The live folder already holds the current code; the only differences are the files `main` changed since the last successful pull plus the
marker, so the visible effect is the same as one `github.sh` run plus a readable `.deployed-commit`.

```bash
# stop github.sh from running at the same time, without removing it: comment its line out (undo in step 6 if needed)
crontab -l > ~/deploy/crontab.before && crontab -l | sed 's|^\(.*github.sh main.*\)$|#\1|' | crontab - && crontab -l | grep github.sh
# wait for any github.sh run to finish, then deploy once with migrations OFF
sleep 130; pgrep -af github.sh || echo "no github.sh running"
DEPLOY_MIGRATE=0 ~/deploy/pull-deploy.sh; echo "exit=$?"
cat /home/seatoutlet-beta/htdocs/beta.seatoutlet.com/seat-outlet/.deployed-commit
curl -s https://beta.seatoutlet.com/ajax/health.php | head -c 300; echo
```

`commit` on the health page must now show the first 8 characters of the marker and `commitMarker: ok`. If the health page still says
`missing`, `DEPLOY_WEBROOT` is not the served folder: fix the conf (step 1a) and rerun. If it says `unreadable`, run
`chmod 0644 .../.deployed-commit` once (the script writes 0644 from now on).

Migrations: `pendingNames` on the health page lists what `php db/migrate.php` would apply. Run it only with approval:

```bash
cd /home/seatoutlet-beta/htdocs/beta.seatoutlet.com/seat-outlet && php db/migrate.php     # needs approval; additive only (tables and columns are added, never dropped)
```

## Step 5. Switch the cron (**needs approval**)

One line replaces the other in a single `crontab -` write, so there is never a moment with both jobs active, and never a gap:

```bash
crontab -l | sed 's|^#*.*github.sh main.*$|*/2 * * * * /home/seatoutlet-beta/deploy/pull-deploy.sh >> /home/seatoutlet-beta/deploy/deploy.log 2>\&1|' | crontab -
crontab -l
```

The hourly Bulk API line and every other line stay as they are. Then confirm the loop end to end:

```bash
tail -f ~/deploy/deploy.log          # a new line only when main moves; otherwise silence every 2 minutes
```

Merge any small change on GitHub (or an empty commit from a laptop: `git commit --allow-empty -m "deploy check" && git push`), wait up to
2 minutes, and check `curl -s https://beta.seatoutlet.com/ajax/health.php | head -c 120`: `commit` equals `git log -1 --format=%h origin/main`.

## Step 6. Rollback

| Situation | Action |
|---|---|
| the new job misbehaves after the switch | restore the saved crontab: `crontab ~/deploy/crontab.before` (github.sh is back within 2 minutes; the files it manages were never moved) |
| a bad commit reached the site | durable: `git revert <sha>` on `main`, merged; the next run redeploys. Immediate: `DEPLOY_REF=<good sha> ~/deploy/pull-deploy.sh` (the next cron run without `DEPLOY_REF` redeploys the tip, so revert on `main` as well) |
| a migration failed | the code is live and the marker is not advanced; `deployError` on the health page carries the line. Fix the migration file on `main`; the next run retries. Migrations are additive, so rolling the code back is safe. |
| the job must be stopped | `crontab -l | sed 's|^\*/2 \* \* \* \* /home/seatoutlet-beta/deploy/pull-deploy.sh|#&|' | crontab -` |

## After the switch

- `github.sh` and its log can be left in place for a week, then removed with the owner's approval.
- A fix merged to `deploy/pull-deploy.sh` reaches `~/deploy/pull-deploy.sh` by itself on the next deploy (self-update).
- `~/deploy/deploy.log` grows only when `main` moves; rotate with `logrotate` or truncate monthly.
