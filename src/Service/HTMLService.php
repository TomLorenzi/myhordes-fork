<?php

namespace App\Service;

use App\Entity\Award;
use App\Entity\Citizen;
use App\Entity\Emotes;
use App\Entity\ForumUsagePermissions;
use App\Entity\Post;
use App\Entity\Town;
use App\Entity\User;
use App\Structures\HTMLParserInsight;
use App\Structures\MyHordesConf;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class HTMLService {

    private EntityManagerInterface $entity_manager;
    private PermissionHandler $perm;
    private TranslatorInterface $translator;
    private RandomGenerator $rand;
    private Packages $asset;
    private UserHandler $userHandler;
    private UrlGeneratorInterface $router;
    private ConfMaster $conf;

    const ModulationNone    = 0;
    const ModulationDrunk   = 1 << 1;
    const ModulationTerror  = 1 << 2;
    const ModulationHead    = 1 << 3;

    public function __construct(EntityManagerInterface $em, PermissionHandler $perm, TranslatorInterface $trans,
                                RandomGenerator $rand, Packages $a, UserHandler $uh, UrlGeneratorInterface $router,
                                ConfMaster $conf)
    {
        $this->entity_manager = $em;
        $this->perm = $perm;
        $this->translator = $trans;
        $this->rand = $rand;
        $this->asset = $a;
        $this->userHandler = $uh;
        $this->router = $router;
        $this->conf = $conf;
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
            'core_rp' => [
                'div' => [ 'class', 'x-a', 'x-b' ],
            ],
            'core_user' => [
                'div' => [ 'class', 'x-a', 'x-b' ],
            ],
            'extended' => [
                'blockquote' => [],
                'pre' => [],
                'hr' => [],
                'ul' => [],
                'ol' => [],
                'li' => [],
                'div' => [ 'class', 'x-a', 'x-b' ],
                'span' => [ 'class', 'x-a', 'x-b' ],
                'a' => [ 'href', 'title' ],
                'figure' => [ 'style' ],
            ],
            'oracle' => [
                'ul' => ['class'],
                'li' => ['class'],
                'img' => [ 'alt', 'src', 'title']
            ],
            'crow' => [],
            'admin' => [
                'img' => [ 'alt', 'src', 'title']
            ]
        ],
        'attribs' => [
            'core' => [],
            'core_rp' => [
                'div.class' => [
                    'dice-4', 'dice-6', 'dice-8', 'dice-10', 'dice-12', 'dice-20', 'dice-100',
                    'letter-a', 'letter-v', 'letter-c',
                    'rps', 'coin', 'card'
                ],
            ],
            'core_user' => [
                'div.class' => [
                    'cref'
                ],
            ],
            'core_rp_coa' => [ 'div.class' => ['coalition'] ],
            'glory' => [ 'div.class' => [ 'glory' ] ],
            'extended' => [
                'div.class' => [
                    'clear',
                    'spoiler', 'sideNote',
                    'dice-4', 'dice-6', 'dice-8', 'dice-10', 'dice-12', 'dice-20', 'dice-100',
                    'letter-a', 'letter-v', 'letter-c',
                    'rps', 'coin', 'card',
                    'citizen', 'rpText', 'cref',
                    'collapsor', 'collapsed'
                ],
                'span.class' => [
                    'quoteauthor','bad','big','rpauthor','inline-code','.'
                ]
            ],
            'oracle' => [
                'ul.class' => [
                    'poll'
                ],
                'li.class' => [
                    'desc','q'
                ],
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

    protected const HTML_IMMUTABLE = [
        'img.*' => true,
        'ul.class' => ['poll'],
        '*.class' => [
            'clear', 'dice-4', 'dice-6', 'dice-8', 'dice-10', 'dice-12', 'dice-20', 'dice-100',
            'letter-a', 'letter-v', 'letter-c', 'rps', 'coin', 'card', 'citizen', 'html',
            'oracleAnnounce', 'modAnnounce', 'adminAnnounce', 'cref'
        ]
    ];

    protected const HTML_PLAIN_CONTENT = ['span.inline-code','code'];
    protected const HTML_PLAIN_CONTENT_WITH_EMOTES = ['a','span.rpauthor'];

    protected function getAllowedHTML(User $user, int $permissions, bool $extended = true, array $all_ext = []): array {
        $mods_enabled = ['core'];
        if ($extended) $mods_enabled[] = 'extended';
        $mods_enabled = array_merge($mods_enabled, $all_ext);

        if ($this->perm->isPermitted($permissions, ForumUsagePermissions::PermissionFormattingOracle))
            $mods_enabled[] = 'oracle';
        if ($this->perm->isPermitted($permissions, ForumUsagePermissions::PermissionFormattingModerator))
            $mods_enabled[] = 'crow';
        if ($this->perm->isPermitted($permissions, ForumUsagePermissions::PermissionFormattingAdmin))
            $mods_enabled[] = 'admin';

        if (in_array('extended', $mods_enabled) && $this->userHandler->checkFeatureUnlock($user,'f_glory',false))
            $mods_enabled[] = 'glory';

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

    protected function htmlValidator( array $allowedNodes, ?DOMNode $node, ?int &$text_length = null, int $depth = 0, bool $child_nodes_forbidden = false, bool $emotes_explicitly_allowed = false ): bool {
        if (!$node || $depth > 32) return false;
        if ($text_length === null) $text_length = 0;

        if ($node->nodeType === XML_ELEMENT_NODE) {

            $truncate_node = $child_nodes_forbidden;

            // Element not allowed.
            if (!in_array($node->nodeName, array_keys($allowedNodes['nodes'])) && !($depth === 0 && $node->nodeName === 'body'))
                $truncate_node = true;

            // Attributes not allowed - we only need to remove attribs if the node is not truncated
            $remove_attribs = [];
            if (!$truncate_node)
                for ($i = 0; $i < $node->attributes->length; $i++) {
                    if (!in_array($node->attributes->item($i)->nodeName, $allowedNodes['nodes'][$node->nodeName]))
                        $remove_attribs[] = $node->attributes->item($i)->nodeName;
                    elseif (isset($allowedNodes['attribs']["{$node->nodeName}.{$node->attributes->item($i)->nodeName}"])) {
                        // Attribute values not allowed
                        $allowed_entries = $allowedNodes['attribs']["{$node->nodeName}.{$node->attributes->item($i)->nodeName}"];
                        $node->attributes->item($i)->nodeValue = implode( ' ', array_filter( explode(' ', $node->attributes->item($i)->nodeValue), function (string $s) use ($allowed_entries) {
                            return in_array( $s, $allowed_entries );
                        }));

                        if (empty($node->attributes->item($i)->nodeValue))
                            $remove_attribs[] = $node->attributes->item($i)->nodeName;
                    }
                }

            foreach ($remove_attribs as $attrib)
                $node->removeAttribute($attrib);

            //DIV and SPAN are not allowed without any attributes
            if (!$truncate_node && in_array($node->nodeName, ['div','span']) && $node->attributes->length === 0)
                $truncate_node = true;

            $children = [];
            foreach ( $node->childNodes as $child )
                $children[] = $child;

            $plain = $child_nodes_forbidden;
            $emotes_allowed = $emotes_explicitly_allowed;
            if (!$plain) {
                if (in_array(strtolower($node->nodeName), static::HTML_PLAIN_CONTENT)) $emotes_explicitly_allowed = !($plain = true);
                else foreach (explode(' ', strtolower($node->getAttribute('class')) ?? '') as $class)
                    if (in_array("{$node->nodeName}.{$class}", static::HTML_PLAIN_CONTENT)) $emotes_explicitly_allowed = !($plain = true);


            }

            if ($plain && !$emotes_explicitly_allowed) {
                if (in_array(strtolower($node->nodeName), static::HTML_PLAIN_CONTENT_WITH_EMOTES)) $emotes_allowed = true;
                else foreach (explode(' ', strtolower($node->getAttribute('class')) ?? '') as $class)
                    if (in_array("{$node->nodeName}.{$class}", static::HTML_PLAIN_CONTENT_WITH_EMOTES)) $emotes_allowed = true;
            }

            foreach ( $children as $child )
                if (!$this->htmlValidator( $allowedNodes, $child, $text_length, $depth+1, $plain, $emotes_allowed ))
                    return false;

            if ($truncate_node && !$node->parentNode) return false;
            elseif ($truncate_node) {
                $children = [];
                foreach ( $node->childNodes as $child )
                    $children[] = $child;
                foreach ( $children as $child )
                    $node->parentNode->insertBefore( $child, $node );
                $node->parentNode->removeChild($node);
            }

            return true;

        } elseif ($node->nodeType === XML_TEXT_NODE) {
            $text_length += mb_strlen(trim($node->textContent));
            if ($child_nodes_forbidden && !$emotes_explicitly_allowed) $node->textContent = str_replace(':', ':​', $node->textContent);
            return true;
        }
        else return false;
    }

    /**
     * @param User $user
     * @param int $permissions
     * @param bool|array $extended
     * @param string $text
     * @param Town|null $town
     * @param HTMLParserInsight|null $insight
     * @return bool
     */
    public function htmlPrepare(User $user, int $permissions, bool|array $extended, string &$text, ?Town $town = null, ?HTMLParserInsight &$insight = null): bool {

        $insight = new HTMLParserInsight();
        $insight->editable = true;

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $text = str_replace(['@​::','@%E2%80%8B::'],'@::',$text);
        $dom->loadHTML( "<html lang=''><head><title></title><meta charset='UTF-8' /></head><body>$text</body></html>", LIBXML_COMPACT | LIBXML_NONET | LIBXML_HTML_NOIMPLIED);
        $body = $dom->getElementsByTagName('body');
        if (!$body || $body->length > 1) return false;

        if (!$this->htmlValidator($this->getAllowedHTML($user, $permissions,is_bool($extended) ? $extended : false, is_array($extended) ? $extended : []), $body->item(0),$insight->text_length))
            return false;

        $emotes = array_keys($this->get_emotes(false, $user));

        $cache = [ 'citizen' => [], 'coalition' => [] ];

        $sys_urls = $this->conf->getGlobalConf()->get(MyHordesConf::CONF_URLS, []);
        $replace_urls = [];
        foreach ($sys_urls as $url) {
            $replace_urls[] = 'http://' . $url;
            $replace_urls[] = 'https://' . $url;
        }
        $poll_cache = [];

        $handlers = [
            // This invalidates emote tags within code blocks to prevent them from being replaced when rendering the
            // post
            '//pre|//span[@class=\'inline-code\']' =>
                function (DOMNode $d) use(&$emotes) {
                    foreach ($emotes as $emote)
                        $d->textContent = str_replace($emote, str_replace(':', ':​', $emote), $d->textContent);
                },

            // Replace URLs
            '//a[@href]'   => function (DOMNode $d) use ($replace_urls) {
                $url = $d->attributes->getNamedItem('href')->nodeValue;
                $replace_nw = $d->nodeValue === $url;

                $new_url = str_replace( $replace_urls, '@​::dom:0', $url );
                if ($url !== $new_url) {
                    $d->attributes->getNamedItem('href')->nodeValue = $new_url;
                    if ($replace_nw) $d->nodeValue = $new_url;
                }
            },
            '//img[@src]'   => function (DOMNode $d) use ($replace_urls) {
                $url = $d->attributes->getNamedItem('src')->nodeValue;
                $new_url = str_replace( $replace_urls, '@​::dom:0', $url );
                if ($url !== $new_url) $d->attributes->getNamedItem('src')->nodeValue = $new_url;
            },

            '//div[@class=\'dice-4\']'   => function (DOMNode $d) use(&$insight) { $insight->editable = false; $d->nodeValue = mt_rand(1,4); },
            '//div[@class=\'dice-6\']'   => function (DOMNode $d) use(&$insight) { $insight->editable = false; $d->nodeValue = mt_rand(1,6); },
            '//div[@class=\'dice-8\']'   => function (DOMNode $d) use(&$insight) { $insight->editable = false; $d->nodeValue = mt_rand(1,8); },
            '//div[@class=\'dice-10\']'  => function (DOMNode $d) use(&$insight) { $insight->editable = false; $d->nodeValue = mt_rand(1,10); },
            '//div[@class=\'dice-12\']'  => function (DOMNode $d) use(&$insight) { $insight->editable = false; $d->nodeValue = mt_rand(1,12); },
            '//div[@class=\'dice-20\']'  => function (DOMNode $d) use(&$insight) { $insight->editable = false; $d->nodeValue = mt_rand(1,20); },
            '//div[@class=\'dice-100\']' => function (DOMNode $d) use(&$insight) { $insight->editable = false; $d->nodeValue = mt_rand(1,100); },
            '//div[@class=\'letter-a\']' => function (DOMNode $d) use(&$insight) { $insight->editable = false; $l = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'; $d->nodeValue = $l[mt_rand(0,strlen($l)-1)]; },
            '//div[@class=\'letter-c\']' => function (DOMNode $d) use(&$insight) { $insight->editable = false; $l = 'BCDFGHJKLMNPQRSTVWXZ'; $d->nodeValue = $l[mt_rand(0,strlen($l)-1)]; },
            '//div[@class=\'letter-v\']' => function (DOMNode $d) use(&$insight) { $insight->editable = false; $l = 'AEIOUY'; $d->nodeValue = $l[mt_rand(0,strlen($l)-1)]; },
            '//div[@class=\'rps\']'      => function (DOMNode $d) use(&$insight) { $insight->editable = false; $d->nodeValue = $this->rand->pick([$this->translator->trans('Schere',[],'global'),$this->translator->trans('Stein',[],'global'),$this->translator->trans('Papier',[],'global')]); },
            '//div[@class=\'coin\']'     => function (DOMNode $d) use(&$insight) { $insight->editable = false; $d->nodeValue = $this->rand->pick([$this->translator->trans('Kopf',[],'global'),$this->translator->trans('Zahl',[],'global')]); },
            '//div[@class=\'card\']'     => function (DOMNode $d) use(&$insight) { $insight->editable = false;
                $s_color = $this->rand->pick([$this->translator->trans('Kreuz',[],'items'),$this->translator->trans('Pik',[],'items'),$this->translator->trans('Herz',[],'items'),$this->translator->trans('Karo',[],'items')]);
                $value = mt_rand(1,12);
                $s_value = $value < 9 ? ('' . ($value+2)) : [$this->translator->trans('Bube',[],'items'),$this->translator->trans('Dame',[],'items'),$this->translator->trans('König',[],'items'),$this->translator->trans('Ass',[],'items')][$value-9];
                $d->nodeValue = $this->translator->trans('{color} {value}', ['{color}' => $s_color, '{value}' => $s_value], 'global');
            },
            '//div[@class=\'citizen\']'   => function (DOMNode $d) use ($town,&$cache,&$insight) {
                $insight->editable = false;
                $profession = $d->attributes->getNamedItem('x-a')?->nodeValue ?? null;
                if ($profession === 'any') $profession = null;
                $group = is_numeric($d->attributes->getNamedItem('x-b')?->nodeValue) ? (int)$d->attributes->getNamedItem('x-b')?->nodeValue : null;

                if ($town === null) {
                    $d->nodeValue = '???';
                    return;
                }

                if ($group === null || $group <= 0) $group = null;
                elseif (!isset( $cache['citizen'][$group] )) $cache['citizen'][$group] = null;

                $valid = array_filter( $town->getCitizens()->getValues(), function(Citizen $c) use ($profession,$group,&$cache,$user) {
                    if (!$c->getAlive() && ($profession !== 'dead')) return false;
                    if ( $c->getAlive() && ($profession === 'dead')) return false;

                    if ($profession !== null && $profession !== 'dead') {
                        if ($profession === 'hero') {
                            if (!$c->getProfession()->getHeroic()) return false;
                        } elseif ($profession === 'shunned') {
                            if (!$c->getBanished()) return false;
                        }
                        elseif ($profession === 'shaman') {
                            if ($c->getProfession()->getName() !== $profession && !$c->hasRole('shaman')) return false; }
                        elseif ($profession === 'zone') {
                            if (!$c->getZone() || $user->getActiveCitizen()->getZone() !== $c->getZone()) return false;
                        }
                        elseif ($c->getProfession()->getName() !== $profession) return false;
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
                $d->nodeValue = $cc->getName();
            },
            '//div[@class=\'coalition\']'   => function (DOMNode $d) use ($user,$town,&$cache,&$insight) {
                $insight->editable = false;

                $group = is_numeric($d->attributes->getNamedItem('x-b')?->nodeValue) ? (int)$d->attributes->getNamedItem('x-b')?->nodeValue : null;
                if ($group === null || $group <= 0) $group = null;

                $u = null;
                if ($group !== null && !empty($cache['coalition'][$group])) $u = $cache['coalition'][$group];
                else {
                    $coa_members = [...$this->userHandler->getAllOtherCoalitionMembers($user),$user];
                    $coa_members = array_filter($coa_members, fn(User $u) => !in_array( $u, $cache['coalition'] ));

                    if (!$coa_members) {
                        $d->nodeValue = '???';
                        return;
                    }

                    /** @var User $u */
                    $u = $this->rand->pick($coa_members);
                    if ($group !== null) $cache['coalition'][$group] = $u;

                }

                $d->nodeValue = $u->getName();
            },
            // A citizen ref node
            '//div[@class=\'cref\']|//span[@class=\'quoteauthor\']' => function (DOMElement $user_ref) use ($user, &$insight) {
                $id = $user_ref->attributes->getNamedItem('x-a') ? $user_ref->attributes->getNamedItem('x-a')->nodeValue : null;
                $user_ref->removeAttribute('x-a');

                $within_quote = false;
                $current_dom = $user_ref;
                while (!$within_quote && $current_dom) {
                    $within_quote = $current_dom->nodeName === 'blockquote';
                    $current_dom = $current_dom->parentNode;
                }

                $target_user = null;
                if ($id === 'auto') {
                    $name = $user_ref->textContent;
                    if ($name === 'me')
                        // This is safe, because "me" is not a valid username
                        $target_user = $user;
                    else
                        $target_user = !empty(trim($name)) ? $this->entity_manager->getRepository(User::class)->findOneByNameOrDisplayName($name,true,true) : null;
                } elseif (is_numeric($id))
                    $target_user = $this->entity_manager->getRepository(User::class)->find($id);

                if ($target_user === null) {
                    $user_ref->setAttribute('class', $user_ref->getAttribute('class') . ' raw');
                } else {
                    $user_ref->textContent = "@​::un:{$target_user->getId()}";
                    $user_ref->setAttribute('x-user-id', $target_user->getId());
                    $user_ref->setAttribute('class', $user_ref->getAttribute('class') . ' username');
                    if (!$within_quote && !in_array($target_user, $insight->taggedUsers)) $insight->taggedUsers[] = $target_user;
                }
            },

            // A poll node
            '//ul[@class=\'poll\']'   => function (DOMElement $poll) use(&$insight, &$poll_cache) {
                $insight->editable = false;
                $remove = [];

                $answer_count = 0;

                $first = true;
                foreach ($poll->childNodes as $child) {
                    /** @var DOMNode|DOMElement $child */
                    if ($child->nodeType !== XML_ELEMENT_NODE || $child->nodeName !== 'li' || empty(trim($child->textContent))) {
                        $remove[] = $child;
                        continue;
                    }

                    if ($child->getAttribute('class') === 'q' && !$first ) {
                        $remove[] = $child;
                        continue;
                    }

                    $first = false;
                    if (!in_array($child->getAttribute('class'), ['q','desc','']))
                        $remove[] = $child;
                    elseif ($child->getAttribute('class') === '') $answer_count++;
                }

                foreach ($remove as $remove_child)
                    $poll->removeChild($remove_child);

                if ($answer_count === 0) {
                    $poll->parentNode->removeChild($poll);
                    return;
                }

                $gen = function () use (&$poll_cache): string {
                    do $s = bin2hex(random_bytes(64));
                    while (in_array($s,$poll_cache));
                    return $poll_cache[] = $s;
                };

                $poll_parent = $gen();
                $insight->polls[$poll_parent] = [];
                $poll->setAttribute( 'x-poll-id', $poll_parent );

                foreach ($poll->childNodes as $answer) {
                    /** @var DOMElement $answer */
                    if ($answer->getAttribute('class') !== '') continue;
                    $answer->setAttribute('x-poll-id', $poll_parent);
                    $answer->setAttribute('x-answer-id', $insight->polls[$poll_parent][] = $gen());
                }
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

                if (!$in_html && $node) $handler($node);
            }


        $tmp_str = "";
        foreach ($body->item(0)->childNodes as $child)
            $tmp_str .= $dom->saveHTML($child);

        $tmp_str = $this->filterLockedEmotes($user, $tmp_str);
        $text = $tmp_str;

        return true;
    }

    protected const MODULATION_LIST = [
        self::ModulationDrunk => [
            'de' => [
                'words'   => ['... der...', 'und äähh..', ' .. und ich...', '.. nein...', '... noch schlimmer..', '.. hey warte..', ' äh... dort ', '.. scheiss drauf...', '... glaub dass...', '.d..di...dings...', '... die...', '..häh?..', '..weisscho...', '..oder...', '.. ein ähh...', '.. nicht?.. oder?', '... vielleicht auch nicht...', '.. krass halt.. weisst schon...', '.. ich...ich...ich...', '.. das meine ich...', '.. ähh... was wollte ich gerade sagen?...', '... weisst du, weil...', ' aber sicher...', ' so voll...', '.. achhh mist...', '.. aber... ääh..', 'HICKS...', '... sagte doch schon ...', ' arghh.', '... shiggi shiggi..', '... dieses ding da ..', '.. \'s\'zeug halt ...', '... dieses Teil..', '.. so wie...', '..rülps...', '.. kacke..', 'äh...', 'hmmmm...', '..voll der Saustall...', '... weil..'],
                'inserts' => ['... schon...', 'hmmmm..', ' ...', ' .. und ich...', ' nein...', '... \'sgeht mich das an?..', '.. nein warte..', ' hmmm... na dort! ', '.. na und!...', ' nicht doch!', '..Hi hi hi! ...', '... glaub, dass...', '.gghhh...', '..ausm Weg...', '..He he...', '..häh?', '..kann sein...', '.. sollen das?...', ' bitte?..', '... achso...', '.. weisst schon wie ich mein\'...', '.. alles klar...', ' so isses...', '.. äähh... was hab ich grad gesagt...', '... weisst du, weil...', '...siehste!', ' so wie in...', ' so einen auf...', ' und paaam!...', ' aber... ich..', 'HICKS...', '... FRESSE...', ' mein Bier.', '... dingsbums..', ' pfff...', '...glaub ich nicht', '...ach was', 'na sowas!...', '..schaust\'n so?!', '..dumm anmachen!?', '... hau dir ein auf\'s ...'],
            ],
            'en' => [
                'words'   => ['... the...', 'an\' emmm..', ' .. so I...', '.. no...', '... well..', '.. no wait..', ' and eh... there were Jagerbombs...', '.. meh...', '... i believe...', '.t..T..Thing is...', '... the...', '..huh?..', '..you see, he was standing ON the giraffe...', '..or ...', '.. a ahh...', '.. no?.. eh?', '... or not...', '.. finally.. do you see...', '.. thingummy...', '.. you have pretty eyes...', '.. erm yeah... so yeah...', '... now lookee heere...', ' beer o\'clock...', ' siesta!!! ...', '.. disgusting...', '.. but... euh..', 'HIC...', '... but seriously...', ' arf.', '... doofer..', '... banjo..', '.. that thing there...', '... whatsitsname..', '.. whatsherface...', '.. burp...', '.. dump..', 'eh...', 'huh...', '..stranger and strangerer...', '... b\'cause y\'know..', '...that\'s numberwang!...'],
                'inserts' => ['... yep...', 'and euuh..', ' ...2 Scotsmen, 2 Englishmen and a Canadian walk into a bar', ' .. and I...will always love yooooouuuuuu...', ' no...', '... mind..', '.. no, wait..', ' and erm... yeah', '.. dammit...', ' no?.. yeah?', '..Hehehe! ...', '... I believe the plural is peniii...', '.gghhh...', '..well well...', '..Heehee...', '..have you met Epoq?', '..now then...', '.. What the...', ' nah?.. huh?', '... or what...', '.. in the end.. you see...', '.. yeah dude...', ' there it is... what?', '.. and eh... what was I saying...', '... so you see...', '...I didn\'t fall, there\'s just more gravity over here...', ' like...', ' y\'know...', ' then...beep, cleep, chimney...', ' but... erm...', 'HIC...', '... what already...', ' arf.', '... thingy..', ' you and me. outside...', '...', '... and that\'s why it won\'t fit...', '...', '...best actor in Hollywood? Easy. Steven Seagal.', '..', '... but don\'t you remember I said I was allergic to rabbits ...'],
            ],
            'es' => [
                'words'   => ['... seee...', ' y uuuh...', '...', '... eh yo...', ' no...', '... pss...', '... eshpera..', ' eh...oh...la', '...y ent-tonces...', ' oye... ¿quién eres?', '...¡ji!..¡buaaaa!...', '...yo pienso que...', '...dar, el, el extra...', '...yo mismo...soy', '...jejee...', '...¿qui-quién?', ' arff...', '... on-toy...', '... esa cosssa...', '... esa noo...', '... cosssa..', '... algo como...', '... diablos...', '... m\'ldita sea..', 'euh...', 'huh...', '... pues essso...', '... porque...'],
                'inserts' => ['...seee...', ' y uuuh...', ' ...', ' ... eh yo...', ' no...', '... piss...', '... eshpera..', ' eh...oh...la', '...y ent-tonces...', ' oye... ¿quién eres?', '... ¡ji!..¡buaaaa!...', '... yo pienso que...', '... dar, el, el extra...', '... yo mismo...soy', '... jejee...', '... HIP...', '... ¿qui-quién?', '... ánnndate...', '... shomos lo k\' shomosh...', '... mo-momenito...', '... passsame la botell...', '... mira, te voy a...', '... que viva México...', '... que me llevaaa...', '... psss... pssss...', '... déjame, yo puedo...', '... un traguito más...', ' y plafff...', ' ¡eeeeeepa!...', ' y puffff...', ' pero... mmmmh...', '... HIP...', '... me caigo...', ' uuuuughhh...', '... shhhh...', ' pfff...', '...qué bonitos ojos tienes...', '...¡cállate!...', '... HIP...', '... un te-tequilita por favor...', '... ¡qué me estás mirando!...', '... ¡me hago!...', '... ¡malditos todos!...'],
            ],
            'fr' => [
                'words'   => ['... le...', 'et euuh..', ' .. et je...', '.. non...', '... pis..', '.. non attend..', ' et euh... là', '.. pis alors...', '... j\'crois...', '.t..T..Truc...', '... la...', '..hein ?..', '..tu vois...', '..ou bien...', '.. un euh...', '.. non ?.. hein ?', '... ou pas...', '.. enfin.. tu vois quoi...', '.. bidule...', '.. voilà quoi...', '.. et euh... j\'disais quoi...', '... parceque tu vois...', ' genre...', ' style...', '.. raaah bordel...', '.. mais... euh..', 'HIPS...', '... comment déjà...', ' arf.', '... machin..', '... le bazar là..', '.. le truc là...', '... truc..', '.. genre...', '.. rôte...', '.. bordel..', 'euh...', 'huh...', '..le bazar quoi...', '... parceque..'],
                'inserts' => ['... ouais...', 'et euuh..', ' ...', ' .. et je...', ' non...', '... pis..', '.. non attend..', ' et euh... là', '.. pis alors...', ' non ?.. Oui ?', '..Hi hi hi ! ...', '... j\'crois...', '.gghhh...', '..hin hin...', '..Hé hé...', '..hein ?', '..ou bien...', '.. Qu\'est-ce que...', ' non ?.. Oui ?', '... ou pas quoi...', '.. enfin.. tu vois quoi...', '.. ouais les gars...', ' voilà quoi...', '.. et euh... j\'disais quoi...', '... parceque tu vois...', '...', ' genre...', ' style...', ' et paf...', ' mais... euh..', '* HIPS *...', '... comment déjà...', ' arf.', '... truc..', ' pfff...', '...', '...', '...', '..', '..', '... ...'],
            ],
        ],
        self::ModulationTerror => [
            'de' => [
                'words'   => ['Zombie', 'Leiche', 'Schmerzen', 'abartig', 'Schimmel', 'Infektion', 'vergammelt', 'Geist', 'schrecklich', 'grässlich', 'Monster', 'Spargel', 'Birne', 'Banane', 'Apfel', 'Gänseblümchen', 'Angst', 'Flugzeug', 'Stress', 'lustig', 'Blume', 'alles Roger', 'Rakete', 'Palim-palim', 'Balken', 'Erdbeere', 'Ente', 'Puppe', 'Kleidchen', 'Pille-Palle', 'Wasserflasche', 'Karotte', 'Tannenbaum', 'Weichnachtsmann', 'Schaufel', 'Snuff', 'Schlumpf', 'Wahnsinn', 'Biene', 'pervers', 'lalala', 'essen', 'Hunger', 'zermalmen'],
                'inserts' => ['.. HA HA HA!...', '..werd euch alle...', '..die Stimmen...', '.gghhh...', 'NEEEEIN!...', 'murmel...', '..he he...', 'HAUT AB!..', 'die Spinnen...', '..sind überall...', '..Lass mich!..', '.. Ah... Ah ah..', '.. ist da jemand?..', '..rrRRR... AAAAaah!', '.r.tö-ten...', '...lach..', '...', '..Großvater Neeiii.. NEEIIIN!!...', ' nicht schlecht Herr Specht...', '..häh?', '..kalt..', '..oder vielleicht...', '.. Was soll...', ' nein?.. doch nicht?', '. Gar nich..', '.. Lasst mich!', '..Hi hi hi! ...', 'grummel...', '... Mein Auto! ....', '.. lauf mein Kleiner ...', '... im Sommer ist es heiß ...', '.. von den blauen Beeergen kommen wiaaa.. Häh?..', 'ich liebe dich - schon immer...', 'Robert, du bist mein bester Kumpel', 'und das ist rot, rot, ROT!!Ah!...'],
            ],
            'en' => [
                'words'   => ['zombie', 'cadaver', 'suffering', 'atrocity', 'decay', 'infection', 'putrid', 'phantom', 'horrible', 'filthy', 'monster', 'tomato', 'poisonous monkeys', 'banana', 'berk', 'exploding trousers', 'fear', 'plane', 'pip, tiddly bang bang', 'funny', 'flower', 'ultra-banana', 'table', 'fingledom', 'yingiebert', 'dandleban', 'duck', 'fistlebars', 'teepee', 'potato', 'gourd', 'giant carrot', 'tree', 'Father Christmas', 'shovel', 'how rude', 'smurf', 'crazy', 'bzzz, bzzzz, listen', 'jabberwocky', 'tra-la-lah', 'munch', 'smugly', 'Ernie', 'Zinglebert Wangledack'],
                'inserts' => ['.. AH AH AH! ...', '..why i oughtta...', '..the.. voices...', '.gghhh...', 'NOOOoo! ...', 'mumble...', '..hehe...', 'GO AWAY! ..', 'the spiders...', '..they are everywhere...', '..Leave me! ..', '.. Ah... Ah ah..', '.. who\'s there?..', '..rrRRR... RAAAAaah!', '.redrum...', '...laugh..', '...', '..my giddy aunt.. Noo.. NOOOO!!...', ' and then, and then, and then ...', '..as the bishop said to the nun, what?', '..is underestimating the sneakiness..', '..ouch! pointy...', '.. What the ...', ' no?.. Yes?', '. that\'s what she said..', '.. No touchy!', '..Hehehehehe! ...', 'groan...', '... My sainted trousers! ....', '.. oooh! look! shiny!!! ...', '... but seriously, onions? wheeeee! ...', '.. Meuuuhh.. What?..', 'alright stop, collaborate and listen...', 'so much more room for activities!', 'By the beard of Zeus! ! Ah! ...'],
            ],
            'es' => [
                'words'   => ['zombi', 'cadáver', 'sufrimiento', 'atroz', 'vecino', 'infección', 'pútrido', 'fantasma', 'horrible', 'inmundo', 'monstruo', 'tomate', 'pera', 'banana', 'manzana', 'tulipán', 'miedo', 'avión', 'follón', 'chistoso', 'flor', 'patito', 'elefante', 'cacharro', 'feo', 'grano', 'pato', 'pelota', 'pollo', 'papa', 'cantimplora', 'zanahoria', 'árbol', 'Papa Noel', 'abuela', 'pico', 'papel', 'locura', 'abeja', 'medalla', 'lalalá', 'comer', 'saltar', 'aplastar', 'cosita', 'chocolate', 'loca', 'pechito', 'ovni', 'arañita', 'calzones', 'pirulo'],
                'inserts' => ['... ¡JA JA JA!...', '... voy a... ¡a todos!...', '... las... voces...', '... mi cabezaaa...', ' ¡NoOoOo!...', ' ¡un pájaro!...', '... ji ji...', '¡QUE SE VAYAN!..', 'veo arañas...', '... en todas partes...', '... ¡Dejadme!...', '... Ah... Ah ah...', '... ¿quién está ahi?...', '... rrRRR... ¡RrAAAAaah!....', '... ma...tar...', '... no fui...', '... ¡fuera todos de aquí!', '... a Belén pastorcitos.. ¡¡NOOOO!!...', ' estas son las mañaanii...', '... ¿ah?...', '... frío..', '... o este...', '... Pero qué...', ' ¿no?... ¿si?', '... que bonitos ojos tienes...', '... ¡suéltame!...', '...¡jiii jiji!...', 'grrrr...', '... ¡un pitufito!...', '... ¿mamá?...', '... lo que pasa es que...', '... qué linda mi faldita... ¿verdad?..', ' shhh... ¿oyes las mariposas?...', '... se llamaba Chaaarlyy...', ' ¡looca looca looca! ¡ah-ah!...', '... FUAAAAA...', '... ¡no, yo no me llamo Javier!...', '... ¡quién soy!', '... onde estoy', '... ¡Ay!...', '... ¡basta!...', '... ¡silencio!...'],
            ],
            'fr' => [
                'words'   => ['zombie', 'cadavre', 'souffrances', 'atroce', 'pourriture', 'infection', 'putride', 'fantôme', 'horrible', 'immonde', 'monstre', 'tomate', 'poire', 'banane', 'pomme', 'tulipe', 'peur', 'avion', 'pépin', 'rigolo', 'fleur', 'youpi-banane', 'avion', 'polompolom', 'sapine', 'galinacée', 'canard', 'biloute', 'berlingot', 'patate', 'gourde', 'carotte', 'sapin', 'papa Noël', 'pelle', 'pouic', 'schtroumpfer', 'folie', 'abeille', 'patapon', 'lalala', 'manger', 'dévorer', 'broyer'],
                'inserts' => ['.. AH AH AH !...', '..vais tous vous...', '..les.. voix...', '.gghhh...', 'NOOOoon !...', 'marmonne...', '..hin hin...', 'ALLEZ VOUS EN !..', 'des araignées...', '..ils sont partout...', '..Lachez moi !..', '.. Ah... Ah ah..', '.. qui est là ?..', '..rrRRR... RAAAAaah !', '.r.tuer...', '...ricane..', '...', '..Petiiit papaa Noo.. NOOOON !!...', ' et pirouette cacahuètes ...', '..hein ?', '..froid..', '..ou bien...', '.. Qu\'est-ce que...', ' non ?.. Oui ?', '. Pas du tout..', '.. Laissez moi !', '..Hi hi hi ! ...', 'grogne...', '... Mon beau sapiiin ! ....', '.. roule petit patapon ...', '... toujours un beau temps au nord ...', '.. Alouetteuuh gentiiil.. Hein ?..', ' car il y a longtemps que je t\'aime...', 'mon ami Pierroooot', 'et ainsi font font FONT !! Ah !...'],
            ],
        ],
        self::ModulationHead => [
            'de' => ['...schluck..', 'hust...', '...argh..', '..uuh..', '... ich...', '.g...', 'stöhn...', '..Gnaarr..', '..aah..', '....', ' AAH!..', '..der...', 'ooh...'],
            'en' => ['...ggh..', 'cough...', '...argh..', '..nng..', '... I...', '.n...', 'groan...', '..Gr..', '..raah..', '....', ' RAAH! ..', '..the...', 'bu.. no...'],
            'es' => ['...ggh..', 'yaya-yaya...', '...argh..', '..ghhhnn..', '... yo...', '.g...', 'mamááá...', '..ñe..', '..aajh..', '...', ' jjjh...', 'ayayayayyyyy...', 'por la c...'],
            'fr' => ['...ggh..', 'tousse...', '...argh..', '..gnn..', '... je...', '.g...', 'grogne...', '..Gn..', '..raah..', '....', ' RAAH !..', '..le...', 'qu.. non...'],
        ],
    ];

    public function htmlDistort( string $text, int $modulation, string $lang = 'de', ?bool &$distorted = null ): string {
        $mod_list = [];

        if ($this->rand->chance(0.05)) {
            $distorted = false;
            return $text;
        }

        foreach (static::MODULATION_LIST as $m => $langs)
            if (($m & $modulation) === $m && isset($langs[$lang]) && !empty($langs[$lang]))
                $mod_list[$m] = $langs[$lang];

        if (empty($mod_list)) {
            $distorted = false;
            return $text;
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML( "<html lang=''><head><title></title><meta charset='UTF-8' /></head><body>$text</body></html>", LIBXML_COMPACT | LIBXML_NONET | LIBXML_HTML_NOIMPLIED);
        $body = $dom->getElementsByTagName('body');

        $node_collection = [];
        $total_length = 0;

        $traverse = function(DOMNode $node, int $depth = 0) use (&$node_collection, &$traverse, &$total_length,&$dom) {

            if ($depth > 32) return;

            if ($node->nodeType === XML_ELEMENT_NODE) {

                $process = true;

                $targets = ['*',$node->nodeName];
                for ($i = 0; $i < $node->attributes->length; $i++) {

                    $n = $node->attributes->item($i)->nodeName;
                    foreach ($targets as $target) if (isset(static::HTML_IMMUTABLE["$target.$n"])) {

                        $set = static::HTML_IMMUTABLE["$target.$n"];
                        if ($set === true || in_array($node->attributes->item($i)->nodeValue, $set)) {
                            $process = false;
                            break 2;
                        }

                        if (is_array($set)) foreach (explode(' ', $node->attributes->item($i)->nodeValue) as $value)
                            if (in_array($value, $set)) {
                                $process = false;
                                break 3;
                            }


                    }

                }

                if ($process)
                    foreach ( $node->childNodes as $child )
                        $traverse($child, $depth + 1);

            } elseif ($node->nodeType === XML_TEXT_NODE) {
                $l = mb_strlen($node->textContent);

                if ($l == 0) return;

                $total_length += $l;

                while (($l = mb_strlen($node->textContent)) > 100) {

                    $m = [];
                    preg_match_all('/\s+/', mb_substr($node->textContent, 0, 110), $m, PREG_SET_ORDER|PREG_OFFSET_CAPTURE);
                    if (!empty($m)) $m = $m[array_key_last($m)];

                    $use_split = (!empty($m) && $m[array_key_last($m)][1] > 80);
                    $split = $use_split ? $m[array_key_last($m)][1] : 100;

                    $n = $dom->createTextNode( mb_substr($node->textContent, 0, $split) );
                    $node->textContent = mb_substr( $node->textContent, $split );
                    $node->parentNode->insertBefore( $n, $node );
                    $node_collection[] = [$n, mb_strlen($n->textContent)];
                }
                $node_collection[] = [$node, $l];
            }
        };

        if (isset($body[0])) $traverse($body[0]);

        if ($total_length === 0 || empty($node_collection)) {
            $distorted = false;
            return $text;
        }

        $mod_double_letters = function(array &$segments, float $chance) {
            foreach ($segments as &$segment) {
                $o = ord(strtoupper($segment));
                if ($o >= 65 && $o <= 90 && $this->rand->chance($chance))
                    $segment = chr($o) . '...' . strtolower(chr($o)) . '...' . $segment;
                else {
                    $ri = mt_rand(0, mb_strlen($segment));
                    $o = ord(strtoupper(substr($segment, $ri)));
                    if ($o >= 65 && $o <= 90 && $this->rand->chance($chance))
                        $segment = substr($segment, 0, $ri) . '...' . strtolower(chr($o)) . '...' . substr($segment, $ri);
                }
            }
        };

        $mod_insert_replace = function(array &$segments, float $replace_chance,  int $r, array $word_list, array $insert_list) {
            if ($this->rand->chance($replace_chance)) $segments[$r] = $this->rand->pick( $word_list );
            else $segments = array_merge(
                array_slice($segments,0,$r),
                [$this->rand->pick( $insert_list )],
                array_slice($segments,$r)
            );
        };

        $mod_fun = function(array &$segments, int $mod, int $r, float $factor = 1.0) use (&$mod_list, &$mod_double_letters, &$mod_insert_replace) {
            switch ($mod) {
                case HTMLService::ModulationHead:
                    // Head modulation: Insert distortion in any word
                    $ri = mt_rand(0, mb_strlen($segments[$r]));
                    $segments[$r] = substr($segments[$r], 0, $ri) . $this->rand->pick($mod_list[$mod]) . substr($segments[$r], $ri);
                    break;
                case HTMLService::ModulationTerror:
                    $mod_double_letters($segments, 0.075 * $factor);
                    $mod_insert_replace( $segments, 0.75 * $factor, $r, $mod_list[$mod]['words'], $mod_list[$mod]['inserts'] );
                    break;
                case HTMLService::ModulationDrunk:
                    $mod_double_letters($segments, 0.033 * $factor);
                    $mod_insert_replace( $segments, 0.33 * $factor, $r, $mod_list[$mod]['words'], $mod_list[$mod]['inserts'] );
                    break;
            }
        };

        foreach ($node_collection as $potential_node)
            if ($this->rand->chance( $potential_node[1] / 80.0 )) {

                // Chose a modulation
                $mod = $this->rand->pick(array_keys($mod_list));

                $segments = preg_split('/\s+/', $potential_node[0]->textContent, -1 );

                $distorted = true;
                $r = mt_rand(0,count($segments) - 1);
                $mod_fun($segments, $mod, $r);

                foreach ($segments as $r0 => $segment)
                    if ($r0 !== $r && mb_strlen( $segment ) > 24)
                        $mod_fun($segments, $mod, $r, 1.1);

                $potential_node[0]->textContent = implode(' ', $segments);
            }

        $tmp_str = "";
        foreach ($body->item(0)->childNodes as $child)
            $tmp_str .= $dom->saveHTML($child);

        return $tmp_str;
    }

    protected $emote_cache = null;
    public function get_emotes(bool $url_only = false, User $user = null): array {
        if ($this->emote_cache !== null) return $this->emote_cache;

        $this->emote_cache = [];
        $repo = $this->entity_manager->getRepository(Emotes::class);
        foreach($repo->findAll() as $value){
            /** @var $value Emotes */
            $path = $value->getPath();
            if($value->getI18n())
                $path = str_replace("{lang}", ($user !== null ? $user->getLanguage() : "de"), $path);
            $this->emote_cache[$value->getTag()] = $url_only ? $path : "<img alt='{$value->getTag()}' src='{$this->asset->getUrl( $path )}'/>";
        }
        return $this->emote_cache;
    }

    public function prepareEmotes(string $str, User $user = null, Town $town_context = null): string {
        $emotes = $this->get_emotes(false, $user);

        $fixed_account_translators = [
            66 => 'Der Rabe',
            67 => 'Animateur-Team',
        ];

        return preg_replace_callback('/@(?:​|%E2%80%8B)::(\w+):(\d+)/i', function(array $m) use ($town_context, $fixed_account_translators) {
            [, $type, $id] = $m;
            switch ($type) {
                case 'un':case 'up':
                    $target_user = $this->entity_manager->getRepository(User::class)->find((int)$id);
                    $target_citizen = $town_context ? $target_user->getCitizenFor($town_context) : null;
                    if ($target_user === null) return '';

                    $name_fixed = ($fixed_account_translators[$target_user->getId()] ?? null)
                        ? $this->translator->trans($fixed_account_translators[$target_user->getId()], [], 'global')
                        : null;

                    return $type === 'un'
                        ? $name_fixed ?? ($target_citizen ? $target_citizen->getName() : $target_user->getName())
                        : $this->router->generate('soul_visit', ['id' => $target_user->getId()]);
                case 'dom':
                    return (int)$id === 0 ? mb_substr($this->router->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL), 0,-1) : '';
                default: return '';
            }
        }, str_replace( array_keys( $emotes ), array_values( $emotes ), $str ));
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
            if (!$entry->getPrototype()) continue;
            if ($entry->getPrototype()->getAssociatedTag() && in_array($entry->getPrototype()->getAssociatedTag(), $results)) {
                unset($results[array_search($entry->getPrototype()->getAssociatedTag(), $results)]);
            }
        }

        return array_values($results);
    }
}
