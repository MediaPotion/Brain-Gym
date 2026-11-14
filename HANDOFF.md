# Brain Gym handoff

**Status:** v1.7 (Oct 25, 2026) pushed to GitHub. Last version Nick confirmed on the site: v1.3 (Oct 17).

## Built but not confirmed on the real site or iPad
- **v1.5–1.7 reaching the site.** Check Settings shows `Version 1.7`.
- **Online Photo Match on Hostinger.** Tested locally with PHP 8.3 only. Quick check: open `mediapotion.net/braingym/api.php?room=ZZZZ`. It should show `{"error":"no such room"}`. If it doesn't, Hostinger isn't running the PHP or can't write to `rooms/`.
- **Photo reveal with real photos.** `photos/list.json` is still `[]`, so the reveal and photo cards are switched off until photos arrive.
- **Doc blocking.** `.htaccess` should make `mediapotion.net/braingym/HANDOFF.md` return 403. Check it once.
- **Sounds on the iPad.** iOS needs a first tap to unlock audio, which is handled in code but untested on a device.

## Next
1. Nick to confirm v1.7 is live and the `api.php` check passes.
2. Add family photos. Nick sends them, Claude resizes them to ~1600px JPEG, adds captions (who + roughly when), and updates `list.json`.
3. Test Photo Match online: Nick plus another device, then Dad with his grandkid over FaceTime.
4. More personal routines as Nick names them (see ROADMAP.md).

## Gotchas
- Never rename `dailypractice.v1` or move the site address. Either one wipes his progress.
- Exercise `area` must be a string matching `AREAS` exactly (index-based lookups crashed the workout in v1.2–1.4).
- Fire questions assume a wood stove or fireplace, and the stove drawing assumes 4 coil burners with knobs along the front. Neither is confirmed by Nick.
- Photo Match grids differ by device (landscape vs. portrait), so "top left" isn't the same card on both screens.
- Playwright treats flat SVG lines as invisible. Use `state='attached'` when waiting on `.bline`.
- GitHub redirects `brain-gym` to `Brain-Gym`. Use the capitalized remote URL.
- Scores live only on his iPad. Nick can't see them remotely yet.
