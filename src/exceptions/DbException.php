<?php
namespace js\tools\dbhandler\exceptions;

/**
 * @author Juris Sudmalis
 */
class DbException extends \Exception
{
	/** @var array */
	private $errorInfo;
	
	/**
	 * @param string $message : the error message
	 * @param \PDO|null $connection : the connection to retrieve the error info from
	 */
	public function __construct($message, \PDO $connection = null)
	{
		parent::__construct($message);
		
		if (is_null($connection))
		{
			$this->errorInfo = [];
		}
		else
		{
			$this->errorInfo = $connection->errorInfo();
		}
	}
	
	/**
	 * Get more information about the error.
	 * 
	 * @return array an array containing the error details
	 */
	public function getErrorInfo()
	{
		return $this->errorInfo;
	}
}
