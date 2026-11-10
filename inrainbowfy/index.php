<?php
  // inrainbowfy (SVG version)
  // renders your text as Radiohead's In Rainbows album cover

  /* Layout: the cover is drawn as one 860x860 SVG. Every line is forced to the
     same width with textLength + lengthAdjust="spacing", so lines are
     edge-aligned by widening only the gaps between letters; the letters keep
     their real shape. Like the real cover, gaps may also shrink a little
     (up to MAX_TIGHTEN em each). Line widths come from the fonts' width
     tables: letters are dropped from the end of a line that can't fit even
     tightened, and random padding spaces are only added while the line still
     fits. As a safety net for characters neither table knows, fitLines()
     (JavaScript) squeezes a line that still doesn't fit rather than let it
     overlap. Geometry is measured from the real cover: capitals 0.099 of the
     cover tall (ours slightly less), lines 0.148 apart, almost no margins.
     The whole picture scales as one unit on small screens.
     Tune the cover geometry with the constants below. */

  // phrases the starting cover and the randomize button pick from (7 at a time);
  // add more here, upper case, up to 13 characters (longer ones get cut off);
  // non-ASCII letters like Ø are fine (the font has Latin-1)
  $phrasePool = array(
    'COTTON CNDY',
    'ROLLERSKATE',
    'VILLAGEFETE',
    'RADIOHEAD',
    'ALPS MTIMES',
    'XTRM IRONNG',
    'BWITCHD RRS',
    'IN RAINBOWS',
    'KING CONMAN',
    'RAINBOW PIG',
    'IMPALA TUSK',
    'RON BRONSON',
    'GARDEN PALS',
    'PETAL SMILE',
    'BOT MUFFINS',
    'SALT FLASKS',
    'BRNKSMNSHIP',
    'GUAC IS XTR',
    'KEEP IT UWU',
    'FASHIONABLE',
    'BLOCK TYPES',
    'BEE TANK UP',
    'IDK MY NAME',
    'SICK JPEGGS',
    'PLATFORMEEL',
    'PAY YR RATE',
    'CHEWS LIIFE',
    'REMOTE WORK',
    'VICISSITUDE',
    'PULCHRA EST',
    'CAMO ONESIE',
    'BOT ON BOAT',
    'PANAMONSTER',
    'PRIVATE ICE',
    'S-TIER TACO',
    'PIKES PLACE',
    'MILTONRADLY',
    'DUWAMISH HQ',
    'CHECK ENGIN',
    'HYPNODRONE',
    'I WZ INVRTD',
    'ULALA SAVES',
    'SMBDY LUV U',
    'MOONWALKER',
    'PARTY TRAIN',
    'PLUM PACZKI',
    'ANNIHILATED',
    'PØLSE VOGNE',
    'KRUSH CHEVY',
    'EEEE EDDDDD',
    'LATER TOADS',
    'UNRELIVABLE',
    'SIGN HORNET',
    'MOSTLY HARM',
    'GRAVITRON G',
    'NOTBARMUSIC',
    'RENT STRIKE',
    'GO WITH DOG',
    'IF THEN ELSE',
    'CALL UR MUM',
    'GET UR DMV',
    'OK EVRYWHERE',
    'LIMB OF KINGS',
    'Y R U A Q T',
    'LETTUCEPATCH',
    'BLUNTZ MILLY',
    'DIRT CAMPIN',
  );
  $desiredLineLength = 13;  // most characters of text per line, and what random padding fills to
                            // (the cover's lines have 12-13)

  const COVER_SIZE   = 860;  // SVG viewBox width and height
  const TEXT_LEFT    = 4;    // x of the left edge of every line
  const TEXT_WIDTH   = 852;  // every line is justified to exactly this width
  const FONT_SIZE    = 104;  // Transport's capitals are 0.746em tall, so 104px = 78px capitals
                              // (the real cover's are ~84px, but then many phrases don't fit)
  const MAX_TIGHTEN  = 0.085; // em each gap may shrink by to fit (the real cover uses up to ~0.056)

  // Fonts: Transport Medium (the UK road-sign face the real cover uses), built by
  // tools/build_transport.py: fixed baseline, plus accented capitals, O-slash,
  // L-stroke, D-stroke and _ added in Transport's style. Anything still missing
  // (lower-case accents, ß, Æ, Œ, breve, ogonek, symbols) comes from Roadgeek 2005,
  // scaled up 111.4% so its capitals match Transport's (0.746em vs 0.67em).
  // The Transport file isn't in the git repo (see README); without it the cover
  // is all Roadgeek, at the same size.
  const FILL_SCALE   = 1.114; // Roadgeek's size-adjust: 0.746em / 0.67em capitals
  $fontFaces = array(
    array('family' => 'transport', 'url' => 'fonts/transportmedium.woff2', 'format' => 'woff2',
          'extra' => ''),
    array('family' => 'roadgeek-fill', 'url' => 'fonts/roadgeek2005lowline.woff2', 'format' => 'woff2',
          'extra' => 'size-adjust: ' . (FILL_SCALE * 100) . '%;'),
  );
  function fontFaceCss($faces) {
    $css = '';
    foreach ($faces as $f) {
      $css .= "@font-face { font-family: {$f['family']}; src: url({$f['url']}) format('{$f['format']}'); {$f['extra']} }\n";
    }
    return $css;
  }
  // character widths in em, made by tools/font_widths.py: Transport's where it
  // has the character, otherwise Roadgeek's scaled like its size-adjust
  function loadWidths() {
    $t = json_decode((string) @file_get_contents(__DIR__ . '/fonts/transportmedium-widths.json'), true);
    $r = json_decode((string) @file_get_contents(__DIR__ . '/fonts/roadgeek2005lowline-widths.json'), true);
    $w = is_array($t) ? $t : array();
    foreach ((is_array($r) ? $r : array()) as $ch => $em) {
      if (!isset($w[$ch])) { $w[$ch] = $em * FILL_SCALE; }
    }
    return $w;
  }
  $charWidths = loadWidths();

  const FIRST_BASE   = 81;   // baseline y of the first line (capitals start ~3px from the top)
  const LINE_PITCH   = 126;  // distance between baselines; leaves room for the bottom line's _
                              // (which reaches ~20px below its baseline) with ~3px to spare

  // line colors (top to bottom) and the cover character for each line:
  // the original cover uses / in the first four lines and _ in the last three
  $lineStyles = array(
    array('yellowtext', '/', '#f9e933'),
    array('bluetext',   '/', '#4970b8'),
    array('orangetext', '/', '#fd511c'),
    array('greentext',  '/', '#519328'),
    array('goldtext',   '_', '#f9ab1f'),
    array('redtext',    '_', '#f93031'),
    array('greytext',   '_', '#b2cbd3'),
  );

  // the raindomise button wears one of the seven cover colors, with black or
  // white text, whichever contrasts more. The color is kept in a cookie, so
  // refreshing or pressing inrainbowfy keeps it; only a raindomise click picks a
  // new one (always different from the current one). First visit: random.
  function luminance($hex) {
    $l = 0;
    foreach (array(0.2126, 0.7152, 0.0722) as $i => $weight) {
      $c = hexdec(substr($hex, 1 + 2 * $i, 2)) / 255;
      $l += $weight * ($c <= 0.03928 ? $c / 12.92 : pow(($c + 0.055) / 1.055, 2.4));
    }
    return $l;
  }
  $coverColors = array();
  foreach ($lineStyles as $style) { $coverColors[] = $style[2]; }
  $raindomColor = isset($_COOKIE['raindomColor']) ? (string) $_COOKIE['raindomColor'] : '';
  if (isset($_POST['randomize']) || !in_array($raindomColor, $coverColors, true)) {
    $others = array_values(array_diff($coverColors, array($raindomColor)));
    $raindomColor = $others[array_rand($others)];
    setcookie('raindomColor', $raindomColor, array(
      'expires' => time() + 365 * 86400, 'samesite' => 'Lax',
      'secure' => !empty($_SERVER['HTTPS']), 'httponly' => true));
  }
  $raindomText = (luminance($raindomColor) + 0.05) / 0.05 > 1.05 / (luminance($raindomColor) + 0.05) ? '#111' : '#fff';

  //sanitize the form input, then decode the sanitizer's &#34;/&#39; so a quote
  //counts as one character and can't be cut in half; h() escapes everything on output
  $inputText = html_entity_decode(sanitizeInput(), ENT_QUOTES, 'UTF-8');

  if (isset($_POST['randomize'])) {
    //randomize: keep the user's own lines (anything not from the phrase list),
    //fill the rest of the 7 with random phrases, and shuffle the order
    $ownLines = array();
    foreach (explode("\n", $inputText) as $line) {
      $line = trim($line);
      if ($line !== '' && !in_array($line, $phrasePool, true)) { $ownLines[] = $line; }
    }
    $textLines = pickPhrases($ownLines, $phrasePool);
  } elseif ($inputText === '') {
    //first visit, or an empty box: 7 random phrases
    $textLines = pickPhrases(array(), $phrasePool);
  } else {
    //inrainbowfy: use exactly what was entered; only 7 lines fit
    $textLines = array_slice(explode("\n", $inputText), 0, 7);
  }
  //what goes back in the text box
  $boxText = implode("\n", $textLines);

  //put randomized In Rainbows characters into each line
  $arrLines = array();
  foreach ($textLines as $i => $line) {
    $arrLines[$i] = putRainbowsInLine($line, $lineStyles[$i][1]);
  }
  //always have 7 lines to print
  $arrLines = array_pad($arrLines, 7, '');

  //up to 7 lines: all of $keep (first 7), topped up with random unused phrases, shuffled
  function pickPhrases($keep, $pool) {
    $keep = array_slice(array_values($keep), 0, 7);
    $unused = array_values(array_diff($pool, $keep));
    shuffle($unused);
    $lines = array_merge($keep, array_slice($unused, 0, 7 - count($keep)));
    shuffle($lines);
    return $lines;
  }

  function h($s) {
    // escape for HTML/SVG output
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="utf-8">
 <meta name="viewport" content="width=device-width, initial-scale=1">
 <title>inrainbowfy</title>
 <style>
<?= fontFaceCss($fontFaces) ?>  body {
    margin: 0;
    padding: 8px;
    background: #111;
    color: #eee;
    font-family: Arial, sans-serif;
  }
  .wrap {
    /* keep the cover and the buttons below it on screen together: the cover
       shrinks to the window height minus room for the buttons (about 92px) */
    max-width: <?= COVER_SIZE ?>px;
    max-width: min(<?= COVER_SIZE ?>px, max(280px, calc(100vh - 92px)));
    max-width: min(<?= COVER_SIZE ?>px, max(280px, calc(100dvh - 92px)));
    margin: 0 auto;
  }
  #cover {
    display: block;
    width: 100%;
    height: auto;
  }
  form {
    margin-top: 16px;
  }
  textarea {
    display: block;
    box-sizing: border-box;
    width: 100%;
    max-width: 22ch;
    font-size: 28px;
    font-family: monospace;
    text-transform: uppercase;
    margin-top: 12px;
  }
  .buttons {
    /* side by side, even on a narrow phone */
    display: flex;
    gap: 8px;
  }
  .button {
    background-color: #3366ff;
    border: none;
    color: white;
    /* shrink padding and text on narrow screens so both buttons fit on one row */
    padding: 15px clamp(8px, 3vw, 32px);
    font-size: clamp(14px, 4.5vw, 24px);
    white-space: nowrap;
    cursor: pointer;
    flex: 1 1 auto;  /* the row spans exactly the cover width */
    min-width: 0;
  }
  .credits {
    font-size: 12px;
    color: #888;
    margin-top: 16px;
  }
  .credits a {
    color: #aaa;
  }
  .button.random {
    background-color: <?= $raindomColor ?>;
    color: <?= $raindomText ?>;
  }
  .button.secondary {
    background-color: #7a42d6;
  }
 </style>
