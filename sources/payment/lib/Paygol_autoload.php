<?php

/* An autoloader for Paygol\Foo classes. This should be required()
 * by the user before attempting to instantiate any of the Paygol
 * classes.
 */

spl_autoload_register(function ($class) 
{
    if (substr($class, 0, 7) !== 'Paygol\\') 
	{
        /* If the class does not lie under the "Paygol" namespace,
         * then we can exit immediately.
         */
        return;
    }

    /* All of the classes have names like "Paygol\Foo", so we need
     * to replace the backslashes with frontslashes if we want the
     * name to map directly to a location in the filesystem.
     */
    $class = str_replace('\\', '/', $class);

    $path = __DIR__ .'/'.$class.'.php';
    if (file_exists($path)) {
        require_once($path);
    }
});
