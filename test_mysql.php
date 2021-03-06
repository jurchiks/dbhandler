<?php
require 'autoloader.php';

use js\tools\dbhandler\exceptions\DbException;
use js\tools\dbhandler\exceptions\QueryException;
use js\tools\dbhandler\Handler;
use js\tools\dbhandler\parameters\MySQL;

try
{
	// TODO set your own test connection parameters!
	$handler = Handler::getConnection('test', MySQL::viaHost('test', 'test', 'test'));
}
catch (DbException $e)
{
	echo $e->getMessage(), PHP_EOL;
	die();
}

try
{
	// Inserting would throw an exception if a table by this name already existed, but didn't match the same structure,
	// so we're deleting it first thing. Sorry if it was important!
	$handler->exec('DROP TABLE IF EXISTS test');
	$handler->exec(
		'CREATE TABLE test (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			name VARCHAR(255) NOT NULL,
			access_level INT NOT NULL,
			creation_time DATETIME NOT NULL,
			UNIQUE (name)
		) ENGINE InnoDB CHARSET utf8 COLLATE utf8_unicode_ci'
	);
	
	$insertStmt = $handler->prepare(
		'INSERT IGNORE INTO test
		(name, access_level, creation_time)
		VALUES
		(?, ?, NOW())'
	);
	$data = [
		['foo1', 1],
		['foo2', 1],
		['foo3', 10],
		['foo4', 10],
		['bar1', 100],
		['bar2', 100],
	];
	
	foreach ($data as $user)
	{
		$insertStmt->execute($user);
	}
	
	echo '===fetchAllRows===', PHP_EOL;
	$users = $handler
		->prepare('SELECT id, name, access_level FROM test WHERE name LIKE ? LIMIT 10')
		->execute(['foo%'])
		->fetchAllRows();
	var_dump($users);
	
	echo '===fetchRow===', PHP_EOL;
	$user = $handler
		->prepare('SELECT id, name, access_level FROM test WHERE id = ?')
		->execute([2])
		->fetchRow();
	var_dump($user);
	
	echo '===fetchColumn===', PHP_EOL;
	$name = $handler
		->prepare('SELECT name FROM test WHERE id = ?')
		->execute([2])
		->fetchColumn();
	var_dump($name);
	
	echo '===fetchAllRowsOfColumn===', PHP_EOL;
	$stmt = $handler
		->prepare('SELECT SQL_CALC_FOUND_ROWS name FROM test WHERE access_level = ? LIMIT 10')
		->execute([10]);
	$names = $stmt->fetchAllRowsOfColumn();
	var_dump($names);
	
	echo '===getFoundRows===', PHP_EOL;
	$totalFound = $stmt->getFoundRows();
	var_dump($totalFound);
	
	echo '===forEachRow===', PHP_EOL;
	$handler
		->prepare('SELECT id, name, access_level FROM test WHERE name LIKE ? LIMIT 10')
		->execute(['foo%'])
		->forEachRow(
			function (array $row, $index)
			{
				echo $index, '. ', $row['name'], ' (ID=', $row['id'], ', access level=', $row['access_level'], ')', PHP_EOL;
			}
		);
	
	echo '===map===', PHP_EOL;
	$users = $handler
		->prepare('SELECT id, name FROM test WHERE name LIKE ? LIMIT 10')
		->execute(['foo%'])
		->map(
			function ($row)
			{
				return "[{$row['id']}] {$row['name']}";
			}
		);
	var_dump($users);
}
catch (DbException $e)
{
	echo $e->getMessage(), '<br/>';
	
	if ($e instanceof QueryException)
	{
		echo 'Query: ', $e->getQuery(), '<br/>';
	}
	
	print_r($e->getErrorInfo());
}

try
{
	$handler->exec('DROP TABLE IF EXISTS test'); // cleanup
}
catch (DbException $e)
{
}
