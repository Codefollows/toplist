
if (!empty($CONF['visio_screen_api'])) 
{
	$domain_string = filter_var(htmlspecialchars_decode($TMPL['url'], ENT_QUOTES), FILTER_SANITIZE_URL);
    $screenshot_url = trim($domain_string, '/');
    $screenshot_url = preg_replace('/https?:\/\//', '', $screenshot_url);
    $screenshot_url = preg_replace('/(\/)|(\?)|(#)/', '-', $screenshot_url);

    if (file_exists("{$CONF['path']}/screens/{$screenshot_url}_med.jpg")) {
        $TMPL['screenshot_path'] = 'screens/'.$screenshot_url;
    } 
    else {
        $TMPL['screenshot_path'] = 'screens/none'; 
    }
	
	$TMPL['screenshot'] = $this->do_plugin_skin("{$CONF['path']}/plugins/ScreenShots", 'details_layout');
}
