if (!empty($CONF['visio_screen_api'])) 
{  
    $TMPL['user_cp_links'] .= $this->do_plugin_skin("{$CONF['path']}/plugins/ScreenShots", 'user_cp_menu_links');
}   