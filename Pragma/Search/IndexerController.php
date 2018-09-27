<?php
namespace Pragma\Search;

use Pragma\Search\Processor;
use Pragma\Helpers\TaskLock;
use Pragma\Search\ElasticSearch\ElasticSearch;
use Pragma\Search\ElasticSearch\Processor as ProcessorElasticSearch;

class IndexerController{
	public static function run(){
		TaskLock::check_lock(realpath('.').'/locks', 'indexer');

		self::loadClasses();
		Processor::index_pendings();

		TaskLock::flush(realpath('.').'/locks', 'indexer');
	}

	public static function rebuild(){
		TaskLock::check_lock(realpath('.').'/locks', 'indexer');

		self::loadClasses();
		Processor::rebuild();

		TaskLock::flush(realpath('.').'/locks', 'indexer');
	}

	protected static function loadClasses(){
		ini_set('max_execution_time',0);
		ini_set('memory_limit',"2048M");

		$loader = require realpath(__DIR__.'/../../../..') . '/autoload.php';
		$classes = array_keys($loader->getClassMap());

		foreach($classes as $c){
			if(strpos($c, '\\Models\\') !== false){
				if (in_array('Pragma\\Search\\Searchable', class_uses($c))){
					new $c();
				}
			}
		}
	}

	public static function rebuild2(){
		TaskLock::check_lock(realpath('.').'/locks', 'indexer');

		self::loadClasses();
		ProcessorElasticSearch::rebuild();

		TaskLock::flush(realpath('.').'/locks', 'indexer');
	}
}