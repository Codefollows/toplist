

//How New Members To Show
$new_members_limit = !empty($CONF['new_member_num']) ? $CONF['new_member_num'] : 5;

$TMPL['premium_member_carousel_list'] = '';
$TMPL['new_members_side_list'] = '';
$TMPL['new_members_side_list_row'] = '';
$TMPL['new_members_footer_list'] = '';
$TMPL['new_members_footer_list_row'] = '';
$TMPL['screenshot'] = '';

$result = $DB->query("SELECT sites.*, stats.join_date, stats.username FROM {$CONF['sql_prefix']}_sites sites, {$CONF['sql_prefix']}_stats stats WHERE sites.username = stats.username AND sites.active = 1 ORDER BY stats.join_date DESC LIMIT $new_members_limit", __FILE__, __LINE__);
while ($row = $DB->fetch_array($result)) {

    $TMPL['newest_members_title'] = htmlspecialchars($row['title'], ENT_QUOTES, "UTF-8");
    $TMPL['newest_members_url'] = $row['url'];
    $TMPL['newest_members_category'] = $row['category'];
    $TMPL['newest_members_username'] = $row['username'];
    $TMPL['newest_members_category_url'] = urlencode($CONF['categories'][$row['category']]['cat_slug']);

    //Show Screenshots? (VisioList API Access required)
    if (!empty($CONF['visio_screen_api']) && $CONF['new_member_screen'] == 1) {

	    $domain_string = filter_var($TMPL['newest_members_url'],FILTER_SANITIZE_URL);
    	$screenshot_url = trim($domain_string, '/');
        $screenshot_url = preg_replace('/https?:\/\//', '', $screenshot_url);
        $screenshot_url = preg_replace('/(\/)|(\?)/', '-', $screenshot_url);
        $screenshot_path = $screenshot_url;
	    $screenshot_exist = $screenshot_url.'_small.jpg';

        if (file_exists("{$CONF['path']}/screens/{$screenshot_exist}")) {
            $TMPL['screenshot_path'] = 'screens/'.$screenshot_path;
        } else {
            $TMPL['screenshot_path'] =  'screens/none';
        }
        $TMPL['screenshot'] = base::do_plugin_skin("{$CONF['path']}/plugins/NewestMembers", 'screenshot_code');

    }

   $TMPL['new_members_side_list_row'] .= base::do_plugin_skin("{$CONF['path']}/plugins/NewestMembers", 'new_members_side_list_row');

   $TMPL['new_members_footer_list_row'] .= base::do_plugin_skin("{$CONF['path']}/plugins/NewestMembers", 'new_members_footer_list_row');

}

$TMPL['sidebar_1_bottom'] .= base::do_plugin_skin("{$CONF['path']}/plugins/NewestMembers", 'new_members_side_list');
$TMPL['newest_members'] = base::do_plugin_skin("{$CONF['path']}/plugins/NewestMembers", 'new_members_list');
$TMPL['footer_1'] .= base::do_plugin_skin("{$CONF['path']}/plugins/NewestMembers", 'new_members_footer_list');

