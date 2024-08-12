<?php

$DB->query("ALTER TABLE {$CONF['sql_prefix']}_ip_log DROP PRIMARY KEY", __FILE__, __LINE__);
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_settings ADD `premium_order_by` tinyint(1) default 1", __FILE__, __LINE__);
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_settings ADD `description_length` int(5) default 255", __FILE__, __LINE__);
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_etc DROP `original_version`", __FILE__, __LINE__);

// Different language updates
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_langs ADD UNIQUE (`language`, `phrase_name`)", __FILE__, __LINE__);

$IMPORT['a_s_premium_sidebar'] = "List Premium Members in your Sidebar";
$IMPORT['a_s_premium_order_by'] = "Order By";
$IMPORT['a_s_premium_start_date'] = "Premium Start Date";
$IMPORT['g_random'] = "Random";
$IMPORT['error_writing_language'] = "Error Writing Language";
$IMPORT['phrase_added'] = "Phrase Added!";
$IMPORT['a_edit_phrase_edited'] = "Phrase Updated!";
$IMPORT['a_edit_phrase_header'] = "Edit Phrase";
$IMPORT['a_man_languages_header'] = "Manage Languages";
$IMPORT['destroy_language'] = "Destroy Language";
$IMPORT['language_removed'] = "Language Removed!";
$IMPORT['language_warning'] = "Warning! this will remove all phrases from this language!";
$IMPORT['phrase_name'] = "Phrase Name";
$IMPORT['language'] = "Language";
$IMPORT['defintion'] = "Definiton";
$IMPORT['add_new'] = "Add New Phrase";
$IMPORT['import_language'] = "Import Language";
$IMPORT['language_imported'] = "Language Imported!";
$IMPORT['language_remove_english'] = "You cannot delete the core language!";
$IMPORT['a_s_description_length'] = "Member description length: Default - 255 characters";
$IMPORT['install_import'] = "Please Import english (required) + any other language (optional)";