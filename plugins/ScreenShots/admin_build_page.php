   
list($num_waiting_scr) = $DB->fetch("SELECT COUNT(*) FROM {$CONF['sql_prefix']}_screens", __FILE__, __LINE__);
if ($num_waiting_scr == 1) {
    $TMPL['admin_content'] .= "<div class=\"admin_front_approve\"><a href=\"{$TMPL['list_url']}/screenshots.php?list=pending&generate=1\" title=\"{$LNG['plugin_screenshots_approve_scr_a_main']}\" class=\"vistip\" onclick=\"return popitup('screenshots.php?list=pending&generate=1')\">$num_waiting_scr</a></div>";
}
elseif ($num_waiting_scr > 1) {
    $TMPL['admin_content'] .= "<div class=\"admin_front_approve\"><a href=\"{$TMPL['list_url']}/screenshots.php?list=pending&generate=1\" title=\"".sprintf($LNG['plugin_screenshots_approve_scrs_a_main'], $num_waiting_scr)."\" class=\"vistip\" onclick=\"return popitup('screenshots.php?list=pending&generate=1')\">$num_waiting_scr</a></div>";
}
