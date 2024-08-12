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

error_reporting(0);

class manage_skins extends base {
  public function __construct() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['header'] = $LNG['a_man_skins_header'];

    $template_list = '';
    $error_msg = '';
    $invalid = 0;
	$child = '';
	$editchild = '';
	$subfolder = '';
	$edit_subfolder = '';
	
//Secure this using in_array? perhaps more accurate and efficient.
if (isset($FORM['template_name']) && preg_match("/\.\./i", "{$FORM['template_name']}") || preg_match("/\.\./i", "{$FORM['t']}")) {
    $invalid = 1;
    header("Location: {$CONF['list_url']}/index.php?a=admin&b=skins");
}

// Skin name
$skinname = isset($FORM['s']) ? $FORM['s'] : '';

// Valid folder names
$valid_subfolders = array('form_elements');

// The template to edit
$templatename = isset($FORM['t']) ? $FORM['t'] : '';


//disable / enable
if($FORM['disable'] == "1") {
    rename("{$CONF['path']}/skins/{$skinname}/child", "{$CONF['path']}/skins/{$skinname}/0_child");
    header("Location: {$CONF['list_url']}/index.php?a=admin&b=skins");
}
if($FORM['enable'] == "1") {
    rename("{$CONF['path']}/skins/{$skinname}/0_child", "{$CONF['path']}/skins/{$skinname}/child");
    header("Location: {$CONF['list_url']}/index.php?a=admin&b=skins");
}
   
// Subfolder check
if(isset($FORM['f']) && in_array($FORM['f'], $valid_subfolders)) 
{
	// Append for scandir
    $subfolder = $FORM['f'].'/';
	
	// Append for links
	$edit_subfolder = "&f={$FORM['f']}";
}
   
// Child check
if(isset($FORM['child']) && $FORM['child'] == 1) 
{
    $TMPL['header'] = $LNG['a_man_skins_child'];

	// Append for scandir
    $child = 'child/';
	
	// Append for links
	$editchild = '&child=1';
}

// Child copy parent file
if(isset($FORM['copy']) && $FORM['copy'] == 1) {
    
    $sourcepath = "skins/{$skinname}/{$subfolder}{$templatename}";
    $destpath = "skins/{$skinname}/child/{$subfolder}{$templatename}";
	
	// Check if we have a subfolder, form value save to use cause $subfolder was created within check
	if (!empty($subfolder) && !file_exists("skins/{$skinname}/child/{$subfolder}")) {
		mkdir("skins/{$skinname}/child/{$FORM['f']}", 0777);
	}
	
	// Copy the file to child ( base file or into subfolder )
    if (!copy($sourcepath, $destpath)) {
		$error_msg = "<div style=\"margin: 0 0 10px; color: #ff0000;\">failed to copy ...<br />{$sourcepath} -> {$destpath}</div>";
    }
	
    header("Location: {$CONF['list_url']}/index.php?a=admin&b=manage_skins&s={$skinname}&child=1&t=wrapper.html"); 
    exit;   
}


// Process
if (isset($FORM['template_update']) && $invalid != 1) {
	
	$filename = $FORM['template_name'];
	if (is_writable($filename)) {
	    if (!$handle = fopen($filename, 'w')) {
	        echo "Cannot open file ($filename)";
	        exit;
	    } 
	    if (fwrite($handle, stripslashes($FORM['template_update'])) === FALSE) {
	        echo "Cannot write to file ($filename)";
	        exit;
	    }
	}
}

function checkext($file) {
	if(substr($file,-4) == 'html' || substr($file,-3) == 'css') {
		return $file;
	}
}


// Sub folder files - either parent or child view
foreach ($valid_subfolders as $key => $foldername)
{
	$files = scandir("skins/{$skinname}/{$child}{$foldername}/");
	$files = array_filter($files, "checkext");

	foreach ($files as $file) {
		$filename = str_replace('.html', '', $file);
		$template_list .= "<a href=\"index.php?a=admin&b=manage_skins&s={$skinname}&f={$foldername}{$editchild}&t={$file}\">{$foldername}/{$filename}</a>\n";
	}
}

// Base files - either parent or child view 
$files = scandir("skins/{$skinname}/{$child}");
$files = array_filter($files, "checkext");
rsort($files);

foreach ($files as $file) {
	$filename = str_replace('.html', '', $file);
    $template_list .= "<a href=\"index.php?a=admin&b=manage_skins&s={$skinname}{$editchild}&t={$file}\">{$filename}</a>\n";
}


// Edit child - list parent files to copy
if(isset($FORM['child']) && $FORM['child'] == 1) {
	
    $template_list .= "<h3 style=\"padding: 20px 0 0\">{$LNG['a_man_skins_copy_file']}</h3>\n";
    $template_list .= "<small>{$LNG['a_man_skins_copy_file_warning']}</small>\n";

	// Sub folder files - either parent or child view
	foreach ($valid_subfolders as $key => $foldername)
	{
		$files = scandir("skins/{$skinname}/{$foldername}/");
		$files = array_filter($files, "checkext");

		foreach ($files as $file) {
			$filename = str_replace('.html', '', $file);
			$template_list .= "<a onclick=\"return confirmSubmit()\" href=\"index.php?a=admin&b=manage_skins&s={$skinname}&f={$foldername}&copy=1&t={$file}\">{$foldername}/{$filename}</a>\n";
		}
	}
		
	// Parent files
	$parentfiles = scandir("skins/{$skinname}/");
	$parentfiles = array_filter($parentfiles, "checkext");
	rsort($parentfiles);

	foreach ($parentfiles as $parentfile) {
		$parentfilename = str_replace('.html', '', $parentfile);
		$template_list .= "<a onclick=\"return confirmSubmit()\" href=\"index.php?a=admin&b=manage_skins&s={$skinname}&copy=1&t={$parentfile}\">{$parentfilename}</a>\n";
	}	
}

/////////////////
    

$filename = "skins/{$skinname}/{$child}{$subfolder}{$templatename}";

// Let's make sure the file exists and is writable first.
if (is_writable($filename) && $invalid != 1) {
	
    if (!$handle = fopen($filename, 'r+')) {
         echo "Cannot open file ($filename)";
         exit;
    }
    
	$contents = htmlentities(fread($handle, filesize($filename)), ENT_QUOTES, "UTF-8");
	
    fclose($handle);
} 
else {
    $error_msg = "<div style=\"margin: 0 0 10px; color: #ff0000;\">{$filename} -> {$LNG['a_man_skins_not_writable']}</div>";
}

