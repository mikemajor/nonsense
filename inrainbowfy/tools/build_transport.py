#!/usr/bin/env python3
"""Build the Transport web font for inrainbowfy.

    build_transport.py "Transport Medium.ttf" fonts/roadgeek2005lowline.ttf fonts/transportmedium.woff2

The free Transport Medium (roads.org.uk) has defects this fixes:
  * every glyph floats ~0.105em above the baseline -> moved down onto it
  * its OS/2 cap height is wrong (0.867em; really 0.746em) -> corrected
  * some mapped characters are blank (e.g. _ and O-slash) -> unmapped or rebuilt
and it has no accented letters, so this adds upper-case ones: Transport's base
letter plus the accent mark from Roadgeek 2005, scaled to Transport's size,
plus O-slash, L-stroke, D-stroke, Eth and underscore drawn in Transport's
stroke weight. (The page upper-cases input, so lower case isn't needed.)
Needs: pip install fonttools brotli
"""
import sys
import unicodedata
from fontTools.ttLib import TTFont
from fontTools.pens.recordingPen import DecomposingRecordingPen, RecordingPen
from fontTools.pens.transformPen import TransformPen
from fontTools.pens.ttGlyphPen import TTGlyphPen

src, rg_path, dst = sys.argv[1:4]
f = TTFont(src)
rg = TTFont(rg_path)
glyf, hmtx = f['glyf'], f['hmtx']
UPM = f['head'].unitsPerEm

def bounds(font, name):
    g = font['glyf'][name]; g.recalcBounds(font['glyf'])
    return g.xMin, g.yMin, g.xMax, g.yMax

# 1. move every glyph down onto the baseline
shift = -bounds(f, 'H')[1]
for name in f.getGlyphOrder():
    g = glyf[name]
    if g.numberOfContours > 0:       # simple glyphs; composites follow their parts
        g.coordinates.translate((0, shift))
for name in f.getGlyphOrder():
    glyf[name].recalcBounds(glyf)
    if glyf[name].numberOfContours != 0:
        hmtx[name] = (hmtx[name][0], glyf[name].xMin)

CAP = bounds(f, 'H')[3]                       # 1528 of 2048 = 0.746em
STEM = bounds(f, 'I')[2] - bounds(f, 'I')[0]  # vertical stem width
BAR = round(STEM * 0.85)                      # horizontal stroke weight

# 2. unmap characters whose glyphs are blank, so browsers fall back for them
cmaps = [t for t in f['cmap'].tables if t.isUnicode()]
orig = dict(f.getBestCmap())
blank = {n for n in f.getGlyphOrder() if glyf[n].numberOfContours == 0}
for t in cmaps:
    for cp in [cp for cp, n in t.cmap.items() if n in blank and cp not in (0x20, 0xA0)]:
        del t.cmap[cp]
tcmap = f.getBestCmap()

# 3. accent marks from Roadgeek, taken from its accented capitals (already
#    positioned for capitals); scaled so Roadgeek's 0.67em caps = Transport's caps
rg_gs, rg_cmap, rg_glyf = rg.getGlyphSet(), rg.getBestCmap(), rg['glyf']
RG_CAP = 670
K = CAP / RG_CAP
WIDEN = 1.25            # Transport is much wider than Roadgeek; widen marks a bit

def contours(rec):
    out, cur = [], []
    for op, args in rec:
        cur.append((op, args))
        if op in ('closePath', 'endPath'):
            out.append(cur); cur = []
    return out

def rg_mark(composite_char):
    """(mark contours, x-center of the base letter) from a Roadgeek composite."""
    name = rg_cmap[ord(composite_char)]
    base = rg_glyf[name].components[0].glyphName
    p = DecomposingRecordingPen(rg_gs); rg_gs[name].draw(p)
    b = DecomposingRecordingPen(rg_gs); rg_gs[base].draw(b)
    nbase = len(contours(b.value))
    bx0, _, bx1, _ = bounds(rg, base)
    return contours(p.value)[nbase:], (bx0 + bx1) / 2

MARKS = {   # combining mark -> Roadgeek accented capital to borrow it from
    '̀': 'À', '́': 'Á', '̂': 'Â', '̃': 'Ã', '̈': 'Ä',
    '̊': 'Å', '̌': 'Š', '̧': 'Ç',
}
marks = {m: rg_mark(c) for m, c in MARKS.items()}
# macron: Roadgeek's spacing macron, raised to sit where the other marks sit
mac = RecordingPen(); rg_gs[rg_cmap[0xAF]].draw(mac)
acute_bottom = min(pt[1] for c in marks['́'][0] for op, a in c for pt in a if pt)
mac_c = contours(mac.value)
mac_bottom = min(pt[1] for c in mac_c for op, a in c for pt in a if pt)
mac_x0, _, mac_x1, _ = bounds(rg, rg_cmap[0xAF])
marks['̄'] = ([[(op, [(x, y + acute_bottom + 25 - mac_bottom) if (x, y) else None for (x, y) in a] if a else a)
                     for op, a in c] for c in mac_c], (mac_x0 + mac_x1) / 2)
