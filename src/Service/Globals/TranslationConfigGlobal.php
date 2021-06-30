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
}