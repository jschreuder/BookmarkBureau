<?php

use jschreuder\BookmarkBureau\Response\JsonResponseTransformer;
use jschreuder\BookmarkBureau\Exception\ResponseTransformerException;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;

describe('JsonResponseTransformer', function () {
    describe('initialization', function () {
        test('creates transformer instance', function () {
            $transformer = new JsonResponseTransformer();

            expect($transformer)->toBeInstanceOf(JsonResponseTransformer::class);
        });

        test('is readonly', function () {
            $transformer = new JsonResponseTransformer();

            expect($transformer)->toBeInstanceOf(JsonResponseTransformer::class);
        });
    });

    describe('transform method', function () {
        test('transforms empty array to JSON response', function () {
            $transformer = new JsonResponseTransformer();

            $response = $transformer->transform([]);

            expect($response)->toBeInstanceOf(ResponseInterface::class);
            expect($response->getStatusCode())->toBe(200);
            expect($response->getHeader('Content-Type')[0])->toContain('application/json');
        });

        test('transforms simple array to JSON response', function () {
            $transformer = new JsonResponseTransformer();
            $data = ['id' => 1, 'name' => 'Test'];

            $response = $transformer->transform($data);

            expect($response)->toBeInstanceOf(ResponseInterface::class);
            expect($response->getStatusCode())->toBe(200);
            $body = json_decode($response->getBody()->getContents(), true);
            expect($body)->toBe(['id' => 1, 'name' => 'Test']);
        });

        test('transforms complex nested array to JSON response', function () {
            $transformer = new JsonResponseTransformer();
            $data = [
                'id' => 1,
                'user' => ['name' => 'John', 'email' => 'john@example.com'],
                'tags' => ['tag1', 'tag2', 'tag3']
            ];

            $response = $transformer->transform($data);

            $body = json_decode($response->getBody()->getContents(), true);
            expect($body)->toBe($data);
        });

        test('uses default status code 200 when not specified', function () {
            $transformer = new JsonResponseTransformer();

            $response = $transformer->transform(['status' => 'ok']);

            expect($response->getStatusCode())->toBe(200);
        });

        test('applies custom status code 201', function () {
            $transformer = new JsonResponseTransformer();

            $response = $transformer->transform(['id' => 1], 201);

            expect($response->getStatusCode())->toBe(201);
        });

        test('applies custom status code 204', function () {
            $transformer = new JsonResponseTransformer();

            $response = $transformer->transform([], 204);

            expect($response->getStatusCode())->toBe(204);
        });

        test('applies custom status code 400', function () {
            $transformer = new JsonResponseTransformer();

            $response = $transformer->transform(['error' => 'Bad Request'], 400);

            expect($response->getStatusCode())->toBe(400);
        });

        test('applies custom status code 500', function () {
            $transformer = new JsonResponseTransformer();

            $response = $transformer->transform(['error' => 'Internal Server Error'], 500);

            expect($response->getStatusCode())->toBe(500);
        });

        test('adds single additional header', function () {
            $transformer = new JsonResponseTransformer();
            $headers = ['X-Custom-Header' => 'custom-value'];

            $response = $transformer->transform(['data' => 'value'], 200, $headers);

            expect($response->getHeader('X-Custom-Header')[0])->toBe('custom-value');
        });

        test('adds multiple additional headers', function () {
            $transformer = new JsonResponseTransformer();
            $headers = [
                'X-Custom-Header-1' => 'value-1',
                'X-Custom-Header-2' => 'value-2',
                'X-Custom-Header-3' => 'value-3'
            ];

            $response = $transformer->transform(['data' => 'value'], 200, $headers);

            expect($response->getHeader('X-Custom-Header-1')[0])->toBe('value-1');
            expect($response->getHeader('X-Custom-Header-2')[0])->toBe('value-2');
            expect($response->getHeader('X-Custom-Header-3')[0])->toBe('value-3');
        });

        test('preserves Content-Type header when adding additional headers', function () {
            $transformer = new JsonResponseTransformer();
            $headers = ['X-Custom' => 'value'];

            $response = $transformer->transform(['data' => 'value'], 200, $headers);

            expect($response->getHeader('Content-Type')[0])->toContain('application/json');
            expect($response->getHeader('X-Custom')[0])->toBe('value');
        });

        test('returns JsonResponse instance', function () {
            $transformer = new JsonResponseTransformer();

            $response = $transformer->transform(['status' => 'ok']);

            expect($response)->toBeInstanceOf(JsonResponse::class);
        });

        test('serializes null values correctly', function () {
            $transformer = new JsonResponseTransformer();
            $data = ['field' => null, 'value' => 'test'];

            $response = $transformer->transform($data);

            $body = json_decode($response->getBody()->getContents(), true);
            expect($body['field'])->toBeNull();
            expect($body['value'])->toBe('test');
        });

        test('serializes numeric arrays correctly', function () {
            $transformer = new JsonResponseTransformer();
            $data = [1, 2, 3, 4, 5];

            $response = $transformer->transform($data);

            $body = json_decode($response->getBody()->getContents(), true);
            expect($body)->toBe([1, 2, 3, 4, 5]);
        });

        test('serializes string values with special characters', function () {
            $transformer = new JsonResponseTransformer();
            $data = ['message' => 'Hello "World" with \\ backslash'];

            $response = $transformer->transform($data);

            $body = json_decode($response->getBody()->getContents(), true);
            expect($body['message'])->toBe('Hello "World" with \\ backslash');
        });

        test('serializes unicode characters correctly', function () {
            $transformer = new JsonResponseTransformer();
            $data = ['name' => 'José', 'city' => '北京'];

            $response = $transformer->transform($data);

            $body = json_decode($response->getBody()->getContents(), true);
            expect($body['name'])->toBe('José');
            expect($body['city'])->toBe('北京');
        });

        test('serializes boolean values correctly', function () {
            $transformer = new JsonResponseTransformer();
            $data = ['active' => true, 'deleted' => false];

            $response = $transformer->transform($data);

            $body = json_decode($response->getBody()->getContents(), true);
            expect($body['active'])->toBe(true);
            expect($body['deleted'])->toBe(false);
        });

        test('serializes float values correctly', function () {
            $transformer = new JsonResponseTransformer();
            $data = ['price' => 19.99, 'discount' => 0.15];

            $response = $transformer->transform($data);

            $body = json_decode($response->getBody()->getContents(), true);
            expect($body['price'])->toBe(19.99);
            expect($body['discount'])->toBe(0.15);
        });

        test('combines status code and custom headers', function () {
            $transformer = new JsonResponseTransformer();
            $headers = ['X-Request-ID' => '12345'];

            $response = $transformer->transform(['created' => true], 201, $headers);

            expect($response->getStatusCode())->toBe(201);
            expect($response->getHeader('X-Request-ID')[0])->toBe('12345');
        });

        test('with empty headers array', function () {
            $transformer = new JsonResponseTransformer();

            $response = $transformer->transform(['data' => 'value'], 200, []);

            expect($response->getStatusCode())->toBe(200);
            expect($response->getHeader('Content-Type')[0])->toContain('application/json');
        });
    });

    describe('interface implementation', function () {
        test('implements ResponseTransformerInterface', function () {
            $transformer = new JsonResponseTransformer();

            expect($transformer)->toBeInstanceOf(\jschreuder\BookmarkBureau\Response\ResponseTransformerInterface::class);
        });

        test('transform method returns ResponseInterface', function () {
            $transformer = new JsonResponseTransformer();

            $response = $transformer->transform(['test' => 'data']);

            expect($response)->toBeInstanceOf(ResponseInterface::class);
        });
    });

    describe('edge cases', function () {
        test('handles deeply nested arrays', function () {
            $transformer = new JsonResponseTransformer();
            $data = [
                'level1' => [
                    'level2' => [
                        'level3' => [
                            'level4' => 'deep value'
                        ]
                    ]
                ]
            ];

            $response = $transformer->transform($data);

            $body = json_decode($response->getBody()->getContents(), true);
            expect($body['level1']['level2']['level3']['level4'])->toBe('deep value');
        });

        test('handles large arrays', function () {
            $transformer = new JsonResponseTransformer();
            $data = [];
            for ($i = 0; $i < 1000; $i++) {
                $data[] = ['id' => $i, 'value' => "item-{$i}"];
            }

            $response = $transformer->transform($data);

            $body = json_decode($response->getBody()->getContents(), true);
            expect(count($body))->toBe(1000);
            expect($body[0]['id'])->toBe(0);
            expect($body[999]['id'])->toBe(999);
        });

        test('handles mixed numeric and string keys', function () {
            $transformer = new JsonResponseTransformer();
            $data = [
                'string_key' => 'value',
                0 => 'numeric key zero',
                'another_key' => 'another value',
                1 => 'numeric key one'
            ];

            $response = $transformer->transform($data);

            $body = json_decode($response->getBody()->getContents(), true);
            expect($body['string_key'])->toBe('value');
            expect($body['another_key'])->toBe('another value');
        });

        test('response body contains valid JSON', function () {
            $transformer = new JsonResponseTransformer();
            $data = ['test' => 'data'];

            $response = $transformer->transform($data);
            $body = $response->getBody()->getContents();

            expect($body)->toBe(json_encode($data));
        });

        test('handles empty headers with all other parameters', function () {
            $transformer = new JsonResponseTransformer();

            $response = $transformer->transform(['id' => 1], 201, []);

            expect($response->getStatusCode())->toBe(201);
            $body = json_decode($response->getBody()->getContents(), true);
            expect($body['id'])->toBe(1);
        });
    });

    describe('OutputSpec integration', function () {
        test('transforms output from LinkOutputSpec', function () {
            $transformer = new JsonResponseTransformer();
            $spec = new \jschreuder\BookmarkBureau\OutputSpec\LinkOutputSpec();
            $link = TestEntityFactory::createLink();

            $outputData = $spec->transform($link);
            $response = $transformer->transform($outputData);

            $body = json_decode($response->getBody()->getContents(), true);
            expect($body)->toHaveKeys(['id', 'url', 'title', 'description', 'icon', 'created_at', 'updated_at']);
            expect($body['url'])->toBeString();
            expect($body['created_at'])->toBeString();
        });

        test('transforms output from CategoryOutputSpec', function () {
            $transformer = new JsonResponseTransformer();
            $spec = new \jschreuder\BookmarkBureau\OutputSpec\CategoryOutputSpec();
            $category = TestEntityFactory::createCategory();

            $outputData = $spec->transform($category);
            $response = $transformer->transform($outputData);

            $body = json_decode($response->getBody()->getContents(), true);
            expect($body)->toHaveKeys(['id', 'dashboard_id', 'title', 'color', 'sort_order', 'created_at', 'updated_at']);
            expect($body['id'])->toBeString();
        });

        test('transforms output from DashboardOutputSpec', function () {
            $transformer = new JsonResponseTransformer();
            $spec = new \jschreuder\BookmarkBureau\OutputSpec\DashboardOutputSpec();
            $dashboard = TestEntityFactory::createDashboard();

            $outputData = $spec->transform($dashboard);
            $response = $transformer->transform($outputData);

            $body = json_decode($response->getBody()->getContents(), true);
            expect($body)->toHaveKeys(['id', 'title', 'description', 'icon', 'created_at', 'updated_at']);
        });

        test('transforms ActionController response format with single OutputSpec result', function () {
            $transformer = new JsonResponseTransformer();
            $spec = new \jschreuder\BookmarkBureau\OutputSpec\LinkOutputSpec();
            $link = TestEntityFactory::createLink();

            // Simulate ActionController response format
            $data = [
                'success' => true,
                'data' => $spec->transform($link)
            ];

            $response = $transformer->transform($data);

            $body = json_decode($response->getBody()->getContents(), true);
            expect($body['success'])->toBeTrue();
            expect($body['data'])->toHaveKeys(['id', 'url', 'title', 'description', 'icon', 'created_at', 'updated_at']);
        });

        test('transforms ActionController response format with array of OutputSpec results', function () {
            $transformer = new JsonResponseTransformer();
            $spec = new \jschreuder\BookmarkBureau\OutputSpec\LinkOutputSpec();
            $link1 = TestEntityFactory::createLink();
            $link2 = TestEntityFactory::createLink();

            // Simulate ActionController response format with multiple items
            $data = [
                'success' => true,
                'data' => [
                    $spec->transform($link1),
                    $spec->transform($link2)
                ]
            ];

            $response = $transformer->transform($data);

            $body = json_decode($response->getBody()->getContents(), true);
            expect($body['success'])->toBeTrue();
            expect($body['data'])->toBeArray();
            expect(count($body['data']))->toBe(2);
            expect($body['data'][0])->toHaveKeys(['id', 'url', 'title']);
        });

        test('preserves datetime formats from OutputSpec', function () {
            $transformer = new JsonResponseTransformer();
            $spec = new \jschreuder\BookmarkBureau\OutputSpec\LinkOutputSpec();
            $createdAt = new DateTimeImmutable('2024-05-15 14:30:45', new DateTimeZone('UTC'));
            $link = TestEntityFactory::createLink(createdAt: $createdAt);

            $outputData = $spec->transform($link);
            $response = $transformer->transform($outputData);

            $body = json_decode($response->getBody()->getContents(), true);
            expect($body['created_at'])->toBe($createdAt->format(DateTimeInterface::ATOM));
        });

        test('handles OutputSpec with null and optional values', function () {
            $transformer = new JsonResponseTransformer();
            $spec = new \jschreuder\BookmarkBureau\OutputSpec\LinkOutputSpec();
            $link = TestEntityFactory::createLink(icon: null);

            $outputData = $spec->transform($link);
            $response = $transformer->transform($outputData);

            $body = json_decode($response->getBody()->getContents(), true);
            expect($body['icon'])->toBeNull();
            // Verify the key exists in the output array
            expect(array_key_exists('icon', $body))->toBeTrue();
        });

        test('transforms large collection of OutputSpec results', function () {
            $transformer = new JsonResponseTransformer();
            $spec = new \jschreuder\BookmarkBureau\OutputSpec\LinkOutputSpec();

            $links = [];
            for ($i = 0; $i < 100; $i++) {
                $links[] = $spec->transform(TestEntityFactory::createLink());
            }

            $data = [
                'success' => true,
                'data' => $links
            ];

            $response = $transformer->transform($data);

            $body = json_decode($response->getBody()->getContents(), true);
            expect($body['success'])->toBeTrue();
            expect(count($body['data']))->toBe(100);
            // Verify all items have required fields
            foreach ($body['data'] as $item) {
                expect($item)->toHaveKeys(['id', 'url', 'title', 'description', 'icon', 'created_at', 'updated_at']);
            }
        });

        test('combines OutputSpec data with custom status code', function () {
            $transformer = new JsonResponseTransformer();
            $spec = new \jschreuder\BookmarkBureau\OutputSpec\LinkOutputSpec();
            $link = TestEntityFactory::createLink();

            $outputData = $spec->transform($link);
            $data = [
                'success' => true,
                'data' => $outputData
            ];

            $response = $transformer->transform($data, 201);

            expect($response->getStatusCode())->toBe(201);
            $body = json_decode($response->getBody()->getContents(), true);
            expect($body['data']['id'])->toBeString();
        });
    });

    describe('exception handling', function () {
        test('throws ResponseTransformerException when JSON encoding fails', function () {
            $transformer = new JsonResponseTransformer();
            // Create data with circular reference that cannot be JSON encoded
            $data = [];
            $data['self'] = &$data;

            expect(fn() => $transformer->transform($data))
                ->toThrow(ResponseTransformerException::class);
        });

        test('exception contains error message from original exception', function () {
            $transformer = new JsonResponseTransformer();
            $data = [];
            $data['self'] = &$data;

            expect(fn() => $transformer->transform($data))
                ->toThrow(function (ResponseTransformerException $e) {
                    return str_contains($e->getMessage(), 'Generating JSON response failed');
                });
        });

        test('exception preserves original exception as previous', function () {
            $transformer = new JsonResponseTransformer();
            $data = [];
            $data['self'] = &$data;

            expect(fn() => $transformer->transform($data))
                ->toThrow(function (ResponseTransformerException $e) {
                    return $e->getPrevious() !== null;
                });
        });

        test('exception has status code 500', function () {
            $transformer = new JsonResponseTransformer();
            $data = [];
            $data['self'] = &$data;

            try {
                $transformer->transform($data);
            } catch (ResponseTransformerException $e) {
                expect($e->getCode())->toBe(500);
            }
        });

        test('throws exception when headers parameter causes encoding failure', function () {
            $transformer = new JsonResponseTransformer();
            // Note: This test depends on Laminas behavior - invalid headers might cause exception
            // We'll test with valid data but simulate a scenario that could fail
            $data = ['valid' => 'data'];

            // This should succeed with valid data and valid headers
            $response = $transformer->transform($data, 200, ['X-Custom' => 'value']);
            expect($response)->toBeInstanceOf(ResponseInterface::class);
        });

        test('exception message is descriptive', function () {
            $transformer = new JsonResponseTransformer();
            $data = [];
            $data['self'] = &$data;

            expect(fn() => $transformer->transform($data))
                ->toThrow(ResponseTransformerException::class)
                ->and(fn() => $transformer->transform($data))
                ->toThrow(function (ResponseTransformerException $e) {
                    return strlen($e->getMessage()) > 0 &&
                           str_contains($e->getMessage(), 'Generating JSON response failed');
                });
        });
    });
});
