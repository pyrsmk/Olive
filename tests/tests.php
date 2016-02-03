<?php

use Symfony\Component\ClassLoader\ClassLoader;

########################################################### Prepare

error_reporting(E_ALL);

require 'vendor/autoload.php';

$loader = new ClassLoader;
$loader->register();
$loader->addPrefix('Olive', __DIR__.'/../src/');

$minisuite = new MiniSuite('Olive');

########################################################### Base

$test_base = function($olive) use($minisuite) {
	$minisuite->expects('Get a data container [1]')
			  ->that($olive->users)
			  ->isInstanceOf('Olive\AbstractDataContainer');

	$minisuite->expects('Get a data container [2]')
			  ->that($olive['users'])
			  ->isInstanceOf('Olive\AbstractDataContainer');

	$olive->setNamespace('olive_');
	$minisuite->expects('Set/get namespace')
			  ->that($olive->getNamespace())
			  ->equals('olive_');

	$minisuite->expects('Retrieve a namespaced data container')
			  ->that($olive->groups)
			  ->isInstanceOf('Olive\AbstractDataContainer');

	$names = $olive->getDataContainerNames();
	sort($names);
	$minisuite->expects('Get container names')
			  ->that($names)
			  ->equals(array('olive_articles', 'olive_users'));
};

########################################################### Insert

$test_insert = function($olive) use($minisuite) {
	$johndoe = $olive->users->insert(array(
		'username' => 'JohnDoe',
		'gender' => 'M',
		'age' => 25
	));
	
	$janedoe = $olive->users->insert(array(
		'username' => 'JaneDoe',
		'gender' => 'F',
		'age' => 22
	));
	
	$janettedoe = $olive->users->insert(array(
		'username' => 'JanetteDoe',
		'gender' => 'F',
		'age' => 5
	));
	
	$olive->articles->insert(array(
		'tag' => 'cooking',
		'date' => mktime(12, 00, 00, 02, 01, 2016),
		'title' => 'New recipe : rice and fried salmon',
		'user_id' => $johndoe
	));
	
	$olive->articles->insert(array(
		'tag' => 'cooking',
		'date' => mktime(21, 10, 53, 10, 03, 2013),
		'title' => 'New recipe : chocolate/lemon cake',
		'user_id' => $janedoe
	));
	
	$olive->articles->insert(array(
		'tag' => 'people',
		'date' => mktime(10, 58, 02, 18, 01, 2014),
		'title' => 'Paris Hilton loves recipes!',
		'user_id' => $janedoe
	));
};

########################################################### Search

$test_search = function($olive) use($minisuite) {
	$minisuite->expects('Search condition : is')
			  ->that(count($olive->users->search('gender', 'is', 'M')->fetch()))
			  ->equals(1);
	
	$minisuite->expects('Search condition : is not')
			  ->that(count($olive->users->search('gender', 'is not', 'M')->fetch()))
			  ->equals(2);
	
	$minisuite->expects('Search condition : less')
			  ->that(count($olive->users->search('age', 'less', 20)->fetch()))
			  ->equals(1);
	
	$minisuite->expects('Search condition : greater')
			  ->that(count($olive->users->search('age', 'greater', 20)->fetch()))
			  ->equals(2);
	
	$minisuite->expects('Search condition : in')
			  ->that(count($olive->articles->search('tag', 'in', array('cooking', 'people'))->fetch()))
			  ->equals(3);
	
	$minisuite->expects('Search condition : not in')
			  ->that(count($olive->articles->search('tag', 'not in', array('cooking', 'geo'))->fetch()))
			  ->equals(1);
	
	$minisuite->expects('Search condition : like')
			  ->that(count($olive->articles->search('title', 'like', '%recipe%')->fetch()))
			  ->equals(3);
	
	$minisuite->expects('Search condition : not like')
			  ->that(count($olive->articles->search('title', 'not like', '%recipes%')->fetch()))
			  ->equals(2);
	
	try {
		$minisuite->expects('Search condition : match')
				  ->that(count($olive->articles->search('title', 'match', '^New recipe')->fetch()))
				  ->equals(2);

		$minisuite->expects('Search condition : not match')
				  ->that(count($olive->articles->search('title', 'not match', '.+recipe.+')->fetch()))
				  ->equals(0);
	}
	catch(Exception $e) {}
};

########################################################### From

$test_from = function($olive) use($minisuite) {
	$results = $olive->articles
					 ->search()
					 ->from('articles', 'tickets')
					 ->join('tickets.user_id', 'users._id')
					 ->fetch();
	
	$minisuite->expects('From')
			  ->that(count($results))
			  ->equals(3);
};

########################################################### Select/aliases

$test_search_select = function($olive) use($minisuite) {
	$results = $olive->users
					 ->search('gender', 'is', 'M')
					 ->select('username', 'user')
					 ->fetchOne();
	
	$minisuite->expects('Select')
			  ->that(count($results))
			  ->equals(1);

	$minisuite->expects('Alias')
			  ->that($results['user'])
			  ->equals('JohnDoe');
};

########################################################### Join

$test_search_join = function($olive) use($minisuite) {
	$results = $olive->articles
					 ->search('age', 'greater', '20')
					 ->join('articles.user_id', 'users._id')
					 ->fetch();
	
	$minisuite->expects('Simple join')
			  ->that(count($results))
			  ->equals(3);
};

