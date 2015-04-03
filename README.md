# dbhandler

Requirements: PHP 5.4+

These classes are made to simplify database operations via the PHP PDO interface.

Using PDO's native methods makes for a lot of code bloat, so I've made this project
to avoid that. There are also some pitfalls that I've worked around.

To work with these classes, include the provided autoloader in your bootloader
and specify the database connection parameters in the getInstance() method
of the Handler class.

Code examples:

```php
try
{
	// example #1 - no parameters or fixed parameters, no return value
	\database\Handler::getInstance()
		->exec('TRUNCATE TABLE abc');
	\database\Handler::getInstance()
		->exec('UPDATE abc SET col1 = 2 WHERE col1 = 1');
	
	// example #2 - fixed parameters with return value
	$name = \database\Handler::getInstance()
		->query('SELECT name FROM abc WHERE id = 1');
	
	// example #3 - variable parameters with return value
	$id = 2;
	$name = \database\Handler::getInstance()
		->prepare('SELECT name FROM abc WHERE id = ?')
		->execute(array($id))
		->fetchColumn();
	
	$users = \database\Handler::getInstance()
		->prepare('SELECT id, name, accesslevel FROM abc WHERE something = ? LIMIT 10')
		->execute(array($something))
		->fetchAllRows();
	// $users = [ [ 'id' => 1, 'name' => 'something', 'accesslevel' => 1 ], ... ]
	
	$stmt = \database\Handler::getInstance()
		->prepare('SELECT SQL_CALC_FOUND_ROWS name FROM abc WHERE something = ? LIMIT 10')
		->execute(array($something));
	$names = $stmt->fetchAllRowsOfColumn();
	// $names = [ 'foo', 'bar', ... ]
	$totalFound = $stmt->getFoundRows();
	
	\database\Handler::getInstance()
		->prepare('SELECT id, name, accesslevel FROM abc WHERE something = ? LIMIT 10')
		->execute(array($something))
		->forEachRow(function (array $row, $index)
		{
			echo ($index + 1), '. ', $row['name'], ' (', $row['id'], ')<br/>';
		});
	
	$users = \database\Handler::getInstance()
		->prepare('SELECT id, name, accesslevel FROM abc WHERE something = ? LIMIT 10')
		->execute(array($something))
		->map(function ($id, $name, $accesslevel)
		{
			// [id] name (access)
			return "[{$id}] {$name} (" . getAccessLevelName($accesslevel) . ')';
		});
}
catch (DbException $e)
{
	echo $e->getMessage(), '<br/>',
		'Query: ', $e->getQuery(), '<br/>';
	print_r($e->getErrorInfo());
}
```
