<?php
namespace js\tools\dbhandler\exceptions;

class QueryException extends DbException
{
	/** @var string */
	private $query;
	
	/**
	 * @param string $message : the error message
	 * @param string $query : the SQL query that caused the exception (optional)
	 * @param \PDO|null $connection : the connection to retrieve the error info from
	 */
	public function __construct($message, $query, \PDO $connection = null)
	{
		parent::__construct($message, $connection);
		
		$this->query = $query;
	}
	
	/**
	 * Get the SQL query that caused this exception.
	 *
	 * @return string
	 */
	public function getQuery()
	{
		return print_r($this->query, true);
	}
}
