# inrainbowfy

Turns your text into the cover of Radiohead's *In Rainbows*. Type up to seven lines and press **inrainbowfy**: each line is upper-cased, trimmed to fit, sprinkled with one random `/` or `_` like the original cover, padded with random spaces, and drawn in the cover's seven colors.

Live at <https://www.mmajor.com/ir/>.

## Running it

It's a single PHP page with no dependencies (works on PHP 7 and 8). Serve the folder with any PHP-capable web server, or locally:

```
php -S localhost:8000
```

then open <http://localhost:8000/>.

### Background image

The page expects the cover artwork, with its lettering removed, at `images/irbg3.jpg` (about 860×860 px). The artwork is copyrighted, so it isn't included in this repo and `images/` is git-ignored. Supply your own copy. Without it, the text still renders on a plain background.

## Files

| Path | What it is |
|---|---|
| `index.php` | The whole app. The cover is one SVG, and each line is stretched to exactly the same width with `textLength`. Scales to phone screens, and has a **save as PNG** button. Layout constants are at the top of the file. |
| `styles/stylesheet.css` | `@font-face` for the font and the seven cover colors. |
| `fonts/roadgeek2005lowline.*` | The font, in woff2, woff and ttf. |

## Font

Roadgeek 2005 Engschrift, Copyright (c) Michael D. Adams, 2005. All rights reserved. "This font may be freely distributed and used provided copyright notifications remain intact." Web font files were generated with Font Squirrel.
