<?php
spl_autoload_register(
	function ($className)
	{
		$ds = DIRECTORY_SEPARATOR;
		$className = str_replace('js\\tools\\dbhandler', '', $className);
		$className = str_replace('\\', $ds, $className);
		$className = trim($className, $ds);
		
		$path = __DIR__ . $ds . 'src' . $ds . $className . '.php';
		
		if (!is_readable($path))
		{
			return false;
		}
		
		require $path;
		return true;
	},
	true
);
