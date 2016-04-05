<?php
namespace js\tools\dbhandler\parameters;

class SQLite extends ConnectionParameters
{
	const DRIVER = 'sqlite';
	
	/**
	 * Note: if using a file for the database, the DIRECTORY must be writable by the webserver, because SQLite uses
	 * temporary files other than the db file itself.
	 * Note 2: if using :memory: database, it will only stay in memory for the duration of the request.
	 * 
	 * @param string $dbname : either ':memory:' or an absolute path to the database file
	 * @param string $username
	 * @param string $password
	 * @return static
	 */
	public static function create(string $dbname, string $username, $password)
	{
		return new static(self::buildDNS([$dbname]), $username, $password);
	}
}
