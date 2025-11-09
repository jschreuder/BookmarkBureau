<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Controller;

use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Entity\Value\TokenResponse;
use jschreuder\BookmarkBureau\Entity\Value\TokenType;
use jschreuder\BookmarkBureau\Exception\UserNotFoundException;
use jschreuder\BookmarkBureau\InputSpec\LoginInputSpec;
use jschreuder\BookmarkBureau\OutputSpec\TokenOutputSpec;
use jschreuder\BookmarkBureau\Response\ResponseTransformerInterface;
use jschreuder\BookmarkBureau\Service\JwtServiceInterface;
use jschreuder\BookmarkBureau\Service\TotpVerifierInterface;
use jschreuder\BookmarkBureau\Service\UserServiceInterface;
use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestFilterInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class LoginController implements
    ControllerInterface,
    RequestFilterInterface,
    RequestValidatorInterface
{
    public function __construct(
        private LoginInputSpec $inputSpec,
        private UserServiceInterface $userService,
        private JwtServiceInterface $jwtService,
        private TotpVerifierInterface $totpVerifier,
        private TokenOutputSpec $tokenOutputSpec,
        private ResponseTransformerInterface $responseTransformer,
    ) {}

    #[\Override]
    public function filterRequest(
        ServerRequestInterface $request,
    ): ServerRequestInterface {
        $rawData = (array) $request->getParsedBody();
        $filtered = $this->inputSpec->filter($rawData);
        return $request->withParsedBody($filtered);
    }

    #[\Override]
    public function validateRequest(ServerRequestInterface $request): void
    {
        $data = (array) $request->getParsedBody();
        $this->inputSpec->validate($data);
    }

    #[\Override]
    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $data = (array) $request->getParsedBody();

        try {
            // Get user by email
            $email = new Email($data["email"]);
            $user = $this->userService->getUserByEmail($email);

            // Verify password
            if (!$this->userService->verifyPassword($user, $data["password"])) {
                throw new \InvalidArgumentException("Invalid credentials");
            }

            // Verify TOTP if enabled on user account
            if ($user->requiresTotp()) {
                $totpCode = $data["totp_code"] ?? "";
                if (empty($totpCode)) {
                    throw new \InvalidArgumentException("TOTP code required");
                }
                if (
                    !$this->totpVerifier->verify($totpCode, $user->totpSecret)
                ) {
                    throw new \InvalidArgumentException("Invalid TOTP code");
                }
            }

            // Generate appropriate token based on rememberMe flag
            $rememberMe = $data["remember_me"] ?? false;
            $tokenType = $rememberMe
                ? TokenType::REMEMBER_ME_TOKEN
                : TokenType::SESSION_TOKEN;

            $jwtToken = $this->jwtService->generate($user, $tokenType);
            $claims = $this->jwtService->verify($jwtToken);

            $tokenResponse = new TokenResponse(
                $jwtToken,
                $tokenType->value,
                $claims->expiresAt,
            );

            return $this->responseTransformer->transform(
                data: [
                    "success" => true,
                    "data" => $this->tokenOutputSpec->transform($tokenResponse),
                ],
                statusCode: 200,
            );
        } catch (UserNotFoundException $e) {
            throw new \InvalidArgumentException("Invalid credentials");
        }
    }
}
