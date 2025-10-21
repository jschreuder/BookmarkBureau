<?php

use jschreuder\BookmarkBureau\Controller\Action\ActionInterface;
use jschreuder\BookmarkBureau\Controller\ActionController;
use Laminas\Diactoros\ServerRequest;

describe('ActionController', function () {
    describe('initialization', function () {
        test('creates controller with action and default success status', function () {
            $action = Mockery::mock(ActionInterface::class);
            $controller = new ActionController($action);

            expect($controller)->toBeInstanceOf(ActionController::class);
        });

        test('creates controller with action and custom success status', function () {
            $action = Mockery::mock(ActionInterface::class);
            $controller = new ActionController($action, 201);

            expect($controller)->toBeInstanceOf(ActionController::class);
        });
    });

    describe('filterRequest method', function () {
        test('filters GET request with query parameters', function () {
            $action = Mockery::mock(ActionInterface::class);
            $action->shouldReceive('filter')
                ->with(['name' => 'test', 'value' => '123'])
                ->andReturn(['name' => 'test', 'value' => 123]);

            $controller = new ActionController($action);
            $request = new ServerRequest(
                uri: 'http://example.com/api?name=test&value=123',
                method: 'GET',
                serverParams: []
            );
            $request = $request->withQueryParams(['name' => 'test', 'value' => '123']);

            $filtered = $controller->filterRequest($request);

            expect($filtered->getParsedBody())->toBe(['name' => 'test', 'value' => 123]);
        });

        test('filters POST request with parsed body', function () {
            $action = Mockery::mock(ActionInterface::class);
            $action->shouldReceive('filter')
                ->with(['name' => 'test'])
                ->andReturn(['name' => 'test']);

            $controller = new ActionController($action);
            $request = new ServerRequest(
                uri: 'http://example.com/api',
                method: 'POST',
                serverParams: []
            );
            $request = $request->withParsedBody(['name' => 'test']);

            $filtered = $controller->filterRequest($request);

            expect($filtered->getParsedBody())->toBe(['name' => 'test']);
        });

        test('handles null parsed body by treating as empty array', function () {
            $action = Mockery::mock(ActionInterface::class);
            $action->shouldReceive('filter')
                ->with([])
                ->andReturn([]);

            $controller = new ActionController($action);
            $request = new ServerRequest(
                uri: 'http://example.com/api',
                method: 'POST',
                serverParams: []
            );
            $request = $request->withParsedBody(null);

            $filtered = $controller->filterRequest($request);

            expect($filtered->getParsedBody())->toBe([]);
        });

        test('includes route parameter id in filtered data', function () {
            $action = Mockery::mock(ActionInterface::class);
            $action->shouldReceive('filter')
                ->with(['id' => '123', 'name' => 'test'])
                ->andReturn(['id' => '123', 'name' => 'test']);

            $controller = new ActionController($action);
            $request = new ServerRequest(
                uri: 'http://example.com/api/123',
                method: 'GET',
                serverParams: []
            );
            $request = $request->withQueryParams(['name' => 'test'])
                ->withAttribute('id', '123');

            $filtered = $controller->filterRequest($request);

            expect($filtered->getParsedBody())->toHaveKey('id', '123');
        });

        test('does not include id if not in request attributes', function () {
            $action = Mockery::mock(ActionInterface::class);
            $action->shouldReceive('filter')
                ->with(['name' => 'test'])
                ->andReturn(['name' => 'test']);

            $controller = new ActionController($action);
            $request = new ServerRequest(
                uri: 'http://example.com/api',
                method: 'GET',
                serverParams: []
            );
            $request = $request->withQueryParams(['name' => 'test']);

            $filtered = $controller->filterRequest($request);

            expect($filtered->getParsedBody())->not->toHaveKey('id');
        });

        test('transforms data using action filter', function () {
            $action = Mockery::mock(ActionInterface::class);
            $action->shouldReceive('filter')
                ->with(['email' => 'test@example.com', 'age' => '30'])
                ->andReturn(['email' => 'test@example.com', 'age' => 30]);

            $controller = new ActionController($action);
            $request = new ServerRequest(
                uri: 'http://example.com/api',
                method: 'POST',
                serverParams: []
            );
            $request = $request->withParsedBody(['email' => 'test@example.com', 'age' => '30']);

            $filtered = $controller->filterRequest($request);

            expect($filtered->getParsedBody())->toBe(['email' => 'test@example.com', 'age' => 30]);
        });
    });

    describe('validateRequest method', function () {
        test('validates request data through action', function () {
            $validatableCalled = false;
            $validatedData = null;

            $action = Mockery::mock(ActionInterface::class);
            $action->shouldReceive('validate')
                ->with(['name' => 'test', 'email' => 'test@example.com'])
                ->andReturnUsing(function($data) use (&$validatableCalled, &$validatedData) {
                    $validatableCalled = true;
                    $validatedData = $data;
                });

            $controller = new ActionController($action);
            $request = new ServerRequest(
                uri: 'http://example.com/api',
                method: 'POST',
                serverParams: []
            );
            $request = $request->withParsedBody(['name' => 'test', 'email' => 'test@example.com']);

            $controller->validateRequest($request);

            expect($validatableCalled)->toBeTrue();
            expect($validatedData)->toBe(['name' => 'test', 'email' => 'test@example.com']);
        });

        test('treats null parsed body as empty array for validation', function () {
            $validatedData = ['not', 'set'];

            $action = Mockery::mock(ActionInterface::class);
            $action->shouldReceive('validate')
                ->with([])
                ->andReturnUsing(function($data) use (&$validatedData) {
                    $validatedData = $data;
                });

            $controller = new ActionController($action);
            $request = new ServerRequest(
                uri: 'http://example.com/api',
                method: 'POST',
                serverParams: []
            );
            $request = $request->withParsedBody(null);

            $controller->validateRequest($request);

            expect($validatedData)->toBe([]);
        });

        test('throws exception when validation fails', function () {
            $action = Mockery::mock(ActionInterface::class);
            $action->shouldReceive('validate')
                ->andThrow(new Exception('Validation failed'));

            $controller = new ActionController($action);
            $request = new ServerRequest(
                uri: 'http://example.com/api',
                method: 'POST',
                serverParams: []
            );
            $request = $request->withParsedBody(['invalid' => 'data']);

            expect(fn() => $controller->validateRequest($request))
                ->toThrow(Exception::class, 'Validation failed');
        });
    });

    describe('execute method', function () {
        test('executes action and returns JSON response with success status', function () {
            $action = Mockery::mock(ActionInterface::class);
            $action->shouldReceive('execute')
                ->with(['name' => 'test'])
                ->andReturn(['id' => 1, 'name' => 'test']);

            $controller = new ActionController($action, 200);
            $request = new ServerRequest(
                uri: 'http://example.com/api',
                method: 'POST',
                serverParams: []
            );
            $request = $request->withParsedBody(['name' => 'test']);

            $response = $controller->execute($request);

            expect($response->getStatusCode())->toBe(200);
            expect($response->getHeader('Content-Type')[0])->toContain('application/json');
        });

        test('executes action with default success status 200', function () {
            $action = Mockery::mock(ActionInterface::class);
            $action->shouldReceive('execute')
                ->with(['name' => 'test'])
                ->andReturn(['id' => 1, 'name' => 'test']);

            $controller = new ActionController($action);
            $request = new ServerRequest(
                uri: 'http://example.com/api',
                method: 'POST',
                serverParams: []
            );
            $request = $request->withParsedBody(['name' => 'test']);

            $response = $controller->execute($request);

            expect($response->getStatusCode())->toBe(200);
        });

        test('executes action with custom success status 201', function () {
            $action = Mockery::mock(ActionInterface::class);
            $action->shouldReceive('execute')
                ->andReturn(['id' => 1]);

            $controller = new ActionController($action, 201);
            $request = new ServerRequest(
                uri: 'http://example.com/api',
                method: 'POST',
                serverParams: []
            );
            $request = $request->withParsedBody([]);

            $response = $controller->execute($request);

            expect($response->getStatusCode())->toBe(201);
        });

        test('returns response body with success flag and data', function () {
            $expectedData = ['id' => 42, 'name' => 'Created Item', 'status' => 'active'];
            $action = Mockery::mock(ActionInterface::class);
            $action->shouldReceive('execute')
                ->andReturn($expectedData);

            $controller = new ActionController($action);
            $request = new ServerRequest(
                uri: 'http://example.com/api',
                method: 'POST',
                serverParams: []
            );
            $request = $request->withParsedBody(['name' => 'Item']);

            $response = $controller->execute($request);
            $body = json_decode($response->getBody()->getContents(), true);

            expect($body)->toHaveKey('success', true);
            expect($body)->toHaveKey('data', $expectedData);
        });

        test('handles empty array result from action', function () {
            $action = Mockery::mock(ActionInterface::class);
            $action->shouldReceive('execute')
                ->andReturn([]);

            $controller = new ActionController($action);
            $request = new ServerRequest(
                uri: 'http://example.com/api',
                method: 'DELETE',
                serverParams: []
            );
            $request = $request->withParsedBody([]);

            $response = $controller->execute($request);
            $body = json_decode($response->getBody()->getContents(), true);

            expect($body)->toHaveKey('success', true);
            expect($body['data'])->toBe([]);
        });

        test('treats null parsed body as empty array for execution', function () {
            $action = Mockery::mock(ActionInterface::class);
            $action->shouldReceive('execute')
                ->with([])
                ->andReturn(['result' => 'ok']);

            $controller = new ActionController($action);
            $request = new ServerRequest(
                uri: 'http://example.com/api',
                method: 'GET',
                serverParams: []
            );
            $request = $request->withParsedBody(null);

            $response = $controller->execute($request);
            $body = json_decode($response->getBody()->getContents(), true);

            expect($body['data'])->toBe(['result' => 'ok']);
        });

        test('response is JsonResponse instance', function () {
            $action = Mockery::mock(ActionInterface::class);
            $action->shouldReceive('execute')
                ->andReturn(['status' => 'ok']);

            $controller = new ActionController($action);
            $request = new ServerRequest(
                uri: 'http://example.com/api',
                method: 'POST',
                serverParams: []
            );
            $request = $request->withParsedBody([]);

            $response = $controller->execute($request);

            expect($response)->toBeInstanceOf(Laminas\Diactoros\Response\JsonResponse::class);
        });
    });

    describe('interface implementation', function () {
        test('implements ControllerInterface', function () {
            $action = Mockery::mock(ActionInterface::class);
            $controller = new ActionController($action);

            expect($controller)->toBeInstanceOf(jschreuder\Middle\Controller\ControllerInterface::class);
        });

        test('implements RequestFilterInterface', function () {
            $action = Mockery::mock(ActionInterface::class);
            $controller = new ActionController($action);

            expect($controller)->toBeInstanceOf(jschreuder\Middle\Controller\RequestFilterInterface::class);
        });

        test('implements RequestValidatorInterface', function () {
            $action = Mockery::mock(ActionInterface::class);
            $controller = new ActionController($action);

            expect($controller)->toBeInstanceOf(jschreuder\Middle\Controller\RequestValidatorInterface::class);
        });
    });

    describe('integration scenarios', function () {
        test('full request lifecycle with GET request', function () {
            $action = Mockery::mock(ActionInterface::class);
            $action->shouldReceive('filter')
                ->with(['search' => 'test'])
                ->andReturn(['search' => 'test']);
            $action->shouldReceive('validate')
                ->with(['search' => 'test'])
                ->once();
            $action->shouldReceive('execute')
                ->with(['search' => 'test'])
                ->andReturn(['results' => []]);

            $controller = new ActionController($action);
            $request = new ServerRequest(
                uri: 'http://example.com/api?search=test',
                method: 'GET',
                serverParams: []
            );
            $request = $request->withQueryParams(['search' => 'test']);

            $filtered = $controller->filterRequest($request);
            $controller->validateRequest($filtered);
            $response = $controller->execute($filtered);

            expect($response->getStatusCode())->toBe(200);
            $body = json_decode($response->getBody()->getContents(), true);
            expect($body['data'])->toBe(['results' => []]);
        });

        test('full request lifecycle with POST request and ID in route', function () {
            $action = Mockery::mock(ActionInterface::class);
            $action->shouldReceive('filter')
                ->with(['id' => 'abc-123', 'name' => 'Updated'])
                ->andReturn(['id' => 'abc-123', 'name' => 'Updated']);
            $action->shouldReceive('validate')
                ->with(['id' => 'abc-123', 'name' => 'Updated'])
                ->once();
            $action->shouldReceive('execute')
                ->with(['id' => 'abc-123', 'name' => 'Updated'])
                ->andReturn(['id' => 'abc-123', 'name' => 'Updated']);

            $controller = new ActionController($action, 201);
            $request = new ServerRequest(
                uri: 'http://example.com/api/abc-123',
                method: 'POST',
                serverParams: []
            );
            $request = $request->withParsedBody(['name' => 'Updated'])
                ->withAttribute('id', 'abc-123');

            $filtered = $controller->filterRequest($request);
            $controller->validateRequest($filtered);
            $response = $controller->execute($filtered);

            expect($response->getStatusCode())->toBe(201);
        });
    });
});
