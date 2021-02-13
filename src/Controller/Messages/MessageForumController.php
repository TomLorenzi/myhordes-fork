<?php

namespace App\Controller\Messages;

use App\Entity\AdminDeletion;
use App\Entity\AdminReport;
use App\Entity\Citizen;
use App\Entity\Forum;
use App\Entity\ForumModerationSnippet;
use App\Entity\ForumThreadSubscription;
use App\Entity\ForumUsagePermissions;
use App\Entity\Post;
use App\Entity\Thread;
use App\Entity\ThreadReadMarker;
use App\Entity\User;
use App\Response\AjaxResponse;
use App\Service\CitizenHandler;
use App\Service\CrowService;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\PictoHandler;
use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @IsGranted("ROLE_USER")
 * @method User getUser
 */
class MessageForumController extends MessageController
{

    private function default_forum_renderer(int $fid, int $tid, int $pid, EntityManagerInterface $em, JSONRequestParser $parser, CitizenHandler $ch): Response {
        $num_per_page = 20;

        $user = $this->getUser();

        /** @var Forum $forum */
        $forum = $em->getRepository(Forum::class)->find($fid);
        $permissions = $this->perm->getEffectivePermissions( $user, $forum );

        if (!$forum || !$this->perm->isAnyPermitted($permissions, [ ForumUsagePermissions::PermissionModerate, ForumUsagePermissions::PermissionListThreads, ForumUsagePermissions::PermissionReadThreads ]) )
            return $this->redirect($this->generateUrl('forum_list'));

        $sel_post = $sel_thread = null;
        if ($pid > 0) $sel_post = $em->getRepository(Post::class)->find($pid);
        if ($tid > 0) $sel_thread = $em->getRepository(Thread::class)->find($tid);

        if (($pid > 0 && !$sel_post) || ($tid > 0 && !$sel_thread) || ($sel_thread && $sel_thread->getForum() !== $forum) || ($sel_post && $sel_thread && $sel_post->getThread() !== $sel_thread) )
            return $this->redirect($this->generateUrl('forum_list'));

        // Set the activity status
        if ($forum->getTown() && $user->getActiveCitizen() && $user->getActiveCitizen()->getTown() === $forum->getTown()) {
            $c = $user->getActiveCitizen();
            if ($c) $ch->inflictStatus($c, 'tg_chk_forum');
            $em->persist( $c );
            $em->flush();
        }

        $show_hidden_threads = $this->perm->isPermitted( $permissions, ForumUsagePermissions::PermissionModerate );

        if ( $this->perm->isPermitted( $permissions, ForumUsagePermissions::PermissionListThreads ) ) {
            $pages = floor(max(0,$em->getRepository(Thread::class)->countByForum($forum, $show_hidden_threads, false)-1) / $num_per_page) + 1;

            if ($sel_thread && !$sel_thread->getPinned())
                $page = 1 + floor(($em->getRepository(Thread::class)->countByForum($forum, $show_hidden_threads, false, $sel_thread)) / $num_per_page);
            elseif ($parser->has('page'))
                $page = min(max(1,$parser->get('page', 1)), $pages);
            else $page = 1;

            $threads = $em->getRepository(Thread::class)->findByForum($forum, $num_per_page, ($page-1)*$num_per_page, $show_hidden_threads);
        } elseif ( $this->perm->isPermitted( $permissions, ForumUsagePermissions::PermissionModerate ) ) {

            $tp = $ttp = 0;
            $threads = array_filter( $em->getRepository(Thread::class)->findByForum($forum, null, null, $show_hidden_threads), function(Thread $t) use ($tp,$ttp,$sel_thread): bool { $tp++; if ($t === $sel_thread) $ttp = $tp-1; return $t->hasReportedPosts(); } );
            $pages = floor(max(0,count($threads)-1) / $num_per_page) + 1;
            if ($sel_thread && !$sel_thread->getPinned())
                $page = 1 + ($ttp / $num_per_page);
            elseif ($parser->has('page'))
                $page = min(max(1,$parser->get('page', 1)), $pages);
            else $page = 1;

            $threads = array_slice($threads, ($page-1)*$num_per_page, $num_per_page);
        } else {
            $page = $pages = 1;
            $threads = [];
        }

        foreach ($threads as $thread) {
            /** @var Thread $thread */
            /** @var ThreadReadMarker $marker */
            $marker = $em->getRepository(ThreadReadMarker::class)->findByThreadAndUser($user, $thread);
            $lastPost = $thread->lastPost( $show_hidden_threads );
            if (!$marker || ($lastPost && $lastPost !== $marker->getPost()))
                $thread->setNew();
        }

        if ( $this->perm->isPermitted( $permissions, ForumUsagePermissions::PermissionListThreads ) ) {
            $pinned_threads = $em->getRepository(Thread::class)->findPinnedByForum($forum, null, null, $show_hidden_threads);
        } elseif ( $this->perm->isPermitted( $permissions, ForumUsagePermissions::PermissionModerate ) ) {
            $pinned_threads = array_filter( $em->getRepository(Thread::class)->findPinnedByForum($forum, null, null, $show_hidden_threads), fn(Thread $t): bool => $t->hasReportedPosts() );
        } else $pinned_threads = [];

        foreach ($pinned_threads as $thread) {
            /** @var Thread $thread */
            /** @var ThreadReadMarker $marker */
            $marker = $em->getRepository(ThreadReadMarker::class)->findByThreadAndUser($user, $thread);
            $lastPost = $thread->lastPost( $show_hidden_threads );
            if (!$marker || ($lastPost && $lastPost !== $marker->getPost()))
                $thread->setNew();
        }

        return $this->render( 'ajax/forum/view.html.twig', $this->addDefaultTwigArgs(null, [
            'forum' => $forum,
            'threads' => $threads,
            'pinned_threads' => $pinned_threads,
            'permission' => $this->getPermissionObject( $permissions ),
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

        if ($citizen !== null && $citizen->getAlive() && $citizen->getTown()->getForum() && $this->perm->checkEffectivePermissions( $user, $citizen->getTown()->getForum(), ForumUsagePermissions::PermissionRead ))
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
        $post = $this->entity_manager->getRepository(Post::class)->find($pid);

        return $this->default_forum_renderer($post ? $post->getThread()->getForum()->getId() : -1,$post ? $post->getThread()->getId() : -1,$post ? $pid : -1,$em,$p,$ch);
    }

    /**
     * @Route("jx/forum", name="forum_list")
     * @return Response
     */
    public function forums(): Response
    {
        $forums = $this->perm->getForumsWithPermission($this->getUser());
        $subscriptions = $this->getUser()->getForumThreadSubscriptions()->filter(fn(ForumThreadSubscription $s) => in_array($s->getThread()->getForum(), $forums));

        return $this->render( 'ajax/forum/list.html.twig', $this->addDefaultTwigArgs(null, [
            'user' => $this->getUser(),
            'forums' => $forums,
            'subscriptions' => $subscriptions
        ] ));
    }

    /**
     * @Route("api/forum/{id<\d+>}/post", name="forum_new_thread_controller")
     * @param int $id
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function new_thread_api(int $id, JSONRequestParser $parser, EntityManagerInterface $em): Response {

        /** @var Forum $forum */
        $forum = $em->getRepository(Forum::class)->find($id);
        if (!$forum) return AjaxResponse::error( self::ErrorForumNotFound );

        $user = $this->getUser();
        $permission = $this->perm->getEffectivePermissions($user,$forum);
        if ($user->getIsBanned() || !$this->perm->isPermitted( $permission, ForumUsagePermissions::PermissionCreateThread ))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if (!$parser->has_all(['title','text'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);


        $title = $parser->trimmed('title');
        $text  = $parser->trimmed('text');

        $type = $parser->get('type') ?? 'USER';
        $valid = ['USER'];
        if ($this->perm->isPermitted( $permission, ForumUsagePermissions::PermissionPostAsCrow )) $valid[] = 'CROW';
        if ($this->perm->isPermitted( $permission, ForumUsagePermissions::PermissionPostAsDev )) $valid[] = 'DEV';
        if (!in_array($type, $valid)) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if (mb_strlen($title) < 3 || mb_strlen($title) > 64)  return AjaxResponse::error( self::ErrorPostTitleLength );
        if (mb_strlen($text) < 2 || mb_strlen($text) > 16384) return AjaxResponse::error( self::ErrorPostTextLength );

        $thread = (new Thread())->setTitle( $title )->setOwner($user);

        $post = (new Post())
            ->setOwner( $type === "CROW" ? $this->entity_manager->getRepository(User::class)->find(66) : $user )
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
            $em->persist((new ForumThreadSubscription())->setThread($thread)->setUser($user));
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
     * @param PictoHandler $ph
     * @return Response
     */
    public function new_post_api(int $fid, int $tid, JSONRequestParser $parser, EntityManagerInterface $em, PictoHandler $ph): Response {
        $user = $this->getUser();

        $thread = $em->getRepository(Thread::class)->find( $tid );
        if (!$thread || $thread->getForum()->getId() !== $fid) return AjaxResponse::error( self::ErrorForumNotFound );

        /** @var Forum $forum */
        $forum = $thread->getForum();

        $permissions = $this->perm->getEffectivePermissions($user, $forum);

        if ($user->getIsBanned())
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $mod_post = false;
        if (!$this->perm->isPermitted( $permissions, ForumUsagePermissions::PermissionCreatePost )) {
            if ($thread->hasReportedPosts() && $this->perm->isPermitted( $permissions, ForumUsagePermissions::PermissionModerate ) )
                $mod_post = true;
            else return AjaxResponse::error( ErrorHelper::ErrorPermissionError );
        }

        if (($thread->getLocked() || $thread->getHidden()) && !$this->perm->isPermitted($permissions, ForumUsagePermissions::PermissionModerate))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        // Check the last 4 posts; if they were all made by the same user, they must wait 4h before they can post again
        if (!$this->perm->isPermitted( $permissions, ForumUsagePermissions::PermissionModerate )) {
            $last_posts = $this->entity_manager->getRepository(Post::class)->findBy(['thread' => $thread], ['date' => 'DESC'], 4);
            if (count($last_posts) === 4) {
                $all_by_user = true;
                foreach ($last_posts as $last_post) $all_by_user = $all_by_user && ($last_post->getOwner() === $user);
                if ($all_by_user && $last_posts[0]->getDate()->getTimestamp() > (time() - 14400) )
                    return AjaxResponse::error( self::ErrorForumLimitHit );
            }
        }


        if (!$parser->has_all(['text'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $text = $parser->get('text');

        $type = $parser->get('type') ?? 'USER';
        $valid = ['USER'];
        if ($this->perm->isPermitted( $permissions, ForumUsagePermissions::PermissionPostAsCrow )) $valid[] = 'CROW';
        if ($this->perm->isPermitted( $permissions, ForumUsagePermissions::PermissionPostAsDev )) $valid[] = 'DEV';
        if (!in_array($type, $valid)) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $post = (new Post())
            ->setOwner( $type === "CROW" ? $this->entity_manager->getRepository(User::class)->find(66) : $user )
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
            /** @var Citizen $current_citizen */
            $current_citizen = $this->entity_manager->getRepository(Citizen::class)->findOneBy(['user' => $user, 'town' => $forum->getTown(), 'alive' => true]);
            if ($current_citizen) {
                // Give picto if the post is in the town forum
                $ph->give_picto($current_citizen, 'r_forum_#00');
            }
        }

        /** @var ForumThreadSubscription[] $subscriptions */
        $subscriptions = $em->getRepository(ForumThreadSubscription::class)->matching(
            (new Criteria())
                ->andWhere( Criteria::expr()->neq('user', $user) )
                ->andWhere( Criteria::expr()->eq('thread', $thread) )
                ->andWhere( Criteria::expr()->lt('num', 10) )
        );

        foreach ($subscriptions as $s) $em->persist($s->setNum($s->getNum() + 1));

        try {
            $em->persist($thread);
            $em->persist($forum);
            $em->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success( true, ['url' =>
            $this->generateUrl('forum_thread_view', ['fid' => $fid, 'tid' => $tid])
        ] );
    }

    /**
     * @Route("api/forum/{fid<\d+>}/{tid<\d+>}/{pid<\d+>}/edit", name="forum_edit_post_controller")
     * @param int $fid
     * @param int $tid
     * @param int $pid
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param CrowService $crow
     * @return Response
     */
    public function edit_post_api(int $fid, int $tid, int $pid, JSONRequestParser $parser, EntityManagerInterface $em, CrowService $crow): Response {
        $user = $this->getUser();
        if ($user->getIsBanned()) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $post = $em->getRepository(Post::class)->find($pid);
        if (!$post) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if ($post->getTranslate()) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        /** @var Thread $thread */
        $thread = $em->getRepository(Thread::class)->find( $tid );
        if (!$thread || $thread->getForum()->getId() !== $fid || $post->getThread() !== $thread)
            return AjaxResponse::error( self::ErrorForumNotFound );

        $permission = $this->perm->getEffectivePermissions($user, $thread->getForum());

        $mod_permissions = $thread->hasReportedPosts() && $this->perm->isPermitted($permission, ForumUsagePermissions::PermissionModerate);

        if ($post->getOwner()->getId() === 66 && !$this->perm->isPermitted($permission, ForumUsagePermissions::PermissionPostAsCrow | ForumUsagePermissions::PermissionEditPost))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if ((($post->getOwner() !== $user && $post->getOwner()->getId() !== 66) || !$post->isEditable()) && !$mod_permissions && !$this->perm->isPermitted($permission, ForumUsagePermissions::PermissionModerate | ForumUsagePermissions::PermissionEditPost) )
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if (($thread->getLocked() || $thread->getHidden()) && !$mod_permissions && !$this->perm->isPermitted($permission, ForumUsagePermissions::PermissionModerate))
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

            $notification = $crow->createPM_moderation( $post->getOwner(),
                CrowService::ModerationActionDomainForum, CrowService::ModerationActionTargetPost, CrowService::ModerationActionEdit,
                $post
            );
            if ($notification) $em->persist($notification);
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
    public function small_viewer_api( int $fid, int $sem, EntityManagerInterface $em) {
        $user = $this->getUser();

        if ($sem === 0) return new Response('');

        /** @var Forum $forum */
        $forum = $em->getRepository(Forum::class)->find($fid);
        if (!$forum || !$this->perm->checkEffectivePermissions( $user, $forum, ForumUsagePermissions::PermissionReadThreads ))
            return new Response('');

        /** @var Thread $thread */
        $thread = $em->getRepository(Thread::class)->findByForumSemantic( $forum, $sem );
        if (!$thread || $thread->getHidden() || $thread->getForum()->getId() !== $fid) return new Response(' ');

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
     * @param int $pid
     * @return Response
     */
    public function viewer_api(int $fid, int $tid, EntityManagerInterface $em, JSONRequestParser $parser, int $pid = -1): Response {
        $num_per_page = 10;
        $user = $this->getUser();

        /** @var Thread $thread */
        $thread = $em->getRepository(Thread::class)->find( $tid );
        if (!$thread || $thread->getForum()->getId() !== $fid) return new Response('');

        /** @var Forum $forum */
        $forum = $em->getRepository(Forum::class)->find($fid);
        $permissions = $this->perm->getEffectivePermissions( $user, $forum );

        if ($thread->getHidden() && !$this->perm->isPermitted( $permissions, ForumUsagePermissions::PermissionModerate ))
            return new Response('');

        if (!$this->perm->isPermitted( $permissions, ForumUsagePermissions::PermissionReadThreads )) {
            if (!$thread->hasReportedPosts() || !$this->perm->isPermitted( $permissions, ForumUsagePermissions::PermissionModerate ) )
                return new Response('', 200, ['X-AJAX-Control' => 'reload']);
        }

        $jump_post = ($pid > 0) ? $em->getRepository(Post::class)->find( $pid ) : null;
        if ($jump_post && $jump_post->getThread() !== $thread) return new Response('');

        $marker = $em->getRepository(ThreadReadMarker::class)->findByThreadAndUser( $user, $thread );
        if (!$marker) $marker = (new ThreadReadMarker())->setUser($user)->setThread($thread);

        if ($this->perm->isPermitted( $permissions, ForumUsagePermissions::PermissionModerate ))
            $pages = floor(max(0,$em->getRepository(Post::class)->countByThread($thread)-1) / $num_per_page) + 1;
        else
            $pages = floor(max(0,$em->getRepository(Post::class)->countUnhiddenByThread($thread)-1) / $num_per_page) + 1;

        if ($jump_post)
            $page = min($pages,1 + floor(($em->getRepository(Post::class)->getOffsetOfPostByThread( $thread, $jump_post, $this->perm->isPermitted( $permissions, ForumUsagePermissions::PermissionModerate ) )) / $num_per_page));
        elseif ($parser->has('page'))
            $page = min(max(1,$parser->get('page', 1)), $pages);
        elseif (!$marker->getPost()) $page = 1;
        else $page = min($pages,1 + floor(($em->getRepository(Post::class)->getOffsetOfPostByThread( $thread, $marker->getPost(), $this->perm->isPermitted( $permissions, ForumUsagePermissions::PermissionModerate ) )) / $num_per_page));

        if ($this->perm->isPermitted( $permissions, ForumUsagePermissions::PermissionModerate ))
            $posts = $em->getRepository(Post::class)->findByThread($thread, $num_per_page, ($page-1)*$num_per_page);
        else
            $posts = $em->getRepository(Post::class)->findUnhiddenByThread($thread, $num_per_page, ($page-1)*$num_per_page);


        $announces = [
            'reported' => $this->perm->isPermitted( $permissions, ForumUsagePermissions::PermissionModerate ) ? $thread->getUnseenReportedPosts() : [],
            'admin' => $em->getRepository(Post::class)->findAdminAnnounces($thread),
            'oracle' => $em->getRepository(Post::class)->findOracleAnnounces($thread)
        ];

        foreach ($posts as $post){
            /** @var $post Post */
            if ($marker->getPost() === null || $marker->getPost()->getDate() < $post->getDate())
                $post->setNew();
        }

        $flush = false;

        if (!empty($posts)) {

            /** @var Post $read_post */
            $read_post = $posts[array_key_last($posts)];
            /** @var Post $last_read */
            $last_read = $marker->getPost();
            if ($last_read === null || $read_post->getId() > $last_read->getId()) {
                $marker->setPost($read_post);
                $em->persist($marker);
                $flush = true;
            }

            /** @var ForumThreadSubscription[] $subscriptions */
            $subscriptions = $em->getRepository(ForumThreadSubscription::class)->matching(
                (new Criteria())
                    ->andWhere( Criteria::expr()->eq('user', $user) )
                    ->andWhere( Criteria::expr()->eq('thread', $thread) )
                    ->andWhere( Criteria::expr()->gt('num', 0))
            );

            if (!empty($subscriptions)) {
                foreach ($subscriptions as $s) $em->persist($s->setNum(0));
                $flush = true;
            }
        }

        if ($flush) try { $em->flush(); } catch (Exception $e) {}

        foreach ($posts as &$post) $post->setText( $this->prepareEmotes( $post->getText() ) );
        return $this->render( 'ajax/forum/posts.html.twig', [
            'posts' => $posts,
            'owned' => $thread->getOwner() === $user,
            'locked' => $thread->getLocked(),
            'pinned' => $thread->getPinned(),
            'fid' => $fid,
            'tid' => $tid,
            'current_page' => $page,

            'permission' => $this->getPermissionObject($permissions),

            'pages' => $pages,
            'announces' => $announces,
            'markedPost' => $pid,
            'subscribed' => $em->getRepository(ForumThreadSubscription::class)->count( ['user' => $user, 'thread' => $thread] )
        ] );
    }

    /**
     * @Route("jx/forum/{id<\d+>}/editor", name="forum_thread_editor_controller")
     * @param int $id
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function editor_thread_api(int $id, EntityManagerInterface $em): Response {
        $forum = $em->getRepository(Forum::class)->find($id);
        $permissions = $this->perm->getEffectivePermissions( $this->getUser(), $forum );

        if (!$forum || !$this->perm->isPermitted( $permissions, ForumUsagePermissions::PermissionCreateThread ))
            return new Response('');

        return $this->render( 'ajax/forum/editor.html.twig', [
            'fid' => $id,
            'tid' => null,
            'pid' => null,

            'permission' => $this->getPermissionObject( $permissions ),
            'snippets' => $this->perm->isPermitted( $permissions, ForumUsagePermissions::PermissionPostAsCrow ) ? $this->entity_manager->getRepository(ForumModerationSnippet::class)->findAll() : [],

            'emotes' => $this->getEmotesByUser($this->getUser(),true),
            'username' => $this->getUser()->getName(),
            'forum' => true,
            'town_controls' => $forum->getTown() !== null,
        ] );
    }

    /**
     * @Route("jx/forum/{fid<\d+>}/{tid<\d+>}/editor", name="forum_post_editor_controller")
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

        $permissions = $this->perm->getEffectivePermissions( $user, $thread->getForum() );
        if (!$this->perm->isPermitted( $permissions, ForumUsagePermissions::PermissionCreatePost )) {
            if (!$thread->hasReportedPosts() || !$this->perm->isPermitted( $permissions, ForumUsagePermissions::PermissionModerate ) )
                return new Response('');
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

            'permission' => $this->getPermissionObject( $permissions ),
            'snippets' => $this->perm->isPermitted( $permissions, ForumUsagePermissions::PermissionPostAsCrow ) ? $this->entity_manager->getRepository(ForumModerationSnippet::class)->findAll() : [],

            'emotes' => $this->getEmotesByUser($this->getUser(),true),
            'forum' => true,
            'town_controls' => $thread->getForum()->getTown() !== null,
        ] );
    }

    /**
     * @Route("api/forum/{fid<\d+>}/{tid<\d+>}/subscribe", name="forum_thread_subscribe_controller")
     * @param int $fid
     * @param int $tid
     * @return Response
     */
    public function forum_thread_subscribe(int $fid, int $tid): Response {
        /** @var Forum $forum */
        $forum = $this->entity_manager->getRepository(Forum::class)->find($fid);
        /** @var Thread $thread */
        $thread = $this->entity_manager->getRepository(Thread::class)->find($tid);

        if ($thread->getForum() !== $forum) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $permissions = $this->perm->getEffectivePermissions( $this->getUser(), $thread->getForum() );
        if (!$this->perm->isPermitted( $permissions, ForumUsagePermissions::PermissionRead ))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );


        $existing = $this->entity_manager->getRepository(ForumThreadSubscription::class)->count(['user' => $this->getUser(), 'thread' => $thread]);
        if (!$existing) try {
            $this->entity_manager->persist((new ForumThreadSubscription())->setThread($thread)->setUser($this->getUser()));
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/forum/{fid<\d+>}/{tid<\d+>}/unsubscribe", name="forum_thread_unsubscribe_controller")
     * @param int $fid
     * @param int $tid
     * @return Response
     */
    public function forum_thread_unsubscribe(int $fid, int $tid): Response {
        /** @var Forum $forum */
        $forum = $this->entity_manager->getRepository(Forum::class)->find($fid);
        /** @var Thread $thread */
        $thread = $this->entity_manager->getRepository(Thread::class)->find($tid);

        if ($thread->getForum() !== $forum) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $existing = $this->entity_manager->getRepository(ForumThreadSubscription::class)->findOneBy(['user' => $this->getUser(), 'thread' => $thread]);
        if ($existing) try {
            $this->entity_manager->remove($existing);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/forum/{fid<\d+>}/{tid<\d+>}/moderate/{mod}", name="forum_thread_mod_controller")
     * @param int $fid
     * @param int $tid
     * @param string $mod
     * @param JSONRequestParser $parser
     * @param CrowService $crow
     * @return Response
     */
    public function mod_thread_api(int $fid, int $tid, string $mod, JSONRequestParser $parser, CrowService $crow): Response {
        $success = false;

        /** @var Forum $forum */
        $forum = $this->entity_manager->getRepository(Forum::class)->find($fid);

        /** @var Thread $thread */
        $thread = $this->entity_manager->getRepository(Thread::class)->find($tid);

        if ($thread->getForum() !== $forum) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        switch ($mod) {
            case 'lock':
                if ($thread->getOwner() !== $this->getUser() && !$this->perm->checkEffectivePermissions($this->getUser(), $forum, ForumUsagePermissions::PermissionModerate))
                    return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

                $thread->setLocked(true);
                try {
                    $this->entity_manager->persist($thread);
                    $this->entity_manager->flush();
                    return AjaxResponse::success();
                } catch (Exception $e) {
                    return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
                }

            case 'unlock':
                if (!$this->perm->checkEffectivePermissions($this->getUser(), $forum, ForumUsagePermissions::PermissionModerate))
                    return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

                $thread->setLocked(false);
                try {
                    $this->entity_manager->persist($thread);
                    $this->entity_manager->flush();
                    return AjaxResponse::success();
                } catch (Exception $e) {
                    return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
                }

            case 'pin':
                if (!$this->perm->checkEffectivePermissions($this->getUser(), $forum, ForumUsagePermissions::PermissionModerate))
                    return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

                $thread->setPinned(true);
                try {
                    $this->entity_manager->persist($thread);
                    $this->entity_manager->flush();
                    return AjaxResponse::success();
                } catch (Exception $e) {
                    return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
                }

            case 'unpin':
                if (!$this->perm->checkEffectivePermissions($this->getUser(), $forum, ForumUsagePermissions::PermissionModerate))
                    return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

                $thread->setPinned(false);
                try {
                    $this->entity_manager->persist($thread);
                    $this->entity_manager->flush();
                    return AjaxResponse::success();
                } catch (Exception $e) {
                    return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
                }

            case 'delete':

                /** @var Post $post */
                $post = $this->entity_manager->getRepository(Post::class)->find((int)$parser->get('postId'));
                $reason = $parser->get( 'reason', '' );
                if (!$post || empty($reason)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                if (!$this->perm->checkEffectivePermissions($this->getUser(), $forum, ForumUsagePermissions::PermissionModerate))
                    return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

                if ($post->getHidden() || $post->getThread() !== $thread) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                try {
                    $post->setHidden(true);
                    $this->entity_manager->persist( $post );
                    $this->entity_manager->persist( (new AdminDeletion())
                                                        ->setSourceUser( $this->getUser() )
                                                        ->setTimestamp( new DateTime('now') )
                                                        ->setReason( $reason )
                                                        ->setPost( $post ) );
                    $reports = $post->getAdminReports(true);
                    foreach ($reports as $report)
                        $this->entity_manager->persist($report->setSeen(true));

                    if ($post === $thread->firstPost(true)) {
                        $thread->setHidden(true)->setLocked(true);
                        $this->entity_manager->persist($thread);

                        $notification = $crow->createPM_moderation( $post->getOwner(),
                            CrowService::ModerationActionDomainForum, CrowService::ModerationActionTargetThread, CrowService::ModerationActionDelete,
                            $post, $reason
                        );

                    } else {
                        $notification = $crow->createPM_moderation( $post->getOwner(),
                            CrowService::ModerationActionDomainForum, CrowService::ModerationActionTargetPost, CrowService::ModerationActionDelete,
                            $post, $reason
                        );
                    }

                    if ($notification) $this->entity_manager->persist($notification);

                    $this->entity_manager->flush();
                    return AjaxResponse::success();
                }
                catch (Exception $e) {
                    return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
                }

            case 'undelete':
                if (!$this->perm->checkEffectivePermissions($this->getUser(), $forum, ForumUsagePermissions::PermissionModerate))
                    return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

                /** @var Post $post */
                $post = $this->entity_manager->getRepository(Post::class)->find((int)$parser->get('postId'));
                if (!$post || !$post->getHidden() || $post->getThread() !== $thread) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                try {
                    $post->setHidden(false);
                    if ($ad = $this->entity_manager->getRepository(AdminDeletion::class)->findOneBy(['post' => $post]))
                        $this->entity_manager->remove($ad);
                    $this->entity_manager->persist( $post );
                    $this->entity_manager->flush();
                    return AjaxResponse::success();
                }
                catch (Exception $e) {
                    return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
                }

            case 'seen':

                /** @var Post $post */
                $post = $this->entity_manager->getRepository(Post::class)->find((int)$parser->get('postId'));

                if (!$this->perm->checkEffectivePermissions($this->getUser(), $forum, ForumUsagePermissions::PermissionModerate))
                    return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

                if (!$post || $post->getAdminReports(true)->isEmpty()) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                try {
                    foreach ($post->getAdminReports(true) as $report)
                        $this->entity_manager->persist($report->setSeen(true));
                    $this->entity_manager->persist( $post );
                    $this->entity_manager->flush();
                    return AjaxResponse::success();
                }
                catch (Exception $e) {
                    return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
                }
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
        if (!$parser->has('postId'))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $user = $this->getUser();
        $postId = $parser->get('postId');

        /** @var Post $post */
        $post = $em->getRepository( Post::class )->find( $postId );
        if ($post->getTranslate() || $post->getThread()->getId() !== $tid || $post->getThread()->getForum()->getId() !== $fid) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if (!$this->perm->checkEffectivePermissions($user, $post->getThread()->getForum(), ForumUsagePermissions::PermissionReadThreads))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $targetUser = $post->getOwner();
        if ($targetUser->getName() === "Der Rabe" ) {
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
        $message = $ti->trans('Du hast die Nachricht von %username% dem Raben gemeldet. Wer weiß, vielleicht wird %username% heute Nacht stääärben...', ['%username%' => '<span>' . $post->getOwner()->getName() . '</span>'], 'game');
        $this->addFlash('notice', $message);
        return AjaxResponse::success( );
    }
}