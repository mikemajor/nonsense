<?php
  // inrainbowfy (SVG version)
  // renders your text as Radiohead's In Rainbows album cover

  /* Layout: the cover is drawn as one 860x860 SVG. Every line is forced to the
     same width with textLength + lengthAdjust="spacingAndGlyphs", so lines are
     edge-aligned regardless of their content (no CSS justify/scale tricks), and
     the whole picture scales as one unit on small screens.
     Tune the cover geometry with the constants below. */

  // phrases the starting cover and the randomize button pick from (7 at a time);
  // add more here, upper case, up to 13 characters (longer ones get cut off)
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
  );
  $desiredLineLength = 14;  // characters per line, after random padding

  const COVER_SIZE   = 860;  // SVG viewBox width and height
  const TEXT_LEFT    = 20;   // x of the left edge of every line
  const TEXT_WIDTH   = 820;  // every line is stretched to exactly this width
  const FONT_SIZE    = 110;
  const FIRST_BASE   = 100;  // baseline y of the first line
  const LINE_PITCH   = 122;  // distance between baselines

  // line colors (top to bottom) and the cover character for each line:
  // the original cover uses / in the first four lines and _ in the last three
  $lineStyles = array(
    array('yellowtext', '/'),
    array('bluetext',   '/'),
    array('orangetext', '/'),
    array('greentext',  '/'),
    array('goldtext',   '_'),
    array('redtext',    '_'),
    array('greytext',   '_'),
  );

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
 <link rel="stylesheet" href="styles/stylesheet.css" type="text/css">
 <style>
  body {
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
  .button.random {
    background-color: #7a42d6;
  }
  .button.secondary {
    background-color: #555;
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
     font-family: roadgeek, Arial, sans-serif;
     font-size: <?= FONT_SIZE ?>px;
     white-space: pre;
   }
   .yellowtext { fill: #f9e933; }
   .bluetext   { fill: #4970b8; }
   .orangetext { fill: #fd511c; }
   .greentext  { fill: #519328; }
   .goldtext   { fill: #f9ab1f; }
   .redtext    { fill: #f93031; }
   .greytext   { fill: #b2cbd3; }
  </style>
  <rect width="100%" height="100%" fill="#0b0b1a"/>
  <image href="images/irbg3.jpg" x="0" y="0" width="<?= COVER_SIZE ?>" height="<?= COVER_SIZE ?>" preserveAspectRatio="xMidYMid slice"/>
<?php
  foreach ($arrLines as $i => $line) {
    if (trim($line) === '') { continue; }
    printf("  <text class=\"%s\" x=\"%d\" y=\"%d\" textLength=\"%d\" lengthAdjust=\"spacingAndGlyphs\">%s</text>\n",
      $lineStyles[$i][0], TEXT_LEFT, FIRST_BASE + $i * LINE_PITCH, TEXT_WIDTH, h($line));
  }
?>
 </svg>

 <form action="" method="post">
   <div class="buttons">
    <input type="submit" value="inrainbowfy" class="button">
    <input type="submit" name="randomize" value="randomize" class="button random" title="New random phrases (your own lines are kept)">
    <button type="button" id="savepng" class="button secondary">save as PNG</button>
   </div>
   <textarea id="inputtextarea" name="inputtextarea" rows="7" cols="15" aria-label="Your text, up to 7 lines"><?= h($boxText) ?></textarea>
 </form>
</div>

<script>
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
    try {
      const font = await toDataURL('fonts/roadgeek2005lowline.woff2');
      style.textContent = "@font-face { font-family: roadgeek; src: url(" + font + ") format('woff2'); }\n" + style.textContent;
    } catch (e) { /* falls back to Arial */ }

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
      a.download = 'inrainbows.png';
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
    //force to uppercase
    $v = strtoupper($v);
    //preserve newlines before sanitizing by replacing them to another string temporarily
    $v = str_replace("\r\n", "\n", $v);
    $v = str_replace("%0A", "\n", $v);
    $v = str_replace("\n", "[NEWLINE]", $v);
    //sanitize the string for malicious input
    // (replaces FILTER_SANITIZE_STRING, deprecated in PHP 8.1, with identical behavior)
    $v = strip_tags($v);
    $v = preg_replace('/<.*$/s', '', $v);              // drop any unclosed tag remnant
    $v = preg_replace('/[\x00-\x1F\x80-\xFF`]/', '', $v); // strip low, high and backtick chars
    $v = str_replace(array('"', "'"), array('&#34;', '&#39;'), $v);
    //now replace the placeholder with the original newline
    $v = str_replace("[NEWLINE]", "\n", $v);
    return $v;
  }

  function putRainbowsInLine($strInput, $rainbowChar) {
    global $desiredLineLength;
    //if the line is already too long, truncate to one less than desired length
    if (strlen($strInput) >= $desiredLineLength) { $strInput = substr($strInput, 0, $desiredLineLength - 1); }
    //add the cover character somewhere in the line
    $randomPosition = rand(0, strlen($strInput));
    $strReturn = substr_replace($strInput, $rainbowChar, $randomPosition, 0);
    //add spaces somewhere in the line to get it to the desired length
    $countAddSpaces = $desiredLineLength - strlen($strReturn);
    for ($x = 0; $x < $countAddSpaces; $x++) {
      $randomPosition = rand(1, strlen($strReturn) - 1);
      $strReturn = substr_replace($strReturn, ' ', $randomPosition, 0);
    }
    return $strReturn;
  }
?>
