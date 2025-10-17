<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Model\UserCredential;
use App\Infrastructure\Security\GoogleAuthService;
use App\Infrastructure\Security\JwtTokenValidator;
use App\Infrastructure\Security\OAuthAuthorizationCodeStore;
use App\Infrastructure\Security\UserCredentialRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * OAuth 2.1 Authorization Server for MCP Redmine.
 * Allows users to register their Redmine credentials and get access tokens.
 */
final class OAuthController extends AbstractController
{
    public function __construct(
        private readonly UserCredentialRepository $credentialRepository,
        private readonly JwtTokenValidator $tokenValidator,
        private readonly OAuthAuthorizationCodeStore $codeStore,
        private readonly GoogleAuthService $googleAuth,
    ) {
    }

    /**
     * RFC 9728: OAuth 2.0 Protected Resource Metadata.
     * Tells clients where the authorization server is.
     */
    #[Route('/.well-known/oauth-protected-resource', name: 'oauth_metadata', methods: ['GET'])]
    public function metadata(Request $request): JsonResponse
    {
        // Use X-Forwarded-Proto header from ngrok/reverse proxy
        $scheme = $request->headers->get('X-Forwarded-Proto', $request->getScheme());
        $host = $request->getHost();
        $baseUrl = $scheme.'://'.$host;

        return new JsonResponse([
            'resource' => $baseUrl.'/mcp',
            'authorization_servers' => [$baseUrl],
            'bearer_methods_supported' => ['header'],
            'resource_signing_alg_values_supported' => ['HS256'],
        ]);
    }

    /**
     * RFC 8414: OAuth 2.0 Authorization Server Metadata.
     * Describes the OAuth endpoints and capabilities.
     */
    #[Route('/.well-known/oauth-authorization-server', name: 'oauth_authorization_server_metadata', methods: ['GET'])]
    public function authorizationServerMetadata(Request $request): JsonResponse
    {
        // Use X-Forwarded-Proto header from ngrok/reverse proxy
        $scheme = $request->headers->get('X-Forwarded-Proto', $request->getScheme());
        $host = $request->getHost();
        $baseUrl = $scheme.'://'.$host;

        return new JsonResponse([
            'issuer' => $baseUrl,
            'authorization_endpoint' => $baseUrl.'/oauth/authorize',
            'token_endpoint' => $baseUrl.'/oauth/token',
            'registration_endpoint' => $baseUrl.'/oauth/register',
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code'],
            'token_endpoint_auth_methods_supported' => ['none', 'client_secret_post'],
            'code_challenge_methods_supported' => ['plain', 'S256'],
        ]);
    }

    /**
     * RFC 7591: OAuth 2.0 Dynamic Client Registration.
     * Allows clients to register themselves automatically.
     */
    #[Route('/oauth/register', name: 'oauth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        // Parse JSON body
        $data = json_decode($request->getContent(), true);

        // Generate client credentials
        $clientId = 'mcp-'.bin2hex(random_bytes(16));
        $clientSecret = bin2hex(random_bytes(32));

        // Get base URL for response
        $scheme = $request->headers->get('X-Forwarded-Proto', $request->getScheme());
        $host = $request->getHost();
        $baseUrl = $scheme.'://'.$host;

