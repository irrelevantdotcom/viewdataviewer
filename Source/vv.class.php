<?php

/**
 * Viewdata Viewer as a Class
 *
 * The object of this file is to present a standard API to handle
 * frames presented in any format. i.e. should a new file type turn
 * up, /this/ is the only code that should need updating.
 *
 *
 *
 * @version 0.6.3
 * @copyright 2011 Robert O'Donnell
 */

// TODO: BuildIndex AXIS frame types.
// TODO: check that all routines check a valid frame has been loaded!
// TODO: finish metadata routines for all frame formats
// TODO: +try and identify page number from text on page. remember teletext!
// TODO: +try and find any dates within text on page. multiple formats!
// TODO: routine to insert text to frame (replace topline facility)
// TODO: routine to retrieve text from part of frame
// TODO: store image in $frameindex rather than creating it every time.
//		(how many times will one want to fetch the same image in a session?)
// TODO: html text mode

// some useful constants.
// (all these defns were hard coded prior to creation of this file!)

define ("VVTYPE_MODE7",1);
define ("VVTYPE_GNOME",2);
define ("VVTYPE_RAW",3);
define ("VVTYPE_ABZTTXT",4);
define ("VVTYPE_AXIS",5);
define ("VVTYPE_SVREADER",6);
define ("VVTYPE_AXIS_I",7);
define ("VVTYPE_PLUS3",8);
define ("VVTYPE_EPX",9);
define ("VVTYPE_TT",10);
define ("VVTYPE_JOHNCLARKE",11);
define ("VVTYPE_EP1",12);

// flags as used by vv.php as modifiers to the format number.
//   Most of these (probably those marked **) are redundant.
// not intending to use these|format in here!!

define ("VVFLAG_ACORNFILENAME",16);	// change . in filenanes to /
define ("VVFLAG_NOREADCACHE",32);	// ** do not check cache for image
define ("VVFLAG_NOWRITECACHE",64);	// ** do not write image to cache
define ("VVFLAG_NOBLACK",128);		// do not allow "black as a colour"
define ("VVFLAG_NOCASE",256);		// ** case insensitive file access
define ("VVFLAG_OVERWRITETOP",512);	// Use supplied top line as line 0
define ("VVFLAG_RAWFIRST",1024);	// ** parse input as raw first
define ("VVFLAG_REVEAL",2048);		// do not conceal text
define ("VVFLAG_TTMODE",4096);		// **force TT mode

// function to return friendly name for a given type number.

function vvtypes($val){
	switch($val){
		case VVTYPE_MODE7:
			return "BBC Mode 7";
		case VVTYPE_GNOME:
			return "Autonomic Systems";
		case VVTYPE_RAW:
			return "Raw as-transmitted";
		case VVTYPE_ABZTTXT:
			return "ABZTtext (JGH)";
		case VVTYPE_AXIS:
			return "Axis Microbase";
		case VVTYPE_SVREADER:
			return "!SVReader format";
		case VVTYPE_AXIS_I:
			return "Axis \"I\" format";
		case VVTYPE_PLUS3:
			return "Sinclair PLUS3 extraction";
		case VVTYPE_EPX:
			return ".EPX file (Ant)";
		case VVTYPE_TT:
			return ".TT file (Ant)";
		case VVTYPE_JOHNCLARKE:
			return "JCC Workstation Data";
		case VVTYPE_EP1:
			return ".EP1 file (Ant)";
		default:
			return FALSE;
	}
}

//define ("CACHEDIR","./cache/");
define ("FONTDIR", dirname( __FILE__  )."/Font/");
define ("TEMPDIR", dirname( __FILE__  )."/cache/");

include "GIFEncoder.class.php";

class ViewdataViewer {


	// no need for external code to access these
	private $content;		// raw data.
	private $sourcefile;	//
	// probably won't need to access these from outside
	public $format;   		// format of data.

	// available to access if really really needed.
    public $framesfound;   // number of frames found in data
	public $frameindex = array();	 	// array of arrays that hold stuff about the files..





