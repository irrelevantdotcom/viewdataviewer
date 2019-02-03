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
 * @version 0.7.4
 * @version 0.7.4
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
// TODO: html encoded text mode

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
define ("VVTYPE_VTF",13);		// Quantec QMX - not finished
define ("VVTYPE_G7JJF",14);		// http://g7jjf.com/teletext.htm. Is actually raw data stream....
define ("VVTYPE_TFLINKS",15);	// Pages grabbed from uniquecodeanddata
define ("VVTYPE_TTI",16);		// TTI and TTIx - inserter formats
define ("VVTYPE_24x40",17);		// TTX format.
define ("VVTYPE_25x40",18);		// TTX format.
define ('VVTYPE_GNOMEVAR',19);	// varient of gnome files found in some of rob's files
define ('VVTYPE_VTP',20);		// VTPlus files.

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
//define ("CACHEDIR","./cache/");
define ("FONTDIR", dirname( __FILE__  )."/Font/");
define ("TEMPDIR", dirname( __FILE__  )."/cache/");

include_once "GIFEncoder.class.php";

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

		return $this->AnalyseFormat($hint, $fsp);
//		if ($this->AnalyseFormat($hint, $fsp)) {
//			return TRUE;
//		} else {
//			return FALSE;
//		}
	}




	// analyse and return format of data
	private function AnalyseFormat($hint = 0, $fsp = "", $alwaysguess = false)
	{
//		global $this->content;
//		global $this->format;
//		global $this->framesfound;

//echo "hint $hint ";
		if ($hint && !$alwaysguess ) {
			$format = $hint;
		} else {
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
			if ($format == 0 && (strtolower(substr($fsp,-4,4))==".vtf")) {
				$format += VVTYPE_VTF;
			}

			if ($format == 0 && (strtolower(substr($fsp,-4,4))==".ttx")) {
				if (substr($this->content,0,20) == substr($this->content,1000,20)) {
					$format += VVTYPE_25x40;
				} else {
					$format += VVTYPE_24x40;
				}
			}

			if ($format == 0 && (strtolower(substr($fsp,-4,4))==".dat") &&
			substr($this->content,0,128) == str_repeat(chr(0),128)) {
				$format += VVTYPE_G7JJF;
			}

			if ($format == 0 && (strtolower(substr($fsp,-4,4))==".tti" ||
			strtolower(substr($fsp,-5,5))==".ttix")) {
				$format += VVTYPE_TTI;
			}

			// magic strings
			if ($format == 0 && strpos($this->content, 'DOCTYPE html') && strpos($this->content,'/#0:')) {
				$format += VVTYPE_TFLINKS;
			}
			if ($format == 0 && ((strtolower(substr($fsp,-4,4))==".vtp"
				|| substr($this->content,0,3) == 'VTP'))) {
				$format += VVTYPE_VTP;
			}


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

			// now we can only guess a format.  We were given a hint?

			if ($format == 0 && $hint > 0) $format += $hint;		// accept hint now.

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



			// now the "let's guess" ones ...

			if ($format == 0) {
				$notsm=FALSE;
				$defsm=FALSE;
				// look for a SofMac route table with an empty route in it ("*")
				for ($i=0;$i<10 && $notsm == FALSE;$i++) {
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

			if ($format == VVTYPE_GNOME && strlen($this->content >= 2048)) {
				$format = VVTYPE_GNOMEVAR;
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

		}




		if ($format == 0) {	// still couldn't decide
//			echo "no format";
			return FALSE;
		} else {
			$this->format = $format;

			if (!$this->BuildIndex()) {
//				echo "error in build index";
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
			case VVTYPE_GNOMEVAR:
			case VVTYPE_EP1;
			$h = 24;
			case VVTYPE_MODE7:

				$size = strlen($temp);
				if ($size < 2 * 960) {
					$h = (int) $size / 40;
					$this->framesfound = 1;
				} else {
					if ($this->format == VVTYPE_GNOME or $this->format == VVTYPE_GNOMEVAR) {
						$size = 1024;
						$h = 23;
					} else  {
						// deduce image height
						$nl = $size % 40;	// number of lines in file
						if ($nl % 24 == 0) { // exact multiple of 24 lines
							$h = 24;
						} else if ($nl % 23 == 0) {
							$h = 23;
						} else if ($nl % 25 == 0) {
							$h = 25;
						} else $h = 24;		// most obvious possibility
						$size = $h * 40;
					}
				}

				for ($offset = 0; $offset < strlen($this->content); $offset+= $size) {


					// try and find page number
					if ($this->format == VVTYPE_GNOME || $this->format = VVTYPE_GNOMEVAR) {
						$pn = '';
						for ($i = 129; $i < 139; $i++) {
							$pn .=  chr(ord($this->content{$offset + $i}) & 127);
						}
						$pn = trim($pn);
						$subpage = substr($pn,-1);
						$page = substr($pn,0,strlen($pn)-1);
					} else {

						$matches = array();
						// grab top line stripping top bits.
						$tl = '';
						for ($i=0; $i<40; $i++) {
							$tl .= chr(ord($this->content{$offset + $i}) & 127);
						}
						if (preg_match('/[0-9]{1,9}[a-z]/',$tl,$matches,PREG_OFFSET_CAPTURE)) {
							//print_r($matches);
							$page = substr($matches[0][0],0,strlen($matches[0][0]-1));
							$subpage = substr($matches[0][0],-1);
						} else if (preg_match('/[0-9A-F]{3}/',$tl,$matches,PREG_OFFSET_CAPTURE)) {
							//print_r($matches);
							$page = $matches[0][0];
							if ($tl{$matches[0][1] + 3} == '/') {
								$subpage = '00' . substr($tl,$matches[0][1] + 4,2);
							}
						} else  {// Unable to find a page number, use 000. (Not found in real teletext.)
							$page = '000';
							$subpage = '';
						}
					}
					if ($this->format == VVTYPE_MODE7 || $this->format == VVTYPE_EP1) $h = 25;
					$this->framesfound += 1;
					$this->frameindex[] = array("file" => $dat, "offset" =>$offset, "size" => $size, "height" => $h,
							'ttpage' => $page, 'subpage' => $subpage);
				}
				break;


			case VVTYPE_JOHNCLARKE:
				$temp = file_get_contents(substr($this->sourcefile,0,strlen($this->sourcefile)-4).".IDX");
				for ($offset = 0; $offset < strlen($temp); $offset +=9)	{
					$pn = 0;
					for ($i = 0; $i<5; $i++) $pn=256*$pn + ord(substr($temp,$offset+$i,1));
					$pn = "$pn" . $temp[$offset+5];
					$fo = 65536*ord($temp[$offset+6])+256*ord($temp[$offset+7])+ord($temp[$offset+8]);
					$page = substr($this->content,$fo,9);
					if ($page == "000000000") {
						$page = "0";
					} else {
						$page = ltrim($page,'0');
					}
					$subpage = substr($this->content,$fo+9,1);
					$this->frameindex[$pn] = array("file" => $dat,
					"offset"=>$fo,
					"size" => -1,
					"height"=>24,
					"ttpage"=>$page,
					"subpage"=>$subpage);
					$this->framesfound++ ;
				}
				break;

			case VVTYPE_TTI:
				$ol = false;		// no text found
				$meta = array();
				$tla = array();
				$lines =  preg_split ('/$\R?^/m', $this->content);
				$ptr = 0;
				foreach ($lines as $l){
//echo "$line<br />"					;
					if (trim($l) == '') {
						continue;
					}

					$cc = substr($l,0,2);
					$par = substr($l,3);
					// have we seen lines but not in a line now? must be another page so save the index.
					if ($ol && $cc != 'OL' && $cc != 'FL') {
						$this->frameindex[$this->framesfound] = array_merge($meta, array("file" => $dat,
								"offset"=> $ptr,
								"size" => 40+40*max(array_keys($tla)),	// not blocksize, this is size of image section.
								"height"=> 1+max(array_keys($tla)),
								"id"=>$this->framesfound,
								"ttpage" => $page,
								"subpage" => $subpage,
								"content" => $tla ));
						$this->framesfound++ ;

						$meta = $tla = array();
						$ol = false;
					}

					switch(substr($l,0,2)){
						case 'PN':
							$page = substr($l,3,3);
							if (strlen($l) > 6) {
								$subpage = substr('0000' . substr($l,6),-4);
							}
							break;
						case 'SC':
							$subpage = substr('0000' . $par,-4);
							break;
						case 'CT':
							$meta['cycletime'] = $par;
							break;
						case 'DE':
							$meta['description'] = $par;
							break;
						case 'PS':
							$meta['mrg-ps'] = $par;
							$meta['language'] = ($par & 896) / 128;
							break;
						case 'OL':
							$ol = true;
							$raw = substr($l,strpos($l,',',3)+1);
							$txt = '';																																		;
							for ($i = 0; $i < strlen($raw); $i++) {
								$char = ord($raw{$i}) & 127;
								if ($char == 16) {
									$char = 13;
								}
								if ($char == 27) {
									$char = (ord($raw{$i+1}) & 127) - 64;
									$i++;
								}
								$txt .= chr($char);
							}
							$tla[0|$par] = $txt;
							break;
						case 'FL':
							$links = explode(',',$par);
							if (count($links) == 6) {
								for ($i = 0; $i < 6; $i++) {
									if (hexdec($links[$i]) < 256) {	 #100
										$links[$i] = '';
									}
								}
								$meta['red'] = $links[0];
								$meta['green'] = $links[1];
								$meta['yellow'] = $links[2];
								$meta['blue'] = $links[3];
								$meta['link4'] = $links[4];
								$meta['index'] = $links[5];
							}
							break;
					} // switch
					$ptr += strlen($l)+2;
				}

				if ($ol) {
					$this->frameindex[$this->framesfound] = array_merge($meta, array("file" => $dat,
							"offset"=> $ptr,
							"size" => 40+40*max(array_keys($tla)),	// not blocksize, this is size of image section.
							"height"=> 1+max(array_keys($tla)),
							"id"=>$this->framesfound,
							"ttpage" => $page,
							"subpage" => $subpage,
							"content" => $tla));
//var_dump($this->frameindex[$this->framesfound]);
					$this->framesfound++ ;
				}
				break;

			case VVTYPE_ABZTTXT:
				$height = 23;
				$blocksize = 1024;
			case VVTYPE_25x40:
				if ($this->format == VVTYPE_25x40) {
					$height = 25;
					$blocksize = 1000;
				}
			case VVTYPE_24x40:
				if ($this->format == VVTYPE_24x40) {
					$height = 24;
					$blocksize = 960;
				}
				$index="";
				$data=""; //substr($temp,0,4096);
				$oldpage = '';
				for ($h = 0; $h < strlen($this->content); $h = $h+$blocksize) {
					$subpage = '';
					$matches = array();

					$tl = '';
					for ($i=0; $i<40; $i++) {
						$tl .= chr(ord($this->content{$h+$i}) & 127);
					}
					if (preg_match('/[0-9]{1,9}[a-z]/',$tl,$matches,PREG_OFFSET_CAPTURE)) {
						//print_r($matches);
						$page = substr($matches[0][0],0,strlen($matches[0][0]-1));
						$subpage = substr($matches[0][0],-1);
					} else
					if (preg_match('/[0-9A-F]{3}/',$tl,$matches)) {
						//print_r($matches);
						$page = $matches[0];
					} else
					if (preg_match('/[0-9A-F]{3}/',$this->sourcefile,$matches)) {
						//print_r($matches);
						$page = $matches[0];
						if ($page == $oldpage) {
							$subpage++;
						} else {
							$oldpage = $page;
							$subpage = 1;
						}
					} else {
						// grab top line stripping top bits.
						$page = '000';
					}

					if ($subpage == '') {
						if (substr($tl,0,5) != '     ') {
							$subpage = $this->zeropad(dechex(ord($this->content{$h+5})),2) . $this->zeropad(dechex(ord($this->content{$h+4})),2);
							$this->content = substr_replace($this->content, '        ', $h, 8);
						} else {
							// we don't have real subpage information, so just use a counter.
							if ($page == $oldpage) {
								$subpage++;
							} else {
								$oldpage = $page;
								$subpage = 1;
							}
						}
					}


					$this->frameindex[$this->framesfound] = array("file" => $dat,
							"offset"=> $h,
							"size" => 40*$height,	// not blocksize, this is size of image section.
							"height"=> $height,
							"id"=>$this->framesfound,
							"ttpage" => $page,
							"subpage" => $subpage );
					$this->framesfound++ ;
				}

				break;


			case VVTYPE_AXIS:
			case VVTYPE_AXIS_I:
				$index="";
				$data=""; //substr($temp,0,4096);

				for ($h = 0; $h < strlen($this->content); $h = $h+352256) {
					for ($i = 16; $i < 4096; $i = $i + 12) {
						$id = 256 * ord($this->content[$h+$i+10]) + ord($this->content[$h+$i+11]);
						$page = trim(substr($this->content,$h+$i,10));
						$subpage = substr($page,-1);
						$page = substr($page,0,strlen($page)-1);
						$offset = 4096 + 1024 * ($this->framesfound);
						$offset += 4096*(int)($this->framesfound /352256);
						if ($id) {
							$this->frameindex[$page.$subpage] = array("file" => $dat,
							"offset"=> $offset,
							"size" => 1024,
							"height"=>24,
							"id"=>$id,
							"ttpage" => $page,
							"subpage" => $subpage );
							$this->framesfound++ ;

						}

						}
					}

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
				for ($offset = 6; $offset < $flen; $offset += 1008) {
					if ($flen - $offset > 500) { // lose crap at end of file
						$ttpage = ord(substr($temp,$offset+8,1))+256*ord(substr($temp,$offset+9,1));
						$this->frameindex[$this->framesfound] = array("file" => $dat, "offset" => $offset, "size" => 1008, "height" =>25, "ttpage" => $ttpage);
						$this->framesfound++ ;
					}
				}
				break;

			case VVTYPE_VTP:
				$ttpage = dechex(ord($temp{5})) . substr('0' . dechex(ord($temp{4})),-2);
				$n = ord($temp{6});
				$offset = 0x76;
				$subpage = 0;
				for ($offset = 0x76; $offset < $flen; $offset += 970) {
					if ($flen - $offset > 500) { // lose crap at end of file

//						$date = $this->guess_date(substr($temp,$offset,40), '1900');

						$subpage = ord($temp{$offset + 0x3c2}) + 10 * ord($temp{$offset + 0x3c3});
						$this->frameindex[$this->framesfound] = array("file" => $dat, "offset" => $offset,
//							"date" => $date,
							"size" => 960, "height" =>24, "ttpage" => $ttpage, 'subpage' => substr('0000' . $subpage, -4));
						$this->framesfound++ ;
						$subpage++;
					}
				}
				break;


			case VVTYPE_TFLINKS:
				$dom = new DOMDocument();
				$temp = strip_tags($temp, '<a>');
				$dom->loadHTML($temp);
				$as = $dom->getElementsByTagName('a');
				$last = null;
				$subpage = 1;
				foreach ($as as $a){
					$pagenumber = $a->textContent;
					$link = $a->getAttribute('href');
//					echo $pagenumber . ' ' . $link . '<br>';
					if (strlen($link) > 1200 && strlen($pagenumber) == 3) {
						if ($pagenumber == $last) {
							$subpage++;
						} else {
							$subpage = 1;
						}
						$tf = substr($link, strpos($link, '#')+1);
						$this->frameindex[$this->framesfound] = array("file" => $dat, "tf" => $tf,
							 "size" => 1008, "height" =>25, "ttpage" => $pagenumber, "subpage" => substr('0000' . $subpage, -4));
						$this->framesfound++ ;
					}
					$last = $pagenumber;
				}
				break;

			case VVTYPE_TT:
				$offset = 0;
				$len = ord(substr($temp,$offset,1))+256*ord(substr($temp,$offset+1,1));
				$blkcnt=0;
				$fltemp = array();
				while($offset < $flen && ord(substr($temp,$offset-($offset%2048)+2047,1))<5){
					$ttpage=dechex(ord(substr($temp,$offset+14,1))+256*ord(substr($temp,$offset+15,1)));
//					$subpage=ord(substr($temp,$offset+16,1))+256*ord(substr($temp,$offset+17,1));
					$subpage = $this->zeropad(dechex(ord($temp{$offset+17})),2) . $this->zeropad(dechex(ord($temp{$offset+16})),2);
					if ($len) {
						$fltemp[] = array("file" => $dat, "offset" => $offset, "size" => $len, "height" => 24, "ttpage"=>$ttpage, "subpage"=>$subpage);
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

			case VVTYPE_VTF:	// Quantec QMX TODO
				// bloody hell this is ..  weird...

				// TODO... something. for now, very temporary single entry -
				$this->frameindex[$page] = array("file" => $dat,
				"offset"=> 0x400,
				"size" => 1024,
				"height"=>24,
				"id"=>"0a" );
				$this->framesfound = 1 ;

				// TODO !!!!

				break;
			case VVTYPE_G7JJF:		// this is basically a raw teletext data stream

				// ! Frame here relates to TV transmission frames!
 				$numFrames = (int)($flen / 860);
				For ($frame = 0; $frame < $numFrames; $frame++) {

 					$offset = $frame * 860; // + 1;
					$ScreenB = substr($temp, $offset, 860);

					// scan lines..
					For ($i = 3; $i <= 15; $i++) {

 						$X = ord($ScreenB{$i * 43 + 0});
 						$y = ord($ScreenB{$i * 43 + 1});

	 					$mag = ($X & 2) / 2 + (($X & 8) / 8) * 2 + (($X & 32) / 32) * 4;

	 					If ($mag == 0) $mag = 8;

	           			$row = ($X & 128) / 128 + (($y & 2) / 2) * 2 + (($y & 8) / 8) * 4 + (($y & 32) / 32) * 8 + (($y & 128) / 128) * 16;


//	'            Debug.Print "Offset : " & Hex$(i * 43 + frame * 860) & ", Row " & i & " : " & Hex$(x) & ", " & Hex$(y) & ", " & mag & ", " & row & " ";
//	            echo "Found at frame $frame Offset $offset  Mag $mag  Row $row <br>\n";

						If ($row == 0 And $X <> 0) {

			                $X = ord($ScreenB{$i * 43 + 2});
			                $y = ord($ScreenB{$i * 43 + 3});

			                $pageu = ($X & 2) / 2 + (($X & 8) / 8) * 2 + (($X & 32) / 32) * 4 + (($X & 128) / 128) * 8;
			                $paget = ($y & 2) / 2 + (($y & 8) / 8) * 2 + (($y & 32) / 32) * 4 + (($y & 128) / 128) * 8;

			                $page = $mag . dechex($paget) . dechex($pageu);

							$w = ord($ScreenB{$i * 43 + 4});
							$x = ord($ScreenB{$i * 43 + 5});
							$y = ord($ScreenB{$i * 43 + 6});
							$z = ord($ScreenB{$i * 43 + 7});

							$s1 = ($w & 2) / 2 + (($w & 8) / 8) * 2 + (($w & 32) / 32) * 4 + (($w & 128) / 128) * 8;
							$s2 = ($x & 2) / 2 + (($x & 8) / 8) * 2 + (($x & 32) / 32) * 4;
							$s3 = ($y & 2) / 2 + (($y & 8) / 8) * 2 + (($y & 32) / 32) * 4 + (($y & 128) / 128) * 8;
							$s4 = ($z & 2) / 2 + (($z & 8) / 8) * 2;

							$subpage = $s4.dechex($s3).$s2.dechex($s1);

							// Note that offset does not point to rhe exact start of the data we
							// require, but to the tv-frame where the header occurs somewhere within ...
							$this->frameindex[$this->framesfound] =
								array("file" => $dat,
								"offset" => $offset,
								"size" => 40*26,
								"height" =>26,				// top + 24 + fasttext line
								"ttpage" => $page,
								"subpage" => $subpage);
//	echo "added {$this->framesfound} $page:$subpage<br>\n";
							$this->framesfound++ ;
						}
					}
				}

				// anon function flags an error in phpedit v3 - it IS correct.
				usort ($this->frameindex, function($a, $b) {
					return strcmp($a['ttpage'].$a['subpage'],$b['ttpage'].$b['subpage']);
				});

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



	// return metadata for a frame
	// this is coded fairly simply and verbosely for ease of maintenance.
	// I thought about getting clever and using tables of offsets etc, but as every type
	// is so very different...

	// remember, these should return consistent information whatever the type of data file.
	// asking for NULL field should return an array of field names supported.

	function ReturnMetaData($idx = NULL, $param = NULL){
		if ($idx === NULL) {
			reset($this->frameindex);
			$idx = key($this->frameindex);
		}
		switch($this->format){
			case VVTYPE_GNOME: // yes I know offset will almost certainly always be Zero ..
			case VVTYPE_GNOMEVAR:	// but these probably won't

				switch($param){
					case NULL:
						return array('flags','cug','access','type','count','route0','route1','route2',
						'route3','route4','route5','route6','route7','route8','route9','ip','owner',
						'editcug','pagenumber');
						break;
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
						} else {
							$ipp = '';
							for ($i = 0; $i < strlen($ip); $i++) {
								$ipp .= chr(ord($ip{$i}) & 127);
							}
							$ip = $ipp;
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
/*						// TODO - sub-page ID ?
						$o = substr($this->content,$this->frameindex[$idx]["offset"]+128,10);
						$p = trim(substr($this->content,$this->frameindex[$idx]["offset"]+129,10));
						if (strlen(trim($o)) < 10 || strtoupper($p) != $p) {
							return array(substr($p,0,-1), substr($p,-1));
						} else {
							return FALSE;
						}
*/
						return array($this->frameindex[$idx]['ttpage'],$this->frameindex[$idx]['subpage']);
						break;
					default:
						return FALSE;
				} // switch
				break;
			case VVTYPE_AXIS:
				$i = 0;
				switch($param){
					case NULL:
						return array('pagenumber', 'route0', 'route1', 'route2', 'route3', 'route4',
						 'route5', 'route6', 'route7', 'route8', 'route9', 'route#' );	// erm ..
						break;

					case "pagenumber":	// original page number
						$p = trim(substr($this->content,$this->frameindex[$idx]["offset"]+2,10));
						return array(substr($p,0,-1), substr($p,-1));
						break;
					case "route#":
						$i++;
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
						$j = $this->frameindex[$idx]["offset"]+42+2*$i;
						$route = "";
						// get frame id for route
						$id = 256 * ord($this->content[$j]) + ord($this->content[$j+1]);
						if ($id != 0) {
							// scan content tables for this id
							for ($h = 0; $h < strlen($this->content); $h = $h+352256) {
								for ($i = 16; $i < 4096; $i = $i + 12) {
									$p = 256 * ord($this->content[$h+$i+10]) + ord($this->content[$h+$i+11]);
									if ($p == $id) {
										// found it, get textual page name.
										$route = trim(substr($this->content,$h+$i,10));
										break;
									}
								}

							}
							if ($route == "") {
								$route = "?NotFound";
							} else {
								// This format is odd in that any route can point to any frame,
								// not just the first 'a' frame of a page!  So, if destination
								// is NOT first frame on a page, return array of page, subframe id.
								if (substr($route,-1) == 'a') {
									return substr($route,0,-1);
								} else {
									return array(substr($route,0,-1),substr($route,-1));
								}
							}
						}

						return $route;
						break;
						break;
					default:
						return FALSE;
				}
																;
				break;
			case VVTYPE_AXIS_I:
				$i=0;
				switch($param){
					case NULL:
						return array('pagenumber','route0','route1','route2',
						'route3','route4','route5','route6','route7','route8','route9');
						break;

					case "pagenumber":	// original page number
//						return trim(substr($this->content,$this->frameindex[$idx]["offset"]+2,10));
						if ( !empty( $this->frameindex[$idx]["subpage"] ) ) {
							return array( $this->frameindex[$idx]["ttpage"] ,
								$this->frameindex[$idx]["subpage"]);
						}
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
							$route .= str_pad(
								dechex(ord($this->content[$this->frameindex[$idx]["offset"]+34+$j+5*($i % 10)]))
								,2,"0",STR_PAD_LEFT);
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

*/

			case VVTYPE_TTI:
				if ($param == null) {
					 return array('pagenumber','red','green','yellow','blue','link4','index', 'cycletime', 'language',
					 'description', 'mrg-ps');
				}

			case VVTYPE_G7JJF:
				switch($param){
					case NULL:
						return array('pagenumber','red','green','yellow','blue','link4','index');

					case "pagenumber":	// original page number
						return array($this->frameindex[$idx]["ttpage"],(string)$this->frameindex[$idx]["subpage"]); //(string)substr('0000' . (string)$this->frameindex[$idx]["subpage"],-4));
					default:
						if (isset($this->frameindex[$idx][$param])) {
							return $this->frameindex[$idx][$param];
						}
				}
				break;

			case VVTYPE_JOHNCLARKE:
				$i = 0;
				switch($param){
					case NULL:
						return array('pagenumber', 'route0', 'route1', 'route2', 'route3', 'route4',
						 'route5', 'route6', 'route7', 'route8', 'route9' );	// erm ..
						break;

					case "pagenumber":	// original page number
						return array($this->frameindex[$idx]['ttpage'],$this->frameindex[$idx]['subpage']);
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

						$r = substr($this->content,
							$this->frameindex[$idx]['offset'] + 10 + $i * 9 , 9);
						if ($r == '999999999') {
							return '';
						} else if ($r == '000000000') {
							return '0';
						} else
							return ltrim($r,'0 ');
				}

			default:
				switch($param){
					case NULL:
						return array('pagenumber');
						break;

					case "pagenumber":	// original page number
						return array($this->frameindex[$idx]["ttpage"],(string)$this->frameindex[$idx]["subpage"]); //	(string)substr('0000' . (string)$this->frameindex[$idx]["subpage"],-4));
						break;
				}
				if (isset($this->frameindex[$idx][$param])) {
					return $this->frameindex[$idx][$param];
				}
			} // switch

// TODO the rest of the formats!  Do any others actually have any metadata?
	}



	// return a screen image in some format
	function ReturnScreen($idx = NULL, $mode = "internal", $size = 0)
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


			case "imagesize":	// get image of a specific size (aka width)
				// done already?
				if (array_key_exists('image', $this->frameindex[$idx] )
				&& array_key_exists($size, $this->frameindex[$idx]['image'])) {
					return $this->frameindex[$idx]['image'][$size];
				}
				// So we ned to crate this size of image. Do we need to load file?
				if (!array_key_exists("internal", $this->frameindex[$idx])) {
					if ($this->LoadInternalFrame($idx) === FALSE) return FALSE;
				}
				// create native image
				$type="";
				$t = $this->createImage(0, $this->frameindex[$idx]["internal"],40,
					$this->frameindex[$idx]["height"],0,$size);
				$this->frameindex[$idx]["image"][$size] = $t["image"];
				$this->frameindex[$idx]["imagetype"] = $t["imagetype"];
				return $t['image'];

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
				break;



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

		if ($this->format != VVTYPE_TFLINKS && $rawtext === NULL) {
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
				// no break, drop through
			case VVTYPE_PLUS3:
			case VVTYPE_VTP:
				$rawtext = substr($rawtext,0,960);	// if ABZ will already be 920..
				// no break, drop through
			case VVTYPE_24x40:
			case VVTYPE_25x40:
			case VVTYPE_MODE7:
				$rp = 0;
				// no break, drop through
			case VVTYPE_GNOME:
				if ($this->format == VVTYPE_GNOME) $rp = 104;
				// TODO - Massage top line if it's a host frame not a saved frame.
				// what if it's a dynamic or "as raw" frame?
				for ($tp=0; $tp<strlen($text) && $rp <strlen($rawtext); $tp++) {
					$text[$tp] = chr(ord($rawtext[$rp]) & 127);
					$rp++;
				}
				break;
			case VVTYPE_GNOMEVAR:
				$tp = 104;
				$cx = 0; $cy=0;
				$esc=0;
				while ($tp < 1024) {
					$char = ord($rawtext[$tp]);
					if ($char >= 160) {
						$char = $char & 127;
					}
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

						case 12:	// used by Prestel to indicate start of field (c.f. Response frames)
							$char = 27;	// Store an Esc instead
							// drop into
						default:
							if ($esc || $char > 127) {
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
				break;

			case VVTYPE_TTI:
				foreach ($this->frameindex[$idx]['content'] as $key => $value){
					$text = substr_replace($text,substr(str_pad($value,$width),0,$width),$key*$width,$width);
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
				// no break, drop through

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
				// no break, drop through

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
						case 12:	// used by Prestel to indicate start of field (c.f. Response frames)
							$char = 27;	// Store an Esc instead
							// drop into
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

			case VVTYPE_G7JJF:

				// ! Frame here relates to TV transmission frames!
				// so ... [offset] refers to start record. So ignore $rawtext here as it won't
				// be long enough.

				// Much code ripped & translated from VB6 program at https://www.g7jjf.com/teletext.htm

				$grabbing = false;
				for ($offset = $this->frameindex[$idx]["offset"]; $offset < strlen($this->content); $offset+=860) {

					$ScreenB = substr($this->content, $offset, 860);

					// scan lines..
					For ($i = 3; $i <= 15; $i++) {

						$X = ord($ScreenB{$i * 43 + 0});
						$y = ord($ScreenB{$i * 43 + 1});

						$mag = ($X & 2) / 2 + (($X & 8) / 8) * 2 + (($X & 32) / 32) * 4;

						If ($mag == 0) $mag = 8;

						$row = ($X & 128) / 128 + (($y & 2) / 2) * 2 + (($y & 8) / 8) * 4 + (($y & 32) / 32) * 8 + (($y & 128) / 128) * 16;


						//	'            Debug.Print "Offset : " & Hex$(i * 43 + frame * 860) & ", Row " & i & " : " & Hex$(x) & ", " & Hex$(y) & ", " & mag & ", " & row & " ";
						//	            Print #txt, "Offset : " & Hex$(i * 43 + frame * 860) & ", Row " & i & " : " & Hex$(X) & ", " & Hex$(y) & ", " & mag & ", " & row & " ";

						If ($row == 0 And $X <> 0) {
							if ($grabbing && $this->frameindex[$idx]['ttpage']{0} == $mag) {
										// We end reception of a page when get header for the next one in that mag.
								break 2;	// (so need to break when hit the second header whatever page it is.)
							}
							$X = ord($ScreenB{$i * 43 + 2});
							$y = ord($ScreenB{$i * 43 + 3});

							$pageu = ($X & 2) / 2 + (($X & 8) / 8) * 2 + (($X & 32) / 32) * 4 + (($X & 128) / 128) * 8;
							$paget = ($y & 2) / 2 + (($y & 8) / 8) * 2 + (($y & 32) / 32) * 4 + (($y & 128) / 128) * 8;

							$page = $mag . dechex($paget) . dechex($pageu);
							$w = ord($ScreenB{$i * 43 + 4});
							$x = ord($ScreenB{$i * 43 + 5});
							$y = ord($ScreenB{$i * 43 + 6});
							$z = ord($ScreenB{$i * 43 + 7});

							$s1 = ($w & 2) / 2 + (($w & 8) / 8) * 2 + (($w & 32) / 32) * 4 + (($w & 128) / 128) * 8;
							$s2 = ($x & 2) / 2 + (($x & 8) / 8) * 2 + (($x & 32) / 32) * 4;
							$s3 = ($y & 2) / 2 + (($y & 8) / 8) * 2 + (($y & 32) / 32) * 4 + (($y & 128) / 128) * 8;
							$s4 = ($z & 2) / 2 + (($z & 8) / 8) * 2;

							$subpage = $s4.dechex($s3).$s2.dechex($s1);

							if ($page == $this->frameindex[$idx]['ttpage']
							 && $subpage == $this->frameindex[$idx]['subpage']) {
								$grabbing = true;
								For ($z = 0; $z <= 7; $z++){
									$text{$z} = Chr(32);		// row 0 so is first 40 bytes
								}
								For ($z = 8; $z<= 39; $z++) {
									$text{$z} = Chr(ord($ScreenB{$i * 43 + 2 + $z}) & 127);
								}
							}

/*							$this->frameindex[$this->framesfound] =
								array("file" => $dat,
								"offset" => $offset,
								"size" => 1008,
								"height" =>25,
								"ttpage" => $ttpage,
								"subpage" => $subpage);
							$this->framesfound++ ;
*/
						}

						// page content
						If ($row > 0 && $row <= $height && $grabbing && $this->frameindex[$idx]['ttpage']{0} == $mag  ) {
							For ($z = 0; $z<= 39; $z++) {
								$text{$row * $width + $z} = Chr(ord($ScreenB{$i * 43 + 2 + $z}) & 127);
							}
						}

						// fast text links
						If ($row == 27 && $grabbing && $this->frameindex[$idx]['ttpage']{0} == $mag  ) {
							$X = ord($ScreenB{$i * 43 + 2 + 0});

							$code = $this->deham($X);
							if ($code == 0) {	// standard fasttext

								foreach (array(0=>"red", 1=>"green", 2=>"yellow", 3=>"blue", 4=>"link4", 5=>"index") as $li => $label){

									$X = ord($ScreenB{$i * 43 + 3 + $li * 6 + 0});
									$y = ord($ScreenB{$i * 43 + 3 + $li * 6 + 1});

									$pl = dechex($this->deham($y)) . dechex($this->deham($X));

									$w = ord($ScreenB{$i * 43 + 3 + $li * 6 +2});
									$x = ord($ScreenB{$i * 43 + 3 + $li * 6 +3});
									$y = ord($ScreenB{$i * 43 + 3 + $li * 6 +4});
									$z = ord($ScreenB{$i * 43 + 3 + $li * 6 +5});

									$s1 = ($w & 2) / 2 + (($w & 8) / 8) * 2 + (($w & 32) / 32) * 4 + (($w & 128) / 128) * 8;
									$s2 = ($x & 2) / 2 + (($x & 8) / 8) * 2 + (($x & 32) / 32) * 4;
									$s3 = ($y & 2) / 2 + (($y & 8) / 8) * 2 + (($y & 32) / 32) * 4 + (($y & 128) / 128) * 8;
									$s4 = ($z & 2) / 2 + (($z & 8) / 8) * 2;

									$subpage = $s4.dechex($s3).$s2.dechex($s1);

									$m = ((int)($this->deham($z) / 4)) * 2 + (int)($this->deham($x) / 8);

									if ($subpage == '3f7f') $subpage = '';
									if ($pl == 'ff') {
										$pl = '';
									} else {
										$pl = ($m | $mag) . $pl;
									}

									$this->frameindex[$idx][$label] = $pl . $subpage;
								}
							}
						}
					}
				}


				break;

			case VVTYPE_TFLINKS:
					$text = $this->from_hash($this->frameindex[$idx]['tf']);
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

	// used by g7jjf decoder.
	private function deham($b) {
		return ($b & 2) / 2 + (($b & 8) / 8) * 2 + (($b & 32) / 32) * 4 + (($b & 128) / 128) * 8;
	}



// $thumbnail is now the "size" (width in pixels) required... not applicable if textmode set, of course.

	function createImage($textmode, $text, $width = 40, $height = 24, $flags = 0, $thumbnail = 0) {
		// ripped right out of vv 0.5.Q
		// therefore probably needs tidying up considerably.

		$aspect_ratio = 1.2;	// aspect ratio (text pixels are not square.)

		$black = (($flags & VVFLAG_NOBLACK) == 0);

		// font sizes. must match that in font files
		$fwidth = 12;
		$fheight = 20;
		// border in pixels
		$tborder = 5; // top & bottom
		$lborder = 12; // left and right

		// native image size in pixels
		$pwidth = $width * $fwidth + 2 * $lborder;
		$pheight = $height * $fheight + 2 * $tborder;

		// required size
		$thumb_w = $thumbnail / $aspect_ratio;
		$thumb_h = $pheight * $thumbnail / $pwidth;

		// pause time per frame for flashing
		$flashdelay[0] = 100;  // flashing text visible, concealed text hidden
		$flashdelay[1] = 33;   // flashing text hidden,  concealed text hidden


		$longtext = "";


		if (!$textmode) { // don't bother for text mode
			// read fonts
			$fontnum = imageloadfont(FONTDIR . "vvttxt.gdf");
			$fontnumtop = imageloadfont(FONTDIR . "vvttxtop.gdf");
			$fontnumbot = imageloadfont(FONTDIR ."vvttxbtm.gdf");
			if ($fontnum == 0 || $fontnumtop == 0 || $fontnumbot == 0) {
				//$error = "cannot find font file";
				return FALSE;
			}

			// create working canvas
			$my_img = imagecreate($pwidth, $pheight);
			// and one for the final image
			if ($thumbnail) {
				$ar_img = ImageCreateTrueColor($thumbnail, $thumb_h);
			} else {
				$ar_img = ImageCreateTrueColor($pwidth * $aspect_ratio, $pheight);

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

				if ($doublebottom) { // if we're on the bottom row of a double height bit
					$char = $prev[$cx]; // use character from previous row!
				} else { // otherwise
					$char = ord($text[$tp]); // int!
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
//						case 155:		// an Escape indicates start of a field
										// let default replace it with a space.
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
							if ($textmode) {
								if ($textmode == 2 && $char>32) {
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
				if (($flags & VVFLAG_REVEAL )==0 && $conceal == 1 && !$textmode) $char = 32;
				// only bottom of double height chars show up on line below a d.h character
				if ($doublebottom && (!$double || $textmode)) $char = 32;
				// offset to get graphics characters within fontfile
				// switch to alternate font files for double height
				if (!$textmode) { // don't bother for text mode
					if ($double) {
						if ($doublebottom) {
							$fnum = $fontnumbot;
						} else {
							$fnum = $fontnumtop;
						}
					} else $fnum = $fontnum;
				}
				// OK we now have everything we need to write a character!
				if ($textmode) {
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
					if ($textmode) {
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
			if (!$textmode) {
				// resize native image into final size
				if ($thumbnail) {
					imagecopyresampled($ar_img,$my_img,0,0,0,0,
						$thumb_w * $aspect_ratio, $thumb_h, $pwidth, $pheight);

					if ($flasher) {	// save as temp file for animating
						$fname = tempnam(TEMPDIR,"vv"); //TEMPDIR . $cachepage . "_" . $flashcycle . ".gif";
						$frames[] = $fname;
						$framed[] = $flashdelay[$flashcycle];
						imagegif($ar_img, $fname);
					}
				} else {
					imagecopyresampled($ar_img,$my_img,0,0,0,0,
						$pwidth * $aspect_ratio, $pheight, $pwidth, $pheight);

					if ($flasher) { // save as temp file for animating
						$fname = tempnam(TEMPDIR,"vv"); //$fname = TEMPDIR . $cachepage . "_" . $flashcycle . ".gif";

						$frames[] = $fname;
						$framed[] = $flashdelay[$flashcycle];
						imagegif($ar_img, $fname);
					}
				}
			}
		} while (!$textmode && $flasher > 0 && $flashcycle < 1);
		// display image
		if ($textmode) {
			$longtext = str_replace(array("#","_","[","]","{","\\","}","~","`"),
				array("&pound;","#","&laquo;","&raquo;","&frac14;","&frac12;","&frac34;","&divide;","-") , $longtext);
			return $longtext;
		}
		if ($flasher == 0) {
			// simple static image
			return array("image"=>$ar_img, "imagetype"=>"png");
		}
		// animate the (two!) gif frames.
		$gif = new GIFEncoder ($frames,	// list of frames
		    $framed,					// list of delays between frames
		    0,
		    2,
		    1, 2, 3,
		    "url"
		    ); // 1,2,3 is the transparent colour; this one won't be in the image!
		$image = $gif->GetAnimation ();
		// kill temporary files
		foreach ($frames as $fnam) unlink($fnam);

		return array("image"=>$image, "imagetype"=>"gif");
	}



	// function to return friendly name for a given type number.

	public function vvtypes($val){
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
			case VVTYPE_VTF:
				return "Quantec QMX database";
			case VVTYPE_G7JJF:
				return "GJ77F Beebem TTxt Server data";
			case VVTYPE_TFLINKS:
				return "HTML page of edit.tf links";
	 		case VVTYPE_TTI:
				return "MRG Inserter format TTI/TTIX";
	 		case VVTYPE_24x40:
	 			return ".TTX (24x40 n*960B)";
	 		case VVTYPE_25x40:
	 			return ".TTX (25x40 n*1000B)";
	 		case VVTYPE_GNOMEVAR:
	 			return "Sequential Gnomeish files (Rob)";
			case VVTYPE_VTP:
				return ".VTP VTPlus format";

			default:
				return FALSE;
		}
	}



	// function to return mime type for a given type number.

	public function vvmimetypes($val){
		switch($val){
			case VVTYPE_GNOME:
				return "videotex/gnome";
			case VVTYPE_MODE7:
			case VVTYPE_ABZTTXT:
				return "videotex/screen";
			case VVTYPE_RAW:
				return "videotex/stream";
			case VVTYPE_AXIS:
			case VVTYPE_SVREADER:
			case VVTYPE_AXIS_I:
			case VVTYPE_PLUS3:
			case VVTYPE_EPX:
			case VVTYPE_TT:
			case VVTYPE_JOHNCLARKE:
			case VVTYPE_EP1:
				return "videotex/screen-multiple";
			default:
				return FALSE;
		}
	}


	// Return an http://edit.tf/#hashstring
	// straight port from https://github.com/rawles/edit-tf/blob/gh-pages/teletext-editor.js

public function to_hash($data, $cset = 0, $blackfg = 0, $extras = null ){

	$b64chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_";

	$encoding = '';

	// Construct the metadata as described above.
	$metadata = $cset;
	if ( $blackfg != 0 ) { $metadata += 8; }
	$encoding .= "$metadata";
	$encoding .= ":";

	// Construct a base-64 array by iterating over each character
	// in the frame.
	$b64 = array_fill(0,1167,0);
	for ( $r=0; $r<25; $r++ ) {
		for ( $c=0; $c<40; $c++ ) {
			for ( $b=0; $b<7; $b++ ) {

				// How many bits into the frame information we
				// are.
				$framebit = 7 * (( $r * 40 ) + $c) + $b;

				// Work out the position of the character in the
				// base-64 encoding and the bit in that position.
				$b64bitoffset = $framebit % 6;
				$b64charoffset = (int)(( $framebit - $b64bitoffset ) / 6);

				// Read a bit and write a bit.
				if (isset($data[$r*40+$c])) {
					$bitval = ord($data[$r*40+$c]) & ( 1 << ( 6 - $b ));
					if ( $bitval > 0 ) { $bitval = 1; }
				} else {
					$bitval = 0;
				}
				$b64[$b64charoffset] |= $bitval << ( 5 - $b64bitoffset );
			}
		}
	}

	// Encode bit-for-bit.
	for ( $i = 0; $i < 1167; $i++ ) {
		$encoding .= $b64chars[(int)$b64[$i]];
	}

	// add extension options. https://github.com/rawles/edit-tf/wiki/Teletext-page-hashstring-format
	// leading zeros are added and arrays imploded, but otherwise correct format must be provided by caller.
	if (!empty($extras)) {
		foreach (array('pn' => 3, 'ps' => 4, 'sc' => 4, 'x270' => 42, 'x280' => 64, 'x284' => 64, 'zx' => 0) as $key => $length) {
			if (isset($extras[$key])) {
				$value = $extras[$key];
				if (is_array($value)) {
					$value = implode($value);
				}
				if (!$length) $length = strlen($value);
				$encoding .= ':' . strtoupper($key) . '=' . substr(str_repeat('0',$length) . $value, -$length);
			}
		}
	}
	return $encoding;

/* http://temp.zxnet.co.uk/editor/#0:QIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIE
//	CBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIEC
    BAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECB
    AgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBA
    gQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAg
    QIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAg
    QIECBALF09-3L00ad2dBs068qDpo08xQpAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQ
    IECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQI
    ECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIE
    CBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIEC
    BAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECB
    AgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBAgQIECBA
    gQIECBAgQIECA:PN=100:PS=4009:SC=0:X270=1013F7F1023F7F1033F7F1043F7F8FF3F7F1003F7FF
*/
}

public function from_hash($hashstring) {

	$cc = array();
			if ( strpos($hashstring,":") !== false ) {

				// The metadata is here, so split it out.
				$parts = explode(':',$hashstring);

				// metadata is one nybble. The most significant bit is
				// whether we're enabling black foreground. The three
				// least significant bits describe the character set we're
				// using.

				// Extract the base-10 integer, assuming 0 (English) if it
				// turns out not to make sense.
				$metadata = $parts[0]|0;

				$cset_reqd = $metadata & 7;
				$blackfg = 0;
				if ( $metadata >= 8 ) { $blackfg = 1; }

/*				// A change of character set requires a reload of the font.
				if ( $cset_reqd >= 0 && $cset_reqd < 8 && $cset != $cset_reqd ) {
					$cset = $cset_reqd;
					init_font($cset);
				}
*/
				// The data replaces the value in hashstring ready for
				// decoding.
				$hashstring = $parts[1];
			}

			// We may be dealing with old hexadecimal format, in which the
			// 1920 hexadecimal digits after the colon are such that the
			// byte for row r and column c (both zero-indexed) is described
			// by the two hex digits starting at position 80r+2c. Base-64
			// is the new format. If we get a URL in the hexadecimal format
			// the editor will convert it.

			if ( strlen($hashstring) == 1920 ) {
				// The alphabet of symbols!
				$hexdigits = "0123456789abcdef";

				// Iterate through each row and each column in that row.
				for ( $r = 0; $r < 24; $r++) {

					// It's a good test to do this backwards!
					for ( $c = 39 ; $c >= 0; $c--) {

						// Default to a space.
						$cc[$r][$c] = 32;

						// The characte offset for this value is as follows:
						$offset = 2 * ( ( $r * 40 ) + $c );

						// If the data is here, turn it into an integer between 0 and
						// 127, and set the cc-array with that code.
						// If it's a control character, place it, so the attributes update.
						if ( $offset + 1 < strlen($hashstring )) {
							$hv1 = strpos($hexdigits,$hashstring{$offset});
							$hv2 = strpos($hexdigits, $hashstring{$offset + 1});
							if ( $hv1 !== false && $hv2 !== false ) {
								$newcode = ( ( $hv1 * 16 ) + $hv2 ) % 128;
								$cc[$c][$r] = chr($newcode);
							}
						}
					}
				}
			}

			// This block deals with the new base 64 format.

			// We need to be able to handle two cases here, depending on the
			// size of the frame. 24-line frames have 1120 characters, and
			// 25-line frames, the new way we do things, have 1167 characters.
			// 25-line frames have two bits at the end which are ignored and
			// just exist for padding.

			if ( strlen($hashstring) == 1120 || strlen($hashstring) == 1167 ) {
				$numlines = 25;
				if ( strlen($hashstring) == 1120 ) { $numlines = 24; }

				// As we scan across the hashstring, we keep track of the
				// code for the current character cell we're writing into.
				$currentcode = 0;

				// p is the position in the string.
				for ( $p = 0; $p < strlen($hashstring); $p++ ) {
					$pc = $hashstring{$p};
					$pc_dec = strpos("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_",
						$hashstring{$p});

					// b is the bit in the 6-bit base-64 character.
					for ( $b = 0; $b < 6; $b++ ) {

						// The current bit posiiton in the character being
						// written to.
						$charbit = ( 6*$p + $b ) % 7;

						// The bit value (set or unset) of the bit we're
						// reading from.
						$b64bit = $pc_dec & ( 1 << ( 5 - $b ) );
						if ( $b64bit > 0 ) { $b64bit = 1; }

						// Update the current code.
						$currentcode |= $b64bit << ( 6 - $charbit );

						// If we've reached the end of this character cell
						// and it's the last bit in the character we're
						// writing to, set the character code or place the
						// code.
						if ( $charbit == 6 ) {

							// Work out the cell to write to and put it there.
							$charnum = ( ( 6*$p + $b ) - $charbit ) / 7;
							$c = $charnum % 40;
							$r = ($charnum - $c) / 40;
							$cc[$r][$c] = chr($currentcode);


							// Reset for next time.
							$currentcode = 0;
						}
					}
				}

				// If we only read in a 24-line file, we need to blank the final
				// line.
				if ( $numlines == 24 ) {
					for ( $x = 0; $x < 40; $x++ ) {
						$cc[24][$x] = chr(32);
					}
				}
			}

	return implode('',
		array_map(function($el){
						return implode('',$el);
			 		}, $cc));
}
function zeropad($num, $lim)
{
	return (strlen($num) >= $lim) ? $num : $this->zeropad("0" . $num, $lim);
}

/*	function guess_date($s, $y = '1970 '){
		$dt = false;
		for ($l = 8; $l < 40 && $l <= strlen($s); $l++) {
			$td = strtotime( substr($s, 0-$l));
			if ($td != false)
				$dt = $td;
		}
		return $dt;
	}
*/
}