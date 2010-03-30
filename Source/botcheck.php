<?php

/**
 * taken from http://www.nes-emulator.com/x_bot.php
 * @version $Id$
 * @copyright 2010
 */

function botcheck()
{
    $botlist = array(
        "Teoma",
        "alexa",
        "froogle",
        "inktomi",
        "looksmart",
        "URL_Spider_SQL",
        "Firefly",
        "NationalDirectory",
        "Ask Jeeves",
        "TECNOSEEK",
        "InfoSeek",
        "WebFindBot",
        "girafabot",
        "crawler",
        "www.galaxy.com",
        "Googlebot",
        "Scooter",
        "Slurp",
        "appie",
        "FAST",
        "WebBug",
        "Spade",
        "ZyBorg",
        "rabaz");

    $isbot = 0;

    foreach($botlist as $bot) {
        if (ereg($bot, $_SERVER['HTTP_USER_AGENT'])) {
            $isbot = 1;
            break;
            /*
		if($bot == "Googlebot") {
			if (substr($REMOTE_HOST, 0, 11) == "216.239.46.") $bot = "Googlebot Deep Crawl";
			elseif (substr($REMOTE_HOST, 0,7) == "64.68.8") $bot = "Google Freshbot";
		}
		if ($QUERY_STRING != "") {
			$url = "http://" . $SERVER_NAME . $PHP_SELF . "?" . $QUERY_STRING . "";
		} else {
			$url = "http://" . $SERVER_NAME . $PHP_SELF . "";
		}

		// settings
		$to = "email@your-domain.com";
		$subject = "Detected: $bot on $url";
$body = "$bot was deteched on $url\n\n
Date.............: " . date("F j, Y, g:i a") . "
Page.............: " . $url . "
Robot Name.......: " . $HTTP_USER_AGENT . "
Robot Address....: " . $REMOTE_ADDR . "
Robot Host.......: " . $REMOTE_HOST . "
";

		mail($to, $subject, $body);
*/
        }
    }



    return $isbot;
}

?>