	// load and parse file into variables above.
	// return TRUE if successfull, FALSE if failed.
	// $file = file on disc, $hint = format we think it might be, $fsp = original filename
	function LoadFile($file, $hint = 0, $fsp = "") {
		if (file_exists($file)) {
			$this->content = file_get_contents($file);
			$this->sourcefile = $file;
			if ($fsp == "") $fsp = $file;
			if ($this->AnalyseFormat($hint, $fsp)) {
				return TRUE;
			} else {
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	// dito from data already held
	function LoadData($data, $hint = 0, $fsp = "") {
		$this->content = $data;
		$this->sourcefile = "";
		if ($this->AnalyseFormat($hint, $fsp)) {
			return TRUE;
		} else {
			return FALSE;
		}
	}




	// analyse and return format of data
	private function AnalyseFormat($hint = 0, $fsp = "")#
	{
//		global $this->content;
//		global $this->format;
//		global $this->framesfound;

		$format = 0;  // do we use $hint here or assume we know best?
		$fperfile = -1;

// first do the easy ones

// filename based
		if ($format == 0 && (strtolower(substr($fsp,-4,4))==".pic")) {
			$format += VVTYPE_JOHNCLARKE;
		}
		if ($format == 0 && (strtolower(substr($fsp,-3,3))==".tt")) {
			$format += VVTYPE_TT;
		}
		if ($format == 0 && (strtolower(substr($fsp,-4,4))==".ep1")) {
			$format += VVTYPE_EP1;
		}
		if ($format == 0 && (strtolower(substr($fsp,-4,4))==".epx")) {
			$format += VVTYPE_EPX;
		}

// magic strings
		if ($format == 0 && substr($this->content,0,3) == "JWC") {
			$format += VVTYPE_EPX;
		}
		if ($format == 0 && substr($this->content,0,8) == "PLUS3DOS") {
			// Ripped from a spectrum disc. assume it's a viewer file
			// as I don't have anything else yet!
			$format += VVTYPE_PLUS3;
		}

		if ($format == 0 && strlen($this->content) >= 5120 ) {
			if ( substr($this->content,4096,2) == chr(0)."F"
				&& substr($this->content,4098,10) == substr($this->content,16,10)) { // Axis database
				$format += VVTYPE_AXIS;
			}
		}
		if ($format == 0 && strlen($this->content) >= 5120 ) {
			if ( substr($this->content,4096,2) == chr(240)."i"
				&& substr($this->content,4098,10) == substr($this->content,16,10)) { // Axis "i" database
				$format += VVTYPE_AXIS_I;
			}
		}
		if ($format == 0 && ($this->content[21] == "Y" || $this->content[21] == "N") &&
			($this->content[22] == "Y" || $this->content[22] == "N") && ($this->content[10] == "Y" || $this->content[10] == "N")) {  // !SVreader
			$format += VVTYPE_SVREADER;
		}

// some reasonable assumptions
		if ($format == 0) {
			if (strpos(substr($this->content,0,920),chr(13).chr(10)) !== FALSE ||
			strpos(substr($this->content,0,920),chr(10).chr(13)) !== FALSE ) {
				$format += VVTYPE_RAW;
			}
		}
		if ($format == 0) {
			$char = ord($this->content[920]); // ABVTtxt version byte
			$routing = substr($this->content, 936, 64); // scan routing area for 000000
			if (($char == 13 || ($char > 1 && $char < 6)) && strpos($routing, chr(0) . chr(0) . chr(0)) !== false) {
				$format += VVTYPE_ABZTTXT; // ABZTtxt
			}
		}


// now we can only guess a format.  We were given a hint?

		if ($format == 0 && $hint > 0) $format += $hint;		// accept hint now.

// now the "let's guess" ones ...

		if ($format == 0) {
			$notsm=FALSE;
			$defsm=FALSE;
			// look for a SofMac route table with an empty route in it ("*")
			for ($i=0;$i<10 && $ntsm == FALSE;$i++) {
				$route=substr($this->content,14+9*$i,9);
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
				$format += VVTYPE_GNOME;
			} else if (!$notsm) {
				if (strpos(substr($this->content,0,16),chr(0).chr(0)) !== FALSE) {
					$format += VVTYPE_GNOME;
				} else if (chr(127 & ord($this->content[143])) == "p" &&
				is_numeric(chr(127 & ord($this->content[142])))) {
					$format += VVTYPE_GNOME; // found a 0p in the right place for a gnome host frame
				} else {
					for ($i=3; $i<104 && $notsm == FALSE; $i++) {
						if ($this->content[$i]<" " || $this->content[$i] > "z") $notsm = TRUE;
					}
					if (!$notsm) $format += VVTYPE_GNOME; //likely
				}
			}
		}


		if ($format == 0) {
			$cnt = 0;
			for ($i=0; $i<1024; $i+=40) {
				$c = ord($this->content[$i]) & 127;
				$d = ord($this->content[$i+1]) & 127;
				if ( $c < 9 && $c != 0 && $d > 8) {	// colour code followed by non-colour code
					$cnt++;
				}
			}
			if ($cnt >3) {	// more than three lines start with a colour?
				$format += VVTYPE_MODE7;
			}
		}




		if ($format == 0) {	// still couldn't decide
			return FALSE;
		} else {
			$this->format = $format;

			if (!$this->BuildIndex()) {
				return FALSE;
			}
			return TRUE;
		}
	}

	// index frames within file
	function BuildIndex(){
		$this->framesfound = 0;
		$flen = strlen($this->content);
		$temp = $this->content;
		$dat = "";	//was for filename..
		switch($this->format){
			case -1:
			case 0:
				return FALSE;
				break;

			case VVTYPE_RAW:
			case VVTYPE_SVREADER:
			case VVTYPE_GNOME:
			case VVTYPE_EP1;
			$h = 24;
			case VVTYPE_MODE7:
				if ($this->format == VVTYPE_MODE7 || $this->format == VVTYPE_EP1) $h = 25;
				$this->framesfound = 1;
				$this->frameindex[0] = array("file" => $dat, "offset" =>0, "size" => strlen($temp), "height" => $h);
				break;

			case VVTYPE_ABZTTXT:
				$this->framesfound = 1;
				$this->frameindex[0] = array("file" => $dat, "offset" =>0, "size" => min(array(920,strlen($temp))), "height" => 23);
				break;


			case VVTYPE_JOHNCLARKE:
				$temp = file_get_contents(substr($this->sourcefile,0,strlen($this->sourcefile)-4).".IDX");
				for ($offset = 0; $offset < strlen($temp); $offset +=9)	{
					$pn = 0;
					for ($i = 0; $i<5; $i++) $pn=256*$pn + ord(substr($temp,$offset+$i,1));

					$this->frameindex[$pn] = array("file" => $dat,
					"offset"=>65536*ord($temp[$offset+6])+256*ord($temp[$offset+7])+ord($temp[$offset+8]),
					"size" => -1,
					"height"=>24);
					$this->framesfound++ ;
				}
				break;

			case VVTYPE_AXIS:
			case VVTYPE_AXIS_I:
				$index="";
				$data=""; //substr($temp,0,4096);
				if (strlen($temp)>356352) {
					for ($j=0; $j<strlen($temp); $j+=352256) {
						$data .= substr($temp,$j,4096);
					}
				}
				for ($j=0; $j<strlen($data); $j+=4096) {
					for ($i=16;  $i<4096; $i+=12) {
						$id = ord($data[10+$i+$j])+256*ord($data[11+$i+$j]);
						$offset = 4096 + 1024 * ($this->framesfound);
						$offset += 4096*(int)($this->framesfound /352256);	// TODO 2012; i think this is wrong.
						if ($id) {
							$this->frameindex[trim(substr($data,$i+$j,10))] = array("file" => $dat,
							"offset"=> $offset,
							"size" => 1024,
							"height"=>24,
							"id"=>$id
							);
							$this->framesfound++ ;
						}

					}
				}
				// TODO

				break;

			case VVTYPE_PLUS3:
				for ($offset = 128; $offset < $flen; $offset += 960) {
					if ($flen - $offset > 500) { // lose crap at end of file
						$this->frameindex[$this->framesfound] = array("file" => $dat, "offset" => $offset, "size" => 960, "height" =>24);
						$this->framesfound++ ;
					}
				}
				break;

			case VVTYPE_EPX:
				for ($offset = 4; $offset < $flen; $offset += 1008) {
					if ($flen - $offset > 500) { // lose crap at end of file
						$this->frameindex[$this->framesfound] = array("file" => $dat, "offset" => $offset, "size" => 1008, "height" =>25);
						$this->framesfound++ ;
					}
				}
				break;

			case VVTYPE_TT:
				$offset = 0;
				$len = ord(substr($temp,$offset,1))+256*ord(substr($temp,$offset+1,1));
				$blkcnt=0;
				$fltemp = array();
				while($offset < $flen && ord(substr($temp,$offset-($offset%2048)+2047,1))<5){
					$ttpage=ord(substr($temp,$offset+2,1))+256*ord(substr($temp,$offset+3,1));
					if ($len) {
						$fltemp[] = array("file" => $dat, "offset" => $offset, "size" => $len, "height" => 24, "ttpage"=>$ttpage);
						$offset += $len;
						$this->framesfound++;
					}
					$blkcnt++;

					if ($blkcnt > ord(substr($temp,$offset-($offset%2048)+2047,1))) {
						$offset += 2048-($offset%2048);
						$blkcnt=1;
					}
					$len = ord(substr($temp,$offset,1))+256*ord(substr($temp,$offset+1,1));
				}

				if (!function_exists("usortcmp")) {
					function usortcmp($a,$b){
						if ($a["ttpage"] == $b["ttpage"]) return 0;
						return ($a["ttpage"] < $b["ttpage"]) ? -1 : 1;
					}
				}
				usort($fltemp,"usortcmp");
				$this->frameindex = array_merge($this->frameindex,$fltemp);
				break;

			default:	// actually, there shouldn't be anything arriving here now...
/*				foreach (array(960,1024,1000,1090) as $fsize) {
					if (abs($flen % $fsize) < 10) {  // allow for just a few bytes of crud on a file.
						for ($offset = 0; $offset < $flen; $offset += $fsize) {
							if ($flen - $offset > 500) { // lose crap at end of file
								$this->frameindex[$this->framesfound] = array("file" => $dat, "offset" => $offset, "size" => $fsize, "height" =>24);
								$this->framesfound++ ;
							}
						}
						break;
					}
				}
*/
		} // switch
		if ($this->framesfound == 0) {
			return FALSE;
		}
		return TRUE;
	}



	// return metadata for a screen
	// this is coded fairly simply and verbosely for ease of maintenance.
	// I thought about getting clever and using tables of offsets etc, but why bother?

	// remember, these should return consistent information whatever the type of data file.

	function ReturnMetaData($idx = NULL, $param){
		if ($idx === NULL) {
			reset($this->frameindex);
			$idx = key($this->frameindex);
		}
		switch($this->format){
			case VVTYPE_GNOME: // yes I know offset will almost certainly always be Zero ..
				switch($param){
					case "flags":	// type-dependent flags
						return ord($this->content[$this->frameindex[$idx]["offset"]]);
						break;
					case "cug":		// closed user group
						return substr($this->content,$this->frameindex[$idx]["offset"]+3,5);
						break;
					case "access":	// Y/N is frame accessible
						return $this->content[$this->frameindex[$idx]["offset"]+8];
						break;
					case "type":	// "i" information frame, etc.
						return $this->content[$this->frameindex[$idx]["offset"]+9];
						break;
					case "count":	// access count
						$c = 0;
						for ($i=0; $i<4; $i++) $c = 256 * $c + ord($this->content[$this->frameindex[$idx]["offset"]+10+$i]);
						return $c;
						break;
					case "route0":
						return ($r = trim(substr($this->content,$this->frameindex[$idx]["offset"]+14,9))) == "*" ? "" : $r;
 						break;
					case "route1":
						return ($r = trim(substr($this->content,$this->frameindex[$idx]["offset"]+23,9))) == "*" ? "" : $r;
						break;
					case "route2":
						return ($r = trim(substr($this->content,$this->frameindex[$idx]["offset"]+32,9))) == "*" ? "" : $r;
						break;
					case "route3":
						return ($r = trim(substr($this->content,$this->frameindex[$idx]["offset"]+41,9))) == "*" ? "" : $r;
						break;
					case "route4":
						return ($r = trim(substr($this->content,$this->frameindex[$idx]["offset"]+50,9))) == "*" ? "" : $r;
						break;
					case "route5":
						return ($r = trim(substr($this->content,$this->frameindex[$idx]["offset"]+59,9))) == "*" ? "" : $r;
						break;
					case "route6":
						return ($r = trim(substr($this->content,$this->frameindex[$idx]["offset"]+68,9))) == "*" ? "" : $r;
						break;
					case "route7":
						return ($r = trim(substr($this->content,$this->frameindex[$idx]["offset"]+77,9))) == "*" ? "" : $r;
						break;
					case "route8":
						return ($r = trim(substr($this->content,$this->frameindex[$idx]["offset"]+86,9))) == "*" ? "" : $r;
						break;
					case "route9":
						return ($r = trim(substr($this->content,$this->frameindex[$idx]["offset"]+95,9))) == "*" ? "" : $r;
						break;
					case "ip":		// Information provider.  Name in header? ID? what?
						// TODO - utilise owner field
						// TODO - return text-only version of header.
						$ip = substr($this->content,$this->frameindex[$idx]["offset"]+104,24);
						if (substr($ip,0,4) == "    " ||
							substr($ip,0,4) == chr(160).chr(160).chr(160).chr(160) ||
							substr($ip,0,4) == chr(0).chr(0).chr(0).chr(0) ) {
								$ip="";
						}
						return $ip;
						break;
					case "owner":	// Gnome-specific owner field. always 10 u/c cars TGGTGGTGGT
						$o = substr($this->content,$this->frameindex[$idx]["offset"]+128,10);
//						$p = trim(substr($this->content,$this->frameindex[$idx]["offset"]+129,10));
						if (strlen(trim($o)) == 10 && strtoupper($o) == $o ) {
							return $o;
						} else {
							return FALSE;
						}
						break;
					case "editcug":
						return substr($this->content,$this->frameindex[$idx]["offset"]+139,5);
						break;
					case "pagenumber": // original page number, if stored. 123456789a
						$o = substr($this->content,$this->frameindex[$idx]["offset"]+128,10);
						$p = trim(substr($this->content,$this->frameindex[$idx]["offset"]+129,10));
						if (strlen(trim($o)) < 10 || strtoupper($p) != $p) {
							return $p;
						} else {
							return FALSE;
						}
						break;
					default:
						return FALSE;
				} // switch
				break;
			case VVTYPE_AXIS:
				switch($param){
					case "pagenumber":	// original page number
						//return trim(substr($this->content,$this->frameindex[$idx]["offset"]+2,10));
						return $idx;
						break;
					case "route0":
						// TODO do we return page number or id number?  frameindex is currently indexec by id number!
						break;
					default:
						return FALSE;
				}
																;
				break;
			case VVTYPE_AXIS_I:
				$i=0;
				switch($param){
					case "pagenumber":	// original page number
//						return trim(substr($this->content,$this->frameindex[$idx]["offset"]+2,10));
						return $idx;
						break;
					case "route9":
						$i++;
					case "route8":
						$i++;
					case "route7":
						$i++;
					case "route6":
						$i++;
					case "route5":
						$i++;
					case "route4":
						$i++;
					case "route3":
						$i++;
					case "route2":
						$i++;
					case "route1":
						$i++;
					case "route0":
						$route="";
						for ($j=0;$j<5;$j++)
							$route .= str_pad(dechex(ord($this->content[$this->frameindex[$idx]["offset"]+34+$j+5*($i % 10)])),2,"0",STR_PAD_LEFT);
						$route = 0+$route; // 800FFFFFFF -> 800
						if ($route == 0) $route = "";
						return $route;
						break;
					default:
						return FALSE;
				}
				break;
/*			case VV:
				;
				break;

*/// TODO the rest of the formats!  Do any others actually have any metadata?
			default:
				return FALSE;
		} // switch
	}



	// return a screen image in some format
	function ReturnScreen($idx = NULL, $mode = "internal")
	{
		if ($idx === NULL) {
			reset($this->frameindex);
			$idx = key($this->frameindex);
		}

		if (!array_key_exists($idx, $this->frameindex)) {
			return FALSE;
		}
		switch($mode){
			case "internal":
				if (!array_key_exists("internal", $this->frameindex[$idx])) {
					if ($this->LoadInternalFrame($idx) === FALSE) return FALSE;
				}
				return $this->frameindex[$idx]["internal"];

			case "simple":
				if (!array_key_exists("internal", $this->frameindex[$idx])) {
					if ($this->LoadInternalFrame($idx) === FALSE) return FALSE;
				}
				$t = "";
				for ($y=0; $y<$this->frameindex[$idx]["height"]; $y++){
					for ($x=0; $x<40; $x++) {
						if (($c=ord($this->frameindex[$idx]["internal"][$y*40+$x]))>31) {
							$t .= chr($c);
						} else {
							$t .= " ";
						}
					}
					$t .= "\n";
				}
				return $t;

			case "text":
				if (!array_key_exists("internal", $this->frameindex[$idx])) {
					if ($this->LoadInternalFrame($idx) === FALSE) return FALSE;
				}
				$t = $this->createImage(1, $this->frameindex[$idx]["internal"]
					,40,$this->frameindex[$idx]["height"],0,0);
				return $t;

			case "stars":
				if (!array_key_exists("internal", $this->frameindex[$idx])) {
					if ($this->LoadInternalFrame($idx) === FALSE) return FALSE;
				}
				$t = $this->createImage(2, $this->frameindex[$idx]["internal"],40,
					$this->frameindex[$idx]["height"],0,0);
				return $t;

			case "html":
				;	//TODO
					break;


			case "image":
			case "imagetype":
				if (!array_key_exists($mode, $this->frameindex[$idx])) {
					if (!array_key_exists("internal", $this->frameindex[$idx])) {
						if ($this->LoadInternalFrame($idx) === FALSE) return FALSE;
					}
					$type="";
					$t = $this->createImage(0, $this->frameindex[$idx]["internal"],40,
							$this->frameindex[$idx]["height"],0,0);
					$this->frameindex[$idx]["image"] = $t["image"];
					$this->frameindex[$idx]["imagetype"] = $t["imagetype"];
					return $t[$mode];
				} else
					return $this->frameindex[$idx][$mode];

			default:
				return FALSE;
		}
	}




	// Load data into basic frame buffer.
	// frame buffer is straight 40x(height) characters. 7 bit, <32=colours etc.
	// exactly as per most of saved formats!
	function LoadInternalFrame($idx = NULL, $rawtext = NULL){
		if ($idx === NULL) {
			reset($this->frameindex);
			$idx = key($this->frameindex);
		}
		if (!array_key_exists($idx, $this->frameindex)) {
			return FALSE;
		}

		if ($rawtext === NULL) {
			if (-1 == ($l = $this->frameindex[$idx]["size"])) {
				// length == -1 means unknown length but terminated with &FF. really? why? hmm.  This is qa VVTYPE_JOHNCLARKE format.
				$l = strpos($this->content,chr(255),$this->frameindex[$idx]["offset"]);
				$l = $l - $this->frameindex[$idx]["offset"] - 1;
			}
			$rawtext = substr($this->content, $this->frameindex[$idx]["offset"], $l );
		}

//		echo "idx $idx - size " .$this->frameindex[$idx]["size"] . "- height " .$this->frameindex[$idx]["height"] ."<br>\n";
//		echo htmlspecialchars($rawtext); // this prints crap just fine.

		$width = 40; // Will we ever need anything else? TODO: Consider this.
		$height = $this->frameindex[$idx]["height"];
		$text = str_repeat(" ",$width*$height);

//		echo "text " . strlen($text) . " raw " . strlen($rawtext);
		switch($this->format){
			case VVTYPE_EPX:
			case VVTYPE_EP1:
				$rp = 6;
				for ($tp=0; $tp<strlen($text) && $rp <strlen($rawtext); $tp++) {
					$text[$tp] = chr(ord($rawtext[$rp]) & 127);
					$rp++;
				}
				break;

			case VVTYPE_ABZTTXT:
				$rawtext = substr($rawtext,0,920);
			case VVTYPE_PLUS3:
				$rawtext = substr($rawtext,0,960);	// if ABZ will already be 920..
			case VVTYPE_MODE7:
				$rp = 0;
			case VVTYPE_GNOME:
				if ($this->format == VVTYPE_GNOME) $rp = 104;
				// TODO - Massage top line if it's a host frame not a saved frame.
				for ($tp=0; $tp<strlen($text) && $rp <strlen($rawtext); $tp++) {
					$text[$tp] = chr(ord($rawtext[$rp]) & 127);
					$rp++;
				}
				break;


			case VVTYPE_TT:
				$t="";
				$rawtext = "        " . substr($rawtext,18);//,$framelength-18);
				for ($x=0;$x<min(array(strlen($text),strlen($rawtext)));$x++) {
					$c=ord($rawtext[$x]);
					if ($c == 15) {
						$t .= str_repeat(chr(ord($rawtext[$x+2])&127),ord($rawtext[$x+1]));
						$x += 2;
					} else $t .= chr($c & 127);
				}
				$text = str_pad($t,$width*$height," "); // just in case it's short.
				break;


			case VVTYPE_AXIS_I:
				$tp = $rp = 104;
			case VVTYPE_AXIS:
/*				if ($this->format == VVTYPE_AXIS) $rp = 64;
				for ($tp=0; $tp<strlen($text) && $rp <1024; $tp++) {
					if (($c = ord($rawtext[$rp]))>127) $c -= 192;
					$rawtext[$tp] = chr($c);
					$rp++;
				}
*/
				if ($this->format == VVTYPE_AXIS) $tp = $rp = 64;
				for (; $rp<strlen($rawtext); $rp++) {
					if (($c = ord($rawtext[$rp]))>127) $rawtext[$rp] = chr($c-64);
				}

			case VVTYPE_SVREADER:		// ctrl codes plus colour codes 80-9F
				if ($this->format == VVTYPE_SVREADER) $tp = 190;
			case VVTYPE_JOHNCLARKE:		// ctrl codes
				if ($this->format == VVTYPE_JOHNCLARKE) $tp = 109;
			case VVTYPE_RAW:			// ctrl codes
				if ($this->format == VVTYPE_RAW ) $tp = 0;
				$cx = 0; $cy=0;
				$esc=0;
				while ($tp <strlen($rawtext)) {
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
								$char = $char & 31; // ESC A stored as #01
							}
							$text[($cx+($width * $cy))] = chr($char & 127);
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
				break;
			default:
//				echo "incorrect format?";
				return FALSE;
		} // switch

//		echo htmlspecialchars($text);
//		echo "text " . strlen($text) . " raw " . strlen($rawtext);
//		echo $text;

		$this->frameindex[$idx]["internal"] = $text;
		return TRUE;
	}




	function createImage($longdesc, $text, $width = 40, $height = 24, $flags = 0, $thumbnail = 0) {
		// ripped right out of vv 0.5.Q
		// therefore probably needs tidying up considerably.

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

		$longtext = "";


		if (!$longdesc) { // don't bother for text mode
			// read fonts
			$fontnum = imageloadfont(FONTDIR . "vvttxt.gdf");
			$fontnumtop = imageloadfont(FONTDIR . "vvttxtop.gdf");
			$fontnumbot = imageloadfont(FONTDIR ."vvttxbtm.gdf");
			if ($fontnum == 0 || $fontnumtop == 0 || $fontnumbot == 0) {
				//$error = "cannot find font file";
				return FALSE;
			}

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
//				echo "*";

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
				if (($flags & VVFLAG_REVEAL )==0 && $conceal == 1 && !$longdesc) $char = 32;
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
				} else $fnum = $fontnum;

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
			if (!$longdesc) {

				if ($thumbnail) {
					imagecopyresampled($thumb_img,$my_img,0,0,0,0,
						$thumb_w,$thumb_h,$pwidth,$pheight);

					if ($flasher) {
						$fname = tempnam(TEMPDIR,"vv"); //TEMPDIR . $cachepage . "_" . $flashcycle . ".gif";
						$frames[] = $fname;
						$framed[] = $flashdelay[$flashcycle];
						imagegif($thumb_img, $fname);
					}
				} else {

					if ($flasher) {
						$fname = tempnam(TEMPDIR,"vv"); //$fname = TEMPDIR . $cachepage . "_" . $flashcycle . ".gif";

						$frames[] = $fname;
						$framed[] = $flashdelay[$flashcycle];
						imagegif($my_img, $fname);
					}
				}
			}
		} while (!$longdesc && $flasher > 0 && $flashcycle < 1);
		// display image
		if ($longdesc) {
//			header("Content-type: text/html");
			$longtext = str_replace(array("#","_","[","]","{","\\","}","~","`"),array("&pound;","#","&laquo;","&raquo;","&frac14;","&frac12;","&frac34;","&divide;","-") , $longtext);

			return $longtext;
/*			if ($longdesc == 2) echo "<pre>";
			echo $longtext;
			if ($longdesc == 2) echo "</pre>";
*/
		} else {
			if (($flasher == 0)) {
//				header("Content-type: image/png");
				if ($thumbnail) {
//					imagepng($thumb_img);
					return array("image"=>$thumb_img, "imagetype"=>"png");
				} else {
//					imagepng($my_img);
					return array("image"=>$my_img, "imagetype"=>"png");
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
				// kill temporary files
				foreach ($frames as $fnam) unlink($fnam);

//				header ('Content-type:image/gif');
//				echo $image;
				return array("image"=>$image, "imagetype"=>"gif");
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