<?php
namespace App\Annotations;

interface CustomAttribute
{
    public static function getAliasName(): string;
    public static function isRepeatable(): bool;
}