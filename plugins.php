<?php
//===========================================================================\\
// VISIOLIST is a proud derivative work of Aardvark Topsites                 \\
// Copyright (c) 2000-2009 Jeremy Scheff.  All rights reserved.              \\
//---------------------------------------------------------------------------\\
// http://www.aardvarktopsitesphp.com/                http://www.avatic.com/ \\
//---------------------------------------------------------------------------\\
// This program is free software; you can redistribute it and/or modify it   \\
// under the terms of the GNU General Public License as published by the     \\
// Free Software Foundation; either version 2 of the License, or (at your    \\
// option) any later version.                                                \\
//                                                                           \\
// This program is distributed in the hope that it will be useful, but       \\
// WITHOUT ANY WARRANTY; without even the implied warranty of                \\
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General \\
// Public License for more details.                                          \\
//===========================================================================\\

if (!defined('VISIOLIST')) {
  die("This file cannot be accessed directly.");
}

class pluginManager
{
    private static $pluginManager;
    private $plugins;
    private $hooks;

    function __construct()
	{
		$this->plugins = array();
		$this->hooks   = array();
		$plugin_dir    = __DIR__ . '/plugins/';
		$plugin_list   = scandir($plugin_dir);
	
		foreach ($plugin_list as $plugin)
		{
			if ($plugin != '.' && $plugin != '..') 
			{
				// Look for folders of plugins
				$plugin_path = $plugin_dir . $plugin;

				if (is_dir($plugin_path) && mb_strpos($plugin, '0_') === FALSE) 
				{									
					// Now we look at the files in the plugin folder
					$files = scandir($plugin_path);
					foreach($files as $file)  
					{
						$file_lowercase = mb_strtolower($file);
						if ($file != '.' && $file != '..' && mb_substr($file_lowercase, -4) === '.php') 
						{
							$code = file_get_contents("{$plugin_path}/{$file}");        
							$hook = mb_substr($file_lowercase, 0, -4); // Strip the .php
							
							// Save the Plugin (/plugins/PlugName/HookName.php)
							$this->addHook($hook, $plugin, $code);
						}
					}
				}
			}
		}
    }

    // Gets the plugin manager singleton
    public static function getPluginManager()
	{
		if (is_null(self::$pluginManager)) 
		{
			self::$pluginManager = new pluginManager();
		}
		
		return self::$pluginManager;
    }

    private function addHook($hook, $plugin, $code) 
	{
		if (!isset($this->hooks[$hook][$plugin])) 
		{
			$this->hooks[$hook][$plugin] = $code;
		}
		else 
		{
			die("Already Hooked into this - " . $plugin . " -> " . $hook);
		}
	}

    // Call any plugins that are hooked into this
    public function pluginHooks($hook)
	{
		$hook = mb_strtolower($hook);

		$code = '';

		if (isset($this->hooks[$hook])) 
		{
			foreach($this->hooks[$hook] as $plugin => $hook) 
			{
				$code .= $hook;
			}
		}
		
		return $code;
    }
}
