<?php
namespace js\tools\dbhandler;

use js\tools\dbhandler\exceptions\DbException;
use js\tools\dbhandler\exceptions\QueryException;

/**
 * @author Juris Sudmalis
 */
class Handler
{
	private $name;
	/** @var \PDO */
	private $connection;
	private $connectionParameters;
	private $connectionOptions;
	
	/**
	 * Create and/or retrieve a named database connection. Allows for multiple connections to different servers.
	 *
	 * @param array $connectionParameters : an array containing connection parameters.
	 * Required parameters: driver, username, password, database.
	 * Optional parameters: socket | host[, port] (by default, host=localhost).
	 * @param array $customOptions : custom PDO::ATTR_* values, e.g., [ PDO::ATTR_PERSISTENT => true ]
	 * @param string $name : the name of this particular connection
	 * @return Handler the connection handler
	 * @throws DbException if something goes wrong. This is the base exception class for all exceptions thrown by this library.
	 */
	public static function getConnection($name = 'default', array $connectionParameters = [], array $customOptions = [])
	{
		if (!is_string($name))
		{
			throw new DbException('Invalid connection name ' . var_export($name, true));
		}
		
		/** @var Handler[] */
		static $connections = [];
		
		if (isset($connections[$name]))
		{
			// existing connection, check if it is valid and if not - try to reconnect
			$connections[$name]->checkConnection();
		}
		else
		{
			// new connection
			$connections[$name] = new static($name, $connectionParameters, $customOptions);
		}
		
		return $connections[$name];
	}
	
	private function __construct($name, $connectionParameters, $customOptions)
	{
		if (!isset($connectionParameters['driver'], $connectionParameters['database'], $connectionParameters['username'], $connectionParameters['password']))
		{
			throw new DbException('Missing required database connection parameters');
		}
		
		$drivers = \PDO::getAvailableDrivers();
		
		if (!in_array($connectionParameters['driver'], $drivers))
		{
			throw new DbException('Unsupported connection type "' . $connectionParameters['driver'] . '"'
				. ', only the following drivers are enabled: ["' . implode('", "', $drivers) . '"]');
		}
		
		static $defaultOptions = [
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
		];
		
		if (array_key_exists(\PDO::ATTR_ERRMODE, $customOptions))
		{
			// Disallow changing error mode, exceptions are the only way forward.
			// Use try/catch if you need to handle your problems.
			// It makes for a lot of code bloat if I have to manually check every single call to the connection both by a try/catch
			// and by error code if no exception was thrown.
			unset($customOptions[\PDO::ATTR_ERRMODE]);
		}
		
		$options = array_merge($defaultOptions, $customOptions);
		
		$this->name = $name;
		$this->connection = self::connect($connectionParameters, $options);
		$this->connectionParameters = $connectionParameters;
		$this->connectionOptions = $options;
	}
	
	private function __clone()
	{
	}
	
	/**
	 * Switch to the specified database.
	 * 
	 * @param string $dbName : the name of the database to switch to
	 * @return Handler this Handler object
	 * @throws DbException if the database name is invalid
	 */
	public function useDatabase($dbName)
	{
		if (preg_match('/\s/us', $dbName) === 1)
		{
			throw new DbException('Invalid database name specified, no whitespace allowed: ' . print_r($dbName, true));
		}
		
		return $this->exec('USE ' . $dbName);
	}
	
	/**
	 * @return array an array containing information about the last error
	 * that occurred in this database handle (not in any statement handle).
	 */
	public function errorInfo()
	{
		return $this->connection->errorInfo();
	}
	
	/**
	 * Start a database transaction if the database supports it.
	 * 
	 * @return Handler this Handler object
	 */
	public function beginTransaction()
	{
		$this->connection->beginTransaction();
		return $this;
	}
	
	/**
	 * Check if this database connection is currently in transaction mode.
	 *
	 * @return boolean true if this connection is currently in transaction mode, false otherwise
	 */
	public function inTransaction()
	{
		return $this->connection->inTransaction();
	}
	
	/**
	 * Commit a transaction and return to auto-commit mode.
	 * 
	 * @return Handler this Handler object
	 */
	public function commit()
	{
		$this->connection->commit();
		return $this;
	}
	
	/**
	 * Roll back any changes made during the transaction and return to auto-commit mode.
	 * 
	 * @return Handler this Handler object
	 */
	public function rollBack()
	{
		$this->connection->rollBack();
		return $this;
	}
	
