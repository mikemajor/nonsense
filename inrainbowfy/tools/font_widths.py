#!/usr/bin/env python3
"""Write a font's character advance widths (in em) as JSON, for index.php's
line-width check.

    font_widths.py fonts/transportmedium.woff2 fonts/transportmedium-widths.json
    font_widths.py fonts/roadgeek2005lowline.ttf fonts/roadgeek2005lowline-widths.json

Needs: pip install fonttools brotli
"""
import json
import sys
from fontTools.ttLib import TTFont

src, dst = sys.argv[1:3]
f = TTFont(src)
upm = f['head'].unitsPerEm
hmtx = f['hmtx']
glyf = f['glyf'] if 'glyf' in f else None
widths = {}
for cp, name in sorted(f.getBestCmap().items()):
    if glyf is not None and glyf[name].numberOfContours == 0 and cp not in (0x20, 0xA0):
        continue                      # blank glyph: the browser falls back for it
    widths[chr(cp)] = round(hmtx[name][0] / upm, 4)
with open(dst, 'w', encoding='utf-8') as out:
    json.dump(widths, out, ensure_ascii=False, separators=(',', ':'))
print(dst, len(widths), 'characters')
