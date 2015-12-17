<?php
namespace js\tools\dbhandler\exceptions;

/**
 * @author Juris Sudmalis
 */
class DbException extends \Exception
{
	/** @var string */
	private $query;
	/** @var array */
	private $errorDetails;
	
	/**
	 * @param string $message : the error message
	 * @param string $query : the SQL query that caused the exception (optional)
	 * @param \PDO|null $connection : the connection to retrieve the error info from
	 */
	public function __construct($message, $query = '', \PDO $connection = null)
	{
		parent::__construct($message);
		$this->query = $query;
		
		if (is_null($connection))
		{
			$this->errorDetails = [];
		}
		else
		{
			$this->errorDetails = $connection->errorInfo();
		}
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
	
	/**
	 * Get more information about the error.
	 * 
	 * @return array an array containing the error details
	 */
	public function getErrorInfo()
	{
		return $this->errorDetails;
	}
}
