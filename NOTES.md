# Brain Gym notes

Decision log, newest first. One entry per session. Grep by date; don't read it whole.

## 2026-11-14 (v1.8–1.10)
- Feedback button (v1.10): always on screen, top right. Quick-tap tags + optional note. Nick chose to have notes go straight into ROADMAP.md on the public repo (told it's public). The server commits via the GitHub API with a token kept outside the repo; notes queue on the server and on the iPad if anything fails. Installed PHP via Homebrew on Nick's Mac for testing.
- Listen and find (v1.9): audiovisual search after Keller 2010 / Bolognini. A star appears with a noise burst panned to its side (noise is easier to place by ear than a tone). Two of three stars on the left. Levels fade the cue: repeating beep, then one beep, then lookalike distractors, then only every other star beeps. Hint after 8 s. Logs find time per side; Progress compares left vs. right.
- Dad has partial left homonymous hemianopia (stroke, May 2026). Research review: scanning training has low-certainty evidence and the 2025 SEARCH RCT found it no better than sham; reading training (Read-Right) has a modest sham-controlled benefit; blind-field discrimination training (Huxlin; JAMA Netw Open 2025 VR trial) can shrink defects but needs verified fixation, which an iPad web app can't do, so leave that to his eye specialist.
- Read line by line: tap the first word of each line, then read it. Trains the leftward return sweep, which is the main reading problem with left-field loss. Self-paced, no timer. Levels add lines and remove the red arrow and dimming. Two questions per passage: one answered at the start of a line, one at the end. Progress shows line starts found on the first try.
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
