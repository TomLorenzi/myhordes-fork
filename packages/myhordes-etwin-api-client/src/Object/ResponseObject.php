<?php


namespace EternalTwinClient\Object;


use Exception;

/**
 * Base class for all response objects for the EternalTwin API, with the exception of the access token.
 * Extend this class when adding support for new API responses.
 * @package EternalTwinClient\Object
 */
class ResponseObject
{
    /** @var string Expected entry type; use * for any */
    protected const EXPECTED_TYPE = '*';

    /** @var string[]|null Available property list */
    protected const ENTRY_WHITELIST = null;

    /** @var array Raw JSON data */
    protected array $_data;

    /** @var bool Validity flag */
    private bool $_valid;

    /**
     * ResponseObject constructor.
     * @param array|null $data EternalTwin raw data
     */
    public final function __construct(?array $data = null)
    {
        // If no data is passed, use an empty array
        $this->_data  = $data ?? [];

        // Set validity flag is data is not null and the entry type matches
        $this->_valid =
            $data !== null &&
            (static::EXPECTED_TYPE === '*') || (isset($data['type']) && $data['type'] === static::EXPECTED_TYPE);
    }

    /**
     * Checks the object for validity. Trying to fetch any data from an invalid object will return null.
     * @return bool True if the object is valid; otherwise false.
     */
    public function isValid(): bool
    {
        return $this->_valid;
    }

    /**
     * Internal fetch function; attempts to fetch a property by it's name and common variants (is_* and has_*)
     * Can be overwritten to handle specific properties.
     * You should not call this function manually; __call will take care of that. When this function has been called,
     * the whitelist has already been checked, but the existence of the property in the raw data has not.
     * @param string $name Property name in snake case
     * @param array $args If magic getter has been called with arguments, they are passed here
     * @return mixed Fetched property or null
     */
    protected function fetch(string $name, array $args)
    {
        return $this->_data[$name] ?? $this->_data["is_$name"] ?? $this->_data["has_$name"] ?? null;
    }

    /**
     * Invoking a ResponseObject instance returns its validity status.
     * @return bool
     */
    public final function __invoke(): bool
    {
        return $this->isValid();
    }

    /**
     * @param string $name
     * @param array $args
     * @return mixed|null
     * @throws Exception
     */
    public final function __call(string $name, array $args)
    {
        // Do not attempt to fetch anything if the instance is not valid
        if (!$this->isValid()) return null;

        // Convert CamelCase to snake_case
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $name, $matches);
        $blocks = array_map(
            fn(string $match) => ($match === strtoupper($match)) ? strtolower($match) : lcfirst($match),
            $matches[0]
        );

        // Magic methods have to start with get*, is* or has*
        // Reject any methods starting with something else
        if (count($blocks) < 2 || !in_array($blocks[0], ['get','is','has']))
            throw new Exception("'{$name}' is not linked to a valid property.");

        // Construct the snake case name
        $snake_name = implode( '_', array_slice($blocks, 1) );

        // Check if the name is contained in the whitelist, then call fetch()
        if (static::ENTRY_WHITELIST === null || in_array($snake_name, static::ENTRY_WHITELIST))
            return $this->fetch($snake_name, $args);
        else throw new Exception("'{$name}' is not linked to a valid property.");
    }
}