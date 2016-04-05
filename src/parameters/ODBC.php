<?php
namespace js\tools\dbhandler\parameters;

/**
 * Connect to any database that supports the ODBC driver.
 */
class ODBC extends ConnectionParameters
{
	const DRIVER = 'odbc';
	
	/**
	 * Connect to a database cataloged in the ODBC driver manager or the DB2 catalog.
	 *
	 * @param string $dbname : the name of the cataloged database
	 * @param string $username
	 * @param string $password
	 * @return static
	 */
	public static function viaCataloged(string $dbname, string $username, string $password)
	{
		return new static(self::buildDNS([$dbname]), $username, $password);
	}
	
	public static function viaHost(
		string $dbname, string $username, string $password, string $server, string $driver = ''
	)
	{
		$dns = [];
		
		if ($driver)
		{
			$dns['Driver'] = $driver;
		}
		
		$dns['Server'] = $server;
		$dns['Database'] = $dbname;
		
		return new static(self::buildDNS($dns), $username, $password);
	}
	
	/**
	 * @param string $username
	 * @param string $password
	 * @param array $parameters : a key-value map of parameters for your specific database; full list available here:
	 *     {@link http://www.connectionstrings.com/}
	 * @return static
	 */
	public static function custom(string $username, string $password, array $parameters)
	{
		return new static(self::buildDNS($parameters), $username, $password);
	}
}
