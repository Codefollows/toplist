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

class plugins extends base {
	public function __construct(){
        global $CONF, $FORM, $LNG, $TMPL, $DB;

        $TMPL['header'] = $LNG['a_plugins_header'];
        $TMPL['admin_content'] = '';


        $filename = "{$CONF['path']}/plugins"; // Name the folder
        if (is_writable($filename))
        {
            $writable = "<img src=\"{$TMPL['skins_url']}/{$TMPL['skin_name']}/images/yes.png\" width=\"12px\"> {$LNG['a_plugins_writeable']}";
        } else
        {
            $writable = "<img src=\"{$TMPL['skins_url']}/{$TMPL['skin_name']}/images/no.png\"width=\"12px\"> {$LNG['a_plugins_not_writeable']}";
        }

        if (function_exists('zip_open'))
        {
            $TMPL['admin_content'] .= "<div class=\"highlight\" style=\"padding: 10px;border-radius:5px;\"><img src=\"{$TMPL['skins_url']}/{$TMPL['skin_name']}/images/yes.png\"  width=\"12px\"> {$LNG['a_plugins_zip']}<br />$writable</div><br />\n";
        } else
        {
            $TMPL['admin_content'] .= "<div class=\"error\">{$LNG['a_plugins_not_zip']}</div><br />\n";
        }



        $TMPL['admin_content'] .= <<< EndHTML

        <div style="float:right">
            <form id="file_upload" action="index.php?a=admin&b=plugins" method="POST" enctype="multipart/form-data">
				<input type="hidden" name="MAX_FILE_SIZE" value="600000" />
                <input type="file" name="pluginzip" multiple><br />
                <button class="positive"><strong>{$LNG['a_plugins_upload']}</strong></button>

            </form>
            <table id="files"></table>
        </div>



    <script type="text/javascript">
    function showImage(){
        document.getElementById('loadingImage').style.visibility="visible";
    }

    </script>

EndHTML;


        $TMPL['admin_content'] .= '<h1><a href="index.php?a=admin&b=plugins&check=1" onclick="showImage();">'.$LNG['a_plugins_update_check'].' <img id="loadingImage" src="images/progressbar.gif" style="visibility:hidden; vertical-align:middle;"/></a></h1><br /><table cellpadding="4">';
        $TMPL['admin_content'] .= '<tr>
        <th align="left">'.$LNG['a_plugins_name'].'</th>
        <th>'.$LNG['a_main_your'].'</th>
        <th>'.$LNG['install_header'].'</th>
        <th>'.$LNG['a_custom_menu_delete1'].'</th>
        <th>'.$LNG['a_plugins_disable'].'</th>
        <th>'.$LNG['a_plugins_dependencies'].'</th>
        </tr>';

		$plugin_dir    = "{$CONF['path']}/plugins/";
		$plugin_list   = scandir($plugin_dir);
		
		foreach ($plugin_list as $plugin)
		{
			if ($plugin != '.' && $plugin != '..') 
			{
				// Look for folders of plugins
				$plugin_path = $plugin_dir . $plugin;

				if (!is_dir($plugin_path) || !file_exists("{$plugin_path}/info.php")) 
				{
					continue;
				}
				
				// plugin_real is for enable/disable simplified behavour
				$enabled     = substr($plugin, 0, 2) === '0_' ? false : true;
                $plugin_real = ltrim($plugin, '0_');
				
				// Reset a few info.php vars before they get included
				// to make sure no undefined indexes
                $statusclass  = '';
				$pluginname   = 'N/A';
				$author       = 'N/A';
				$url          = '#';
				$depend       = '';
				$install      = 0;
				$version      = 0;

                require_once("{$plugin_path}/info.php");

                if ($install == 1) {
                    $dbsetup = "<a href=\"index.php?a=admin&b=plugins&install={$plugin}\" title=\"Install\"><img src=\"{$TMPL['skins_url']}/{$TMPL['skin_name']}/images/database_install.png\" alt=\"Install\"></a>";
                } 
				else {
					$dbsetup = '';
				}
				

                // Plugin Update Check
                $update_plugin = '';
                if(isset($_GET['check']) && $_GET['check'] == 1 && !empty($url) && $url !== '#') 
				{
                    $data = file_get_contents($url);

                    $dom = new DOMDocument;
                    $dom->loadHTML($data);
                    $xpath = new DOMXPath($dom);
                    $version_html = $xpath->query("//h1/span[@class='u-muted']");
                    if ($version_html->length > 0) 
					{
                        $new_version = $version_html->item(0)->nodeValue;
						
                        if($new_version > $version) 
						{
                            $update_plugin = '<a href="'.$url.'">'.$LNG['a_plugins_update_to'].' '.$new_version.'</a>';
                            $statusclass   = ' style="background: #e9bab7;"';
                        }
                    }
                }
				
                //ENABLE DISABLE
                if (!$enabled && isset($FORM['enable']) && $FORM['enable'] === $plugin_real) 
				{
                    rename("{$CONF['path']}/plugins/0_{$plugin_real}", "{$CONF['path']}/plugins/{$plugin_real}");
					
					$enabled     = true;
                    $statusclass = ' style="background: #e2eacc;"';
                }
                elseif ($enabled && isset($FORM['disable']) && $FORM['disable'] === $plugin_real) 
				{
                    rename("{$CONF['path']}/plugins/{$plugin_real}", "{$CONF['path']}/plugins/0_{$plugin_real}");
					
					$enabled     = false;
                    $statusclass = ' style="background: #eacccc;"';
                }


                if ($enabled) {
					$enable_disable = "<a href=\"index.php?a=admin&b=plugins&disable={$plugin_real}\"><img src=\"skins/admin/images/yes.png\"> {$LNG['a_plugins_disable']}</a>";
                }
				else {
					$enable_disable = "<a href=\"index.php?a=admin&b=plugins&enable={$plugin_real}\"><img src=\"skins/admin/images/no.png\"> {$LNG['a_plugins_enable']}</a>";
                }


                $TMPL['admin_content'] .= "

                    <tr{$statusclass}>
                        <td><h2><a href=\"{$url}\">{$pluginname}</a></h2></td>
                        <td align=\"center\">{$version}<br />{$update_plugin}</td>
                        <td>{$dbsetup}</td>
                        <td><a onClick=\"return confirmSubmit()\" href=\"index.php?a=admin&b=plugins&delete={$plugin}\" title=\"Delete\"><img src=\"{$TMPL['skins_url']}/{$TMPL['skin_name']}/images/delete.png\" alt=\"delete\"></a></td>
                        <td>{$enable_disable}</td>
                        <td align=\"center\">{$depend}</td>
                    </tr>
				";
			}
		}
		
        $TMPL['admin_content'] .= '</table>';



        if (isset($_FILES['pluginzip']))
        {
            $target_path = "{$CONF['path']}/plugins/";
            $foldername  = substr(basename($_FILES['pluginzip']['name']), 0, -4); // For install include only
            $finaltarget = $target_path . basename($_FILES['pluginzip']['name']);

            if (move_uploaded_file($_FILES['pluginzip']['tmp_name'], $finaltarget)) {
                $TMPL['admin_content'] = "{$LNG['a_plugins_upload_complete1']} " . basename($_FILES['pluginzip']['name']) . " {$LNG['a_plugins_upload_complete2']}";
            } 
			else {
                $TMPL['admin_content'] = $LNG['a_plugins_upload_fail'];
            }


            $zip = zip_open($finaltarget);
            if ($zip)
            {
                while ($zip_entry = zip_read($zip))
                {
                    // Set the zip contents path
                    // e.g plugins/Name/file.php - plugins/Name/subfolder/file.php
					$zip_file = $target_path . zip_entry_name($zip_entry);

				    // Create possible subdirectories before we extract each $zip_file. Only applies for filled directories
				    $zip_subdir = $target_path . dirname(zip_entry_name($zip_entry));
					if(!is_dir($zip_subdir)) { mkdir($zip_subdir, 0777); }

                    $fp = fopen($zip_file, "w");
                    if (zip_entry_open($zip, $zip_entry, "r"))
                    {
                      if($fp) {
					    // Current $zip_file can be written
                        $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                        fwrite($fp, $buf);
                        zip_entry_close($zip_entry);
                        fclose($fp);
					  }
					  else {
		                // Current $zip_file cant be written. It might be a empty directory. Create to be safe
                        mkdir($zip_file, 0777);
                      }
                    }
                }
				
                zip_close($zip);
                unlink($finaltarget);

                if (file_exists("{$CONF['path']}/plugins/{$foldername}/install.php")) {
                    require_once("{$CONF['path']}/plugins/{$foldername}/install.php");
                }
            }
        }


        if (isset($_GET['install']))
        {
            if (file_exists("{$CONF['path']}/plugins/{$_GET['install']}/install.php")) 
			{
				require_once("{$CONF['path']}/plugins/{$_GET['install']}/install.php");
			}
			
			if (isset($already)) {
				$TMPL['admin_content'] = $already;
			}
			else {
				$TMPL['admin_content'] = $LNG['a_plugins_changes_complete'];
			}
			
			header("refresh: 2; url={$TMPL['list_url']}/index.php?a=admin&b=plugins");
        }
        elseif (isset($_GET['delete']))
        {
            $kill = $_GET['delete']; ////VALIDATE THIS!!
            $path = "{$CONF['path']}/plugins/{$kill}";

            if (file_exists("{$CONF['path']}/plugins/{$kill}/delete.php")) {
                require_once("{$CONF['path']}/plugins/{$kill}/delete.php");
            }

            $dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::CHILD_FIRST);

            for ($dir->rewind(); $dir->valid(); $dir->next())
            {
                if ($dir->isDir()) {
                    rmdir($dir->getPathname());
                } 
				else {
                    unlink($dir->getPathname());
                }
            }

            rmdir($path);

            $TMPL['admin_content'] = $LNG['a_plugins_file_delete'];
			
   			header("refresh: 2; url={$TMPL['list_url']}/index.php?a=admin&b=plugins");
        }
    }
}
