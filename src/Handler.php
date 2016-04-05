<?php
namespace js\tools\dbhandler;

use Exception;
use js\tools\dbhandler\exceptions\DbException;
use js\tools\dbhandler\exceptions\QueryException;
use js\tools\dbhandler\parameters\ConnectionParameters;
use PDO;
use PDOException;
use PDOStatement;

/**
 * @author Juris Sudmalis
 */
class Handler
{
	private $name;
	/** @var PDO */
	private $connection;
	private $connectionParameters;
	private $connectionOptions;
	
	/**
	 * Create and/or retrieve a named database connection. Allows for multiple connections to different servers.
	 *
	 * @param ConnectionParameters $connectionParameters : a connection parameter container
	 * @param array $customOptions : custom PDO::ATTR_* values, e.g., [ PDO::ATTR_PERSISTENT => true ]
	 * @param string $name : the name of this particular connection
	 * @return Handler the connection handler
	 * @throws DbException if something goes wrong. This is the base exception class for all exceptions thrown by this library.
	 */
	public static function getConnection(string $name = 'default', ConnectionParameters $connectionParameters = null, array $customOptions = [])
	{
		/** @var Handler[] $connections */
		static $connections = [];
		
		if (isset($connections[$name]))
		{
			// existing connection, check if it is valid and if not - try to reconnect
			if (!$connections[$name]->checkConnection())
			{
				$connections[$name]->connect();
			}
		}
		else
		{
			if ($connectionParameters === null)
			{
				throw new DbException('Cannot create a connection without parameters');
			}
			
			// new connection
			$connections[$name] = new static($name, $connectionParameters, $customOptions);
		}
		
		return $connections[$name];
	}
	
	private function __construct(string $name, ConnectionParameters $connectionParameters, array $customOptions)
	{
		$drivers = PDO::getAvailableDrivers();
		
		if (!in_array($connectionParameters::DRIVER, $drivers))
		{
			throw new DbException('Unsupported connection type "' . $connectionParameters::DRIVER . '"'
				. ', only the following drivers are enabled: ["' . implode('", "', $drivers) . '"]');
		}
		
		static $defaultOptions = [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		];
		
		if (array_key_exists(PDO::ATTR_ERRMODE, $customOptions))
		{
			// Disallow changing error mode, exceptions are the only way forward.
			// Use try/catch if you need to handle your problems.
			// It makes for a lot of code bloat if I have to manually check every single call to the connection both by a try/catch
			// and by error code if no exception was thrown.
			unset($customOptions[PDO::ATTR_ERRMODE]);
		}
		
		$options = array_merge($defaultOptions, $customOptions);
		
		$this->name = $name;
		$this->connectionParameters = $connectionParameters;
		$this->connectionOptions = $options;
		
		$this->connect();
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
	public function errorInfo(): array
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
	public function inTransaction(): bool
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
	 * @param string|null $string : the string to quote
	 * @param int $dataType : a PDO::PARAM_* value that specifies
	 * how to quote the value (default: PDO::PARAM_STR)
	 * @return string the quoted string
	 */
	public function quote(string $string = null, $dataType = PDO::PARAM_STR)
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
	public function exec(string $query)
	{
		try
		{
			$this->connection->exec($query);
			return $this;
		}
		catch (Exception $e)
		{
			throw new QueryException('Failed to exec(): ' . $e->getMessage(), $query, $this->connection);
		}
	}
	
	/**
	 * Execute a query on the database and retrieve the result set.
	 *
	 * @param string $query : the SQL query to execute on the database
	 * @return PDOStatement|false the PDOStatement object if the query was successful, false otherwise
	 * @throws QueryException if the query is invalid
	 * @see exec, prepare, quote
	 */
	public function query(string $query)
	{
		try
		{
			return $this->connection->query($query);
		}
		catch (Exception $e)
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
	public function prepare(string $query, array $pdoParams = []): PreparedStatement
	{
		return new PreparedStatement($this->connection, $query, $pdoParams);
	}
	
	/**
	 * Get the number of found rows from the previous SELECT statement.
	 * Works only if the previous statement used the SQL_CALC_FOUND_ROWS modifier.
	 * 
	 * @return int the number of found rows
	 */
	public function getFoundRows(): int
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
		catch (Exception $e)
		{
			return 0;
		}
	}
	
	/**
	 * @param string|null $sequence : name of the sequence object from which the ID should be returned
	 * @return string the ID of the last row that was inserted via this connection
	 */
	public function getLastInsertId(string $sequence = null): string
	{
		return $this->connection->lastInsertId($sequence);
	}
	
	public function checkConnection()
	{
		try
		{
			// this throws an error (not an exception) if the connection has gone away;
			// needs to be suppressed because we want only exceptions
			$sum = @$this->connection->query('SELECT 1 + 1');
			
			if ($sum instanceof PDOStatement)
			{
				$sum = $sum->fetchColumn(0);
			}
			else
			{
				$sum = 0;
			}
			
			if (intval($sum) !== 2)
			{
				// Invalid result, something is clearly wrong.
				return false;
			}
		}
		catch (Exception $e)
		{
			// Connection may have timed out.
			// There is no single standard SQLSTATE code for "connection timed out", nor is there a built-in way to check this,
			// so we can only guess that that's what happened.
			return false;
		}
		
		return true;
	}
	
	public function connect()
	{
		try
		{
			$this->connection = new PDO(
				$this->connectionParameters->getConnectionString(),
				$this->connectionParameters->getUsername(),
				$this->connectionParameters->getPassword(),
				$this->connectionOptions
			);
		}
		catch (PDOException $e)
		{
			throw new DbException('Failed to initialize database connection. '
				. 'Message: ' . $e->getMessage());
		}
	}
}
