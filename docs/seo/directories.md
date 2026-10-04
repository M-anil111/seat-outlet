# A to Z directories (separate lists instead of one)

Before: one page, `/all-artists-and-teams`, listed every artist, team and show together. Now each type has its own crawlable list, with its own title, description, focus keyword and SEO copy. All of them are one template (`all-artists-and-teams.php`) driven by `inc/directories.php`; each URL is a small stub file that sets `$soDirKey` (same pattern as the genre pages).

| URL | Lists | Category path filter | Focus keyword | Volume | KD |
|---|---|---|---|---|---|
| /all-artists-and-teams | everything (hub, links to all below) | none | all artists | 390 | 38 |
| /concert-artists | artists and bands (includes comedy and festivals, which TicketNetwork files under concerts) | `.1859.1986.` | artists on tour | 1,600 | 69 |
| /sports-teams | all teams | `.1859.1988.` | sports teams | 1,500 | 66 |
| /nfl-teams | NFL | `.1859.1988.1879.1959.` | nfl teams | 165,000 | 94 |
| /nba-teams | NBA | `.1859.1988.1865.1971.` | nba teams | 135,000 | 92 |
| /mlb-teams | MLB | `.1859.1988.1864.1969.` | mlb teams | 60,500 | 95 |
| /nhl-teams | NHL | `.1859.1988.1883.1972.` | nhl teams | 49,500 | 80 |
| /mls-teams | MLS | `.1859.1988.1913.1970.` | mls teams | 12,100 | 93 |
| /broadway-shows | musicals, plays, touring shows | `.1859.1989.` | broadway shows list | 440 | 16 |
| /comedians-on-tour | comedians | `.1859.1986.1872.` | comedians on tour | 2,400 | 61 |
| /music-festivals-list | festivals | `.1859.1986.1877.` | music festivals list | 260 | 36 |

Volume and KD: SE Ranking, US, 4 Oct 2026. The league and "teams" terms are very competitive; the pages are built to be useful and to pass authority to the team pages, not because they will rank next month. "broadway shows list" (KD 16) and "who is on tour" style terms are the realistic early wins.

How it works
- Each letter (`?letter=A`) and page (`?page=2`) is its own address with its own title and description; the plain list takes its title from the keyword plan.
- Only names with tickets on sale are listed (`getTnPerformers()` adds `_metadata/hasTickets eq true`).
- The category filter is `startswith(defaultCategory/path, ...)`, the same field the events endpoint filters on. If TicketNetwork ever rejects it for performers, the page shows its normal "could not load" state and the CI check still passes: verify a directory on live after any API change.
- Every directory page links to all the others ("Browse by type"), the menu has a column for each group, and the footer links the main three.
- `tools/check-seo-titles.php` (CI) checks every directory title (59 characters, no pipe, dash or colon) and description (120 to 155 characters).
