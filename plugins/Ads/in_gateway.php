
$result = $DB->query("SELECT * FROM {$CONF['sql_prefix']}_osbanners WHERE type = 'in' AND active = 1 ORDER BY RAND()", __FILE__, __LINE__);
while ($row = $DB->fetch_array($result)) 
{
    $TMPL['zone_'.$row['display_zone']] = $row['code'];

    $finalviews = $row['views'] + 1;
	$DB->query("UPDATE {$CONF['sql_prefix']}_osbanners SET views = {$finalviews} WHERE id = {$row['id']}", __FILE__, __LINE__);

    if ($row['max_views'] > 1 && $finalviews >= $row['max_views']) 
    {
		$DB->query("UPDATE {$CONF['sql_prefix']}_osbanners SET active = 0 WHERE id = {$row['id']}", __FILE__, __LINE__);
	}
		
}