</head>
<body>
<div class="wrap">
 <svg id="cover" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
      viewBox="0 0 <?= COVER_SIZE ?> <?= COVER_SIZE ?>" width="<?= COVER_SIZE ?>" height="<?= COVER_SIZE ?>"
      role="img" aria-label="<?= h(implode(' / ', array_map('trim', $arrLines))) ?>">
  <style>
   text {
     font-family: transport, roadgeek-fill, Arial, sans-serif;
     font-size: <?= FONT_SIZE ?>px;
     white-space: pre;
   }
<?php foreach ($lineStyles as $style) { echo "   .{$style[0]} { fill: {$style[2]}; }\n"; } ?>  </style>
  <rect width="100%" height="100%" fill="#0b0b1a"/>
  <image href="images/irbg3.jpg" x="0" y="0" width="<?= COVER_SIZE ?>" height="<?= COVER_SIZE ?>" preserveAspectRatio="xMidYMid slice"/>
<?php
  foreach ($arrLines as $i => $line) {
    if (trim($line) === '') { continue; }
    printf("  <text class=\"%s\" x=\"%d\" y=\"%d\" textLength=\"%d\" lengthAdjust=\"spacing\">%s</text>\n",
      $lineStyles[$i][0], TEXT_LEFT, FIRST_BASE + $i * LINE_PITCH, TEXT_WIDTH, h($line));
  }
