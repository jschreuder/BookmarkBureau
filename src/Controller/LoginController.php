<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Controller;

use InvalidArgumentException;
use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Entity\Value\TokenResponse;
use jschreuder\BookmarkBureau\Entity\Value\TokenType;
use jschreuder\BookmarkBureau\Exception\UserNotFoundException;
use jschreuder\BookmarkBureau\InputSpec\InputSpecInterface;
use jschreuder\BookmarkBureau\OutputSpec\TokenOutputSpec;
use jschreuder\BookmarkBureau\Response\ResponseTransformerInterface;
use jschreuder\BookmarkBureau\Service\JwtServiceInterface;
use jschreuder\BookmarkBureau\Service\RateLimitServiceInterface;
use jschreuder\BookmarkBureau\Service\TotpVerifierInterface;
use jschreuder\BookmarkBureau\Service\UserServiceInterface;
use jschreuder\BookmarkBureau\Util\IpAddress;
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
        private InputSpecInterface $inputSpec,
        private UserServiceInterface $userService,
        private JwtServiceInterface $jwtService,
        private TotpVerifierInterface $totpVerifier,
        private TokenOutputSpec $tokenOutputSpec,
        private ResponseTransformerInterface $responseTransformer,
        private RateLimitServiceInterface $rateLimitService,
        private bool $trustProxyHeaders = false,
    ) {}

    #[\Override]
    public function filterRequest(
        ServerRequestInterface $request,
    ): ServerRequestInterface {
        /** @var array<string, mixed> $rawData */
        $rawData = (array) $request->getParsedBody();
        $filtered = $this->inputSpec->filter($rawData);
        return $request->withParsedBody($filtered);
    }

    #[\Override]
    public function validateRequest(ServerRequestInterface $request): void
    {
        /** @var array<string, mixed> $data */
        $data = (array) $request->getParsedBody();
        $this->inputSpec->validate($data);
    }

    #[\Override]
    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array{email: string, password: string, remember_me: bool, totp_code: ?string} $data */
        $data = (array) $request->getParsedBody();
        $email = new Email($data["email"]);

        // First check if there's any rate-limit blocks
        $clientIp = IpAddress::fromRequest($request, $this->trustProxyHeaders);
        $this->rateLimitService->checkBlock($email->value, $clientIp);

        try {
            // Get user by email
            $user = $this->userService->getUserByEmail($email);
            // Verify password
            if (!$this->userService->verifyPassword($user, $data["password"])) {
                $this->rateLimitService->recordFailure(
                    $email->value,
                    $clientIp,
                );
                throw new InvalidArgumentException("Invalid credentials");
            }

            // Verify TOTP if enabled on user account
            if ($user->requiresTotp()) {
                $totpCode = $data["totp_code"];
                if (empty($totpCode)) {
                    $this->rateLimitService->recordFailure(
                        $email->value,
                        $clientIp,
                    );
                    throw new InvalidArgumentException("TOTP code required");
                }
                if (
                    !$this->totpVerifier->verify($totpCode, $user->totpSecret)
                ) {
                    $this->rateLimitService->recordFailure(
                        $email->value,
                        $clientIp,
                    );
                    throw new InvalidArgumentException("Invalid TOTP code");
                }
            }

            // Everything checked out, clear username failures
            $this->rateLimitService->clearUsername($email->value);

            // Generate appropriate token based on rememberMe flag
            $rememberMe = $data["remember_me"];
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
            $this->rateLimitService->recordFailure($email->value, $clientIp);
            throw new InvalidArgumentException("Invalid credentials");
        }
    }
}
