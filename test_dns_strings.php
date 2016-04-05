<?php
use js\tools\dbhandler\parameters\ConnectionParameters;
use js\tools\dbhandler\parameters\MySQL;
use js\tools\dbhandler\parameters\OCI;
use js\tools\dbhandler\parameters\ODBC;
use js\tools\dbhandler\parameters\PostgreSQL;
use js\tools\dbhandler\parameters\SQLite;
use js\tools\dbhandler\parameters\SQLServerOfficial;
use js\tools\dbhandler\parameters\SQLServerSybase;
use js\tools\dbhandler\parameters\SQLServerTDS;
use js\tools\dbhandler\parameters\SQLSrv;

require 'autoloader.php';

$host = 'localhost';
$port = 1234;
$dbname = 'test';
$username = 'test';
$password = 'test';
/** @var ConnectionParameters[] $parameters */
$parameters = [
	MySQL::viaHost($dbname, $username, $password, $host, $port),
	PostgreSQL::viaHost($dbname, $username, $password, $host, $port),
	SQLSrv::viaHost($dbname, $username, $password, $host, $port),
	SQLSrv::viaHost($dbname, $username, $password, 'foo.database.windows.net', $port),
	SQLServerOfficial::viaHost($dbname, $username, $password, $host, $port),
	SQLServerSybase::viaHost($dbname, $username, $password, $host, $port),
	SQLServerTDS::viaHost($dbname, $username, $password, $host, $port),
	ODBC::viaCataloged($dbname, $username, $password),
	ODBC::viaHost($dbname, $username, $password, $host),
	OCI::viaHost($dbname, $username, $password, $host, $port),
	SQLite::create(':memory:', $username, $password),
];

foreach ($parameters as $container)
{
	echo get_class($container), ' - ', $container->getConnectionString(), PHP_EOL;
}
