<?php


namespace App\Service;


use App\Entity\Item;
use App\Entity\ItemPrototype;
use Doctrine\ORM\EntityManagerInterface;

class ErrorHelper
{
    const BaseInventoryErrors     = 100;
    const BaseJobErrors           = 200;
    const BaseTownErrors          = 300;
    const BaseTownSelectionErrors = 400;
    const BaseUserErrors          = 500;
    const BaseActionErrors        = 600;
    const BaseBeyondErrors        = 700;

    const ErrorInvalidRequest    = 1;
    const ErrorDatabaseException = 2;
    const ErrorInternalError     = 3;

    const ErrorNoAP              = 51;
}