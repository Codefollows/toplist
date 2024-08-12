<?php

// Installed allready?
$result = $DB->query("SHOW COLUMNS FROM {$CONF['sql_prefix']}_settings LIKE 'new_member_num'", __FILE__, __LINE__);

if(!$DB->num_rows($result)) {
	
   $DB->query("ALTER TABLE `{$CONF['sql_prefix']}_settings`
		ADD `new_member_num` tinyint(10) unsigned default 5 NOT NULL,
		ADD `new_member_screen` tinyint(1) unsigned default 1 NOT NULL
	", __FILE__, __LINE__);

} 
else {
   	$already = $LNG['a_plugins_installed_allready'];
}

?>