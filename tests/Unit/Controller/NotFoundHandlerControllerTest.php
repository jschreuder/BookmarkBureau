<?php

use jschreuder\BookmarkBureau\Controller\NotFoundHandlerController;
use jschreuder\Middle\Controller\ControllerInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;

describe('NotFoundHandlerController', function () {
    describe('initialization', function () {
        test('creates controller instance', function () {
            $controller = new NotFoundHandlerController();

            expect($controller)->toBeInstanceOf(NotFoundHandlerController::class);
        });

        test('implements ControllerInterface', function () {
            $controller = new NotFoundHandlerController();

            expect($controller)->toBeInstanceOf(ControllerInterface::class);
        });
    });

    describe('execute method', function () {
        test('returns 404 status code', function () {
            $controller = new NotFoundHandlerController();
            $request = new ServerRequest(
                uri: 'http://example.com/api/nonexistent',
                method: 'GET',
                serverParams: []
            );

            $response = $controller->execute($request);

            expect($response->getStatusCode())->toBe(404);
        });

        test('returns JsonResponse instance', function () {
            $controller = new NotFoundHandlerController();
            $request = new ServerRequest(
                uri: 'http://example.com/api/notfound',
                method: 'GET',
                serverParams: []
            );

            $response = $controller->execute($request);

            expect($response)->toBeInstanceOf(JsonResponse::class);
        });

        test('returns message with requested path', function () {
            $controller = new NotFoundHandlerController();
            $request = new ServerRequest(
                uri: 'http://example.com/api/users/123',
                method: 'GET',
                serverParams: []
            );

            $response = $controller->execute($request);
            $body = json_decode($response->getBody()->getContents(), true);

            expect($body)->toHaveKey('message')
                ->and($body['message'])->toBe('Not found: /api/users/123');
        });

        test('handles different request methods', function () {
            $controller = new NotFoundHandlerController();
            $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];

            foreach ($methods as $method) {
                $request = new ServerRequest(
                    uri: 'http://example.com/api/unknown',
                    method: $method,
                    serverParams: []
                );

                $response = $controller->execute($request);

                expect($response->getStatusCode())->toBe(404);
                expect($response)->toBeInstanceOf(JsonResponse::class);
            }
        });

        test('handles paths with special characters', function () {
            $controller = new NotFoundHandlerController();
            $request = new ServerRequest(
                uri: 'http://example.com/api/resource%20with%20spaces',
                method: 'GET',
                serverParams: []
            );

            $response = $controller->execute($request);
            $body = json_decode($response->getBody()->getContents(), true);

            expect($body)->toHaveKey('message')
                ->and($body['message'])->toContain('Not found:');
        });

        test('handles root path', function () {
            $controller = new NotFoundHandlerController();
            $request = new ServerRequest(
                uri: 'http://example.com/',
                method: 'GET',
                serverParams: []
            );

            $response = $controller->execute($request);
            $body = json_decode($response->getBody()->getContents(), true);

            expect($body)->toHaveKey('message')
                ->and($body['message'])->toBe('Not found: /');
        });
    });
});
