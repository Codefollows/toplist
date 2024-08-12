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

class edit_language extends base {
  public function __construct() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['header'] = $LNG['a_edit_phrase_header'];

    $id = $DB->escape($FORM['id']);
    list($TMPL['id']) = $DB->fetch("SELECT phrase_id FROM {$CONF['sql_prefix']}_langs WHERE phrase_id = '{$id}'", __FILE__, __LINE__);
    if ($TMPL['id']) {
      if (!isset($FORM['submit'])) {
        $this->form();
      }
      else {
        $this->process();
      }
    }
    else {
      $this->error($LNG['a_del_page_invalid_id'], 'admin');
    }
  }

  function form() {
    global $CONF, $DB, $LNG, $TMPL;

    list($TMPL['phrase_name'], $TMPL['definition'], $TMPL['language']) = $DB->fetch("SELECT phrase_name, definition, language FROM {$CONF['sql_prefix']}_langs WHERE phrase_id = '{$TMPL['id']}'", __FILE__, __LINE__); 

    $TMPL['admin_content'] = <<<EndHTML
<form action="{$TMPL['list_url']}/index.php?a=admin&amp;b=edit_language&amp;id={$TMPL['id']}" method="post">
<fieldset>
<legend>{$LNG['a_edit_phrase_header']}</legend>
<label for="phrase_name">{$LNG['phrase_name']}</label>
<input type="text" name="phrase_name" id="phrase_name" size="50" value="{$TMPL['phrase_name']}" readonly="readonly" /> ID: {$TMPL['id']}

<label for="definition">{$LNG['defintion']}</label>
<textarea style="width: 100%;" rows="10" name="definition" id="definition" class="tinymce1">{$TMPL['definition']}</textarea><br /><br />

<input type="text" name="language" value="{$TMPL['language']}" />
<input name="submit" type="submit" value="{$LNG['a_edit_phrase_header']}" class="positive" />
</fieldset>
</form>
EndHTML;
  }

  function process() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['phrase_name'] = $DB->escape($FORM['phrase_name']);
    $TMPL['definition'] = $DB->escape($FORM['definition']);
    $TMPL['language'] = $DB->escape($FORM['language']);
    
    $DB->query("UPDATE {$CONF['sql_prefix']}_langs SET definition = '{$TMPL['definition']}', phrase_name = '{$TMPL['phrase_name']}' WHERE phrase_id = '{$TMPL['id']}'", __FILE__, __LINE__);

    //GRAB ALL PHRASES
    $output = '';
    $result = $DB->query("SELECT phrase_name,definition,language FROM {$CONF['sql_prefix']}_langs WHERE language = '{$TMPL['language']}' ORDER BY phrase_name ASC", __FILE__, __LINE__);
    while (list($phrase_name,$definition,$language) = $DB->fetch_array($result)) {
        $definition = stripslashes($definition);
        $definition = str_replace('\"', '"', addslashes($definition));
        $phrase_name = preg_replace("/[^ \w]+/", "", $phrase_name);
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
	else {
	    $TMPL['admin_content'] = $LNG['error_writing_language'];
	}
 
    $TMPL['admin_content'] = $LNG['a_edit_phrase_edited'];
    header("refresh:1; url={$TMPL['list_url']}/index.php?a=admin&b=edit_language&id={$TMPL['id']}");
  }
}
