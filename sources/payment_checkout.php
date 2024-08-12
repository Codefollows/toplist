<?php
//===========================================================================\\
// VISIOLIST is a proud derivative work of Aardvark Topsites                 \\
// Copyright (c) 2000-2007 Jeremy Scheff.  All rights reserved.              \\
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

class payment_checkout extends base
{	
	/**
	 * Unified entry point for payments checkout ( final button click ) via ajax
	 * This validates passed checkout data attributes and possibly setup a js provider ( e.g stripe )
	 *
	 * Returns error ( integrated as alert ), or empty/populated response based on provider
	 * 
	 * Requirements to be passed in the request: Check checkoutValidate() function
	 */
	public function __construct() 
	{
		global $CONF, $FORM, $DB, $LNG, $TMPL;
	
		require_once("{$CONF['path']}/sources/misc/Payment.php");
		$Payment = new Payment();
		
		
		/**
		 * Plugin Hook - Do whatever really, should not be used in most cases though
		 *
		 * e.g you can disable the login check by setting the property to false based on which service is called
		 * This could be useful if you have some sort of custom advertisement system outside of the user panel
		 *
		 * if (isset($FORM['service']) && $FORM['service'] === 'My Service') {
		 *		$Payment->checkout_require_login = false;
		 * }
		 */
		eval(PluginManager::getPluginManager()->pluginHooks('payment_checkout_init'));
		
		
		// Validates the passed ajax data 
		$Payment->checkoutValidate();
	}
}
