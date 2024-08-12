<html>
<head>
<style>
html {background: #424242;}
body {background: #424242;}
</style>
</head>
<body>


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
define('VISIOLIST', 1);
$CONF = array();
$FORM = array();
$TMPL = array();


error_reporting(0);

set_time_limit(0);
ini_set('max_execution_time',0);
// Set encoding for multi-byte string functions
mb_internal_encoding("UTF-8");

if (!isset($_COOKIE['atsphp_sid_admin'])) {
    exit;
}

// Change the path to your full path if necessary
$CONF['path'] = __DIR__;
$FORM = array_merge($_GET, $_POST);

// Require some classes and start the timer
require_once ("{$CONF['path']}/sources/misc/classes.php");

// Connect to the database
require_once ("{$CONF['path']}/settings_sql.php");
require_once ("{$CONF['path']}/sources/sql/{$CONF['sql']}.php");
$DB = "sql_{$CONF['sql']}";
$DB = new $DB;
$DB->connect($CONF['sql_host'], $CONF['sql_username'], $CONF['sql_password'], $CONF['sql_database'], $CONF['debug']);


// Settings
$settings = $DB->fetch("SELECT * FROM {$CONF['sql_prefix']}_settings", __FILE__, __LINE__);
$CONF = array_merge($CONF, $settings);


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
if($FORM['url'] == 'all') {
	$domain_string = '';
    $result = $DB->query("SELECT url FROM {$CONF['sql_prefix']}_sites WHERE active = 1", __FILE__, __LINE__);
    while($domain = $DB->fetch_array($result)){
	$domain['url'] = trim($domain['url'], '/');
    $domain_string .= $domain['url'].', ';
    }

 $domain_string = substr($domain_string,0,-2);
}

if($FORM['list'] == 'pending') {

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

//$DB->close();

if($FORM['generate'] == 1) {


    $domain_string = preg_replace('/https?:\/\//', '', "$domain_string");

    //Begin Output Buffer
    if (ob_get_level() == 0) {
        ob_start();
    }
    ob_implicit_flush(true);


	$arr = explode(",", $domain_string);


	foreach ($arr as &$value) {
		$url = trim($value);
		$url_encoded = urlencode($url);


       $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL, "http://104.207.141.83/index.php?url={$url_encoded}&apikey={$apikey}");
       curl_setopt($ch, CURLOPT_HEADER, 0);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
       curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');
       $content = curl_exec($ch);

   //Close curl connection
    curl_close($ch);

    //Have a nap while the remote server opens browser and saves screenshot
  	sleep(1); //Full Seconds
 	//usleep($throttle); //Microseconds 1000000 =  1 full second

    //clean slashes from urls
 	$cleanurl = preg_replace('/(\/)|(\?)|(#)/', '-', $url);

	$ch = curl_init("http://104.207.141.83/$cleanurl.png");
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
    $top = 0; // set to 20 to hide the IE intranet warning bar

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
    $work -> resize(400, "screens/{$cleanurl}_med.jpg");
    $work -> resize(200, "screens/{$cleanurl}_small.jpg");
    ///

    //Get size in bytes
    $weight = filesize("screens/{$cleanurl}.png");


    ////////////////////////
//Check for good image
if($weight < 13000) {

       $ch = curl_init();
           curl_setopt($ch, CURLOPT_URL, "http://104.207.141.83/index.php?url={$url_encoded}&apikey={$apikey}");
           curl_setopt($ch, CURLOPT_HEADER, 0);
           curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
           curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');
       $content = curl_exec($ch);
       curl_close($ch);

              	sleep(1); //Full Seconds

             	$cleanurl = preg_replace('/(\/)|(\?)|(#)/', '-', $url);

            	$ch = curl_init("http://104.207.141.83/$cleanurl.png");
            	$fp = fopen("screens/$cleanurl.png", 'w');
               		curl_setopt($ch, CURLOPT_FILE, $fp);
               		curl_setopt($ch, CURLOPT_HEADER, 0);
               		curl_exec($ch);
               		curl_close($ch);
            	fclose($fp);

                $filename = "screens/$cleanurl.png";

                list($current_width, $current_height) = getimagesize($filename);

                $left = 0;
                $top = 0; // set to 20 to hide the IE intranet warning bar

                $crop_width = 1000;
                $crop_height = 728;

                // Resample the image
                $canvas = imagecreatetruecolor($crop_width, $crop_height);
                $current_image = imagecreatefrompng($filename);
                imagecopy($canvas, $current_image, 0, 0, $left, $top, $current_width, $current_height);
                imagejpeg($canvas, $filename, 85);


                //RESIZE OPTIONS
                $work = new ImgResizer("screens/{$cleanurl}.png");
                $work -> resize(400, "screens/{$cleanurl}_med.jpg");
                $work -> resize(200, "screens/{$cleanurl}_small.jpg");
                $weight = filesize("screens/{$cleanurl}.png");


             ///////////////////

                             if($weight < 13000) {


                                $ch = curl_init();

                               curl_setopt($ch, CURLOPT_URL, "http://104.207.141.83/index.php?url={$url_encoded}&apikey={$apikey}");
                               curl_setopt($ch, CURLOPT_HEADER, 0);
                               curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                               curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');

                               $content = curl_exec($ch);

                               curl_close($ch);
                              	sleep(2); //Full Seconds

                             	$cleanurl = preg_replace('/(\/)|(\?)|(#)/', '-', $url);

                            	$ch = curl_init("http://104.207.141.83/$cleanurl.png");
                            	$fp = fopen("screens/$cleanurl.png", 'w');
                               		curl_setopt($ch, CURLOPT_FILE, $fp);
                               		curl_setopt($ch, CURLOPT_HEADER, 0);
                               		curl_exec($ch);
                               		curl_close($ch);
                            	fclose($fp);



                                $filename = "screens/$cleanurl.png";

                                list($current_width, $current_height) = getimagesize($filename);

                                $left = 0;
                                $top = 0; // set to 20 to hide the IE intranet warning bar

                                $crop_width = 1000;
                                $crop_height = 728;

                                // Resample the image
                                $canvas = imagecreatetruecolor($crop_width, $crop_height);
                                $current_image = imagecreatefrompng($filename);
                                imagecopy($canvas, $current_image, 0, 0, $left, $top, $current_width, $current_height);
                                imagejpeg($canvas, $filename, 85);


                                //RESIZE OPTIONS
                                $work = new ImgResizer("screens/{$cleanurl}.png");
                                $work -> resize(400, "screens/{$cleanurl}_med.jpg");
                                $work -> resize(200, "screens/{$cleanurl}_small.jpg");
                                $weight = filesize("screens/{$cleanurl}.png");
                }
}


$rand = rand(1,99999);

    $weightkb = round($weight / 1024,2);
	echo "<div style=\"box-shadow: 0 0 9px #000;float: left; width: 200px; margin: 10px; overflow: hidden;text-align: center; background: #1e1e1e; color: #fff; border: 1px solid #000;padding: 5px;font-size: 0.7em;\">$url<br /><img src='screens/{$cleanurl}_small.jpg?a=$rand'> $weightkb KB <a href=\"screenshots.php?url={$url_encoded}&generate=1\" target=\"_blank\"><img src=\"images/refresh.png\" alt=\"Refresh\"></a></div>";
    for($k = 0; $k < 40000; $k++) echo ' ';

    flush();
    ob_flush();

}
$status = 3;

ob_end_flush();


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
       $TMPL['admin_content'] .= '<a id="gen" onClick="loadbar()" href="'.$CONF['list_url'].'/index.php?a=admin&amp;b=screenshots&amp;generate=1&amp;url=all">'.$LNG['plugin_screenshots_generate_a_screenshots'].'</a>


       <div id="progressBar" style="display:none">
            <img src="progressbar.gif" alt="Progress Bar" />
       </div>

       <br /><br />'.$domain_string;
   }
   else {
   $TMPL['admin_content'] .=  "<br /><h3>{$LNG['plugin_screenshots_key_a_screenshots']}</h3>";
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
///////////



?>
</body></html>
