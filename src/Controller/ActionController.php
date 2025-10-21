<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Controller;

use jschreuder\BookmarkBureau\Controller\Action\ActionInterface;
use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestFilterInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Generic controller for simple CRUD operations
 * 
 * The Closure must always return an array that can be safely converted to JSON
 */
final class ActionController implements 
    ControllerInterface,
    RequestFilterInterface,
    RequestValidatorInterface
{
    public function __construct(
        private ActionInterface $action,
        private int $successStatus = 200
    ) {}

    public function filterRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        // Fetch input data, query parameters for GET request, the (parsed) body parameters for all others
        $rawData = $request->getMethod() === 'GET' ? $request->getQueryParams() : $request->getParsedBody();
        if (!is_array($rawData)) {
            $rawData = [];
        }

        // Often the ID is part of the path, in which case routing should have added it to attributes
        $id = $request->getAttribute('id');
        if (!is_null($id)) {
            $rawData['id'] = $id;
        }
        
        // Return request with filtered body data
        return $request->withParsedBody($this->action->filter($rawData));
    }

    public function validateRequest(ServerRequestInterface $request): void
    {
        $data = (array) $request->getParsedBody();
        $this->action->validate($data);
    }

    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $result = $this->action->execute($data);
        
        return new JsonResponse([
            'success' => true,
            'data' => $result
        ], $this->successStatus);
    }
}
