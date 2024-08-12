<?php

$result = $DB->query("SHOW TABLES LIKE '{$CONF['sql_prefix']}_osbanners'", __FILE__, __LINE__);
$result_update = $DB->query("SHOW TABLES LIKE '{$CONF['sql_prefix']}_osbanners_zones'", __FILE__, __LINE__);

// Not Installed
if(!$DB->num_rows($result)) {

    $DB->query("CREATE TABLE `{$CONF['sql_prefix']}_osbanners` (
        `id` BIGINT unsigned NOT NULL AUTO_INCREMENT,
        `code` TEXT,
        `name` TEXT, 
        `display_zone` TEXT,
        `active` tinyint(1) unsigned default 1 NOT NULL, 
        `views` int(10) unsigned default 0 NOT NULL, 
        `max_views` int(10) unsigned default 0 NOT NULL, 
        `type` varchar(255) default 'global' NOT NULL, 
        PRIMARY KEY (`id`)
    )CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);

    $DB->query("CREATE TABLE `{$CONF['sql_prefix']}_osbanners_zones` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `zone` VARCHAR(255) default '' NOT NULL, 
        `type` VARCHAR(255) default 'global' NOT NULL, 
        PRIMARY KEY (`id`)
    )CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);
	
    // Defaults
    $zones = array(
        'a' => 'global|Global', 
        'b' => 'global|Global', 
        'c' => 'global|Global', 
        'd' => 'details|Details Page'
    ); 
    foreach ($zones as $zone => $type) {
        $DB->query("INSERT INTO `{$CONF['sql_prefix']}_osbanners_zones` (`zone`, `type`) VALUES ('{$zone}', '{$type}')", __FILE__, __LINE__);
    }
				  
} 

// Installed -> Update 1
elseif(!$DB->num_rows($result_update)) {

    $DB->query("CREATE TABLE `{$CONF['sql_prefix']}_osbanners_zones` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `zone` VARCHAR(255) default '' NOT NULL, 
        `type` VARCHAR(255) default 'global' NOT NULL, 
        PRIMARY KEY (`id`)
    )CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);
	
    $DB->query("ALTER TABLE `{$CONF['sql_prefix']}_osbanners` ADD `type` varchar(255) default 'global' NOT NULL", __FILE__, __LINE__);

    // Defaults
    $zones = array(
        'a' => 'global|Global', 
        'b' => 'global|Global', 
        'c' => 'global|Global', 
        'd' => 'details|Details Page'
    ); 
    foreach ($zones as $zone => $type) {
        $DB->query("INSERT INTO `{$CONF['sql_prefix']}_osbanners_zones` (`zone`, `type`) VALUES ('{$zone}', '{$type}')", __FILE__, __LINE__);
    }

    $tr = $DB->query("SELECT id, display_zone FROM {$CONF['sql_prefix']}_osbanners", __FILE__, __LINE__);
    while (list($id, $display_zone) = $DB->fetch_array($tr)) {
        if (array_key_exists($display_zone, $zones)) {
            list($type, $type_display) = explode('|', $zones[$display_zone]);
	        $DB->query("UPDATE {$CONF['sql_prefix']}_osbanners SET type = '{$type}' WHERE id = {$id}", __FILE__, __LINE__);
        }
    }

}

else {
   	$already = $LNG['a_plugins_installed_allready'];
}

?>