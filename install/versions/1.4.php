<?php

// New language
$IMPORT['user_cp_banner_no_image'] = 'No Image selected!';
$IMPORT['g_invalid_category'] = 'Invalid Category. Please try again.';
$IMPORT['join_recaptcha_text'] = 'Spam Check';
$IMPORT['join_error_recaptcha'] = 'Please prove that you are a real person and not a bot.';
$IMPORT['a_s_paypal_sandbox'] = 'PayPal sandbox mode';
$IMPORT['a_s_maintenance_mode'] = 'Maintenance mode';
$IMPORT['maintenance_header'] = 'Maintenance mode';
$IMPORT['maintenance_info'] = 'We are temporarily unavailable. Please try back later.';

// Changed language
$DB->query("UPDATE {$CONF['sql_prefix']}_langs SET phrase_name = 'a_s_recaptcha_sitekey', definition = 'Site Key' WHERE phrase_name = 'a_s_recaptcha_pub_key' AND language = 'english'", __FILE__, __LINE__);
$DB->query("UPDATE {$CONF['sql_prefix']}_langs SET phrase_name = 'a_s_recaptcha_secret', definition = 'Secret' WHERE phrase_name = 'a_s_recaptcha_priv_key' AND language = 'english'", __FILE__, __LINE__);

// Delete lng phrases
// Also unset the auto generated imports so they not inserted again!
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'a_manage_add_banner'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'a_manage_banners'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'a_choose_zone'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'a_banner_updated'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'a_banner_deleted'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'a_banner_activated'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'a_banner_deactivated'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'g_zone'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'a_banner_name'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'g_banner_views'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'g_banner_max_views'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'a_banner_unlimited'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'a_banner_code'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'a_banner_activate'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'a_banner_deactivate'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'a_banner_deactivate_number'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'a_banner_added'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'g_zone_a'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'g_zone_b'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'g_zone_c'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'g_zone_d'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'g_zone_site_wide'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'fuck'", __FILE__, __LINE__);

if (isset($IMPORT['a_manage_add_banner'])) { unset($IMPORT['a_manage_add_banner']); }
if (isset($IMPORT['a_manage_banners'])) { unset($IMPORT['a_manage_banners']); }
if (isset($IMPORT['a_choose_zone'])) { unset($IMPORT['a_choose_zone']); }
if (isset($IMPORT['a_banner_updated'])) { unset($IMPORT['a_banner_updated']); }
if (isset($IMPORT['a_banner_deleted'])) { unset($IMPORT['a_banner_deleted']); }
if (isset($IMPORT['a_banner_activated'])) { unset($IMPORT['a_banner_activated']); }
if (isset($IMPORT['a_banner_deactivated'])) { unset($IMPORT['a_banner_deactivated']); }
if (isset($IMPORT['g_zone'])) { unset($IMPORT['g_zone']); }
if (isset($IMPORT['a_banner_name'])) { unset($IMPORT['a_banner_name']); }
if (isset($IMPORT['g_banner_views'])) { unset($IMPORT['g_banner_views']); }
if (isset($IMPORT['g_banner_max_views'])) { unset($IMPORT['g_banner_max_views']); }
if (isset($IMPORT['a_banner_unlimited'])) { unset($IMPORT['a_banner_unlimited']); }
if (isset($IMPORT['a_banner_code'])) { unset($IMPORT['a_banner_code']); }
if (isset($IMPORT['a_banner_activate'])) { unset($IMPORT['a_banner_activate']); }
if (isset($IMPORT['a_banner_deactivate'])) { unset($IMPORT['a_banner_deactivate']); }
if (isset($IMPORT['a_banner_deactivate_number'])) { unset($IMPORT['a_banner_deactivate_number']); }
if (isset($IMPORT['a_banner_added'])) { unset($IMPORT['a_banner_added']); }
if (isset($IMPORT['g_zone_a'])) { unset($IMPORT['g_zone_a']); }
if (isset($IMPORT['g_zone_b'])) { unset($IMPORT['g_zone_b']); }
if (isset($IMPORT['g_zone_c'])) { unset($IMPORT['g_zone_c']); }
if (isset($IMPORT['g_zone_d'])) { unset($IMPORT['g_zone_d']); }
if (isset($IMPORT['g_zone_site_wide'])) { unset($IMPORT['g_zone_site_wide']); }
if (isset($IMPORT['fuck'])) { unset($IMPORT['fuck']); }



// Change join fields key and values to text so they hold more than 255 chars
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_join_fields CHANGE `field_choice_value` `field_choice_value` TEXT NOT NULL", __FILE__, __LINE__);
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_join_fields CHANGE `field_choice_text` `field_choice_text` TEXT NOT NULL", __FILE__, __LINE__);

// Rename recaptcha config
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_settings CHANGE `recaptcha_public` `recaptcha_sitekey` VARCHAR(255) NULL DEFAULT ''", __FILE__, __LINE__);
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_settings CHANGE `recaptcha_private` `recaptcha_secret` VARCHAR(255) NULL DEFAULT ''", __FILE__, __LINE__);

// Paypal sandbox
$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_settings` ADD `paypal_sandbox` TINYINT(1) NULL DEFAULT 0", __FILE__, __LINE__);

// Maintenance mode
$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_settings` ADD `maintenance_mode` TINYINT(1) NULL DEFAULT 0", __FILE__, __LINE__);
