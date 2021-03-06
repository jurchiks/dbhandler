<?php
namespace js\tools\dbhandler;

use Exception;
use js\tools\dbhandler\exceptions\PreparedStatementException;
use PDO;
use PDOException;
use PDOStatement;

/**
 * @author Juris Sudmalis
 */
class PreparedStatement
{
	/** @var PDO */
	private $connection;
	/** @var PDOStatement */
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
	 * @param PDO $connection : the database connection to use
	 * @param string $sqlQuery : the SQL query to prepare
	 * @param array $pdoParams : see http://php.net/manual/en/pdo.prepare.php for more details
	 * @throws PreparedStatementException if failed to prepare the query
	 */
	public function __construct(PDO $connection, string $sqlQuery, array $pdoParams)
	{
		$this->connection = $connection;
		$this->sqlQuery = $sqlQuery;
		$this->isCalcRows = (stripos($sqlQuery, 'SQL_CALC_FOUND_ROWS') !== false);
		
		try
		{
			$this->statement = $this->connection->prepare($sqlQuery, $pdoParams);
		}
		catch (PDOException $e)
		{
			throw new PreparedStatementException($e->getMessage(), $sqlQuery, $this->connection);
		}
	}

	/**
	 * Execute the prepared statement with the provided query parameters.
	 *
	 * @param array $queryParams : the parameters for the prepared SQL query
	 * @return PreparedStatement this PreparedStatement object
	 * @throws PreparedStatementException if failed to execute the statement
	 */
	public function execute(array $queryParams = [])
	{
		try
		{
			$this->statement->execute($queryParams);
		}
		catch (PDOException $e)
		{
			throw new PreparedStatementException('Failed to execute prepared statement: ' . $e->getMessage(), $this->sqlQuery, $this->connection);
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
			catch (Exception $e)
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
	 * @return mixed the next row in the result set (data type depends on fetch mode)
	 * or false if failed to fetch
	 * @throws PreparedStatementException if a problem occurred while fetching
	 * @see http://php.net/manual/en/pdostatement.fetch.php#refsect1-pdostatement.fetch-parameters
	 */
	public function fetchRow(int $fetchMode = PDO::FETCH_ASSOC)
	{
		try
		{
			return $this->statement->fetch($fetchMode);
		}
		catch (Exception $e)
		{
			throw new PreparedStatementException($e->getMessage(), $this->sqlQuery, $this->connection);
		}
	}
	
	/**
	 * Fetch a single column from the next row in the result set.
	 * 
	 * @param int $colIndex : the index of the column to fetch, starting from 0
	 * (default: 0) [optional]
	 * @return string a single column value from the next row in the result set
	 * or false if there are no more rows
	 * @throws PreparedStatementException if a problem occurred while fetching
	 */
	public function fetchColumn(int $colIndex = 0)
	{
		try
		{
			return $this->statement->fetchColumn($colIndex);
		}
		catch (Exception $e)
		{
			throw new PreparedStatementException($e->getMessage(), $this->sqlQuery, $this->connection);
		}
	}
	
	/**
	 * @param int $fetchMode : the fetch mode to use when fetching the result set
	 * (default: PDO::FETCH_ASSOC, one of PDO::FETCH_* allowed)
	 * @param mixed $fetchArgument : optional argument for fetch mode
	 * @return array an array containing all rows in the result set
	 * @throws PreparedStatementException if a problem occurred while fetching
	 * @see http://php.net/manual/en/pdostatement.fetch.php#refsect1-pdostatement.fetch-parameters
	 */
	public function fetchAllRows(int $fetchMode = PDO::FETCH_ASSOC, $fetchArgument = null): array
	{
		try
		{
			// if the second argument is provided with FETCH_ASSOC, it always throws an error
			// there is no default value that could be passed that wouldn't throw it
			if (($fetchArgument !== null)
				&& ((($fetchMode & PDO::FETCH_CLASS) > 0)
					|| (($fetchMode & PDO::FETCH_FUNC) > 0)
					|| (($fetchMode & PDO::FETCH_COLUMN) > 0)))
			{
				return $this->statement->fetchAll($fetchMode, $fetchArgument);
			}
			
			return $this->statement->fetchAll($fetchMode);
		}
		catch (Exception $e)
		{
			throw new PreparedStatementException($e->getMessage(), $this->sqlQuery, $this->connection);
		}
	}
	
	/**
	 * Fetch the value of the specified column from *all* rows in the result set.
	 * 
	 * @param int $columnIndex : the index of the column to fetch (0-indexed, default: 0)
	 * @return array an array containing all values of a single column from the result set
	 * @throws PreparedStatementException a problem occurred while fetching
	 */
	public function fetchAllRowsOfColumn(int $columnIndex = 0): array
	{
		return $this->fetchAllRows(PDO::FETCH_COLUMN, $columnIndex);
	}
	
	/**
	 * Execute a function for each row in the result set.
	 * 
	 * @param callable $callback : the function to execute on each row.
	 * If the callback function returns false, iteration is stopped.
	 * Signature - (array $rowAssoc, int $index): void|bool
	 * @throws PreparedStatementException if the statement hasn't been executed yet
	 */
	public function forEachRow(callable $callback)
	{
		if (!$this->statementExecuted)
		{
			throw new PreparedStatementException('Cannot iterate over rows if statement is not executed');
		}
		
		$i = 0;
		
		while ($row = $this->fetchRow())
		{
			if ($callback($row, $i) === false)
			{
				break;
			}
			
			$i++;
		}
	}
	
	/**
	 * Apply a function to each row of the result set.
	 * 
	 * @param callable $callback : the function to execute on each row.
	 * Signature - (array $row, int $index): mixed
	 * @throws PreparedStatementException if the statement hasn't been executed yet
	 * @return array the result of the callback function being applied to all rows
	 */
	public function map(callable $callback): array
	{
		if (!$this->statementExecuted)
		{
			throw new PreparedStatementException('Cannot iterate over rows if statement is not executed');
		}
		
		$i = 0;
		$data = [];
		
		while ($row = $this->fetchRow())
		{
			$data[] = $callback($row, $i);
			$i++;
		}
		
		return $data;
	}
	
	/**
	 * Get the number of found rows from a SELECT statement.
	 * Works only if the previous statement used the SQL_CALC_FOUND_ROWS modifier.
	 * 
	 * @return int the number of found rows
	 */
	public function getFoundRows(): int
	{
		return $this->foundRows;
	}
	
	/**
	 * @return int the number of rows inserted/updated/deleted by the last executed SQL query
	 */
	public function getAffectedRowCount(): int
	{
		return ($this->statementExecuted
			? $this->statement->rowCount()
			: 0);
	}
	
	/**
	 * @return string|int the value of the last inserted AUTO_INCREMENT column
	 * @see http://php.net/manual/en/pdo.lastinsertid.php
	 */
	public function getLastInsertId()
	{
		return ($this->statementExecuted
			? $this->connection->lastInsertId()
			: 0);
	}
}
