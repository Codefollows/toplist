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

class manage_languages extends base {
  public function __construct() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['header'] = $LNG['a_man_languages_header'];
	$num_list = 50;
    $TMPL['admin_content'] = '';
	
    // Destroy Language
    if(isset($FORM['destroy']) && $FORM['destroy'] == 1){
		if($FORM['languages'] == 'english') {
            $TMPL['admin_content'] .= $LNG['language_remove_english'];
            header("refresh:1; url={$TMPL['list_url']}/index.php?a=admin&b=manage_languages");
        }
        else {	
            $FORM['languages'] = $DB->escape($FORM['languages'],1);		
            $DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE language = '{$FORM['languages']}'", __FILE__, __LINE__);
            unlink("./languages/{$FORM['languages']}.php");
            $TMPL['admin_content'] .= $LNG['language_removed'];
            header("refresh:1; url={$TMPL['list_url']}/index.php?a=admin&b=manage_languages");
        }			
    }

	// Add New Phrase
    elseif(isset($FORM['newname']) && !empty($FORM['newname'])) {
    
        $FORM['newname'] = $DB->escape($FORM['newname'],1);
        $FORM['defintion'] = $DB->escape($FORM['defintion'],1);
        $FORM['languages'] = $DB->escape($FORM['languages'],1);

        $DB->query("INSERT IGNORE INTO {$CONF['sql_prefix']}_langs SET phrase_name = '{$FORM['newname']}', definition = '{$FORM['defintion']}', language = '{$FORM['languages']}'", __FILE__, __LINE__);
		      
        //GRAB ALL PHRASES
        $output = '';
        $result = $DB->query("SELECT phrase_name,definition,language FROM {$CONF['sql_prefix']}_langs WHERE language = '{$FORM['languages']}' ORDER BY phrase_name ASC", __FILE__, __LINE__);
        while (list($phrase_name,$definition,$language) = $DB->fetch_array($result)) {
            $definition = stripslashes($definition);
            $definition = str_replace('\"', '"', addslashes($definition));
            $phrase_name = preg_replace("/[^ \w]+/", "", $phrase_name);
            $output .= "\$LNG['{$phrase_name}'] = '$definition';\n";
        }

        $file = "./languages/{$FORM['languages']}.php";
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
      
            $TMPL['admin_content'] = $LNG['phrase_added'];
            header("refresh:1; url={$TMPL['list_url']}/index.php?a=admin&b=manage_languages");
      
        } 
	    else {
	        $TMPL['admin_content'] = $LNG['error_writing_language'];
	    }    
    
    }

    // Main Page + Search Result Page
    else {

        $language_list = '<select name="languages">';
        $result = $DB->query("SELECT DISTINCT language FROM {$CONF['sql_prefix']}_langs ORDER BY language ASC", __FILE__, __LINE__);
        while (list($language) = $DB->fetch_array($result)) {
		    if ($language == $CONF['default_language']) {
                $language_list .= "<option selected=\"selected\">{$language}</option>";
			}
			else {
                $language_list .= "<option>{$language}</option>";			
			}
        }
        $language_list .= "</select>";
			
        $import_languages_menu = '<select id="import_languages" name="import_languages">';	
        $import_languages = array();
        $dir = opendir("{$CONF['path']}/languages/import/");
        while (false !== ($file = readdir($dir))) {
            $file = str_replace('.php', '', $file);
            if (is_file("{$CONF['path']}/languages/import/{$file}.php")) {
                $import_languages[$file] = $file;
            }
        }
        ksort($import_languages);
        foreach ($import_languages as $file => $translation) {
            $import_languages_menu .= "<option value=\"{$file}\">{$file}</option>\n";
        }		
        $import_languages_menu .= "</select>";
		  		
		
        $TMPL['admin_content'] .= <<<EndHTML
		
            <script language="javascript" type="text/javascript">
	            $(document).ready(function() {
				    $("#submit_import").click(function() {
                        var lang_name = $("#import_languages").val();
						var url = 'languages/importer.php?l='+lang_name;
						importwindow = window.open(url,'name','height=600,width=850,scrollbars=yes');
	                    if (window.focus) { 
						    importwindow.focus();
							$.fancybox.close();
						}
	                    return false;
                    });
                });
            </script>

            <div style="float: right;">
                <a class="overla" href="#addnew" id="static_opener">[{$LNG['add_new']}]</a> -
                <a class="overla" href="#import" id="import_opener">[{$LNG['import_language']}]</a> -				
                <a class="overla" href="#destory" id="opener">[{$LNG['destroy_language']}]</a>
            </div>

		    <div style="width: 400px; height: auto; overflow: hidden; position: relative;display: none;">
            <div id="import" title="{$LNG['import_language']}">
                <h2>{$LNG['import_language']}</h2>
                {$import_languages_menu}
                <button id="submit_import" class="positive">{$LNG['import_language']}</button>
            </div>
			</div>			
			
		    <div style="width: 400px; height: auto; overflow: hidden; position: relative;display: none;">
            <div id="destory" title="{$LNG['destroy_language']}">
                <form action="{$TMPL['list_url']}/index.php?a=admin&amp;b=manage_languages" method="post">
                    <h2>{$LNG['destroy_language']}</h2>
                    {$LNG['language_warning']}<br />
                    {$language_list}
                    <input type="hidden" name="destroy" value="1" />
                    <button type="submit" class="positive">{$LNG['destroy_language']}</button>
                </form>
            </div>
			</div>

		    <div style="width: auto; height: auto; overflow: hidden; position: relative;display: none;">
            <div id="addnew" title="{$LNG['add_new']}">
                <form action="{$TMPL['list_url']}/index.php?a=admin&amp;b=manage_languages" method="post">
                    <h2>{$LNG['add_new']}</h2>
                    <label class="l120">{$LNG['phrase_name']}:</label> <input name="newname" type="text"/><br />
                    <label class="l120">{$LNG['language']}:</label> $language_list<br />
                    <label class="l120">{$LNG['defintion']}:</label> <textarea name="defintion"></textarea><br />
                    <button type="submit" class="positive">{$LNG['g_form_submit_short']}</button>
                </form>
            </div>
            </div>
 
EndHTML;

        $TMPL['admin_content'] .= "
            <div style=\"float: left;\">
                <form method=\"POST\" action=\"index.php?a=admin&b=manage_languages\">
				    {$language_list} 
                    {$LNG['a_s_search']}: <input type=\"text\" value=\"\" name=\"q\">
                    <button type=\"submit\" class=\"positive\">{$LNG['g_form_submit_short']}</button>
                </form>
            </div>
            <br style=\"clear:both;\"><br />
        ";

 
        if(isset($FORM['languages']) && strlen($FORM['q']) > 1) {

            $TMPL['admin_content'] .= <<<EndHTML

                <form action="{$TMPL['list_url']}/index.php?a=admin&amp;b=delete_language" method="post">
                    <table class="darkbg" cellpadding="1" cellspacing="1" width="100%">
                        <tr class="mediumbg">
                            <td>{$LNG['a_man_delete']}?</td>
                            <td align="center" width="1%">{$LNG['a_man_rev_id']}</td>
                            <td width="20%">{$LNG['phrase_name']}</td>
                            <td width="10%">{$LNG['language']}</td>
                            <td width="70%">{$LNG['defintion']}</td>
                            <td align="center" colspan="2">{$LNG['a_man_actions']}</td>
                        </tr>
EndHTML;

            $alt = '';
            $num = 0;

            $FORM['languages'] = $DB->escape($FORM['languages'],1);
            $result = $DB->select_limit("SELECT phrase_id, language, definition, phrase_name 
                                         FROM 
                                         {$CONF['sql_prefix']}_langs WHERE (phrase_name LIKE \"%{$FORM['q']}%\" OR definition LIKE \"%{$FORM['q']}%\") AND language = '{$FORM['languages']}'
                                         ORDER BY phrase_id ASC", $num_list, 0, __FILE__, __LINE__);
            while (list($id, $language, $definition, $phrase_name) = $DB->fetch_array($result)) {
                if($language == $FORM['languages']) { 
                    $TMPL['admin_content'] .= <<<EndHTML
                        <tr class="lightbg{$alt}">
                            <td><input type="checkbox" name="id[]" value="{$id}" id="checkbox_{$num}" class="check_selectall_none" /></td>
                            <td align="center">{$id}</td>
                            <td width="20%">{\$lng->{$phrase_name}}</td>
                            <td width="10%">{$language}</td>
                            <td width="70%">{$definition}</td>
                            <td align="center"><a href="{$TMPL['list_url']}/index.php?a=admin&amp;b=edit_language&amp;id={$id}">{$LNG['a_man_edit']}</a></td>
                            <td align="center"><a href="{$TMPL['list_url']}/index.php?a=admin&amp;b=delete_language&amp;id={$id}">{$LNG['a_man_delete']}</a></td>
                        </tr>
EndHTML;
    
                    if ($alt) { $alt = ''; }
                    else { $alt = 'alt'; }
                    $num++;
                }
            }
			
            $TMPL['admin_content'] .= <<<EndHTML
                    </table><br />
                    <span id="selectall">{$LNG['a_man_all']}</span> | 
                    <span id="selectnone">{$LNG['a_man_none']}</span><br /><br />
                    <input type="submit" value="{$LNG['a_man_del_sel']}" class="positive" />
                </form>
EndHTML;

        }

    }


  }
}
