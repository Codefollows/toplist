
$TMPL['error_new_member_num'] = '';
$TMPL['error_style_new_member_num'] = '';

$TMPL['new_member_screen'] = $CONF['new_member_screen'];
$TMPL['new_member_num'] = $CONF['new_member_num'];


$admin_new_tab_settings .= "<h3>{$LNG['a_s_newest_members']}</h3>";
$admin_new_tab_settings .= '<div>';

$admin_new_tab_settings .= generate_select('new_member_screen', $LNG['a_s_newest_members_screen'], '1, 0', 'Yes, No', $TMPL['new_member_screen']);
$admin_new_tab_settings .= generate_input('new_member_num', $LNG['a_s_newest_members_num'], 20);

$admin_new_tab_settings .= '</div>';
