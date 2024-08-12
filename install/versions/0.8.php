<?php

// Different language updates
$IMPORT['a_menu_inactive'] = "Manage Inactive Members";
$IMPORT['a_menu_manage_languages'] = "Manage Languages";
$IMPORT['a_skins_edit_child'] = "Edit Child";
$IMPORT['a_man_skins_header'] = "Edit Template";
$IMPORT['a_man_skins_child'] = "Edit Child Template";
$IMPORT['a_man_skins_copy_file'] = "Select Template to Copy";
$IMPORT['a_man_skins_copy_file_warning'] = "This will copy/overwrite parent template file into your child folder";
$IMPORT['a_man_skins_save_file'] = "Save Template";
$IMPORT['a_man_skins_not_writable'] = "The file is not writable or does not exist";

$DB->query("UPDATE {$CONF['sql_prefix']}_langs SET definition = 'All Sites' WHERE phrase_name = 'main_all' AND language = 'english'", __FILE__, __LINE__);
