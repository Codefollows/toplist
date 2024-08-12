<?php

$IMPORT['a_email_method_gt'] = 'Greater than';
$IMPORT['a_email_method_gte'] = 'Greater than or equal to';
$IMPORT['a_email_method_lt'] = 'Lower than';
$IMPORT['a_email_method_lte'] = 'Lower than or equal to';
$IMPORT['a_email_method_eq'] = 'Equal to';

$DB->query("ALTER TABLE {$CONF['sql_prefix']}_settings DROP `description_length`", __FILE__, __LINE__);
