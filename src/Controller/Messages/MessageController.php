<?php

namespace App\Controller\Messages;

use App\Controller\CustomAbstractController;
use App\Entity\Award;
use App\Entity\Citizen;
use App\Entity\Emotes;
use App\Entity\Forum;
use App\Entity\ForumUsagePermissions;
use App\Entity\Post;
use App\Entity\Town;
use App\Entity\User;
use App\Service\CitizenHandler;
use App\Service\ErrorHelper;
use App\Service\InventoryHandler;
use App\Service\PermissionHandler;
use App\Service\RandomGenerator;
use App\Service\TimeKeeperService;
use App\Service\ConfMaster;
use App\Structures\ForumPermissionAccessor;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @IsGranted("ROLE_USER")
 * @method User getUser
 */
class MessageController extends CustomAbstractController
{
    const ErrorForumNotFound    = ErrorHelper::BaseForumErrors + 1;
    const ErrorPostTextLength   = ErrorHelper::BaseForumErrors + 2;
    const ErrorPostTitleLength  = ErrorHelper::BaseForumErrors + 3;
    const ErrorPMItemLimitHit   = ErrorHelper::BaseForumErrors + 4;
    const ErrorForumLimitHit    = ErrorHelper::BaseForumErrors + 5;

    protected RandomGenerator $rand;
    protected Packages $asset;
    protected PermissionHandler $perm;

    public function __construct(RandomGenerator $r, TranslatorInterface $t, Packages $a, EntityManagerInterface $em, InventoryHandler $ih, TimeKeeperService $tk, PermissionHandler $p, ConfMaster $conf, CitizenHandler $ch)
    {
        parent::__construct($conf, $em, $tk, $ch, $ih, $t);
        $this->asset = $a;
        $this->rand = $r;
        $this->perm = $p;
    }

    protected const HTML_ALLOWED = [
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
        'blockquote' => [],
        'pre' => [],
        'hr' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'p'  => [],
        'div' => [ 'class', 'x-a', 'x-b' ],
        'span' => [ 'class' ],
        'a' => [ 'href', 'title' ],
        'figure' => [ 'style' ],
    ];

    protected const HTML_ALLOWED_ADMIN = [
        'img' => [ 'alt', 'src', 'title']
    ];

    protected const HTML_ATTRIB_ALLOWED_ADMIN = [
        'div.class' => [
            'adminAnnounce',
        ]
    ];

    protected const HTML_ATTRIB_ALLOWED_ORACLE = [
        'div.class' => [
            'oracleAnnounce'
        ]
    ];

    protected const HTML_ATTRIB_ALLOWED_CROW = [
        'div.class' => [
            'modAnnounce', 'html'
        ]
    ];

    protected const HTML_ATTRIB_ALLOWED = [
        'div.class' => [
            'glory', 'spoiler', 'sideNote',
            'dice-4', 'dice-6', 'dice-8', 'dice-10', 'dice-12', 'dice-20', 'dice-100',
            'letter-a', 'letter-v', 'letter-c',
            'rps', 'coin', 'card',
            'citizen', 'rpText',
        ],
        'span.class' => [
            'quoteauthor','bad','rpauthor','inline-code',
        ]
    ];

    protected function getAllowedHTML(?Forum $forum = null): array {
        $r = self::HTML_ALLOWED;
        $a = self::HTML_ATTRIB_ALLOWED;

        $p = $forum ? $this->perm->getEffectivePermissions($this->getUser(), $forum) : (
            ($this->isGranted("ROLE_ADMIN")  * ForumUsagePermissions::PermissionFormattingAdmin) |
            ($this->isGranted("ROLE_CROW")   * ForumUsagePermissions::PermissionFormattingModerator) |
            ($this->isGranted("ROLE_ORACLE") * ForumUsagePermissions::PermissionFormattingOracle)
        );

        if ($this->perm->isPermitted($p, ForumUsagePermissions::PermissionFormattingAdmin)) {
            foreach (self::HTML_ALLOWED_ADMIN as $key => $value) {
                if(isset($r[$key])) {
                    $r[$key] = array_merge($r[$key], self::HTML_ALLOWED_ADMIN[$key]);
                } else {
                    $r[$key] = self::HTML_ALLOWED_ADMIN[$key];
                }
            }

            foreach (self::HTML_ATTRIB_ALLOWED_ADMIN as $key => $value) {
                if(isset($a[$key])) {
                    $a[$key] = array_merge($a[$key], self::HTML_ATTRIB_ALLOWED_ADMIN[$key]);
                } else {
                    $a[$key] = self::HTML_ATTRIB_ALLOWED_ADMIN[$key];
                }
            }
        }

        if ($this->perm->isPermitted($p, ForumUsagePermissions::PermissionFormattingModerator)) {
            foreach (self::HTML_ATTRIB_ALLOWED_CROW as $key => $value) {
                if(isset($a[$key])) {
                    $a[$key] = array_merge($a[$key], self::HTML_ATTRIB_ALLOWED_CROW[$key]);
                } else {
                    $a[$key] = self::HTML_ATTRIB_ALLOWED_CROW[$key];
                }
            }
        }

        if ($this->perm->isPermitted($p, ForumUsagePermissions::PermissionFormattingOracle)) {
            foreach (self::HTML_ATTRIB_ALLOWED_ORACLE as $key => $value) {
                if(isset($a[$key])) {
                    $a[$key] = array_merge($a[$key], self::HTML_ATTRIB_ALLOWED_ORACLE[$key]);
                } else {
                    $a[$key] = self::HTML_ATTRIB_ALLOWED_ORACLE[$key];
                }
            }
        }

        return ['nodes' => $r, 'attribs' => $a];
    }

