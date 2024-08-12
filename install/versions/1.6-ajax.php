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
header("Content-type: application/json; charset=utf-8");
define('VISIOLIST', 1);

// Drop no ajax requests
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {}
else {
    die("No Ajax request.");
}

// Set encoding for multi-byte string functions
mb_internal_encoding("UTF-8");

// Change the path to your full path if necessary
$CONF['path'] = '../..';
require_once ("{$CONF['path']}/settings_sql.php");
require_once ("{$CONF['path']}/sources/sql/{$CONF['sql']}.php");

$DB = "sql_{$CONF['sql']}";
$DB = new $DB;
$DB->connect($CONF['sql_host'], $CONF['sql_username'], $CONF['sql_password'], $CONF['sql_database'], 0);

// Settings
$settings = $DB->fetch("SELECT * FROM {$CONF['sql_prefix']}_settings", __FILE__, __LINE__);
$CONF = array_merge($CONF, $settings);

// Combine the GET and POST input
$FORM = array_merge($_GET, $_POST);

function getExtension($str) 
{
	$i = strrpos($str,".");
	if (!$i) { return ""; }
	$l = strlen($str) - $i;
	$ext = substr($str,$i+1,$l);
	return $ext;
}

$total     = (int)$FORM['total'];
$checked   = (int)$FORM['checked'];
$updated   = (int)$FORM['updated'];
$converted = (int)$FORM['converted'];

// width/height local image not found
$failed1   = (int)$FORM['failed1'];
// width/height external image check allow_url_fopen disabled
$failed2   = (int)$FORM['failed2'];

$remaining = $total - $checked;
$start     = $total - $remaining;

$response = array();

// First load
if (!isset($FORM['ffmpeg_support']))
{
	$ffmpeg_support = 0;

	if (function_exists('shell_exec')) {
		
		// Force a file path search using "type".
		// "which" may be empty if you not modified $PATH 
		// Returns filepath for ffmpeg
		$ffmpeg_path = trim(shell_exec('type -P ffmpeg'));
			
		if (!empty($ffmpeg_path)) {
			$ffmpeg_support = 1;
		}
	} 
}
else {
	$ffmpeg_support = (int)$FORM['ffmpeg_support'];
}

