<?php

namespace App\Service;

use App\Entity\BankAntiAbuse;
use App\Entity\Citizen;
use App\Entity\Forum;
use App\Entity\Post;
use App\Entity\Thread;
use App\Entity\User;
use App\Structures\TownConf;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class CrowService {

    private $em;
    private $crow_cache = null;

    private function getCrowAccount(): User {
        return $this->crow_cache ?? ($this->crow_cache = $this->em->getRepository(User::class)->find(66));
    }

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param Forum $forum
     * @param string|array $text
     * @param bool $pinned
     * @param bool $translatable
     * @param string|array|null $title
     * @param Thread|null $thread
     */
    public function postToForum( Forum $forum, $text, bool $pinned, bool $translatable, $title = null, $semantic = 0, ?Thread $thread = null ) {

        if (is_array( $text )) {

            foreach ($text as $k => $single_text)
                $this->postToForum(
                    $forum, $single_text, $pinned, $translatable,
                    is_array($title) ? $title[$k] : $title,
                    is_array($semantic) ? $semantic[$k] : $semantic,
                    $thread
                );
                return;
        }

        if ($thread === null) {
            $thread = (new Thread())
                ->setTitle( $title )
                ->setTranslatable( $translatable )
                ->setOwner( $this->getCrowAccount() );
            $forum->addThread($thread);
        }

        if ($pinned) $thread->setPinned( true );
        if ($semantic !== 0) $thread->setSemantic( $semantic );
        $thread->setLastPost( new DateTime() );

        $post = (new Post())
            ->setDate(new DateTime())
            ->setOwner( $this->getCrowAccount() )
            ->setText( $text )
            ->setTranslate( $translatable );
        $thread->addPost($post);

        $this->em->persist( $thread );
        $this->em->persist( $post );
    }

}