    protected function htmlValidator( array $allowedNodes, ?DOMNode $node, int &$text_length, int $depth = 0 ): bool {
        if (!$node || $depth > 32) return false;

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

    protected function preparePost(User $user, ?Forum $forum, $post, int &$tx_len, ?Town $town = null, ?bool &$editable = null): bool {
        if (!$town && $forum && $forum->getTown())
            $town = $forum->getTown();

        $editable = true;

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML( '<?xml encoding="utf-8" ?>' . $post->getText() );
        $body = $dom->getElementsByTagName('body');
        if (!$body || $body->length > 1) return false;

        if (!$this->htmlValidator($this->getAllowedHTML($forum), $body->item(0),$tx_len))
            return false;

        $emotes = array_keys($this->get_emotes());

        $cache = [
            'citizen' => [],
        ];
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
                //foreach ($d->childNodes as $childNode)
                //    $d->parentNode->insertBefore( $childNode, $d );
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
        $post->setText( $tmp_str );
        if ($post instanceof Post && $post->getType() !== 'CROW' && $forum !== null && $forum->getTown()){

            $citizen = $user->getActiveCitizen();
            if ($citizen && $citizen->getTown() === $forum->getTown()) {

                if ($citizen->getZone() && ($citizen->getZone()->getX() !== 0 || $citizen->getZone()->getY() !== 0))  {
                    if($citizen->getTown()->getChaos()){
                        $note = $this->translator->trans('Draußen', [], 'game');
                    } else {
                        $note = "[{$citizen->getZone()->getX()}, {$citizen->getZone()->getY()}]";
                    }
                }
                else {
                    $note = $this->translator->trans('in der Stadt oder am Stadttor', [], 'game');
                }

                $post->setNote("<img alt='' src='{$this->asset->getUrl("build/images/professions/{$citizen->getProfession()->getIcon()}.gif")}' /> <img alt='' src='{$this->asset->getUrl('build/images/icons/item_map.gif')}' /> <span>$note</span>");

            }

        }

        return true;
    }

    protected $emote_cache = null;
    protected function get_emotes(bool $url_only = false): array {
        if ($this->emote_cache !== null) return $this->emote_cache;

        $this->emote_cache = [];
        $repo = $this->entity_manager->getRepository(Emotes::class);
        foreach($repo->findAll() as $value)
            /** @var $value Emotes */
            $this->emote_cache[$value->getTag()] = $url_only ? $value->getPath() : "<img alt='{$value->getTag()}' src='{$this->asset->getUrl( $value->getPath() )}'/>";
        return $this->emote_cache;
    }

    protected function getEmotesByUser(User $user, bool $url_only = false): array {
        $repo = $this->entity_manager->getRepository(Emotes::class);
        $emotes = $repo->getDefaultEmotes();
        $awards = $this->entity_manager->getRepository(Award::class)->getAwardsByUser($user);
        $results = array();

        foreach($awards as $entry) {
            /** @var $entry Award */
            $emote = $repo->findByTag($entry->getPrototype()->getAssociatedTag());
            if(!in_array($emote, $emotes)) {
                $emotes[] = $emote;
            }
        }

        foreach($emotes as $entry) {
            /** @var $entry Emotes */
            $results[$entry->getTag()] = $url_only ? $entry->getPath() : "<img alt='{$entry->getTag()}' src='{$this->asset->getUrl( $entry->getPath() )}'/>";
        }
        return $results;
    }

    protected function prepareEmotes(string $str): string {
        $emotes = $this->get_emotes();
        return str_replace( array_keys( $emotes ), array_values( $emotes ), $str );
    }

    protected function filterLockedEmotes(User $user, string $text): string {
        $lockedEmotes = $this->getLockedEmoteTags($user);
        foreach($lockedEmotes as $emote) {
            $text = str_replace($emote, '', $text);
        }
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

        if($unlocks != null) {
            foreach($unlocks as $entry) {
                /** @var $entry Award */
                if(in_array($entry->getPrototype()->getAssociatedTag(), $results)) {
                    unset($results[array_search($entry, $results)]);
                }
            }
        }

        return array_values($results);
    }

    protected function getPermissionObject($forumOrPermission = null): ForumPermissionAccessor {
        $p = 0;
        if (is_int($forumOrPermission)) $p = $forumOrPermission;
        elseif (is_a($forumOrPermission, Forum::class)) $p = $this->perm->getEffectivePermissions($this->getUser(), $forumOrPermission);

        return new ForumPermissionAccessor($p, $this->perm);
    }
}
