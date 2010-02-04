<?php

/**
 * Teletext image viewer
 * 
 * @version 0.5.6 beta
 * @copyright 2010 Rob O'Donnell. robert@irrelevant.com
 * 
 * 
 *   See README.TXT for important information.
 * 
 * 
 * This is a very simple renderer that **cannot cope with "dynamic" frames! **
 * 
 * TODO: check validity of cache (compare dates)
 * TODO: alternate languages and characer sets
 * TODO: optimise second pass so the only write to image is bg colour on flashings...
 *		  (that means - to just remove the flashing characters!)
 *  	  - can also put back only bother writing bg if colour>0 (except in above case)
 * TODO: optional colours in text mode??
 * 
 * Call with ttxview.php?page=65656a
 * where page = filename to load
 *   gal = folder to scan (default 'frames')
*  OR text = character sequence to display
 * width = width in columns
 * height = height in lines
*  top = single line to place at top of page
 * format = 0 - auto, 1=mode7, 2=gnome, 3=raw, 4=ABZTtxt (JGH)
*  add 512 for $top to overwrite top line of page
*  add 256 for case insensitivity
 * add 128 to disable black 
 * add 64 to disable cache write
 *    add 32 to disable cache read
 *  add 16 to map . in pagename to /
 * longdesc=1 - disable graphic and provide textual equivelant!
 *  =2 ditto but replace graphics with *s (as per Prestel old 300 baud access!)
 * thumbnail=1 - display image as thumbnail
 * 
 * 
 * First things first.  Image size.
 * 40 x 25 lines by default
 * font is 20 high x 12 wide
 */

include "GIFEncoder.class.php";

function similar_file_exists($filename) {
  if ($filename == "") return "";
  
  if (file_exists($filename)) {
    return $filename;
  }
  $dir = dirname($filename);
  if (!file_exists($dir)) {
      if (($dir=similar_file_exists($dir)) == "") return "";
  }
  $files = glob($dir . '/*');
  $lcaseFilename = strtolower($filename);
  foreach($files as $file) {
    if (strtolower($file) == $lcaseFilename) {
      return $file;
    }
  }
  return "";
} 

$error = "";
// image size in characters
$width = 40;
if (isset($_GET["width"])) {
    if (is_numeric($_GET["width"])) $width = $_GET["width"]; 
    // else $error = "Invalid width";
} 

$height = 25;
if (isset($_GET["height"])) {
    if (is_numeric($_GET["height"])) $height = $_GET["height"]; 
    // else $error = "Invalid height";
} 

$folder = "frames";
if (isset($_GET["gal"])) {
    if (preg_match('/^[a-zA-Z0-9_]{3,16}$/', $_GET['gal'])) $folder = $_GET["gal"];
    // else $folder = "frames";
} 

$text = "";
$longtext = "";
$longdesc = 0;
if (isset($_GET["longdesc"])) {
    if (is_numeric($_GET["longdesc"])) $longdesc = $_GET["longdesc"] ; 
	if ($longdesc < 0 || $longdesc > 2) $longdesc = 0; // sanity check  
	  // else $error = "Invalid flag";
} 

$thumbnail=0;
if (isset($_GET["thumbnail"])) {
    if (is_numeric($_GET["thumbnail"])) $thumbnail = $_GET["thumbnail"] ; 
	if ($thumbnail < 0 || $thumbnail > 3) $thumbnail = 0; // sanity check
    // else $error = "Invalid flag";
} 

$top="";
if (isset($_GET["top"])) {
    $top = str_pad(html_entity_decode($_GET["top"]),$width);
}