?>
 </svg>

 <form action="" method="post">
   <div class="buttons">
    <input type="submit" value="inrainbowfy" class="button">
    <input type="submit" name="randomize" value="raindomise" class="button random" title="New random phrases (your own lines are kept)">
    <button type="button" id="savepng" class="button secondary">save as PNG</button>
   </div>
   <textarea id="inputtextarea" name="inputtextarea" rows="7" cols="15" aria-label="Your text, up to 7 lines"><?= h($boxText) ?></textarea>
 </form>
 <p class="credits">Fonts: Transport (Crown Copyright; contains public sector information licensed under the
 <a href="https://www.nationalarchives.gov.uk/doc/open-government-licence/version/1/">Open Government Licence v1.0</a>)
 and Roadgeek 2005 (&copy; Michael D. Adams, 2005).</p>
</div>

<script>
const FONT_FACES = <?= json_encode($fontFaces, JSON_UNESCAPED_SLASHES) ?>;
// Save the cover as a PNG: an SVG drawn as an image can't load external files,
// so embed the font and background as data: URLs first, then draw it on a canvas.
async function toDataURL(url) {
  const resp = await fetch(url);
  if (!resp.ok) throw new Error(url + ': ' + resp.status);
  const blob = await resp.blob();
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = reject;
    reader.readAsDataURL(blob);
  });
}

