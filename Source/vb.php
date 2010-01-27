<?php

/**
 * Simple Viewdata Browser
 * 
 * @version 0.1.2
 * @copyright 2010 Rob O'Donnell
 */

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
 
 
 
$folder = "frames";
$urf="1a";

$page = "";
if (isset($_GET["page"])) {
    if (preg_match('/^[a-zA-Z0-9_.]{2,16}$/', $_GET['page'])) $page = $_GET["page"];
    else $error = "Invalid page number";
} else $page = $urf;

if ($page == "0a") {
    $page=$urf;
}

$fnam="./" . $folder . "/" . str_replace(".","/",$page);

$mode = 0;
if (isset($_GET["mode"])) {
    if (is_numeric($_GET["mode"])) $mode = $_GET["mode"];
    else $error = "Invalid mode";
    if ($mode < 0 || $mode > 1) $mode = 0;
} 

$text = "";
if ($error == "") {
    if (($fn = similar_file_exists($fnam)) != "" ) {
        $text = file_get_contents($fn);
//		$page = substr($fn,strlen($fn)-strlen($page));
    } else {
        $error = chr(130) . "File not found $fnam";
        $format = 1;
    } 
} 
if ($error != "") {
    echo $error;
} else {

    ?><head>
<title>Viewdata Page Browser</title></head>
<body>

<script type="text/javascript">
function textsizer(e){
var evtobj=window.event? event : e //distinguish between IE's explicit event object (window.event) and Firefox's implicit.
var unicode=evtobj.charCode? evtobj.charCode : evtobj.keyCode
var actualkey=String.fromCharCode(unicode)
if (actualkey=="1")
document.body.style.fontSize="120%"
if (actualkey=="z")
document.body.style.fontSize="100%"
}
document.onkeypress=textsizer
</script>


<center>
<?php
	if ($mode == 0) {
?>	    
   <img src="vv.php?format=274&gal=<?php echo $folder;

    ?>&page=<?php echo $page;
    if ($offset > 0) {

        ?>&offset=<?php echo $offset;
    } 

    ?>" alt="<?php echo $page;

    ?>" longdesc="vv.php?format=274&longdesc=1&gal=<?php echo $folder;

    ?>&page=<?php echo $page;
    if ($offset > 0) {

        ?>&offset=<?php echo $offset;
    } 

    ?>" />
<br>
<small><a href="vb.php?mode=1&page=<?php echo $page; ?>">Switch to text mode</a></small><br />

	<?php
	} else {

	?>
   <iframe width=350 height=400 src="vv.php?longdesc=2&format=274&gal=<?php echo $folder;

    ?>&page=<?php echo $page;
    if ($offset > 0) {

        ?>&offset=<?php echo $offset;
    } 

    ?>"  /></iframe>
<br>
<small><a href="vb.php?mode=0&page=<?php echo $page; ?>">Switch to graphics mode</a></small><br />
	
	<?php
	}
?>
<br>

<?php
    for ($i = 1; $i <= 10; $i++) {
        $route = rtrim(substr($text, 14 + 9 * ($i % 10), 9));
        if (substr($route, 0, 1) == "*" || $route == "" ) {
        	$route = "";
        }
        if ($route != "") {
            echo '<a href="' . $PHP_SELF . '?mode=' . $mode . '&page=' . $route . 'a" id="link' . ($i % 10) . '">';
        } 
        echo "[" . ($i % 10) . "]";
        if ($route != "") {
            echo "</a>";
        }
		echo " ";
    } 
    $route = substr($page, 0, strlen($page)-1);
    $frame = substr($page, strlen($page)-1);
    if ($frame < "z") {
        $frame = chr((ord($frame)|32) + 1);
        $route = $route . $frame;
    } else {
        $route = "";
    } 
    if ($route != "") {
        echo '<a href="' . $PHP_SELF . '?mode=' . $mode . '&page=' . $route . '" id="linkHash">';
    } 
    echo "[#] ";
    if ($route != "") {
        echo "</a>";
    } 
} 

?>
</center>
</body>
