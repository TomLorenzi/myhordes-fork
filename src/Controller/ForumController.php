<?php

namespace App\Controller;

use App\Entity\AdminReport;
use App\Entity\Citizen;
use App\Entity\Forum;
use App\Entity\Post;
use App\Entity\Thread;
use App\Entity\ThreadReadMarker;
use App\Entity\User;
use App\Exception\DynamicAjaxResetException;
use App\Service\CitizenHandler;
use App\Service\ErrorHelper;
use App\Service\AdminActionHandler;
use App\Service\JSONRequestParser;
use App\Service\RandomGenerator;
use App\Service\UserFactory;
use App\Response\AjaxResponse;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @IsGranted("ROLE_USER")
 */
class ForumController extends AbstractController
{
    const ErrorForumNotFound    = ErrorHelper::BaseForumErrors + 1;
    const ErrorPostTextLength   = ErrorHelper::BaseForumErrors + 2;
    const ErrorPostTitleLength  = ErrorHelper::BaseForumErrors + 3;

    private $rand;
    private $trans;

    public function __construct(RandomGenerator $r, TranslatorInterface $t)
    {
        $this->rand = $r;
        $this->trans = $t;
    }

    private function default_forum_renderer(int $fid, int $tid, EntityManagerInterface $em, JSONRequestParser $parser, CitizenHandler $ch): Response {
        $num_per_page = 20;

        /** @var User $user */
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

        $thread_list = [];
        foreach ($threads as $thread) {
            /** @var Thread $thread */
            /** @var ThreadReadMarker $marker */
            $marker = $em->getRepository(ThreadReadMarker::class)->findByThreadAndUser($user, $thread);
            if ($marker && $thread->getLastPost() <= $marker->getPost()->getDate()) $thread_list[] = [$thread,true];
            else $thread_list[] = [$thread,false];
        }

        $pinned_threads = $em->getRepository(Thread::class)->findPinnedByForum($forums[0], $num_per_page, ($page-1)*$num_per_page);

        $pinned_thread_list = [];
        foreach ($pinned_threads as $thread) {
            /** @var Thread $thread */
            /** @var ThreadReadMarker $marker */
            $marker = $em->getRepository(ThreadReadMarker::class)->findByThreadAndUser($user, $thread);
            if ($marker && $thread->getLastPost() <= $marker->getPost()->getDate()) $pinned_thread_list[] = [$thread,true];
            else $pinned_thread_list[] = [$thread,false];
        }

        return $this->render( 'ajax/forum/view.html.twig', [
            'forum' => $forums[0],
            'threads' => $thread_list,
            'pinned_threads' => $pinned_thread_list,
            'select' => $tid,
            'pages' => $pages,
            'current_page' => $page
        ] );
    }

