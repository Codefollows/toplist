<?php

// Help prevent register_globals injection
define('VISIOLIST', 1);
$CONF = array();
$FORM = array();
$TMPL = array();
$CONF['debug'] = '';
// Set encoding for multi-byte string functions
mb_internal_encoding("UTF-8");

// Change the path to your full path if necessary
$CONF['path'] = './..';

// Provide a user ip fix in case site uses cloudflare - previously a plugin
$_SERVER['REMOTE_ADDR'] = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : $_SERVER['REMOTE_ADDR'];

// Connect to the database
require_once("{$CONF['path']}/settings_sql.php");
require_once("{$CONF['path']}/sources/sql/{$CONF['sql']}.php");
$DB = "sql_{$CONF['sql']}";
$DB = new $DB;
$DB->connect($CONF['sql_host'], $CONF['sql_username'], $CONF['sql_password'], $CONF['sql_database'], $CONF['debug']);

// Settings
$settings = $DB->fetch("SELECT * FROM {$CONF['sql_prefix']}_settings", __FILE__, __LINE__);
$CONF = array_merge($CONF, $settings);

/* Make sure the site uses
** www. or non-www domain
** http or https
** As set in settings
*/
if ($CONF['list_url'] != 'http://localhost')
{
    $canonical_domain_info = parse_url($CONF['list_url']);
    $visitor_scheme        = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';

    if ($_SERVER['HTTP_HOST'] != $canonical_domain_info['host'] || $visitor_scheme != $canonical_domain_info['scheme'])
    {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: '.$canonical_domain_info['scheme'].'://'.$canonical_domain_info['host'].$_SERVER['REQUEST_URI']);
        exit;
    }
}

// Language
$LNG['charset'] = "utf-8";
require_once("{$CONF['path']}/languages/english.php");
require_once("{$CONF['path']}/languages/{$CONF['default_language']}.php");

// Session
require_once("{$CONF['path']}/sources/misc/session.php");
$session = new session;

// Drop possible sessions or redirect to admin if exist
if (isset($_COOKIE['atsphp_sid_admin'])) {

	list($type, $data) = $session->get($_COOKIE['atsphp_sid_admin']);

	if ($type == 'admin') {
		header("Location: {$CONF['list_url']}/index.php?a=admin");
		exit;
	}
	else {
		$session->delete($_COOKIE['atsphp_sid_admin'], 'atsphp_sid_admin');
	}
}

if (!empty($_GET['bf'])) {

	// If blocked by bruteforce
	$error = $LNG['g_invalid_u_or_p_bfd'];
}
elseif (!empty($_GET['recaptcha'])) {

	// wrong recaptcha
	$error = $LNG['join_error_recaptcha'];
}
elseif (!empty($_GET['fail'])) {

	// If fail ( wrong pass ), default error
	$error = $LNG['g_invalid_p'];

	// if fail, email sid expired or google wrong code
	if (isset($_COOKIE['atsphp_sid_admin_2step'])) {

		if ($CONF['2step'] == 1) {

			// On email sid, delete 2step delete db session + cookie
			$session->delete($_COOKIE['atsphp_sid_admin_2step'], 'atsphp_sid_admin_2step');

			$error = $LNG['g_session_expired'];
		}
		elseif ($CONF['2step'] == 2) {

			// On google error, just error
			$error = $LNG['2step_google_invalid'];
		}
	}
}
elseif (isset($_COOKIE['atsphp_sid_admin_2step'])) {

	// 2step session verification to determine if we still need to display email msg or google form
	list($type, $data) = $session->get($_COOKIE['atsphp_sid_admin_2step']);

	if ($type === 'admin_2step') {
		// Do nothing
	}
	else {

		// Delete expired db session + cookie
		$session->delete($_COOKIE['atsphp_sid_admin_2step'], 'atsphp_sid_admin_2step');

		$error = $LNG['g_session_expired'];
	}
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="author" content="Mark Artyniuk" />

	<title><?php echo "{$LNG['a_title']}"; ?></title>


    <script type="text/javascript" src="../skins/admin/jquery.js"></script>

<style>
body {
	background: #454545;
	text-align: center;
	font: 16px trebuchet ms, sans-serif;
	color: #ccc;
	margin: 0;
	padding: 0;
}
a {
	color: #ccc;
}
a:hover {
	color: #f2f2f2;
}
input {
	border: 2px solid #ccc;
	background: #f2f2f2;
	-webkit-border-radius: 5px;
	-moz-border-radius: 5px;
	border-radius: 5px;
	padding: 4px;
	color: #666;
	}
</style>
</head>

<body>

<img src="../skins/admin/images/logo.png" alt="" style="margin: 200px 0 30px 0;" id="visio"/>
<br />



<?php
	// Errors
	if (!empty($error)) {

		echo "{$error}<br />";
		echo "<a href=\"{$CONF['list_url']}/admin/\">{$LNG['a_title']}</a>";
	}
	elseif (isset($_COOKIE['atsphp_sid_admin_2step'])) {

		if ($CONF['2step'] == 1) {

			echo $LNG['2step_email_confirm'];
		}
		elseif ($CONF['2step'] == 2) {
?>
			<form action="<?php echo $CONF['list_url']; ?>/index.php?a=admin" method="post" id="myform">
				<label>
					<?php echo $LNG['2step_google_label']; ?>
					<input type="text" name="2step_validate" size="15" value="" />
				</label>

				<input type="submit" value="<?php echo $LNG['2step_google_confirm']; ?>" id="go" />
			</form>
<?php
		}
	}
	else {
?>
		<form action="<?php echo $CONF['list_url']; ?>/index.php?a=admin" method="post" id="myform">

			<label for="username" style="display: none;">
				<?php echo "{$LNG['g_username']}" ?>: <input type="text" name="username" size="15" />
			</label>

			<label>
				<?php echo $LNG['g_password']; ?>
				<input type="password" name="password" size="15" />
			</label>

			<?php if($CONF['recaptcha'] && $CONF['admin_recaptcha']){ ?>
				<style>
					.g-recaptcha {
						display: inline-block;
					}
				</style>
				<div style="text-align:center;margin:20px 0;" id="recap">
					<div class="g-recaptcha" data-theme="dark" data-sitekey="<?php echo $CONF['recaptcha_sitekey'];?>"></div>
					<script type="text/javascript" src="https://www.google.com/recaptcha/api.js" async defer></script>
				</div>
			<?php } ?>

			<input type="submit" value="<?php echo $LNG['a_login']; ?>" id="go"/>

		</form>
<?php
	}
?>


<script type="text/javascript">

	$('#myform').submit(function(event) {

		event.preventDefault();

		var self = this;
		window.setTimeout(function() {
			self.submit();
		}, 1200);
	});

	$("#go").click(function() {

		$("#visio").animate({
			width: "132px",
			marginTop: "0"
		}, 1100);

		$('#go').hide('fast');
    $('#recap').hide('fast');
		$('label').hide('fast');
	});
</script>
</body>
</html>
