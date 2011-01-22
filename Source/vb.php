<?php

/**
 * Simple Viewdata Browser
 *
 * @version 0.1.D
 * @copyright 2010 Rob O'Donnell
 */

/// Configuration

include "botcheck.php";

$folder = "frames";
$db = "";
$urf="1a";
$ipheader = chr(5)."The Gnome At Home";
$format=786;
/*/
$folder = "frames";
$db = "VXFRAMES.MAS";
$urf=4;
$ipheader="";
$format=5;
/*/
///

if (isset($_GET['format'])) {
    if (is_numeric($_GET['format'])) $format=$_GET['format'];
}
if (isset($_GET['urf'])) {
    if (is_numeric($_GET['urf']) || preg_match('/^[a-zA-Z0-9_.]{2,16}$/', $_GET['urf']))
		 $urf=$_GET['urf'];
}
if (isset($_GET['gal'])) {
    if (preg_match('/^[a-zA-Z0-9_.]{2,16}$/', $_GET['gal']))
		 $folder=$_GET['gal'];
}

if (isset($_GET['db'])) {
    if (preg_match('/^[a-zA-Z0-9_.]{2,16}$/', $_GET['db']))
		 $db = $_GET['db'];
}
if (isset($_GET['ip'])) {
    $ipheader=$_GET['ip'];
}
$baseurl = "";
if (isset($_GET['baseurl'])) {
    $baseurl = $_GET['baseurl'];
    if (strlen($baseurl)) {
        if ($baseurl[strlen($baseurl)-1] != "/") {

            $baseurl .= "/";
        }
    }
}
$restp = "";
foreach ($_GET as $key => $value) {
	if (isset($_GET['baseurl'])) {
	    if (stripos("format|urf|db|ip|baseurl|goto|mode|gal", $key) === FALSE) {
	        $restp .= $key . "=" . $value . "&";
	    }
	} else {
	    if (stripos("goto|mode", $key) === FALSE) {
	        $restp .= $key . "=" . $value . "&";
	    }

	}

}




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



$goto = "";
if (isset($_GET["goto"])) {
	if (($format & 15) == 5 ) {
	    if (is_numeric($_GET['goto'])) {
			$goto = $_GET['goto'];
/*		} else {
		    if (preg_match('/^[a-zA-Z0-9_.]{2,16}$/', $_GET['goto'])) $goto = $_GET["goto"];
			$goto = array_search($goto,$idx); */
		}
	} else {
	    if (preg_match('/^[a-zA-Z0-9_.]{2,16}$/', $_GET['goto'])) $goto = $_GET["goto"];
	    else $error = "Invalid goto number";
	}
} else $goto = $urf;

if ($goto == "0a") {
    $goto=$urf;
}

if (($format & 15) == 0 && substr($fnam,-4) == ".PIC") $format = 11;

if (($format & 15) == 11) {	// .PIC/.IDX
	if ($db == "") {
		$db = $folder;
		$folder = "";
		$fnam="./" . $db;
	} else {
		$fnam="./" . $folder . "/" . $db;
	}

	$ifile = substr($fnam,0,strlen($fnam)-4).".IDX";
//	echo $ifile;
	$temp = file_get_contents($ifile);
	$flen =strlen($temp);
//	echo $flen;
	for ($offset = 0; $offset < $flen; $offset +=9)	{
		$pn = 0;
		for ($i = 0; $i<5; $i++) $pn=256*$pn + ord(substr($temp,$offset+$i,1));
		$idx["$pn".substr($temp,$offset+5,1)] = 65536*ord(substr($temp,$offset+6,1))+256*ord(substr($temp,$offset+7,1))+ord(substr($temp,$offset+8,1));
//		echo $pn.substr($temp,$offset+5);

	}
	//print_r($idx);

} else if (($format & 15) == 5 || ($format & 15) == 7) {
	if ($db == "") {
	    $db = $folder;
		$folder = "";
		$fnam="./" . $db;
	} else {
		$fnam="./" . $folder . "/" . $db;
	}
    if (!file_exists("./cache/".$folder . "_" .$db.".idx")) {
		if (!file_exists($fnam)) {
		    echo "Missing database file?";
			exit;
		}
		echo "Please wait. Building index..";
		$index="";
		$data=file_get_contents($fnam,0,NULL,0,4096);
		if (filesize($fnam)>356352) {
			for ($j=352256; $j<filesize($fnam); $j+=352256) {
				$data .= file_get_contents($fnam,0,NULL,$j,4096);
			}
		}
		for ($j=0; $j<strlen($data); $j+=4096) {
			for ($i=16;  $i<4096; $i+=12) {
				$id = ord($data[10+$i+$j])+256*ord($data[11+$i+$j]);
				if ($id) {
				    $index .= $id . "=" . trim(substr($data,$i+$j,10)) . "&";
				}

			}
		}
        file_put_contents("./cache/".$folder . "_" .$db.".idx",$index);
    } else {
		$index= file_get_contents("./cache/".$folder . "_" .$db.".idx");
	}

	parse_str($index,$idx);

} else { // gnome format
	if ($folder == "") {
	    $fnam="./" . str_replace(".","/",$goto);
	} else {
		$fnam="./" . $folder . "/" . str_replace(".","/",$goto);
	}

}

