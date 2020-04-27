<?php

namespace App\Controller;

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
use App\Service\UserFactory;
use App\Response\AjaxResponse;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use DOMNode;
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
        'ul' => [ 'class' ],
        'ol' => [ 'class' ],
        'li' => [],
        'p'  => [ 'class' ],
        'div' => [ 'class' ],
        'a' => [ 'href', 'title' ],
        'figure' => [ 'style' ],
    ];

    private const HTML_ALLOWED_ADMIN = [
        'img' => [ 'alt', 'src', 'title'],
    ];

    private function getAllowedHTML(): array {
        $r = self::HTML_ALLOWED;
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getIsAdmin())
            $r = array_merge( $r, self::HTML_ALLOWED_ADMIN );

        return $r;
    }

    private function htmlValidator( array $allowedNodes, DOMNode $node, int &$text_length, int $depth = 0 ): bool {
        if ($depth > 32) return false;
        if ($node->nodeType === XML_ELEMENT_NODE) {

            // Element not allowed.
            if (!in_array($node->nodeName, array_keys($allowedNodes))) {
                $node->parentNode->removeChild( $node );
                return true;
            }

            // Attributes not allowed.
            $remove_attribs = [];
            for ($i = 0; $i < $node->attributes->length; $i++)
                if (!in_array($node->attributes->item($i)->nodeName, $allowedNodes[$node->nodeName]))
                    $remove_attribs[] = $node->attributes->item($i)->nodeName;
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
        $dom->loadHTML( '<?xml encoding="utf-8" ?><body>' . $post->getText() . '</body>' );
        $body = $dom->getElementsByTagName('body');
        if (!$body || $body->length > 1) return false;

        if (!$this->htmlValidator($this->getAllowedHTML(), $body->item(0),$tx_len))
            return false;

        $tmp_str = "";
        if ($body_item = $body->item(0)) {
            foreach ($body_item->childNodes as $child) {
                $tmp_str .= $dom->saveHTML($child);
            }
        }

        if (mb_strlen(trim($tmp_str)) == 0) {
            return false;
        }
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

        if (mb_strlen($title) < 3 || mb_strlen($title) > 64)   return AjaxResponse::error( self::ErrorPostTitleLength );

        $as_crow = $parser->get('as_crow');
        if (isset($as_crow)){
            if ($as_crow) {
                $thread = $admh->crowPost($user->getId(), $forum, null, $text, $title);
                if (isset($thread))
                    return AjaxResponse::success( true, ['url' => $this->generateUrl('forum_thread_view', ['fid' => $id, 'tid' => $thread->getId()])] );
                else return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
            }
        }

        if (mb_strlen($text) < 10 || mb_strlen($text) > 16384) return AjaxResponse::error( self::ErrorPostTextLength );

        $thread = (new Thread())->setTitle( $title )->setOwner($user);

        $post = (new Post())
            ->setOwner( $user )
            ->setText( $text )
            ->setDate( new DateTime('now') );
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

        $forums = $em->getRepository(Forum::class)->findForumsForUser($user, $fid);
        if (count($forums) !== 1) return AjaxResponse::error( self::ErrorForumNotFound );

        $thread = $em->getRepository(Thread::class)->find( $tid );
        if (!$thread || $thread->getForum()->getId() !== $fid) return AjaxResponse::error( self::ErrorForumNotFound );
        if ($thread->getLocked())
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );
        /** @var Forum $forum */
        $forum = $forums[0];


        if (!$parser->has_all(['text'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $text  = $parser->get('text');
        $as_crow = $parser->get('as_crow');
        if (isset($as_crow)){
            if ($as_crow) {
                if ($admh->crowPost($user->getId(), $forum, $thread, $text, null))
                    return AjaxResponse::success( true, ['url' => $this->generateUrl('forum_thread_view', ['fid' => $fid, 'tid' => $tid])] );
                else return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
            }
        }

        if (mb_strlen(strip_tags($text)) < 10 || mb_strlen(strip_tags($text)) > 16384) return AjaxResponse::error( self::ErrorPostTextLength );

        $post = (new Post())
            ->setOwner( $user )
            ->setText( $text )
            ->setDate( new DateTime('now') );
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

        $forums = $em->getRepository(Forum::class)->findForumsForUser($this->getUser(), $fid);
        if (count($forums) !== 1) return new Response('');

        $thread = $em->getRepository(Thread::class)->find( $tid );
        if (!$thread || $thread->getForum()->getId() !== $fid) return new Response('');

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
            $marker->setPost( $posts[array_key_last($posts)] );
            try {
                $em->persist($marker);
                $em->flush();
            } catch (Exception $e) {}
        }

        return $this->render( 'ajax/forum/posts.html.twig', [
            'posts' => $posts,
            'locked' => $thread->getLocked(),
            'fid' => $fid,
            'tid' => $tid,
            'current_page' => $page,
            'pages' => $pages
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
        $forums = $em->getRepository(Forum::class)->findForumsForUser($this->getUser(), $fid);
        if (count($forums) !== 1) return new Response('');

        $thread = $em->getRepository( Thread::class )->find( $tid );
        if ($thread === null || $thread->getForum()->getId() !== $fid) return new Response('');

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
        
        /** @var User $user */
        $user = $this->getUser();
        $postId = $parser->get('postId');
        
        if ($admh->hidePost($user->getId(), $postId, "abc" ))
            return AjaxResponse::success( true, ['url' => $this->generateUrl('forum_thread_view', ['fid' => $fid, 'tid' => $tid])] );

            
        return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
    }
}
