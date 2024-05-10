<?php

namespace App\Service\Actions\Mercure;

use App\Entity\User;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

readonly class BroadcastViaMercureAction
{
    public function __construct(
        private HubInterface $hub
    ) { }

    public function __invoke(string $message, array $data, bool $public = false, array|User|int $users = []): ?string
    {
        $user_ids = match (true) {
            !$public && is_array($users) => array_map( fn(int|User $user) => is_int($user) ? $user : $user->getId() , $users ),
            !$public && is_int($users) => [$users],
            !$public && !is_int($users) => [$users->getId()],
            default => []
        };
        if (!$public && empty($user_ids)) return null;

        return $this->hub->publish(new Update(
            topics: $public ? 'myhordes://live/concerns/authorized' : array_map( fn(int $user) => "myhordes://live/concerns/{$user}", $user_ids ),
            data: json_encode([
                                  'message' => $message,
                                  ...$data
                              ]),
            private: !$public
        ));
    }
}