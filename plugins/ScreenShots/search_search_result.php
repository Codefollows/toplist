
if (!empty($CONF['visio_screen_api'])) {

	$domain_string = filter_var($TMPL['url'],FILTER_SANITIZE_URL);
	$screenshot_url = trim($domain_string, '/');
	$screenshot_url = preg_replace('/https?:\/\//', '', $screenshot_url);
	$screenshot_url = preg_replace('/(\/)|(\?)|(#)/', '-', $screenshot_url);
	$screenshot_path = $screenshot_url; 
	$screenshot_url = $screenshot_url.'_small.jpg';

	if (file_exists("{$CONF['path']}/screens/{$screenshot_url}")) {
		$TMPL['screenshot'] = "<img src=\"screens/{$screenshot_url}\" alt=\"{$TMPL['title']}\" class=\"rankshot\"/>"; 
		$TMPL['screenshot_path'] = 'screens/'.$screenshot_path;
	}
	else {
		$TMPL['screenshot'] = "<img src=\"screens/none_small.jpg\" alt=\"{$TMPL['title']}\" class=\"rankshot\"/>"; 
		$TMPL['screenshot_path'] = 'screens/none'; 
	}
}