if ($remaining > 0)
{
	$result = $DB->select_limit("SELECT username, banner_url, premium_banner_url FROM {$CONF['sql_prefix']}_sites", 5, $start, __FILE__, __LINE__);
	
    if ($DB->num_rows($result)) 
	{
		while ($row = $DB->fetch_array($result)) 
		{			
			$banner_width  = 0;
			$banner_height = 0;
			$premium_banner_width  = 0;
			$premium_banner_height = 0;
			
			$update_set = '';
			
			// Normal banner
			// If like $CONF['default_banner'] skip, system ( rankings etc ) will read width/height and if exist mp4 of button_config.php
			if (!empty($row['banner_url']) && $row['banner_url'] != $CONF['default_banner']) 
			{	
				// Hosted on list url or external image?
				$local_image = stripos($row['banner_url'], $CONF['list_url']) === 0 ? true : false; 

				if ($local_image === true) 
				{
					// Replace list url with path
					$image_path = str_replace($CONF['list_url'], $CONF['path'], $row['banner_url']);
					
					// Check if file is still on filesystem
					if (file_exists($image_path)) {
						
						$banner_size = getimagesize($image_path);	

						if (!empty($banner_size))
						{			
							$banner_width  = (int)$banner_size[0];
							$banner_height = (int)$banner_size[1];
							
							$update_set .= "banner_width = {$banner_width},banner_height = {$banner_height},";
							
							// gif to mp4 conversion using ffmpeg for smaller filesizes		
							if (!empty($ffmpeg_support)) 
							{
								$banner_extension = getExtension($row['banner_url']);
								$banner_extension = strtolower($banner_extension);

								// Force a file path search using "type".
								// "which" may be empty if you not modified $PATH 
								// Returns filepath for ffmpeg, just some safecheck since ffmpeg alone may not always work on every system
								$ffmpeg_path = trim(shell_exec('type -P ffmpeg'));

								if (!empty($ffmpeg_path)) 
								{
									// We can convert gif. Png aint possible due transparency, jpg possible but many color jpg's get blurry due mp4 max 258 colors or so
									if ($banner_extension == 'gif')
									{
										// Remove list url + slash
										$ffmpeg_image_path = str_replace("{$CONF['list_url']}/", '', $row['banner_url']);

										// Get the filename without extension
										$ffmpeg_image = basename($ffmpeg_image_path, ".{$banner_extension}");

										// Remove slash + file from path
										$ffmpeg_image_path = str_replace("/{$ffmpeg_image}.{$banner_extension}", '', $ffmpeg_image_path);

										// mp4 url
										$mp4_url = $DB->escape("{$CONF['list_url']}/{$ffmpeg_image_path}/mp4/{$ffmpeg_image}.mp4", 1);

										// actual convert command
										// (-y)                                      - Disable confirm promts when overwriting files 
										// (-movflags faststart)                     - Make video playable and load quicker under html5
										// (-pix_fmt yuv420p)                        - Set pixel format. H.264 video is safe across browsers. If not available -pix_fmt auto picks best value for encoder in use
										// (-vf 'scale=trunc(iw/2)*2:trunc(ih/2)*2') - MP4 videos using H.264 need to have a dimensions that is divisible by 2. This option ensures that's the case for the filtergraph frame conversion
										// (</dev/null >/dev/null 2>&1)              - Redirect stdin/stdout/stderr into nothingness and make it a background process
										shell_exec("{$ffmpeg_path} -y -i \"{$CONF['path']}/{$ffmpeg_image_path}/{$ffmpeg_image}.{$banner_extension}\" -movflags faststart -pix_fmt yuv420p -preset veryslow -vf 'scale=trunc(iw/2)*2:trunc(ih/2)*2' \"{$CONF['path']}/{$ffmpeg_image_path}/mp4/{$ffmpeg_image}.mp4\" </dev/null >/dev/null 2>&1");
											
										$update_set .= "mp4_url = '{$mp4_url}',";

										$converted++;
									}
								}
							}
							
							$updated++;
						}
					}
					else {
						$failed1++;
					}
				}
				else 
				{					
					// External file, mostly aardvark upgraders
					if (ini_get('allow_url_fopen')) {
						
						$banner_size = @getimagesize($row['banner_url']);	

						if (!empty($banner_size))
						{			
							$banner_width  = (int)$banner_size[0];
							$banner_height = (int)$banner_size[1];
							
							$update_set .= "banner_width = {$banner_width},banner_height = {$banner_height},";

							$updated++;
						}
						else {
							$failed1++;
						}
					}
					else {
						$failed2++;
					}
				}
				
			}
			
			// Premium banner
			// If like $CONF['default_banner'] skip, system ( rankings etc ) will read width/height and if exist mp4 of button_config.php
			if (!empty($row['premium_banner_url']) && $row['premium_banner_url'] != $CONF['default_banner']) 
			{	
				// Hosted on list url or external image?
				$premium_local_image = stripos($row['premium_banner_url'], $CONF['list_url']) === 0 ? true : false; 

				if ($premium_local_image === true) 
				{
					// Replace list url with path
					$premium_image_path = str_replace($CONF['list_url'], $CONF['path'], $row['premium_banner_url']);
					
					// Check if file is still on filesystem
					if (file_exists($premium_image_path)) {
						
						$premium_banner_size = getimagesize($premium_image_path);	

						if (!empty($premium_banner_size))
						{			
							$premium_banner_width  = (int)$premium_banner_size[0];
							$premium_banner_height = (int)$premium_banner_size[1];
							
							$update_set .= "premium_banner_width = {$premium_banner_width},premium_banner_height = {$premium_banner_height},";

							// gif to mp4 conversion using ffmpeg for smaller filesizes
							// For premium banner, skip mp4 convert if same as normal banner and normal mp4 already converted
							if (!empty($row['banner_url']) && !empty($mp4_url) && $row['banner_url'] == $row['premium_banner_url'])
							{
								$update_set .= "premium_mp4_url = '{$mp4_url}',";
							}
							elseif (!empty($ffmpeg_support)) 
							{
								$premium_banner_extension = getExtension($row['premium_banner_url']);
								$premium_banner_extension = strtolower($premium_banner_extension);

								// Force a file path search using "type".
								// "which" may be empty if you not modified $PATH 
								// Returns filepath for ffmpeg, just some safecheck since ffmpeg alone may not always work on every system
								$ffmpeg_path = trim(shell_exec('type -P ffmpeg'));

								if (!empty($ffmpeg_path)) 
								{
									// We can convert gif. Png aint possible due transparency, jpg possible but many color jpg's get blurry due mp4 max 258 colors or so
									if ($premium_banner_extension == 'gif')
									{
										// Remove list url + slash
										$premium_ffmpeg_image_path = str_replace("{$CONF['list_url']}/", '', $row['premium_banner_url']);

										// Get the filename without extension
										$premium_ffmpeg_image = basename($premium_ffmpeg_image_path, ".{$premium_banner_extension}");

										// Remove slash + file from path
										$premium_ffmpeg_image_path = str_replace("/{$premium_ffmpeg_image}.{$premium_banner_extension}", '', $premium_ffmpeg_image_path);

										// mp4 url
										$premium_mp4_url = $DB->escape("{$CONF['list_url']}/{$premium_ffmpeg_image_path}/mp4/{$premium_ffmpeg_image}.mp4", 1);

										// actual convert command
										// (-y)                                      - Disable confirm promts when overwriting files 
										// (-movflags faststart)                     - Make video playable and load quicker under html5
										// (-pix_fmt yuv420p)                        - Set pixel format. H.264 video is safe across browsers. If not available -pix_fmt auto picks best value for encoder in use
										// (-vf 'scale=trunc(iw/2)*2:trunc(ih/2)*2') - MP4 videos using H.264 need to have a dimensions that is divisible by 2. This option ensures that's the case for the filtergraph frame conversion
										// (</dev/null >/dev/null 2>&1)              - Redirect stdin/stdout/stderr into nothingness and make it a background process
										shell_exec("{$ffmpeg_path} -y -i \"{$CONF['path']}/{$premium_ffmpeg_image_path}/{$premium_ffmpeg_image}.{$premium_banner_extension}\" -movflags faststart -pix_fmt yuv420p -preset veryslow -vf 'scale=trunc(iw/2)*2:trunc(ih/2)*2' \"{$CONF['path']}/{$premium_ffmpeg_image_path}/mp4/{$premium_ffmpeg_image}.mp4\" </dev/null >/dev/null 2>&1");
										
										$update_set .= "premium_mp4_url = '{$premium_mp4_url}',";
					
										$converted++;
									}
								}
							}
							
							$updated++;
						}
					}
					else {
						$failed1++;
					}
				}
				else 
				{					
					// External file, should not be, but just in case
					if (ini_get('allow_url_fopen')) {
						
						$premium_banner_size = @getimagesize($row['premium_banner_url']);	

						if (!empty($premium_banner_size))
						{			
							$premium_banner_width  = (int)$premium_banner_size[0];
							$premium_banner_height = (int)$premium_banner_size[1];
							
							$update_set .= "premium_banner_width = {$premium_banner_width},premium_banner_height = {$premium_banner_height},";

							$updated++;
						}
						else {
							$failed1++;
						}
					}
					else {
						$failed2++;
					}
				}
				
			}
			
			if (!empty($update_set))
			{
				$update_set = rtrim($update_set, ',');
				$DB->query("UPDATE {$CONF['sql_prefix']}_sites SET {$update_set} WHERE username = '{$row['username']}'", __FILE__, __LINE__);
			}

			$checked++;
		}
	}
}

$response['ffmpeg_support'] = $ffmpeg_support;
$response['total']          = $total;
$response['checked']        = $checked;
$response['updated']        = $updated;
$response['converted']      = $converted;
$response['failed1']        = $failed1;
$response['failed2']        = $failed2;

echo json_encode($response);

$DB->close();