        // Return client registration response (RFC 7591)
        return new JsonResponse([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'client_id_issued_at' => time(),
            'client_secret_expires_at' => 0, // Never expires
            'redirect_uris' => $data['redirect_uris'] ?? [],
            'token_endpoint_auth_method' => 'none',
            'grant_types' => ['authorization_code'],
            'response_types' => ['code'],
        ], 201);
    }

    /**
     * OAuth authorization endpoint.
     * Redirects to Google for authentication, then shows Redmine credential form.
     */
    #[Route('/oauth/authorize', name: 'oauth_authorize', methods: ['GET'])]
    public function authorize(Request $request): Response
    {
        $clientId = $request->query->get('client_id');
        $redirectUri = $request->query->get('redirect_uri');
        $state = $request->query->get('state');

        // Validate required parameters
        if (!$clientId || !$redirectUri) {
            return new JsonResponse(['error' => 'invalid_request', 'error_description' => 'Missing client_id or redirect_uri'], 400);
        }

        // Store OAuth params in session to retrieve after Google callback
        $session = $request->getSession();
        $session->set('oauth_client_id', $clientId);
        $session->set('oauth_redirect_uri', $redirectUri);
        $session->set('oauth_state', $state);

        // Get Google authorization URL
        $googleAuth = $this->googleAuth->getAuthorizationUrl();

        // Store Google state in session for CSRF protection
        $session->set('google_oauth_state', $googleAuth['state']);

        // Redirect to Google
        return $this->redirect($googleAuth['url']);
    }

    /**
     * Google OAuth callback endpoint.
     * Receives the user from Google, then shows form to enter Redmine credentials.
     */
    #[Route('/oauth/google-callback', name: 'oauth_google_callback', methods: ['GET', 'POST'])]
    public function googleCallback(Request $request): Response
    {
        $session = $request->getSession();

        // Handle GET: Google redirects back with authorization code
        if ($request->isMethod('GET')) {
            $code = $request->query->get('code');
            $state = $request->query->get('state');
            $expectedState = $session->get('google_oauth_state');

            if (!$code || !is_string($code)) {
                return new JsonResponse(['error' => 'access_denied', 'error_description' => 'User denied access or Google error'], 400);
            }

            try {
                // Exchange code for user info
                $googleUser = $this->googleAuth->handleCallback($code, (string) $state, $expectedState);

                // Validate user email is authorized
                if (!$this->isEmailAuthorized($googleUser['email'])) {
                    return new JsonResponse([
                        'error' => 'access_denied',
                        'error_description' => sprintf(
                            'Email "%s" is not authorized to access this application. Please contact your administrator.',
                            $googleUser['email']
                        ),
                    ], 403);
                }

                // Store Google user info in session
                $session->set('google_user_email', $googleUser['email']);
                $session->set('google_user_name', $googleUser['name']);

                // Check if user already has credentials
                if ($this->credentialRepository->exists($googleUser['email'])) {
                    // User already registered, complete authorization
                    return $this->completeAuthorization($googleUser['email'], $session);
                }

                // Show Redmine credentials form
                return $this->render('oauth/authorize.html.twig', [
                    'google_user_name' => $googleUser['name'],
                    'google_user_email' => $googleUser['email'],
                    'client_id' => $session->get('oauth_client_id'),
                    'redirect_uri' => $session->get('oauth_redirect_uri'),
                    'state' => $session->get('oauth_state'),
                ]);
            } catch (\RuntimeException $e) {
                return new JsonResponse(['error' => 'server_error', 'error_description' => $e->getMessage()], 500);
            }
        }

        // Handle POST: User submitted Redmine credentials
        $redmineUrl = $request->request->get('redmine_url');
        $redmineApiKey = $request->request->get('redmine_api_key');
        $userEmail = $session->get('google_user_email');

        if (!$userEmail) {
            return new JsonResponse(['error' => 'invalid_request', 'error_description' => 'Session expired, please start authorization again'], 400);
        }

        if (!$redmineUrl || !is_string($redmineUrl) || !$redmineApiKey || !is_string($redmineApiKey)) {
            return $this->render('oauth/authorize.html.twig', [
                'google_user_name' => $session->get('google_user_name'),
                'google_user_email' => $userEmail,
                'error' => 'Please provide both Redmine URL and API key',
            ]);
        }

        // Store credentials with email as userId
        $credential = new UserCredential(
            userId: $userEmail,
            redmineUrl: rtrim($redmineUrl, '/'),
            redmineApiKey: $redmineApiKey,
            createdAt: new \DateTimeImmutable(),
        );
        $this->credentialRepository->save($credential);

        return $this->completeAuthorization($userEmail, $session);
    }

    /**
     * Complete OAuth authorization by generating auth code and redirecting to client.
     */
    private function completeAuthorization(string $userId, \Symfony\Component\HttpFoundation\Session\SessionInterface $session): Response
    {
        $clientId = $session->get('oauth_client_id');
        $redirectUri = $session->get('oauth_redirect_uri');
        $state = $session->get('oauth_state');

        // Generate and store authorization code
        $authCode = bin2hex(random_bytes(32));
        $this->codeStore->store($authCode, [
            'user_id' => $userId,
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
        ]);

        // Clear session
        $session->remove('oauth_client_id');
        $session->remove('oauth_redirect_uri');
        $session->remove('oauth_state');
        $session->remove('google_oauth_state');
        $session->remove('google_user_email');
        $session->remove('google_user_name');

        // Redirect back to client with authorization code
        $redirectUrl = $redirectUri.'?code='.$authCode;
        if ($state) {
            $redirectUrl .= '&state='.urlencode($state);
        }

        return $this->redirect($redirectUrl);
    }

    /**
     * OAuth token endpoint.
     * Exchanges authorization code for JWT access token.
     */
    #[Route('/oauth/token', name: 'oauth_token', methods: ['POST'])]
    public function token(Request $request): JsonResponse
    {
        $grantType = $request->request->get('grant_type');
        $code = $request->request->get('code');
        $redirectUri = $request->request->get('redirect_uri');

        // Validate grant type
        if ('authorization_code' !== $grantType) {
            return new JsonResponse(['error' => 'unsupported_grant_type'], 400);
        }

        if (!is_string($code)) {
            return new JsonResponse(['error' => 'invalid_request', 'error_description' => 'Missing code parameter'], 400);
        }

        // Retrieve and consume authorization code (one-time use, auto-expires)
        $authData = $this->codeStore->consumeOnce($code);

        if (null === $authData) {
            return new JsonResponse(['error' => 'invalid_grant', 'error_description' => 'Invalid or expired authorization code'], 400);
        }

        // Validate redirect URI matches
        if ($authData['redirect_uri'] !== $redirectUri) {
            return new JsonResponse(['error' => 'invalid_grant', 'error_description' => 'Redirect URI mismatch'], 400);
        }

        // Generate JWT access token
        $accessToken = $this->tokenValidator->createToken(
            userId: $authData['user_id'],
            expiresIn: 86400, // 24 hours
        );

        return new JsonResponse([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 86400,
        ]);
    }

    /**
     * Check if an email is authorized to access this application.
     *
     * Authorized emails are configured via environment variables:
     * - ALLOWED_EMAIL_DOMAINS: Comma-separated list of allowed domains (e.g., "company.com,example.org")
     * - ALLOWED_EMAILS: Comma-separated list of specific allowed emails (e.g., "alice@example.com,bob@example.com")
     */
    private function isEmailAuthorized(string $email): bool
    {
        // Check allowed domains
        $allowedDomains = $_ENV['ALLOWED_EMAIL_DOMAINS'] ?? '';
        if ('' !== $allowedDomains) {
            $domains = array_map('trim', explode(',', $allowedDomains));
            foreach ($domains as $domain) {
                if ('' !== $domain && str_ends_with($email, '@'.$domain)) {
                    return true;
                }
            }
        }

        // Check whitelisted emails
        $allowedEmails = $_ENV['ALLOWED_EMAILS'] ?? '';
        if ('' !== $allowedEmails) {
            $emails = array_map('trim', explode(',', $allowedEmails));
            if (in_array($email, $emails, true)) {
                return true;
            }
        }

        return false;
    }
}
