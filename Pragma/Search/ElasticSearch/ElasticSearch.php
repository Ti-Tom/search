<?php
namespace Pragma\Search\ElasticSearch;

use Elasticsearch\ClientBuilder;

class ElasticSearch {
	protected static $connection = null;//singleton
	protected $type = '_doc';
	protected $index = 'pragma-framework';
	protected $client = null;

	public function __construct(){
		// IP / PORT / User/pwd
		$this->client = ClientBuilder::create()->setHosts(['192.168.10.22:9200'])->build();
		if(!$this->client->indices()->exists(['index' => $this->index])){
			$this->client->indices()->create(['index' => $this->index]);
		}
	}

	public static function getInstance() {
		if (!(self::$connection instanceof self)){
			self::$connection = new self();
		}

		return self::$connection;
	}

	public function getClient(){
		return $this->client;
	}
	public function getType(){
		return $this->type;
	}
	public function getIndex(){
		return $this->index;
	}
}