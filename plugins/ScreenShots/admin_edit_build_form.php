if (!empty($CONF['visio_screen_api'])) {

	$domain_string = filter_var($TMPL['url'],FILTER_SANITIZE_URL);
    $screenshot_url = trim($domain_string, '/');
    $screenshot_url = preg_replace('/https?:\/\//', '', $screenshot_url);
    $screenshot_url = preg_replace('/(\/)|(\?)|(#)/', '-', $screenshot_url);
	$screenshot_url = $screenshot_url.'_med.jpg';
	
	$url_encoded = urlencode($TMPL['url']);

	if (file_exists("{$CONF['path']}/screens/{$screenshot_url}")) {
		$TMPL['screenshot'] = "<div class=\"right\"><img src=\"screens/{$screenshot_url}\" alt=\"{$TMPL['title']}\" /><br /><a href=\"{$TMPL['list_url']}/screenshots.php?url={$url_encoded}&generate=1\" onclick=\"return popitup('screenshots.php?url={$url_encoded}&generate=1')\">{$LNG['plugin_screenshots_update_a_edit']}</a></div>";
	} 
	else {
		$TMPL['screenshot'] = "<div class=\"right\"><img src=\"screens/none_med.jpg\" alt=\"{$TMPL['title']}\" /><br /><a href=\"{$TMPL['list_url']}/screenshots.php?url={$url_encoded}&generate=1\" onclick=\"return popitup('screenshots.php?url={$url_encoded}&generate=1')\">{$LNG['plugin_screenshots_update_a_edit']}</a></div>";
	}

}