<?php
//===========================================================================\\
// VISIOLIST is a proud derivative work of Aardvark Topsites                 \\
// Copyright (c) 2000-2009 Jeremy Scheff.  All rights reserved.              \\
//---------------------------------------------------------------------------\\
// http://www.aardvarktopsitesphp.com/                http://www.avatic.com/ \\
//---------------------------------------------------------------------------\\
// This program is free software; you can redistribute it and/or modify it   \\
// under the terms of the GNU General Public License as published by the     \\
// Free Software Foundation; either version 2 of the License, or (at your    \\
// option) any later version.                                                \\
//                                                                           \\
// This program is distributed in the hope that it will be useful, but       \\
// WITHOUT ANY WARRANTY; without even the implied warranty of                \\
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General \\
// Public License for more details.                                          \\
//===========================================================================\\

if (!defined('VISIOLIST')) {
  die("This file cannot be accessed directly.");
}

class screenshots extends base {
  public function __construct() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['header'] = $LNG['plugin_screenshots_header_a_screenshots'];
    
    $domain_string = '';



//GET API KEY 
$apikey = $CONF['visio_screen_api'];

//Set the width in pixels for medium and small resized images 
$width1 = '400';
$width2 = '200';

//Specific URL
if(isset($FORM['url'])) {
	//$domain_string = parse_url($FORM['url'], PHP_URL_HOST);
	$domain_string = filter_var($FORM['url'],FILTER_SANITIZE_URL);
	$domain_string = trim($domain_string, '/');
}
//ALL URLS
if(isset($FORM['url']) && $FORM['url'] == 'all') {
	$domain_string = '';
    $result = $DB->query("SELECT url FROM {$CONF['sql_prefix']}_sites WHERE active = 1 ORDER BY join_date DESC", __FILE__, __LINE__);
    while($domain = $DB->fetch_array($result)){
	$domain['url'] = trim($domain['url'], '/');
    $domain_string .= $domain['url'].', ';
    }

 $domain_string = substr($domain_string,0,-2); 
}

if(isset($FORM['list']) && $FORM['list'] == 'pending') {

    $result = $DB->query("SELECT requested_url FROM {$CONF['sql_prefix']}_screens", __FILE__, __LINE__);
    while($domain = $DB->fetch_array($result)){
    //$domain['url'] = parse_url($domain['requested_url'], PHP_URL_HOST);
    $domain['url'] = filter_var($domain['requested_url'],FILTER_SANITIZE_URL);
	$domain['url'] = trim($domain['url'], '/');
    $domain_string .= $domain['url'].', ';
    }

 $domain_string = substr($domain_string,0,-2); 
 
     $DB->query("TRUNCATE TABLE {$CONF['sql_prefix']}_screens", __FILE__, __LINE__);
 
}



if(isset($FORM['generate']) && $FORM['generate'] == 1) {

$DB->close();

$domain_string = preg_replace('/https?:\/\//', '', "$domain_string");
mail('mark@osempire.com','VISIO API Request', "$apikey - $domain_string");

//Begin Output Buffer
//HMM  not working inside ATS.

if (ob_get_level() == 0) {
    ob_start();
}
ob_implicit_flush(true);

//Check if user input is clean/valid **NOT DONE HERE
//if(isset($_GET['url']) && isset($_GET['apikey'])) {
	$arr = explode(",", $domain_string);
	
	
	foreach ($arr as &$value) {
    	$url = trim($value);

// make sure curl is installed
//if (function_exists('curl_init')) {
   // initialize a new curl resource
   $ch = curl_init();

   // set the url
   curl_setopt($ch, CURLOPT_URL, "http://visiolist.servehttp.com/screener.php?url={$url}&apikey={$apikey}");

   // ignore headers
   curl_setopt($ch, CURLOPT_HEADER, 0);

   // return the value instead of printing the response to browser
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

   // use a user agent to mimic a browser
   curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');

   $content = curl_exec($ch);

   //Close curl connection
   curl_close($ch);
//} else {
   // curl library is not installed so... alternative
//} 
//	$handle = fopen("Location: http://24.66.138.20/screener.php?url={$_GET['url']}&apikey={$_GET['apikey']}", "r");
	//header("Location: http://24.66.138.20/screener.php?url={$_GET['url']}&apikey={$_GET['apikey']}");
		
    //Have a nap while the remote server opens browser and saves screenshot
  	sleep(4); //Full Seconds
 	//usleep($throttle); //Microseconds 1000000 =  1 full second

    //clean slashes from urls 	
 	$cleanurl = preg_replace('/(\/)|(\?)/', '-', $url);

	$ch = curl_init("http://visiolist.servehttp.com/screenshots/$cleanurl.png");
	$fp = fopen("screens/$cleanurl.png", 'w');
   		curl_setopt($ch, CURLOPT_FILE, $fp);
   		curl_setopt($ch, CURLOPT_HEADER, 0);
   		curl_exec($ch);
   		curl_close($ch);
	fclose($fp);
	
	
///
$filename = "screens/$cleanurl.png";

// Get dimensions of the original image
list($current_width, $current_height) = getimagesize($filename);

// The x and y coordinates on the original image where we
// will begin cropping the image
$left = 0;
$top = 20; // set to 20 to hide the IE intranet warning bar

// This will be the final size of the image (e.g. how many pixels
// left and down we will be going)
$crop_width = 1000;
$crop_height = 728;

// Resample the image
$canvas = imagecreatetruecolor($crop_width, $crop_height);
$current_image = imagecreatefrompng($filename);
imagecopy($canvas, $current_image, 0, 0, $left, $top, $current_width, $current_height);
imagejpeg($canvas, $filename, 85);


//RESIZE OPTIONS
$work = new ImgResizer("screens/{$cleanurl}.png"); 
//$work -> resize(800, "screens/{$cleanurl}_800.jpg");
$work -> resize(400, "screens/{$cleanurl}_med.jpg");
$work -> resize(200, "screens/{$cleanurl}_small.jpg");
///
	
		for($k = 0; $k < 40000; $k++) echo ' ';
	$TMPL['admin_content'] .= "$url {$LNG['plugin_screenshots_rendered_a_screenshots']}<br />";
    flush();
    ob_flush();  
}
$status = 3;



}//GENERATE CHECK
else {
$TMPL['admin_content'] = '


<script type="text/javascript">
function loadbar(){
        document.getElementById("gen").style.display = "none";    
    
    if (navigator.appName == "Microsoft Internet Explorer") {
    document.getElementById("progressBar").innerHTML = "";
    document.getElementById("progressBar").style.display = "block";
    document.getElementById("progressBar").innerHTML = "<img src=\'progressbar.gif\' alt=\'Progress Bar\'>";
    } else {
    document.getElementById("progressBar").style.display = "block";        
    }
}
</script>






'.$LNG['plugin_screenshots_size1_a_screenshots'].' '.$width1.''.$LNG['plugin_screenshots_maxwidth_a_screenshots'].'<br />';
$TMPL['admin_content'] .=  ''.$LNG['plugin_screenshots_size2_a_screenshots'].' '.$width2.''.$LNG['plugin_screenshots_maxwidth_a_screenshots'].'<br />';
$TMPL['admin_content'] .=  "{$LNG['plugin_screenshots_fullsize_a_screenshots']}<br />";

   if ($CONF['visio_screen_api']) {
      /* $TMPL['admin_content'] .= '<a id="gen" onClick="loadbar()" href="'.$CONF['list_url'].'/index.php?a=admin&amp;b=screenshots&amp;generate=1&amp;url=all">'.$LNG['plugin_screenshots_generate_a_screenshots'].'</a>';*/

$TMPL['admin_content'] .= "

<a href=\"screenshots.php?url=all&generate=1\" onclick=\"return popitup('screenshots.php?url=all&generate=1')\">{$LNG['plugin_screenshots_generate_a_screenshots']}</a>

";

$TMPL['admin_content'] .= '


       <div id="progressBar" style="display:none">
            <img src="progressbar.gif" alt="Progress Bar" />
       </div>

       <br /><br />'.$domain_string;
   }
   else {
   $TMPL['admin_content'] .=  "<br /><h3>{$LNG['plugin_screenshots_key_a_screenshots']}</h3>";
   }
}

  }
}


//Class to resize images to specified dimensions
class ImgResizer {
	private $originalFile = '';
	public function __construct($originalFile = '') {
		$this -> originalFile = $originalFile;
	}
	public function resize($newWidth, $targetFile) {
		if (empty($newWidth) || empty($targetFile)) {
			return false;
		}
		$src = imagecreatefromjpeg($this -> originalFile);
		list($width, $height) = getimagesize($this -> originalFile);
		$newHeight = ($height / $width) * $newWidth;
		$tmp = imagecreatetruecolor($newWidth, $newHeight);
		imagecopyresampled($tmp, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
		if (file_exists($targetFile)) {
			unlink($targetFile);
		}
		imagejpeg($tmp, $targetFile, 85);
	}
}
