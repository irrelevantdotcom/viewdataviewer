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
$template = 0;
if (isset($_GET['txt'])) {
	$template = 1;
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

	if ($template == 1) {
		$timezone = "Europe/London";
		# PHP 5
		date_default_timezone_set ($timezone);
		# PHP 4
		putenv ('TZ=' . $timezone);
		$text .= "P100" . $esc . "G" . $esc . "]" . $esc . "Dv'data UK " .$esc . "B". $esc . "\\100" . $esc . "G";
		$text .= date("DdM") . $esc . "C" . date("H:i/s");
		$text .= $esc ."D" . $esc . "]" . $esc ."G" . $esc . "M" . substr(str_pad(html_entity_decode($feed->get_title()),35),0,35)." ";
		$text .= $crlf;
		$text .= $esc . "F" . $esc . "]" . $esc . "D" . substr(str_pad(html_entity_decode($feed->get_description()),36),0,36)." ";
		$text .= $crlf;
	} else {
	 	$text .= str_pad($esc ."CPoC Viewdata.org.uk",24) . $esc . "G" . str_pad("100a",10). $esc . "C   0p"; // . $crlf;
	 	$text .= $esc . "A" . $esc . "]" . $esc . "G" . $esc . "M" . substr(str_pad($feed->get_title(),34),0,34) . "  ";
		$text .= $crlf;
		$text .= " " . $esc . "]" . $esc . "D" . substr(str_pad(html_entity_decode($feed->get_description()),36),0,36)." ";
		$text .= $crlf;
	}
/*
	   Here, we'll loop through all of the items in the feed, and $item represents the current item in the loop.
	*/
	if ($template == 1) {
		$count = 101;
	} else {
		$count = 11;
	}
	$colour = 0;
	$c = 1;
 	foreach ($feed->get_items() as $item) {
 		$text .= $esc . "G" . $count . $esc . ($colour ? "C" : "F");
 		$colour = !$colour;
		$text .= substr(str_pad(html_entity_decode($item->get_title()),36-$template),0,36-$template);
		$count++;
 		$c++;
 		if ($count % 10 == 0) $count += 1;
		if ($c > 18) break;
	}

	while($c < 18){
 		$text .= $crlf;
 		$c++;
 	}

	if ($template == 1) {
		$text .= $esc . "D" . $esc . "]" . $esc . "G100 Index  200 Search  300 Messages";
	} else {
		$text .= $esc . "A" . $esc . "]" . $esc . "G0 Index";
	}

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