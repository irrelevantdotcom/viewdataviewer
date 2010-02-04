<?php

/**
 * Simple Viewdata Browser
 * 
 * @version 0.1.5
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
$ipheader = chr(2)."The Gnome At Home";

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

	if (substr($text,104,4) == "    " || substr($text,104,4) == chr(160).chr(160).chr(160).chr(160)) {
	    $top = str_pad($ipheader,24).chr(7).str_pad($page,10).chr(3)."  0p";
	} else {
	    $top = substr($text,104,24).chr(7).str_pad($page,10).chr(3)."  0p";
	}

    ?><head>
<title>Viewdata Page Browser</title></head>
<body>


<center>
<?php
	if ($mode == 0) { 
?>	    
   <img src="vv.php?format=786&gal=<?php echo $folder;?>&page=<?php echo $page;
    if ($offset > 0) {
        ?>&offset=<?php echo $offset;
    } ?>&top=<?php echo htmlentities($top); ?>" alt="<?php echo $page; ?>" 
	longdesc="vv.php?format=786&longdesc=1&gal=<?php echo $folder;?>&page=<?php echo $page;
    if ($offset > 0) {
        ?>&offset=<?php echo $offset;
    } ?>" />
<br>
<small><a href="vb.php?mode=1&page=<?php echo $page; ?>">Switch to text mode</a></small><br />
	<?php
	} else {

	?>
   <iframe width=350 height=400 SCROLLING="no" 
   src="vv.php?longdesc=2&format=786&gal=<?php echo $folder; ?>&page=<?php echo $page;
    if ($offset > 0) {
        ?>&offset=<?php echo $offset;
    } ?>&top=<?php echo htmlentities($top); ?>"  /></iframe>
<br>
<small><a href="vb.php?mode=0&page=<?php echo $page; ?>">Switch to graphics mode</a></small><br />
	
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
        $route = rtrim(substr($text, 14 + 9 * ($i % 10), 9));
        if (substr($route, 0, 1) == "*" || $route == "" ) {
        	$route = "";
        }
        if ($route != "") {
            $routestuff .= '<a href="' . $PHP_SELF . '?mode=' . $mode . '&page=' . $route . 'a" id="link' . ($i % 10) . '">';
			
			echo 'if (actualkey=="' . ($i % 10) . '")
location.href = "' . $PHP_SELF . '?mode=' . $mode . '&page=' . $route . 'a"

';

        } 
        $routestuff .= "[" . ($i % 10) . "]";
        if ($route != "") {
            $routestuff .= "</a>";
        }
		$routestuff .= " ";
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
        $routestuff .= '<a href="' . $PHP_SELF . '?mode=' . $mode . '&page=' . $route . '" id="linkHash">';
			echo 'if (unicode==13)
location.href = "' . $PHP_SELF . '?mode=' . $mode . '&page=' . $route . '"

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
</body>
