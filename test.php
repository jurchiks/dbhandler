<?php
// composer code:
// require __DIR__ . '/vendor/autoload.php';
// non-composer code (can run this in console immediately):
spl_autoload_register(
	function ($className)
	{
		$ds = DIRECTORY_SEPARATOR;
		$className = str_replace('js\\tools\\dbhandler', '', $className);
		$className = str_replace('\\', $ds, $className);
		$className = trim($className, $ds);
		
		$path = __DIR__ . $ds . 'src' . $ds . $className . '.php';
		
		if (!is_readable($path))
		{
			return false;
		}
		
		require $path;
		return true;
	},
	true
);
// common code:
use \js\tools\dbhandler\exceptions\DbException;
use \js\tools\dbhandler\Handler;

try
{
	// TODO set your own test connection parameters!
	$handler = Handler::getConnection('test', [
		'driver' => 'mysql',
		'username' => 'test',
		'password' => 'test',
		'database' => 'test',
	]);
}
catch (DbException $e)
{
	echo $e->getMessage(), PHP_EOL;
	die();
}

try
{
	$handler->exec('CREATE TABLE IF NOT EXISTS test (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			name VARCHAR(255) NOT NULL,
			access_level INT NOT NULL,
			creation_time DATETIME NOT NULL,
			UNIQUE (name)
		) ENGINE InnoDB CHARSET utf8 COLLATE utf8_unicode_ci');
	
	$insertStmt = $handler->prepare('INSERT IGNORE INTO test
		(name, access_level, creation_time)
		VALUES
		(?, ?, NOW())');
	$data = [
		[ 'foo1', 1 ],
		[ 'foo2', 1 ],
		[ 'foo3', 10 ],
		[ 'foo4', 10 ],
		[ 'bar1', 100 ],
		[ 'bar2', 100 ],
	];
	
	foreach ($data as $user)
	{
		$insertStmt->execute($user);
	}
	
	$users = $handler
		->prepare('SELECT id, name, access_level FROM test WHERE name LIKE ? LIMIT 10')
		->execute([ 'foo%' ])
		->fetchAllRows();
	echo '===fetchAllRows===', PHP_EOL;
	var_dump($users);
	
	$user = $handler
		->prepare('SELECT id, name, access_level FROM test WHERE id = ?')
		->execute([ 2 ])
		->fetchRow();
	echo '===fetchRow===', PHP_EOL;
	var_dump($user);
	
	$name = $handler
		->prepare('SELECT name FROM test WHERE id = ?')
		->execute([ 2 ])
		->fetchColumn();
	echo '===fetchColumn===', PHP_EOL;
	var_dump($name);
	
	$stmt = $handler
		->prepare('SELECT SQL_CALC_FOUND_ROWS name FROM test WHERE access_level = ? LIMIT 10')
		->execute([ 10 ]);
	$names = $stmt->fetchAllRowsOfColumn();
	$totalFound = $stmt->getFoundRows();
	echo '===fetchAllRowsOfColumn===', PHP_EOL;
	var_dump($names);
	echo '===getFoundRows===', PHP_EOL;
	var_dump($totalFound);
	
	echo '===forEachRow===', PHP_EOL;
	$handler
		->prepare('SELECT id, name, access_level FROM test WHERE name LIKE ? LIMIT 10')
		->execute([ 'foo%' ])
		->forEachRow(function (array $row, $index)
		{
			echo $index, '. ', $row['name'], ' (ID=', $row['id'], ', access level=', $row['access_level'], ')', PHP_EOL;
		});
	
	$users = $handler
		->prepare('SELECT id, name FROM test WHERE name LIKE ? LIMIT 10')
		->execute([ 'foo%' ])
		->map(function ($row)
		{
			return "[{$row['id']}] {$row['name']}";
		});
	echo '===map===', PHP_EOL;
	var_dump($users);
}
catch (DbException $e)
{
	echo $e->getMessage(), '<br/>',
	'Query: ', $e->getQuery(), '<br/>';
	print_r($e->getErrorInfo());
}

try
{
	$handler->exec('DROP TABLE IF EXISTS test'); // cleanup
}
catch (DbException $e)
{
}
