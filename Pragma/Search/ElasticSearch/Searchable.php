<?php
namespace Pragma\Search\ElasticSearch;

trait Searchable extends \Pragma\Search\Searchable{
	protected function handle_index($last = false){
		$es = ElasticSearch::getInstance();
		$client = $es->getClient();

		$uid = $this->get_id();
		Processor::index_object($this, $uid);
		return true;
	}

	protected function get_id(){
		$es = ElasticSearch::getInstance();
		$client = $es->getClient();

		$classname = Processor::format_class_name($this);

		if($client->exists([
			'index' => $es->getIndex(),
			'type' => $es->getType(),
			'id' => $classname.'_'.$this->id,
		])){
			return $classname.'_'.$this->id;
		}else{
			return null;
		}
	}

	protected function index_delete(){
		$es = ElasticSearch::getInstance();
		$client = $es->getClient();

		$uid = $this->get_id();
		if(!empty($uid)){
			$client->delete([
				'index' => $es->getIndex(),
				'type' => $es->getType(),
				'id' => $uid,
			]);
		}

		return true;
	}
}
