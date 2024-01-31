<?php

namespace App\Service\Actions\Security;

class GenerateKeyAction
{
    public function __construct() { }

    public function __invoke(int $characters = 16): string
    {
        try {
            $data = bin2hex(random_bytes(1 + ceil($characters/2)));
        } catch (\Throwable) {
            $data = '';
            while (strlen($data) < $characters) $data .= uniqid(more_entropy: true);
        }

        return substr( $data, 0, $characters );
    }
}