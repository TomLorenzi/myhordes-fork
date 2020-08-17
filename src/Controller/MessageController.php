<?php

namespace App\Controller;

use App\Entity\ActionCounter;
use App\Entity\AdminReport;
use App\Entity\Award;
use App\Entity\Changelog;
use App\Entity\Citizen;
use App\Entity\Complaint;
use App\Entity\Emotes;
use App\Entity\Forum;
use App\Entity\Item;
use App\Entity\ItemPrototype;
use App\Entity\Post;
use App\Entity\PrivateMessage;
use App\Entity\PrivateMessageThread;
use App\Entity\Thread;
use App\Entity\ThreadReadMarker;
use App\Entity\Town;
use App\Entity\User;
use App\Service\AdminActionHandler;
use App\Service\CitizenHandler;
use App\Service\ErrorHelper;
use App\Service\InventoryHandler;
use App\Service\JSONRequestParser;
use App\Service\PictoHandler;
use App\Service\RandomGenerator;
use App\Service\TimeKeeperService;
use App\Response\AjaxResponse;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @IsGranted("ROLE_USER")
 * @method User getUser
 */
class MessageController extends AbstractController
{
    const ErrorForumNotFound    = ErrorHelper::BaseForumErrors + 1;
    const ErrorPostTextLength   = ErrorHelper::BaseForumErrors + 2;
    const ErrorPostTitleLength  = ErrorHelper::BaseForumErrors + 3;
    const ErrorPMItemLimitHit   = ErrorHelper::BaseForumErrors + 4;
    const ErrorForumLimitHit    = ErrorHelper::BaseForumErrors + 5;

    private $rand;
    private $asset;
    private $trans;
    private $entityManager;
    private $inventory_handler;
    private $time_keeper;

    public function __construct(RandomGenerator $r, TranslatorInterface $t, Packages $a, EntityManagerInterface $em, InventoryHandler $ih, TimeKeeperService $tk)
    {
        $this->asset = $a;
        $this->rand = $r;
        $this->trans = $t;
        $this->time_keeper = $tk;
        $this->entityManager = $em;
        $this->inventory_handler = $ih;
    }

    protected function addDefaultTwigArgs( ?array $data = null ): array {
        $data = $data ?? [];

        $data['clock'] = [
            'desc'      => $this->getUser()->getActiveCitizen() !== null ? $this->getUser()->getActiveCitizen()->getTown()->getName() : $this->trans->trans('Worauf warten Sie noch?', [], 'ghost'),
            'day'       => $this->getUser()->getActiveCitizen() !== null ? $this->getUser()->getActiveCitizen()->getTown()->getDay() : "",
            'timestamp' => new DateTime('now'),
            'attack'    => $this->time_keeper->secondsUntilNextAttack(null, true),
            'towntype'  => $this->getUser()->getActiveCitizen() !== null ? $this->getUser()->getActiveCitizen()->getTown()->getType()->getName() : "",
        ];

        return $data;
    }

    private function default_forum_renderer(int $fid, int $tid, int $pid, EntityManagerInterface $em, JSONRequestParser $parser, CitizenHandler $ch): Response {
        $num_per_page = 20;

        $user = $this->getUser();

        /** @var Forum[] $forums */
        $forums = $em->getRepository(Forum::class)->findForumsForUser($user, $fid);
        if (count($forums) !== 1) return $this->redirect($this->generateUrl('forum_list'));

        // Set the activity status
        if ($forums[0]->getTown() && $user->getActiveCitizen()) {
            $c = $user->getActiveCitizen();
            if ($c) $ch->inflictStatus($c, 'tg_chk_forum');
            $em->persist( $c );
            $em->flush();
        }

        $pages = floor(max(0,$em->getRepository(Thread::class)->countByForum($forums[0])-1) / $num_per_page) + 1;
        if ($parser->has('page'))
            $page = min(max(1,$parser->get('page', 1)), $pages);
        else $page = 1;

        $threads = $em->getRepository(Thread::class)->findByForum($forums[0], $num_per_page, ($page-1)*$num_per_page);

        foreach ($threads as $thread) {
            /** @var Thread $thread */
            /** @var ThreadReadMarker $marker */
            $marker = $em->getRepository(ThreadReadMarker::class)->findByThreadAndUser($user, $thread);
            if ($marker && $thread->getLastPost() <= $marker->getPost()->getDate()) $thread->setNew();
        }

        $pinned_threads = $em->getRepository(Thread::class)->findPinnedByForum($forums[0], 20, 0);

        foreach ($pinned_threads as $thread) {
            /** @var Thread $thread */
            /** @var ThreadReadMarker $marker */
            $marker = $em->getRepository(ThreadReadMarker::class)->findByThreadAndUser($user, $thread);
            if ($marker && $thread->getLastPost() <= $marker->getPost()->getDate()) $thread->setNew();
        }

        return $this->render( 'ajax/forum/view.html.twig', $this->addDefaultTwigArgs([
            'forum' => $forums[0],
            'threads' => $threads,
            'pinned_threads' => $pinned_threads,
            'select' => $tid,
            'jump' => $pid,
            'pages' => $pages,
            'current_page' => $page,
        ] ));
    }

    /**
     * @Route("jx/forum/town", name="forum_town_redirect")
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function forum_redirector(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        /** @var Citizen $citizen */
        $citizen = $em->getRepository(Citizen::class)->findActiveByUser( $user );

