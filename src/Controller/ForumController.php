<?php

namespace App\Controller;

use App\Entity\Forum;
use App\Entity\Post;
use App\Entity\Thread;
use App\Entity\ThreadReadMarker;
use App\Entity\User;
use App\Exception\DynamicAjaxResetException;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\UserFactory;
use App\Response\AjaxResponse;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
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

    private function default_forum_renderer(int $fid, int $tid, EntityManagerInterface $em, JSONRequestParser $parser): Response {
        $num_per_page = 20;

        $forums = $em->getRepository(Forum::class)->findForumsForUser($this->getUser(), $fid);
        if (count($forums) !== 1) return $this->redirect($this->generateUrl('forum_list'));

        /** @var User $user */
        $user = $this->getUser();

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

        return $this->render( 'ajax/forum/view.html.twig', [
            'forum' => $forums[0],
            'threads' => $thread_list,
            'select' => $tid,
            'pages' => $pages,
            'current_page' => $page
        ] );
    }

    /**
     * @Route("jx/forum/{id}", name="forum_view")
     * @param int $id
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function forum(int $id, EntityManagerInterface $em, JSONRequestParser $p): Response
    {
        return $this->default_forum_renderer($id,-1,$em, $p);
    }

    /**
     * @Route("jx/forum/{fid}/{tid}", name="forum_thread_view")
     * @param int $fid
     * @param int $tid
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function forum_thread(int $fid, int $tid, EntityManagerInterface $em, JSONRequestParser $p): Response
    {
        return $this->default_forum_renderer($fid,$tid,$em,$p);
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

    private function preparePost(User $user, Forum $forum, Thread $thread, Post &$post) {
        if ($forum->getTown()) {

            foreach ( $forum->getTown()->getCitizens() as $citizen )
                if ($citizen->getUser()->getId() === $user->getId()) {
                    if ($citizen->getZone()) $note = "[{$citizen->getZone()->getX()}/{$citizen->getZone()->getY()}]";
                    else $post->setNote("[{$citizen->getTown()->getName()}]");
                }
        }
    }

    /**
     * @Route("api/forum/{id}/post", name="forum_new_thread_controller")
     * @param int $id
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function new_thread_api(int $id, JSONRequestParser $parser, EntityManagerInterface $em): Response {
        $forums = $em->getRepository(Forum::class)->findForumsForUser($this->getUser(), $id);
        if (count($forums) !== 1) return AjaxResponse::error( self::ErrorForumNotFound );

        /** @var Forum $forum */
        $forum = $forums[0];

        /** @var User $user */
        $user = $this->getUser();

        if (!$parser->has_all(['title','text'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $title = $parser->trimmed('title');
        $text  = $parser->trimmed('text');

        if (mb_strlen($title) < 3 || mb_strlen($title) > 64)   return AjaxResponse::error( self::ErrorPostTitleLength );
        if (mb_strlen($text) < 10 || mb_strlen($text) > 16384) return AjaxResponse::error( self::ErrorPostTextLength );

        $thread = (new Thread())->setTitle( $title )->setOwner($user);

        $post = (new Post())
            ->setOwner( $user )
            ->setText( $text )
            ->setDate( new DateTime('now') );
        $this->preparePost($user,$forum,$thread,$post);
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
     * @Route("api/forum/{fid}/{tid}/post", name="forum_new_post_controller")
     * @param int $fid
     * @param int $tid
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function new_post_api(int $fid, int $tid, JSONRequestParser $parser, EntityManagerInterface $em): Response {
        /** @var User $user */
        $user = $this->getUser();

        $forums = $em->getRepository(Forum::class)->findForumsForUser($user, $fid);
        if (count($forums) !== 1) return AjaxResponse::error( self::ErrorForumNotFound );

        $thread = $em->getRepository(Thread::class)->find( $tid );
        if (!$thread || $thread->getForum()->getId() !== $fid) return AjaxResponse::error( self::ErrorForumNotFound );

        /** @var Forum $forum */
        $forum = $forums[0];


        if (!$parser->has_all(['text'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $text  = $parser->trimmed('text');

        if (mb_strlen($text) < 10 || mb_strlen($text) > 16384) return AjaxResponse::error( self::ErrorPostTextLength );

        $post = (new Post())
            ->setOwner( $user )
            ->setText( $text )
            ->setDate( new DateTime('now') );
        $this->preparePost($user,$forum,$thread,$post);
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
     * @Route("api/forum/{tid}/{fid}/view", name="forum_viewer_controller")
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

        $pages = floor(max(0,$em->getRepository(Post::class)->countByThread($thread)-1) / $num_per_page) + 1;
        if ($parser->has('page'))
            $page = min(max(1,$parser->get('page', 1)), $pages);
        elseif (!$marker->getPost()) $page = 1;
        else $page = min($pages,1 + floor((1+$em->getRepository(Post::class)->getOffsetOfPostByThread( $thread, $marker->getPost() )) / $num_per_page));

        $posts = $em->getRepository(Post::class)->findByThread($thread, $num_per_page, ($page-1)*$num_per_page);
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
            'pages' => floor(max(0,$em->getRepository(Post::class)->countByThread($thread)-1) / $num_per_page) + 1
        ] );
    }

    /**
     * @Route("api/forum/{id}/editor", name="forum_thread_editor_controller")
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
        ] );
    }

    /**
     * @Route("api/forum/{fid}/{tid}/editor", name="forum_post_editor_controller")
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
}
