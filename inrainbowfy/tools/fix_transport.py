# Shift Transport Medium's glyphs down onto the baseline, fix its cap height,
# drop glyphs that are mapped but blank, and save as woff2.
import sys
from fontTools.ttLib import TTFont
src, dst = sys.argv[1], sys.argv[2]
f = TTFont(src)
glyf = f['glyf']; upm = f['head'].unitsPerEm
h = glyf['H']; h.recalcBounds(glyf)
shift = -h.yMin                       # letters float this far above the baseline
print('upm', upm, 'shift', shift)
for name in f.getGlyphOrder():
    g = glyf[name]
    if g.numberOfContours > 0:        # simple glyphs; composites follow their parts
        g.coordinates.translate((0, shift))
for name in f.getGlyphOrder():
    glyf[name].recalcBounds(glyf)
h = glyf['H']
f['OS/2'].sCapHeight = h.yMax
x = glyf['x']; x.recalcBounds(glyf); f['OS/2'].sxHeight = x.yMax
# remove cmap entries that point at blank glyphs, so browsers fall back for them
blank = {n for n in f.getGlyphOrder() if glyf[n].numberOfContours == 0 and n not in ('space', 'nbspace', 'uni00A0')}
for t in f['cmap'].tables:
    for cp in [cp for cp, n in t.cmap.items() if n in blank and cp not in (0x20, 0xA0)]:
        del t.cmap[cp]
f['name'].setName('Transport Medium (baseline fixed)', 4, 3, 1, 0x409)
f.flavor = 'woff2'
f.save(dst)
g = TTFont(dst)
print('H after:', g['glyf']['H'].yMin, g['glyf']['H'].yMax, 'cap ratio', g['glyf']['H'].yMax / upm)
cm = g.getBestCmap(); print('_ mapped:', 0x5F in cm, ' Ø mapped:', 0xD8 in cm, ' É mapped:', 0xC9 in cm, ' A mapped:', 0x41 in cm, ' space:', 0x20 in cm)
