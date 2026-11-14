# Brain Gym handoff

**Status:** v1.11 (Nov 14, 2026) pushed to GitHub. Last version Nick confirmed on the site: v1.3 (Oct 17).

## Built but not confirmed on the real site or iPad
- **v1.5–1.10 reaching the site.** Check Settings shows `Version 1.11`.
- **Daily plan and My report** (v1.11): does the plan length (7 a day) and the weekly goals suit him? Both are in Settings. Try Share this report on the iPad: it should open the share sheet so he can send it to Nick by Messages.
- **Feedback button needs a GitHub token on Hostinger** before notes reach ROADMAP.md (until then they wait on the server). Steps:
  1. GitHub > Settings > Developer settings > Fine-grained tokens > Generate. Repository access: only `MediaPotion/Brain-Gym`. Permissions: Contents, Read and write. Note the expiry date; notes queue up again when it expires.
  2. Hostinger File Manager: open the folder that contains `public_html` (one level up, not inside it). Create `braingym-secret.php` containing `<?php return ['github_token' => 'PASTE_TOKEN_HERE'];`
  3. Send a test note from the app. ROADMAP.md on GitHub should show it under "Feedback from the app" within a few seconds.
- **Read line by line on the iPad** (v1.8): text size, tap targets, and whether the questions feel too easy or too hard for him.
- **Listen and find on the iPad** (v1.9): can he tell which side the beep comes from? Regular iPads have stereo speakers only in landscape; headphones always work.
- **Online Photo Match on Hostinger.** Tested locally with PHP 8.3 only. Quick check: open `mediapotion.net/braingym/api.php?room=ZZZZ`. It should show `{"error":"no such room"}`. If it doesn't, Hostinger isn't running the PHP or can't write to `rooms/`.
- **Photo reveal with real photos.** `photos/list.json` is still `[]`, so the reveal and photo cards are switched off until photos arrive.
- **Doc blocking.** `.htaccess` should make `mediapotion.net/braingym/HANDOFF.md` return 403. Check it once.
- **Sounds on the iPad.** iOS needs a first tap to unlock audio, which is handled in code but untested on a device.

## Next
1. Nick to confirm v1.11 is live, set up the feedback token, and the `api.php` check passes.
2. Add family photos. Nick sends them, Claude resizes them to ~1600px JPEG, adds captions (who + roughly when), and updates `list.json`.
3. Test Photo Match online: Nick plus another device, then Dad with his grandkid over FaceTime.
4. Hemianopia work (see ROADMAP.md "Hemianopia"): get his visual-field printout, then quick-glance practice (sound-cued search is built).
5. More personal routines as Nick names them (see ROADMAP.md).

## Gotchas
- Never rename `dailypractice.v1` or move the site address. Either one wipes his progress.
- Exercise `area` must be a string matching `AREAS` exactly (index-based lookups crashed the workout in v1.2–1.4).
- Fire questions assume a wood stove or fireplace, and the stove drawing assumes 4 coil burners with knobs along the front. Neither is confirmed by Nick.
- Photo Match grids differ by device (landscape vs. portrait), so "top left" isn't the same card on both screens.
- Playwright treats flat SVG lines as invisible. Use `state='attached'` when waiting on `.bline`.
- GitHub redirects `brain-gym` to `Brain-Gym`. Use the capitalized remote URL.
- Read line by line passages have fixed line breaks so the app knows which words start each line. Each passage needs a left and a right question within lines 0–3.
- The app commits to `main` (feedback). `git pull` before working or pushes will be rejected.
- Feedback notes are public on GitHub. The server strips links, markdown and @mentions, but anything he types is visible.
- 800-round history cap is about 16 weeks at 7 a day. Fine for the report (4 weeks) but long-term charts lose the oldest rounds.
- Scores live only on his iPad (Share this report is the way out for now). Nick can't see them remotely yet.
