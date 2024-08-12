<?php

// Default Skin
$DB->query("UPDATE {$CONF['sql_prefix']}_settings SET `default_skin` = 'default'", __FILE__, __LINE__);

// Screenshot API
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_settings ADD `visio_screen_api` VARCHAR(255) NULL", __FILE__, __LINE__);

// Clean Url
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_settings ADD `clean_url` TINYINT(1) NOT NULL DEFAULT 0", __FILE__, __LINE__);

// Newest Members
$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_settings`
	ADD `new_member_num` tinyint(10) unsigned default 5 NOT NULL,
	ADD `new_member_screen` tinyint(1) unsigned default 1 NOT NULL
", __FILE__, __LINE__);

// Premium Settings
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_settings
	ADD `premium_number` INT(10) NOT NULL DEFAULT 5,
	ADD `premium_order_by` tinyint(1) DEFAULT 1,
	ADD `currency_code` varchar(55) NOT NULL DEFAULT '',
	ADD `currency_symbol` varchar(55) NOT NULL DEFAULT '',
	ADD `one_w_price` DECIMAL(5, 2) NULL DEFAULT 0,
	ADD `discount_qty_01` SMALLINT NULL DEFAULT 0,
	ADD `discount_value_01` SMALLINT NULL DEFAULT 0,
	ADD `discount_qty_02` SMALLINT NULL DEFAULT 0,
	ADD `discount_value_02` SMALLINT NULL DEFAULT 0,
	ADD `discount_qty_03` SMALLINT NULL DEFAULT 0,
	ADD `discount_value_03` SMALLINT NULL DEFAULT 0,
	ADD `max_premium_banner_width` INT(4) NULL DEFAULT 0,
	ADD `max_premium_banner_height` INT(4) NULL DEFAULT 0,
	ADD `auto_approve_premium` TINYINT(1) NULL DEFAULT 0,
	ADD `new_day_boost` INT(11) NOT NULL DEFAULT 0,
	ADD `new_week_boost` INT(11) NOT NULL DEFAULT 0,
	ADD `new_month_boost` INT(11) NOT NULL DEFAULT 0
", __FILE__, __LINE__);

//RECAPTCHA
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_settings
	ADD `recaptcha` TINYINT(1) NULL DEFAULT 0,
	ADD `recaptcha_sitekey` VARCHAR(255) NULL DEFAULT '',
	ADD `recaptcha_secret` VARCHAR(255) NULL DEFAULT ''
", __FILE__, __LINE__);

$DB->query("ALTER TABLE {$CONF['sql_prefix']}_settings ADD `gateway_recaptcha` tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER recaptcha_sitekey", __FILE__, __LINE__);
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_settings ADD `usercp_recaptcha` tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER recaptcha_sitekey", __FILE__, __LINE__);
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_settings ADD `admin_recaptcha` tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER recaptcha_sitekey", __FILE__, __LINE__);
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_settings ADD `lostpw_recaptcha` tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER recaptcha_sitekey", __FILE__, __LINE__);



// Delete Inactive Sites, change to make them inactive
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_settings CHANGE `delete_after` `inactive_after` INT(5) DEFAULT 14", __FILE__, __LINE__);


//Prepare for SMTP
$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_settings` ADD `smtp_host` VARCHAR(255) default '' NOT NULL", __FILE__, __LINE__);
$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_settings` ADD `smtp_user` VARCHAR(255) default '' NOT NULL", __FILE__, __LINE__);
$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_settings` ADD `smtp_password` VARCHAR(255) default '' NOT NULL", __FILE__, __LINE__);
$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_settings` ADD `smtp_port` VARCHAR(50) default '' NOT NULL", __FILE__, __LINE__);

// Default php date timezone
$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_settings` ADD `time_zone` varchar(85) DEFAULT 'America/Los_Angeles'", __FILE__, __LINE__);

