<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Response;

use jschreuder\BookmarkBureau\Exception\ResponseTransformerException;
use Psr\Http\Message\ResponseInterface;

/**
 * Transforms array data into PSR-7 HTTP responses
 * 
 * ResponseTransformers handle the HTTP-level concern of converting arrays
 * into properly formatted HTTP responses. They are separate from OutputSpecs,
 * which handle the domain-level concern of converting domain objects to arrays.
 * 
 * The separation allows the controller to respond with different response 
 * formats (JSON, XML, HTML, CSV, etc.) by simply changing the transformer
 * for the same dataset.
 * 
 * Flow:
 * - Controller generates a serializable array (for using an Action)
 * - Controller passes array to ResponseTransformer
 * - ResponseTransformer creates PSR-7 Response with appropriate Content-Type
 * 
 * Example implementations:
 * - JsonResponseTransformer:  array → JSON response
 * - XmlResponseTransformer:   array → XML response
 * - TwigResponseTransformer:  array → rendered template response (with Twig)
 */
interface ResponseTransformerInterface
{
    /**
     * Transform array data into a PSR-7 HTTP response
     * 
     * The implementation should:
     * - Serialize the array to the appropriate format (JSON, XML, etc.)
     * - Create a PSR-7 Response with the correct Content-Type header
     * - Apply the provided status code
     * - Apply any additional headers
     * - Handle serialization errors appropriately
     * 
     * @param array $data The data to transform into a response body
     * @param int $statusCode HTTP status code (default: 200)
     * @param array<string, string> $headers Additional headers to include
     * @return ResponseInterface PSR-7 response object
     * @throws ResponseTransformerException When serialization fails
     */
    public function transform(
        array $data,
        int $statusCode = 200,
        array $headers = []
    ): ResponseInterface;
}
