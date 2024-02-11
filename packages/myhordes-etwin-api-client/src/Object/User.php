<?php


namespace EternalTwinClient\Object;


use DateTime;
use Exception;

/**
 * Class User
 * @package ETwinOAuth\Object
 * @method string getID()
 * @method bool isAdministrator()
 * @method string|null getUsername()
 * @method string|null getEmailAddress()
 * @method bool|null hasPassword()
 * @method string getDisplayName()
 * @method DateTime getCTime()
 */
class User extends ResponseObject
{
    protected const EXPECTED_TYPE = 'User';
    protected const ENTRY_WHITELIST = [
        'id', 'administrator', 'username', 'email_address', 'password', 'display_name', 'ctime'
    ];

    protected function fetch(string $name, $args)
    {
        switch ($name) {
            case 'display_name': return $this->_data['display_name']['current']['value'] ?? '';
            case 'ctime':
                try {
                    return new DateTime($this->_data['ctime'] ?? 'now');
                } catch (Exception $e) {
                    return new DateTime();
                }
            default: return parent::fetch($name, $args);
        }
    }


}