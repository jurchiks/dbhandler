<?php
namespace js\tools\dbhandler\parameters;

/**
 * Connect to a Microsoft SQL Server database using the official Microsoft SQL Server libraries.
 */
class SQLServerOfficial extends ConnectionParameters
{
	const DRIVER = 'mssql';
	
	protected static $portSeparator = ',';
	
	public static function viaHost(
		string $dbname, string $username, string $password, string $host = 'localhost', int $port = null
	)
	{
		if ($port !== null)
		{
			$host .= static::$portSeparator . $port;
		}
		
		return new static(self::buildDNS(['host' => $host, 'dbname' => $dbname]), $username, $password);
	}
}
