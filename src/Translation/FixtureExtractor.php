<?php

namespace App\Translation;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;

class FixtureExtractor implements ExtractorInterface
{
    protected $prefix;
    protected $em;

    protected $fixturesGame = ['geöffnet','geschlossen','betreten','verlassen','Zombies',
                                'Nordosten','Nordwesten','Norden','Südosten','Südwesten',
                                'Süden','Osten','Westen','Horizont'];

    protected static $has_been_run = false;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    private function insert(MessageCatalogue &$c, string $message, string $domain) {
        $c->set( $message, $this->prefix . $message, $domain );
    }

    /**
     * @inheritDoc
     */
    public function extract($resource, MessageCatalogue $c)
    {
        if (self::$has_been_run) return;
        self::$has_been_run = true;

        foreach ($this->fixturesGame as $fixture)
            $this->insert( $c, $fixture, 'game' );
    }

    /**
     * @inheritDoc
     */
    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;
    }
}