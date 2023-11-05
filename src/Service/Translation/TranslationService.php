<?php

namespace App\Service\Translation;

use App\Entity\AccountRestriction;
use App\Entity\ConnectionIdentifier;
use App\Entity\User;
use App\Service\ConfMaster;
use App\Service\UserHandler;
use App\Structures\CheatTable;
use DateTime;
use DirectoryIterator;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\Loader\FileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\MessageCatalogue;

class TranslationService {

    /** @var array<MessageCatalogue>  */
    private $cache = [];

    public function __construct(
        private readonly ParameterBagInterface $params,
        private readonly KernelInterface $kernel
    ) { }

    protected function getTranslationSearchPaths(string|false|null $bundle): array {
        $searchPaths = match(true) {
            $bundle === false => ["{$this->params->get('kernel.project_dir')}/translations"],
            $bundle === null => array_map(fn(BundleInterface $bundle) => ["{$bundle->getPath()}/Resources/translations", "{$bundle->getPath()}/translations"], $this->kernel->getBundles()),
            default => ["{$this->kernel->getBundle($bundle)->getPath()}/Resources/translations", "{$this->kernel->getBundle($bundle)->getPath()}/translations"]
        };

        $trueSearchPaths = [];
        array_walk_recursive( $searchPaths, function (string $directory) use (&$trueSearchPaths) {
            if (is_dir( $directory )) $trueSearchPaths[] = $directory;
        } );

        return $trueSearchPaths;
    }

    public function getExistingDomains(string|false|null $bundle = null, ?string $locale = 'de'): array {

        $searchPaths = $this->getTranslationSearchPaths( $bundle );

        $known_domains = [];
        foreach ($searchPaths as $searchPath)
            foreach (new DirectoryIterator($searchPath) as $fileInfo) {
                /** @var SplFileInfo $fileInfo */
                @list($domain_icu,$language,$type) = explode('.', $fileInfo->getFilename());
                if ($type !== 'yml' || ($locale && $language !== $locale)) continue;
                $known_domains[ explode('+', $domain_icu)[0] ] = true;
            };

        return array_filter( array_keys($known_domains), fn($d) => !empty($d) );
    }

    private function getCacheIdentifier(string|false|null $bundle, string|array|null $domains, string $locale) {
        return match(true) {
            $bundle === null => '-all-bundles-',
            $bundle === false => '-core-',
            default => $bundle
        } . '|' . match(true) {
                $domains === null => '-all-domains-',
                is_array( $domains ) => implode(',', $domains),
                is_string( $domains ) => $domains,
            } . '|' . $locale;
    }

    public function getMessageSubCatalogue(string|false|null $bundle = null, string|array|null $domains = null, string $locale = 'de'): MessageCatalogue {
        $identifier = $this->getCacheIdentifier($bundle,$domains,$locale);
        if (array_key_exists( $identifier, $this->cache )) return $this->cache[$identifier];

        $searchPaths = $this->getTranslationSearchPaths( $bundle );

        $domains = match (true) {
            $domains === null => $this->getExistingDomains( $bundle, $locale ),
            is_array( $domains) => $domains,
            is_string( $domains ) => [$domains],
            default => []
        };

        $filesToLoad = [];
        foreach ($searchPaths as $searchPath)
            foreach ($domains as $domain)
                foreach (array_filter([
                                          "{$searchPath}/{$domain}+intl-icu.{$locale}.yml",
                                          "{$searchPath}/{$domain}.{$locale}.yml"
                                      ], fn(string $file) => file_exists( $file )) as $file)
                    $filesToLoad[] = [$file,$domain];

        $catalogue = new MessageCatalogue($locale);

        if (empty($filesToLoad)) return $this->cache[$identifier] = $catalogue;

        $file_loader = new YamlFileLoader();

        foreach ($filesToLoad as [$file,$domain])
            $catalogue->addCatalogue( $file_loader->load( $file, $locale, $domain ) );

        return $this->cache[$identifier] = $catalogue;
    }

}