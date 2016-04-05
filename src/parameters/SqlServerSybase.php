<?php
namespace js\tools\dbhandler\parameters;

/**
 * Connect to a Microsoft SQL Server database using the Sybase libraries.
 */
class SQLServerSybase extends SQLServerOfficial
{
	const DRIVER = 'sybase';
	
	protected static $portSeparator = ':';
}
