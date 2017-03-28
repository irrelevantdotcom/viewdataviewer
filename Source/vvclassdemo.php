<?php

/**
 * Example vv class usage.
 * put some viewdata files in ./upload
 * create a ./cache folder for temporary workspace (optional)
 * ensure you copy in the GIFEncoder.class.php file & Fonts folder.
 *
 * Please note this is just an example of how to call the class.  There is
 * no sanitisation of inputs, and using this code in a production environment
 * may be dangerous.
 *
 * @version 1.0.1 Demo
 * @copyright 2011 Robert O'Donnell.
 */


include('vv.class.php');

/*****************************************************************************
*  Default HTML page when called with no parameters
*
* ***************************************************************************/
if (!isset($_GET['action'])) {
	?>
	<html><body>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get"
	enctype="multipart/form-data">
	<label for="file">Existing File:</label>
	<select name="file">
	<?php
	if ($dh = opendir ("./upload/")) {
		while (FALSE !== ($dat = readdir ($dh))) { // for each file
			if (substr($dat, 0, 1) != ".") {
				echo "<option value=\"" . $dat ."\">" . $dat . "</option>\n";
			}
		}
	} ?>
	</select>
	<input type="submit" name="action" value="View"><br />
	</form>
	<br />

	</body></html>
	<?php
	exit;
}

session_start();

switch($_GET['action']){
/*****************************************************************************
 * File selected  for viewing.  Initialise a class variable and load the file,
 * fetch the textual equivelant and display it.  Set up for image call.
 ****************************************************************************/
	case "View":
		$svar=md5($_GET['file']);	// session variable name for this file
		$_SESSION[$svar] = new ViewdataViewer();
		if (!$_SESSION[$svar]->LoadFile("./upload/".$_GET['file'],0,$_GET['file'])) {
			echo "Unable to load or identify file";
		} else {
			?>
			<html>
			<body>
 <?php
 			echo "<p>File is of type \"" . vvtypes($_SESSION[$svar]->format) . "\".</p>\n";
			if ($_SESSION[$svar]->framesfound > 1) {
				echo "<p>File contains " . $_SESSION[$svar]->framesfound . " frames.</p>\n";
				echo "<p>These are indexed as: <select name=\"frame\">\n";
				foreach ( $_SESSION[$svar]->frameindex as $k=>$l){
					echo "<option>$k</option>\n";
				}
				echo "</select></p>\n";
			} else {
				echo"<p>File contains 1 frame.</p>\n";
			}

			echo "<hr><p>First frame, page number \"" .  $_SESSION[$svar]->ReturnMetaData(NULL,"pagenumber") . "\", is:</p><hr>\n";

			if (($t=$_SESSION[$svar]->ReturnScreen(NULL,"stars")) === FALSE)  {
				echo "ReturnScreen failed";
			} else {
				echo "<pre>\n" . $t . "</pre>\n";
			}


/* ******* call image - note passing of session variable name ***************/
			?>
			<hr>
			<img src="<?php echo $_SERVER['PHP_SELF']; ?>?action=image&foo=<?php echo $svar; ?>">
			<hr>
			</body></html>
			<?php
		}
		break;
/*****************************************************************************
 * Fetch image version of file, and display it.  Note that this is called as a
 * seperate instance, so we use session variables to find the same data.
 ****************************************************************************/
	case "image":
		$svar=$_GET['foo'];	// retrieve session variable name for this file
		if (($t=$_SESSION[$svar]->ReturnScreen(NULL,"image")) === FALSE)  {
			echo "ReturnScreen failed";
		} else {
			switch($_SESSION[$svar]->ReturnScreen(NULL,"imagetype")){
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
		break;
	default:
		;
} // switch


?>