<?php

// Let session type hold more text
$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_sessions` CHANGE `type` `type` VARCHAR(255) NOT NULL DEFAULT ''", __FILE__, __LINE__);

// Remember me login
$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_sessions` ADD `keep_alive` tinyint(1) unsigned NOT NULL DEFAULT 0", __FILE__, __LINE__);
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_sessions ADD INDEX `delete_query` (`time`, `keep_alive`)", __FILE__, __LINE__);
