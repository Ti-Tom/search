<?php
namespace Pragma\Search\ElasticSearch;

use Pragma\Search\Indexed;
use Elasticsearch\ClientBuilder;
use Pragma\DB\DB;

class Processor {

	public static function rebuild(){ //repart de 0
		$es = ElasticSearch::getInstance();
		$client = $es->getClient();

		$db = DB::getDB();

		self::clean_all();

		$classes = Indexed::forge()->select(['classname'])->get_arrays();
		if(!empty($classes)){
			$mapping = [
				'index' => $es->getIndex(),
				'type' => $es->getType(),
				'body' => [
					$es->getType() => [
						'properties' => []
					]
				]];
			foreach($classes as $c){
				if(class_exists($c['classname'])){
					$obj = new $c['classname']();
					list($cols, $infile) = $obj->get_indexed_cols();
					if(!empty($cols)){
						$path = self::format_class_name($obj);

						$describe = $db->describe($obj->get_table());
						$mapping['body'][$es->getType()]['properties'][$path] = ['properties' => [], 'type' => 'nested'];
						foreach($describe as $d){
							$mapping['body'][$es->getType()]['properties'][$path]['properties'][$d['field']] = [
								 'type' => 'text'
							];
							if(array_key_exists($d['field'], $cols)){
								$mapping['body'][$es->getType()]['properties'][$path]['properties'][$d['field']]['copy_to'] = '_all_content';
							}else{
								$mapping['body'][$es->getType()]['properties'][$path]['properties'][$d['field']]['index'] = 'false';
							}
						}
						$mapping['body'][$es->getType()]['properties'][$path]['properties']['_all_content'] = ['type' => 'keyword'];
					}
				}
			}
			$client->indices()->putMapping($mapping);
			// $client->indices()->putSettings(['index' => $es->getIndex(), 'body' => ['settings' => ['number_of_shards' => 1, 'number_of_replicas' => 0]]]);

			foreach($classes as $c){
				if(class_exists($c['classname'])){
					$all = $c['classname']::all();
					if(!empty($all)){
						foreach($all as $obj){
							static::index_object($obj);
						}
					}
					$all = null;
					unset($all);
				}
			}
		}
	}

	public static function format_class_name($obj){
		return strtolower(implode('_', explode('\\', get_class($obj))));
	}

	public static function index_object($obj, $uid = null){
		if(method_exists($obj, 'get_indexed_cols')){
			$es = ElasticSearch::getInstance();
			$client = $es->getClient();
			$db = DB::getDB();

			list($cols, $infile) = $obj->get_indexed_cols();
			if(empty($cols)){
				throw new Exception("Object ".get_class($obj)." has no column to index", 1);
				return;
			}

			$path = self::format_class_name($obj);

			// check mapping
			$mappingQ = $client->indices()->getMapping(['index' => $es->getIndex(), 'type' => $es->getType()]);
			$describe = $db->describe($obj->get_table());
			if(!isset($mappingQ[$es->getIndex()]['mappings'][$es->getType()]['properties'][$path]) ||
				count(array_diff_assoc($mappingQ[$es->getIndex()]['mappings'][$es->getType()]['properties'][$path]['properties'], $describe)) > 1){ // champ _all_content
				$mapping = [
					'index' => $es->getIndex(),
					'type' => $es->getType(),
					'body' => [
						$es->getType() => [
							'properties' => $mappingQ[$es->getIndex()]['mappings'][$es->getType()]['properties']
						]
				]];
				$mapping['body'][$es->getType()]['properties'][$path] = ['properties' => [], 'type' => 'nested'];
				foreach($describe as $d){
					$mapping['body'][$es->getType()]['properties'][$path]['properties'][$d['field']] = [
						 'type' => 'text'
					];
					if(array_key_exists($d['field'], $cols)){
						$mapping['body'][$es->getType()]['properties'][$path]['properties'][$d['field']]['copy_to'] = '_all_content';
					}else{
						$mapping['body'][$es->getType()]['properties'][$path]['properties'][$d['field']]['index'] = 'false';
					}
				}
				$mapping['body'][$es->getType()]['properties'][$path]['properties']['_all_content'] = ['type' => 'keyword'];
			}

			$params = [
				'index' => $es->getIndex(),
				'type' => $es->getType(),
				'id' => $path."_".$obj->id,
				'routing' => $path."_".$obj->id,
				'body' => [
					$path => $obj->get_fields(),
				],
			];

			// https://www.elastic.co/guide/en/elasticsearch/reference/current/parent-join.html
			if(empty($uid)){
				$client->index($params);
			}else{
				$params['id'] = $uid;
				$client->update($params);
			}
		}else{
			throw new Exception("Object ".get_class($obj)." is not searchable", 1);
		}
	}

	private static function clean_all(){
		$es = ElasticSearch::getInstance();
		$client = $es->getClient();

		if($client->indices()->exists(['index' => $es->getIndex()])){
			$client->indices()->delete(['index' => $es->getIndex()]);
		}
		$client->indices()->create(['index' => $es->getIndex()]);
	}
}
