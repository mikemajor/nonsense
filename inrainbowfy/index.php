<?php
  // geniratir.php
  // this page generates your text as Radiohead's In Rainbows album cover

  /* TODO:  fix widths of text divs somehow to get left and right justification nice (on all browsers)
            make the result an image (text overlayed on background) for easier saving of your result as an image
            make the random rainbow characters more alike the original cover ( first 4 use / and last 3 use _ )
  */

  
  $debugging = false;
  #$defaultInput = strtoupper("in rainbows\nin rainbows\nin rainbows\nin rainbows\nin rainbows\nradiohead\nradiohead");
  $defaultInput = strtoupper("cotton cndy\nrollerskate\nvillagefete\nradiohead\nalps mtimes\nxtrm ironng\nbwitchd rrs");
  $desiredLineLength = 14;

  if($debugging){ echo "<div class='yellowtext'>defaultInput is $defaultInput</div>\n"; }
?>
<html>
<head>
 <link rel="stylesheet" href="styles/stylesheet.css" type="text/css" charset="utf-8" />
 <style>
  .bgimg {
    background-image: url('images/irbg3.jpg');
    background-repeat: no-repeat;
    height: 860px;
    width: 860px;
  }
  textarea { 
    font-size: 36px; 
    position: absolute;
    left: 24px; top: 944px;
  }
  .button {
    background-color: #3366ff;
    border: none;
    color: white;
    padding: 15px 32px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 24px;
    float: center;
    position: absolute; 
    left: 24px; top: 880px; 
  }
  h2 {
    font-family: 'roadgeek', Arial, sans-serif;
    font-size: 110px;
    letter-spacing: 8px;
    line-height: 80px;
    width: 830px;
    margin: 18 108 24;
    padding: 0px 0px 0px 20px;
    float: center;
    text-align: justify;
    text-justify: inter-word;
    display: inline-block;
    transform: scale(1.3,1);
  }
  .fulljustify {
    	text-align:justify;
  }
  .fulljustify:after {
        content: "";
        display: inline-block;
        width: 100%;	
  }
  #tagline {
        height: 80px;
        overflow: hidden;
        line-height: 80px; /* vert-center */
  }

 </style> 
</head>
<body>
<div class=bgimg>
<?php

  //if no text has been input, use some default text
  if(!isset($_POST['inputtextarea'])) {
    $arrLines = explode('\n',$defaultInput);
    if($debugging){ echo "<div class='yellowtext'>arrLines is $arrLines</div>\n"; }
  }
  if (empty($_POST['inputtextarea'])) {
    $arrLines = explode('\n',$defaultInput);
    if($debugging){ echo "<div class='yellowtext'>arrLines is $arrLines</div>\n"; }
  }

  if($debugging){ echo "<div class='yellowtext'>textarea had ".$_POST['inputtextarea']."</div>\n"; }
  if($debugging){ echo "<div class='yellowtext'>arrLines is $arrLines</div>\n"; }

  //sanitize the form input
  $safeText = sanitizeInput();
  if($debugging){ echo "<div class='yellowtext'>safeText is $safeText</div>\n"; }
  //use the default text when nothing was entered
  $arrLines = explode("\n", ($safeText === '') ? $defaultInput : $safeText);
  if($debugging){ echo "<div class='yellowtext'>arrLines has ".print_r($arrLines)."</div>\n"; }
  //put randomized In Rainbows chararcters into input
  $arrLines = array_map('putRainbowsInLine', $arrLines);
  //always have 7 lines to print
  $arrLines = array_pad($arrLines, 7, '');

  //write the lines into the image
  echo "<div class='yellowtext'><h2>".$arrLines[0]."</h2></div>";
  echo "<div class='bluetext'><h2>".$arrLines[1]."</h2></div>";
  echo "<div class='orangetext'><h2>".$arrLines[2]."</h2></div>";
  echo "<div class='greentext'><h2>".$arrLines[3]."</h2></div>";
  echo "<div class='goldtext'><h2>".$arrLines[4]."</h2></div>";
  echo "<div class='redtext'><h2>".$arrLines[5]."</h2></div>";
  echo "<div class='greytext'><h2>".$arrLines[6]."</h2></div>";
