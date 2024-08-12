
$DB->query("UPDATE `{$CONF['sql_prefix']}_settings` SET 
	`new_member_screen` = {$TMPL['new_member_screen']},
	`new_member_num` = {$TMPL['new_member_num']}
", __FILE__, __LINE__);  
