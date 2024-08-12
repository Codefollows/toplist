<?php

$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_settings` DROP `visio_screen_api`", __FILE__, __LINE__);
$DB->query("DROP TABLE `{$CONF['sql_prefix']}_screens`", __FILE__, __LINE__);

?>