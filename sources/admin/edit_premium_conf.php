<?php
//===========================================================================\\
// VISIOLIST is a proud derivative work of Aardvark Topsites                 \\
// Copyright (c) 2000-2007 Jeremy Scheff.  All rights reserved.              \\
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

class edit_premium_conf extends base {
  public function __construct() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['header'] = $LNG['a_edit_premium_header'];
	
	$TMPL['username'] = $DB->escape($FORM['u']);
	
    if (!isset($FORM['submit'])) {
        $this->form();
    }
    else {
        $this->process();
    }
  }

  function form() {
    global $CONF, $DB, $LNG, $TMPL;

    list($TMPL['url'], $TMPL['date_start_premium'], $TMPL['total_day'], $TMPL['remain_day'], $TMPL['weeks_buy'], $TMPL['premium_banner_url']) = $DB->fetch("SELECT url, date_start_premium, total_day, remain_day, weeks_buy, premium_banner_url FROM {$CONF['sql_prefix']}_sites WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);

	$TMPL['url'] = htmlspecialchars($TMPL['url'], ENT_QUOTES, "UTF-8");

    $TMPL['admin_content'] = <<<EndHTML
<form action="{$TMPL['list_url']}/index.php?a=admin&amp;b=edit_premium_conf&amp;u={$TMPL['username']}" method="post">
<fieldset>
<legend>{$LNG['a_edit_premium_header']}</legend>
<br />
Username:&nbsp;&nbsp;<strong>{$TMPL['username']}</strong>
<br />
<br />
Site URL:&nbsp;&nbsp;<strong><a href="{$TMPL['url']}" target="_blank">{$TMPL['url']}</a></strong>
<br />
<br />
<label for="total_day">{$LNG['a_edit_premium_total_day']}</label>
<input type="text" size="5" name="total_day" id="total_day" value="{$TMPL['total_day']}" readonly="true">

<label for="date_start_premium">{$LNG['a_edit_premium_date_start']}</label>
<input type="text" size="10" name="date_start_premium" id="date_start_premium" value="{$TMPL['date_start_premium']}" readonly="true">

<label for="remain_day">{$LNG['a_edit_premium_remain_day']}</label>
<input type="text" size="5" name="remain_day" id="remain_day" value="{$TMPL['remain_day']}">

<label for="weeks_buy">{$LNG['a_edit_premium_total_weeks']}</label>
<input type="text" size="5" name="weeks_buy" id="weeks_buy" value="{$TMPL['weeks_buy']}" readonly="true">

<label for="premium_banner_url">{$LNG['a_edit_premium_banner_url']}</label>
<input type="text" size="60" name="premium_banner_url" id="premium_banner_url" value="{$TMPL['premium_banner_url']}">

</fieldset>

<br />
<input name="submit" type="submit" value="{$LNG['a_edit_update_premium_settings']}" class="positive" />

</form>
EndHTML;
  }

  function process() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $remain_day = $DB->escape($FORM['remain_day']);
	$premium_banner_url = empty($FORM['premium_banner_url']) ? 'NULL' : "'".$DB->escape($FORM['premium_banner_url'])."'";

    $DB->query("UPDATE {$CONF['sql_prefix']}_sites SET remain_day = '{$remain_day}', premium_banner_url = {$premium_banner_url} WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);
 
    $TMPL['admin_content'] = $LNG['a_edit_rev_premium_edited'];
    header("refresh:1; url={$TMPL['list_url']}/index.php?a=admin&b=edit_premium_conf&u={$TMPL['username']}");		
  }
}
