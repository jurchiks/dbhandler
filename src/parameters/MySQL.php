<?php
namespace js\tools\dbhandler\parameters;

use js\tools\dbhandler\exceptions\DbException;

class MySQL extends ConnectionParameters
{
	const DRIVER = 'mysql';
	
	public static function viaSocket(string $dbname, string $username, string $password, string $socket)
	{
		if (!file_exists($socket))
		{
			throw new DbException('Invalid connection socket provided');
		}
		
		$con = [
			'dbname'      => $dbname,
			'unix_socket' => $socket,
		];
		
		return new static(self::buildDNS($con), $username, $password);
	}
	
	public static function viaHost(
		string $dbname, string $username, string $password, string $host = 'localhost', int $port = null
	)
	{
		$con = [
			'dbname' => $dbname,
			'host'   => $host,
		];
		
		if ($port !== null)
		{
			$con['port'] = $port;
		}
		
		return new static(self::buildDNS($con), $username, $password);
	}
}
