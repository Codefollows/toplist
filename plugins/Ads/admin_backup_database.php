
$backup_osbanners = array(
  "{$CONF['sql_prefix']}_osbanners" => true,
  "{$CONF['sql_prefix']}_osbanners_zones" => true
);
$tables_to_backup = array_merge($tables_to_backup, $backup_osbanners);
