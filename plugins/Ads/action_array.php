
// Start OS Banners 
$TMPL['zone_a'] = '';
$TMPL['zone_b'] = '';
$TMPL['zone_c'] = '';
$TMPL['zone_d'] = '';

if(isset($FORM['a']) && $FORM['a'] == 'admin') 
{
    // Do nothing
}
else 
{
    $result = $DB->query("SELECT id, code, views, max_views, display_zone, type FROM {$CONF['sql_prefix']}_osbanners WHERE active = 1 ORDER BY RAND()", __FILE__, __LINE__);
    while (list($id, $code, $views, $max_views, $display_zone, $type) = $DB->fetch_array($result)) 
    {
        $finalviews = 0;

        if($type == 'global')
        {
            $TMPL['zone_'.$display_zone] = $code;
            $finalviews = $views + 1;
	        $DB->query("UPDATE {$CONF['sql_prefix']}_osbanners SET views = {$finalviews} WHERE id = {$id}", __FILE__, __LINE__);
        }
        elseif($type == 'details' && isset($FORM['a']) && $FORM['a'] == 'details')
        { 
            $TMPL['zone_'.$display_zone] = $code;
            $finalviews = $views + 1;
	        $DB->query("UPDATE {$CONF['sql_prefix']}_osbanners SET views = {$finalviews} WHERE id = {$id}", __FILE__, __LINE__);
        }
        elseif($type == 'ad_break' && empty($FORM['a']))
        { 
            if ($display_zone == 'ad_break') {
                $TMPL['ad_break'][$id] = array($display_zone => $code);
            }
            elseif ($display_zone == 'ad_break_top') {
                $TMPL['ad_break_top'][$id] = array($display_zone => $code);
            }
        }

        if ($max_views > 1 && $finalviews >= $max_views) 
        {
		    $DB->query("UPDATE {$CONF['sql_prefix']}_osbanners SET active = 0 WHERE id = {$id}", __FILE__, __LINE__);
	    }
		
    }
}
