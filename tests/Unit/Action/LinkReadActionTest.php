<?php

use jschreuder\BookmarkBureau\Action\LinkReadAction;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\InputSpec\IdInputSpec;
use jschreuder\BookmarkBureau\OutputSpec\LinkOutputSpec;
use jschreuder\BookmarkBureau\Service\LinkServiceInterface;
use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Uuid;

describe('LinkReadAction', function () {
    describe('filter method', function () {
        test('trims whitespace from id', function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $outputSpec = new LinkOutputSpec();
            $action = new LinkReadAction($linkService, $inputSpec, $outputSpec);
            $linkId = Uuid::uuid4();

            $filtered = $action->filter([
                'id' => "  {$linkId->toString()}  "
            ]);

            expect($filtered['id'])->toBe($linkId->toString());
        });

        test('handles missing id key with empty string', function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $outputSpec = new LinkOutputSpec();
            $action = new LinkReadAction($linkService, $inputSpec, $outputSpec);

            $filtered = $action->filter([]);

            expect($filtered['id'])->toBe('');
        });

        test('preserves valid id without modification', function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $outputSpec = new LinkOutputSpec();
            $action = new LinkReadAction($linkService, $inputSpec, $outputSpec);
            $linkId = Uuid::uuid4();

            $filtered = $action->filter([
                'id' => $linkId->toString()
            ]);

            expect($filtered['id'])->toBe($linkId->toString());
        });

        test('ignores additional fields in input', function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $outputSpec = new LinkOutputSpec();
            $action = new LinkReadAction($linkService, $inputSpec, $outputSpec);
            $linkId = Uuid::uuid4();

            $filtered = $action->filter([
                'id' => $linkId->toString(),
                'url' => 'https://example.com',
                'title' => 'Should be ignored',
                'extra_field' => 'ignored'
            ]);

            expect($filtered)->toHaveKey('id');
            expect($filtered)->not->toHaveKey('url');
            expect($filtered)->not->toHaveKey('title');
            expect($filtered)->not->toHaveKey('extra_field');
        });
    });

    describe('validate method', function () {
        test('passes validation with valid UUID', function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $outputSpec = new LinkOutputSpec();
            $action = new LinkReadAction($linkService, $inputSpec, $outputSpec);
            $linkId = Uuid::uuid4();

            $data = ['id' => $linkId->toString()];

            try {
                $action->validate($data);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('throws validation error for invalid UUID', function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $outputSpec = new LinkOutputSpec();
            $action = new LinkReadAction($linkService, $inputSpec, $outputSpec);

            $data = ['id' => 'not-a-uuid'];

            expect(fn() => $action->validate($data))
                ->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for empty id', function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $outputSpec = new LinkOutputSpec();
            $action = new LinkReadAction($linkService, $inputSpec, $outputSpec);

            $data = ['id' => ''];

            expect(fn() => $action->validate($data))
                ->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for missing id key', function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $outputSpec = new LinkOutputSpec();
            $action = new LinkReadAction($linkService, $inputSpec, $outputSpec);

            $data = [];

            expect(fn() => $action->validate($data))
                ->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for whitespace-only id', function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $outputSpec = new LinkOutputSpec();
            $action = new LinkReadAction($linkService, $inputSpec, $outputSpec);

            $data = ['id' => '   '];

            expect(fn() => $action->validate($data))
                ->toThrow(ValidationFailedException::class);
        });

        test('throws validation error for null id', function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $outputSpec = new LinkOutputSpec();
            $action = new LinkReadAction($linkService, $inputSpec, $outputSpec);

            $data = ['id' => null];

            expect(fn() => $action->validate($data))
                ->toThrow(ValidationFailedException::class);
        });

        test('validates UUID in different formats', function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $inputSpec = new IdInputSpec();
            $outputSpec = new LinkOutputSpec();
            $action = new LinkReadAction($linkService, $inputSpec, $outputSpec);
            $linkId = Uuid::uuid4();

            $data = ['id' => $linkId->toString()];

            try {
                $action->validate($data);
                expect(true)->toBeTrue();
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });
    });

    describe('execute method', function () {
        test('calls getLink on service with correct UUID', function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $linkId = Uuid::uuid4();
            $link = TestEntityFactory::createLink(id: $linkId);

            $linkService->shouldReceive('getLink')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class))
                ->once()
                ->andReturn($link);

            $inputSpec = new IdInputSpec();
            $outputSpec = new LinkOutputSpec();
            $action = new LinkReadAction($linkService, $inputSpec, $outputSpec);

            $result = $action->execute([
                'id' => $linkId->toString()
            ]);

            expect($result)->toBeArray();
            expect($result)->toHaveKey('id');
        });

        test('returns transformed link data', function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $linkId = Uuid::uuid4();
            $link = TestEntityFactory::createLink(id: $linkId);

            $linkService->shouldReceive('getLink')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class))
                ->andReturn($link);

            $inputSpec = new IdInputSpec();
            $outputSpec = new LinkOutputSpec();
            $action = new LinkReadAction($linkService, $inputSpec, $outputSpec);

            $result = $action->execute([
                'id' => $linkId->toString()
            ]);

            expect($result)->toBeArray();
            expect($result)->toHaveKey('id');
            expect($result)->toHaveKey('url');
            expect($result)->toHaveKey('title');
            expect($result)->toHaveKey('description');
            expect($result)->toHaveKey('icon');
            expect($result)->toHaveKey('created_at');
            expect($result)->toHaveKey('updated_at');
        });

        test('returns correct link data structure', function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $linkId = Uuid::uuid4();
            $link = TestEntityFactory::createLink(
                id: $linkId,
                url: new \jschreuder\BookmarkBureau\Entity\Value\Url('https://example.com'),
                title: new \jschreuder\BookmarkBureau\Entity\Value\Title('Test Link'),
                description: 'Test Description'
            );

            $linkService->shouldReceive('getLink')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class))
                ->andReturn($link);

            $inputSpec = new IdInputSpec();
            $outputSpec = new LinkOutputSpec();
            $action = new LinkReadAction($linkService, $inputSpec, $outputSpec);

            $result = $action->execute([
                'id' => $linkId->toString()
            ]);

            expect($result['id'])->toBe($linkId->toString());
            expect($result['url'])->toBe('https://example.com');
            expect($result['title'])->toBe('Test Link');
            expect($result['description'])->toBe('Test Description');
        });

        test('converts string id to UUID before passing to service', function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $linkId = Uuid::uuid4();
            $link = TestEntityFactory::createLink(id: $linkId);

            $linkService->shouldReceive('getLink')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class))
                ->once()
                ->andReturn($link);

            $inputSpec = new IdInputSpec();
            $outputSpec = new LinkOutputSpec();
            $action = new LinkReadAction($linkService, $inputSpec, $outputSpec);

            $action->execute([
                'id' => $linkId->toString()
            ]);

            expect(true)->toBeTrue();
        });

        test('passes exact UUID to service', function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $linkId = Uuid::uuid4();
            $link = TestEntityFactory::createLink(id: $linkId);

            $uuidCapture = null;
            $linkService->shouldReceive('getLink')
                ->andReturnUsing(function($uuid) use (&$uuidCapture, $link) {
                    $uuidCapture = $uuid;
                    return $link;
                });

            $inputSpec = new IdInputSpec();
            $outputSpec = new LinkOutputSpec();
            $action = new LinkReadAction($linkService, $inputSpec, $outputSpec);

            $action->execute([
                'id' => $linkId->toString()
            ]);

            expect($uuidCapture->toString())->toBe($linkId->toString());
        });

        test('handles link with null icon', function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $linkId = Uuid::uuid4();
            $link = TestEntityFactory::createLink(
                id: $linkId,
                icon: null
            );

            $linkService->shouldReceive('getLink')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class))
                ->andReturn($link);

            $inputSpec = new IdInputSpec();
            $outputSpec = new LinkOutputSpec();
            $action = new LinkReadAction($linkService, $inputSpec, $outputSpec);

            $result = $action->execute([
                'id' => $linkId->toString()
            ]);

            expect($result['icon'])->toBeNull();
        });

        test('handles link with icon', function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $linkId = Uuid::uuid4();
            $link = TestEntityFactory::createLink(
                id: $linkId,
                icon: new \jschreuder\BookmarkBureau\Entity\Value\Icon('test-icon')
            );

            $linkService->shouldReceive('getLink')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class))
                ->andReturn($link);

            $inputSpec = new IdInputSpec();
            $outputSpec = new LinkOutputSpec();
            $action = new LinkReadAction($linkService, $inputSpec, $outputSpec);

            $result = $action->execute([
                'id' => $linkId->toString()
            ]);

            expect($result['icon'])->toBe('test-icon');
        });

        test('formats dates correctly', function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $linkId = Uuid::uuid4();
            $createdAt = new DateTimeImmutable('2024-01-01 12:00:00');
            $updatedAt = new DateTimeImmutable('2024-01-02 13:00:00');
            $link = TestEntityFactory::createLink(
                id: $linkId,
                createdAt: $createdAt,
                updatedAt: $updatedAt
            );

            $linkService->shouldReceive('getLink')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class))
                ->andReturn($link);

            $inputSpec = new IdInputSpec();
            $outputSpec = new LinkOutputSpec();
            $action = new LinkReadAction($linkService, $inputSpec, $outputSpec);

            $result = $action->execute([
                'id' => $linkId->toString()
            ]);

            expect($result['created_at'])->toBe($createdAt->format(DateTimeInterface::ATOM));
            expect($result['updated_at'])->toBe($updatedAt->format(DateTimeInterface::ATOM));
        });
    });

    describe('integration scenarios', function () {
        test('full workflow: filter, validate, and execute', function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $linkId = Uuid::uuid4();
            $link = TestEntityFactory::createLink(id: $linkId);

            $linkService->shouldReceive('getLink')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class))
                ->once()
                ->andReturn($link);

            $inputSpec = new IdInputSpec();
            $outputSpec = new LinkOutputSpec();
            $action = new LinkReadAction($linkService, $inputSpec, $outputSpec);

            $rawData = [
                'id' => "  {$linkId->toString()}  "
            ];

            $filtered = $action->filter($rawData);

            try {
                $action->validate($filtered);
                $result = $action->execute($filtered);
                expect($result)->toBeArray();
                expect($result['id'])->toBe($linkId->toString());
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('full workflow with extra fields in input', function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $linkId = Uuid::uuid4();
            $link = TestEntityFactory::createLink(id: $linkId);

            $linkService->shouldReceive('getLink')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class))
                ->once()
                ->andReturn($link);

            $inputSpec = new IdInputSpec();
            $outputSpec = new LinkOutputSpec();
            $action = new LinkReadAction($linkService, $inputSpec, $outputSpec);

            $rawData = [
                'id' => $linkId->toString(),
                'url' => 'https://example.com',
                'title' => 'Should be ignored',
                'extra' => 'data'
            ];

            $filtered = $action->filter($rawData);
            expect($filtered)->not->toHaveKey('url');
            expect($filtered)->not->toHaveKey('title');

            try {
                $action->validate($filtered);
                $result = $action->execute($filtered);
                expect($result)->toBeArray();
                expect($result['id'])->toBe($linkId->toString());
            } catch (ValidationFailedException $e) {
                throw $e;
            }
        });

        test('full workflow filters and validates id correctly', function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $linkId = Uuid::uuid4();
            $link = TestEntityFactory::createLink(id: $linkId);

            $linkService->shouldReceive('getLink')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class))
                ->andReturn($link);

            $inputSpec = new IdInputSpec();
            $outputSpec = new LinkOutputSpec();
            $action = new LinkReadAction($linkService, $inputSpec, $outputSpec);

            $rawData = [
                'id' => "  {$linkId->toString()}  "
            ];

            $filtered = $action->filter($rawData);
            expect($filtered['id'])->toBe($linkId->toString());

            $action->validate($filtered);
            $result = $action->execute($filtered);

            expect($result)->toBeArray();
            expect($result['id'])->toBe($linkId->toString());
        });

        test('validation failure prevents service call', function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);

            $linkService->shouldNotReceive('getLink');

            $inputSpec = new IdInputSpec();
            $outputSpec = new LinkOutputSpec();
            $action = new LinkReadAction($linkService, $inputSpec, $outputSpec);

            $rawData = [
                'id' => 'invalid-uuid'
            ];

            $filtered = $action->filter($rawData);

            expect(function() use ($action, $filtered) {
                $action->validate($filtered);
            })->toThrow(ValidationFailedException::class);
        });

        test('complete data transformation workflow', function () {
            $linkService = Mockery::mock(LinkServiceInterface::class);
            $linkId = Uuid::uuid4();
            $link = TestEntityFactory::createLink(
                id: $linkId,
                url: new \jschreuder\BookmarkBureau\Entity\Value\Url('https://test.example.com'),
                title: new \jschreuder\BookmarkBureau\Entity\Value\Title('Integration Test'),
                description: 'Full workflow test',
                icon: new \jschreuder\BookmarkBureau\Entity\Value\Icon('workflow-icon')
            );

            $linkService->shouldReceive('getLink')
                ->with(\Mockery::type(\Ramsey\Uuid\UuidInterface::class))
                ->andReturn($link);

            $inputSpec = new IdInputSpec();
            $outputSpec = new LinkOutputSpec();
            $action = new LinkReadAction($linkService, $inputSpec, $outputSpec);

            $rawData = ['id' => $linkId->toString()];
            $filtered = $action->filter($rawData);
            $action->validate($filtered);
            $result = $action->execute($filtered);

            expect($result['id'])->toBe($linkId->toString());
            expect($result['url'])->toBe('https://test.example.com');
            expect($result['title'])->toBe('Integration Test');
            expect($result['description'])->toBe('Full workflow test');
            expect($result['icon'])->toBe('workflow-icon');
            expect($result)->toHaveKey('created_at');
            expect($result)->toHaveKey('updated_at');
        });
    });
});
