<?php

// Meta keywords, description
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_custom_pages
	ADD `keywords` varchar(255) default '',
	ADD `description` varchar(320) default ''
", __FILE__, __LINE__);