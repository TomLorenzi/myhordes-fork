<?php


namespace App\Service\Globals;


class TranslationConfigGlobal
{
    /**
     * @var bool|array
     */
    private $matchFileNames = false;
    private bool $includeDatabase = true;
    private bool $includePhp = true;
    private bool $includeTwig = true;
    private bool $includeConfig = true;
    private bool $configured = false;

    private array $source_cache = [];

    function add_source_for(string $message, string $domain, string $handler, string $source, int $line = -1): void
    {
        $m = $line >= 0 ? "$handler://$source:$line" : "$handler://$source";
        if (!isset($this->source_cache[$domain])) $this->source_cache[$domain] = [];
        if (!isset($this->source_cache[$domain][$message])) $this->source_cache[$domain][$message] = [];
        $this->source_cache[$domain][$message][] = $m;
        $this->source_cache[$domain][$message] = array_unique($this->source_cache[$domain][$message]);
    }

    function get_sources_for(string $message, string $domain): array {
        $domain = explode('+',$domain)[0];
        return $this->source_cache[$domain][$message] ?? [];
    }

    function useFileNameMatching(): bool {
        return $this->matchFileNames !== false;
    }

    function matchingFileNames(): array {
        return $this->useFileNameMatching() ? $this->matchFileNames : [];
    }

    function useDatabase(): bool {
        return $this->includeDatabase;
    }

    function usePHP(): bool {
        return $this->includePhp;
    }

    function useTwig(): bool {
        return $this->includeTwig;
    }

    function useConfig(): bool {
        return $this->includeConfig;
    }

    function addMatchedFileName(string $file): self {
        if ($this->matchFileNames === false) $this->matchFileNames = [$file];
        else $this->matchFileNames[] = $file;
        $this->configured = true;
        return $this;
    }

    function setDatabaseSearch(bool $conf): self {
        $this->includeDatabase = $conf;
        $this->configured = true;
        return $this;
    }

    function setPHPSearch(bool $conf): self {
        $this->includePhp = $conf;
        $this->configured = true;
        return $this;
    }

    function setTwigSearch(bool $conf): self {
        $this->includeTwig = $conf;
        $this->configured = true;
        return $this;
    }

    function setConfigSearch(bool $conf): self {
        $this->includeConfig = $conf;
        $this->configured = true;
        return $this;
    }

    function isExhaustive(): bool {
        return $this->includeDatabase && $this->includePhp && $this->includeTwig && $this->includeConfig && !$this->useFileNameMatching();
    }

    function setConfigured(bool $b) {
        $this->configured = $b;
    }

    function isConfigured(): bool {
        return $this->configured;
    }
}