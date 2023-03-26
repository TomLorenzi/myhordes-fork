<?php

namespace App\Traits\Actions\ActionResults;

trait Optional
{
    public function __get(string $name): mixed
    {
        return null;
    }

    public function __call($name, $arguments)
    {
        return null;
    }
}