    /**
     * @Route("jx/forum/town", name="forum_town_redirect")
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function forum_redirector(EntityManagerInterface $em): Response
    {
        /** @var User $user */
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
        return $this->default_forum_renderer($id,-1,$em, $p, $ch);
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
        return $this->default_forum_renderer($fid,$tid,$em,$p,$ch);
    }

    /**
     * @Route("jx/forum", name="forum_list")
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function forums(EntityManagerInterface $em): Response
    {
        $forum_list = $em->getRepository(Forum::class)->findForumsForUser( $this->getUser() );
        return $this->render( 'ajax/forum/list.html.twig', [
            'forums' => $forum_list
        ] );
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
        'q' => [],
        'blockquote' => [],
        'hr' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'p'  => [],
        'div' => [ 'class' ],
        'a' => [ 'href', 'title' ],
        'figure' => [ 'style' ],
    ];

    private const HTML_ALLOWED_ADMIN = [
        'img' => [ 'alt', 'src', 'title'],
    ];

    private const HTML_ATTRIB_ALLOWED = [
        'div.class' => [
            'glory', 'spoiler',
            'dice-4', 'dice-6', 'dice-8', 'dice-10', 'dice-12', 'dice-20', 'dice-100',
            'letter-a', 'letter-v', 'letter-c',
            'rps', 'coin', 'card'
        ]
    ];

    private function getAllowedHTML(): array {
        $r = self::HTML_ALLOWED;
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getIsAdmin())
            $r = array_merge( $r, self::HTML_ALLOWED_ADMIN );

        return ['nodes' => $r, 'attribs' => self::HTML_ATTRIB_ALLOWED];
    }

    private function htmlValidator( array $allowedNodes, DOMNode $node, int &$text_length, int $depth = 0 ): bool {
        if ($depth > 32) return false;

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

    private function preparePost(User $user, Forum $forum, Thread $thread, Post &$post, int &$tx_len): bool {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML( '<?xml encoding="utf-8" ?>' . $post->getText() );
        $body = $dom->getElementsByTagName('body');
        if (!$body || $body->length > 1) return false;

        if (!$this->htmlValidator($this->getAllowedHTML(), $body->item(0),$tx_len))
            return false;

        $handlers = [
            '//div[@class=\'dice-4\']'   => function (DOMNode $d) { $d->nodeValue = mt_rand(1,4); },
            '//div[@class=\'dice-6\']'   => function (DOMNode $d) { $d->nodeValue = mt_rand(1,6); },
            '//div[@class=\'dice-8\']'   => function (DOMNode $d) { $d->nodeValue = mt_rand(1,8); },
            '//div[@class=\'dice-10\']'  => function (DOMNode $d) { $d->nodeValue = mt_rand(1,10); },
            '//div[@class=\'dice-12\']'  => function (DOMNode $d) { $d->nodeValue = mt_rand(1,12); },
            '//div[@class=\'dice-20\']'  => function (DOMNode $d) { $d->nodeValue = mt_rand(1,20); },
            '//div[@class=\'dice-100\']' => function (DOMNode $d) { $d->nodeValue = mt_rand(1,100); },
            '//div[@class=\'letter-a\']' => function (DOMNode $d) { $l = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'; $d->nodeValue = $l[mt_rand(0,strlen($l)-1)]; },
            '//div[@class=\'letter-c\']' => function (DOMNode $d) { $l = 'BCDFGHJKLMNPQRSTVWXZ'; $d->nodeValue = $l[mt_rand(0,strlen($l)-1)]; },
            '//div[@class=\'letter-v\']' => function (DOMNode $d) { $l = 'AEIOUY'; $d->nodeValue = $l[mt_rand(0,strlen($l)-1)]; },
            '//div[@class=\'rps\']'      => function (DOMNode $d) { $d->nodeValue = $this->rand->pick([$this->trans->trans('Schere',[],'global'),$this->trans->trans('Stein',[],'global'),$this->trans->trans('Papier',[],'global')]); },
            '//div[@class=\'coin\']'     => function (DOMNode $d) { $d->nodeValue = $this->rand->pick([$this->trans->trans('Kopf',[],'global'),$this->trans->trans('Zahl',[],'global')]); },
            '//div[@class=\'card\']'     => function (DOMNode $d) {
                $s_color = $this->rand->pick([$this->trans->trans('Kreuz',[],'items'),$this->trans->trans('Pik',[],'items'),$this->trans->trans('Herz',[],'items'),$this->trans->trans('Karo',[],'items')]);
                $value = mt_rand(1,12);
                $s_value = $value < 9 ? ('' . ($value+2)) : [$this->trans->trans('Bube',[],'items'),$this->trans->trans('Dame',[],'items'),$this->trans->trans('König',[],'items'),$this->trans->trans('Ass',[],'items')][$value-9];
                $d->nodeValue = $this->trans->trans('{color} {value}', ['{color}' => $s_color, '{value}' => $s_value], 'global');
            },
        ];

        foreach ($handlers as $query => $handler)
            foreach ( (new DOMXPath($dom))->query($query, $body->item(0)) as $node )
                $handler($node);

        $tmp_str = "";
        foreach ($body->item(0)->childNodes as $child)
            $tmp_str .= $dom->saveHTML($child);

        $post->setText( $tmp_str );
        if ($forum->getTown()) {

            foreach ( $forum->getTown()->getCitizens() as $citizen )
                if ($citizen->getUser()->getId() === $user->getId()) {
                    if ($citizen->getZone()) $post->setNote("[{$citizen->getZone()->getX()}/{$citizen->getZone()->getY()}]");
                    else $post->setNote("[{$citizen->getTown()->getName()}]");
                }
        }

        return true;
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

        /** @var User $user */
        $user = $this->getUser();
        if ($user->getIsBanned())
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );
            
        if (!$parser->has_all(['title','text'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $title = $parser->trimmed('title');
        $text  = $parser->trimmed('text');

        if ($user->getIsAdmin()) {
            $type  = $parser->get('type');
        }
        else {
            $type = "USER";
        }

        if (mb_strlen($title) < 3 || mb_strlen($title) > 64)   return AjaxResponse::error( self::ErrorPostTitleLength );


        if ($type === "CROW") {
            $thread = $admh->crowPost($user->getId(), $forum, null, $text, $title);
            if (isset($thread))
                return AjaxResponse::success( true, ['url' => $this->generateUrl('forum_thread_view', ['fid' => $id, 'tid' => $thread->getId()])] );
            else return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        if ($type !== "DEV") {
            $type = "USER";
        }

        if (mb_strlen($text) < 10 || mb_strlen($text) > 16384) return AjaxResponse::error( self::ErrorPostTextLength );

        $thread = (new Thread())->setTitle( $title )->setOwner($user);

        $post = (new Post())
            ->setOwner( $user )
            ->setText( $text )
            ->setDate( new DateTime('now') )
            ->setType($type);

        $tx_len = 0;
        if (!$this->preparePost($user,$forum,$thread,$post,$tx_len))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        if ($tx_len < 10) return AjaxResponse::error( self::ErrorPostTextLength );
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
     * @return Response
     */
    public function new_post_api(int $fid, int $tid, JSONRequestParser $parser, EntityManagerInterface $em, AdminActionHandler $admh): Response {
        /** @var User $user */
        $user = $this->getUser();
        if ($user->getIsBanned())
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );
          

        $thread = $em->getRepository(Thread::class)->find( $tid );
        if (!$thread || $thread->getForum()->getId() !== $fid) return AjaxResponse::error( self::ErrorForumNotFound );
        if ($thread->getLocked())
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );
        
        $forums = $em->getRepository(Forum::class)->findForumsForUser($user, $fid);
        if (count($forums) !== 1){
            if (!($user->getIsAdmin() && $thread->hasReportedPosts())){
                return AjaxResponse::error( self::ErrorForumNotFound );
            }      
        } 
        
        /** @var Forum $forum */
        $forum = $thread->getForum();


        if (!$parser->has_all(['text'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $text = $parser->get('text');
        
        if ($user->getIsAdmin()) {
            $type  = $parser->get('type');
        }
        else {
            $type = "USER";
        }
        
        if ($type === "CROW"){
            if ($admh->crowPost($user->getId(), $forum, $thread, $text, null))
                return AjaxResponse::success( true, ['url' => $this->generateUrl('forum_thread_view', ['fid' => $fid, 'tid' => $tid])] );
            else return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }
        if ($type !== "DEV") {
            $type = "USER";
        }

        if (mb_strlen(strip_tags($text)) < 10 || mb_strlen(strip_tags($text)) > 16384) return AjaxResponse::error( self::ErrorPostTextLength );

        $post = (new Post())
            ->setOwner( $user )
            ->setText( $text )
            ->setDate( new DateTime('now') )
            ->setType($type);

        $tx_len = 0;
        if (!$this->preparePost($user,$forum,$thread,$post,$tx_len))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        //if ($tx_len < 10) return AjaxResponse::error( self::ErrorPostTextLength );
        $thread->addPost($post)->setLastPost( $post->getDate() );

        try {
            $em->persist($thread);
            $em->persist($forum);
            $em->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success( true, ['url' => $this->generateUrl('forum_thread_view', ['fid' => $fid, 'tid' => $tid])] );
    }

    /**
     * @Route("api/forum/{tid<\d+>}/{fid<\d+>}/view", name="forum_viewer_controller")
     * @param int $fid
     * @param int $tid
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function viewer_api(int $fid, int $tid, EntityManagerInterface $em, JSONRequestParser $parser): Response {
        $num_per_page = 10;
        /** @var User $user */
        $user = $this->getUser();

        /** @var Thread $thread */
        $thread = $em->getRepository(Thread::class)->find( $tid );
        if (!$thread || $thread->getForum()->getId() !== $fid) return new Response('');

        $forums = $em->getRepository(Forum::class)->findForumsForUser($this->getUser(), $fid);
        if (count($forums) !== 1){
            if (!($user->getIsAdmin() && $thread->hasReportedPosts())){
                return new Response('');
            }      
        } 

        $marker = $em->getRepository(ThreadReadMarker::class)->findByThreadAndUser( $user, $thread );
        if (!$marker) $marker = (new ThreadReadMarker())->setUser($user)->setThread($thread);
        
        if ($user->getIsAdmin())
            $pages = floor(max(0,$em->getRepository(Post::class)->countByThread($thread)-1) / $num_per_page) + 1;
        else
            $pages = floor(max(0,$em->getRepository(Post::class)->countUnhiddenByThread($thread)-1) / $num_per_page) + 1;

        if ($parser->has('page'))
            $page = min(max(1,$parser->get('page', 1)), $pages);
        elseif (!$marker->getPost()) $page = 1;
        else $page = min($pages,1 + floor((1+$em->getRepository(Post::class)->getOffsetOfPostByThread( $thread, $marker->getPost() )) / $num_per_page));

        if ($user->getIsAdmin())
            $posts = $em->getRepository(Post::class)->findByThread($thread, $num_per_page, ($page-1)*$num_per_page);
        else
            $posts = $em->getRepository(Post::class)->findUnhiddenByThread($thread, $num_per_page, ($page-1)*$num_per_page);

        if (!empty($posts)) {
            /** @var Post $read_post */
            $read_post = $posts[array_key_last($posts)];
            /** @var Post $last_read */
            $last_read = $marker->getPost();
            if ($last_read && $read_post->getId() > $last_read->getId()) {
                $marker->setPost($read_post);
                try {
                    $em->persist($marker);
                    $em->flush();
                } catch (Exception $e) {
                }
            }
        }

        return $this->render( 'ajax/forum/posts.html.twig', [
            'posts' => $posts,
            'locked' => $thread->getLocked(),
            'pinned' => $thread->getPinned(),
            'fid' => $fid,
            'tid' => $tid,
            'current_page' => $page,
            'pages' => $pages,
        ] );
    }

    /**
     * @Route("api/forum/{pid<\d+>}/jump", name="forum_viewer_jump_post_controller")
     * @param int $pid
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function jumpToPost_api(int $pid, EntityManagerInterface $em): Response {
        $num_per_page = 10;
        /** @var User $user */
        $user = $this->getUser();

        $jumpPost = $em->getRepository(Post::class)->find( $pid );

        $thread = $jumpPost->getThread();
        if (!$thread) return new Response('');

        $forum = $thread->getForum();

        $forums = $em->getRepository(Forum::class)->findForumsForUser($this->getUser(), $forum->getId());
        if (count($forums) !== 1){
            if (!($user->getIsAdmin() && $thread->hasReportedPosts())){
                return new Response('');
            }      
        } 
        
        if ($user->getIsAdmin())
            $pages = floor(max(0,$em->getRepository(Post::class)->countByThread($thread)-1) / $num_per_page) + 1;
        else
            $pages = floor(max(0,$em->getRepository(Post::class)->countUnhiddenByThread($thread)-1) / $num_per_page) + 1;

        $page = min($pages,1 + floor((1+$em->getRepository(Post::class)->getOffsetOfPostByThread( $thread, $jumpPost )) / $num_per_page));

        if ($user->getIsAdmin())
            $posts = $em->getRepository(Post::class)->findByThread($thread, $num_per_page, ($page-1)*$num_per_page);
        else
            $posts = $em->getRepository(Post::class)->findUnhiddenByThread($thread, $num_per_page, ($page-1)*$num_per_page);

        return $this->render( 'ajax/forum/posts.html.twig', [
            'posts' => $posts,
            'locked' => $thread->getLocked(),
            'pinned' => $thread->getPinned(),
            'fid' => $forum->getId(),
            'tid' => $thread->getId(),
            'current_page' => $page,
            'pages' => $pages,
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
        $forums = $em->getRepository(Forum::class)->findForumsForUser($this->getUser(), $id);
        if (count($forums) !== 1) return new Response('');
        return $this->render( 'ajax/forum/editor.html.twig', [
            'fid' => $id,
            'tid' => null,
            'pid' => null,
            'username' => $this->getUser()->getUsername(),
        ] );
    }

    /**
     * @Route("api/forum/{fid<\d+>}/{tid<\d+>}/editor", name="forum_post_editor_controller")
     * @param int $fid
     * @param int $tid
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function editor_post_api(int $fid, int $tid, EntityManagerInterface $em): Response {
        $user = $this->getUser();

        $thread = $em->getRepository( Thread::class )->find( $tid );
        if ($thread === null || $thread->getForum()->getId() !== $fid) return new Response('');

        $forums = $em->getRepository(Forum::class)->findForumsForUser($user, $fid);
        if (count($forums) !== 1){
            if (!($user->getIsAdmin() && $thread->hasReportedPosts())){
                return new Response('');
            }      
        } 

        return $this->render( 'ajax/forum/editor.html.twig', [
            'fid' => $fid,
            'tid' => $tid,
            'pid' => null,
        ] );
    }

     /**
     * @Route("api/forum/{fid<\d+>}/{tid<\d+>}/lock", name="forum_thread_lock_controller")
     * @param int $fid
     * @param int $tid
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function lock_thread_api(int $fid, int $tid, EntityManagerInterface $em, JSONRequestParser $parser, CitizenHandler $ch, AdminActionHandler $admh): Response {
        $admh->lockThread($this->getUser()->getId(), $fid, $tid);
        return $this->default_forum_renderer($fid, $tid, $em, $parser, $ch);
    }

     /**
     * @Route("api/forum/{fid<\d+>}/{tid<\d+>}/unlock", name="forum_thread_unlock_controller")
     * @param int $fid
     * @param int $tid
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function unlock_thread_api(int $fid, int $tid, EntityManagerInterface $em, JSONRequestParser $parser, CitizenHandler $ch, AdminActionHandler $admh): Response {
        $admh->unlockThread($this->getUser()->getId(), $fid, $tid);
        return $this->default_forum_renderer($fid, $tid, $em, $parser, $ch);

    }

    /**
     * @Route("api/forum/{fid<\d+>}/{tid<\d+>}/pin", name="forum_thread_pin_controller")
     * @param int $fid
     * @param int $tid
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function pin_thread_api(int $fid, int $tid, EntityManagerInterface $em, JSONRequestParser $parser, CitizenHandler $ch, AdminActionHandler $admh): Response {
        $admh->pinThread($this->getUser()->getId(), $fid, $tid);
        return $this->default_forum_renderer($fid, $tid, $em, $parser, $ch);
    }

     /**
     * @Route("api/forum/{fid<\d+>}/{tid<\d+>}/unpin", name="forum_thread_unpin_controller")
     * @param int $fid
     * @param int $tid
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function unpin_thread_api(int $fid, int $tid, EntityManagerInterface $em, JSONRequestParser $parser, CitizenHandler $ch, AdminActionHandler $admh): Response {
        $admh->unpinThread($this->getUser()->getId(), $fid, $tid);
        return $this->default_forum_renderer($fid, $tid, $em, $parser, $ch);

    }

    /**
     * @Route("api/forum/{fid<\d+>}/{tid<\d+>}/post/delete", name="forum_delete_post_controller")
     * @param int $fid
     * @param int $tid
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function delete_post_api(int $fid, int $tid, JSONRequestParser $parser, AdminActionHandler $admh): Response {
        if (!$parser->has('postId')){
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        if ($parser->has('reason'))
            $reason = $parser->get('reason');     
        else 
            $reason = "";
        
        /** @var User $user */
        $user = $this->getUser();
        $postId = $parser->get('postId');
        
        if ($admh->hidePost($user->getId(), $postId, $reason ))
            return AjaxResponse::success( true, ['url' => $this->generateUrl('forum_thread_view', ['fid' => $fid, 'tid' => $tid])] );

            
        return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
    }

    /**
     * @Route("api/forum/{fid<\d+>}/{tid<\d+>}/post/report", name="forum_report_post_controller")
     * @param int $fid
     * @param int $tid
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function report_post_api(int $fid, int $tid, JSONRequestParser $parser, AdminActionHandler $admh, EntityManagerInterface $em, TranslatorInterface $ti): Response {
        if (!$parser->has('postId')){
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }
        
        /** @var User $user */
        $user = $this->getUser();
        $postId = $parser->get('postId');

        $post = $em->getRepository( Post::class )->find( $postId );
        $targetUser = $post->getOwner();
        if ($targetUser->getUsername() === "Der Rabe" ) {
            $message = $ti->trans('Das ist keine gute Idee, das ist dir doch wohl klar!', [], 'game');
            $this->addFlash('notice', $message);
            return AjaxResponse::success( true, ['url' => $this->generateUrl('forum_thread_view', ['fid' => $fid, 'tid' => $tid])] );
        }

        $reports = $post->getAdminReports();
        foreach ($reports as $report) {
            if ($report->getSourceUser() == $user) {
                return AjaxResponse::success( true, ['url' => $this->generateUrl('forum_thread_view', ['fid' => $fid, 'tid' => $tid])] );
            }
        }

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
            return AjaxResponse::success( true, ['url' => $this->generateUrl('forum_thread_view', ['fid' => $fid, 'tid' => $tid])] );
    }
}
