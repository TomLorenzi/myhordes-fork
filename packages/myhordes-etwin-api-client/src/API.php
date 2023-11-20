<?php


namespace EternalTwinClient;

use EternalTwinClient\Object\AccessToken as AccessToken;
use EternalTwinClient\Object\User;

class API
{
    /** @var string Default eternal twin API url */
    const DEFAULT_BASE_URL = 'https://eternal-twin.net/api/v1';

    /** @var string Default eternal twin authorization url */
    const DEFAULT_AUTH_URL = 'https://eternal-twin.net/oauth/authorize';

    /** @var string Default eternal twin grant url */
    const DEFAULT_GRANT_URL = 'https://eternal-twin.net/oauth/token';

    /** @var int Default timeout for all HTTP requests */
    const DEFAULT_HTTP_TIMEOUT = 30;

    /** @var int Error code for failed connections */
    const ERROR_CONNECTION_FAILED = 1;

    /** @var int Error code for failing to parse JSON responses */
    const ERROR_JSON_INVALID = 2;

    /** @var string|null The secret key to authorize against eternal twin */
    private ?string $_secretKey = null;

    /** @var string|null The client app name to identify against eternal twin */
    private ?string $_clientID = null;

    /** @var string|null The eternal twin OAuth API url */
    private ?string $_oauth_base_URL = null;

    /** @var string|null The eternal twin OAuth authorization url */
    private ?string $_oauth_auth_URL = null;

    /** @var string|null The eternal twin OAuth grant url */
    private ?string $_oauth_grant_URL = null;

    /** @var string|null The return URL */
    private ?string $_returnURL = null;

    /** @var bool Holds the configuration state; is set by the constructor to indicate if all conf props are present */
    private bool $_is_configured;

    /** @var string|null The authorization code */
    private ?string $_code = null;

    /** @var AccessToken The last valid access token */
    private AccessToken $_token;

    /**
     * Client constructor. If $sk, $id or $return are empty, a non-functioning Client will be constructed.
     * @param string|null $sk The secret key to authorize against eternal twin
     * @param string|null $id The client app name to identify against eternal twin
     * @param string|null $return The return URL
     * @param string|null $base The eternal twin OAuth API url (optional)
     * @param string|null $auth The eternal twin OAuth auth url (optional)
     * @param string|null $grant The eternal twin OAuth grant url (optional)
     */
    public function __construct(?string $sk, ?string $id, ?string $return,
                                ?string $base = null, ?string $auth = null, ?string $grant = null)
    {
        $this->_is_configured =
            !empty($this->_secretKey = $sk) &&
            !empty($this->_clientID = $id) &&
            !empty($this->_oauth_base_URL =  ($base ?? static::DEFAULT_BASE_URL)) &&
            !empty($this->_oauth_auth_URL =  ($auth ?? static::DEFAULT_AUTH_URL)) &&
            !empty($this->_oauth_grant_URL = ($grant ?? static::DEFAULT_GRANT_URL)) &&
            !empty($this->_returnURL = $return);

        // Set a default invalid token
        $this->_token = new AccessToken();
    }

    /**
     * Indicates if the Client is functional. If any conf props other than the eternal twin oauth urls have ben omitted,
     * this function will return false and all requests made by the client will instantly fail.
     * @return bool
     */
    public function isReady( ): bool
    {
        return $this->_is_configured;
    }

    /**
     * Indicates if an authorization code has been set.
     * @return bool
     */
    public function hasAuthorizationCode( ): bool
    {
        return !empty( $this->_code );
    }

    /**
     * Returns the URL the user needs to be redirected to in order to obtain an authorization code.
     * @param string $state
     * @param array $scopes
     * @return string|null The
     */
    public function createAuthorizationRequest(string $state, array $scopes = []): ?string {
        // Make sure all URL parts are properly encoded
        $client = urlencode($this->_clientID);
        $return = urlencode($this->_returnURL);
        $state  = urlencode($state);
        $scopes = urlencode(implode(' ', $scopes));

        return $this->isReady()
            ? "{$this->_oauth_auth_URL}?response_type=code&client_id={$client}&redirect_uri={$return}&scope={$scopes}&state={$state}&access_type=offline"
            : null;
    }

