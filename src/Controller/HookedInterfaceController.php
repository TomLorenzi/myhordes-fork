<?php
namespace App\Controller;

interface HookedInterfaceController {
    public function before(): bool;
}