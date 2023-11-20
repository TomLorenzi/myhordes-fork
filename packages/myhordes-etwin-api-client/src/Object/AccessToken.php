<?php


namespace EternalTwinClient\Object;


use DateTime;
use Exception;

class AccessToken
{
    /** @var string|null The access token */
    private $_token;

    /** @var DateTime Expiration time of the token */
    private $_expires;

    /** @var string|null Token type */
    private $_type;

    public function __construct(?array $json_data = null)
    {
        if (!empty($json_data)) {
            $this->_token = $json_data['access_token'] ?? null;
            $this->_type  = $json_data['token_type']   ?? null;

            if (!empty( $json_data['expires_in'] ) && is_int( $json_data['expires_in'] ) && (int)$json_data['expires_in'] > 0)
                try {
                    $this->_expires = new DateTime('+' . (int)$json_data['expires_in'] . 'seconds');
                } catch (Exception $e) {
                    $this->_expires = new DateTime('-1seconds');
                }
            else $this->_expires = new DateTime('+60seconds');
        } else $this->_expires = new DateTime('-1seconds');
    }

    /**
     * Converts the AccessToken into the actual token. Will produce an empty string if the token is not valid
     * @return string
     */
    public function __toString(): string
    {
        return $this->getAccessToken() ?? '';
    }

    /**
     * Indicates if the AccessToken is (still) valid
     * @return bool
     */
    public function isValid(): bool
    {
        return !empty($this->_token) && $this->_expires > new DateTime();
    }

    /**
     * Returns the access token or null, if the token is not valid
     * @return string|null
     */
    public function getAccessToken(): ?string
    {
        return $this->isValid() ? $this->_token : null;
    }

    /**
     * Returns the access type. If the type is not set, an empty string will be returned.
     * @return string
     */
    public function getAccessType(): string
    {
        return $this->_type ?? '';
    }

    /**
     * Returns the token expiration time
     * @return DateTime
     */
    public function getExpirationTime(): DateTime
    {
        return $this->_expires;
    }
}