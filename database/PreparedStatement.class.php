<?php
namespace database;

/**
 * @author Juris Sudmalis
 */
class PreparedStatement
{
	/** @var \PDO */
	private $connection;
	/** @var \PDOStatement */
	private $statement;
	/** @var string */
	private $sqlQuery;
	/** @var boolean */
	private $isCalcRows;
	/** @var boolean */
	private $statementExecuted = false;
	/** @var int */
	private $foundRows = 0;
	
	/**
	 * Create a new PreparedStatement object.
	 * 
	 * @param \PDO $connection : the database connection to use
	 * @param string $sqlQuery : the SQL query to prepare
	 * @param array $pdoParams : see http://php.net/manual/en/pdo.prepare.php for more details
	 * @throws DbException if failed to prepare the query
	 */
	public function __construct(\PDO $connection, $sqlQuery, array $pdoParams)
	{
		$this->connection = $connection;
		$this->sqlQuery = $sqlQuery;
		$this->isCalcRows = (stripos($sqlQuery, 'SQL_CALC_FOUND_ROWS') !== false);
		
		try
		{
			$this->statement = $this->connection->prepare($sqlQuery, $pdoParams);
		}
		catch (\PDOException $e)
		{
			throw new DbException($e->getMessage(), $sqlQuery);
		}
	}

	/**
	 * Execute the prepared statement with the provided query parameters.
	 *
	 * @param array $queryParams : the parameters for the prepared SQL query
	 * @return PreparedStatement this PreparedStatement object
	 * @throws DbException if failed to execute the statement
	 */
	public function execute(array $queryParams = array())
	{
		if (!$this->statement->execute($queryParams))
		{
			throw new DbException('Failed to execute prepared statement', $this->sqlQuery);
		}
		
		$this->statementExecuted = true;
		
		if ($this->isCalcRows)
		{
			try
			{
				$result = $this->connection->query('SELECT FOUND_ROWS()');
				
				if ($result === false)
				{
					$this->foundRows = 0;
				}
				else
				{
					$count = $result->fetchColumn(0);
					$this->foundRows = (empty($count) ? 0 : intval($count));
				}
			}
			catch (\Exception $e)
			{
				$this->foundRows = 0;
			}
		}
		
		return $this;
	}
	
	/**
	 * Fetch the next row in the result set.
	 * 
	 * @param int $fetchMode : the fetch mode to use when fetching the result set
	 * (default: PDO::FETCH_ASSOC, one of PDO::FETCH_* allowed)
	 * @return mixed the next row in the result set (datatype depends on fetch mode)
	 * or false if failed to fetch
	 * @throws DbException a problem occurred while fetching
	 * @see http://php.net/manual/en/pdostatement.fetch.php#refsect1-pdostatement.fetch-parameters
	 */
	public function fetchRow($fetchMode = \PDO::FETCH_ASSOC)
	{
		try
		{
			return $this->statement->fetch($fetchMode);
		}
		catch (\Exception $e)
		{
			throw new DbException($e->getMessage(), $this->sqlQuery);
		}
	}
	
	/**
	 * Fetch a single column from the next row in the result set.
	 * 
	 * @param int $colIndex : the index of the column to fetch, starting from 0
	 * (default: 0) [optional]
	 * @return string a single column value from the next row in the result set
	 * or false if there are no more rows
	 * @throws DbException a problem occurred while fetching
	 */
	public function fetchColumn($colIndex = 0)
	{
		try
		{
			return $this->statement->fetchColumn($colIndex);
		}
		catch (\Exception $e)
		{
			throw new DbException($e->getMessage(), $this->sqlQuery);
		}
	}
	
	/**
	 * @param int $fetchMode : the fetch mode to use when fetching the result set
	 * (default: PDO::FETCH_ASSOC, one of PDO::FETCH_* allowed)
	 * @param mixed $fetchArgument : optional argument for fetch mode
	 * @return array an array containing all rows in the result set
	 * @throws DbException a problem occurred while fetching
	 * @see http://php.net/manual/en/pdostatement.fetch.php#refsect1-pdostatement.fetch-parameters
	 */
	public function fetchAllRows($fetchMode = \PDO::FETCH_ASSOC, $fetchArgument = null)
	{
		try
		{
			// if the second argument is provided with FETCH_ASSOC, it always throws an error
			// there is no default value that could be passed that wouldn't throw it
			if (($fetchArgument !== null)
				&& ($fetchMode & \PDO::FETCH_CLASS > 0)
					|| ($fetchMode & \PDO::FETCH_FUNC > 0)
					|| ($fetchMode & \PDO::FETCH_COLUMN > 0))
			{
				return $this->statement->fetchAll($fetchMode, $fetchArgument);
			}
			
			return $this->statement->fetchAll($fetchMode);
		}
		catch (\Exception $e)
		{
			throw new DbException($e->getMessage(), $this->sqlQuery);
		}
	}
	
	/**
	 * Fetch the value of the specified column from *all* rows in the result set.
	 * 
	 * @param int $columnIndex : the index of the column to fetch (0-indexed, default: 0)
	 * @return array an array containing all values of a single column from the result set
	 * @throws DbException a problem occurred while fetching
	 */
	public function fetchAllRowsOfColumn($columnIndex = 0)
	{
		return $this->fetchAllRows(\PDO::FETCH_COLUMN, $columnIndex);
	}
	
	/**
	 * Execute a function for each row in the result set.
	 * The function must accept an array as the first parameter, an integer
	 * as an optional second parameter (row index in the result set),
	 * and should not return anything.
	 * 
	 * @param callable $func : the function to execute on each row
	 * @throws DbException if the statement hasn't been executed yet
	 */
	public function forEachRow(callable $func)
	{
		if (!$this->statementExecuted)
		{
			throw new DbException('Cannot iterate over rows if statement is not executed');
		}
		
		$rows = $this->fetchAllRows();
		
		array_walk($rows, $func);
	}
	
	/**
	 * Apply a function to each row of the result set.
	 * The function must accept as many parameters as there are columns
	 * in the statement, and return whatever you want it to return.
	 * 
	 * @param callable $func : the function to execute on each row
	 * @throws DbException if the statement hasn't been executed yet
	 * @return array the result of the callback function being applied to all rows
	 */
	public function map(callable $func)
	{
		if (!$this->statementExecuted)
		{
			throw new DbException('Cannot iterate over rows if statement is not executed');
		}
		
		return $this->fetchAllRows(\PDO::FETCH_FUNC, $func);
	}
	
	/**
	 * Get the number of found rows from a SELECT statement.
	 * Works only if the previous statement used the SQL_CALC_FOUND_ROWS modifier.
	 * 
	 * @return int the number of found rows
	 */
	public function getFoundRows()
	{
		return $this->foundRows;
	}
	
	/**
	 * @return int the number of rows inserted/updated/deleted by the last executed SQL query
	 */
	public function getAffectedRowCount()
	{
		return ($this->statementExecuted
			? $this->statement->rowCount()
			: 0);
	}
	
	/**
	 * @return int the value of the last inserted AUTO_INCREMENT column
	 * @see http://php.net/manual/en/pdo.lastinsertid.php
	 */
	public function getLastInsertId()
	{
		return ($this->statementExecuted
			? $this->connection->lastInsertId()
			: 0);
	}
}
