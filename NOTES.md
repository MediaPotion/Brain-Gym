# Brain Gym notes

Decision log, newest first. One entry per session. Grep by date; don't read it whole.

## 2026-11-14
- Added project-session docs: CLAUDE.md, ARCHITECTURE.md, HANDOFF.md, ROADMAP.md, NOTES.md, scripts/check.sh.

## 2026-10-25 (v1.6–1.7)
- Photo Match family game: same iPad or online with a 4-letter code. Online runs through `api.php` on Hostinger (PHP + JSON files) instead of Firebase, so Nick has nothing to set up.
- Find the middle (line bisection). Its average tap offset is the main left-side progress measure, shown as a gauge on Progress.
- Same or mirror (mental rotation with chiral block shapes).
- Discussed Screwdom 3D: fine as a side game, not therapy (small targets, ads, no tracking).

## 2026-10-17 (v1.0–1.5)
- Built as "Daily Practice", renamed **Brain Gym** (Nick's pick). Focus is right-hemisphere deficits: left neglect, spatial skills, attention, reading tone and intent. Constant Therapy and Tactus are mostly aphasia (left-brain), so they're a different focus.
- Hosting: no Claude account for Dad, so it's on Hostinger at mediapotion.net/braingym, auto-deployed from GitHub.
- My routines quiz: wood-stove fire safety + stove burner check with a drawn stove.
- Follow the circle: tracking with on/off sound and a red line from finger to target. Circle radius is 90% of the original (Nick: 75% was too small).
- Daily photo reveal: 12 tiles uncover as exercises finish.
- Bug: `AREAS[i]` lookups broke when "Hand and eye" was inserted, crashing Start today's workout (v1.2–1.4). Fixed by using area names.
