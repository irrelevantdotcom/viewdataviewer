<?php 
// Viewdata Page Lister
// (c)2010 Robert O'Donnell, robert@irrelevant.com
// Version 0.3.0 beta
// See README.TXT for important information.
//
// TODO: pagination

?>          <div class="gallerynumbermenu">
            <table class="gallerytable" summary="gallery table">
<tr><?php

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

foreach ($files as $dat) {
    $flen = filesize("./" . $folder . "/" . $dat);
    if ($flen % 1024 == 0 || $flen < 1024) {
        for ($offset = 0; $offset < $flen; $offset += 1024) {
            // "file.txt" is description for matching "file". only use 1st line here.
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

            if ($layout == 0) {

                ?><td class="gallerytd" style="width:<?php echo 100 / $maxcols;

                ?>%;" valign="top">
 <a href="vv.php?gal=<?php echo $folder;

                ?>&page=<?php echo $dat;
				if ($offset>0) {
                ?>&offset=<?php echo $offset;
				}
                ?>" target="_blank" title="Full size view: &quot;<?php echo $dat;

                ?>&quot;">
   <img src="vv.php?thumbnail=1&gal=<?php echo $folder;

                ?>&page=<?php echo $dat;
				if ($offset>0) {
                ?>&offset=<?php echo $offset;
				}
                ?>" alt="<?php echo $dat;

				?>" longdesc="vv.php?longdesc=1&gal=<?php echo $folder;

                ?>&page=<?php echo $dat;
				if ($offset>0) {
                ?>&offset=<?php echo $offset;
				}
                ?>" class="thumbnail" width="100"/>
 </a>
 <br />
<small><a href="vv.php?longdesc=2&gal=<?php echo $folder;
                ?>&page=<?php echo $dat;
				if ($offset>0) {
                ?>&offset=<?php echo $offset;
				 } ?>" target="_blank" title="Textual view: &quot;<?php echo $dat;

                ?>&quot;">
View as text</a></small><br />
 <?php echo $title;

                ?>
</td><?php

            } else {

                ?><td class="gallerytd" style="width:<?php echo 50 / $maxcols;

                ?>%;" valign="top">
 <a href="vv.php?gal=<?php echo $folder;

                ?>&page=<?php echo $dat;
				if ($offset>0) {
                ?>&offset=<?php echo $offset;
				}
                ?>" target="_blank" title="Full size view: &quot;<?php echo $dat;

                ?>&quot;">
   <img src="vv.php?thumbnail=2&gal=<?php echo $folder;

                ?>&page=<?php echo $dat;
				if ($offset>0) {
                ?>&offset=<?php echo $offset;
				}
                ?>" alt="<?php echo $dat;

				?>" longdesc="vv.php?longdesc=1&gal=<?php echo $folder;

                ?>&page=<?php echo $dat;
				if ($offset>0) {
                ?>&offset=<?php echo $offset;
				}
			
                ?>" class="thumbnail" width="200"/>
 </a>
 <br />
<small><a href="vv.php?longdesc=2&gal=<?php echo $folder;
                ?>&page=<?php echo $dat;
				if ($offset>0) {
                ?>&offset=<?php echo $offset;
				 } ?>" target="_blank" title="Textual view: &quot;<?php echo $dat;

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
        } 
    } 
} 

?></tr></table>
          </div><br />
