<?php
namespace database;

/**
 * @author Juris Sudmalis
 */
class Handler
{
	/** @var \PDO */
	private $connection;
	
	private function __construct(array $params, array $customOptions = array())
	{
		if (!isset($params['type'], $params['dbname'], $params['username'], $params['password']))
		{
			throw new DbException('Missing database connection parameters');
		}
		
		static $defaultOptions = array(
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
		);
		
		try
		{
			$this->connection = new \PDO(
				self::buildDNSString($params),
				$params['username'],
				$params['password'],
				array_merge($defaultOptions, $customOptions)
			);
		}
		catch (\PDOException $e)
		{
			throw new DbException('Failed to initialize database connection. '
				. 'Message: ' . $e->getMessage());
		}
	}
	
	private function __clone()
	{
	}
	
	private static function buildDNSString(array $params)
	{
		$dns = $params['type'] . ':';
		
		$con = array(
			'dbname' => $params['dbname']
		);
		
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
	
	public static function getInstance()
	{
		static $connection = null;
		
		if ($connection === null)
		{
			$connection = new Handler(
				array(
					'type' => 'mysql',
					'host' => 'localhost',
					'dbname' => 'test',
					'username' => 'test',
					'password' => 'test',
				),
				array(
					\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
				)
			);
		}
		
		return $connection;
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
		if (!preg_match('/\A[a-zA-Z0-9_]+\z/iu', $dbName))
		{
			throw new DbException('Invalid database name specified, only letters'
				. ', numbers, and _ are allowed: ' . print_r($dbName, true));
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
	 * @throws DbException if the query is invalid
	 * @see query, prepare, quote
	 */
	public function exec($query)
	{
		if (!is_string($query))
		{
			throw new DbException('Invalid SQL query', $query);
		}
		
		try
		{
			$this->connection->exec($query);
			return $this;
		}
		catch (\Exception $e)
		{
			throw new DbException('Failed to exec(): ' . $e->getMessage(), $query, $this);
		}
	}
	
	/**
	 * Execute a query on the database and retrieve the result set.
	 *
	 * @param string $query : the SQL query to execute on the database
	 * @return \PDOStatement|false the PDOStatement object if the query was successful, false otherwise
	 * @throws DbException if the query is invalid
	 * @see exec, prepare, quote
	 */
	public function query($query)
	{
		if (!is_string($query))
		{
			throw new DbException('Invalid SQL query', $query);
		}
		
		try
		{
			return $this->connection->query($query);
		}
		catch (\Exception $e)
		{
			throw new DbException('Failed to query(): ' . $e->getMessage(), $query, $this);
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
	public function prepare($query, array $pdoParams = array())
	{
		if (!is_string($query))
		{
			throw new DbException('Invalid SQL query', $query);
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
}
