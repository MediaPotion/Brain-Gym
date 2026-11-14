# Brain Gym architecture

A static single-page app plus one PHP file. No build step, no framework, no database.

## Files
| File | What it is |
|---|---|
| `index.html` | The whole app (~1,900 lines): CSS, then one inline script |
| `api.php` | Tiny server: online Photo Match rooms, plus the Feedback button (commits notes to ROADMAP.md through the GitHub API) |
| `photos/list.json` + `photos/*.jpg` | Family photos for the daily reveal and Photo Match |
| `rooms/` | Live online games, one JSON file each, deleted after a day. Also `feedback-pending.jsonl` (notes not yet on GitHub), `feedback-sent.jsonl`, `feedback-times.log` (rate limit). `.htaccess` blocks direct access |
| `braingym-secret.php` | **Not in the repo.** Lives on Hostinger in the folder above `public_html`. Returns `['github_token' => ...]` for feedback commits |
| `icon.png` | Home Screen icon (180px dumbbell) |
| `scripts/check.sh` | Syntax check for the script and PHP |
| `.htaccess` | Blocks `*.md`, `*.sh` and `scripts/` on the public site (these docs get deployed too) |

## index.html map (line numbers as of v1.8; grep the section banners `/* ---------- ` to re-find)
| Lines | Section |
|---|---|
| 13–331 | CSS. Color tokens at top (light + dark), iPad sizes in `@media (min-width:700px)` |
| 341–380 | Helpers: `h()` builds HTML, `s()` builds SVG, `screen()`, `later()`/`clearTimers()`, `stops` |
| 381–394 | Saved data: `S`, key `dailypractice.v1` in localStorage, `save()` |
| 395–437 | Sound and speech: `tone()`, `blip()`, `sideBeep(x)` (noise burst panned left/right), `say()`, `listenBtn()` |
| 438–459 | `choices()` multiple-choice buttons, `fresh()` no-repeat picker |
| 460–539 | Content banks: `WORDS`, `STEPS`, `MEAN` (sayings, hints, sarcasm, judgment) |
| 540–578 | **Personal routines** `ROUTINE` (fire, coffee burner) + stove drawing |
| 583–654 | `PASSAGES` for Read line by line (fixed line breaks + left/right questions) |
| 655 | `AREAS`, the workout sections in order |
| 670–1284 | `EX`, the exercises. Each has `id, area, name, what, trials, max, how(lv), run(lv, stage, finish)` |
| 1285–1342 | Daily photo reveal (12 tiles, one step per finished exercise) |
| 1343–1451 | Weekly goals and today's plan (`TARGETS`, `todayPlan`), then session flow: `pickSession` (fallback once the plan is done), `showIntro`, `showTrial`, `endBlock` (level up ≥85%, down <50%), `showSummary` |
| 1452–1600 | Home (plan checklist), My report (stat tiles, calendar, goals, share), Find the middle gauge, left/right bars, line starts, left/right find times, mini charts), Settings (version line) |
| 1601–1888 | Photo Match family game: deck, `commit()`/polling against `api.php`, setup screen, board |

## Daily plan and report (v1.11)
- `TARGETS`: times per week for each exercise id (Read line by line and Listen and find 5, Quick look 4, Find them all 3, routines 3, others 2). Settings can change them (`S.targets`).
- `todayPlan()`: once a day, picks the `S.settings.perDay` exercises with the lowest (done in the last 7 days ÷ goal), keeps EX order, saves `S.plan` and appends to `S.plans` (60 days, for the calendar). It only rebuilds the same day if nothing in it has been played (`resetPlanIfUntouched` after Settings changes).
- `planPart()`: with `S.settings.parts = 2`, the day splits into two halves ("Start part 1 of 2").
- Home shows the plan as a checklist. A plan item counts as done when that exercise was played that day, even from the "Any exercise" list.
- My report (`showProgress`): `reportTop()` with last-7-day stat tiles, a 4-week calendar (whole plan / some / none), each exercise's count vs. goal, and Share (`navigator.share`, falling back to copy). Then the older vision cards and mini charts.
- History entries now have `d`, seconds spent on that exercise (intro to finish, capped at 30 min). Older entries have none, so minutes start counting from v1.11.

