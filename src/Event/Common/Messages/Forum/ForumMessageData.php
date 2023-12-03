<?php

namespace App\Event\Common\Messages\Forum;

use App\Entity\Post;
use App\Structures\HTMLParserInsight;

readonly class ForumMessageData
{

    public Post $post;
    public HTMLParserInsight $insight;

    /**
     * @param Post $post
     * @param HTMLParserInsight $insight
     * @return ForumMessageEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( Post $post, HTMLParserInsight $insight ): void {
        $this->post = $post;
        $this->insight = $insight;
    }
}