    $TMPL['admin_content'] = <<<EndHTML

<style type="text/css">


textarea {font: 12px SourceCode,arial, sans-serif; text-shadow: 0 0 0;}
#code {font-family:SourceCode;}
#template_links a {font: 11px arial, sans-serif;text-decoration: none; display: block;background: #454545; color: #c6c1ad; padding: 3px 5px; border-bottom: 1px solid #3b3b3b}
#template_links a:hover {color:#fff;background: #3b3b3b;border-bottom: 1px solid #747474;}

</style>

<div style="width: 200px; float: left; margin-right: 10px;" id="template_links"/>
{$template_list}
</div>
{$error_msg}
<form method="POST" action="index.php?a=admin&b=manage_skins&s={$skinname}{$edit_subfolder}{$editchild}&t={$templatename}" />


<input type="text" name="template_name" value="{$filename}" style="width: 400px;" readonly="readonly"/>
<a href="index.php?a=admin&b=manage_skins&s=parabola&disable=1" class="button">Disable Child</a>
<a href="index.php?a=admin&b=manage_skins&s=parabola&enable=1" class="button">Enable Child</a>


    <textarea id="code" name="template_update">{$contents}</textarea>

    <script>
      var editor = CodeMirror.fromTextArea(document.getElementById("code"), {mode: "text/html", tabMode: "indent"});
    </script>


<button type="submit">{$LNG['a_man_skins_save_file']}</button>

EndHTML;

  }
}