$mode = 0;
if (isset($_GET["mode"])) {
    if (is_numeric($_GET["mode"])) $mode = $_GET["mode"];
    else $error = "Invalid mode";
    if ($mode < 0 || $mode > 1) $mode = 0;
}

if (($format & 15) == 5) {
    $offset=array_search($goto,array_keys($idx));
	if ($offset===FALSE) {
		$error =  "Sorry, the page requested, $goto, was not found in the database available. Please press BACK in your browser and try another route.";
	    $format = 1;
	} else {
		$offset=4096+1024*$offset;
		$offset += 4096*(int)($offset/352256);
		$text=file_get_contents($fnam,0,NULL,$offset,1024);
	}
} else if (($format & 15) == 7) {
	$offset=array_search(array_search($goto,$idx),array_keys($idx));

	if ($offset===FALSE) {
		$error =  "Sorry, the page requested, $goto, was not found in the database available. Please press BACK in your browser and try another route.";
	    $format = 1;
	} else {
		$offset=4096+1024*$offset;
		$offset += 4096*(int)($offset/352256);
		$text=file_get_contents($fnam,0,NULL,$offset,1024);
	    $frame = substr($goto, strlen($goto)-1);
	    if ($frame < "z") {
	        $frame = chr((ord($frame)|32) + 1);
	        $hashroute = substr($goto, 0, strlen($goto)-1) . $frame;
			if (array_search($hashroute,$idx) === FALSE) $hashroute="";
		} else $hashroute = "";
	}
} else if (($format & 15) == 11) {
//	$offset=array_search(array_search($goto,$idx),array_keys($idx));
	if (!isset($idx[$goto])) {
		$error =  "Sorry, the page requested, $goto, was not found in the database available. Please press BACK in your browser and try another route.";
	    $format = 1;
	} else {
		$offset = $idx[$goto];
		$text=file_get_contents($fnam,0,NULL,$offset,1024);
		$text=substr($text,0,strpos($text,chr(255))-1);
	    $frame = substr($goto, strlen($goto)-1);
	    if ($frame < "z") {
	        $frame = chr((ord($frame)|32) + 1);
	        $hashroute = substr($goto, 0, strlen($goto)-1) . $frame;
//			if (array_search($hashroute,$idx) === FALSE) $hashroute="";
	    	if (!isset($idx[$hashroute])) $hashroute="";
		} else $hashroute = "";
	}
} else {
	$text = "";
	if ($error == "") {
	    if (($fn = similar_file_exists($fnam)) != "" ) {
	        $text = file_get_contents($fn);
	//		$goto = substr($fn,strlen($fn)-strlen($goto));
		    $frame = substr($goto, strlen($goto)-1);
		    if ($frame < "z") {
		        $frame = chr((ord($frame)|32) + 1);
		        $hashroute = substr($goto, 0, strlen($goto)-1) . $frame;
				if ($folder == "") {
				    $fnam="./" . str_replace(".","/",$hashroute);
				} else {
					$fnam="./" . $folder . "/" . str_replace(".","/",$hashroute);
				}
				if (similar_file_exists($fnam)=="") {
				    $hashroute = "";
				}
		    } else {
		        $hashroute = "";
		    }
	    } else {
	        $error =  "Sorry, the page requested, $goto, was not found in the database available. Please press BACK in your browser and try another route.";
	        $format = 1;
	    }
	}
}
if ($error != "") {
    echo $error;
	exit;
} else {

	if (($format & 15) == 2) {
		if (substr($text,104,4) == "    " || substr($text,104,4) == chr(160).chr(160).chr(160).chr(160)) {
		    $top = str_pad($ipheader,24).chr(7).str_pad($goto,10).chr(3)."  0p";
		} else {
		    $top = substr($text,104,24).chr(7).str_pad($goto,10).chr(3)."  0p";
		}
	}
    ?><a id="zoom"></a><center>
<?php
	if (($format & 15)==5 || ($format & 15)==7 || ($format & 15) == 11) {
		$lgoto=$db;
	    $lfolder=$folder;
	} else {
		$lgoto = $goto;
		$lfolder = $folder;
	}


	if (botcheck()==0 && $mode == 0) {
?>
   <img src="<?php echo $baseurl;?>vv.php?format=<?echo $format; ?>&gal=<?php echo $lfolder;?>&page=<?php echo $lgoto;
    if ($offset > 0) {
        ?>&offset=<?php echo $offset;
    } ?><?php if ($top != "") echo "&top=".htmlentities($top); ?>" alt="<?php echo $lgoto; ?>"
	longdesc="<?php echo $baseurl;?>vv.php?format=<?echo $format; ?>&longdesc=1&gal=<?php echo $lfolder;?>&page=<?php echo $lgoto;
    if ($offset > 0) {
        ?>&offset=<?php echo $offset;
    } ?>" />
<br>
<small><a href="?<?php echo $restp;?>mode=1&goto=<?php echo $goto; ?>">Switch to text mode</a></small><br />
	<?php
	} else {

	$savedget=$_GET;
	$savedText=$text;
	$savedFormat=$format;
	$_GET = array("longdesc" => 2,
	"format" => $format,
	"gal" => $lfolder,
	"page" => "$lgoto",
	"offset" => $offset,
	"top" => $top);
	echo "<table border=\"1\"><tr><td>";
	//virtual("vv.php?");
	include "vv.php";

	echo "</td></tr></table>";
	$_GET=$savedget;
	$text=$savedText;
	$format=$savedFormat

/*	?>
   <iframe width=350 height=400 SCROLLING="no"
   src="<?php echo $baseurl;?>vv.php?longdesc=2&format=<?echo $format; ?>&gal=<?php echo $lfolder; ?>&page=<?php echo $lgoto;
    if ($offset > 0) {
        ?>&offset=<?php echo $offset;
    } ?><?php if ($top != "") echo "&top=".htmlentities($top);; ?>"  /></iframe>
*/
?>
<br>
<small><a href="?<?php echo $restp;?>mode=0&goto=<?php echo $goto; ?>">Switch to graphics mode</a></small><br />

	<?php
	}

	$routestuff="";
?>
<br>
<script type="text/javascript">
function textsizer(e){
var evtobj=window.event? event : e //distinguish between IE's explicit event object (window.event) and Firefox's implicit.
var unicode=evtobj.charCode? evtobj.charCode : evtobj.keyCode
var actualkey=String.fromCharCode(unicode)

<?php
    for ($i = 1; $i <= 10; $i++) {
		if (($format & 15) == 2) {
	        $route = rtrim(substr($text, 14 + 9 * ($i % 10), 9));
	        if (substr($route, 0, 1) == "*" || $route == "" ) {
	        	$route = "";
	        } else $route .="a";
		} else if (($format & 15) == 5) {
		    $route = ord($text[42+2*($i % 10)])+256*ord($text[43+2*($i % 10)]);
			if ($route == 0) $route = "";
       } else if (($format & 15) == 6) {
	   	    $route = trim(substr($text, 50 + 9 * ($i % 10), 9));
	        if ($route != "") $route .="a";
	   } else if (($format & 15) == 7) {
			$route="";
			for ($j=0;$j<5;$j++)
				 $route .= str_pad(dechex(ord($text[34+$j+5*($i % 10)])),2,"0",STR_PAD_LEFT);

	        $route = 0+$route; // 800FFFFFFF -> 800
			if ($route == 0) $route = "";
			else $route ="{$route}a";
	    } else if (($format & 15) == 11) {
			$route = ltrim(substr($text,10+($i % 10)*9,9),"0");
	    	if ($route == 0 || $route == 999999999) $route = "";
	    	if ($route != "") $route .="a";

	    }
        if ($route != "") {
            $routestuff .= '<a href="?' . $restp . 'mode=' . $mode . '&goto=' . $route . '#zoom" id="link' . ($i % 10) . '">';

			echo 'if (actualkey=="' . ($i % 10) . '")
location.href = "?' . $restp . 'mode=' . $mode . '&goto=' . $route . '#zoom"

';

        }
        $routestuff .= "[" . ($i % 10) . "]";
        if ($route != "") {
            $routestuff .= "</a>";
        }
		$routestuff .= " ";
    }
	if (($format & 15) == 2 || ($format & 15) == 6
		|| ($format & 15) == 7 || ($format & 15) == 11) {
/*	    $route = substr($goto, 0, strlen($goto)-1);
	    $frame = substr($goto, strlen($goto)-1);
	    if ($frame < "z") {
	        $frame = chr((ord($frame)|32) + 1);
	        $route = $route . $frame;
	    } else {
	        $route = "";
	    } */
		$route = $hashroute;
	} else if (($format & 15) == 5) {
	    $route = ord($text[62])+256*ord($text[63]);
		if ($route == 0) $route = "";
	}

    if ($route != "") {
        $routestuff .= '<a href="?' . $restp . 'mode=' . $mode . '&goto=' . $route . '#zoom" id="linkHash">';
			echo 'if (unicode==13)
location.href = "?' . $restp . 'mode=' . $mode . '&goto=' . $route . '#zoom"

';
    }
    $routestuff .= "[#] ";
    if ($route != "") {
        $routestuff .= "</a>";
    }
?>
}
document.onkeypress=textsizer
</script>
<?php
	echo $routestuff;

}

?>

<br />or press a number key. For # press Enter.<br />
</center>