# Brain Gym

Daily brain-exercise app for home practice after a right-hemisphere stroke, built for iPad.
The app is one file, `index.html`. Scores are saved in the browser on the device that uses it.

## Photos

Each workout reveals a family photo piece by piece. Photos live in `photos/`, listed in `photos/list.json`:

```json
[
  { "file": "cabin-christmas.jpg", "caption": "Christmas at the cabin, 2019" },
  "lake-dock.jpg"
]
```

- An entry is either a filename or an object with an optional `caption`, shown after the full reveal.
- Keep photos around 1600px on the long side and saved as JPEG so they load fast.
- The site is public, so anyone with a photo's exact address can open it. Search engines are told not to index the page.
