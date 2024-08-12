<?php

$DB->query("DELETE FROM `{$CONF['sql_prefix']}_menu` WHERE `title` = '{$LNG['plugin_overall_stats']}'", __FILE__, __LINE__);

?>