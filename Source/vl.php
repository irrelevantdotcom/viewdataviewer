<?php 
// Viewdata Page Lister
// (c)2010 Robert O'Donnell, robert@irrelevant.com
// Version 0.3.5 beta
// See README.TXT for important information.

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
$pageqty = 999999;
if (isset($_GET['qty']) && is_numeric($_GET['qty'])) {
    $pageqty = $_GET['qty'];
    if ($pageqty < 1) {
        $pageqty = 999999; // sanity check
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
    } 
    if (isset($_GET['textmode']) && is_numeric($_GET['textmode'])) {
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

$c = 0;
/*
	Build a frames array from sources...
*/
$files = array();

if ($dh = opendir ("./" . $folder . "/")) {
    while (FALSE !== ($dat = readdir ($dh))) { // for each file
        if (substr($dat, 0, 1) != "." && substr($dat, strlen($dat)-4, 4) != ".txt") {
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
    if ($flen % 1024 == 0 || $flen < 1024) {
        for ($offset = 0; $offset < $flen; $offset += 1024) {
            $framelist[] = array($dat, $offset);
        } 
    } 
} 

if ($zoom>=0) {
?><td class="gallerytd" valign="top" colspan=<?php echo $maxcols; ?>><?php
	if ($textmode == 0) {
	    echo "<img ";
	} else {
	    echo "<iframe width=350 height=400 SCROLLING=\"no\" ";
	}
	echo "src=\"".$baseurl."vv.php?";
	if ($textmode) echo "longdesc=".$textmode."&";
    echo "gal=".$folder."&page=".$framelist[$zoom][0];
       if ($framelist[$zoom][1] > 0) {
		echo "&offset=".$framelist[$zoom][1];
       } 
	echo "\">";
	if ($textmode) echo "</iframe>";
	
	 if (file_exists("./" . $folder . "/" . $framelist[$zoom][0] . ".txt")) {
            $text = file_get_contents("./" . $folder . "/" . $framelist[$zoom][0] . ".txt");
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
            if (isset($index[$framelist[$zoom][0] . "+" . $framelist[$zoom][1]])) {
                $title = $index[$framelist[$zoom][0] . "+" . $framelist[$zoom][1]][0];
                $text = $index[$framelist[$zoom][0] . "+" . $framelist[$zoom][1]][1];
            } else if (isset($index[$framelist[$zoom][0]])) {
                $title = $index[$framelist[$zoom][0]][0];
                $text = $index[$framelist[$zoom][0]][1];
            } 
        } 
	
	if ($title != "") echo "<br />[ueber2|".$title."]";
	if ($text != "") echo "<br />".$text;
?></td></tr>

<?php

} else {
    $dispcnt = $pageqty;
    $dispnum = $pagestart;
    while ($dispnum < count($framelist) && $dispcnt > 0) {
        $oneframe = $framelist[$dispnum];
        // foreach ($framelist as $oneframe) {
        $dat = $oneframe[0];
        $offset = $oneframe[1]; 
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
 <a href="?<?php echo $_SERVER['QUERY_STRING']."&zoom=". $dispnum ; ?>" title="Full size view: &quot;<?php echo $dat; ?>&quot;">

   <img src="<?php echo $baseurl; ?>vv.php?thumbnail=1&gal=<?php echo $folder;  ?>&page=<?php echo $dat;
            if ($offset > 0) {

                ?>&offset=<?php echo $offset;
            } 

            ?>" alt="<?php echo $dat;

            ?>" longdesc="<?php echo $baseurl; ?>vv.php?longdesc=1&gal=<?php echo $folder; ?>&page=<?php echo $dat;
            if ($offset > 0) {
			 ?>&offset=<?php echo $offset;
            } 
            ?>" class="thumbnail" width="100"/>
 </a>
 <br />
<small> <a href="?<?php echo $_SERVER['QUERY_STRING']."&zoom=". $dispnum ; ?>&textmode=2"  title="Textual view: &quot;<?php echo $dat;

            ?>&quot;">
View as text</a></small><br />
 <?php echo $title;

            ?>
</td><?php

        } else {

            ?><td class="gallerytd" style="width:<?php echo 50 / $maxcols;

            ?>%;" valign="top">
 <a href="?<?php echo $_SERVER['QUERY_STRING']."&zoom=". $dispnum ; ?>" title="Full size view: &quot;<?php echo $dat; ?>&quot;">

   <img src="<?php echo $baseurl;

            ?>vv.php?thumbnail=2&gal=<?php echo $folder;

            ?>&page=<?php echo $dat;
            if ($offset > 0) {

                ?>&offset=<?php echo $offset;
            } 

            ?>" alt="<?php echo $dat;

            ?>" longdesc="<?php echo $baseurl;

            ?>vv.php?longdesc=1&gal=<?php echo $folder;

            ?>&page=<?php echo $dat;
            if ($offset > 0) {

                ?>&offset=<?php echo $offset;
            } 

            ?>" class="thumbnail" width="200"/>
 </a>
 <br />
<small><a href="?<?php echo $_SERVER['QUERY_STRING']."&zoom=". $dispnum ; ?>&textmode=2"  title="Textual view: &quot;<?php echo $dat;

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
    $restp = "";
    foreach ($_GET as $key => $value) {
		if (isset($_GET['baseurl'])) {
	        if (stripos("zoom|textmode|layout|cols|gal|baseurl|start", $key) === FALSE) {
	            $restp .= $key . "=" . $value . "&";
	        } 
		} else {
	        if (stripos("start", $key) === FALSE) {
	            $restp .= $key . "=" . $value . "&";
	        } 
		
		}
    } 
    $nextp = "";
    $prevp = "";
    $firstp = "";
    $lastp = "";
	$backp = "";

	if ($zoom>=0) {
		$backp = $restp."start=".$pagestart;
		if ($textmode) $restp .= "textmode=".$textmode."&";
	    if ($zoom + 2 < count($framelist)) {
	        $nextp = $restp . "zoom=" . ($zoom+1) . "&start=".$pagestart;
	    } 
        $lastp = $restp ."zoom=". (count($framelist)-1). "&start=".$pagestart;
	    if ($zoom > 0) {
            $prevp = $restp . "zoom=".($zoom-1). "&start=" . $pagestart;
	    } 
        $firstp = $restp . "zoom=0" . "&start=".$pagestart;
	

	} else {
		
	    if ($pagestart + $pageqty < count($framelist)) {
	        $nextp = $restp . "start=" . ($pagestart + $pageqty);
	        $lastp = $restp . "start=" . (count($framelist) - (count($framelist) % $_GET['qty']));
	    } 
	    if ($pagestart > 0) {
	        if ($pagestart > $pageqty) {
	            $prevp = $restp . "start=" . ($pagestart - $pageqty);
	        } else {
	            $prevp = $restp . "start=0" ;
	        } 
	        $firstp = $restp ."start=0" ;
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
/*		  
		  <br />
</div>
</body>
* */?>