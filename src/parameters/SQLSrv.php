<?php
namespace js\tools\dbhandler\parameters;

/**
 * Connect to Microsoft SQL Server and Azure databases.
 */
class SQLSrv extends ConnectionParameters
{
	const DRIVER = 'sqlsrv';
	
	public static function viaHost(
		string $dbname, string $username, string $password, string $server = 'localhost', int $port = null
	)
	{
		$con = [
			'Server'   => $server,
			'Database' => $dbname,
		];
		
		if ($port !== null)
		{
			$con['Server'] .= ',' . $port;
		}
		
		if (stripos($server, 'database.windows.net'))
		{
			// for Azure database connections, the server URL is "XXXX.database.windows.net" and username is "user@XXXX"
			$serverName = substr($server, 0, stripos($server, '.database.windows.net'));
			
			if (!strpos($username, $serverName))
			{
				$username .= '@' . $serverName;
			}
		}
		
		return new static(self::buildDNS($con), $username, $password);
	}
	
	/**
	 * @param string $username
	 * @param string $password
	 * @param array $parameters : a key-value map of any of the parameters listed here:
	 *     {@link http://php.net/manual/en/ref.pdo-sqlsrv.connection.php}
	 * @return static
	 */
	public static function custom(string $username, string $password, array $parameters = [])
	{
		return new static(self::buildDNS($parameters), $username, $password);
	}
}
