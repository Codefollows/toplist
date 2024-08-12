<?php

// Different language updates
$IMPORT['a_edit_pending'] = "Pending";
$IMPORT['a_man_jf_internal'] = " ( internal value )";
$IMPORT['a_man_jf_display_value'] = "Template Tag ( display value ):";
$IMPORT['g_visit'] = "Visit";
$IMPORT['g_invalid_page'] = "Invalid page. Please try again.";

$IMPORT['a_s_smtp_host'] = "SMTP Host";
$IMPORT['a_s_smtp_password'] = "SMTP Password";
$IMPORT['a_s_smtp_user'] = "SMTP User";
$IMPORT['a_s_smtp_port'] = "SMTP Port";

$DB->query("UPDATE {$CONF['sql_prefix']}_langs SET definition = 'Google-friendly links' WHERE phrase_name = 'a_s_google_friendly_links' AND language = 'english'", __FILE__, __LINE__);
$DB->query("UPDATE {$CONF['sql_prefix']}_langs SET definition = 'Show ads after these ranks (separate with commas)' WHERE phrase_name = 'a_s_ad_breaks' AND language = 'english'", __FILE__, __LINE__);
$DB->query("UPDATE {$CONF['sql_prefix']}_langs SET definition = 'Security question and answer to block spammers' WHERE phrase_name = 'a_s_security_question' AND language = 'english'", __FILE__, __LINE__);

//Prepare for SMTP
$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_settings` ADD `smtp_host` VARCHAR(255) default '' NOT NULL", __FILE__, __LINE__);
$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_settings` ADD `smtp_user` VARCHAR(255) default '' NOT NULL", __FILE__, __LINE__);
$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_settings` ADD `smtp_password` VARCHAR(255) default '' NOT NULL", __FILE__, __LINE__);
$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_settings` ADD `smtp_port` VARCHAR(50) default '' NOT NULL", __FILE__, __LINE__);

// Custom join forms, field_choice_text as tmpl tag update
$result = $DB->query("SELECT * FROM {$CONF['sql_prefix']}_join_fields WHERE `field_type` = 'dropdown' OR `field_type` = 'radio' OR `field_type` = 'checkbox'", __FILE__, __LINE__);
while($row = $DB->fetch_array($result)) {

	// First off, create the column
	$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_sites` ADD `{$row['field_id']}_display` VARCHAR(255) default '' NOT NULL", __FILE__, __LINE__);

	// Now query members where the field_id is not empty
	$result1 = $DB->query("SELECT `username`, `{$row['field_id']}`, `{$row['field_id']}_display` FROM {$CONF['sql_prefix']}_sites WHERE `{$row['field_id']}` != ''", __FILE__, __LINE__);
	while($row1 = $DB->fetch_array($result1)) {

		// Create array out of the comma lists
		$field_choice_value_array  = explode(', ', $row['field_choice_value']);
		$field_choice_text_array   = explode(', ', $row['field_choice_text']);

		// get key number from selected value of the internal value array $field_choice_value_array
		$field_choice_key = array_search($row1[$row['field_id']], $field_choice_value_array);

		// Get the display text value using $field_choice_key for $field_choice_text_array
		$field_choice_val = $DB->escape($field_choice_text_array[$field_choice_key], 1);

		// For checkboxes
		if($row['field_type'] == 'checkbox') {
			$checkbox_array = explode(', ', $row1[$row['field_id']]);
			$field_choice_temp = array();
			foreach($checkbox_array as $checked) {
				$field_choice_key = array_search($checked, $field_choice_value_array);
				$field_choice_temp[] = $field_choice_text_array[$field_choice_key];
			}
			$field_choice_val = $DB->escape(implode(', ', $field_choice_temp), 1);
		}

		$DB->query("UPDATE {$CONF['sql_prefix']}_sites SET `{$row['field_id']}_display` = '{$field_choice_val}' WHERE username = '{$row1['username']}'", __FILE__, __LINE__);

	}

}