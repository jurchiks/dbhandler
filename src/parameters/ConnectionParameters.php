<?php
namespace js\tools\dbhandler\parameters;

abstract class ConnectionParameters
{
	const DRIVER = 'undefined';
	
	protected $connectionString;
	protected $username;
	protected $password;
	
	public final function __construct(string $dns, string $username, string $password)
	{
		$this->connectionString = static::DRIVER . ':' . $dns;
		$this->username = $username;
		$this->password = $password;
	}
	
	public function getConnectionString(): string
	{
		return $this->connectionString;
	}
	
	public function getUsername(): string
	{
		return $this->username;
	}
	
	public function getPassword(): string
	{
		return $this->password;
	}
	
	protected static function buildDNS(array $parameters): string
	{
		$dns = '';
		
		foreach ($parameters as $key => $value)
		{
			if (is_int($key))
			{
				$dns .= $value . ';';
			}
			else
			{
				$dns .= "{$key}={$value};";
			}
		}
		
		return trim($dns, ';');
	}
}