// font sizes. must match that in font files
$fwidth = 12;
$fheight = 20; 
// border in pixels
$tborder = 5; // top & bottom
$lborder = 12; // left and right  
// thumbnail size
$thumb_w = 100 * $thumbnail;
$thumb_h = 100 * $thumbnail;
// pause time per frame for flashing
$flashdelay[0] = 100;
$flashdelay[1] = 33; 
// config stuff
$black = 1; // support black ink (not available on SAA5050...)
// what to display..
$donotcache = 0;
$alwaysrender = 0;
$page = "";
if (isset($_GET["page"])) {
    if (preg_match('/^[a-zA-Z0-9_.]{1,16}$/', $_GET['page'])) $page = $_GET["page"];
    else $error = "Invalid page number";
} else {
	if (isset($_GET["text"])) {
	    $text = substr(html_entity_decode($_GET["text"]),0,$width);
		$donotcache = 1;
		$alwaysrender = 1;
	}
}

$cachepage = $folder . "_" . $page;


$offset = 0;
if (isset($_GET["offset"])) {
    if (is_numeric($_GET["offset"])) {
        $offset = $_GET["offset"];
        $cachepage = $folder . "_" . $page . "+" . $offset;
    } 
    // else $error = "Invalid offset";
} 
if ($thumbnail) {
    $cachepage.= "_thumb".$thumb_w;
}
// what format is it in?
$format = 0;
if (isset($_GET["format"])) {
    if (is_numeric($_GET["format"])) {
        $format = 0 + $_GET["format"];
        if ($format & 128) {
            $black = 0;
//            $format -= 128;
        } 
        if ($format & 64) {
            $donotcache = 1;
//            $format -= 64;
        } 
        if ($format & 32) {
            $alwaysrender = 1;
//            $format -= 32;
        } 
		if ($format & 16) {
			$page=str_replace(".","/",$page);
		}
    } else $error = "Invalid format";
} 


