# inrainbowfy

Turns your text into the cover of Radiohead's *In Rainbows*. Type up to seven lines and press **inrainbowfy**: each line is upper-cased, trimmed to fit, sprinkled with one random `/` or `_` like the original cover, padded with random spaces, and drawn in the cover's seven colors.

It starts with 7 random phrases from a built-in list (`$phrasePool` at the top of `index.php`; add your own). **randomize** picks new phrases and shuffles the order, but always keeps any lines you typed yourself. **inrainbowfy** keeps your text as is and re-rolls the spacing and the `/` / `_` placement.

Live at <https://www.mmajor.com/ir/>.

## Running it

It's a single PHP page with no dependencies (works on PHP 7 and 8). Serve the folder with any PHP-capable web server, or locally:

```
php -S localhost:8000
```

then open <http://localhost:8000/>.

### Background image

The page expects the cover artwork, with its lettering removed, at `images/irbg3.jpg` (about 860×860 px). The artwork is copyrighted, so it isn't included in this repo and `images/` is git-ignored. Supply your own copy. Without it, the text still renders on a plain background.

### Transport font (optional)

The real cover is set in Transport, the UK road-sign typeface. A free Transport Medium is available from [roads.org.uk](https://www.roads.org.uk/fonts) ([direct zip](https://www.roads.org.uk/sites/default/files/pages/fonts/transportmedium.zip)). Its site says the files are intended for private, non-commercial use, so it isn't redistributed here and `fonts/transportmedium.ttf` is git-ignored. To use it, unzip it and save `Transport Medium.ttf` as `fonts/transportmedium.ttf`.

Without it, the cover is drawn entirely in Roadgeek 2005 at the same size.

## Files

| Path | What it is |
|---|---|
| `index.php` | The whole app. The cover is one SVG, and each line is stretched to exactly the same width with `textLength`. Scales to phone screens, and has a **save as PNG** button. Layout constants are at the top of the file. |
| `fonts/roadgeek2005lowline.*` | Roadgeek 2005, in woff2, woff and ttf. |
| `fonts/transportmedium.ttf` | Transport Medium, if you add it (see above). |

## Fonts

Lines are drawn in Transport Medium where it has the character. This free version only has plain letters, digits and basic punctuation: no `_`, no accented letters. Those come from Roadgeek 2005, scaled up 129.4% so its capitals match Transport's height. `$fontFaces` in `index.php` lists exactly which characters come from Transport.

Transport Medium is subject to Crown Copyright and contains public sector information licensed under the [Open Government Licence v1.0](https://www.nationalarchives.gov.uk/doc/open-government-licence/version/1/). The free font file is by Nathaniel Porter, via roads.org.uk.

Roadgeek 2005 Engschrift, Copyright (c) Michael D. Adams, 2005. All rights reserved. "This font may be freely distributed and used provided copyright notifications remain intact." Web font files were generated with Font Squirrel.