    /**
     * Sets the authorization code
     * @param string $code
     */
    public function setAuthorizationCode( string $code ): void
    {
        $this->_code = $code;
    }

    /**
     * Fetches an access token from eternal twin. Requires an authorization code.
     * @param int|string|null $error Optional error indicator; either an error code (int) or error message (string) will
     * be written when fetching the token fails.
     * @return AccessToken
     */
    public function getAccessToken( &$error = null ): AccessToken
    {
        // No need to proceed further if the client is not ready or does not have a code yet
        if (!$this->isReady() || !$this->hasAuthorizationCode()) return new AccessToken();

        // Generate the auth string
        $auth = base64_encode( "{$this->_clientID}:{$this->_secretKey}" );

        // Create a POST request and send it to eternal twin
        $response = file_get_contents($this->_oauth_grant_URL, false, stream_context_create([
            'http' => [
                'method' => "POST",
                'header' => "Content-type: application/x-www-form-urlencoded\r\nAuthorization: Basic {$auth}",
                'timeout' => static::DEFAULT_HTTP_TIMEOUT,
                'content' => http_build_query($form = [
                    'client_id'         => $this->_clientID,
                    'client_secret'     => $this->_secretKey,
                    'redirect_uri'      => $this->_returnURL,
                    'code'              => $this->_code,
                    'grant_type'        => 'authorization_code',
                ])
            ]
        ]));

        // If the response is FALSE, we did not get an actual connection
        if ($response === false) {
            $error = self::ERROR_CONNECTION_FAILED;
            return new AccessToken();
        }

        // Parse the response
        $token_response = json_decode($response, true, 512, JSON_INVALID_UTF8_IGNORE);

        // If the parsed response does not return an array, something must have gone wrong
        if (!is_array($token_response)) {
            $error = self::ERROR_JSON_INVALID;
            return new AccessToken();
        }

        // If an error field is present, abort
        if (isset($token_response["error"])) {
            $error = $token_response["error"];
            return new AccessToken();
        }

        // Create a new access token and replace the old one if it is valid
        $token = new AccessToken($token_response);
        if ($token->isValid()) $this->_token = $token;
        return $token;
    }

    protected function get_raw( array $route, &$error = null ): ?array {
        // Check if the current token is valid; otherwise, try requesting a new token.
        // If the new token is also invalid, cancel and return NULL
        if ( !$this->_token->isValid() && !$this->getAccessToken($error)->isValid() ) return null;

        // Create the actual request
        $response = file_get_contents(
            array_reduce( $route, fn($c,$i) => $c . '/' . urlencode($i), $this->_oauth_base_URL ),
            false, stream_context_create([
            'http' => [
                'method' => "GET",
                'header' => "Authorization: Bearer {$this->_token}",
                'timeout' => static::DEFAULT_HTTP_TIMEOUT
            ]
        ]));

        // If the response is FALSE, we did not get an actual connection
        if ($response === false) {
            $error = self::ERROR_CONNECTION_FAILED;
            return null;
        }

        // Parse the response
        $data = json_decode($response, true, 512, JSON_INVALID_UTF8_IGNORE);

        // If the parsed response does not return an array, something must have gone wrong
        if (!is_array($data)) {
            $error = self::ERROR_JSON_INVALID;
            return null;
        }

        // If an error field is present, abort
        if (isset($data["error"])) {
            $error = $data["error"];
            return null;
        }

        // Return
        return $data;
    }

    public function requestAuthSelf( &$error = null ): User {
        return isset(($raw_data = $this->get_raw(['auth','self'], $error))['user']) ? new User($raw_data['user']) : new User();
    }

    public function requestUser( string $id, &$error = null ): User {
        return isset(($raw_data = $this->get_raw(['users',$id], $error))['user']) ? new User($raw_data['user']) : new User();
    }
}