## Exercises (id: area)
scan, word, bisect, read, seek, glance: Looking left and scanning · trail, alert: Attention · copy, mirror, clock: Space and shapes ·
track: Hand and eye · memory: Memory · steps, meaning: Thinking it through · routine: My routines

`finish(score 0–1, message, extras)`. Extras feed Progress: `L`/`R` arrays are left/right hits, and `bi` is the Find the middle offset (tenths of %, count). `ls` is Read line by line's line starts found on the first try (hits, lines). `tL`/`tR` are Listen and find times (tenths of a second, count) per side.

## Online play (api.php)
- `POST {create, state}` returns a 4-letter room. `GET ?room=X&since=v` returns the state, or 204 if nothing changed.
- `POST {room, base, state}` saves only if `base` matches the current version, otherwise 409 with the latest.
- Clients poll every second. Turn-based, so lag doesn't matter. Only the player whose turn it is can flip.
- Storage is `rooms/` (falls back to the system temp folder if it can't write there).

## Real-life practice (v1.14)
- `LIFE`: 24 short, safe tasks around the house that use the same skills (look left first, read whole lines). Home shows one a day (`lifeCard`, `S.lifeToday`), skipping the last 12 done. "A different one" moves to the next.
- "I did it" adds `{t, i}` to `S.life` (300 kept). The report counts it (tile, calendar "some practice", share text) and it syncs to the family report.

## Family report link (v1.13)
- The iPad makes a random key once (`braingym.reportkey`). After each exercise (and every 6 hours on open) `syncReport()` posts the last 5 weeks of history, plans, targets, levels and plan settings to api.php, which saves `rooms/reports/<key>.json`. No name is sent.
- `index.html?report=<key>` is viewer mode (`VIEW`): loads that data into `S`, never saves, no Feedback button, and shows My report with a Refresh button.
- Settings > Family report: on/off switch (`S.settings.shareReport`, default on) and "Share the family link" (share sheet, falling back to copy).
- The key is the only protection, so the link should stay private. `rooms/` is blocked from direct access, including `rooms/reports/`.

## Daily reminder (v1.12)
- Settings > Daily reminder: time input(s) (`S.settings.remind`, `remind2` when the day is in two parts) and a button that opens `api.php?reminder=1&d=YYYYMMDD&t=HH:MM[&t2=]`.
- api.php returns an .ics with one or two daily repeating events (floating local time, alert at start, link back to the app). iPadOS offers "Add All" to Calendar. Changing the time means deleting the event in Calendar and adding again.
- Home shows a "Set a daily reminder" button until `S.settings.remindSet`.

## Feedback button
- `#fbk`, fixed top right on every screen. `#app > :first-child` gets right padding so headers don't sit under it.
- Opens a dialog: 8 quick-tap tags (must match `FEEDBACK_TAGS` in both index.html and api.php) + an optional note. Adds where he was (`whereNow()`), version, local time.
- Client: queued in `braingym.outbox`, sent by `flushOutbox()` now, on the `online` event, and 3 s after load.
- Server: cleans the text (no markdown, links, HTML or @mentions, since the repo is public), rate limits to 10 an hour and 30 a day, appends to `rooms/feedback-pending.jsonl`, then commits every pending note under `<!-- feedback:start -->` in ROADMAP.md. Without a token, notes wait on the server and go out with the next note after the token is added.

## Saved on the iPad (localStorage)
`dailypractice.v1`: settings (incl. `perDay`, `parts`, `goalDays`), levels, `targets`, `plan`, `plans`, history (last 800 rounds), today's photo, recent photos, Photo Match bests.
`braingym.room`: the last online game, for Rejoin (6 hours).
`braingym.outbox`: feedback notes not yet sent.
`braingym.reportkey`, `braingym.reportSent`: family report key and last send time.

## Deploy
GitHub `main` deploys to Hostinger `public_html/braingym` via Git auto-deploy, at mediapotion.net/braingym.
