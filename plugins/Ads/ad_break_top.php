
if (isset($TMPL['ad_break_top'])) 
{
    // Random ad array
    // it holds display zone and code as array key => value
    $ad_break_id = array_rand($TMPL['ad_break_top']);

    // Get the display zone of ad_break_id
    $ad_zone = key($TMPL['ad_break_top'][$ad_break_id]);

    // Construct tmpl tag
    $TMPL['zone_'.$ad_zone] = $TMPL['ad_break_top'][$ad_break_id][$ad_zone];

    // Push used ad id into an array to update views once all ads are displayed
    $ad_id_array[] = $ad_break_id;
}
