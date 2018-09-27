<?php
namespace Pragma\Search\ElasticSearch;

class Search{
	const START_PRECISION = 1;
	const END_PRECISION = 2;
	const LARGE_PRECISION = 3;
	const EXACT_PRECISION = 4;

	const RANKED_RESULTS = 1;
	const OBJECTS_RESULTS = 2;
	const FULL_RESULTS = 3;

	/*
	* $query : the search query
	* $score : https://www.elastic.co/guide/en/elasticsearch/guide/current/relevance-intro.html#explain
	* $types : filter on indexable_type. given as an array
	* $cols : filter on col. given as an array
	*/
	public static function process($query,
			 $score = null,
			 $types = null,
			 $cols = null){

		$es = ElasticSearch::getInstance();
		$client = $es->getClient();

		$querySearch = [
			'index' => $es->getIndex(),
			'type' => $es->getType(),
			'body' => [
				'query' => [
					'bool' => [
						'must' => [],
						'filter' => []
					],
				]
			]
		];
		if(!empty($score) && $score > 0){
			$querySearch['body']['min_score'] = floatval($score);
		}

		if(!empty($types)){
			$querySearch['body']['query']['bool']['filter']['terms'] = ['_classname' => $types];
		}

		if(empty($cols)){
			$querySearch['body']['query']['bool']['must']['match'] = ['_all_content' => $query, 'operator' => 'and'];
		}else{
			$querySearch['body']['query']['bool']['must']['multi_match'] = ['query' => $query, 'fields' => $cols, 'operator' => 'and'];
		}

		var_dump(json_encode($querySearch));

		return $client->search($querySearch);
	}
}

