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

The background is Stanley Donwood's artwork for *In Rainbows* (2007) with the lettering removed: the dark splattered image with the blue arc and the orange and yellow blob. The page expects it at `images/irbg3.jpg`, square, about 860×860 px or larger.

The artwork is copyrighted (© Stanley Donwood / Radiohead), so it isn't included in this repo and `images/` is git-ignored. To get a copy:

- search for "In Rainbows cover without text" or "In Rainbows textless artwork"; text-free versions are around, or
- take a scan of the cover and paint out the lettering in an image editor (the copy used on the live site was made that way, in Paint.NET).

Donwood's own site, [slowlydownward.com](https://www.slowlydownward.com/), has more of his Radiohead work. Without the image, the text renders on a plain dark background.

### Transport font (optional)

The real cover is set in Transport, the UK road-sign typeface. A free Transport Medium is available from [roads.org.uk](https://www.roads.org.uk/fonts) ([direct zip](https://www.roads.org.uk/sites/default/files/pages/fonts/transportmedium.zip)). Its site says the files are intended for private, non-commercial use, so it isn't redistributed here and `fonts/transportmedium.woff2` is git-ignored.

To set it up, run:

```
tools/setup_fonts.sh
```

It downloads Transport Medium, builds `fonts/transportmedium.woff2` and its width table, and cleans up after itself. It needs `curl`, `unzip` and `python3`, and installs `fonttools` into a temporary environment.

The downloaded file has defects, and no accented letters. `tools/build_transport.py` (run by the setup script) fixes the defects and adds the missing letters:

- every letter floats about 0.1em above the baseline: moved down onto it
- some characters it lists (like `_` and `Ø`) are blank: rebuilt or removed
- adds 76 upper-case letters: the accented capitals of Latin-1 and Latin Extended-A (Western, Central and Eastern European, Turkish, Baltic), each Transport's own letter plus an accent mark borrowed from Roadgeek 2005, and `Ø`, `Ł`, `Đ`, `Ð` and `_` drawn in Transport's stroke weight

Without it, the cover is drawn entirely in Roadgeek 2005 at the same size.

## Files

| Path | What it is |
|---|---|
| `index.php` | The whole app. The cover is one SVG, and every line is justified to exactly the same width by widening only the gaps between letters (`textLength` with `lengthAdjust="spacing"`), so letters keep their real shape. Lines are trimmed to fit: at most 13 characters, and letters are dropped from the end of a line that's still too wide (say, lots of `W`s), using the fonts' width tables. Scales to phone screens, and has a **save as PNG** button. Layout constants are at the top of the file. |
| `fonts/roadgeek2005lowline.woff2` | Roadgeek 2005, the web font the page loads. |
| `fonts/roadgeek2005lowline.ttf` | Roadgeek 2005, original file (keeps its copyright notice); the font tools read it. |
| `fonts/transportmedium.woff2` | Transport Medium, built by `tools/build_transport.py`, if you add it (see above). |
| `fonts/*-widths.json` | Each font's character widths, used to trim lines that are too wide. Made by `tools/font_widths.py`. |
| `tools/setup_fonts.sh` | One step: downloads Transport Medium and runs the two scripts below. |
| `tools/font_widths.py` | Writes a font's character widths as JSON. |
| `tools/build_transport.py` | Fixes Transport's baseline and blank characters, adds accented capitals and `Ø Ł Đ _`, saves woff2. |

## Fonts

Lines are drawn in Transport Medium (as built above). Input is upper-cased, including accented letters, so only capitals matter. Characters the build still lacks (`ß`, `Æ`, `Œ`, letters with a breve or ogonek such as `Ğ` and `Ą`, and most symbols) come from Roadgeek 2005, scaled up 111.4% so its capitals match Transport's height.

Transport Medium is subject to Crown Copyright and contains public sector information licensed under the [Open Government Licence v1.0](https://www.nationalarchives.gov.uk/doc/open-government-licence/version/1/). The free font file is by Nathaniel Porter, via roads.org.uk.

Roadgeek 2005 Engschrift, Copyright (c) Michael D. Adams, 2005. All rights reserved. "This font may be freely distributed and used provided copyright notifications remain intact." Web font files were generated with Font Squirrel.
