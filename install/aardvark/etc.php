<?php

// VL Version
$DB->query("UPDATE {$CONF['sql_prefix']}_etc SET `version` = '{$new_version}'", __FILE__, __LINE__);