/*
 <div class='yellowtext'><h2>IN/ RAINBOWS</h2></div>
 <div class='bluetext'><h2>IN RAIN/BOWS</h2></div>
 <div class='orangetext'><h2>IN RAINBOW/S</h2></div>
 <div class='greentext'><h2>IN RAINBOWS/</h2></div>
 <div class='goldtext'><h2>IN RAIN_BOWS</h2></div>
 <div class='redtext'><h2>RA D IOHEA _D</h2></div>
 <div class='greytext'><h2>_RAD IO HEA D</h2></div>
*/
?>
 <br><div width=30>&nbsp;</div>
 <form action="index.php" method="post">
   <br><input type="submit" name="submit" id="submit" value="inrainbowfy" class="button"/>
   <br><textarea id="inputtextarea" name="inputtextarea" rows="7" cols="15"><?php
    if (empty($_POST['inputtextarea'])) {
      echo $defaultInput;
    } else {
      echo $safeText;
    } ?></textarea>
 </form>
 </div>
</body>
</html>

<?php 
  if($debugging) {
    echo "<br><br>";
    echo strtoupper(urlencode($_POST['inputtextarea']));
    echo "<br><br>";
    echo strtoupper(urlencode($sanitizedInput));
    echo "$debugline<br>\n";
    if($debugging){print_r($arrLines);echo "<br>\n";}
  }

  // user-defined functions
  function sanitizeInput() {
    //sanitize the relevant input
    $v = isset($_POST['inputtextarea']) ? (string) $_POST['inputtextarea'] : '';
    //trim leading or trailing whitespace
    $v = trim($v);
    //force to uppercase
    $v = strtoupper($v);
    //preserve newlines before sanitizing by replacing them to another string temporarily
    $v=str_replace("\r\n","\n",$v);
    $v=str_replace("%0A","\n",$v);
    $v=str_replace("\n","[NEWLINE]",$v);
    //sanitize the string for malicious input
    // (replaces FILTER_SANITIZE_STRING, deprecated in PHP 8.1, with identical behavior)
    $v = strip_tags($v);
    $v = preg_replace('/<.*$/s', '', $v);              // drop any unclosed tag remnant
    $v = preg_replace('/[\x00-\x1F\x80-\xFF`]/', '', $v); // strip low, high and backtick chars
    $v = str_replace(array('"', "'"), array('&#34;', '&#39;'), $v);
    //now replace the placeholder with the original newline
    $v = str_replace("[NEWLINE]","\n",$v);
    $sanitizedInput = $v;
    $_POST['inputtextarea'] = $sanitizedInput;
    return $sanitizedInput;
  }

  function putRainbowsInLine($strInput) {
    global $debugging;
    global $desiredLineLength;
    //if the line is already too long, truncate to one less than desired length
    $strLength = strlen($strInput);
    if ($strLength >= $desiredLineLength) {$strInput = substr($strInput, 0, $desiredLineLength-1);}
    //add one random rainbow somewhere in the line 
    $rainbowChars = '_/';
    $randomChar = $rainbowChars[rand(0,strlen($rainbowChars)-1)];
    $strLength = strlen($strInput);
    $randomPosition = rand(0,$strLength);
    $strReturn=substr_replace($strInput, $randomChar, $randomPosition, 0);
    //add spaces somewhere in the line to get it to the desired length
    $countAddSpaces = $desiredLineLength - strlen($strReturn);
    for ($x = 0; $x < $countAddSpaces; $x++) { 
      $randomPosition = rand(1,strlen($strReturn)-1);
      $strReturn=substr_replace($strReturn, ' ', $randomPosition, 0);
      if($debugging){echo "<div class='yellowtext'>strReturn is $strReturn</div>\n";}
    }
    if($debugging){echo "<div class='greytext'>strReturn $strReturn is ".strlen($strReturn)." chars long</div>\n";}
    return $strReturn;
  }
?>
