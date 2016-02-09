Olive 0.25.4
============

Olive is a database library that aims to handle several databases with one simple API. It is designed for small to medium projects that don't need to be highly optimized because the API just supports the common tasks.

This project is a proof-of-concept that can be useful for many developers.

Even if you can switch between different database types (like MySQL and MongoDB) with the same project and the same data structure, I __do not__ encourage anyone to do it. Each database system has its own pros and cons and you should choose wisely what to use for your needs. Moreover, some methods on the API can be greedy, like `join()` with MongoDB which runs one additional request per join to retrieve data.

Please not that MongoDB is relation-less and [should not be used with relational data structures](http://www.sarahmei.com/blog/2013/11/11/why-you-should-never-use-mongodb/).

Installation
------------

```json
composer require pyrsmk/olive
```

Features
--------

- CRUD operations
- querying with support for AND/OR operators
- select fields
- create aliases
- joining
- sorting
- limit and skip results
- support for regexes
- namespaces
- simple ORM/ODM support

Create database connection
--------------------------

Creating a database connection works the same over all database adapters, it takes a database name as first argument and an array of options as second. Here's the available options for each database object :

- [Olive\4d](http://php.net/manual/en/ref.pdo-4d.connection.php)
- [Olive\Cubrid](http://php.net/manual/en/ref.pdo-cubrid.connection.php)
- [Olive\Firebird](http://php.net/manual/en/ref.pdo-firebird.connection.php)
- [Olive\Freetds](http://php.net/manual/en/ref.pdo-dblib.connection.php)
- [Olive\Ibm](http://php.net/manual/en/ref.pdo-ibm.connection.php)
- [Olive\Informix](http://php.net/manual/en/ref.pdo-informix.connection.php)
- [Olive\Mariadb](http://php.net/manual/en/ref.pdo-mysql.connection.php) (just an alias of `Olive\Mysql`)
- [Olive\Mongodb](http://php.net/manual/en/mongoclient.construct.php)
- [Olive\Mssql](http://php.net/manual/en/ref.pdo-dblib.connection.php) (Microsoft SQL Server)
- [Olive\Mysql](http://php.net/manual/en/ref.pdo-mysql.connection.php)
- [Olive\Odbc](http://php.net/manual/en/ref.pdo-odbc.connection.php)
- [Olive\Oracle](http://php.net/manual/en/ref.pdo-oci.connection.php)
- [Olive\Postgresql](http://php.net/manual/en/ref.pdo-pgsql.connection.php)
- [Olive\Sqlite](http://php.net/manual/en/ref.pdo-sqlite.connection.php)
- [Olive\Sqlsrv](http://php.net/manual/en/ref.pdo-sqlsrv.connection.php) (MS SQL Server and SQL Azure)
- [Olive\Sybase](http://php.net/manual/en/ref.pdo-dblib.connection.php)

### MongoDB

```php
// Create a connection to a database on localhost using default 27017 port
$olive=new Olive\Mongodb('my_database', array(
    'username' => 'root',
    'password' => 'blahblahblah'
));

// Create a connection to mongodb.example.com:67095 (you can easily add more hosts if you want)
$olive=new Olive\Mongodb('my_database', array(
    'username' => 'root',
    'password' => 'blahblahblah',
    'hosts' => array(
        'mongodb.example.com' => 67095
    )
));
```

### MySQL

```php
// Create a connection on localhost ('host' option is optional)
$olive=new Olive\Mysql('my_database', array(
    'username' => 'root',
    'password' => 'blahblahblah',
    'host' => 'localhost'
));
```

### SQLite

```php
// Create a simple connection with SQLite3
$olive=new Olive\Sqlite('path/to/database.db');

// Create a connection with SQLite2
$olive=new Olive\Sqlite('path/to/database.db', array(
    'sqlite2' => true
));
```

Get data containers
-------------------

A data container is an abstraction class for a table or a collection, per example. Data containers are the entry point to create queries. We can retrieve them with a simple call to :

```php
$olive = new Olive\MariaDB('my_database', $options);

// Get the users table
$container = $olive->users;
```

If your table has a weird name, you can get it anyway with :

```php
$container = $olive['some_weird#table;name'];
```

We strongly advise you to use namespaces in your applications so that your database is not pollute by random tables and avoid incompatibility issues between different applications/websites :

```php
$container = $olive->my_app_users;
```

In Olive, you can specify a global namespace for simplicity :

```php
// Set the global namespace
$olive->setNamespace('my_app_');
// Get the global namespace
$olive->getNamespace();
```

If you need to remove the global namespace :

```php
$olive->setNamespace(null);
```

CRUD operations
---------------

The `insert()`, `update()`, `save()` and `remove()` are used for basic CRUD operations. These methods can accept an additional argument : an array of options for the driver. Please refer to the related PHP documentation pages (in PDO or MongoDB chapters) to have further information about it.

```php
// Insert data
$new_id = $olive->people->insert(array(
    'firstname' => 'John',
    'lastname' => 'Doe',
    'age' => 52
));

// Update data
$olive->people
      ->search('_id', 'is', $new_id)
      ->update(array(
            'firstname' => 'John',
            'lastname' => 'Doe',
            'age' => 52
      ));

// Save data
$olive->people->save(array(
    '_id' => 123,
    'firstname' => 'John',
    'lastname' => 'Doe',
    'age' => 52
));

// Remove data
$olive->people
      ->search('_id', 'is', $new_id)
      ->remove();
```

Querying
--------

### Searching & fetching

As you have seen, searches use a simple syntax to handle conditional operators. These operators are :

- `is` : equality operator
- `is not` : non-equality operator
- `less` : the field value is less than the specified value
- `greater` : the field value is greater than the specified value
- `in` : verify if the field value is in the specified array
- `not in` : verify if the field value is not in the specified array
- `like` : use the [LIKE SQL syntax](https://mariadb.com/kb/en/mariadb/like/) to match a field against a pattern
- `not like` : use the [LIKE SQL syntax](https://mariadb.com/kb/en/mariadb/like/) to verify if the field does not match the provided pattern
- `match` : use regexes to match a string against a pattern
- `not match` : use regexes to verify if the field does not match the provided pattern

Take a look at how we're getting results :

```php
$ids = array(14, 51, 20, 18);

// Search for articles with an ID that is not in the $ids array
$olive->articles
      ->search('_id', 'not in', $ids)
      ->fetch();

// Get all articles
$olive->articles
      ->search()
      ->fetch();
```

The `fetch()` method retrieves all results. But there's other methods like `fetchOne()` which get the first row in the results and `fetchFirst()` that get the first field of the first row. Let's see a concrete example for that case :

```php
// Get the title of the article with the 72 ID
$title=$olive->articles
             ->search('_id', 'is', 72)
             ->select('title')
             ->fetchFirst();
```

There's also direct methods to search and retrieve in one call :

```php
// Get articles written by '@pyrsmk'
$olive->articles->find('author_id', 'is', '@pyrsmk');
// Get one article
$olive->articles->findOne('_id', 'is', 10);
// Get the first field of the requested article
$olive->articles->findFirst('_id', 'is', 10);
```

But please note that `findFirst()` is here for API consistency. Since we're not selecting any field, all of them are returned and the first field is often the ID of the row.

Of course, you can specify several searches in one request. Each search will be appended to the request with a `AND` operator.

```php
// Let's get admins less older than 50 yo
$title=$olive->members
             ->search('group', 'is', 'admins')
             ->search('age', 'less', '50')
             ->fetch();
```

Let's get a further look how searching works. In fact, each call to `search()` will be concatenated with `AND` operators. But we sometimes need to add an `OR` clause to the query. It is obtained by calling the `orSearch()` method :

```php
$olive->articles->search('author_id', 'is', '@pyrsmk')
                ->orSearch('author_id', 'is', '@dreamysource')
                ->orSearch('author_id', 'is', '@4lbl');
```

All `orSearch()` clauses will be appended to the previous search.

Last note on this subject : calling `search()` returns a new `Query` object. It could happen that you need, per example, to do a loop and add a search at each cycle. In that case, you'll need to get a new query before calling `search()` :

```php
$query = $olive->my_table
			   ->query();

foreach($data as $name => $value) {
	$query->search($name, 'is', $value);
}

$results = $query->fetch();
```

### Select fields & set aliases

```php
// Get title, text, author and date fields
$article = $olive->articles
                 ->search('_id', 'is', 72)
                 ->select('title')
                 ->select('text')
                 ->select('author')
                 ->select('date')
                 ->fetchOne();

// The second parameter of select() is the alias
$item = $olive->items
              ->search('iditem', 'is', 72)
              ->select('iditem', '_id')
              ->select('text', 'french')
              ->select('title', 'h1)
              ->fetchOne();
```

With a SQL database, you could need to set aliases for several tables in your query to avoid conflicts :

```php
$results = $olive->categories
				 ->search('root.idparent', 'is', $id)
				 ->from('categories', 'root')
				 ->from('categories', 'subcategories')
				 ->join('root.idparent', 'subcategories.idcat')
				 ->join('subcategories.idcat', 'items.idcat')
				 ->select('subcategories.idcat', '_id')
				 ->select('items.title')
				 ->join('items.idimg', 'images.idimg')
				 ->select('images.uriimg', 'image')
				 ->fetch();
```

### Join

```php
// Get articles from two weeks ago, with the author name
$olive->articles
      ->search('date', 'greater', time() - 1209600)
      ->join('articles.author_id', 'members.id')
      ->select('title')
      ->select('text')
      ->select('date')
      ->select('members.name', 'author')
      ->fetch();
```

### Sort

The `sort()` method takes the field and the sorting direction as arguments. The direction is either `asc` or `desc`.

```php
$olive->articles
      ->search()
      ->sort('date', 'desc')
      ->fetch();
```

### Limit

```php
// Get the 10 newest articles
$olive->articles
      ->search()
      ->sort('date', 'desc')
      ->limit(10)
      ->fetch();
```

### Skip

Skipping results is useful when using `limit()` for pagination.

```php
// Get articles for the page 3
$olive->articles
      ->search()
      ->sort('date', 'desc')
      ->limit(10)
      ->skip(20)
      ->fetch();
```

### Count

For ease of use, you can directly count how many results that a search should return.

```php
$olive->articles
      ->search('date', 'greater', time() - 1209600)
      ->count();
```

Create models
-------------

To simplify your models and have a nice object-oriented API, you can extend `Olive\Model`. The constructor takes an `Olive` object as argument and expects that `$singular`, `$plural`, `$data_container` and `$primary_key` class properties are well defined. Let's say we have a `users` table that we want to map, here's how we're defining it :

```php
class MyUsersModel extends Olive\Model{
    // 'singular' and 'plural' properties are used in calls (see the method below)
    protected $singular='user';
    protected $plural='users';
	
    // Define the data container name (AKA table or collection name)
    protected $data_container='users';
	
    // Define the primary key name
    protected $primary_key='_id';
}
```

That's all we need for a simple model. But you often need specific queries to optimize things. You can just do it by adding a new method to your class, like `getThoseFuckingWeirdResults()`.

Here's the exhaustive list of the methods you can natively call (replace `singular` and `plural` parameters by those you defined in your class) :

- `<singular>Exists($id)` : verify if an element with the provided id exists (ex : `userExists(123)`)
- `<singular>Exists($search)` : verify if an element with the provided search exists (ex : `userExists(array('email'=>'account@email.com'))`)
- `<singular>ExistsBy<SearchField>($value)` : verify if an element exists by verifying one of its field (ex : `userExistsByEmail('account@email.com')`)
- `<plural>Exist($search)` : verify if several elements exist (ex : `usersExist(array('name'=>'Thomas'))`)
- `<plural>ExistBy<SearchField>($value)` : verify if several elements exist against a field value (ex : `usersExistByName('Thomas')`)
- `count<Plural>()` : count results (ex : `countUsers()`)
- `count<Plural>($search)` : count results (ex : `countUsers('name','is','Thomas')`)
- `count<Plural>By<SearchField>($value)` : count results by searching a field (ex : `countUsersByName('Thomas')`)
- `insert<Singular>($data)` : insert data (ex : `insertUser($data)`)
- `insert<Plural>($data)` : insert several rows (ex : `insertUsers($data)`)
- `add<Singular>($data)` : alias of `insert<Singular>`
- `add<Plural>($data)` : alias of `insert<Plural>`
- `get<Singular>($id, $fields)` : get a row by its id (ex : `getUser(72)`)
- `get<Singular>($search, $fields)` : get a row by a specific search  (ex : `getUser(array('email'=>'account@email.com'))`)
- `get<Singular><Field>($id)` : get a field by id (ex : `getUserEmail(72)`)
- `get<Singular><Field>($search)` : get a field by a specific search (ex : `getUserEmail(array('name' => 'Thomas'))`)
- `get<Singular>By<SearchField>($value, $fields)` : get at row by a field (ex : `getUserByName('Thomas')`)
- `get<Singular><Field>By<SearchField>($value)` : get a field by a search on another field (ex : `getUserEmailByName('Thomas')`)
- `get<Plural>($search, $fields)` : search for several rows (ex : `getUsers(array('status' => 'admin'))`)
- `get<Plural><Field>($search)` : search for several rows but retrieve one field (ex : `getUsersEmail(array('status' => 'admin'))`)
- `get<Plural>By<SearchField>($value, $fields)` : search for several rows by a specific field (ex : `getUsersByStatus('admin')`)
- `get<Plural><Field>By<SearchField>($value)` : search for several rows by a specific field, and return one field per row (ex : `getUsersEmailByStatus('admin')`)
- `update<Singular>($id, $data)` : update an ID specific element (ex : `updateUser(72, $data)`)
- `update<Singular>($search, $data)` : update an element with a search (ex : `updateUser(array('_id' => 72), $data)`)
- `update<Singular><Field>($id, $value)` : update a specific field of an element (ex : `updateUserName(72,'Pierre')`)
- `update<Singular><Field>($search, $value)` : update a specific field of an element with a search (ex : `updateUserName(array('_id' => 72), 'Pierre')`)
- `update<Singular>By<SearchField>($value, $data)` : update an element by searching a field (ex : `updateUserById(72, $data)`)
- `update<Singular><Field>By<SearchField>($search_value, $field_value)` : update the field of an element by searching another field (ex : `updateUserNameById(72, 'Pierre')`)
- `update<Plural>($search, $data)` : update several elements (ex : `updateUsers(array('name' => 'Pierre'), $data)`)
- `update<Plural><Field>($search, $value)` : update several element fields (ex : `updateUsersName(array('name' => 'Pierre'), 'Jacques')`)
- `update<Plural>By<SearchField>($value, $data)` : update several elements by searching a field (ex : `updateUsersByName('Pierre', $data)`)
- `update<Plural><Field>By<SearchField>($search_value, $field_value)` : update several elements field by searching another field (ex : `updateUsersNameByName('Pierre', 'Jacques')`)
- `save<Singular>($data)` : save data (ex : `saveUser($data)`)
- `set<Singular>($data)` : alias of `save<Singular>`
- `remove<Singular>($id)` : remove an element (ex : `removeUser(72)`)
- `remove<Singular>($search)` : remove an element by search (ex : `removeUser(array('_id' => 72))`)
- `remove<Singular>By<SearchField>($value)` : remove an element by searching a field (ex : `removeUserByEmail('example@mail.com')`)
- `remove<Plural>($search)` : remove several elements (ex : `removeUsers(array('name' => 'Pierre'))`)
- `remove<Plural>By<SearchField>($value)` : remove several elements by searching a field  (ex : `removeUsersByName('Pierre')`)
- `delete<Singular>($id)` : alias of `remove<Singular>`
- `delete<Singular>($search)` : alias of `remove<Singular>`
- `delete<Singular>By<SearchField>($value)` : alias of `remove<Singular>By<SearchField>`
- `delete<Plural>($search)` : alias of `remove<Plural>`
- `delete<Plural>By<SearchField>($value)` : alias of `remove<Plural>By<SearchField>`

We should take a look at all those variables defined in the API. Most of them talk by themselves but not `$search` or `$fields`. The `$search` parameter is an associative array that lists the fields with the value to match for the query :

```php
// Remove any user with their email and name fields set to 'pwet@example.com' and 'Thomas' respectively
$userModel->removeUsers(array(
    'email' => 'pwet@example.com',
    'name' => 'Thomas'
));
```

The `$fields` parameter is also an associative array that lists the fields to retrieve and maps aliases :

```php
// Get an user with the following fields : '_id', 'email', 'date' (alias of 'user_creation') and 'text' (alias of 'profile_text')
$userModel->getUser($id,array(
    '_id',
    'email',
    'user_creation' => 'date'
    'profile_text' => 'text'
));
```

Advanced use
------------

```php
// Get table/collection names
$names = $olive->getDataContainerNames();

// Get database object (like PDO, MongoClient, ...)
$driver = $olive->getDriver();

// Verify adapter support
if(Olive\Mysql::isSupported()) {
	// MySQL is currently supported in the PHP environment
}
```

Last notes
----------

- you may want to use `_id` as default primary key for your tables because it's the key used in MongoDB, so you can use it across you applications regardless of the database in use
- in MongoDB all `_id` primary keys are an instance of `ObjectId`, in Olive we automatically stringify the id and create objects when needed : shortly, you don't need to bother about `ObjectId` at all

License
-------

Olive is released under the [MIT license](http://dreamysource.mit-license.org).