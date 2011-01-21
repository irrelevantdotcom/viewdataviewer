<?php
// Viewdata Page Lister
// (c)2010 Robert O'Donnell, robert@irrelevant.com
// Version 0.3.H beta!
// See README.TXT for important information.



vl_main();


function numtest($str){
	$num=TRUE;
	for ($i=0;$i<strlen($str);$i++) {
		if ((ord(substr($str,$i,1))&127) >= ord("A")) $num=FALSE;
	}
	return $num;
}


function vl_main(){

include "botcheck.php";
/*
?>
<head>
    <style type="text/css">
    @import "/layouts/Simple-nbsp~Beauty/css/galstyle.css";
    </style>
</head>
  <body bgcolor="white" BACKGROUND="">
	  <div class="gallerycontent">
*/?>
          <div class="gallerynumbermenu">
            <table class="gallerytable" summary="gallery table">
<tr><?php
// echo $_SERVER['QUERY_STRING'];
$layout = 0; // 0=horizontal, 1=vertical
$maxcols = 4; // number of pictures across
if (isset($_GET['layout']) && is_numeric($_GET['layout'])) {
    $layout = $_GET['layout'];
    if ($layout < 0 || $layout > 1) {
        $layout = 0; // sanity check
    }
    if ($layout == 1) {
        $maxcols = 1; // default for vertical
    }
}
if (isset($_GET['cols']) && is_numeric($_GET['cols'])) {
    $maxcols = $_GET['cols'];
    if ($maxcols < 1 || $maxcols > 255) {
        $maxcols = 4; // sanity check
    }
}
$pageqty = 0;
if (isset($_GET['qty']) && is_numeric($_GET['qty'])) {
    $pageqty = $_GET['qty'];
    if ($pageqty < 1) {
        $pageqty = 0; // sanity check
    }
}
$pagestart = 0;
if (isset($_GET['start']) && is_numeric($_GET['start'])) {
    $pagestart = $_GET['start'];
    if ($pagestart < 1) {
        $pagestart = 0; // sanity check
    }
}
$zoom = -1;
$textmode = 0;
if (isset($_GET['zoom']) && is_numeric($_GET['zoom'])) {
    $zoom = $_GET['zoom'];
    if ($zoom < 0) {
        $zooom = -1; // sanity check
    } else echo "<a name=\"zoom\"></a>";

	if (botcheck()) {
		$textmode = 2;
	} else if (isset($_GET['textmode']) && is_numeric($_GET['textmode'])) {
        $textmode = $_GET['textmode'];
        if ($textmode < 1) {
            $textmode = 0; // sanity check
        }
    }
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

$folder = "frames";
if (isset($_GET["gal"])) {
    if (preg_match('/^[a-zA-Z0-9_]{3,16}$/', $_GET['gal'])) $folder = $_GET["gal"];
    // else $folder = "frames";
}
$cache = "cache";
if (isset($_GET["cache"])) {
	if (preg_match('/^[a-zA-Z0-9_]{3,16}$/', $_GET['cache'])) $cache = $_GET["cache"];
}

$format = 0;
if (isset($_GET['format']) && is_numeric($_GET['format'])) {
		$format=$_GET['format'];
}



$framesize = 1024;
$height = 0;
if (isset($_GET['framesize']) && is_numeric($_GET['framesize'])) {
	$framesize = $_GET['framesize'];
	if ($framesize < 0 || $framesize > 1024) {
		$framesize = 1024; // sanity check
	}
	$height = $framesize / 40;
}

$restp = "";
foreach ($_GET as $key => $value) {
	if (isset($_GET['baseurl'])) { // implies it's embedded in a page
	     if (stripos("format|zoom|textmode|layout|cols|gal|baseurl|start|qty|framesize|cache", $key) === FALSE) {
			  if ($restp != "") $restp .= "&";
			  $restp .= $key . "=" . $value;
	     }
	} else {
	     if (stripos("zoom|textmode|start", $key) === FALSE) {
			  if ($restp != "") $restp .= "&";
	         $restp .= $key . "=" . $value;
	     }

	}
}






$c = 0;
/*
	Build a frames array from sources...
*/
$files = array();

if ($dh = opendir ("./" . $folder . "/")) {
    while (FALSE !== ($dat = readdir ($dh))) { // for each file
        if (substr($dat, 0, 1) != "."
        	&& substr($dat, strlen($dat)-4, 4) != ".txt"
        	&& !(substr($dat,-4,4) == ".PIC" && file_exists("./" . $folder . "/" . substr($dat,0,-4)).".IDX")
        	&& !(substr($dat,-4,4) == ".pic" && file_exists("./" . $folder . "/" . substr($dat,0,-4)).".idx")

			) {
            $files[] = $dat;
        }
    }
    closedir ($dh);
}
sort ($files, SORT_STRING);

if (file_exists("./" . $folder . "/index.txt")) {
    $index = array();
    foreach(file("./" . $folder . "/index.txt") as $line => $content) {
        // $index[$line] = explode(':',$content,2);
        $stuff = explode (':', $content, 3);
        $index[$stuff[0]] = array($stuff[1], $stuff[2]);
        // print_r($index);
    }
}

$framelist = array(); //array(),array());
foreach ($files as $dat) {
    $flen = filesize("./" . $folder . "/" . $dat);
	$temp = file_get_contents("./" . $folder . "/" . $dat );
	$test = substr($temp,0,8);
	$test2 = substr($temp,42,7);
	if (substr($dat,-4,4)==".IDX" && file_exists("./".$folder."/".substr($dat,0,-4).".PIC")) {
		for ($offset = 0; $offset < $flen; $offset +=9)	{
			$framelist[] = array("file" =>substr($dat,0,-4).".PIC",
			"offset"=>65536*ord(substr($temp,$offset+6,1))+256*ord(substr($temp,$offset+7,1))+ord(substr($temp,$offset+8,1)),
			"height"=>24);
		}
	} else if (substr($dat,-4,4)==".idx" && file_exists("./".$folder."/".substr($dat,0,-4).".pic")) {
		for ($offset = 0; $offset < $flen; $offset +=9)	{
			$framelist[] = array("file" =>substr($dat,0,-4).".pic",
			"offset"=>65536*ord(substr($temp,$offset+6,1))+256*ord(substr($temp,$offset+7,1))+ord(substr($temp,$offset+8,1)),
			"height"=>24);
		}

	} else if ($test 	 == "PLUS3DOS") {
        for ($offset = 128; $offset < $flen; $offset += 960) {
			if ($flen - $offset > 500) { // lose crap at end of file
	            $framelist[] = array("file" => $dat, "offset" => $offset, "height" =>24);
			}
        }
	} else if (substr($test,0,3) == "JWC") {
        for ($offset = 4; $offset < $flen; $offset += 1008) {
			if ($flen - $offset > 500) { // lose crap at end of file
	            $framelist[] = array("file" => $dat, "offset" => $offset, "height" =>24);
			}

        }
	} else if (numtest($test2) || isset($_GET["tt"])) {
		$offset = 0;
		$len = ord(substr($temp,$offset,1))+256*ord(substr($temp,$offset+1,1));
		$blkcnt=0;
		$fltemp = array();
		while($offset < $flen && ord(substr($temp,$offset-($offset%2048)+2047,1))<5){
			$ttpage=ord(substr($temp,$offset+2,1))+256*ord(substr($temp,$offset+3,1));
			if ($len) {
				$fltemp[] = array("file" => $dat, "offset" => $offset, "height" => -$len, "ttpage"=>$ttpage);
				$offset += $len;
			}
			$blkcnt++;

			if ($blkcnt > ord(substr($temp,$offset-($offset%2048)+2047,1))) {
				$offset += 2048-($offset%2048);
				$blkcnt=1;
			}

			$len = ord(substr($temp,$offset,1))+256*ord(substr($temp,$offset+1,1));

//			if ($len > 1024) $len = 600; // sanity check...!
		}

		if (!function_exists("usortcmp")) {
			function usortcmp($a,$b){
				if ($a["ttpage"] == $b["ttpage"]) return 0;
				 return ($a["ttpage"] < $b["ttpage"]) ? -1 : 1;
			}
		}
		usort($fltemp,"usortcmp");
		$framelist = array_merge($framelist,$fltemp);

	} else if (isset($_GET["framesize"])) {
		for ($offset = 0; $offset < $flen; $offset += $framesize) {
			if ($flen - $offset > 500) { // lose crap at end of file
				$framelist[] = array("file" => $dat, "offset" => $offset, "height" => $framesize/40);
			}
		}
	} else {
		foreach (array(960,1024,1000,1090) as $fsize) {
		 	if (abs($flen % $fsize) < 10) {  // allow for just a few bytes of crud on a file.
		        for ($offset = 0; $offset < $flen; $offset += $fsize) {
					if ($flen - $offset > 500) { // lose crap at end of file
			            $framelist[] = array("file" => $dat, "offset" => $offset, "height" =>24);
					}
		        }
		 		break;
		 	}
		}
    }

}

if ($zoom>=0) {
?><td class="gallerytd" valign="top" colspan=<?php echo $maxcols; ?>><?php
	if ($textmode == 0) {
	    echo "<img ";
		echo "src=\"".$baseurl."vv.php?";
//		if ($textmode) echo "longdesc=".$textmode."&";
	    echo "format=".$format."&gal=".$folder."&page=".$framelist[$zoom]["file"];
	       if ($framelist[$zoom]["offset"] > 0) {
			echo "&offset=".$framelist[$zoom]["offset"];
	       }
		echo "&height=".$framelist[$zoom]["height"];
		echo "\"";
		echo " alt=\"" . $framelist[$zoom]["file"]."\"";
		echo " longdesc=".$baseurl."vv.php?format=".$format."&longdesc=1&gal=".$folder."&page=".$framelist[$zoom]["file"];

		if ($framelist[$zoom]["offset"] > 0) {
			echo "&offset=".$framelist[$zoom]["offset"];
		}

		echo ">";

		?><small> <a href="?<?php
		  echo $restp;
		 if ($pageqty && $pagestart) echo "&start=".$pagestart;
		 echo"&textmode=2&zoom=". $zoom ; ?>#zoom"  title="Textual view: &quot;<?php echo  $framelist[$zoom]["file"];

		            ?>&quot;">
		View as text</a></small><br />
<?php



	} else {
		$savedget=$_GET;
		$_GET = array("longdesc" => $textmode,
		"gal" => $folder,
		"page" => $framelist[$zoom]["file"],
		"offset" => $framelist[$zoom]["offset"],
		"height" => $framelist[$zoom]["height"],
		"format" =>$format );
		echo "<table border=\"1\"><tr><td>";
		//virtual ($baseurl."/vv.php?");
		include "vv.php";

		echo "</td></tr></table>";
		$_GET=$savedget;

		?><small> <a href="?<?php
		echo $restp;
		if ($pageqty && $pagestart) echo "&start=".$pagestart;
		echo"&zoom=". $zoom ; ?>#zoom"  title="Graphical view: &quot;<?php echo  $framelist[$zoom]["file"];

		?>&quot;">
				Return to graphical view</a></small><br />
		<?php


/*
	    echo "<iframe width=350 height=400 SCROLLING=\"no\" ";
		echo "src=\"".$baseurl."vv.php?";
		if ($textmode) echo "longdesc=".$textmode."&";
	    echo "gal=".$folder."&page=".$framelist[$zoom][0];
	       if ($framelist[$zoom][1] > 0) {
			echo "&offset=".$framelist[$zoom][1];
	       }
		echo "\">";
		echo "</iframe>";
*/
	}

	 if (file_exists("./" . $folder . "/" . $framelist[$zoom]["file"] . ".txt")) {
            $text = file_get_contents("./" . $folder . "/" . $framelist[$zoom]["file"] . ".txt");
            $cr = stripos($text, "\n");
            if ($cr != FALSE) {
                $title = substr($text, 0, $cr);
                $text = substr($text, $cr + 1);
            } else {
                $title = $text;
                $text = "";
            }
        } else {
            $title = "";
            $text = "";
        }

        if ($title == "") {
            if (isset($index[$framelist[$zoom]["file"] . "+" . $framelist[$zoom]["offset"]])) {
                $title = $index[$framelist[$zoom]["file"] . "+" . $framelist[$zoom]["offset"]][0];
                $text = $index[$framelist[$zoom]["file"] . "+" . $framelist[$zoom]["offset"]][1];
            } else if (isset($index[$framelist[$zoom]["page"]])) {
                $title = $index[$framelist[$zoom]["page"]][0];
                $text = $index[$framelist[$zoom]["page"]][1];
            }
        }

	if ($title != "") echo "<br />[ueber2|".$title."]";
	if ($text != "") echo "<br />".$text;
?></td></tr>

<?php

} else {
    $dispcnt = $pageqty;
    $dispnum = $pagestart;
    while ($dispnum < count($framelist) && ($dispcnt > 0 || $pageqty == 0)) {
        $oneframe = $framelist[$dispnum];
        // foreach ($framelist as $oneframe) {
        $dat = $oneframe["file"];
        $offset = $oneframe["offset"];
    	$height = $oneframe["height"];
        // $flen = filesize("./" . $folder . "/" . $dat);
        // if ($flen % 1024 == 0 || $flen < 1024) {
        // for ($offset = 0; $offset < $flen; $offset += 1024) {
        // "file.txt" is an individual description for matching "file". only use 1st line here.
        if (file_exists("./" . $folder . "/" . $dat . ".txt")) {
            $text = file_get_contents("./" . $folder . "/" . $dat . ".txt");
            $cr = stripos($text, "\n");
            if ($cr != FALSE) {
                $title = substr($text, 0, $cr);
                $text = substr($text, $cr + 1);
            } else {
                $title = $text;
                $text = "";
            }
        } else {
            $title = "";
            $text = "";
        }

		$cachepage = $folder . "_" . $dat;
		if ($offset>0) $cachepage = $folder . "_" . $dat . "+" . $offset;
		if ($layout == 0) { // horizontal
			$cachepage .= "_thumb100"; // 100px wide thumbnail. adjust as necessary
		} else {			// vertical
			$cachepage .= "_thumb200"; // 200px wide thumbnail. adjust as necessary
		}


        if ($title == "") {
            if (isset($index[$dat . "+" . $offset])) {
                $title = $index[$dat . "+" . $offset][0];
                $text = $index[$dat . "+" . $offset][1];
            } else if (isset($index[$dat])) {
                $title = $index[$dat][0];
                $text = $index[$dat][1];
            }
        }

        if ($layout == 0) {

            ?><td class="gallerytd" style="width:<?php echo 100 / $maxcols;

            ?>%;" valign="top">
 <a href="?<?php
  echo $restp;
 if ($pageqty && $pagestart) echo "&start=".$pagestart;
 echo "&zoom=". $dispnum ; ?>#zoom" title="Full size view: &quot;<?php echo $dat; ?>&quot;">

   <img src="<?php

   if (file_exists($cache."/".$cachepage.".gif")) {
   		echo $baseurl.$cache."/".$cachepage.".gif";
   } elseif (file_exists($cache."/".$cachepage)) {
   		echo $baseurl.$cache."/".$cachepage;
   } else {


    	echo $baseurl; ?>vv.php?format=<?php echo $format; ?>&thumbnail=1&gal=<?php echo $folder;  ?>&page=<?php echo $dat;
            if ($offset > 0) {

                ?>&offset=<?php echo $offset;
            }
			?>&height=<?php echo $height;
}
            ?>" alt="<?php echo $dat;

            ?>" longdesc="<?php echo $baseurl; ?>vv.php?format=<?php echo $format; ?>&longdesc=1&gal=<?php echo $folder; ?>&page=<?php echo $dat;
            if ($offset > 0) {
			 ?>&offset=<?php echo $offset;
            }
            ?>" class="thumbnail" width="100"/>
 </a>
 <br />
<small> <a href="?<?php
  echo $restp;
 if ($pageqty && $pagestart) echo "&start=".$pagestart;
 echo"&textmode=2&zoom=". $dispnum ; ?>#zoom"  title="Textual view: &quot;<?php echo $dat;

            ?>&quot;">
View as text</a></small><br />
 <?php echo $title;

            ?>
</td><?php

        } else {

            ?><td class="gallerytd" style="width:<?php echo 50 / $maxcols;

            ?>%;" valign="top">
 <a href="?<?php echo $restp;
  if ($pageqty && $pagestart) echo "&start=".$pagestart;
  echo "&zoom=". $dispnum ; ?>#zoom" title="Full size view: &quot;<?php echo $dat; ?>&quot;">

   <img src="<?php
   if (file_exists($cache."/".$cachepage.".gif")) {
   		echo $baseurl.$cache."/".$cachepage.".gif";
   } elseif (file_exists($cache."/".$cachepage)) {
   		echo $baseurl.$cache."/".$cachepage;
   } else {

   		echo $baseurl;

            ?>vv.php?format=<?php echo $format;?>&thumbnail=2&gal=<?php echo $folder;

            ?>&page=<?php echo $dat;
            if ($offset > 0) {

                ?>&offset=<?php echo $offset;
			}
			?>&height=<?php echo $height;
	}
            ?>" alt="<?php echo $dat;

            ?>" longdesc="<?php echo $baseurl;

            ?>vv.php?format=<?php echo $format; ?>&longdesc=1&gal=<?php echo $folder;

            ?>&page=<?php echo $dat;
            if ($offset > 0) {

                ?>&offset=<?php echo $offset;
            }

            ?>" class="thumbnail" width="200"/>
 </a>
 <br />
<small><a href="?<?php
 echo $restp;
 if ($pageqty && $pagestart) echo "&start=".$pagestart;

echo "&textmode=2&zoom=". $dispnum ; ?>#zoom"  title="Textual view: &quot;<?php echo $dat;


            ?>&quot;">View as text</a></small><br />
</td><td class="gallerytd" style="width:<?php echo 50 / $maxcols;

            ?>%;"><strong><?php echo $title;

            ?></strong><br /><?php echo $text;

            ?>
</td><?php

        }

        $c++;
        if ($c >= $maxcols) {
            echo "</tr><tr>";
            $c = 0;
        }
        // }
        // }
        $dispcnt--;
        $dispnum++;
    }
}

?></tr>
<?php
if (isset($_GET['qty']) || $zoom>=0) {
    $nextp = "";
    $prevp = "";
    $firstp = "";
    $lastp = "";
	$backp = "";

	if ($zoom>=0) {
		$backp = $restp;
		if ($pageqty && $pagestart != 0) $backp .= "&start=".$pagestart;
//		$backp .= "#zoom";
		if ($textmode) $restp .= "&textmode=".$textmode;
	    if ($zoom + 1 < count($framelist)) {
	        $nextp = $restp . "&zoom=" . ($zoom+1);
			if ($pageqty && $pagestart != 0) $nextp .= "&start=".$pagestart;
			$nextp .="#zoom";
	    }
        $lastp = $restp ."&zoom=". (count($framelist)-1);
		if ($pageqty && $pagestart != 0) $lastp .= "&start=".$pagestart;
		$lastp .= "#zoom";
	    if ($zoom > 0) {
            $prevp = $restp . "&zoom=".($zoom-1);
			if ($pageqty && $pagestart != 0) $prevp .= "&start=" . $pagestart;
			$prevp .="#zoom";
	    }
        $firstp = $restp . "&zoom=0";
		if ($pageqty && $pagestart != 0) $firstp .= "&start=".$pagestart;
		$firstp .="#zoom";

	} else {
	    if ($pagestart + $pageqty < count($framelist)) {
	        $nextp = $restp . "&start=" . ($pagestart + $pageqty);
	        $lastp = $restp . "&start=" . (count($framelist) - (count($framelist) % $_GET['qty']));
	    }
	    if ($pagestart > 0) {
	        if ($pagestart > $pageqty) {
	            $prevp = $restp . "&start=" . ($pagestart - $pageqty);
	        } else {
	            $prevp = $restp; // . "&start=0" ;
	        }
	        $firstp = $restp; // ."&start=0" ;
	    }
	}

    ?><tr><td class="gallerytd" valign="top" colspan=<?php echo $maxcols;
    ?> ><?php
    if ($firstp != "") echo "<a href=\"?" . $firstp . "\">";
    echo "[First] ";
    if ($firstp != "") echo "</a>";
    if ($prevp != "") echo "<a href=\"?" . $prevp . "\">";
    echo "[Previous] ";
    if ($prevp != "") echo "</a>";

	if ($backp != "") echo "<a href=\"?".$backp."\">[Index]</a> ";

    if ($nextp != "") echo "<a href=\"?" . $nextp . "\">";
    echo "[Next] ";
    if ($nextp != "") echo "</a>";
    if ($lastp != "") echo "<a href=\"?" . $lastp . "\">";
    echo "[Last]";
    if ($lastp != "") echo "</a>";

    ?></td></tr>
<?php
}

?>


</table>
          </div>
		  <?php
 }
/*
		  <br />
</div>
</body>
   * */?>