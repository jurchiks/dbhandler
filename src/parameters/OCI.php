<?php
namespace js\tools\dbhandler\parameters;

class OCI extends ConnectionParameters
{
	const DRIVER = 'oci';
	
	/**
	 * If you wish to connect to a database defined in "tnsnames.ora", you can skip host and port.
	 *
	 * @param string $dbname
	 * @param string $username
	 * @param string $password
	 * @param string $host
	 * @param int $port
	 * @return static
	 */
	public static function viaHost(
		string $dbname, string $username, string $password, string $host = '', int $port = null
	)
	{
		if ($host)
		{
			$dns = '//' . $host;
			
			if ($port !== null)
			{
				$dns .= ':' . $port;
			}
			
			if ($dbname)
			{
				$dns .= '/' . $dbname;
			}
		}
		else
		{
			$dns = $dbname;
		}
		
		return new static(self::buildDNS(['database' => $dns]), $username, $password);
	}
}
