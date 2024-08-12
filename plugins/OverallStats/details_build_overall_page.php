class stats_overall extends details {
  public function __construct() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

	if ($CONF['clean_url'] == 1 && preg_match('/\?/', $_SERVER['REQUEST_URI'])) 
	{
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: {$CONF['list_url']}/overall-stats/");
		exit;
	}
	
    $TMPL['header'] = $LNG['stats_overall'];

    $stats = $DB->fetch("SELECT SUM(unq_pv_overall), SUM(tot_pv_overall), SUM(unq_in_overall), SUM(tot_in_overall), SUM(unq_out_overall), SUM(tot_out_overall),
                         SUM(unq_pv_0_daily), SUM(unq_pv_1_daily), SUM(unq_pv_2_daily), SUM(unq_pv_3_daily), SUM(unq_pv_4_daily), SUM(unq_pv_5_daily), SUM(unq_pv_6_daily), SUM(unq_pv_7_daily), SUM(unq_pv_8_daily), SUM(unq_pv_9_daily), SUM(tot_pv_0_daily), SUM(tot_pv_1_daily), SUM(tot_pv_2_daily), SUM(tot_pv_3_daily), SUM(tot_pv_4_daily), SUM(tot_pv_5_daily), SUM(tot_pv_6_daily), SUM(tot_pv_7_daily), SUM(tot_pv_8_daily), SUM(tot_pv_9_daily),
                         SUM(unq_in_0_daily), SUM(unq_in_1_daily), SUM(unq_in_2_daily), SUM(unq_in_3_daily), SUM(unq_in_4_daily), SUM(unq_in_5_daily), SUM(unq_in_6_daily), SUM(unq_in_7_daily), SUM(unq_in_8_daily), SUM(unq_in_9_daily), SUM(tot_in_0_daily), SUM(tot_in_1_daily), SUM(tot_in_2_daily), SUM(tot_in_3_daily), SUM(tot_in_4_daily), SUM(tot_in_5_daily), SUM(tot_in_6_daily), SUM(tot_in_7_daily), SUM(tot_in_8_daily), SUM(tot_in_9_daily),
                         SUM(unq_out_0_daily), SUM(unq_out_1_daily), SUM(unq_out_2_daily), SUM(unq_out_3_daily), SUM(unq_out_4_daily), SUM(unq_out_5_daily), SUM(unq_out_6_daily), SUM(unq_out_7_daily), SUM(unq_out_8_daily), SUM(unq_out_9_daily), SUM(tot_out_0_daily), SUM(tot_out_1_daily), SUM(tot_out_2_daily), SUM(tot_out_3_daily), SUM(tot_out_4_daily), SUM(tot_out_5_daily), SUM(tot_out_6_daily), SUM(tot_out_7_daily), SUM(tot_out_8_daily), SUM(tot_out_9_daily),
                         SUM(unq_pv_0_weekly), SUM(unq_pv_1_weekly), SUM(unq_pv_2_weekly), SUM(unq_pv_3_weekly), SUM(unq_pv_4_weekly), SUM(unq_pv_5_weekly), SUM(unq_pv_6_weekly), SUM(unq_pv_7_weekly), SUM(unq_pv_8_weekly), SUM(unq_pv_9_weekly), SUM(tot_pv_0_weekly), SUM(tot_pv_1_weekly), SUM(tot_pv_2_weekly), SUM(tot_pv_3_weekly), SUM(tot_pv_4_weekly), SUM(tot_pv_5_weekly), SUM(tot_pv_6_weekly), SUM(tot_pv_7_weekly), SUM(tot_pv_8_weekly), SUM(tot_pv_9_weekly),
                         SUM(unq_in_0_weekly), SUM(unq_in_1_weekly), SUM(unq_in_2_weekly), SUM(unq_in_3_weekly), SUM(unq_in_4_weekly), SUM(unq_in_5_weekly), SUM(unq_in_6_weekly), SUM(unq_in_7_weekly), SUM(unq_in_8_weekly), SUM(unq_in_9_weekly), SUM(tot_in_0_weekly), SUM(tot_in_1_weekly), SUM(tot_in_2_weekly), SUM(tot_in_3_weekly), SUM(tot_in_4_weekly), SUM(tot_in_5_weekly), SUM(tot_in_6_weekly), SUM(tot_in_7_weekly), SUM(tot_in_8_weekly), SUM(tot_in_9_weekly),
                         SUM(unq_out_0_weekly), SUM(unq_out_1_weekly), SUM(unq_out_2_weekly), SUM(unq_out_3_weekly), SUM(unq_out_4_weekly), SUM(unq_out_5_weekly), SUM(unq_out_6_weekly), SUM(unq_out_7_weekly), SUM(unq_out_8_weekly), SUM(unq_out_9_weekly), SUM(tot_out_0_weekly), SUM(tot_out_1_weekly), SUM(tot_out_2_weekly), SUM(tot_out_3_weekly), SUM(tot_out_4_weekly), SUM(tot_out_5_weekly), SUM(tot_out_6_weekly), SUM(tot_out_7_weekly), SUM(tot_out_8_weekly), SUM(tot_out_9_weekly),
                         SUM(unq_pv_0_monthly), SUM(unq_pv_1_monthly), SUM(unq_pv_2_monthly), SUM(unq_pv_3_monthly), SUM(unq_pv_4_monthly), SUM(unq_pv_5_monthly), SUM(unq_pv_6_monthly), SUM(unq_pv_7_monthly), SUM(unq_pv_8_monthly), SUM(unq_pv_9_monthly), SUM(tot_pv_0_monthly), SUM(tot_pv_1_monthly), SUM(tot_pv_2_monthly), SUM(tot_pv_3_monthly), SUM(tot_pv_4_monthly), SUM(tot_pv_5_monthly), SUM(tot_pv_6_monthly), SUM(tot_pv_7_monthly), SUM(tot_pv_8_monthly), SUM(tot_pv_9_monthly),
                         SUM(unq_in_0_monthly), SUM(unq_in_1_monthly), SUM(unq_in_2_monthly), SUM(unq_in_3_monthly), SUM(unq_in_4_monthly), SUM(unq_in_5_monthly), SUM(unq_in_6_monthly), SUM(unq_in_7_monthly), SUM(unq_in_8_monthly), SUM(unq_in_9_monthly),  SUM(tot_in_0_monthly), SUM(tot_in_1_monthly), SUM(tot_in_2_monthly), SUM(tot_in_3_monthly), SUM(tot_in_4_monthly), SUM(tot_in_5_monthly), SUM(tot_in_6_monthly), SUM(tot_in_7_monthly), SUM(tot_in_8_monthly), SUM(tot_in_9_monthly),
                         SUM(unq_out_0_monthly), SUM(unq_out_1_monthly), SUM(unq_out_2_monthly), SUM(unq_out_3_monthly), SUM(unq_out_4_monthly), SUM(unq_out_5_monthly), SUM(unq_out_6_monthly), SUM(unq_out_7_monthly), SUM(unq_out_8_monthly), SUM(unq_out_9_monthly), SUM(tot_out_0_monthly), SUM(tot_out_1_monthly), SUM(tot_out_2_monthly), SUM(tot_out_3_monthly), SUM(tot_out_4_monthly), SUM(tot_out_5_monthly), SUM(tot_out_6_monthly), SUM(tot_out_7_monthly), SUM(tot_out_8_monthly), SUM(tot_out_9_monthly)
                         FROM {$CONF['sql_prefix']}_stats", __FILE__, __LINE__);

    // Get rid of SUM() in array keys
    foreach ($stats as $key => $value) {
      $new_key = str_replace(array('SUM(', ')'), '', $key);
      $stats[$new_key] = $value;
      unset($stats[$key]);
    }
	
    $TMPL = array_merge($TMPL, $stats);

    $this->averages();
	
	// Number format only valid stats, after averages have been built
	// As average needs ints
	foreach ($stats as $key => $value)
	{
		if (strpos($key, 'unq_') === 0 || strpos($key, 'tot_') === 0)
		{
			$TMPL[$key] = number_format($TMPL[$key]);
		}
	}

    $this->locale();
 

    // Plugin Hook - Can be used to extend this overall stats plugin
    eval (PluginManager::getPluginManager ()->pluginHooks ('plugin_overall_stats_extend'));
 

    $TMPL['content'] = $this->do_plugin_skin("{$CONF['path']}/plugins/OverallStats", 'stats_overall');
  }
} 