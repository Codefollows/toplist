<?php
//===========================================================================\\
// Aardvark Topsites PHP 5                                                   \\
// Copyright (c) 2003-2005 Jeremy Scheff.  All rights reserved.              \\
//---------------------------------------------------------------------------\\
// http://www.aardvarkind.com/                        http://www.avatic.com/ \\
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


//===========================================================================\\
// OS Topsite Ad Manager                                                     \\
// Copyright (c) 2006 Mark Artyniuk.       All rights reserved.              \\
//---------------------------------------------------------------------------\\
// http://www.osempire.com/                         http://www.osempire.com/ \\
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


class add_banner extends base {
  public function __construct() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['header'] = $LNG['p_ads_add_banner'];

    $TMPL['error_top']             = '';
    $TMPL['error_style_top']       = '';
    $TMPL["error_name"]            = '';    
    $TMPL["error_style_name"]      = '';
    $TMPL["error_code"]            = '';    
    $TMPL["error_style_code"]      = '';
    $TMPL["error_zone_new"]        = '';    
    $TMPL["error_style_zone_new"]  = '';
    $TMPL["error_max_views"]       = '';    
    $TMPL["error_style_max_views"] = '';


    if (!isset($FORM['submit'])) {
      $this->form();
    }
    else {
      $this->process();
    }

  }

  function form() {
    global $LNG, $CONF, $DB, $TMPL, $FORM;

    $TMPL['name']      = !isset($TMPL['name'])      ? '' : htmlspecialchars(stripslashes($TMPL['name']), ENT_QUOTES, "UTF-8"); 
    $TMPL['code']      = !isset($TMPL['code'])      ? '' : htmlspecialchars(stripslashes($TMPL['code']), ENT_QUOTES, "UTF-8"); 
    $TMPL['zone']      = !isset($TMPL['zone'])      ? '' : htmlspecialchars(stripslashes($TMPL['zone']), ENT_QUOTES, "UTF-8"); 
    $TMPL['zone_new']  = !isset($TMPL['zone_new'])  ? '' : htmlspecialchars(stripslashes($TMPL['zone_new']), ENT_QUOTES, "UTF-8"); 
    $TMPL['type']      = !isset($TMPL['type'])      ? '' : htmlspecialchars(stripslashes($TMPL['type']), ENT_QUOTES, "UTF-8"); 
    $TMPL['max_views'] = !isset($TMPL['max_views']) ? '' : htmlspecialchars(stripslashes($TMPL['max_views']), ENT_QUOTES, "UTF-8"); 
    $TMPL['active']    = !isset($TMPL['active'])    ? '' : htmlspecialchars(stripslashes($TMPL['active']), ENT_QUOTES, "UTF-8"); 

    $TMPL['admin_content'] = <<<EndHTML

    <script type="text/javascript">
        $(function() {
            var zone_new = '{$TMPL['zone_new']}';
            if (zone_new.length < 1) {
                $('#zone_new_container, #type_new_container').hide();
            }
            else {
                $('#zone_container').hide();
                $('#add_zone').attr('id', 'cancel_zone').text('Cancel');
            }
            $("#add_banner").on('click', '#add_zone', function() {
                $(this).attr('id', 'cancel_zone').text('Cancel');
                $('#zone_container').hide();
                $('#zone_new_container, #type_new_container').show();
            });
            $("#add_banner").on('click', '#cancel_zone', function() {
                $(this).attr('id', 'add_zone').text('{$LNG['p_ads_add_new_zone']}');
                $('#zone_new_container').hide().find('input').val('');
                $('#type_new_container').hide();
                $('#zone_container').show();
            });
            $("#add_banner").on('change', '#type_new', function() {
                if($(this).val() == 'ad_break|Ad Breaks') {
                    $('#zone_new').after('<div>Ad break zone name must be either<br />"ad_break" to be used in ad_break.html<br /> Or "ad_break_top" to be used in ad_break_top.html</div>');
                }
                else {
                    $('#zone_new').next('div').remove();
                }
            });
        });
    </script>

EndHTML;

    $TMPL['admin_content'] .= '
        <p class="'.$TMPL['error_style_top'].'">'.$TMPL['error_top'].'</p>
        <form action="index.php?a=admin&amp;b=add_banner" method="post" id="add_banner">
        <fieldset>
    ';

    $TMPL['admin_content'] .= generate_input('name', $LNG['p_ads_banner_name'], 50, 1);
    $TMPL['admin_content'] .= generate_textarea('code', $LNG['p_ads_banner_code'], 1);

    $zones = '';
    $zones_display = '';
    $result = $DB->query("SELECT zone, type FROM {$CONF['sql_prefix']}_osbanners_zones ORDER BY type = 'global|Global' DESC, type ASC", __FILE__, __LINE__);
    while (list($used_zone, $used_type) = $DB->fetch_array($result)) {
        list($type, $type_display) = explode('|', $used_type);
        $zones .= "{$used_zone}|{$type}, ";
        $zones_display .= "{$type_display} - {$LNG['p_ads_zone']} {$used_zone}, ";
    }
    $zones = rtrim($zones, ', ');
    $zones_display = rtrim($zones_display, ', ');
    $TMPL['admin_content'] .= generate_select('zone', $LNG['p_ads_zone'], $zones, $zones_display);
    $TMPL['admin_content'] .= generate_input('zone_new', $LNG['p_ads_zone_new'], 50);
    $TMPL['admin_content'] .= generate_select('type_new', $LNG['p_ads_zone_display_type'], 'global|Global, details|Details Page, in|Gateway Page, ad_break|Ad Breaks', 'Global, Details Page, Gateway Page, Ad Breaks');
    $TMPL['admin_content'] .= '<a href="#" id="add_zone">'.$LNG['p_ads_add_new_zone'].'</a>';

    $TMPL['admin_content'] .= generate_input('max_views', $LNG['p_ads_banner_deactivate_number'], 50, 1);
    $TMPL['admin_content'] .= generate_select('active', $LNG['p_ads_banner_activate'], '1, 0', "{$LNG['a_s_yes']}, {$LNG['a_s_no']}");

    $TMPL['admin_content'] .= '</fieldset><br /><input name="submit" class="positive" type="submit" value="'.$LNG['p_ads_add_banner'].'" />
                               </form>';

  }

  function process() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['name']      = $DB->escape($FORM['name'], 1);
    $TMPL['code']      = $DB->escape($FORM['code']);
    $TMPL['zone']      = $DB->escape($FORM['zone'], 1);
    list($zone, $type) = explode('|', $TMPL['zone']);

    $TMPL['zone_new']  = $DB->escape($FORM['zone_new'], 1);
    $TMPL['type_new']  = $DB->escape($FORM['type_new'], 1);
    $TMPL['max_views'] = intval($FORM['max_views']);
    $TMPL['active']    = intval($FORM['active']);

    // validate fields
	$form_validate = array(
        validate_only_required('name', $TMPL['name']),
        validate_only_required('code', $TMPL['code']),
        validate_preg_match('zone_new', $TMPL['zone_new'], '/^[a-z0-9_]+$/D'),
        validate_number('max_views', $TMPL['max_views'])
    );
    if (!empty($TMPL['zone_new'])) {
        array_push($form_validate, validate_db_duplicate('zone_new', $TMPL['zone_new'], array('`zone`'), 'osbanners_zones'));
        if ($TMPL['type_new'] == 'ad_break|Ad Breaks') {
            if (validate_preg_match('zone_new', $TMPL['zone_new'], '/^(ad_break|ad_break_top)$/D') == 0) {
                array_push($form_validate, 0);
                error_display('zone_new', $LNG['p_ads_error_ad_break_zone']); 
            }
        }
    }

    if (!in_array(0, $form_validate)) {

        if (!empty($TMPL['zone_new'])) {
            $DB->query("INSERT INTO `{$CONF['sql_prefix']}_osbanners_zones` (`zone`, `type`) VALUES ('{$TMPL['zone_new']}', '{$TMPL['type_new']}')", __FILE__, __LINE__);
            $zone = $TMPL['zone_new'];
            list($type, $notneeded) = explode('|', $TMPL['type_new']);
        }
        $DB->query("INSERT INTO `{$CONF['sql_prefix']}_osbanners` (`code`, `name`, `display_zone`, `active`, `max_views`, `type`) VALUES ('{$TMPL['code']}', '{$TMPL['name']}', '{$zone}', {$TMPL['active']}, {$TMPL['max_views']}, '{$type}')", __FILE__, __LINE__);

        $TMPL['admin_content'] = $LNG['p_ads_banner_added'];
        header("refresh:2; url={$TMPL['list_url']}/index.php?a=admin&b=manage_banners");		
    }
    else {
	    $this->form();
	}

  }

}
