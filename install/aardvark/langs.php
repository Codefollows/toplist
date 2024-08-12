<?php

$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_langs` (
	`phrase_id` int(10) NOT NULL auto_increment,
	`language` varchar(150) NOT NULL,
	`definition` text NOT NULL,
	`phrase_name` varchar(100) NOT NULL,
	PRIMARY KEY (`phrase_id`),
	UNIQUE (`language`, `phrase_name`)
)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);

// Rebuild Only english language file. Translated phrases are later imported via admin
foreach ($LNG as $key => $value) {
	$key = $DB->escape("$key",1);
	$value = $DB->escape("$value",1);
	$DB->query("INSERT IGNORE INTO {$CONF['sql_prefix']}_langs SET phrase_name = '{$key}', definition = '{$value}', language = 'english'", __FILE__, __LINE__);
}

$output = '';
$result = $DB->query("SELECT phrase_name, definition FROM {$CONF['sql_prefix']}_langs WHERE language = 'english' ORDER BY phrase_name ASC", __FILE__, __LINE__);
while (list($phrase_name, $definition) = $DB->fetch_array($result)) 
{
	$definition = stripslashes($definition);
	$definition = str_replace('\"', '"', addslashes($definition));
	$output .= "\$LNG['{$phrase_name}'] = '$definition';\n";
}

$file = "./../languages/english.php";
if ($fh = @fopen($file, 'w')) {
	$lang_output = <<<EndHTML
<?php

if (!defined('VISIOLIST')) {
die("This file cannot be accessed directly.");
}

$output
?>
EndHTML;

	fwrite($fh, $lang_output);
	fclose($fh);
}