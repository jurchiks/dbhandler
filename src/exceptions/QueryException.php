<?php
namespace js\tools\dbhandler\exceptions;

use PDO;

class QueryException extends DbException
{
	/** @var string */
	private $query;
	
	/**
	 * @param string $message : the error message
	 * @param string $query : the SQL query that caused the exception (optional)
	 * @param PDO|null $connection : the connection to retrieve the error info from
	 */
	public function __construct(string $message, string $query, PDO $connection = null)
	{
		parent::__construct($message, $connection);
		
		$this->query = $query;
	}
	
	/**
	 * Get the SQL query that caused this exception.
	 *
	 * @return string
	 */
	public function getQuery(): string
	{
		return print_r($this->query, true);
	}
}
