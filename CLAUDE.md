# Brain Gym: rules for Claude

Home brain-exercise app for Nick's dad (right-hemisphere stroke) on his iPad. Live at mediapotion.net/braingym.
Start every session by reading HANDOFF.md, then ARCHITECTURE.md. Grep `index.html` by line range; never read it whole.

## Rules
- **Never rename the saved-data key** `dailypractice.v1` or change the site address. Either one wipes his progress on the iPad.
- **Bump the version** in Settings (`Version X.Y, Month D, YYYY`, grep `Version 1.`) on every push. It's how Nick checks an update reached the iPad.
- **Exercise `area` values are strings that must match `AREAS` exactly.** Never use `AREAS[i]`: inserting an area once shifted the indexes and crashed the daily workout (v1.2–1.4).
- Adding an exercise to an existing area doesn't lengthen the workout (one exercise per area, least-recently-played). Adding an area does, and it also changes the photo reveal's `need`.
- Write for him, not about him: plain words, large targets (48px+), no timers where avoidable, never shaming feedback.
- Personal reminders go in `ROUTINE` (index.html, "personal routines" section). Ask Nick for what goes wrong, then write the safe answer plus two wrong ones.
- The whole repo deploys to the public site. Root `.htaccess` blocks `.md`/`.sh`, so keep anything private in those, never in `.json`, `.txt` or other files.
- Photos are public files on the site. Remind Nick before adding anything private.

## Verify before every commit
```
scripts/check.sh
```
Checks the inline script parses (node) and `api.php` lints (php -l). For behavior, run a headless Playwright pass at 1180×820 and 820×1180 (iPad landscape/portrait). Online play needs `php -S 127.0.0.1:8770` from the repo, since `api.php` must run.

## Deploy
Run `git pull` before starting work: the app's Feedback button commits notes to ROADMAP.md on GitHub, so the server may be ahead of this checkout.
Push to `main` on GitHub (MediaPotion/Brain-Gym). Hostinger Git auto-deploy puts it in `public_html/braingym`. Nothing else to do. Never point the deploy at `public_html` itself, because it would overwrite mediapotion.net.

## Commits
Author `Nick Gascho <info@mediapotion.net>`. Never commit `rooms/*.json` (live game files).
