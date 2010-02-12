<?php 
// Viewdata Page Lister
// (c)2010 Robert O'Donnell, robert@irrelevant.com
// Version 0.3.2 beta
// See README.TXT for important information.
//
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
*/$page = "";
if (isset($_GET["page"])) {
    if (preg_match('/^[a-zA-Z0-9_]{1,16}$/', $_GET['page'])) $page = $_GET["page"];
    else $error = "Invalid page number";
} 
$files = array();

if ($dh = opendir ("./" . $folder . "/")) {
    while (false !== ($dat = readdir ($dh))) { // for each file
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
$dispcnt=$pageqty;
$dispnum=$pagestart;
while ($dispnum < count($framelist) && $dispcnt > 0) {
    $oneframe = $framelist[$dispnum];
    $dispcnt--;
    $dispnum++; 
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
        if ($cr != false) {
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
 <a href="<?php echo $baseurl;

        ?>vv.php?gal=<?php echo $folder;

        ?>&page=<?php echo $dat;
        if ($offset > 0) {

            ?>&offset=<?php echo $offset;
        } 

        ?>" target="_blank" title="Full size view: &quot;<?php echo $dat;

        ?>&quot;">
   <img src="<?php echo $baseurl;

        ?>vv.php?thumbnail=1&gal=<?php echo $folder;

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

        ?>" class="thumbnail" width="100"/>
 </a>
 <br />
<small><a href="<?php echo $baseurl;

        ?>vv.php?longdesc=2&gal=<?php echo $folder;

        ?>&page=<?php echo $dat;
        if ($offset > 0) {

            ?>&offset=<?php echo $offset;
        } 

        ?>" target="_blank" title="Textual view: &quot;<?php echo $dat;

        ?>&quot;">
View as text</a></small><br />
 <?php echo $title;

        ?>
</td><?php

    } else {

        ?><td class="gallerytd" style="width:<?php echo 50 / $maxcols;

        ?>%;" valign="top">
 <a href="<?php echo $baseurl;

        ?>vv.php?gal=<?php echo $folder;

        ?>&page=<?php echo $dat;
        if ($offset > 0) {

            ?>&offset=<?php echo $offset;
        } 

        ?>" target="_blank" title="Full size view: &quot;<?php echo $dat;

        ?>&quot;">
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
<small><a href="<?php echo $baseurl;

        ?>vv.php?longdesc=2&gal=<?php echo $folder;

        ?>&page=<?php echo $dat;
        if ($offset > 0) {

            ?>&offset=<?php echo $offset;
        } 

        ?>" target="_blank" title="Textual view: &quot;<?php echo $dat;

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
} 

?></tr>
<?php
if (isset($_GET['qty'])) {
    $restp = "";
    foreach ($_GET as $key => $value) {
        if (stripos("layout|cols|gal|baseurl|start",$key) === FALSE ) {
            $restp .= "&" . $key . "=" . $value;
        } 
    } 
    $nextp = "";
    $prevp = "";
    $firstp = "";
    $lastp = "";

    if ($pagestart+$pageqty < count($framelist)) {
        $nextp = "start=" . ($pagestart+$pageqty) . $restp;
        $lastp = "start=" . (count($framelist) - (count($framelist) % $_GET['qty'])) . $restp;
    } 
    if ($pagestart > 0) {
        if ($pagestart >$pageqty) {
        	$prevp = "start=" . ($pagestart - $pageqty) . $restp;
		} else {
        	$prevp = "start=0" . $restp;
		
		}
        $firstp = "start=0" . $restp;
    } 

    ?><tr><td class="gallerytd" valign="top" colspan=<?php echo $maxcols;?> ><?php
    if ($firstp != "") echo "<a href=\"?" . $firstp . "\">";
    echo "[First]";
    if ($firstp != "") echo "</a>";
    if ($prevp != "") echo "<a href=\"?" . $prevp . "\">";
    echo "[Previous]";
    if ($prevp != "") echo "</a>";
    if ($nextp != "") echo "<a href=\"?" . $nextp . "\">";
    echo "[Next]";
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