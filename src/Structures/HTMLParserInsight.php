<?php

namespace App\Structures;

use App\Entity\User;

class HTMLParserInsight {

    public int $text_length = 0;
    public bool $editable = true;
    public array $polls = [];

    /** @var User[] */
    public array $taggedUsers = [];

}