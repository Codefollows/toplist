<?php

// Fix issues with default values for database strict mode
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_reviews CHANGE `date` `date` DATETIME NOT NULL", __FILE__, __LINE__);
