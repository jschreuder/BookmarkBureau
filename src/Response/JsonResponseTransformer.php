<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Response;

use jschreuder\BookmarkBureau\Exception\ResponseTransformerException;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Throwable;

/**
 * Transforms arrays into JSON HTTP responses
 *
 * This is the primary ResponseTransformer for REST APIs, converting
 * array data into JSON-formatted HTTP responses with appropriate headers.
 */
final readonly class JsonResponseTransformer implements ResponseTransformerInterface
{
    /**
     * Transform array into JSON response
     *
     * @param array $data Array to serialize as JSON
     * @param int $statusCode HTTP status code
     * @param array<string, string> $headers Additional headers
     * @return ResponseInterface JSON response with Content-Type: application/json
     * @throws \RuntimeException When JSON encoding fails
     */
    public function transform(
        array $data,
        int $statusCode = 200,
        array $headers = []
    ): ResponseInterface {
        try {
            $response = new JsonResponse($data, $statusCode);

            foreach ($headers as $name => $value) {
                $response = $response->withHeader($name, $value);
            }
        } catch (Throwable $exception) {
            throw new ResponseTransformerException(
                'Generating JSON response failed: ' . $exception->getMessage(),
                500,
                $exception
            );
        }

        return $response;
    }
}
