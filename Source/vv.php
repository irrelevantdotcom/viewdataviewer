<?php

/**
 * Teletext image viewer
 *
 * @version 0.5.P beta
 * @copyright 2010 Rob O'Donnell. robert@irrelevant.com
 *
 *
 *   See README.TXT for important information.
 *
 *
 * This is a very simple renderer that **cannot cope with "dynamic" frames! **
 * (proviso = if it detects you need raw mode, or you set format | 1024, then it
 * can cope with them, but you only get to see the final result, not the dynamic
 * effects.)
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
 * format = 0 - auto, 1=mode7, 2=gnome, 3=raw, 4=ABZTtxt (JGH) 5-Axis
*  6 = !SVReader, 7=Axis "i" format, 8 Spectrum +3 files, 9 .EPX files.
* 10= .TT files 11=.pic/.idx  12=.EP1 files
* add 4096 for .tt mode parsing
* * add 2048 to force reveal mode
* * add 1024 to always parse raw moe
* *  add 512 for $top to overwrite top line of page
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

if (!function_exists('numtest')) {
	function numtest($str){
		$num=TRUE;
		for ($i=0;$i<strlen($str);$i++) {
			if ((ord(substr($str,$i,1))&127) >= ord("A")) $num=FALSE;
			if ((ord(substr($str,$i,1))&127) == ord(" ")) $num=FALSE;

		}
		return $num;
	}
}

vv_main();




function vv_main(){




include "GIFEncoder.class.php";

if (!function_exists('similar_file_exists')) {
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
}

$error = "";
// image size in characters
$width = 40;
if (isset($_GET["width"])) {
    if (is_numeric($_GET["width"])) $width = $_GET["width"];
    // else $error = "Invalid width";
}

$height = 25;
$framelength = 0;
if (isset($_GET["height"])) {
    if (is_numeric($_GET["height"])) $height = $_GET["height"];
    // else $error = "Invalid height";
	if ($height < 0) {
		$framelength =-$height;
		$height = 24;
	}
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
$flashdelay[0] = 100;  // flashing text visible, concealed text hidden
$flashdelay[1] = 33;   // flashing text hidden,  concealed text hidden
// config stuff
$black = 1; // support black ink (not available on SAA5050...)
// what to display..
$donotcache = 0;
$alwaysrender = 0;
$ttmode = 0;
$page = "";
if (isset($_GET["page"])) {
    if (preg_match('/^[a-zA-Z0-9_.]{1,16}$/', $_GET['page'])) $page = $_GET["page"];
    else $error = "Invalid page number";
} else {
	if (isset($_GET["text"])) {
//	    $text = substr(html_entity_decode($_GET["text"]),0,$width);
		$text = urldecode(html_entity_decode($_GET["text"]));

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
    	if ($format & 4096) {
    		$ttmode =1;
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

			if (($format & 15) == 0 && substr($text,0,3) == "JWC") {
			    $format += 9;
			}

        	if (($format & 15) == 0 && substr($text,0,8) == "PLUS3DOS") {
        		// Ripped from a spectrum disc. assume it's a viewer file
        		// as I don't have anything else yet!
        		$format +=8;
        	}

			if (($format & 15) == 0 && strlen($text) >= 5120 ) {
				if ( substr($text,4096,2) == chr(0)."F"
				    && substr($text,4098,10) == substr($text,16,10)
					) { // Axis database
				 	  $format +=5;
				}

			}
			if (($format & 15) == 0 && strlen($text) >= 5120 ) {
				if ( substr($text,4096,2) == chr(240)."i"
				    && substr($text,4098,10) == substr($text,16,10)
					) { // Axis "i" database
				 	  $format +=7;
				}




			}



			if (($format & 15) == 0 && ($text[21] == "Y" || $text[21] == "N") &&
			($text[22] == "Y" || $text[22] == "N") && ($text[10] == "Y" || $text[10] == "N")
			) {  // !SVreader
			    $format += 6;
			}


//        	if (($format & 15) == 0 && numtest(substr($text,42,7))) {	// looking for time ... not very good.
        	if (($format & 15) == 0 && (strtolower(substr($page,-3,3))==".tt")) {
        		$format += 10; // .TT file
        	}
        	if (($format & 15) == 10 ) $ttmode = 1;

        	if (($format & 15) == 0 && (strtolower(substr($page,-4,4))==".pic")) {
        		$format += 11;
        	}
        	if (($format & 15) == 0 && is_numeric(substr($text,0,9)) && ctype_alpha(substr($text,9,1) && is_numeric(substr($text,10,99))) ) {
        		$format += 11;
        	}

        	if (($format & 15) == 0) {
        		$cnt = 0;
        		for ($i=0; $i+=40; $i<1024) {
        			$c = ord(substr($text,$i,1)) & 127;
					if ( $c < 9 && $c != 0) {	// colour code ?
        				$cnt++;
        			}
        		}
        		if ($cnt >3) {	// more than three lines start with a colour?
        			$format += 1;
        		}
        	}




	// skip over any file headers (not frame headers)

			if ((($format & 15) == 5 || ($format & 15) == 7) && $offset == 0) {
			    $offset = 4096;
			}
			if (($format & 15)==8 && $offset == 0) {
			    $offset = 128;
			}
			if (($format & 15) == 9 && $offset == 0) {
			    $offset = 4;
			}

		// jump to offset

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
        		$char = ord(substr($text, 920, 1)); // ABVTtxt version byte
        		$routing = substr($text, 936, 64); // scan routing area for 000000
        		if (($char == 13 || ($char > 1 && $char < 6)) && strpos($routing, chr(0) . chr(0) . chr(0)) !== false) {
        			$format += 4; // ABZTtxt
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
					} else {
						for ($i=3; $i<104 && $notsm == FALSE; $i++) {
							if ($text[$i]<" " || $text[$i] > "z") $notsm = TRUE;
						}
						if (!$notsm) $format+=2; // likely
					}
				}
            }

			// strip frame headers and trim to page length.
            if (($format & 15) == 2) {
                $text = substr($text, 104, 920);
                $height = 24; // 23+1 blank.
            }
            if (($format & 15) == 4) {
                $text = substr($text, 0, 920);
                $height = 24; // 23+1 blank
            }
			if (($format & 15) == 5) {
				$text = substr($text,64,920);
				$height = 24;
			}
			if (($format & 15) == 6) {
				$text = substr($text,190);
				$height = 24;
			}
			if (($format & 15) == 7) {
				$text = substr($text,104,920);
				$height = 24;
			}
			if (($format & 15) == 8) {
				$text = substr($text,0,960);
				$height = 24;
			}
			if (($format & 15) == 9) {
   				$text = substr($text,8,1000);
				$height = 25;
			}
			if (($format & 15) == 10) {
				if ($framelength == 0) $framelength = ord(substr($text,0,1))+256*ord(substr($text,1,1));
				// blank first five characters of page
				$text = "        " . substr($text,18,$framelength-18);
				$height = 24;
			}

        	if (($format & 15) == 11) {
        		$text=substr($text,109);
        		$text=substr($text,0,strpos($text,chr(255))-1);
        		$height=24;
        	}
        	if (($format & 15) == 12) {
        		$text = substr($text,6,1000);
        		$height = 24;
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

	if ($ttmode) {
		$t="";
		for ($x=0;$x<strlen($text);$x++) {
			$c=ord(substr($text,$x,1));
			if ($c == 15) {
				$t .= str_repeat(substr($text,$x+2,1),ord(substr($text,$x+1,1)) );
				$x += 2;
			} else $t .= chr($c);
		}
		$text = $t;
	}

	if (($format & 15) == 3 || ($format & 15)==6 || ($format & 15)==11 ||($format & 1024)) {	// RAW mode
		$rawtext = $text;
		$text = str_repeat(" ",$width*$height);
		$cx = 0; $cy=0;
		$tp = 0; $esc=0;
        while ($tp < strlen($rawtext)) {
            $char = ord($rawtext[$tp]);
			switch(0+$char){
				case 0 :
					break;
				case 13 :
					$cx = 0;
					break;
				case 9:
					$cx++;
					break;
				case 10 :
					$cy++;
					if ($cy>$height - 1) $cy=0;
					break;
				case 8:
					$cx -= 1;
					break;
				case 11:
					$cy--;
					if ($cy<0) $cy=$height - 1;
					break;
				case 27:
					$esc = 1; //!$esc;
					break;
				case 30:
					$cx = $cy = 0;
					break;
				case 15:

					break;

				default:
					if ($esc) {
						$esc = 0;
						$char = $char & 31;
					}
					$text[($cx+($width * $cy))] = chr($char);
					$cx++;
					break;
			} // switch
			if ($cx>$width - 1) {
			    $cx=0;
				$cy++;
				if ($cy>$height - 1) $cy=0;
			}
			if ($cx<0) {
			    $cx=$width - 1;
				$cy--;
				if ($cy<0) $cy=$height - 1;
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
		$newcf=7;
        $cb = 0;
        $flash = 0; // flashing off
		$newflash=0;
        $double = 0; // doubleheight off
        $graphics = 0; // text mode
    	$newgraph = 0;
        $seperated = 0; // normal graphics
		$newsep=0;
        $holdgraph = 0; // hold mode off
    	$newhold = 0;
        $holdchar = 32; // default hold char
        $conceal = 0;
    	$newconc = 0;
        // starting textpointer position
        $tp = 0;

        while ($tp < strlen($text)) {
			$cf=$newcf;
			$flash=$newflash;
			$seperated=$newsep;
        	$holdgraph=$newhold;
        	$graphics=$newgraph;
        	$conceal=$newconc;

            $char = ord($text[$tp]); // int!
            if ($doublebottom) { // if we're on the bottom row of a double height bit
                $char = $prev[$cx]; // use character from previous row!
            } else { // otherwise
                $prev[$cx] = $char; // store this character for next time ..
            }

            $fnum = $fontnum;
            // if (($format & 15) < 3) {

			if (($format & 15) == 5 || ($format & 15) == 7) {
				if ($char & 128) {	// top bit set
				    $char -= 192;
				}

			}
            // strip top bit in image files
            $char = $char & 127;
            // }
            if ($char < 32) {
                switch ($char + 128) { // just for consistency ** remove this**
                    case 128;			// black
                case 129:			// other coours
                case 130:
                case 131:
                case 132:
                case 133:
                case 134:
                case 135:
                	if ($char || $black == 1) {
                		$newcf = $char;
                	}
                    $newgraph = 0;
                	$newconc = 0;
                    break;
                case 136:		// flash on
                    $newflash = 1;
                    break;
                case 137:		// flash off
                    $newflash = 0;
                    break;
                case 140:		// double height off
                    $double = 0;
                    break;
                case 141:		// double height on
                    if (!$doublebottom) $nextbottom = 1;
                    $double = 1;
                    break;
                case 144;		// black graphics
                case 145:		// other colours
                case 146:
                case 147:
                case 148:
                case 149:
                case 150:
                case 151:
                	if ($char != 16 || $black == 1) {
                		$newcf = $char-16;
                	}
                    $graphics = 1;
                	$newgraph = 1;	// CHECK does graphics mode start immediately?
                    $newconc = 0;
                    break;
                case 152: 		// conceal
                    $conceal = 1; // immediately
                	$newconc = 1;
                    break;
                case 153:		// contiguous grapohics
                    $newsep = 0;
                    break;
                case 154:		// seperated graphics
                    $newsep = 1;
                    break;
                case 156:		// black background
                    $cb = 0;
                    break;
                case 157:		// new background (i.e. same as foreground)
                    $cb = $cf;
                    break;
                case 158:		// hold graphics mode on
                    $holdgraph = 1; // hold works immediately
                	$newhold = 1;
                    break;
                case 159:		// hold graphics mode off
                    $newhold = 0; // release works after this cell.
                    break;

                default: ;		// ignore all other control codes
                } // switch
                $char = 32;		// all codes display as a space unless hold mode on.
                if ($holdgraph == 1 && $graphics == 1) $char = $holdchar;
            } else { // char>= 32
            	if ($graphics) {
            		if ($char & 32) { // actual graphics and not "blast through caps"
            			if ($longdesc) {
            				if ($longdesc == 2 && $char>32) {
            					$char = 42;	// star
            				} else $char=32;  // ignore graphics in text mode
            			} else {
            				$char += 96;
            				if ($char >= 160) $char -= 32;
            				if ($seperated) $char += 64;
            			}
            		}
            	}

            }

           	// save last graphics char for hold mode
           	if (($char >= 128) && $graphics) {
           		$holdchar = $char;
           	} else $holdchar = 32;


            // are we a flasher - i.e. is anything visible flashing?
            if ($flash == 1 && $char > 32) {
                $flasher = 1;
                if ($flashcycle == 1) $char = 32;
            }
            // concealed text does not display
            if (($format & 2048)==0 && $conceal == 1 && !$longdesc) $char = 32;
            // only bottom of double height chars show up on line below a d.h character
            if ($doublebottom && (!$double || $longdesc)) $char = 32;
            // offset to get graphics characters within fontfile
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
				$newcf = 7;
                $cb = 0;
                $flash = 0; // flashing off
				$newflash = 0;
                $double = 0; // doubleheight off
                $graphics = 0; // text mode
            	$newgraph = 0;
                $seperated = 0; // normal graphics
				$newsep = 0;
                $holdgraph = 0; // hold mode off
            	$newhold = 0;
                $holdchar = 32; // default hold char
                $conceal = 0;
            	$newconc = 0;
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
		$longtext = str_replace(array("#","_","[","]","{","\\","}","~","`"),array("&pound;","#","&laquo;","&raquo;","&frac14;","&frac12;","&frac34;","&divide;","-") , $longtext);


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
			// kill temporary files
			foreach ($frames as $fnam) unlink($fnam);

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


}
?>