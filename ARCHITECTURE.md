# Brain Gym architecture

A static single-page app plus one PHP file. No build step, no framework, no database.

## Files
| File | What it is |
|---|---|
| `index.html` | The whole app (~1,900 lines): CSS, then one inline script |
| `api.php` | Tiny game server for online Photo Match (~100 lines) |
| `photos/list.json` + `photos/*.jpg` | Family photos for the daily reveal and Photo Match |
| `rooms/` | Live online games, one JSON file each, deleted after a day. `.htaccess` blocks direct access |
| `icon.png` | Home Screen icon (180px dumbbell) |
| `scripts/check.sh` | Syntax check for the script and PHP |
| `.htaccess` | Blocks `*.md`, `*.sh` and `scripts/` on the public site (these docs get deployed too) |

## index.html map (line numbers as of v1.8; grep the section banners `/* ---------- ` to re-find)
| Lines | Section |
|---|---|
| 13–331 | CSS. Color tokens at top (light + dark), iPad sizes in `@media (min-width:700px)` |
| 341–380 | Helpers: `h()` builds HTML, `s()` builds SVG, `screen()`, `later()`/`clearTimers()`, `stops` |
| 381–394 | Saved data: `S`, key `dailypractice.v1` in localStorage, `save()` |
| 395–437 | Sound and speech: `tone()`, `blip()`, `say()`, `listenBtn()` |
| 438–459 | `choices()` multiple-choice buttons, `fresh()` no-repeat picker |
| 460–539 | Content banks: `WORDS`, `STEPS`, `MEAN` (sayings, hints, sarcasm, judgment) |
| 540–578 | **Personal routines** `ROUTINE` (fire, coffee burner) + stove drawing |
| 583–654 | `PASSAGES` for Read line by line (fixed line breaks + left/right questions) |
| 655 | `AREAS`, the workout sections in order |
| 670–1284 | `EX`, the exercises. Each has `id, area, name, what, trials, max, how(lv), run(lv, stage, finish)` |
| 1285–1342 | Daily photo reveal (12 tiles, one step per finished exercise) |
| 1343–1451 | Session flow: `pickSession`, `showIntro`, `showTrial`, `endBlock` (level up ≥85%, down <50%), `showSummary` |
| 1452–1600 | Home, Progress (Find the middle gauge, left/right bars, line starts, mini charts), Settings (version line) |
| 1601–1888 | Photo Match family game: deck, `commit()`/polling against `api.php`, setup screen, board |

## Exercises (id: area)
scan, word, bisect, read: Looking left and scanning · trail, alert: Attention · copy, mirror, clock: Space and shapes ·
track: Hand and eye · memory: Memory · steps, meaning: Thinking it through · routine: My routines

`finish(score 0–1, message, extras)`. Extras feed Progress: `L`/`R` arrays are left/right hits, and `bi` is the Find the middle offset (tenths of %, count). `ls` is Read line by line's line starts found on the first try (hits, lines).

## Online play (api.php)
- `POST {create, state}` returns a 4-letter room. `GET ?room=X&since=v` returns the state, or 204 if nothing changed.
- `POST {room, base, state}` saves only if `base` matches the current version, otherwise 409 with the latest.
- Clients poll every second. Turn-based, so lag doesn't matter. Only the player whose turn it is can flip.
- Storage is `rooms/` (falls back to the system temp folder if it can't write there).

## Saved on the iPad (localStorage)
`dailypractice.v1`: settings, levels, history (last 800 rounds), today's photo, recent photos, Photo Match bests.
`braingym.room`: the last online game, for Rejoin (6 hours).

## Deploy
GitHub `main` deploys to Hostinger `public_html/braingym` via Git auto-deploy, at mediapotion.net/braingym.