// first check to see if cached copy already exists
// gif image (for animations)
if (!$longdesc && $alwaysrender != 1 && $page != "" && file_exists("./cache/" . $cachepage . ".gif")) {
    // TODO observe dates, etc, to ensure is up to date)
    // can't use imagegif as this loses the animation!!
    $my_img = file_get_contents("./cache/" . $cachepage . ".gif");
    header("Content-type: image/gif");
    echo $my_img;
} else if (!$longdesc && $alwaysrender != 1 && $page != "" && file_exists("./cache/" . $cachepage)) { // standard image
    // TODO observe dates, etc, to ensure is up to date)
    $my_img = imagecreatefrompng("./cache/" . $cachepage);
    header("Content-type: image/png");
    imagepng($my_img);
    imagedestroy($my_img);
} else {
    if (!$longdesc) { // don't bother for text mode
        // read fonts
        $fontnum = imageloadfont("./vvttxt.gdf");
        $fontnumtop = imageloadfont("./vvttxtop.gdf");
        $fontnumbot = imageloadfont("./vvttxbtm.gdf");
    } 
    if (!$longdesc && ($fontnum == 0 || $fontnumtop == 0 || $fontnumbot == 0)) {
        $error = "cannot find font file";
    } else {
        // .. read file ..
        if ($page == "" || $error != "") {
            if ($error != "") {
                $text = chr(129) . chr(157) . chr(135) . $error . "  " . chr(156);
                $donotcache = 1;
            } else { 
				if ($text == "") {
					// sample text
	                $text = "The" . chr(129) . "quick" . chr(130) . "brown" . chr(131)
	                 . "fox" . chr(132) . "jumped" . chr(133) . "over" . chr(134) . "the" .
	                chr(135) . "lazy" . "dog" . chr(136) . "0123456789 ![]{}^#" . chr(141) . "Double" . chr(140) . "Height    " . "0123456789012345678901234567890123456789" . " Viewdata Viewer (C)2010 Rob O'Donnell  " .
	                chr(147) . "ssss" . chr(154) . "ssss" . chr(153) . "ssss" .
	                chr(8) . "flash?" . chr(136) . "flash?";
	                $donotcache = 1;
				}
            } 
        } else {
            $text = "";
			$fnam= "./" . $folder . "/" . $page;
            if (file_exists($fnam))
                $text = file_get_contents($fnam);
            else {
				if ($format & 256) {
				    if (($fnam = similar_file_exists($fnam)) != "" ) {
				        $text = file_get_contents($fnam);
				    } 
				}
                if ($text == "") {
                    
	                $text = chr(129) . chr(157) . chr(135) . "File not found  " . chr(156);
	                $donotcache = 1;
	                $format = 1;
				}
            } 

            if ($offset > strlen($text)) {
                $offset = 0;
                $cachepage = $folder . "_" . $page;
				if ($thumbnail) {
				    $cachepage.= "_thumb".$thumb_w;
				}
            } else {
                $text = substr($text, $offset);
            } 
			
			if (($format & 15) == 0) {
			    if (strpos(substr($text,0,920),chr(13).chr(10)) !== FALSE ||
				strpos(substr($text,0,920),chr(10).chr(13)) !== FALSE ) {
			        $format += 3;
			    }
			}

            if (($format & 15) == 0) {
				$notsm=FALSE;
				$defsm=FALSE;
				// look for a SofMac route table with an empty route in it ("*")
				for ($i=0;$i<10 && $ntsm == FALSE;$i++) {
					$route=substr($text,14+9*$i,9);
					if ($route == "*        ") {
					    $defsm=TRUE;
					}
/*					$fsp =strpos($route," ");
					$rln = strlen(rtrim($route));
					if ($fsp == 0 || ($rln < 9 && $fsp != $rln+1)) {
					    $notsm=TRUE; $defsm=FALSE;
					} */
				}
				if ($defsm) {
				    $format += 2;
				} else if (!$notsm) {
					if (strpos(substr($text,0,16),chr(0).chr(0)) !== FALSE) {
					    $format += 2;
					} else if (chr(127 & ord(substr($text, 143, 1))) == "p" &&
	                        is_numeric(chr(127 & ord(substr($text, 142, 1))))) {
	                    $format += 2; // gnome host frame
	                } 
				}
            } 
            if (($format & 15) == 0) {
                $char = ord(substr($text, 920, 1)); // ABVTtxt version byte
                $routing = substr($text, 936, 64); // scan routing area for 000000
                if (($char == 13 || ($char > 1 && $char < 6)) && strpos($routing, chr(0) . chr(0) . chr(0)) !== false) {
                    $format += 4; // ABZTtxt
                } 
            } 
            if (($format & 15) == 2) {
                $text = substr($text, 104, 920);
                $height = 24; // 23+1 blank. 
            } 
            if (($format & 15) == 4) {
                $text = substr($text, 0, 920);
                $height = 24; // 23+1 blank
            } 
			
			if ($top != "") {
			    if ($format & 512) { // overwrite top line
			        $text = substr_replace($text,$top,0,$width);
			    } else { // insert
					$text = $top .$text;
					$height++;
				}
			}
			
        } 
    } 

	if (($format & 15) == 3) {	// RAW mode
		$rawtext = $text;
		$text = str_repeat(" ",960);
		$cx = 0; $cy=0;
		$tp = 0; $esc=0;
        while ($tp < strlen($rawtext)) {
            $char = ord($rawtext[$tp]);
			switch(0+$char){
				case 13 :
					$cx = 0;
					break;
				case 9:
					$cx++;
					break;
				case 10 :
					$cy++;
					if ($cy>23) $cy=0;
					break;
				case 8:
					$cx -= 1;
					break;
				case 11:
					$cy--;
					if ($cy<0) $cy=23;
					break;
				case 27:
					$esc = 1; //!$esc;
					break;
				default:
					if ($esc) {
						$esc = 0;
						$char = $char & 31;
					} 
					$text[($cx+(40 * $cy))] = chr($char);
					$cx++;
					break;
			} // switch
			if ($cx>39) {
			    $cx=0;
				$cy++;
				if ($cy>23) $cy=0;
			}
			if ($cx<0) {
			    $cx=39;
				$cy--;
				if ($cy<0) $cy=23;
			}
			$tp++;
		}
	}
	
	
	
	
	
    if (!$longdesc) { // don't bother for text mode
        // image size in pixels
        $pwidth = $width * $fwidth + 2 * $lborder;
        $pheight = $height * $fheight + 2 * $tborder; 
        // create canvas
        $my_img = imagecreate($pwidth, $pheight); 
		if ($thumbnail) {
		    $thumb_img=ImageCreateTrueColor($thumb_w,$thumb_h);
		}
		
        // define the colours
        for ($i = 0; $i < 8; $i++) {
            $colour[$i] = imagecolorallocate($my_img, ($i & 1)?255:0, ($i & 2)?255:0, ($i & 4)?255:0);
        } 
    } 
    // flasher flag
    $flasher = 0;
    $flashcycle = 0;
    do { // for each flashcycle
        if ($flasher > 0) $flashcycle++; 
        // starting character position
        $cx = 0;
        $cy = 0; 
        // starting forground and background colours
        $doublebottom = 0;
        $nextbottom = 0;
        $cf = 7;
        $cb = 0;
        $flash = 0; // flashing off
        $double = 0; // doubleheight off
        $graphics = 0; // text mode
        $seperated = 0; // normal graphics
        $holdgraph = 0; // hold mode off
        $holdchar = 32; // default hold char
        $conceal = 0; 
        // starting textpointer position
        $tp = 0;

        while ($tp < strlen($text)) {
            $char = ord($text[$tp]); // int!
            if ($doublebottom) { // if we're on the bottom row of a double height bit
                $char = $prev[$cx]; // use character from previous row!
            } else { // otherwise
                $prev[$cx] = $char; // store this character for next time ..
            } 

            $fnum = $fontnum;
            // if (($format & 15) < 3) { 
            // strip top bit in image files
            $char = $char & 127;
            // }
			// save last graphics char for hold mode
            if (($char & 32) && $graphics) $holdchar = $char;
            if ($char < 32) {
                switch ($char + 128) { // just for consistency ** remove this**
                    case 128;			// black
                    if ($black != 1) {
                        break;
                } 
                case 129:			// other coours
                case 130:
                case 131:
                case 132:
                case 133:
                case 134:
                case 135:
                    $cf = $char;
                    $graphics = 0;
                    $conceal = 0;
                    break;
                case 136:		// flash on
                    $flash = 1;
                    break;
                case 137:		// flash off
                    $flash = 0;
                    break;
                case 140:		// double height off
                    $double = 0;
                    break;
                case 141:		// double height on
                    if (!$doublebottom) $nextbottom = 1;
                    $double = 1;
                    break;
                case 144;		// black graphics
                if ($black != 1) {
                    break;
                } 
                case 145:		// other colours
                case 146:
                case 147:
                case 148:
                case 149:
                case 150:
                case 151:
                    $cf = $char-16;
                    $graphics = 1;
                    $conceal = 0;
                    break;
                case 152: 		// conceal
                    $conceal = 1;
                    break;
                case 153:		// contiguous grapohics
                    $seperated = 0;
                    break;
                case 154:		// seperated graphics
                    $seperated = 1;
                    break;
                case 156:		// black background
                    $cb = 0;
                    break;
                case 157:		// new background (i.e. same as foreground)
                    $cb = $cf;
                    break;
                case 158:		// hold graphics mode on
                    $holdgraph = 1;
                    break;
                case 159:		// hold graphics mode off
                    $holdgraph = 0;
                    break;

                default: ;		// ignore all other control codes
                } // switch
                $char = 32;		// all codes display as a space unless hold mode on.
                if ($holdgraph == 1 && $graphics == 1) $char = $holdchar;
            } 
            // are we a flasher - i.e. is anything visible flashing?
            if ($flash == 1 && $char > 32) {
                $flasher = 1;
                if ($flashcycle == 1) $char = 32;
            } 
            // concealed text does not display
            if ($conceal == 1 && !$longdesc) $char = 32; 
            // only bottom of double height chars show up on line below a d.h character
            if ($doublebottom && (!$double || $longdesc)) $char = 32; 
            // offset to get graphics characters within fontfile
            if ($graphics) {
                if ($char & 32) { // actual graphics and not "blast through caps"
                    if ($longdesc) {
                        if ($longdesc == 2 && $char>32) {
                         	$char = 42; 
                        } else $char=32;  // ignore graphics in text mode   
                    } else {
                        $char += 96;
                        if ($char >= 160) $char -= 32;
                        if ($seperated) $char += 64;
                    } 
                } 
            } 
            // switch to alternate font files for double height
            if ($double) {
                if ($doublebottom) {
                    $fnum = $fontnumbot;
                } else {
                    $fnum = $fontnumtop;
                } 
            } 
            // OK we now have everything we need to write a character!
            if ($longdesc) {
                if ($char == 32) {
                    $longtext .= "&nbsp;";
                } else $longtext .= chr($char);
            } else {
                // draw background colour
                imagefilledrectangle($my_img, $lborder + ($cx * $fwidth), $tborder + ($cy * $fheight), $lborder + (($cx + 1) * $fwidth-1), $tborder + (($cy + 1) * $fheight-1) , $cb); 
                // draw character
                if ($char > 32) imagestring($my_img, $fnum , $lborder + ($cx * $fwidth) , $tborder + ($cy * $fheight) , chr($char) , $cf);
            } 
            // next..
            $cx++;
            if ($cx >= $width) {
                $cx = 0;
                $cy++;
                if ($longdesc) {
                    $longtext .= "<br />";
                } 
                if ($cy >= $height) {
                    $cy = 0;
                    break;
                } 
                $cf = 7;
                $cb = 0;
                $flash = 0; // flashing off
                $double = 0; // doubleheight off
                $graphics = 0; // text mode
                $seperated = 0; // normal graphics
                $holdgraph = 0; // hold mode off
                $holdchar = 32; // default hold char
                $conceal = 0;
                $doublebottom = $nextbottom;
                $nextbottom = 0;
            } 
            $tp++;
        } // while textpointer		       
        // write cache file
        if (!$longdesc) {

			if ($thumbnail) {
				imagecopyresampled($thumb_img,$my_img,0,0,0,0,
					$thumb_w,$thumb_h,$pwidth,$pheight); 

	            if ($flasher == 0) {
	                if ($donotcache != 1) {
	                    imagepng($thumb_img, "./cache/" . $cachepage);
	                } 
	            } else {
	                $fname = "./cache/" . $cachepage . "_" . $flashcycle . ".gif";
	                $frames[] = $fname;
	                $framed[] = $flashdelay[$flashcycle];
	                imagegif($thumb_img, $fname);
	            } 
			} else {

	            if ($flasher == 0) {
	                if ($donotcache != 1) {
	                    imagepng($my_img, "./cache/" . $cachepage);
	                } 
	            } else {
	                $fname = "./cache/" . $cachepage . "_" . $flashcycle . ".gif";
	                $frames[] = $fname;
	                $framed[] = $flashdelay[$flashcycle];
	                imagegif($my_img, $fname);
	            } 
			}
        } 
    } while (!$longdesc && $flasher > 0 && $flashcycle < 1); 
    // display image
    if ($longdesc) {
        header("Content-type: text/html");
	  	if ($longdesc == 2) echo "<pre>";
        echo $longtext;
	  	if ($longdesc == 2) echo "</pre>";
    } else {
        if (($flasher == 0)) {
            header("Content-type: image/png");
			if ($thumbnail) {
	            imagepng($thumb_img);
			} else {
	            imagepng($my_img);
			}
        } else {
            $gif = new GIFEncoder ($frames,
                $framed,
                0,
                2,
                1, 2, 3,
                "url"
                ); // 1,2,3 is the transparent colour; this one won't be in the image!
            $image = $gif->GetAnimation ();
      		// Write cache with animated image
	        if ($donotcache != 1) {
                $fname = "./cache/" . $cachepage;
				$fname .= ".gif";
                fwrite (fopen ($fname, "wb"), $image);
            } 
            header ('Content-type:image/gif');
            echo $image;
        } 
        // clean closedown
        for ($i = 0;$i < 8;$i++) imagecolordeallocate($my_img, $colour[$i]);
        imagedestroy($my_img);
		if ($thumbnail) {
		    imagedestroy($thumb_img);
		}
    } 
} 

?>