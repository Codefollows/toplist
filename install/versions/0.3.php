<?php

$result = $DB->query("SELECT username FROM {$CONF['sql_prefix']}_sites", __FILE__, __LINE__);
while (list($newowner) = $DB->fetch_array($result)) 
{
	$DB->query("UPDATE {$CONF['sql_prefix']}_sites SET owner = '{$newowner}' WHERE  username = '{$newowner}'", __FILE__, __LINE__);
	
	$TMPL['upgrade'] .= "{$LNG['upgrade_added_ownership']} {$newowner}.<br />";
}