	/**
	 * Quote a string for use in an SQL query.
	 * Note: it is strongly recommended to use prepared statements instead of
	 * manually escaping values!
	 *
	 * @param string $string : the string to quote
	 * @param int $dataType : a PDO::PARAM_* value that specifies
	 * how to quote the value (default: PDO::PARAM_STR)
	 * @return string the quoted string
	 */
	public function quote($string, $dataType = \PDO::PARAM_STR)
	{
		if (is_null($string))
		{
			return 'NULL';
		}
		
		return $this->connection->quote($string, $dataType);
	}
	
	/**
	 * Execute a query on the database.
	 *
	 * @param string $query : the SQL query to execute on the database
	 * @return Handler this Handler object
	 * @throws QueryException if the query is invalid
	 * @see query, prepare, quote
	 */
	public function exec($query)
	{
		if (!is_string($query))
		{
			throw new QueryException('Invalid SQL query', $query);
		}
		
		try
		{
			$this->connection->exec($query);
			return $this;
		}
		catch (\Exception $e)
		{
			throw new QueryException('Failed to exec(): ' . $e->getMessage(), $query, $this->connection);
		}
	}
	
	/**
	 * Execute a query on the database and retrieve the result set.
	 *
	 * @param string $query : the SQL query to execute on the database
	 * @return \PDOStatement|false the PDOStatement object if the query was successful, false otherwise
	 * @throws QueryException if the query is invalid
	 * @see exec, prepare, quote
	 */
	public function query($query)
	{
		if (!is_string($query))
		{
			throw new QueryException('Invalid SQL query', $query);
		}
		
		try
		{
			return $this->connection->query($query);
		}
		catch (\Exception $e)
		{
			throw new QueryException('Failed to query(): ' . $e->getMessage(), $query, $this->connection);
		}
	}
	
	/**
	 * Create a prepared statement with the specified query and return it.
	 *
	 * @param string $query : the SQL query to prepare
	 * @param array $pdoParams : an array of driver options to pass
	 * to the PDO prepare() method (default: empty array)
	 * @return PreparedStatement the newly created PreparedStatement object
	 * @throws DbException if the query is invalid
	 * @see exec, query
	 */
	public function prepare($query, array $pdoParams = [])
	{
		if (!is_string($query))
		{
			throw new QueryException('Invalid SQL query', $query);
		}
		
		return new PreparedStatement($this->connection, $query, $pdoParams);
	}
	
	/**
	 * Get the number of found rows from the previous SELECT statement.
	 * Works only if the previous statement used the SQL_CALC_FOUND_ROWS modifier.
	 * 
	 * @return int the number of found rows
	 */
	public function getFoundRows()
	{
		try
		{
			$result = $this->connection->query('SELECT FOUND_ROWS()');
			
			if (empty($result))
			{
				return 0;
			}
			
			$count = $result->fetchColumn(0);
			
			return (empty($count) ? 0 : intval($count));
		}
		catch (\Exception $e)
		{
			return 0;
		}
	}
	
	/**
	 * @return int the ID of the last row that was inserted via this connection
	 */
	public function getLastInsertId()
	{
		return $this->connection->lastInsertId();
	}
	
	private function checkConnection()
	{
		try
		{
			$sum = $this->connection->query('SELECT 1 + 1')->fetchColumn(0);
			
			if (intval($sum) !== 2)
			{
				// Invalid result, try reconnecting because something is clearly wrong.
				$this->connection = self::connect($this->connectionParameters, $this->connectionOptions);
			}
		}
		catch (\Exception $e)
		{
			// Connection may have timed out, try reconnecting.
			// There is no single standard SQLSTATE code for "connection timed out", nor is there a built-in way to check this,
			// so we can only guess that that's what happened.
			$this->connection = self::connect($this->connectionParameters, $this->connectionOptions);
		}
	}
	
	private static function connect($connectionParameters, $connectionOptions)
	{
		try
		{
			return new \PDO(
				self::buildDNSString($connectionParameters),
				$connectionParameters['username'],
				$connectionParameters['password'],
				$connectionOptions
			);
		}
		catch (\PDOException $e)
		{
			throw new DbException('Failed to initialize database connection. '
				. 'Message: ' . $e->getMessage());
		}
	}
	
	private static function buildDNSString(array $params)
	{
		$dns = $params['driver'] . ':';
		
		$con = [
			'dbname' => $params['database']
		];
		
		if (isset($params['socket'])
			&& file_exists($params['socket']))
		{
			$con['unix_socket'] = $params['socket'];
		}
		else
		{
			$con['host'] = (isset($params['host'])
				? $params['host']
				: 'localhost');
			
			if (isset($params['port']))
			{
				$con['port'] = $params['port'];
			}
		}
		
		foreach ($con as $key => $value)
		{
			$dns .= "{$key}={$value};";
		}
		
		return $dns;
	}
}