# dot above: one dot of the dieresis, centered
die, die_cx = marks['̈']
dot = [c for c in die if max(pt[0] for op, a in c for pt in a if pt) < die_cx]   # left dot
dot_cx = sum(pt[0] for c in dot for op, a in c for pt in a if pt) / max(1, sum(1 for c in dot for op, a in c for pt in a if pt))
marks['̇'] = (dot, dot_cx)

def draw_mark(pen, mark, base_cx, dx=0, sx=1.0):
    cs, ref_cx = mark
    # x' = (x - ref_cx) * K * WIDEN * sx + base_cx + dx ;  y' = y * K
    t = TransformPen(pen, (K * WIDEN * sx, 0, 0, K, base_cx + dx - ref_cx * K * WIDEN * sx, 0))
    for c in cs:
        for op, args in c:
            getattr(t, op)(*args)

def new_glyph(cp, draw, advance):
    pen = TTGlyphPen(f.getGlyphSet())
    draw(pen)
    g = pen.glyph()
    name = orig.get(cp) if orig.get(cp) in blank else 'uni%04X' % cp
    if name not in f.getGlyphOrder():
        f.setGlyphOrder(f.getGlyphOrder() + [name])
    glyf[name] = g
    g.recalcBounds(glyf)
    hmtx[name] = (advance, g.xMin)
    for t in cmaps:
        t.cmap[cp] = name

def draw_base(pen, base):
    f.getGlyphSet()[base].draw(pen)

added = []
# 3a. accented capitals: everything in Latin-1 + Latin Extended-A that is a
#     capital letter + supported marks
for cp in list(range(0xC0, 0xDF)) + list(range(0x100, 0x180)):
    ch = chr(cp)
    if cp in tcmap or unicodedata.category(ch) != 'Lu':
        continue
    d = unicodedata.normalize('NFD', ch)
    base_ch, combining = d[0], d[1:]
    if ord(base_ch) not in tcmap or not combining:
        continue
    if '̋' in combining:            # double acute: two narrower acutes
        parts = [('́', -0.17, 0.7), ('́', 0.17, 0.7)]
    elif all(m in marks for m in combining):
        parts = [(m, 0, 1.0) for m in combining]
    else:
        continue                          # breve, ogonek, ...: left to the fallback font
    base = tcmap[ord(base_ch)]
    bx0, _, bx1, _ = bounds(f, base)
    cx = (bx0 + bx1) / 2
    def draw(pen, base=base, cx=cx, parts=parts, bw=bx1 - bx0):
        draw_base(pen, base)
        for m, off, sx in parts:
            draw_mark(pen, marks[m], cx, dx=off * bw, sx=sx)
    new_glyph(cp, draw, hmtx[base][0])
    added.append(ch)

# 3b. letters with strokes, drawn as extra shapes in the outer-contour direction
def poly(pen, pts):
    pen.moveTo(pts[0])
    for p in pts[1:]:
        pen.lineTo(p)
    pen.closePath()

def slash(pen, x0, y0, x1, y1, t):        # parallelogram from (x0,y0) up to (x1,y1), clockwise
    poly(pen, [(x0, y0), (x1 - t, y1), (x1, y1), (x0 + t, y0)])

def build(cp, base_ch, extra):
    base = tcmap[ord(base_ch)]
    b = bounds(f, base)
    new_glyph(cp, lambda pen: (draw_base(pen, base), extra(pen, *b)), hmtx[base][0])
    added.append(chr(cp))

T = round(STEM * 1.05)
build(0xD8, 'O', lambda pen, x0, y0, x1, y1: slash(pen, x0 - 30, y0 - 90, x1 + 30, y1 + 90, T))
build(0x141, 'L', lambda pen, x0, y0, x1, y1: slash(pen, x0 + STEM / 2 - 300, CAP * 0.32, x0 + STEM / 2 + 300, CAP * 0.62, T))
for cp in (0x110, 0xD0):
    build(cp, 'D', lambda pen, x0, y0, x1, y1: poly(pen, [(x0 - 120, CAP / 2 - BAR / 2), (x0 - 120, CAP / 2 + BAR / 2),
                                                           (x0 + STEM + 330, CAP / 2 + BAR / 2), (x0 + STEM + 330, CAP / 2 - BAR / 2)]))
U_ADV = 1100
new_glyph(0x5F, lambda pen: poly(pen, [(40, -320), (40, -320 + BAR), (U_ADV - 40, -320 + BAR), (U_ADV - 40, -320)]), U_ADV)
added.append('_')

# 4. metrics and tables that list every glyph
f['OS/2'].sCapHeight = CAP
f['OS/2'].sxHeight = bounds(f, tcmap[ord('x')])[3]
for tag in ('LTSH', 'hdmx', 'VDMX'):
    if tag in f:
        del f[tag]
f['name'].setName('Transport Medium (inrainbowfy build)', 4, 3, 1, 0x409)
f.flavor = 'woff2'
f.save(dst)
print('cap', CAP, 'stem', STEM, 'added', len(added), ''.join(added))
