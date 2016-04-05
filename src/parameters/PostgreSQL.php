<?php
namespace js\tools\dbhandler\parameters;

class PostgreSQL extends ConnectionParameters
{
	const DRIVER = 'pgsql';
	
	public static function viaHost(
		string $dbname, string $username, string $password, string $host = 'localhost', int $port = null
	)
	{
		$con = [
			'host'   => $host,
			'dbname' => $dbname,
		];
		
		if ($port !== null)
		{
			$con['port'] = $port;
		}
		
		return new static(self::buildDNS($con), $username, $password);
	}
	
	/**
	 * @param string $username
	 * @param string $password
	 * @param array $parameters : a key-value map of any of the parameters listed here:
	 *     {@link http://www.postgresql.org/docs/current/static/libpq-connect.html#LIBPQ-PARAMKEYWORDS}
	 * @return static
	 */
	public static function custom(string $username, string $password, array $parameters = [])
	{
		return new static(self::buildDNS($parameters), $username, $password);
	}
}
