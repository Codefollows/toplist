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

class delete_language extends base {
  public function __construct() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;
    
    if (is_array($FORM['id']) && count($FORM['id']) > 1) {
      $TMPL['title'] = sprintf($LNG['a_del_page_multi'], count($FORM['id']));
      $LNG['a_del_page_header'] = $LNG['a_del_page_headers'];
      $LNG['a_del_page_done'] = $LNG['a_del_page_dones'];
    }
    else {
      if (is_array($FORM['id']) && count($FORM['id']) == 1) {
        $TMPL['id'] = $DB->escape($FORM['id'][0]);
      }
      else {
        $TMPL['id'] = $DB->escape($FORM['id']);
      }
      list($TMPL['title'], $TMPL['language']) = $DB->fetch("SELECT phrase_name, language FROM {$CONF['sql_prefix']}_langs WHERE phrase_id = '{$TMPL['id']}'", __FILE__, __LINE__);
    }
    $TMPL['header'] = $LNG['a_del_page_header'];

    if ($TMPL['title']) {
      if (!isset($FORM['submit'])) {
        $this->warning();
      }
      else {
        $this->process();
      }
    }
    else {
      $this->error($LNG['a_del_page_invalid_id'], 'admin');
    }
  }

  function warning() {
    global $FORM, $LNG, $TMPL;

    $ids = '';

    if (is_array($FORM['id']) && count($FORM['id']) > 1) {
      $del_warn = sprintf($LNG['a_del_page_warn'], $TMPL['title']);

      foreach ($FORM['id'] as $id) {
        $ids .= "<input type=\"hidden\" name=\"id[]\" value=\"{$id}\" />\n";
      }
    }
    else {
      $del_warn = sprintf($LNG['a_del_page_warn'], "\$lng->{$TMPL['title']}");

        $ids .= "<input type=\"hidden\" name=\"id[]\" value=\"{$TMPL['id']}\" />\n";
    }

    $TMPL['admin_content'] = <<<EndHTML
{$del_warn}<br /><br />
<form action="index.php?a=admin&amp;b=delete_language" method="post">
{$ids}<input type="submit" name="submit" value="{$LNG['a_custom_menu_delete1']}" />
</form>
EndHTML;
  }

  function process() {
    global $FORM, $LNG, $TMPL;

    if (is_array($FORM['id']) && count($FORM['id']) > 1) {
      foreach ($FORM['id'] as $id) {
        $this->do_delete($id);
      }
    }
    else {
      $this->do_delete($TMPL['id']);
    }

    $TMPL['admin_content'] = $LNG['a_del_language_done'];  
    header("refresh:1; url={$TMPL['list_url']}/index.php?a=admin&b=manage_languages");
  }

  function do_delete($id) {
    global $CONF, $TMPL, $DB;

    $DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_id = '{$id}'", __FILE__, __LINE__);
	
        //GRAB ALL PHRASES
        $output = '';
        $result = $DB->query("SELECT phrase_name, definition FROM {$CONF['sql_prefix']}_langs WHERE language = '{$TMPL['language']}' ORDER BY phrase_name ASC", __FILE__, __LINE__);
        while (list($phrase_name, $definition) = $DB->fetch_array($result)) {
            $definition = str_replace("'","\'",$definition);
            $output .= "\$LNG['{$phrase_name}'] = '$definition';\n";
        }

        $file = "./languages/{$TMPL['language']}.php";
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
        } 
	
  }
}
