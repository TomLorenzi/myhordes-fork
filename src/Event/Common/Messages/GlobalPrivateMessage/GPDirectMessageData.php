<?php

namespace App\Event\Common\Messages\GlobalPrivateMessage;

use App\Entity\GlobalPrivateMessage;
use App\Entity\UserGroup;
use App\Structures\HTMLParserInsight;

readonly class GPDirectMessageData
{
    public GlobalPrivateMessage $post;

    /**
     * @param GlobalPrivateMessage $post
     * @return GPDirectMessageEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( GlobalPrivateMessage $post ): void {
        $this->post = $post;
    }
}