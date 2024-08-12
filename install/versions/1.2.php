<?php

$IMPORT['a_s_time_zone'] = 'Your Time Zone';

$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_settings` ADD `time_zone` varchar(85) DEFAULT 'America/Los_Angeles'", __FILE__, __LINE__);