// Lines are justified by widening the gaps only (lengthAdjust="spacing"). A line
// that's naturally wider than its slot would overlap itself, so once the fonts
// have loaded, squeeze just those lines instead.
// (Gaps may shrink by up to MAX_TIGHTEN em, as the server assumes.)
const MAX_TIGHTEN_PX = <?= MAX_TIGHTEN * FONT_SIZE ?>;
function fitLines(svg) {
  for (const t of svg.querySelectorAll('text[textLength]')) {
    const want = Number(t.getAttribute('textLength'));
    t.removeAttribute('textLength');
    const natural = t.getComputedTextLength();
    t.setAttribute('textLength', want);
    const slack = Math.max(0, t.getNumberOfChars() - 1) * MAX_TIGHTEN_PX;
    t.setAttribute('lengthAdjust', natural - slack > want ? 'spacingAndGlyphs' : 'spacing');
  }
}
document.fonts.ready.then(() => fitLines(document.getElementById('cover')));

document.getElementById('savepng').addEventListener('click', async () => {
  const button = document.getElementById('savepng');
  button.disabled = true;
  try {
    const svg = document.getElementById('cover').cloneNode(true);
    const size = svg.viewBox.baseVal.width;

    const bg = svg.querySelector('image');
    try { bg.setAttribute('href', await toDataURL(bg.getAttribute('href'))); }
    catch (e) { bg.remove(); }

    const style = svg.querySelector('style');
    let faces = '';
    for (const f of FONT_FACES) {
      try {
        const font = await toDataURL(f.url);
        faces += '@font-face { font-family: ' + f.family + '; src: url(' + font + ") format('" + f.format + "'); " + f.extra + ' }\n';
      } catch (e) { /* missing font: the next one in the list is used */ }
    }
    style.textContent = faces + style.textContent;

    const svgBlob = new Blob([new XMLSerializer().serializeToString(svg)], { type: 'image/svg+xml' });
    const svgUrl = URL.createObjectURL(svgBlob);
    const img = new Image();
    await new Promise((resolve, reject) => { img.onload = resolve; img.onerror = reject; img.src = svgUrl; });

    const scale = 2;  // export at 2x for a crisp 1720px image
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = size * scale;
    canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
    URL.revokeObjectURL(svgUrl);

    canvas.toBlob(png => {
      const a = document.createElement('a');
      a.href = URL.createObjectURL(png);
      a.download = 'inrainbowfy.png';
      a.click();
      setTimeout(() => URL.revokeObjectURL(a.href), 1000);
    }, 'image/png');
  } catch (e) {
    alert('Sorry, saving the image failed: ' + e.message);
  } finally {
    button.disabled = false;
  }
});
</script>
</body>
</html>
<?php
  // user-defined functions
  function sanitizeInput() {
    //sanitize the relevant input
    $v = isset($_POST['inputtextarea']) ? (string) $_POST['inputtextarea'] : '';
    //trim leading or trailing whitespace
    $v = trim($v);
    //force to uppercase (strtoupper only does A-Z, so accented letters are mapped too)
    $v = upperLatin(strtoupper($v));
    //preserve newlines before sanitizing by replacing them to another string temporarily
    $v = str_replace("\r\n", "\n", $v);
    $v = str_replace("%0A", "\n", $v);
    $v = str_replace("\n", "[NEWLINE]", $v);
    //sanitize the string for malicious input
    // (replaces FILTER_SANITIZE_STRING, deprecated in PHP 8.1, with identical behavior)
    $v = strip_tags($v);
    $v = preg_replace('/<.*$/s', '', $v);              // drop any unclosed tag remnant
    // strip control and invisible characters and backticks; keep printable UTF-8
    // letters like Ø (invalid UTF-8 falls back to dropping all non-ASCII bytes)
    if (preg_match('//u', $v)) {
      $v = preg_replace('/[\x00-\x1F\x7F`]|\p{C}/u', '', $v);
    } else {
      $v = preg_replace('/[\x00-\x1F\x80-\xFF`]/', '', $v);
    }
    $v = str_replace(array('"', "'"), array('&#34;', '&#39;'), $v);
    //now replace the placeholder with the original newline
    $v = str_replace("[NEWLINE]", "\n", $v);
    return $v;
  }

  //upper-case accented Latin letters (Latin-1 and Latin Extended-A); PHP's
  //strtoupper only does A-Z and this server has no mbstring
  function upperLatin($s) {
    static $map = array('à'=>'À', 'á'=>'Á', 'â'=>'Â', 'ã'=>'Ã', 'ä'=>'Ä', 'å'=>'Å', 'æ'=>'Æ', 'ç'=>'Ç', 'è'=>'È', 'é'=>'É', 'ê'=>'Ê', 'ë'=>'Ë', 'ì'=>'Ì', 'í'=>'Í', 'î'=>'Î', 'ï'=>'Ï', 'ð'=>'Ð', 'ñ'=>'Ñ', 'ò'=>'Ò', 'ó'=>'Ó', 'ô'=>'Ô', 'õ'=>'Õ', 'ö'=>'Ö', 'ø'=>'Ø', 'ù'=>'Ù', 'ú'=>'Ú', 'û'=>'Û', 'ü'=>'Ü', 'ý'=>'Ý', 'þ'=>'Þ', 'ÿ'=>'Ÿ', 'ā'=>'Ā', 'ă'=>'Ă', 'ą'=>'Ą', 'ć'=>'Ć', 'ĉ'=>'Ĉ', 'ċ'=>'Ċ', 'č'=>'Č', 'ď'=>'Ď', 'đ'=>'Đ', 'ē'=>'Ē', 'ĕ'=>'Ĕ', 'ė'=>'Ė', 'ę'=>'Ę', 'ě'=>'Ě', 'ĝ'=>'Ĝ', 'ğ'=>'Ğ', 'ġ'=>'Ġ', 'ģ'=>'Ģ', 'ĥ'=>'Ĥ', 'ħ'=>'Ħ', 'ĩ'=>'Ĩ', 'ī'=>'Ī', 'ĭ'=>'Ĭ', 'į'=>'Į', 'ı'=>'I', 'ĳ'=>'Ĳ', 'ĵ'=>'Ĵ', 'ķ'=>'Ķ', 'ĺ'=>'Ĺ', 'ļ'=>'Ļ', 'ľ'=>'Ľ', 'ŀ'=>'Ŀ', 'ł'=>'Ł', 'ń'=>'Ń', 'ņ'=>'Ņ', 'ň'=>'Ň', 'ŋ'=>'Ŋ', 'ō'=>'Ō', 'ŏ'=>'Ŏ', 'ő'=>'Ő', 'œ'=>'Œ', 'ŕ'=>'Ŕ', 'ŗ'=>'Ŗ', 'ř'=>'Ř', 'ś'=>'Ś', 'ŝ'=>'Ŝ', 'ş'=>'Ş', 'š'=>'Š', 'ţ'=>'Ţ', 'ť'=>'Ť', 'ŧ'=>'Ŧ', 'ũ'=>'Ũ', 'ū'=>'Ū', 'ŭ'=>'Ŭ', 'ů'=>'Ů', 'ű'=>'Ű', 'ų'=>'Ų', 'ŵ'=>'Ŵ', 'ŷ'=>'Ŷ', 'ź'=>'Ź', 'ż'=>'Ż', 'ž'=>'Ž', 'ſ'=>'S');
    return strtr($s, $map);
  }

  //does a line of these characters fit in TEXT_WIDTH, allowing each gap
  //between characters to shrink by up to MAX_TIGHTEN em?
  function lineFits($chars) {
    global $charWidths;
    $em = 0;
    foreach ($chars as $ch) {
      $em += isset($charWidths[$ch]) ? $charWidths[$ch] : 0.6;
    }
    return ($em - max(0, count($chars) - 1) * MAX_TIGHTEN) * FONT_SIZE <= TEXT_WIDTH;
  }

  function putRainbowsInLine($strInput, $rainbowChar) {
    global $desiredLineLength;
    //work on characters, not bytes, so letters like Ø count once and never get split
    $chars = preg_split('//u', $strInput, -1, PREG_SPLIT_NO_EMPTY);
    //keep at most $desiredLineLength characters of the text (with its cover
    //character a line can reach one more; the width check below has the last word)
    if (count($chars) > $desiredLineLength) { $chars = array_slice($chars, 0, $desiredLineLength); }
    //if it's too wide to fit even with its cover character alone (lots of Ws,
    //say), drop letters from the end until it fits
    while (count($chars) > 0 && !lineFits(array_merge($chars, array($rainbowChar)))) {
      array_pop($chars);
    }
    //add the cover character somewhere in the line
    array_splice($chars, rand(0, count($chars)), 0, array($rainbowChar));
    //add spaces somewhere in the line, up to the desired length, while it still fits
    while (count($chars) < $desiredLineLength && count($chars) > 1) {
      $padded = $chars;
      array_splice($padded, rand(1, count($chars) - 1), 0, array(' '));
      if (!lineFits($padded)) { break; }
      $chars = $padded;
    }
    return implode('', $chars);
  }
?>
