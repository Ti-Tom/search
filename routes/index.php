<?php
use Pragma\Router\Router;
use Pragma\Search\IndexerController;
use Pragma\Search\ProcessorElasticSearch;

$app = Router::getInstance();

$app->group('indexer:', function() use($app){
	$app->cli('run',function(){
		IndexerController::run();
	});
	$app->cli('rebuild',function(){
		IndexerController::rebuild();
	});
	$app->cli('rebuild2',function(){
		IndexerController::rebuild2();
	});
});
