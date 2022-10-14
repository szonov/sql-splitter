# szonov/sql-splitter
Library for parsing strings with multiple sql queries and split it to single queries

For now supported:
* Mysql (can be used for sqlite)
* Postgresql

Usage Example :: full (from string)
-------------

```php

include "vendor/autoload.php";

use SZonov\Text\Source\Text as Input;
use SZonov\SQL\Splitter\Postgresql as Parser;
use SZonov\Text\Parser\ParserIterator as Queries;

$sql = "CREATE TABLE a (id SERIAL PRIMARY KEY, val TEXT);"
     . "INSERT INTO a (val) VALUES ('myval');";
$input   = new Input($sql);
$parser  = new Parser($input);
$queries = new Queries($parser);

foreach ($queries as $query) {
    // make something useful with single query
    echo "[" . $query . "]\n";
}

```

Usage Example :: short (from file)
-------------

```php

include "vendor/autoload.php";

use SZonov\SQL\Splitter\Postgresql as Parser;
use SZonov\Text\Parser\ParserIterator as Queries;

$queries = new Queries(Parser::fromFile('test.sql'));

foreach ($queries as $query) {
    // make something useful with single query
    echo "[" . $query . "]\n";
}

```

Usage Example :: V2+ syntax
-------------

```php

include "vendor/autoload.php";

use SZonov\SQL\Splitter\Parser;

//$queries = Parser::fromFileUsingDriver('test.sql', 'mysql')->queries();
$queries = Parser::fromFileUsingDriver('test.sql', 'pgsql')->queries();

foreach ($queries as $query) {
    // make something useful with single query
    echo "[" . $query . "]\n";
}
```