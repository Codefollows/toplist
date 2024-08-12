<?php

$result = $DB->query("SHOW TABLES", __FILE__, __LINE__);
while($tables = $DB->fetch_array($result)) {
	foreach ($tables as $key => $value) {
		$DB->query("ALTER TABLE {$value} CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);
	}
}
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_settings
			ADD `new_day_boost` INT(11) NOT NULL DEFAULT 0,
			ADD `new_week_boost` INT(11) NOT NULL DEFAULT 0,
			ADD `new_month_boost` INT(11) NOT NULL DEFAULT 0,
			ADD `adscaptcha` TINYINT(1) NULL DEFAULT 0,
			ADD `adscaptcha_id` VARCHAR(255) NULL DEFAULT 0,
			ADD `adscaptcha_public` VARCHAR(255) NULL DEFAULT 0,
			ADD `adscaptcha_private` VARCHAR(255) NULL DEFAULT 0
", __FILE__, __LINE__);

// Delete Inactive Sites, change to make them inactive
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_settings CHANGE `delete_after` `inactive_after` INT(5) DEFAULT 14", __FILE__, __LINE__);
