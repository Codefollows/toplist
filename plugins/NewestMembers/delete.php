<?php

$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_settings`
	DROP `new_member_num`,
	DROP `new_member_screen`
", __FILE__, __LINE__);

?>