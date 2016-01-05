<?php
require 'autoloader.php';

use js\tools\dbhandler\exceptions\DbException;
use js\tools\dbhandler\Handler;

try
{
	// TODO set your own test connection parameters!
	$handler = Handler::getConnection('test', [
		'driver' => 'mysql',
		'username' => 'test',
		'password' => 'test',
		'database' => 'test',
	]);
	// this is the mysql.ini variable that should be adjusted according to needs
	// but it works via query as well
	$handler->exec('SET SESSION wait_timeout = 1');
	
	echo 'connection timeout set to 1 second, sleeping', PHP_EOL;
	sleep(3);
	
	if ($handler->checkConnection())
	{
		echo 'connection is good', PHP_EOL;
	}
	else
	{
		echo 'connection is down, reconnecting', PHP_EOL;
		$handler->connect();
	}
	
	if ($handler->checkConnection())
	{
		echo 'connection is now good', PHP_EOL;
	}
	else
	{
		echo 'connection is still down, failed to reconnect', PHP_EOL;
	}
}
catch (DbException $e)
{
	echo $e->getMessage(), PHP_EOL;
	die();
}
