<?php
namespace js\tools\dbhandler\parameters;

/**
 * Connect to a Microsoft SQL Server database using the FreeTDS libraries.
 */
class SQLServerTDS extends SQLServerOfficial
{
	const DRIVER = 'dblib';
	
	protected static $portSeparator = ':';
}
