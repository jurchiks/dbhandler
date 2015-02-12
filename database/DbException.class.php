<?php
namespace database;

/**
 * @author Juris Sudmalis
 */
class DbException extends \Exception
{
	/** @var string */
	private $query;
	/** @var array */
	private $errors;
	
	/**
	 * @param string $message : the error message
	 * @param string $query : the SQL query that caused the exception (optional)
	 */
	public function __construct($message, $query = '')
	{
		parent::__construct($message);
		$this->query = $query;
		$this->errors = Handler::getInstance()->errorInfo();
	}
	
	public function getQuery()
	{
		return print_r($this->query, true);
	}
	
	/**
	 * @return array an array containing the error details
	 */
	public function getErrorInfo()
	{
		return $this->errors;
	}
}
