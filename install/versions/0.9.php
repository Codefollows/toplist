<?php

// Different language updates
$IMPORT['a_menu_manage_join'] = "Manage Join Fields";
$IMPORT['link_code_text_link'] = 'Preview:';
$IMPORT['link_code_static_button'] = 'Preview:';
$IMPORT['link_code_rank_button'] = 'Preview:';
$IMPORT['link_code_stats_button'] = 'Preview:';
$IMPORT['a_plugins_update_check'] = 'Check For Updates';
$IMPORT['a_plugins_update_to'] = 'Update To';
$IMPORT['a_email_inactive_days'] = 'Email members who are inactive for more than how many days. (leave blank to email all members): ';
$IMPORT['a_email_throttle'] = 'Send 1 Email Every How Many Seconds:';
$IMPORT['a_email_exportable'] = 'Exportable Mail List';
$IMPORT['validate_required'] = 'This field is required';
$IMPORT['validate_preg_match'] = 'It appears this field does not match the requirements';
$IMPORT['validate_number'] = 'Only plain numbers / decimal numbers allowed';
$IMPORT['validate_min_chars'] = 'Minimum of %d characters required';
$IMPORT['validate_max_chars'] = 'Maximum of %d characters allowed';
$IMPORT['validate_range_chars'] = 'Value must be between %d and %d characters';
$IMPORT['validate_ip'] = 'No valid IP';
$IMPORT['validate_db_duplicate'] = 'Column already exist. Please choose another';
$IMPORT['a_man_jf_header'] = 'Manage Join Fields';
$IMPORT['a_man_jf_delete_msg'] = 'Your join field has been deleted';
$IMPORT['a_man_jf_addnew'] = 'Add New Field';
$IMPORT['a_man_jf_website'] = 'Website Fieldset';
$IMPORT['a_man_jf_user'] = 'User Fieldset';
$IMPORT['g_type'] = 'Type';
$IMPORT['a_man_jf_textbox'] = 'Single line textbox';
$IMPORT['a_man_jf_textarea'] = 'Textarea';
$IMPORT['a_man_jf_dropdown'] = 'Dropdown select';
$IMPORT['a_man_jf_checkbox'] = 'Checkboxes';
$IMPORT['a_man_jf_radio'] = 'Radio buttons';
$IMPORT['a_edit_jf_header'] = 'Edit Join Field';
$IMPORT['a_edit_jf_error'] = 'The field you are trying to delete does not exist';
$IMPORT['a_man_jf_none'] = 'None';
$IMPORT['a_man_jf_url'] = 'Url / IP';
$IMPORT['a_man_jf_number'] = 'Number only';
$IMPORT['a_man_jf_az_09'] = 'a-z, 0-9, and -_ only';
$IMPORT['a_man_jf_none1'] = 'No input length check';
$IMPORT['a_man_jf_min'] = 'Min. only';
$IMPORT['a_man_jf_max'] = 'Max. only';
$IMPORT['a_man_jf_range'] = 'Range between min, max';
$IMPORT['a_man_jf_remove'] = 'Remove';
$IMPORT['a_man_jf_displaytext'] = 'Display Text';
$IMPORT['a_man_jf_value'] = 'Value ( a-z, 0-9, and _ only )';
$IMPORT['a_man_jf_tab1'] = 'General Information';
$IMPORT['a_man_jf_tab2'] = 'Text Field Options';
$IMPORT['a_man_jf_tab3'] = 'Choice Field Options';
$IMPORT['a_man_jf_tab4'] = 'Display Options';
$IMPORT['a_man_jf_field_id'] = 'Field ID';
$IMPORT['a_man_jf_field_id_info'] = 'Unique Identifier. Lowercase "a-z", "0-9", and "_" only. Max 30 chars. Cant be changed once set';
$IMPORT['a_man_jf_label_text'] = 'Label Text';
$IMPORT['a_man_jf_label_text_info'] = 'Information Text above the field on Join/Edit';
$IMPORT['a_man_jf_label_desc_info'] = 'Visible to admin only';
$IMPORT['a_man_jf_field_type'] = 'Field Type';
$IMPORT['a_man_jf_required'] = 'Required Field?';
$IMPORT['a_man_jf_requirements'] = 'Requirements for Single Line Textboxes';
$IMPORT['a_man_jf_char_requirements'] = 'Min, Max or Range Chars of text field ( textbox and textarea )';
$IMPORT['a_man_jf_char_min'] = 'Min. Chars';
$IMPORT['a_man_jf_char_max'] = 'Max. Chars';
$IMPORT['a_man_jf_char_range'] = 'Range: eg. 6-50';
$IMPORT['a_man_jf_not_set'] = 'Not set';
$IMPORT['a_man_jf_input_size'] = 'Input Size: Controls width of box, default - 50';
$IMPORT['a_man_jf_add_choice'] = 'Add more choices';
$IMPORT['a_man_jf_add_choice_h3'] = 'Add Choices for Dropdown Selection, Checkboxes, Radio Buttons';
$IMPORT['a_man_jf_add_choice_info'] = 'Left Box = Internal Value :: Right Box = Visible Text';
$IMPORT['a_man_jf_display_h3'] = 'Add your Site / Edit Site Form - Display Options';
$IMPORT['a_man_jf_display_info'] = 'Display Location In the public join form. Has no effect on edit, join_existing form in user control panel.';
$IMPORT['a_man_jf_show_on_join'] = 'Display on Join and Edit Form? Checked = Both - Unchecked = Edit form only';
$IMPORT['a_man_jf_edit'] = 'Edit Field';
$IMPORT['a_man_jf_add_success'] = 'Your new field has been added';
$IMPORT['a_man_jf_edit_success'] = 'Your field has been updated';


$DB->query("ALTER TABLE {$CONF['sql_prefix']}_custom_pages
			ADD `keywords` varchar(255) default '',
			ADD `description` varchar(255) default ''
		 ", __FILE__, __LINE__);

$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_join_fields` (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`field_id` varchar(150) NOT NULL,
			`label_text` varchar(255) NOT NULL,
			`description` text NOT NULL,
			`field_type` varchar(10) NOT NULL DEFAULT 'textbox',
			`required` tinyint(1) unsigned NOT NULL DEFAULT '0',
			`field_text_requirements` varchar(6) NOT NULL DEFAULT 'none',
			`field_text_enable_length` varchar(5) NOT NULL DEFAULT 'none',
			`field_text_length` varchar(100) NOT NULL DEFAULT '',
			`field_text_input_size` tinyint(1) unsigned NOT NULL DEFAULT '50',
			`field_choice_value` varchar(255) NOT NULL,
			`field_choice_text` varchar(255) NOT NULL,
			`display_location` varchar(7) NOT NULL DEFAULT 'website',
			`show_join_edit` tinyint(1) NOT NULL DEFAULT '1',
			`field_sort` tinyint(1) unsigned NOT NULL DEFAULT '255',
			PRIMARY KEY (`id`)
		)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);