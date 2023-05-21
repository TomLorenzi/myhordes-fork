<?php

namespace App\Controller\REST\User;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Service\Actions\Security\GenerateKeyAction;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/rest/v1/user/security", name="rest_user_security_", condition="request.headers.get('Accept') === 'application/json'")
 * @GateKeeperProfile("skip")
 */
class SecurityController extends CustomAbstractCoreController
{

    /**
     * @Route("/token/exchange/{ticket}", name="token_exchange", methods={"GET"})
     * @param string $ticket
     * @param Request $request
     * @param GenerateKeyAction $keygen
     * @return JsonResponse
     */
    public function find(string $ticket, Request $request, GenerateKeyAction $keygen): JsonResponse {
        $tokens = $request->getSession()->get('token-ticket');
        if ($ticket && isset($tokens[$ticket])) {
            $token = $tokens[$ticket];
            unset($tokens[$ticket]);
            $request->getSession()->set('token-ticket', $tokens);
        } else $token = null;

        if ($token === null) $token = $keygen(16);

        return new JsonResponse(['token' => $token]);
    }

}
