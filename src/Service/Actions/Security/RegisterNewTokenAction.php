<?php

namespace App\Service\Actions\Security;

use Symfony\Component\HttpFoundation\Request;

class RegisterNewTokenAction
{
    public function __construct(
        private readonly GenerateKeyAction $keygen
    ) { }

    public function __invoke(Request $request): string
    {
        if ($request->headers->get('Sec-Fetch-Dest') !== 'document') return ($this->keygen)(16);

        $token  = $request->getSession()->get('token', ($this->keygen)(16));
        if (strlen($token) !== 16) $token = ($this->keygen)(16);
        $tickets = [ ($ticket = ($this->keygen)(16)) => $token ];

        $request->getSession()->set('token', $token);
        $request->getSession()->set('token-ticket', $tickets);

        return $ticket;
    }
}