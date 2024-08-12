
if (isset($ad_id_array) && (isset($TMPL['ad_break']) || isset($TMPL['ad_break_top']))) 
{
    $ad_break_ids = implode(',', $ad_id_array);
    $DB->query("UPDATE {$CONF['sql_prefix']}_osbanners SET views = views + 1 WHERE id IN({$ad_break_ids})", __FILE__, __LINE__);
}
