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

class ffmpeg_convert extends base {
  public function __construct() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['header'] = $LNG['ffmpeg_header'];
    
	$TMPL['admin_content'] = "<p>{$LNG['ffmpeg_sub_header']}<br />{$LNG['ffmpeg_docs']} <a href=\"http://visiolist.com/community/threads/member-banner-gif-to-mp4-conversion.2201/\" target
	=\"_blank\">Documentation</a></p>";

	$error = 0;
	
	if (!function_exists('shell_exec')) 
	{
		$TMPL['admin_content'] .= "<b><i>{$LNG['ffmpeg_shell_exex_disabled']}</i></b><br /><br />";
		$error = 1;
	}
	else 
	{
		// Force a file path search using "type".
		// "which" may be empty if you not modified $PATH 
		// Returns filepath for ffmpeg, just some safecheck since ffmpeg alone may not always work on every system
		$ffmpeg_path = trim(shell_exec('type -P ffmpeg'));

		if (empty($ffmpeg_path)) {
			$TMPL['admin_content'] .= "<b><i>{$LNG['ffmpeg_not_installed']}</i></b><br /><br />";
			$error = 1;
		}
	}

	if (empty($error)) 
	{
		list($total_users) = $DB->fetch("SELECT COUNT(*) FROM {$CONF['sql_prefix']}_sites", __FILE__, __LINE__);

		$TMPL['admin_content'] .= <<<EndHTML

			<a href="#" id="convert" class="positive">{$LNG['ffmpeg_convert_start']}</a>
			
			<div id="convert_result" style="display: none;">
				<br /><br />
				<h3 id="users"><i class="fas fa-spinner fa-spin"></i> {$LNG['ffmpeg_checking']} <span>0</span>/{$total_users}</h3>
				<br />
													
				<div id="mp4">
					<i class="fas fa-spinner fa-spin"></i> <b>{$LNG['ffmpeg_converting']}</b>
					<div id="converted">{$LNG['ffmpeg_convert_success']}: <span>0</span></div>
					<div id="failed" style="display: none;">
						{$LNG['ffmpeg_convert_fail']}: <span>0</span>
					</div>
				</div><br />
			</div>					

			<script type="text/javascript">
			
				var dataObj = {
					action: 'ffmpeg_convert',
					total: {$total_users},
					checked: 0,
					failed: 0,
					converted: 0
				};
				
				$('#convert').on('click', function (e) {
					
					e.preventDefault();
					
					$('#convert_result').slideDown('slow', function() {
					
						banner_updates(dataObj);
						$('#convert').slideUp('slow');

					});
				});
				
				function banner_updates(dataObj) {

					$.ajax({
						type: 'POST',
						url: '{$CONF['list_url']}/ajax.php',
						data: dataObj,
						cache: false,
						dataType: 'json'
					}).success(function(response) {

						var checked     = response.checked,
							converted   = response.converted,
							failed      = response.failed,
							failed_urls = response.failed_urls;
							
						$('#users span').text(checked);
						
						if (failed > 0) {
							
							if (failed_urls.length > 0) {
								$('#mp4 #failed').append('<div>'+failed_urls+'</div>');
							}
							
							$('#mp4 #failed').find('span').text(failed).end().slideDown('slow');
						}
						
						$('#mp4 #converted span').text(converted);

						if (checked < {$total_users}) {
							
							dataObj = { 
								action: 'ffmpeg_convert',
								total: {$total_users},
								checked: checked,
								converted: converted,
								failed: failed
							};
				
							banner_updates(dataObj);
						}
						else {
							
							$('#users i').remove();
							$('#mp4 i').remove();

							$('#success').slideDown('slow');
						}
						
					}).error(function(jqXHR, textStatus, errorThrown) {

					
					});

				}
			</script>
EndHTML;
	}

  }
}
