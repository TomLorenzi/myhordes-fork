<?php

namespace App\Event\Common\Messages\GlobalPrivateMessage;

use App\Entity\GlobalPrivateMessage;
use App\Entity\UserGroup;
use App\Structures\HTMLParserInsight;

readonly class GPMessageData
{
    public GlobalPrivateMessage $post;
    public HTMLParserInsight $insight;

    /**
     * @param GlobalPrivateMessage $post
     * @param HTMLParserInsight $insight
     * @return GPMessageEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( GlobalPrivateMessage $post, HTMLParserInsight $insight ): void {
        $this->post = $post;
        $this->insight = $insight;
    }
}