// Maintaince mode
$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_settings` ADD `maintenance_mode` TINYINT(1) NULL DEFAULT 0", __FILE__, __LINE__);

// 2Step security
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_settings
	ADD `2step` tinyint(1) unsigned NOT NULL DEFAULT 0,
	ADD `2step_secret` varchar(255) NOT NULL DEFAULT ''
", __FILE__, __LINE__);

// Create button_config.php and remove old settings
$button_file = "{$CONF['path']}/button_config.php";
if ($fh = @fopen($button_file, 'w')) {

	if ($CONF['ranks_on_buttons'] == 1) {
		$static_button = 0;
		$rank_button = 1;
		$stats_button = 0;
	}
	elseif ($CONF['ranks_on_buttons'] == 2) {
		$static_button = 0;
		$rank_button = 0;
		$stats_button = 1;
	}
	else {
		$static_button = 1;
		$rank_button = 0;
		$stats_button = 0;
	}

	// Set width/height for default banner, optionally mp4 default banner
	$default_banner_width  = 0;
	$default_banner_height = 0;
	$default_banner_mp4    = '';

	if (!empty($CONF['default_banner']))
	{
		// Hosted on list url or external sourse
		if (stripos($CONF['default_banner'], $CONF['list_url']) === 0 || ini_get('allow_url_fopen'))
		{
			$default_banner_size = getimagesize($CONF['default_banner']);

			if (!empty($default_banner_size))
			{
				$default_banner_width  = (int)$default_banner_size[0];
				$default_banner_height = (int)$default_banner_size[1];
			}
		}

		// gif to mp4 conversion using ffmpeg for smaller filesizes
		// Extra check if default banner is on this domain
		if (function_exists('shell_exec') && stripos($CONF['default_banner'], $CONF['list_url']) === 0)
		{
			$default_banner_extension = getExtension($CONF['default_banner']);
			$default_banner_extension = strtolower($default_banner_extension);

			// Force a file path search using "type".
			// "which" may be empty if you not modified $PATH
			// Returns filepath for ffmpeg, just some safecheck since ffmpeg alone may not always work on every system
			$ffmpeg_path = trim(shell_exec('type -P ffmpeg'));

			if (!empty($ffmpeg_path))
			{
				// We can convert gif. Png aint possible due transparency, jpg possible but many color jpg's get blurry due mp4 max 258 colors or so
				if ($default_banner_extension == 'gif')
				{
					// Remove list url + slash
					$ffmpeg_image_path = str_replace("{$CONF['list_url']}/", '', $CONF['default_banner']);

					// Get the filename without extension
					$ffmpeg_image = basename($ffmpeg_image_path, ".{$default_banner_extension}");

					// Remove slash + file from path
					$ffmpeg_image_path = str_replace("/{$ffmpeg_image}.{$default_banner_extension}", '', $ffmpeg_image_path);

					// mp4 url
					$default_banner_mp4 = "{$CONF['list_url']}/{$ffmpeg_image_path}/{$ffmpeg_image}.mp4";

					// actual convert command
					// (-y)                                      - Disable confirm promts when overwriting files
					// (-movflags faststart)                     - Make video playable and load quicker under html5
					// (-pix_fmt yuv420p)                        - Set pixel format. H.264 video is safe across browsers. If not available -pix_fmt auto picks best value for encoder in use
					// (-vf 'scale=trunc(iw/2)*2:trunc(ih/2)*2') - MP4 videos using H.264 need to have a dimensions that is divisible by 2. This option ensures that's the case for the filtergraph frame conversion
					// (</dev/null >/dev/null 2>&1)              - Redirect stdin/stdout/stderr into nothingness and make it a background process
					shell_exec("{$ffmpeg_path} -y -i \"{$CONF['path']}/{$ffmpeg_image_path}/{$ffmpeg_image}.{$default_banner_extension}\" -movflags faststart -pix_fmt yuv420p -preset veryslow -vf 'scale=trunc(iw/2)*2:trunc(ih/2)*2' \"{$CONF['path']}/{$ffmpeg_image_path}/{$ffmpeg_image}.mp4\" </dev/null >/dev/null 2>&1");
				}
			}
		}
	}

	$button_config = <<<EndHTML
<?php

\$CONF['count_pv'] = 0; //Count pageviews

\$CONF['text_link_button_alt'] = '{$CONF['list_name']}'; // Text Link Anchor, Alt for Buttons
\$CONF['text_link'] = 1; //Enable Text Link
\$CONF['static_button'] = {$static_button}; // Show Only Static Button
\$CONF['static_button_url'] = '';

\$CONF['rank_button'] = {$rank_button}; //Show buttons with rank 1.gif, 2.gif etc
\$CONF['default_rank_button'] = '{$CONF['button_url']}';
\$CONF['button_dir'] = '{$CONF['button_dir']}';
\$CONF['button_ext'] = '{$CONF['button_ext']}';
\$CONF['button_num'] = {$CONF['button_num']};

\$CONF['stats_button'] = {$stats_button}; //Show Dynamic Stats Button

\$CONF['hidden_button_url'] = '{$CONF['list_url']}/images/clear.png';

\$CONF['default_banner_mp4'] = '{$default_banner_mp4}';
\$CONF['default_banner_width'] = {$default_banner_width};
\$CONF['default_banner_height'] = {$default_banner_height};

?>
EndHTML;

	fwrite($fh, $button_config);
	fclose($fh);

	// Drop old button db settings
	$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_settings`
		DROP `ranks_on_buttons`,
		DROP `button_url`,
		DROP `button_dir`,
		DROP `button_ext`,
		DROP `button_num`
	", __FILE__, __LINE__);
}


// Payment system
$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_settings` ADD `payment_providers` TEXT NULL DEFAULT NULL", __FILE__, __LINE__);

require_once("{$CONF['path']}/sources/misc/Payment.php");
require_once("{$CONF['path']}/install/providers.php");

$Payment = new Payment();
$Payment->insertProviders($providers);