########################################################### Sort

$test_search_sort = function($olive) use($minisuite) {
	$minisuite->expects('Sort : asc')
			  ->that($olive->users->search()->select('username')->sort('age', 'asc')->fetch())
			  ->isTheSameAs(array(
				  array('username'=>'JanetteDoe'),
				  array('username'=>'JaneDoe'),
				  array('username'=>'JohnDoe'),
			  ));
	
	$minisuite->expects('Sort : desc')
			  ->that($olive->users->search()->select('username')->sort('username', 'desc')->fetch())
			  ->isTheSameAs(array(
				  array('username'=>'JohnDoe'),
				  array('username'=>'JanetteDoe'),
				  array('username'=>'JaneDoe'),
			  ));
};

########################################################### Limit/skip

$test_search_limit = function($olive) use($minisuite) {
	$minisuite->expects('Limit/skip')
			  ->that($olive->users->search()->select('username')->sort('age', 'asc')->limit(1)->skip(1)->fetch())
			  ->isTheSameAs(array(
				  array('username'=>'JaneDoe')
			  ));
};

########################################################### Count

$test_search_count = function($olive) use($minisuite) {
	$minisuite->expects('Count')
			  ->that($olive->users->search()->count())
			  ->equals(3);
};

########################################################### Update

$test_update = function($olive) use($minisuite) {
	$olive->users
		  ->search('username', 'is', 'JohnDoe')
		  ->update(array('age' => 26));
	
	$minisuite->expects('Update')
			  ->that($olive->users->search('age', 'is', 26)->select('username')->fetchFirst())
			  ->equals('JohnDoe');
};

########################################################### Save

$test_save = function($olive) use($minisuite) {};

########################################################### Remove

$test_remove = function($olive) use($minisuite) {
	$olive->users
		  ->search('username', 'is', 'JohnDoe')
		  ->remove();
	
	$minisuite->expects('Remove')
			  ->that($olive->users->search()->count())
			  ->equals(2);
};

########################################################### Models

$test_models = function($olive) use($minisuite) {};

########################################################### Define run()

$run_tests = function($name, $olive) use($minisuite, $test_base, $test_insert, $test_search, $test_search_select, $test_search_join, $test_search_sort, $test_search_limit, $test_search_count, $test_from, $test_update, $test_save, $test_remove, $test_models) {
	
	$minisuite->group($name, function($minisuite) use($olive, $test_base, $test_insert, $test_search, $test_search_select, $test_search_join, $test_search_sort, $test_search_limit, $test_search_count, $test_from, $test_update, $test_save, $test_remove, $test_models) {
		
		$test_base($olive);
		$test_insert($olive);
		$test_search($olive);
		$test_search_select($olive);
		$test_search_join($olive);
		$test_search_sort($olive);
		$test_search_limit($olive);
		$test_search_count($olive);
		$test_from($olive);
		$test_update($olive);
		$test_save($olive);
		$test_remove($olive);
		$test_models($olive);
		
	});
	
};

########################################################### Run MongoDB tests

if(Olive\Mongodb::isSupported()) {
	$olive = new Olive\Mongodb('tests', array(
		'username' => 'root',
		'password' => 'shalanla'
	));

	$driver = $olive->getDriver();
	$driver->users->drop();
	$driver->articles->drop();
	$driver->createCollection('users');
	$driver->createCollection('articles');

	$run_tests('MongoDB', $olive);

	$driver->users->drop();
	$driver->articles->drop();
}

########################################################### Run MySQL tests

if(Olive\Mysql::isSupported()) {
	$olive = new Olive\Mysql('tests', array(
		'username' => 'root',
		'password' => 'shalanla'
	));

	try {
		$driver = $olive->getDriver();
		$driver->exec('DROP TABLE IF EXISTS `olive_users`');
		$driver->exec('DROP TABLE IF EXISTS `olive_articles`');
		$driver->exec(file_get_contents(__DIR__.'/tables/users.sql'));
		$driver->exec(file_get_contents(__DIR__.'/tables/articles.sql'));
	}
	catch(Exception $e) {}

	$run_tests('MySQL', $olive);

	try {
		$driver->exec('DROP TABLE `olive_users`');
		$driver->exec('DROP TABLE `olive_articles`');
	}
	catch(Exception $e) {}
}

########################################################### Run SQLite tests

if(Olive\Sqlite::isSupported()) {
	$olive = new Olive\Sqlite(__DIR__.'/sqlite.db');
	
	$formatSql = function($sql) {
		return str_replace(
			array('VARCHAR(255)', 'AUTO_INCREMENT', 'INT'),
			array('TEXT', 'AUTOINCREMENT', 'INTEGER'),
			$sql
		);
	};

	try {
		$driver = $olive->getDriver();
		$driver->exec('DROP TABLE IF EXISTS `olive_users`');
		$driver->exec('DROP TABLE IF EXISTS `olive_articles`');
		$driver->exec($formatSql(file_get_contents(__DIR__.'/tables/users.sql')));
		$driver->exec($formatSql(file_get_contents(__DIR__.'/tables/articles.sql')));
	}
	catch(Exception $e) {}

	$run_tests('SQLite', $olive);

	try {
		$driver->exec('DROP TABLE `olive_users`');
		$driver->exec('DROP TABLE `olive_articles`');
	}
	catch(Exception $e) {}
}