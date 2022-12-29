# monolog-extensions
Additional handlers, formatters and processors for use with Monolog
(at the moment only PDOHandler)

## Installation

```bash
$ composer require dansol/monolog-extensions
```

## PDO Handler
Allows to store log messages to Database via PDO.
The Handler can manage record mapping to match custom database table/fields

example:

```php
use MonologExtensions\Handler\PDOHandler;
use Monolog\Logger;

...

// PDO
try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

//---------------------------------------------------------------------
// Logger Initialization 
// *** example*** adapat tablename, dateformat, mapping to your needs

// tb_log ->([Date] [datetime] NULL,[Type] [nvarchar](50) NULL,[Event] [nvarchar](max) NULL,[UserName] [nvarchar](50) NULL) 
$dbTableName="Tb_Logs";

// database date format
$databaseDateFormat="Y-m-d H:i:s";

// map log properties to database table fields
$mapping = [
    'datetime' 	=> 'date',
    'level'  	=> 'type',
    'message'   => 'event',
    'context'	=> [
    	'username'  => 'username' // custom fields/info
    ] 
];

// monolog new pdo Handler
$pdoHandler= new PDOHandler($pdo,$dbTableName,$databaseDateFormat,$mapping);
				
$logger = new Logger('channel name');
$logger->pushHandler($pdoHandler, \Monolog\Logger::DEBUG );
//----------------------------------------------------------------

....

// write log example
$this->logger->info('successfully login for user ' . $identity ,['username'=>$identity]);


```