        if ($citizen !== null && $citizen->getAlive() && $citizen->getTown()->getForum())
            return $this->redirect($this->generateUrl('forum_view', ['id' => $citizen->getTown()->getForum()->getId()]));
        else return $this->redirect( $this->generateUrl( 'forum_list' ) );
    }

    /**
     * @Route("jx/forum/{id<\d+>}", name="forum_view")
     * @param int $id
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $p
     * @param CitizenHandler $ch
     * @return Response
     */
    public function forum(int $id, EntityManagerInterface $em, JSONRequestParser $p, CitizenHandler $ch): Response
    {
        return $this->default_forum_renderer($id,-1,-1,$em, $p, $ch);
    }

    /**
     * @Route("jx/forum/{fid<\d+>}/{tid<\d+>}", name="forum_thread_view")
     * @param int $fid
     * @param int $tid
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $p
     * @param CitizenHandler $ch
     * @return Response
     */
    public function forum_thread(int $fid, int $tid, EntityManagerInterface $em, JSONRequestParser $p, CitizenHandler $ch): Response
    {
        return $this->default_forum_renderer($fid,$tid,-1,$em,$p,$ch);
    }

    /**
     * @Route("jx/forum/jump/{pid<\d+>}", name="forum_jump_view")
     * @param int $pid
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $p
     * @param CitizenHandler $ch
     * @return Response
     */
    public function forum_jump_post(int $pid, EntityManagerInterface $em, JSONRequestParser $p, CitizenHandler $ch): Response
    {
        /** @var Post $post */
        $post = $this->entityManager->getRepository(Post::class)->find($pid);

        return $this->default_forum_renderer($post ? $post->getThread()->getForum()->getId() : -1,$post ? $post->getThread()->getId() : -1,$post ? $pid : -1,$em,$p,$ch);
    }

    /**
     * @Route("jx/forum", name="forum_list")
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function forums(EntityManagerInterface $em): Response
    {
        $forum_list = $em->getRepository(Forum::class)->findForumsForUser( $this->getUser() );
        return $this->render( 'ajax/forum/list.html.twig', $this->addDefaultTwigArgs([
            'forums' => $forum_list,
        ] ));
    }

    private const HTML_ALLOWED = [
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

    private const HTML_ALLOWED_ADMIN = [
        'img' => [ 'alt', 'src', 'title']
    ];

    private const HTML_ATTRIB_ALLOWED_ADMIN = [
        'div.class' => [
            'adminAnnounce',
        ]
    ];

    private const HTML_ATTRIB_ALLOWED_ORACLE = [ 
        'div.class' => [ 
            'oracleAnnounce'
        ]
    ];

    private const HTML_ATTRIB_ALLOWED_CROW = [ 
        'div.class' => [ 
            'modAnnounce', 'html'
        ]
    ];

    private const HTML_ATTRIB_ALLOWED = [
        'div.class' => [
            'glory', 'spoiler', 'sideNote',
            'dice-4', 'dice-6', 'dice-8', 'dice-10', 'dice-12', 'dice-20', 'dice-100',
            'letter-a', 'letter-v', 'letter-c',
            'rps', 'coin', 'card',
             'citizen', 'rpText',
        ],
        'span.class' => [
            'quoteauthor','bad','rpauthor'
        ]
    ];

    private function getAllowedHTML(): array {
        $r = self::HTML_ALLOWED;
        $a = self::HTML_ATTRIB_ALLOWED;

        if ($this->isGranted("ROLE_ADMIN")) {
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

        if ($this->isGranted("ROLE_CROW")) {
            foreach (self::HTML_ATTRIB_ALLOWED_CROW as $key => $value) {
                if(isset($a[$key])) {
                    $a[$key] = array_merge($a[$key], self::HTML_ATTRIB_ALLOWED_CROW[$key]);
                } else {
                    $a[$key] = self::HTML_ATTRIB_ALLOWED_CROW[$key];
                }
            }
        }
        if ($this->isGranted("ROLE_ORACLE")) {
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

    private function htmlValidator( array $allowedNodes, ?DOMNode $node, int &$text_length, int $depth = 0 ): bool {
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

    private function preparePost(User $user, ?Forum $forum, $post, int &$tx_len, ?Town $town = null, ?bool &$editable = null): bool {
        if (!$town && $forum && $forum->getTown())
            $town = $forum->getTown();

        $editable = true;

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML( '<?xml encoding="utf-8" ?>' . $post->getText() );
        $body = $dom->getElementsByTagName('body');
        if (!$body || $body->length > 1) return false;

        if (!$this->htmlValidator($this->getAllowedHTML(), $body->item(0),$tx_len))
            return false;

        $cache = [
            'citizen' => [],
        ];
        $handlers = [
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
            '//div[@class=\'rps\']'      => function (DOMNode $d) use(&$editable) { $editable = false; $d->nodeValue = $this->rand->pick([$this->trans->trans('Schere',[],'global'),$this->trans->trans('Stein',[],'global'),$this->trans->trans('Papier',[],'global')]); },
            '//div[@class=\'coin\']'     => function (DOMNode $d) use(&$editable) { $editable = false; $d->nodeValue = $this->rand->pick([$this->trans->trans('Kopf',[],'global'),$this->trans->trans('Zahl',[],'global')]); },
            '//div[@class=\'card\']'     => function (DOMNode $d) use(&$editable) { $editable = false;
                $s_color = $this->rand->pick([$this->trans->trans('Kreuz',[],'items'),$this->trans->trans('Pik',[],'items'),$this->trans->trans('Herz',[],'items'),$this->trans->trans('Karo',[],'items')]);
                $value = mt_rand(1,12);
                $s_value = $value < 9 ? ('' . ($value+2)) : [$this->trans->trans('Bube',[],'items'),$this->trans->trans('Dame',[],'items'),$this->trans->trans('König',[],'items'),$this->trans->trans('Ass',[],'items')][$value-9];
                $d->nodeValue = $this->trans->trans('{color} {value}', ['{color}' => $s_color, '{value}' => $s_value], 'global');
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
                $d->nodeValue = $cc->getUser()->getUsername();
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
        if ($post->getType() !== 'CROW'){
            if ($forum !== null && $forum->getTown()) {
                foreach ( $forum->getTown()->getCitizens() as $citizen )
                    if ($citizen->getUser()->getId() === $user->getId()) {
                        $note = null;
                        if ($citizen->getZone() && ($citizen->getZone()->getX() > 0 || $citizen->getZone()->getY() > 0))  {
                            if($citizen->getTown()->getChaos()){
                                $note = $this->trans->trans('Draußen', [], 'game');
                            } else {
                                $note = "[{$citizen->getZone()->getX()}, {$citizen->getZone()->getY()}]";
                            }
                        }
                        else {
                            $note = $this->trans->trans('in der Stadt oder am Stadttor', [], 'game');
                        }
                        // <img src="{{ asset('build/images/professions/' ~ post.owner.getActiveCitizen.profession.icon ~ '.gif') }}" />
                        // return "<img alt='' src='{$this->asset->getUrl( "build/images/item/item_{$obj->getPrototype()->getIcon()}.gif" )}' /> {$this->trans->trans($obj->getPrototype()->getLabel(), [], 'items')} <i>x {$obj->getChance()}</i>";
                        $post->setNote("<img alt='' src='{$this->asset->getUrl("build/images/professions/{$citizen->getProfession()->getIcon()}.gif")}' /> <img alt='' src='{$this->asset->getUrl('build/images/icons/item_map.gif')}' /> <span>$note</span>");
                    }
            }
        }

        return true;
    }

    private $emote_cache = null;
    private function get_emotes(bool $url_only = false): array {
        if ($this->emote_cache !== null) return $this->emote_cache;

        $this->emote_cache = [];
        $repo = $this->entityManager->getRepository(Emotes::class);
        foreach($repo->findAll() as $value)
            /** @var $value Emotes */
        $this->emote_cache[$value->getTag()] = $url_only ? $value->getPath() : "<img alt='{$value->getTag()}' src='{$this->asset->getUrl( $value->getPath() )}'/>";
        return $this->emote_cache;
    }

    private function getEmotesByUser(User $user, bool $url_only = false): array {
        $repo = $this->entityManager->getRepository(Emotes::class);
        $emotes = $repo->getDefaultEmotes();
        $awards = $this->entityManager->getRepository(Award::class)->getAwardsByUser($user);
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

    private function prepareEmotes(string $str): string {
        $emotes = $this->get_emotes();
        return str_replace( array_keys( $emotes ), array_values( $emotes ), $str );
    }

    private function filterLockedEmotes(User $user, string $text): string {
        $lockedEmotes = $this->getLockedEmoteTags($user);
        foreach($lockedEmotes as $emote) {
            $text = str_replace($emote, '', $text);
        }
        return $text;
    }

    private function getLockedEmoteTags(User $user): array {
        $emotes = $this->entityManager->getRepository(Emotes::class)->getUnlockableEmotes();
        $unlocks = $this->entityManager->getRepository(Award::class)->getAwardsByUser($user);
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

    /**
     * @Route("api/forum/{id<\d+>}/post", name="forum_new_thread_controller")
     * @param int $id
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function new_thread_api(int $id, JSONRequestParser $parser, EntityManagerInterface $em, AdminActionHandler $admh): Response {
        $forums = $em->getRepository(Forum::class)->findForumsForUser($this->getUser(), $id);
        if (count($forums) !== 1) return AjaxResponse::error( self::ErrorForumNotFound );

        /** @var Forum $forum */
        $forum = $forums[0];

        $user = $this->getUser();
        if ($user->getIsBanned())
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if (!$parser->has_all(['title','text'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);


        $title = $parser->trimmed('title');
        $text  = $parser->trimmed('text');

        $type = $this->isGranted("ROLE_CROW") ? $parser->get('type') : 'USER';
        if (!in_array($type, ['DEV','CROW','USER'])) $type = 'USER';

        if (mb_strlen($title) < 3 || mb_strlen($title) > 64)   return AjaxResponse::error( self::ErrorPostTitleLength );
        if (mb_strlen($text) < 2 || mb_strlen($text) > 16384) return AjaxResponse::error( self::ErrorPostTextLength );

        $thread = (new Thread())->setTitle( $title )->setOwner($user);

        $post = (new Post())
            ->setOwner( $type === "CROW" ? $this->entityManager->getRepository(User::class)->find(66) : $user )
            ->setText( $text )
            ->setDate( new DateTime('now') )
            ->setType($type)
            ->setEditingMode( Post::EditorPerpetual )
            ->setLastAdminActionBy($type === "CROW" ? $user : null);

        $tx_len = 0;
        if (!$this->preparePost($user,$forum,$post,$tx_len, null, $edit))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        if ($tx_len < 2) return AjaxResponse::error( self::ErrorPostTextLength );

        if (!$edit) $post->setEditingMode( Post::EditorLocked );

        $thread->addPost($post)->setLastPost( $post->getDate() );
        $forum->addThread($thread);

        try {
            $em->persist($thread);
            $em->persist($forum);
            $em->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success( true, ['url' => $this->generateUrl('forum_thread_view', ['fid' => $id, 'tid' => $thread->getId()])] );
    }

    /**
     * @Route("api/forum/{fid<\d+>}/{tid<\d+>}/post", name="forum_new_post_controller")
     * @param int $fid
     * @param int $tid
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param AdminActionHandler $admh
     * @param PictoHandler $ph
     * @return Response
     */
    public function new_post_api(int $fid, int $tid, JSONRequestParser $parser, EntityManagerInterface $em, AdminActionHandler $admh, PictoHandler $ph): Response {
        $user = $this->getUser();
        if ($user->getIsBanned())
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $thread = $em->getRepository(Thread::class)->find( $tid );
        if (!$thread || $thread->getForum()->getId() !== $fid) return AjaxResponse::error( self::ErrorForumNotFound );
        if ($thread->getLocked())
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        // Check the last 4 threads; if they were all made by the same user, they must wait 4h before they can post again
        if (!$this->isGranted('ROLE_CROW') && !$this->isGranted('ROLE_ORACLE')) {
            $last_posts = $this->entityManager->getRepository(Post::class)->findBy(['thread' => $thread], ['date' => 'DESC'], 4);
            if (count($last_posts) === 4) {
                $all_by_user = true;
                foreach ($last_posts as $last_post) $all_by_user = $all_by_user && ($last_post->getOwner() === $user);
                if ($all_by_user && $last_posts[0]->getDate()->getTimestamp() > (time() - 14400) )
                    return AjaxResponse::error( self::ErrorForumLimitHit );
            }
        }

        $mod_post = false;
        $forums = $em->getRepository(Forum::class)->findForumsForUser($user, $fid);
        if (count($forums) !== 1){
            if (!($user->getRightsElevation() >= User::ROLE_CROW && $thread->hasReportedPosts())){
                return AjaxResponse::error( self::ErrorForumNotFound );
            } else $mod_post = true;
        } 

        /** @var Forum $forum */
        $forum = $thread->getForum();

        if (!$parser->has_all(['text'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $text = $parser->get('text');

        $type = $this->isGranted("ROLE_CROW") ? $parser->get('type') : 'USER';
        if (!in_array($type, ['DEV','CROW','USER'])) $type = 'USER';

        $post = (new Post())
            ->setOwner( $type === "CROW" ? $this->entityManager->getRepository(User::class)->find(66) : $user )
            ->setText( $text )
            ->setDate( new DateTime('now') )
            ->setType($type)
            ->setEditingMode( $type !== "USER" ? Post::EditorPerpetual : Post::EditorTimed )
            ->setLastAdminActionBy($type === "CROW" ? $user : null);

        $tx_len = 0;
        if (!$this->preparePost($user,$forum,$post,$tx_len, null, $edit))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if ($tx_len < 2) return AjaxResponse::error( self::ErrorPostTextLength );

        if (!$edit) $post->setEditingMode(Post::EditorLocked);

        $thread->addPost($post)->setLastPost( $post->getDate() );
        if ($forum->getTown()) {
            foreach ($forum->getTown()->getCitizens() as $citizen)
                if ($citizen->getUser()->getId() === $user->getId()) {
                    // Give picto if the post is in the town forum
                    $ph->give_picto($citizen, 'r_forum_#00');
                }
        }

        try {
            $em->persist($thread);
            $em->persist($forum);
            $em->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success( true, ['url' => $mod_post
            ? $this->generateUrl('admin_reports')
            : $this->generateUrl('forum_thread_view', ['fid' => $fid, 'tid' => $tid])
        ] );
    }

    /**
     * @Route("api/forum/{fid<\d+>}/{tid<\d+>}/{pid<\d+>}/edit", name="forum_edit_post_controller")
     * @param int $fid
     * @param int $tid
     * @param int $pid
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param AdminActionHandler $admh
     * @param PictoHandler $ph
     * @return Response
     */
    public function edit_post_api(int $fid, int $tid, int $pid, JSONRequestParser $parser, EntityManagerInterface $em, AdminActionHandler $admh, PictoHandler $ph): Response {
        $user = $this->getUser();
        if ($user->getIsBanned()) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $post = $em->getRepository(Post::class)->find($pid);
        if (!$post) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );


        if ((
            ($post->getOwner() !== $user && !$this->isGranted("ROLE_CROW")) &&
            !($post->getOwner()->getId() === 66 || $this->isGranted("ROLE_CROW")))

            ||

            (!$post->isEditable() && !$this->isGranted("ROLE_CROW")))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if ($post->getTranslate()) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $thread = $em->getRepository(Thread::class)->find( $tid );
        if (!$thread || $thread->getForum()->getId() !== $fid || $post->getThread() !== $thread)
            return AjaxResponse::error( self::ErrorForumNotFound );

        if ($thread->getLocked() && !$this->isGranted("ROLE_CROW"))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        /** @var Forum $forum */
        $forum = $thread->getForum();

        if (!$parser->has_all(['text'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $text = $parser->get('text');

        $old_text = $post->getText();
        $post
            ->setText( $text )
            ->setEdited( new DateTime() );

        if ($user !== $post->getOwner()) {
            $post
                ->setEditingMode(Post::EditorLocked)
                ->setLastAdminActionBy($user);
            if ($post->getOriginalText() === null && $post->getOwner()->getId() !== 66)
                $post->setOriginalText($old_text);
        }


        $tx_len = 0;
        if (!$this->preparePost($user,$forum,$post,$tx_len, null, $edit))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if ($tx_len < 2) return AjaxResponse::error( self::ErrorPostTextLength );

        if (!$edit) $post->setEditingMode(Post::EditorLocked);

        try {
            $em->persist($post);
            $em->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success( true, ['url' => $this->generateUrl('forum_jump_view', ['pid' => $pid])] );
    }


    /**
     * @Route("api/forum/{sem<\d+>}/{fid<\d+>}/preview", name="forum_previewer_controller")
     * @param int $fid
     * @param int $sem
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function small_viewer_api( int $fid, int $sem, EntityManagerInterface $em ) {
        $user = $this->getUser();

        if ($sem === 0) return new Response('');

        $forums = $em->getRepository(Forum::class)->findForumsForUser($user, $fid);
        if (count($forums) !== 1)
            return new Response('');

        /** @var Thread $thread */
        $thread = $em->getRepository(Thread::class)->findByForumSemantic( $forums[0], $sem );
        if (!$thread || $thread->getForum()->getId() !== $fid) return new Response(' ');

        $posts = $em->getRepository(Post::class)->findUnhiddenByThread($thread, 5, -5);

        foreach ($posts as $post) $post->setText( $this->prepareEmotes( $post->getText() ) );
        return $this->render( 'ajax/forum/posts_small.html.twig', [
            'posts' => $posts,
            'fid' => $fid,
            'tid' => $thread->getId(),
        ] );
    }

    /**
     * @Route("api/forum/{tid<\d+>}/{fid<\d+>}/view/{pid<\d+>}", name="forum_viewer_controller")
     * @param int $fid
     * @param int $tid
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function viewer_api(int $fid, int $tid, EntityManagerInterface $em, JSONRequestParser $parser, int $pid = -1): Response {
        $num_per_page = 10;
        $user = $this->getUser();

        /** @var Thread $thread */
        $thread = $em->getRepository(Thread::class)->find( $tid );
        if (!$thread || $thread->getForum()->getId() !== $fid) return new Response('');

        $forums = $em->getRepository(Forum::class)->findForumsForUser($user, $fid);
        if (count($forums) !== 1){
            if (!($user->getRightsElevation() >= User::ROLE_CROW && $thread->hasReportedPosts())){
                return new Response('');
            }      
        }

        $jump_post = ($pid > 0) ? $em->getRepository(Post::class)->find( $pid ) : null;
        if ($jump_post && $jump_post->getThread() !== $thread) return new Response('');

        $marker = $em->getRepository(ThreadReadMarker::class)->findByThreadAndUser( $user, $thread );
        if (!$marker) $marker = (new ThreadReadMarker())->setUser($user)->setThread($thread);

        if ($user->getRightsElevation() >= User::ROLE_CROW)
            $pages = floor(max(0,$em->getRepository(Post::class)->countByThread($thread)-1) / $num_per_page) + 1;
        else
            $pages = floor(max(0,$em->getRepository(Post::class)->countUnhiddenByThread($thread)-1) / $num_per_page) + 1;

        if ($jump_post)
            $page = min($pages,1 + floor((1+$em->getRepository(Post::class)->getOffsetOfPostByThread( $thread, $jump_post )) / $num_per_page));
        elseif ($parser->has('page'))
            $page = min(max(1,$parser->get('page', 1)), $pages);
        elseif (!$marker->getPost()) $page = 1;
        else $page = min($pages,1 + floor((1+$em->getRepository(Post::class)->getOffsetOfPostByThread( $thread, $marker->getPost() )) / $num_per_page));

        if ($user->getRightsElevation() >= User::ROLE_CROW)
            $posts = $em->getRepository(Post::class)->findByThread($thread, $num_per_page, ($page-1)*$num_per_page);
        else
            $posts = $em->getRepository(Post::class)->findUnhiddenByThread($thread, $num_per_page, ($page-1)*$num_per_page);


        $announces = [
            'admin' => [],
            'oracle' => []
        ];

        $announces['admin'] = $em->getRepository(Post::class)->findAdminAnnounces($thread);
        $announces['oracle'] = $em->getRepository(Post::class)->findOracleAnnounces($thread);

        foreach ($posts as $post){
            /** @var $post Post */
            if ($marker->getPost() === null || $marker->getPost()->getId() < $post->getId())
                $post->setNew();
        }

        if (!empty($posts)) {
            /** @var Post $read_post */
            $read_post = $posts[array_key_last($posts)];
            /** @var Post $last_read */
            $last_read = $marker->getPost();
            if ($last_read === null || $read_post->getId() > $last_read->getId()) {
                $marker->setPost($read_post);
                try {
                    $em->persist($marker);
                    $em->flush();
                } catch (Exception $e) {}
            }
        }

        foreach ($posts as &$post) $post->setText( $this->prepareEmotes( $post->getText() ) );
        return $this->render( 'ajax/forum/posts.html.twig', [
            'posts' => $posts,
            'owned' => $thread->getOwner() === $user,
            'locked' => $thread->getLocked(),
            'pinned' => $thread->getPinned(),
            'fid' => $fid,
            'tid' => $tid,
            'current_page' => $page,
            'pages' => $pages,
            'announces' => $announces,
            'markedPost' => $pid,
        ] );
    }

    /**
     * @Route("api/forum/{id<\d+>}/editor", name="forum_thread_editor_controller")
     * @param int $id
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function editor_thread_api(int $id, EntityManagerInterface $em): Response {
        /** @var Forum[] $forums */
        $forums = $em->getRepository(Forum::class)->findForumsForUser($this->getUser(), $id);
        if (count($forums) !== 1) return new Response('');

        return $this->render( 'ajax/forum/editor.html.twig', [
            'fid' => $id,
            'tid' => null,
            'pid' => null,
            'emotes' => $this->getEmotesByUser($this->getUser(),true),
            'username' => $this->getUser()->getUsername(),
            'forum' => true,
            'town_controls' => $forums[0]->getTown() !== null,
        ] );
    }

    /**
     * @Route("api/forum/{fid<\d+>}/{tid<\d+>}/editor", name="forum_post_editor_controller")
     * @param int $fid
     * @param int $tid
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function editor_post_api(int $fid, int $tid, EntityManagerInterface $em, JSONRequestParser $parser): Response {
        $user = $this->getUser();

        $thread = $em->getRepository( Thread::class )->find( $tid );
        if ($thread === null || $thread->getForum()->getId() !== $fid) return new Response('');

        $forums = $em->getRepository(Forum::class)->findForumsForUser($user, $fid);
        if (count($forums) !== 1){
            if (!($this->isGranted("ROLE_CROW") && $thread->hasReportedPosts())){
                return new Response('');
            }      
        }

        $pid = $parser->get('pid', null);
        if ($pid !== null) {
            $post = $em->getRepository(Post::class)->find((int)$pid);
            if (!$post || (!$post->isEditable() && !$this->isGranted("ROLE_CROW")) || $post->getThread() !== $thread || (
                (($post->getOwner() !== $user && !$this->isGranted("ROLE_CROW")) && !($this->isGranted("ROLE_CROW") && $post->getOwner()->getId() === 66))
            )) return new Response('');
        }

        return $this->render( 'ajax/forum/editor.html.twig', [
            'fid' => $fid,
            'tid' => $tid,
            'pid' => $pid,
            'emotes' => $this->getEmotesByUser($this->getUser(),true),
            'forum' => true,
            'town_controls' => isset($forums[0]) ? $forums[0]->getTown() !== null : null,
        ] );
    }

    /**
     * @Route("api/forum/{fid<\d+>}/{tid<\d+>}/moderate/{mod}", name="forum_thread_mod_controller")
     * @param int $fid
     * @param int $tid
     * @param string $mod
     * @param JSONRequestParser $parser
     * @param AdminActionHandler $admh
     * @return Response
     */
    public function lock_thread_api(int $fid, int $tid, string $mod, JSONRequestParser $parser, AdminActionHandler $admh): Response {
        $success = false;
        $uid = $this->getUser()->getId();

        switch ($mod) {
            case 'lock':
                $thread = $this->entityManager->getRepository(Thread::class)->find($tid);
                if ($thread && $thread->getOwner() === $this->getUser()) {
                    $thread->setLocked(true);
                    try {
                        $this->entityManager->persist($thread);
                        $this->entityManager->flush();
                        $success = true;
                    } catch (Exception $e) {
                        $success = false;
                    }
                }
                else $success = $admh->lockThread($uid, $fid, $tid); break;

            case 'unlock': $success = $admh->unlockThread($uid, $fid, $tid); break;
            case 'pin':    $success = $admh->pinThread($uid, $fid, $tid); break;
            case 'unpin':  $success = $admh->unpinThread($uid, $fid, $tid); break;
            case 'delete': $success = $admh->hidePost($uid, (int)$parser->get('postId'), $parser->get( 'reason', '' ) ); break;
            default: break;
        }

        return $success ? AjaxResponse::success() : AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
    }

    /**
     * @Route("api/forum/{fid<\d+>}/{tid<\d+>}/post/report", name="forum_report_post_controller")
     * @param int $fid
     * @param int $tid
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $ti
     * @return Response
     */
    public function report_post_api(int $fid, int $tid, JSONRequestParser $parser, EntityManagerInterface $em, TranslatorInterface $ti): Response {
        if (!$parser->has('postId')){
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        $user = $this->getUser();
        $postId = $parser->get('postId');

        $post = $em->getRepository( Post::class )->find( $postId );
        if ($post->getTranslate() || $post->getThread()->getId() !== $tid || $post->getThread()->getForum()->getId() !== $fid) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $targetUser = $post->getOwner();
        if ($targetUser->getUsername() === "Der Rabe" ) {
            $message = $ti->trans('Das ist keine gute Idee, das ist dir doch wohl klar!', [], 'game');
            $this->addFlash('notice', $message);
            return AjaxResponse::success();
        }

        $reports = $post->getAdminReports();
        foreach ($reports as $report)
            if ($report->getSourceUser()->getId() == $user->getId())
                return AjaxResponse::success();

            $newReport = (new AdminReport())
            ->setSourceUser($user)
            ->setTs(new DateTime('now'))
            ->setPost($post);

            try {
                $em->persist($newReport);
                $em->flush();
            } catch (Exception $e) {
                return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
            }
            $message = $ti->trans('Du hast die Nachricht von %username% dem Raben gemeldet. Wer weiß, vielleicht wird %username% heute Nacht stääärben...', ['%username%' => '<span>' . $post->getOwner()->getUsername() . '</span>'], 'game');
            $this->addFlash('notice', $message);
            return AjaxResponse::success( );
        }

    /**
     * @Route("api/town/house/sendpm", name="town_house_send_pm_controller")
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @param TranslatorInterface $t
     * @return Response
     */
    public function send_pm_api(EntityManagerInterface $em, JSONRequestParser $parser, TranslatorInterface $t): Response {
        $type      = $parser->get('type', "");
        $recipient = $parser->get('recipient', '');
        $title     = $parser->get('title', '');
        $content   = $parser->get('content', '');
        $items     = $parser->get('items', '');
        $tid       = $parser->get('tid', -1);

        $allowed_types = ['pm', 'global'];

        if(!in_array($type, $allowed_types)) {
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        if($type === 'pm' && (empty($recipient) && $tid === -1))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if(($tid === -1 && empty($title)) || empty($content)) {
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        $sender = $this->getUser()->getActiveCitizen();

        if($type === "global" && !$sender->getProfession()->getHeroic()){
            return AjaxResponse::error(ErrorHelper::ErrorMustBeHero);
        }

        $linked_items = array();

        if(is_array($items)){
            foreach ($items as $item_id) {
                $valid = false;
                $item = $em->getRepository(Item::class)->find($item_id);

                if (in_array($item->getPrototype()->getName(), ['bagxl_#00', 'bag_#00', 'cart_#00', 'pocket_belt_#00'])) {
                    // We cannot send bag expansion
                    continue;
                }

                if($item->getInventory()->getHome() !== null && $item->getInventory()->getHome()->getCitizen() === $sender){
                    // This is an item from a chest
                    $valid = true;
                } else if($item->getInventory()->getCitizen() === $sender){
                    // This is an item from the rucksack
                    $valid = true;
                }

                if($sender->getTown()->getChaos() && count($linked_items) > 3) {
                    return AjaxResponse::error(self::ErrorPMItemLimitHit);
                }

                if($valid)
                    $linked_items[] = $item;
            }
        }
        $global_thread = null;
        if ($tid !== -1) {
            $global_thread = $em->getRepository(PrivateMessageThread::class)->find($tid);
            if ($global_thread === null || $global_thread->getSender() === null)
                return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

            if ($global_thread->getSender() !== $sender && $global_thread->getRecipient() !== $sender)
                return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
        }
        $global_recipient = $global_thread ? (
            $global_thread->getSender() === $sender ? $global_thread->getRecipient() : $global_thread->getSender()
        ) : null;

        $recipients = [];
        if ($type === 'pm') {
            $recipient = $global_recipient ?? $em->getRepository(Citizen::class)->find($recipient);

            if (count($linked_items) > 0) {
                if ($recipient->getBanished() != $sender->getBanished())
                    return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
                if ($sender->getTown()->getChaos()){
                    if($recipient->getZone())
                        return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
                    else {
                        $counter = $sender->getSpecificActionCounter(ActionCounter::ActionTypeSendPMItem);
                        if($counter->getCount() > 3)
                            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
                        else if ($counter->getCount() + count($linked_items) > 3)
                            return AjaxResponse::error(self::ErrorPMItemLimitHit);
                        else {
                            $counter->setCount(min($counter->getCount() + count($linked_items), 3));
                            $em->persist($counter);
                        }
                    }
                }

                // Check inventory size
                $max_size = $this->inventory_handler->getSize($recipient->getHome()->getChest());
                if ($max_size > 0 && count($recipient->getHome()->getChest()->getItems()) + count($linked_items) >= $max_size)
                    return AjaxResponse::error(InventoryHandler::ErrorInventoryFull);
            }

            if ($recipient) $recipients[] = $recipient;

        } else {

            if ($global_thread) return AjaxResponse::errorMessage( ErrorHelper::ErrorActionNotAvailable );

            foreach ($sender->getTown()->getCitizens() as $citizen)
                $recipients[] = $citizen;

            if (count($linked_items) > 0) return AjaxResponse::error(self::ErrorPMItemLimitHit);

        }

        if (empty($recipients)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $success = 0;
        foreach ($recipients as $recipient) {
            if(!$recipient->getAlive()) continue;
            if($recipient == $sender) continue;

            if (!$global_thread) {
                $thread = new PrivateMessageThread();

                $thread->setSender($sender)
                    ->setTitle($title)
                    ->setLocked(false)
                    ->setLastMessage(new DateTime('now'))
                    ->setRecipient($recipient);
            } else
                $thread = $global_thread;

            $post = new PrivateMessage();
            $post->setDate(new DateTime('now'))
                ->setText($content)
                ->setPrivateMessageThread($thread)
                ->setOwner($sender)
                ->setNew(true)
                ->setRecipient($recipient);

            $items_prototype = [];
            foreach ($linked_items as $item) {
                $items_prototype[] = $item->getPrototype()->getId();
                $this->inventory_handler->forceMoveItem($recipient->getHome()->getChest(), $item);
            }

            $post->setItems($items_prototype);

            $tx_len = 0;
            if (!$this->preparePost($this->getUser(),null,$post,$tx_len, $recipient->getTown()))
                return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

            $thread
                ->setLastMessage($post->getDate())
                ->addMessage($post);

            $success++;
            $em->persist($thread);
            $em->persist($post);
        }

        $em->flush();

        if ($success === 0) {
            return AjaxResponse::error( ErrorHelper::ErrorInternalError );
        } else {
            // Show confirmation
            if(count($linked_items) > 0)
                $message = $t->trans("Deine Nachricht und deine ausgewählten Gegenstände wurden überbracht.", [], 'game');
            else
                $message = $t->trans('Deine Nachricht wurde korrekt übermittelt!', [], 'game');

            $this->addFlash( 'notice',  $message);
            return AjaxResponse::success( true, ['url' => $this->generateUrl('town_house', ['tab' => 'messages', 'subtab' => 'received'])] );
        }


    }

    /**
     * @Route("api/town/house/pm/{tid<\d+>}/view", name="home_view_thread_controller")
     * @param int $tid
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function pm_viewer_api(int $tid, EntityManagerInterface $em): Response {
        $user = $this->getUser();

        /** @var Citizen $citizen */
        $citizen = $user->getActiveCitizen();

        $thecrow = $em->getRepository(User::class)->find(66);

        /** @var PrivateMessageThread $thread */
        $thread = $em->getRepository(PrivateMessageThread::class)->find( $tid );
        if (!$thread) return new Response('');

        $valid = false;
        foreach ($thread->getMessages() as $message)
            if ($message->getRecipient() === $citizen)
                $valid = true;

        if(!$valid) return new Response('');

        $thread->setNew(false);

        $posts = $thread->getMessages();

        foreach ($posts as $message) {
            if($message->getRecipient() === $citizen) {
                $message->setNew(false);
                $em->persist($message);
            }
        }

        $em->persist($thread);
        $em->flush();
        $items = [];
        foreach ($posts as &$post) {
            if($post->getItems() !== null && count($post->getItems()) > 0) {
                $items[$post->getId()] = [];
                foreach ($post->getItems() as $proto_id) {
                    $items[$post->getId()][] = $em->getRepository(ItemPrototype::class)->find($proto_id);
                }
            }

            switch ($post->getTemplate()) {

                case PrivateMessage::TEMPLATE_CROW_COMPLAINT_ON:
                    $complaint = $this->entityManager->getRepository(Complaint::class)->find( $post->getForeignID() );
                    $thread->setTitle( $this->trans->trans('Anonyme Beschwerde', [], 'game') );
                    $post->setText( $this->prepareEmotes($post->getText()) . $this->trans->trans( 'Es wurde eine neue anonyme Beschwerde gegen dich eingelegt: "%reason%"', ['%reason%' => $this->trans->trans( $complaint ? $complaint->getReason() : '', [], 'game' )], 'game' ) );
                    break;
                case PrivateMessage::TEMPLATE_CROW_COMPLAINT_OFF:
                    $complaint = $this->entityManager->getRepository(Complaint::class)->find( $post->getForeignID() );
                    $thread->setTitle( $this->trans->trans('Beschwerde zurückgezogen', [], 'game') );
                    $post->setText( $this->prepareEmotes($post->getText()) . $this->trans->trans( 'Es gibt gute Nachrichten! Folgende Beschwerde wurde zurückgezogen: "%reason%"', ['%reason%' => $this->trans->trans( $complaint ? $complaint->getReason() : '', [], 'game' )], 'game' ) );
                    break;
                case PrivateMessage::TEMPLATE_CROW_TERROR:
                    $thread->setTitle( $this->trans->trans('Du bist vor Angst erstarrt!!', [], 'game') );
                    $post->setText( $this->prepareEmotes($post->getText()) . $this->trans->trans( 'Wir haben zwei Neuigkeiten für dich. Eine gute und eine schlechte. Zuerst die gute: Trotz ihrer hartnäckigen Versuche, ist es den %num% Zombie(s) nicht gelungen, dich aufzufressen. Du hast dich wacker geschlagen. Bravo! Die schlechte: Das Erlebnis war so schlimm, dass du in eine Angststarre verfallen bist. So etwas möchtest du nicht wieder erleben...', ['%num%' => $post->getForeignID()], 'game' ) );
                    break;
                case PrivateMessage::TEMPLATE_CROW_THEFT:
                    /** @var ItemPrototype $item */
                    $item = $this->entityManager->getRepository(ItemPrototype::class)->find( $post->getForeignID() );
                    $thread->setTitle( $this->trans->trans('Haltet den Dieb!', [], 'game') );

                    $img = "<img src='{$this->asset->getUrl('build/images/item/item_' . ($item ? $item->getIcon() : 'none') . '.gif')}' alt='' />";
                    $name = $this->trans->trans( $item ? $item->getLabel() : '', [], 'items' );
                    $post->setText( $this->prepareEmotes($post->getText()) . $this->trans->trans( 'Es scheint so, als ob ein anderer Bürger Gefallen an deinem Inventar gefunden hätte... Dir wurde folgendes gestohlen: %icon% %item%', ['%icon%' => $img, '%item%' => $name], 'game' ) );
                    break;
                default:
                    $post->setText($this->prepareEmotes($post->getText()));
            }

        }

        return $this->render( 'ajax/game/town/posts.html.twig', [
            'thread' => $thread,
            'posts' => $posts,
            'items' => $items,
            'thecrow' => $thecrow,
            'emotes' => $this->getEmotesByUser($user,true),
        ] );
    }

    /**
     * @Route("api/town/house/pm/{tid<\d+>}/archive/{action<\d+>}", name="home_archive_pm_controller")
     * @param int $tid
     * @param int $action
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function pm_archive_api(int $tid, int $action, EntityManagerInterface $em): Response {
        $user = $this->getUser();

        /** @var Citizen $citizen */
        if (!($citizen = $user->getActiveCitizen())) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var PrivateMessageThread $thread */
        $thread = $em->getRepository(PrivateMessageThread::class)->find( $tid );
        if (!$thread || !$thread->getSender() || ($thread->getRecipient()->getId() !== $citizen->getId() && $thread->getSender()->getId() !== $citizen->getId())) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $thread->setArchived($action !== 0);

        $em->persist($thread);
        $em->flush();

        return AjaxResponse::success();
    }

    /**
     * @Route("town/house/pm/{tid<\d+>}/editor", name="home_answer_post_editor_controller")
     * @param int $tid
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function home_answer_editor_post_api(int $tid, EntityManagerInterface $em): Response {
        $user = $this->getUser();

        $thread = $em->getRepository( PrivateMessageThread::class )->find( $tid );
        if ($thread === null) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        return $this->render( 'ajax/forum/editor.html.twig', [
            'fid' => null,
            'tid' => $tid,
            'pid' => null,
            'emotes' => $this->getEmotesByUser($user,true),
            'forum' => false,
            'type' => 'pm',
            'target_url' => 'town_house_send_pm_controller',
            'town_controls' => true,
        ] );
    }

    /**
     * @Route("town/house/pm/{type}/editor", name="home_new_post_editor_controller")
     * @param string $type
     * @return Response
     */
    public function home_new_editor_post_api(string $type): Response {
        $user = $this->getUser();

        $allowed_types = ['pm', 'global'];
        if(!in_array($type, $allowed_types)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        return $this->render( 'ajax/forum/editor.html.twig', [
            'fid' => null,
            'tid' => null,
            'pid' => null,
            'emotes' => $this->getEmotesByUser($user,true),
            'forum' => false,
            'type' => $type,
            'target_url' => 'town_house_send_pm_controller',
            'town_controls' => true,
        ] );
    }

    /**
     * @Route("admin/pm/{type}/editor", name="admin_pm_editor_controller")
     * @param string $type
     * @return Response
     */
    public function admin_pm_new_editor_post_api(string $type): Response {
        $user = $this->getUser();

        $allowed_types = ['pm', 'global'];
        if(!in_array($type, $allowed_types)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        return $this->render( 'ajax/forum/editor.html.twig', [
            'fid' => null,
            'tid' => null,
            'pid' => null,
            'emotes' => $this->getEmotesByUser($user,true),
            'forum' => false,
            'type' => $type,
            'target_url' => 'admin_send_pm_controller',
            'town_controls' => true,
        ] );
    }


    /**
     * @Route("api/admin/changelogs/editor", name="admin_new_changelog_editor_controller")
     * @return Response
     */
    public function admin_new_changelog_editor_controller(): Response {
        $user = $this->getUser();

        return $this->render( 'ajax/forum/editor.html.twig', [
            'fid' => null,
            'tid' => null,
            'pid' => null,
            'emotes' => $this->getEmotesByUser($user,true),
            'forum' => false,
            'type' => 'changelog',
            'target_url' => 'admin_changelog_new_changelog',
            'town_controls' => false
        ] );
    }

    /**
     * @Route("api/admin/sendpm", name="admin_send_pm_controller")
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @param TranslatorInterface $t
     * @return Response
     */
    public function admin_pm_api(EntityManagerInterface $em, JSONRequestParser $parser, TranslatorInterface $t): Response {
        $type      = $parser->get('type', "");
        $recipient = $parser->get('recipient', '');
        $title     = $parser->get('title', '');
        $content   = $parser->get('content', '');

        $allowed_types = ['pm', 'global'];

        if(!in_array($type, $allowed_types))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if(empty($recipient) || empty($title) || empty($content))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $sender = null;

        $recipients = [];

        if ($type === 'pm') {

            $recipient = $em->getRepository(Citizen::class)->find($recipient);
            if ($recipient)
                $recipients[] = $recipient;

        } else {

            $town = $em->getRepository( Town::class )->find( $recipient );
            if ($town)
                foreach ($town->getCitizens() as $citizen)
                    $recipients[] = $citizen;

        }

        $success = 0;
        foreach ($recipients as $recipient) {
            if(!$recipient->getAlive()) continue;

            $thread = new PrivateMessageThread();

            $thread
                ->setTitle($title)
                ->setLocked(false)
                ->setLastMessage(new DateTime('now'))
                ->setRecipient($recipient);

            $post = new PrivateMessage();
            $post->setDate(new DateTime('now'))
                ->setText($content)
                ->setPrivateMessageThread($thread)
                ->setNew(true)
                ->setRecipient($recipient);

            $tx_len = 0;
            if (!$this->preparePost($this->getUser(),null,$post,$tx_len, $recipient->getTown()))
                return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

            $thread
                ->setLastMessage($post->getDate())
                ->addMessage($post);

            $success++;
            $em->persist($thread);
            $em->persist($post);
        }

        $em->flush();

        if ($success === 0) {
            return AjaxResponse::error( ErrorHelper::ErrorInternalError );
        } else {
            // Show confirmation
            $message = $t->trans('Deine Nachricht wurde korrekt übermittelt!', [], 'game');

            $this->addFlash( 'notice',  $message);
            return AjaxResponse::success( true, ['url' =>
                $type === 'pm'
                    ? $this->generateUrl('admin_users_citizen_view', ['id' => $recipients[0]->getUser()->getId()])
                    : $this->generateUrl('admin_town_explorer', ['id' => $parser->get('recipient', '')])
            ] );
        }


    }

    /**
     * @Route("api/admin/changelogs/new_changelog", name="admin_changelog_new_changelog")
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function create_changelog_api(EntityManagerInterface $em, JSONRequestParser $parser): Response {
        $title     = $parser->get('title', '');
        $content   = $parser->get('content', '');
        $version   = $parser->get('version', '');
        $lang      = $parser->get('lang', 'de');

        $author    = $this->getUser();

        if(empty($title) || empty($content) || empty($version)) {
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        }

        $change = new Changelog();
        $change->setTitle($title)->setText($content)->setVersion($version)->setLang($lang)->setAuthor($author)->setDate(new DateTime());

        $tx_len = 0;
        if (!$this->preparePost($author,null,$change,$tx_len))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $em->persist($change);
        $em->flush();

        return AjaxResponse::success( true, ['url' => $this->generateUrl('admin_changelogs')] );
    }
}
