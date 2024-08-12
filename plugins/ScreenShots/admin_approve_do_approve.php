
if (!empty($CONF['visio_screen_api'])) {  
    $DB->query("INSERT INTO {$CONF['sql_prefix']}_screens (requested_url, requested_time, username) VALUES ('{$TMPL['url']}', '{$join_date}', '{$TMPL['username']}')", __FILE__, __LINE__);
}
