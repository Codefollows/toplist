<?php

$result = $DB->query("SELECT `title` FROM `{$CONF['sql_prefix']}_menu` WHERE `title` = '{$LNG['plugin_overall_stats']}'", __FILE__, __LINE__);

if(!$DB->num_rows($result)) {

	$DB->query("INSERT INTO `{$CONF['sql_prefix']}_menu` (menu_id, title, path, sort) VALUES (1, '{$LNG['plugin_overall_stats']}', '{$CONF['list_url']}/?a=details', 1)", __FILE__, __LINE__);
} 
else {
	$already = $LNG['a_plugins_installed_allready'];
}

?>