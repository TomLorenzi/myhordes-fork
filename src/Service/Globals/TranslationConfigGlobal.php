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

    private array $source_cache = [];

    function add_source_for(string $message, string $domain, string $handler, string $source, int $line = -1) {
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

    function addMatchedFileName(string $file): self {
        if ($this->matchFileNames === false) $this->matchFileNames = [$file];
        else $this->matchFileNames[] = $file;
        return $this;
    }

    function setDatabaseSearch(bool $conf): self {
        $this->includeDatabase = $conf;
        return $this;
    }

    function setPHPSearch(bool $conf): self {
        $this->includePhp = $conf;
        return $this;
    }

    function setTwigSearch(bool $conf): self {
        $this->includeTwig = $conf;
        return $this;
    }

    function isExhaustive(): bool {
        return $this->includeDatabase && $this->includePhp && $this->includeTwig && !$this->useFileNameMatching();
    }
}