<?php

namespace App\Service;

use App\Entity\Award;
use App\Entity\Citizen;
use App\Entity\Emotes;
use App\Entity\ForumUsagePermissions;
use App\Entity\Post;
use App\Entity\Town;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Symfony\Component\Asset\Packages;
use Symfony\Contracts\Translation\TranslatorInterface;

class HTMLService {

    private EntityManagerInterface $entity_manager;
    private PermissionHandler $perm;
    private TranslatorInterface $translator;
    private RandomGenerator $rand;
    private Packages $asset;

    public function __construct(EntityManagerInterface $em, PermissionHandler $perm, TranslatorInterface $trans, RandomGenerator $rand, Packages $a)
    {
        $this->entity_manager = $em;
        $this->perm = $perm;
        $this->translator = $trans;
        $this->rand = $rand;
        $this->asset = $a;
    }

    protected const HTML_LIB = [
        'tags' => [
            'core' => [
                'br' => [],
                'b' => [],
                'strong' => [],
                'i' => [],
                'em' => [],
                'u' => [],
                'del' => [],
                'strike' => [],
                's' => [],
                'q' => [],
                'p'  => [],
            ],
            'extended' => [
                'blockquote' => [],
                'pre' => [],
                'hr' => [],
                'ul' => [],
                'ol' => [],
                'li' => [],
                'div' => [ 'class', 'x-a', 'x-b' ],
                'span' => [ 'class' ],
                'a' => [ 'href', 'title' ],
                'figure' => [ 'style' ],
            ],
            'oracle' => [],
            'crow' => [],
            'admin' => [
                'img' => [ 'alt', 'src', 'title']
            ]
        ],
        'attribs' => [
            'core' => [],
            'extended' => [
                'div.class' => [
                    'clear',
                    'glory', 'spoiler', 'sideNote',
                    'dice-4', 'dice-6', 'dice-8', 'dice-10', 'dice-12', 'dice-20', 'dice-100',
                    'letter-a', 'letter-v', 'letter-c',
                    'rps', 'coin', 'card',
                    'citizen', 'rpText',
                ],
                'span.class' => [
                    'quoteauthor','bad','rpauthor','inline-code',
                ]
            ],
            'oracle' => [
                'div.class' => [
                    'oracleAnnounce'
                ]
            ],
            'crow' => [
                'div.class' => [
                    'modAnnounce', 'html'
                ]
            ],
            'admin' => [
                'div.class' => [
                    'adminAnnounce',
                ]
            ]
        ]
    ];


    protected function getAllowedHTML(int $permissions, bool $extended = true): array {
        $mods_enabled = ['core'];
        if ($extended) $mods_enabled[] = 'extended';
        if ($this->perm->isPermitted($permissions, ForumUsagePermissions::PermissionFormattingOracle))
            $mods_enabled[] = 'oracle';
        if ($this->perm->isPermitted($permissions, ForumUsagePermissions::PermissionFormattingModerator))
            $mods_enabled[] = 'crow';
        if ($this->perm->isPermitted($permissions, ForumUsagePermissions::PermissionFormattingAdmin))
            $mods_enabled[] = 'admin';

        $r = [];
        $a = [];

        $html_extension = function(array &$base, string $tag, array $list) {
            if (isset($base[$tag]))
                $base[$tag] = array_merge($base[$tag], $list);
            else
                $base[$tag] = $list;
        };

        foreach ($mods_enabled as $mod) {
            if (isset(self::HTML_LIB['tags'][$mod] )) foreach (self::HTML_LIB['tags'][$mod] as $k => $v) $html_extension($r,$k,$v);
            if (isset(self::HTML_LIB['attribs'][$mod] )) foreach (self::HTML_LIB['attribs'][$mod] as $k => $v) $html_extension($a,$k,$v);
        }

        return ['nodes' => $r, 'attribs' => $a];
    }

    protected function htmlValidator( array $allowedNodes, ?DOMNode $node, ?int &$text_length = null, int $depth = 0 ): bool {
        if (!$node || $depth > 32) return false;
        if ($text_length === null) $text_length = 0;

        if ($node->nodeType === XML_ELEMENT_NODE) {

            // Element not allowed.
            if (!in_array($node->nodeName, array_keys($allowedNodes['nodes'])) && !($depth === 0 && $node->nodeName === 'body')) {
                $node->parentNode->removeChild( $node );
                return true;
            }

            // Attributes not allowed.
            $remove_attribs = [];
            for ($i = 0; $i < $node->attributes->length; $i++) {
                if (!in_array($node->attributes->item($i)->nodeName, $allowedNodes['nodes'][$node->nodeName]))
                    $remove_attribs[] = $node->attributes->item($i)->nodeName;
                elseif (isset($allowedNodes['attribs']["{$node->nodeName}.{$node->attributes->item($i)->nodeName}"])) {
                    // Attribute values not allowed
                    $allowed_entries = $allowedNodes['attribs']["{$node->nodeName}.{$node->attributes->item($i)->nodeName}"];
                    $node->attributes->item($i)->nodeValue = implode( ' ', array_filter( explode(' ', $node->attributes->item($i)->nodeValue), function (string $s) use ($allowed_entries) {
                        return in_array( $s, $allowed_entries );
                    }));
                }
            }

            foreach ($remove_attribs as $attrib)
                $node->removeAttribute($attrib);

            $children = [];
            foreach ( $node->childNodes as $child )
                $children[] = $child;

            foreach ( $children as $child )
                if (!$this->htmlValidator( $allowedNodes, $child, $text_length, $depth+1 ))
                    return false;

            return true;

        } elseif ($node->nodeType === XML_TEXT_NODE) {
            $text_length += mb_strlen($node->textContent);
            return true;
        }
        else return false;
    }

