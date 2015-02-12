<?php
define('DS', DIRECTORY_SEPARATOR);

spl_autoload_register(function ($className)
{
	static $extensions = array(
		'.class.php',
		'.interface.php',
		'.php',
	);
	
	if (DS !== '\\')
	{
		$className = str_replace('\\', DS, $className);
	}
	
	$className = ltrim($className, '/\\');
	
	foreach ($extensions as $ext)
	{
		$path = __DIR__ . DS . $className . $ext;
		
		if (is_readable($path))
		{
			require $path;
			return true;
		}
	}
	
	return false;
});
