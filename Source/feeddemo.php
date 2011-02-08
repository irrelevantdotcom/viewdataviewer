<?php

/**
 *
 *
 * @version $Id$
 * @copyright 2010
 */


include('vv.class.php');
// Make sure SimplePie is included. You may need to change this to match the location of simplepie.inc.
require_once('./simplepie.inc');

// We'll process this feed with all of the default options.
$feed = new SimplePie();

$feedurl = 'http://newsrss.bbc.co.uk/rss/newsonline_uk_edition/front_page/rss.xml';
if (!empty($_GET['feed'])) {
	if (get_magic_quotes_gpc()) {
		$feedurl = stripslashes($_GET['feed']);
	} else {
		$feedurl = $_GET['feed'];

	}
}
// Set which feed to process.
$feed->set_feed_url($feedurl);

$feed->set_cache_duration(600);
$feed->set_output_encoding("US-ASCII");

// Run SimplePie.
$feed->init();

// This makes sure that the content is sent to the browser as text/html and the UTF-8 character set (since we didn't change it).
//$feed->handle_content_type();

 	$text = "";
 	$esc = chr(27);
 	$crlf = chr(13).chr(10);

 	$text .= str_pad($esc ."CPoC Viewdata.org.uk",24) . $esc . "G" . str_pad("100a",10). $esc . "C   0p"; // . $crlf;
 	$text .= $esc . "A" . $esc . "]" . $esc . "G" . $esc . "M" . substr(str_pad($feed->get_title(),34),0,34) . "  ";
	$text .= $crlf;
	$text .= " " . $esc . "]" . $esc . "D" . substr(str_pad(html_entity_decode($feed->get_description()),36),0,36)." ";
	$text .= $crlf;

/*
	   Here, we'll loop through all of the items in the feed, and $item represents the current item in the loop.
	*/
	$count = 11;
	$colour = 0;
 	foreach ($feed->get_items() as $item) {
 		$text .= $esc . "G" . $count . $esc . ($colour ? "C" : "F");
 		$colour = !$colour;
		$text .= substr(str_pad(html_entity_decode($item->get_title()),36),0,36);
		$count++;
		if ($count % 10 == 0) $count += 1;
		if ($count > 30) break;
	}

	while($count < 30){
 		$text .= $crlf;
 		$count++;
 		if ($count % 10 == 0) $count += 1;
 	}

	$text .= $esc . "A" . $esc . "]" . $esc . "G0 Index";

//	$text = "hello";
	$pg = new ViewdataViewer();
	if ($pg->LoadData($text,VVTYPE_RAW)=== FALSE) {
		echo "LoadData failed";
	}

	if (($t=$pg->ReturnScreen(NULL,"image")) === FALSE)  {
		echo "ReturnScreen failed";
	} else {
		switch($pg->ReturnScreen(NULL,"imagetype")){
		case "png":
			header("Content-type: image/png");
			Imagepng($t);
			break;
		case "gif":
			header ("Content-type:image/gif");
			echo $t;
			break;
	} // switch
}