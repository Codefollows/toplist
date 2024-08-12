<html>
<style>
html {background: #424242;}
body {background: #424242; color: #fff;}
</style>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<script language="Javascript" type="text/javascript">	
    var Step = 10;
    function scrollToBottom() { 
        var y = 0;
        if (window.pageYOffset) {
            y = window.pageYOffset;
        }
		else if (document.body && document.body.scrollTop) {
            y = document.body.scrollTop;
        }	
        window.scrollBy(0, Step);
        if (document.readyState === "complete") {
	        if (y == window.pageYOffset) {
			    return false;
            }
            else if (y == document.body.scrollTop) {
			    return false;
            }
			else {
			    window.setTimeout('scrollToBottom()', 1);
			}            
        }				
        window.setTimeout('scrollToBottom()', 1);
		return true;
    }
    window.onLoad = scrollToBottom();	
</script>
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

// Set encoding for multi-byte string functions
mb_internal_encoding("UTF-8");

// Cookie check
if (isset($_COOKIE['atsphp_sid_admin']) || isset($_COOKIE['vl_install'])) {}
else {exit;}

// Change the path to your full path if necessary
$CONF['path'] = __DIR__ .'/..';
$FORM = array_merge($_GET, $_POST);

// Set to 1 to display SQL queries and GET/POST/COOKIE data
$CONF['debug'] = 0;

// Require some classes and start the timer
require_once ("{$CONF['path']}/sources/misc/classes.php");
$TIMER = new timer;
	
// Connect to the database
require_once ("{$CONF['path']}/settings_sql.php");
require_once ("{$CONF['path']}/sources/sql/{$CONF['sql']}.php");
$DB = "sql_{$CONF['sql']}";
$DB = new $DB;
$DB->connect($CONF['sql_host'], $CONF['sql_username'], $CONF['sql_password'], $CONF['sql_database'], $CONF['debug']);
	
	
if(isset($FORM['l']) && !preg_match('/[^a-zA-Z_]/i', $FORM['l'])) 
{
    $language = $FORM['l'];

	if (file_exists("{$CONF['path']}/languages/import/{$language}.php"))
	{
		//Begin Output Buffer
		if (ob_get_level() == 0) {
			ob_start();
		}
		ob_implicit_flush(true);
	
		// Selected language
		// In there we might have newly translated phrases he before only had in english. User imports and have all these in his database + file
		// existing phrases in database will be skipped and not imported again.
		require_once("{$CONF['path']}/languages/import/{$language}.php");		
		
		foreach ($LNG as $key => $value) {

			$key = $DB->escape("$key",1);
			$value = $DB->escape("$value",1);

			// Insert all vars not already there
			$DB->query("INSERT IGNORE INTO {$CONF['sql_prefix']}_langs SET phrase_name = '{$key}', definition = '{$value}', language = '{$language}'", __FILE__, __LINE__);
			
			//$phrase_id = mysqli_insert_id($DB);
			// echo out successful inserts
			//list($import_phrase_name, $import_definition) = $DB->fetch("SELECT phrase_name, definition FROM {$CONF['sql_prefix']}_langs WHERE language = '{$language}' AND phrase_id = {$phrase_id}", __FILE__, __LINE__);
			//if ($phrase_id) {
			//    echo "<div style=\"margin: 5px;\"><span style=\"box-shadow: 0 0 9px #000; overflow: hidden;text-align: center; background: #1e1e1e; border: 1px solid #000; margin: 0 5px; padding: 2px; font-size: 0.7em;\">Imported!</span>Key: {$import_phrase_name}; Value: {$import_definition}</div>\n";
			//}
			
			flush();
			ob_flush();
		
		}

		ob_end_flush();
		
		// Rewrite language file
		$output = '';
		$result = $DB->query("SELECT phrase_name, definition FROM {$CONF['sql_prefix']}_langs WHERE language = '{$language}' ORDER BY phrase_name ASC", __FILE__, __LINE__);
		while (list($phrase_name, $definition) = $DB->fetch_array($result)) {
			$definition = stripslashes($definition);
			$definition = str_replace('\"', '"', addslashes($definition));
			$output .= "\$LNG['{$phrase_name}'] = '$definition';\n";
		}

		$file = "{$CONF['path']}/languages/{$language}.php";
		if ($fh = @fopen($file, 'w')) {
			$lang_output = <<<EndHTML
<?php

if (!defined('VISIOLIST')) {
  die("This file cannot be accessed directly.");
}

$output
?>
EndHTML;
			fwrite($fh, $lang_output);
			fclose($fh);
      
			// Success message
			echo "<div style=\"box-shadow: 0 0 9px #000; overflow: hidden;text-align: center; background: #1e1e1e; border: 1px solid #000; margin: 10px; padding: 5px;\">{$language} language successfully imported and file rewritten</div>\n";
		} 
		else {
			echo "<div style=\"box-shadow: 0 0 9px #000; overflow: hidden;text-align: center; background: #1e1e1e; border: 1px solid #000; margin: 10px; padding: 5px;\">Error writing {$language} language file</div>\n";
		}  	
	
		// Close connection
	}
}

$DB->close();

?>
</body></html>