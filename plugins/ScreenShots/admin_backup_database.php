
$backup_screens = array(
  "{$CONF['sql_prefix']}_screens" => true
);
$tables_to_backup = array_merge($tables_to_backup, $backup_screens);
