# Pull-based deploy (beta)

GitHub-hosted runners cannot reach the beta server's SFTP port (4124): the firewall drops them, and GitHub publishes thousands of changing IP ranges. Instead of opening the port, the server pulls the code itself. Only outbound HTTPS/SSH to github.com is needed, and the site stays where it is hosted today.

## How it works

`deploy/pull-deploy.sh` runs from cron every 2 minutes on the server. If `main` has a new commit it copies the code into the web root, applies database migrations (`php db/migrate.php`) and records the commit in `.deployed-commit`. If nothing changed it exits in about a second.

It never touches `inc/env.local.php` (the server's own secrets) and never overwrites files already in `cache/`. It only adds and overwrites files, never deletes. Tested locally with a bare repo: first deploy, no-op re-run, secrets and cache preserved, no `.git` in the web root.

## One-time server setup (about 10 minutes, by whoever has SSH to the beta site user)

1. Create the secrets file `inc/env.local.php` in the web root from `deploy/env.local.php.example` (values are the ones stored as GitHub repository secrets).
2. Create a read-only deploy key and give the public half to the repo owner:
   ```
   ssh-keygen -t ed25519 -N "" -f ~/.ssh/seatoutlet_deploy -C "seatoutlet beta deploy"
   cat ~/.ssh/seatoutlet_deploy.pub
   printf 'Host github.com\n  IdentityFile ~/.ssh/seatoutlet_deploy\n  IdentitiesOnly yes\n' >> ~/.ssh/config
   ssh-keyscan github.com >> ~/.ssh/known_hosts
   ```
   The repo admin adds it under GitHub, repository Settings, Deploy keys, **without** write access.
3. Put the script in place and add the cron line:
   ```
   mkdir -p ~/deploy
   git clone git@github.com:M-anil111/seat-outlet.git /tmp/so && cp /tmp/so/deploy/pull-deploy.sh ~/deploy/ && rm -rf /tmp/so
   crontab -l 2>/dev/null; (crontab -l 2>/dev/null; echo '*/2 * * * * /home/seatoutlet-beta/deploy/pull-deploy.sh >> /home/seatoutlet-beta/deploy/deploy.log 2>&1') | crontab -
   ```
4. Also add the app's own crons (see CONTRIBUTING.md) **as commands** (`php /path/cron/...`), not by URL: web requests to `cron/*` and `db/migrate.php` are now refused (403) unless `CRON_TOKEN` is set in `inc/env.local.php` and passed as `?token=`. Schedules: `cron/home-*.php` hourly, `cron/warm-listings.php` every 5 minutes, `cron/resolve-images.php` every 10 to 15 minutes, `cron/geoip-update.php` weekly, `cron/build-search-vocab.php` daily.

After that, every merge to `main` reaches beta within about two minutes with no further action. Check `~/deploy/deploy.log`.

## Rollback

`DEPLOY_BRANCH` points at a branch or tag; to roll back, revert on `main` (the next pull redeploys). The script does not delete files, so a rolled-back release can leave newer files behind; they are unused.

## Security notes

- The deploy key is read-only, so a stolen server cannot push to the repo.
- No inbound firewall change and no self-hosted runner.
- `inc/env.local.php` lives only on the server.