    public function htmlPrepare(User $user, int $permissions, bool $extended, string &$text, ?Town $town = null, ?int &$tx_len = null, ?bool &$editable = null): bool {

        $editable = true;

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML( "<html lang=''><head><title></title><meta charset='UTF-8' /></head><body>$text</body></html>", LIBXML_COMPACT | LIBXML_NONET | LIBXML_HTML_NOIMPLIED);
        $body = $dom->getElementsByTagName('body');
        if (!$body || $body->length > 1) return false;

        if (!$this->htmlValidator($this->getAllowedHTML($permissions,$extended), $body->item(0),$tx_len))
            return false;

        $emotes = array_keys($this->get_emotes());

        $cache = [ 'citizen' => [] ];

        $handlers = [
            // This invalidates emote tags within code blocks to prevent them from being replaced when rendering the
            // post
            '//pre|//span[@class=\'inline-code\']' =>
                function (DOMNode $d) use(&$emotes) {
                    foreach ($emotes as $emote)
                        $d->nodeValue = str_replace( $emote, str_replace(':', ':​', $emote),  $d->nodeValue);
                },

            '//div[@class=\'dice-4\']'   => function (DOMNode $d) use(&$editable) { $editable = false; $d->nodeValue = mt_rand(1,4); },
            '//div[@class=\'dice-6\']'   => function (DOMNode $d) use(&$editable) { $editable = false; $d->nodeValue = mt_rand(1,6); },
            '//div[@class=\'dice-8\']'   => function (DOMNode $d) use(&$editable) { $editable = false; $d->nodeValue = mt_rand(1,8); },
            '//div[@class=\'dice-10\']'  => function (DOMNode $d) use(&$editable) { $editable = false; $d->nodeValue = mt_rand(1,10); },
            '//div[@class=\'dice-12\']'  => function (DOMNode $d) use(&$editable) { $editable = false; $d->nodeValue = mt_rand(1,12); },
            '//div[@class=\'dice-20\']'  => function (DOMNode $d) use(&$editable) { $editable = false; $d->nodeValue = mt_rand(1,20); },
            '//div[@class=\'dice-100\']' => function (DOMNode $d) use(&$editable) { $editable = false; $d->nodeValue = mt_rand(1,100); },
            '//div[@class=\'letter-a\']' => function (DOMNode $d) use(&$editable) { $editable = false; $l = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'; $d->nodeValue = $l[mt_rand(0,strlen($l)-1)]; },
            '//div[@class=\'letter-c\']' => function (DOMNode $d) use(&$editable) { $editable = false; $l = 'BCDFGHJKLMNPQRSTVWXZ'; $d->nodeValue = $l[mt_rand(0,strlen($l)-1)]; },
            '//div[@class=\'letter-v\']' => function (DOMNode $d) use(&$editable) { $editable = false; $l = 'AEIOUY'; $d->nodeValue = $l[mt_rand(0,strlen($l)-1)]; },
            '//div[@class=\'rps\']'      => function (DOMNode $d) use(&$editable) { $editable = false; $d->nodeValue = $this->rand->pick([$this->translator->trans('Schere',[],'global'),$this->translator->trans('Stein',[],'global'),$this->translator->trans('Papier',[],'global')]); },
            '//div[@class=\'coin\']'     => function (DOMNode $d) use(&$editable) { $editable = false; $d->nodeValue = $this->rand->pick([$this->translator->trans('Kopf',[],'global'),$this->translator->trans('Zahl',[],'global')]); },
            '//div[@class=\'card\']'     => function (DOMNode $d) use(&$editable) { $editable = false;
                $s_color = $this->rand->pick([$this->translator->trans('Kreuz',[],'items'),$this->translator->trans('Pik',[],'items'),$this->translator->trans('Herz',[],'items'),$this->translator->trans('Karo',[],'items')]);
                $value = mt_rand(1,12);
                $s_value = $value < 9 ? ('' . ($value+2)) : [$this->translator->trans('Bube',[],'items'),$this->translator->trans('Dame',[],'items'),$this->translator->trans('König',[],'items'),$this->translator->trans('Ass',[],'items')][$value-9];
                $d->nodeValue = $this->translator->trans('{color} {value}', ['{color}' => $s_color, '{value}' => $s_value], 'global');
            },
            '//div[@class=\'citizen\']'   => function (DOMNode $d) use ($user,$town,&$cache,&$editable) {
                $editable = false;
                $profession = $d->attributes->getNamedItem('x-a') ? $d->attributes->getNamedItem('x-a')->nodeValue : null;
                if ($profession === 'any') $profession = null;
                $group      = is_numeric($d->attributes->getNamedItem('x-b')->nodeValue) ? (int)$d->attributes->getNamedItem('x-b')->nodeValue : null;

                if ($town === null) {
                    $d->nodeValue = '???';
                    return;
                }

                if ($group === null || $group <= 0) $group = null;
                elseif (!isset( $cache['citizen'][$group] )) $cache['citizen'][$group] = null;

                $valid = array_filter( $town->getCitizens()->getValues(), function(Citizen $c) use ($profession,$group,&$cache) {
                    if (!$c->getAlive() && ($profession !== 'dead')) return false;
                    if ( $c->getAlive() && ($profession === 'dead')) return false;

                    if ($profession !== null && $profession !== 'dead') {
                        if ($profession === 'hero') {
                            if (!$c->getProfession()->getHeroic()) return false;
                        } elseif ($c->getProfession()->getName() !== $profession) return false;
                    }

                    if ($group !== null) {
                        if ($cache['citizen'][$group] !== null && $c->getId() !== $cache['citizen'][$group]) return false;
                        if ($c->getId() === $cache['citizen'][$group]) return true;
                        if (in_array($c->getId(),$cache['citizen'])) return false;
                    }

                    return true;
                } );

                if (!$valid) {
                    $d->nodeValue = '???';
                    return;
                }

                /** @var Citizen $cc */
                $cc = $this->rand->pick($valid);
                if ($group !== null) $cache['citizen'][$group] = $cc->getId();
                $d->nodeValue = $cc->getUser()->getName();
            },

            // This MUST be the last element!
            '//div[@class=\'html\']'   => function (DOMNode $d) {
                while ($d->hasChildNodes())
                    $d->parentNode->insertBefore($d->firstChild,$d);
                $d->parentNode->removeChild($d);
            },
        ];

        foreach ($handlers as $query => $handler)
            foreach ( (new DOMXPath($dom))->query($query, $body->item(0)) as $node ) {
                /** @var DOMNode $node */
                $p = $node->parentNode;
                $in_html = false;

                while ($p) {
                    if ($attribs = $p->attributes) {
                        $class_attrib = $attribs->getNamedItem('class');
                        if (in_array( 'html', $class_attrib ? explode( ' ', $class_attrib->nodeValue ) : [] )) {
                            $in_html = true;
                            break;
                        }
                    }

                    $p = $p->parentNode;
                }

                if (!$in_html) $handler($node);
            }


        $tmp_str = "";
        foreach ($body->item(0)->childNodes as $child)
            $tmp_str .= $dom->saveHTML($child);

        $tmp_str = $this->filterLockedEmotes($user, $tmp_str);
        $text = $tmp_str;

        return true;
    }

    protected $emote_cache = null;
    public function get_emotes(bool $url_only = false): array {
        if ($this->emote_cache !== null) return $this->emote_cache;

        $this->emote_cache = [];
        $repo = $this->entity_manager->getRepository(Emotes::class);
        foreach($repo->findAll() as $value)
            /** @var $value Emotes */
            $this->emote_cache[$value->getTag()] = $url_only ? $value->getPath() : "<img alt='{$value->getTag()}' src='{$this->asset->getUrl( $value->getPath() )}'/>";
        return $this->emote_cache;
    }

    public function prepareEmotes(string $str): string {
        $emotes = $this->get_emotes();
        return str_replace( array_keys( $emotes ), array_values( $emotes ), $str );
    }

    protected function filterLockedEmotes(User $user, string $text): string {
        foreach ($this->getLockedEmoteTags($user) as $emote)
            $text = str_replace($emote, '', $text);
        return $text;
    }

    protected function getLockedEmoteTags(User $user): array {
        $emotes = $this->entity_manager->getRepository(Emotes::class)->getUnlockableEmotes();
        $unlocks = $this->entity_manager->getRepository(Award::class)->getAwardsByUser($user);
        $results = array();

        foreach($emotes as $emote) {
            /** @var $emote Emotes */
            $results[] = $emote->getTag();
        }

        $results = array_unique($results);

        foreach($unlocks as $entry) {
            /** @var $entry Award */
            if ($entry->getPrototype()->getAssociatedTag() && in_array($entry->getPrototype()->getAssociatedTag(), $results)) {
                unset($results[array_search($entry->getPrototype()->getAssociatedTag(), $results)]);
            }
        }

        return array_values($results);
    }
}