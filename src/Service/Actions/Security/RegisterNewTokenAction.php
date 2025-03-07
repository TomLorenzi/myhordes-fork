<?php

namespace App\Service\Actions\Security;

use App\Service\Locksmith;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class RegisterNewTokenAction
{
    public function __construct(
        private readonly GenerateKeyAction $keygen,
        private readonly Locksmith $locksmith,
        private TagAwareCacheInterface $gameCachePool
    ) { }

    public function __invoke(Request $request): string
    {
        if ($request->headers->has('Sec-Fetch-Dest') && $request->headers->get('Sec-Fetch-Dest') !== 'document')
            return ($this->keygen)(16);

        $lock = $this->locksmith->waitForLock("ticketing_{$request->getSession()->getId()}");
        if (!$request->getSession()->has('token')) $request->getSession()->set('token', ($this->keygen)(16));

        $ticket = ($this->keygen)(16);
        $token = $request->getSession()->get('token');

        $this->gameCachePool->get( "ticketing_{$ticket}", function (ItemInterface $item) {
            $item->expiresAfter(60);
            return true;
        } );

        $this->gameCachePool->get( "toaster_{$token}", function (ItemInterface $item) use ($request) {
            $item->expiresAfter(86400);
            return $request->headers->get('User-Agent') ?? '-no-agent-';
        } );

        $this->gameCachePool->get( "toaster_sub_" . substr( $token, 0, 8 ), function (ItemInterface $item) use ($request) {
            $item->expiresAfter(86400);
            return $request->headers->get('User-Agent') ?? '-no-agent-';
        } );

        $lock->release();

        return $ticket;
    }
}