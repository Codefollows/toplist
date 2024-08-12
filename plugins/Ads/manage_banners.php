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

class manage_banners extends base {
  public function __construct() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['header'] = $LNG['p_ads_manage_banners'];

    $TMPL['default_zones'] = array('a', 'b', 'c', 'd');
    $TMPL['zone'] = isset($FORM['zone']) ? $DB->escape($FORM['zone'], 1) : '';

    if (isset($FORM['action'])) {
         $this->process();
    }
    else {
        if(!isset($FORM['zone'])) {	
            $this->zone_list();
        }
        else {
            $this->form();
        }
    }
  }

  function zone_list() {
    global $LNG, $CONF, $DB, $TMPL, $FORM;

    $TMPL['admin_content'] = '
        <script type="text/javascript">
            $(function () {
                $("head").append("<link rel=\"stylesheet\" type=\"text/css\" href=\"plugins/Ads/css/admin_zone_list.css\">");
            });
        </script>
    ';
	$TMPL['admin_content'] .= '<div class="p_ads_button"><a href="index.php?a=admin&b=add_banner" class="positive">'.$LNG['p_ads_add_banner']."</a></div>\n";
	$TMPL['admin_content'] .= $LNG['p_ads_choose_zone']."<br />\n";

    $i = 0;
    $zones_display = '';
    $result = $DB->query("SELECT zone, type FROM {$CONF['sql_prefix']}_osbanners_zones ORDER BY type = 'global|Global' DESC, type ASC", __FILE__, __LINE__);
    while (list($used_zone, $used_type) = $DB->fetch_array($result)) {
        list($type, $type_display) = explode('|', $used_type);

        if ($zones_display != $type_display) {
	        $TMPL['admin_content'] .= ($i > 0) ? "</ul>\n" : '';
	        $TMPL['admin_content'] .= '<ul class="p_ads_headings">
                                         <li><h3>'.$type_display.'</h3></li>
                                         <li><strong>'.$LNG['p_ads_templatetag'].'</strong></li>
                                       </ul>'."\n";
	        $TMPL['admin_content'] .= '<ul class="p_ads_zones">'."\n";
            $zones_display = $type_display;
        }
	    $TMPL['admin_content'] .= '<li class="first"><a href="'.$TMPL['list_url'].'/index.php?a=admin&b=manage_banners&zone='.$used_zone.'">Zone '.$used_zone.'</a></li>'."\n";
	    $TMPL['admin_content'] .= '<li>{$zone_'.$used_zone.'}</li>'."\n";

        if (!in_array($used_zone, $TMPL['default_zones'])) {
	        $TMPL['admin_content'] .= '<li><a href="'.$TMPL['list_url'].'/index.php?a=admin&b=manage_banners&action=delete_zone&del_zone='.$used_zone.'" onClick="return confirmSubmit();">'.$LNG['p_ads_delete'].'</a></li>'."\n";
        }
        $i++;
    }
	$TMPL['admin_content'] .= "</ul>\n";

  }

  function process() {
    global $LNG, $CONF, $DB, $TMPL, $FORM;

    // Delete zone
    if (isset($FORM['action']) && $FORM['action'] == 'delete_zone') 
    {
        if (!in_array($FORM['del_zone'], $TMPL['default_zones'])) 
        {
	        $del_zone = $DB->escape($FORM['del_zone'], 1);
	        $DB->query("DELETE FROM {$CONF['sql_prefix']}_osbanners WHERE display_zone = '{$del_zone}'", __FILE__, __LINE__);
	        $DB->query("DELETE FROM {$CONF['sql_prefix']}_osbanners_zones WHERE zone = '{$del_zone}'", __FILE__, __LINE__);

            $TMPL['admin_content'] = "\"Zone {$del_zone}\" and containing ads have been deleted.";
        }
        else 
        {
            $TMPL['admin_content'] = $LNG['p_ads_error_delete_default'];
        }
        header("refresh:2; url={$TMPL['list_url']}/index.php?a=admin&b=manage_banners");		
    }
    // Update ad
    elseif (isset($FORM['action']) && $FORM['action'] == 'update') 
    {
	    $newcode       = $DB->escape($FORM['editcode'], 0);
	    $newname       = $DB->escape($FORM['editname'], 1);
	    $newid         = intval($FORM['editid']);
	    $new_max_views = intval($FORM['new_max_views']);
	
	    $DB->query("UPDATE {$CONF['sql_prefix']}_osbanners SET code = '{$newcode}', name = '{$newname}', max_views = {$new_max_views} WHERE id = {$newid}", __FILE__, __LINE__);

        $TMPL['admin_content'] = "\"{$newname}\" {$LNG['p_ads_banner_updated']}";
        header("refresh:2; url={$TMPL['list_url']}/index.php?a=admin&b=manage_banners&zone={$TMPL['zone']}");		
    }
    // Delete Ad
    elseif (isset($FORM['action']) && $FORM['action'] == 'delete') 
    {
	    $newid   = intval($FORM['editid']);
	    $newname = $DB->escape($FORM['editname'], 1);

	    $DB->query("DELETE FROM {$CONF['sql_prefix']}_osbanners WHERE id = {$newid}", __FILE__, __LINE__);

        $TMPL['admin_content'] = "\"{$newname}\" {$LNG['p_ads_banner_deleted']}";
        header("refresh:2; url={$TMPL['list_url']}/index.php?a=admin&b=manage_banners&zone={$TMPL['zone']}");
    }
    // Activate ad
    elseif (isset($FORM['action']) && $FORM['action'] == 'activate') 
    {
	    $newid   = intval($FORM['editid']);
	    $newname = $DB->escape($FORM['editname'], 1);

	    $DB->query("UPDATE {$CONF['sql_prefix']}_osbanners SET active = 1 WHERE id = {$newid}", __FILE__, __LINE__);

        $TMPL['admin_content'] =  "\"{$newname}\" {$LNG['p_ads_banner_activated']}";
        header("refresh:2; url={$TMPL['list_url']}/index.php?a=admin&b=manage_banners&zone={$TMPL['zone']}");
    }
    // Deactivate ad
    elseif (isset($FORM['action']) && $FORM['action'] == 'deactivate') 
    {
	    $newid   = intval($FORM['editid']);
	    $newname = $DB->escape($FORM['editname'], 1);

	    $DB->query("UPDATE {$CONF['sql_prefix']}_osbanners SET active = 0 WHERE id = {$newid}", __FILE__, __LINE__);

        $TMPL['admin_content'] = "\"{$newname}\" {$LNG['p_ads_banner_deactivated']}";
        header("refresh:2; url={$TMPL['list_url']}/index.php?a=admin&b=manage_banners&zone={$TMPL['zone']}");		
    }

  }

  function form() {
    global $LNG, $CONF, $DB, $TMPL, $FORM;

    list($type) = $DB->fetch("SELECT type FROM {$CONF['sql_prefix']}_osbanners_zones WHERE zone = '{$TMPL['zone']}'", __FILE__, __LINE__);
    list($notneeded, $type_display) = explode('|', $type);

    $TMPL['admin_content'] = "{$LNG['p_ads_manage_banners']} {$LNG['p_ads_zone']} {$TMPL['zone']} ({$type_display}).  <br /><br />";

    $result = $DB->query("SELECT id, name, display_zone, code, active, views, max_views FROM {$CONF['sql_prefix']}_osbanners WHERE display_zone = '{$TMPL['zone']}' ORDER BY id ASC", __FILE__, __LINE__);
    while (list($id, $name, $display_zone, $code, $active, $views, $max_views) = $DB->fetch_array($result)) {

      $code = htmlentities($code, ENT_NOQUOTES, 'UTF-8');

      $TMPL['admin_content'] .= <<<EndHTML
        <form method="post"><input type="hidden" name="editid" value="{$id}">
          <fieldset>
            <legend>{$name}</legend>
            <table>
              <tr>
                <td>{$LNG['p_ads_banner_name']}</td>
                <td><input type="text" rows="1" name="editname" value="{$name}"></td>
              </tr>
              <tr>
                <td>{$LNG['p_ads_banner_views']} / {$LNG['p_ads_banner_max_views']}:</td><td><img src="images/active{$active}.png" alt="{$active} -> 1=active 0=not active"/> {$views}/<input type="text" rows="1" size="3" name="new_max_views" value="{$max_views}" />{$LNG['p_ads_banner_unlimited']}</td>
              </tr>
              <tr>
                <td>{$LNG['p_ads_banner_code']}</td>
                <td><textarea cols="40" rows="15" id ="editcode" name="editcode" wrap="virtual">$code</textarea></td>
              </tr>
              <tr>
                <td></td>
                <td>
                  <button type="submit" name="action" value="update" class="positive">Save Changes</button>
                  <button type="submit" name="action" value="delete" class="negative">{$LNG['p_ads_delete']}</button>
                  <button type="submit" name="action" value="activate" class="positive">{$LNG['p_ads_banner_activate']}</button>
                  <button type="submit" name="action" value="deactivate" class="negative">{$LNG['p_ads_banner_deactivate']}</button>
                </td>
              </tr> 
            </table>
          </fieldset>
        </form><br />

EndHTML;
    }

 }


}
