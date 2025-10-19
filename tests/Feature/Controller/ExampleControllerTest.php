<?php

use jschreuder\BookmarkBureau\Controller\ExampleController;
use jschreuder\BookmarkBureau\Service\ExampleService;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;

test('example controller returns hello world response', function () {
    $service = mock(ExampleService::class);
    $service->shouldReceive('getMessage')
        ->once()
        ->andReturn('Hello world!');

    $controller = new ExampleController($service);
    
    $request = new ServerRequest(
        [],
        [],
        new Uri('/'),
        'GET'
    );
    
    $response = $controller->execute($request);
    
    expect($response->getStatusCode())->toBe(200);
    expect((string) $response->getBody())->json()->toBe([
        'message' => 'Hello world!'
    ]);
}); 