<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Controller;

use jschreuder\BookmarkBureau\Action\ActionInterface;
use jschreuder\BookmarkBureau\Response\ResponseTransformerInterface;
use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestFilterInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Generic controller for simple CRUD operations
 *
 * The Closure must always return an array that can be safely converted to JSON
 */
final readonly class ActionController implements
    ControllerInterface,
    RequestFilterInterface,
    RequestValidatorInterface
{
    public function __construct(
        private ActionInterface $action,
        private ResponseTransformerInterface $responseTransformer,
        private int $successStatus = 200,
    ) {}

    #[\Override]
    public function filterRequest(
        ServerRequestInterface $request,
    ): ServerRequestInterface {
        // Fetch input data, query parameters for GET request, the (parsed) body parameters for all others
        $rawData =
            $request->getMethod() === "GET"
                ? $request->getQueryParams()
                : $request->getParsedBody();
        if (!\is_array($rawData)) {
            $rawData = [];
        }
        /** @var array<string, mixed> $rawData */

        // Often the ID is part of the path, in which case routing should have added it to attributes
        $id = $request->getAttribute("id");
        if ($id !== null) {
            $rawData["id"] = $id;
        }

        // Return request with filtered body data
        return $request->withParsedBody($this->action->filter($rawData));
    }

    #[\Override]
    public function validateRequest(ServerRequestInterface $request): void
    {
        /** @var array<string, mixed> $data */
        $data = (array) $request->getParsedBody();
        $this->action->validate($data);
    }

    #[\Override]
    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array<string, mixed> $data */
        $data = (array) $request->getParsedBody();
        $result = $this->action->execute($data);

        return $this->responseTransformer->transform(
            data: [
                "success" => true,
                "data" => $result,
            ],
            statusCode: $this->successStatus,
        );
    }
}
