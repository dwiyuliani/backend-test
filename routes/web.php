<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/
$router->get('/', function () use ($router) {
    return response()->json(sprintf('Copyright Backend Test Rest API %s', date('Y')), 200);
});


$router->get('/docs', function () {
    return view('swagger.index');
});

$router->group(['prefix' => 'api', 'middleware' => 'basic.auth'], function () use ($router) {
    $router->post('tag', 'TagController@create');
    $router->put('tag/{id}', ['uses' => 'TagController@update']);
    $router->delete('tag/{id}', ['uses' => 'TagController@delete']);
    $router->get('tag', ['uses' => 'TagController@get']);


    $router->post('topic', 'TopicController@create');
    $router->put('topic/{id}', ['uses' => 'TopicController@update']);
    $router->delete('topic/{id}', ['uses' => 'TopicController@delete']);
    $router->get('topic', ['uses' => 'TopicController@get']);


    $router->post('news', 'NewsController@create');
    $router->put('news/{id}', ['uses' => 'NewsController@update']);
    $router->delete('news/{id}', ['uses' => 'NewsController@delete']);
    $router->post('newslist', ['uses' => 'NewsController@get']);
});
