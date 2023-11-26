<?php

namespace App\Controller\REST\User\Settings;

use App\Entity\NotificationSubscription;
use App\Entity\User;
use App\Enum\NotificationSubscriptionType;
use App\Service\JSONRequestParser;
use ArrayHelpers\Arr;
use BenTools\WebPushBundle\Model\Message\PushNotification;
use BenTools\WebPushBundle\Sender\PushMessageSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


/**
 * @method User getUser()
 */
#[Route(path: '/rest/v1/user/settings/notifications', name: 'rest_user_settings_notifications_', condition: "request.headers.get('Accept') === 'application/json'")]
#[IsGranted('ROLE_USER')]
class NotificationManagerController extends AbstractController
{

    /**
     * @param Packages $assets
     * @param TranslatorInterface $trans
     * @return JsonResponse
     */
    #[Route(path: '/', name: 'index', methods: ['GET'])]
    public function index(Packages $assets, TranslatorInterface $trans): JsonResponse {
        return new JsonResponse([
            'strings' => [
                'common' => [

                ],
            ]
        ]);
    }

    private function renderNotifications(NotificationSubscription $subscription): ?array {
        return [
            'id' => $subscription->getId(),
            'hash' => $subscription->getSubscriptionHash(),
            'desc' => $subscription->getDescription()
        ];
    }

    #[Route(path: '/webpush', name: 'list_webpush', defaults: ['type' => NotificationSubscriptionType::WebPush->value], methods: ['GET'])]
    public function list(NotificationSubscriptionType $type, EntityManagerInterface $em): JsonResponse {
        return new JsonResponse([
            'subscriptions' => array_map(
                fn(NotificationSubscription $n) => $this->renderNotifications($n),
                $em->getRepository(NotificationSubscription::class)->findBy([
                    'type' => $type,
                    'user' => $this->getUser()
                                                                            ])
            )
        ]);
    }

    #[Route(path: '/webpush', name: 'put_webpush', defaults: ['type' => NotificationSubscriptionType::WebPush->value], methods: ['PUT'])]
    public function put(NotificationSubscriptionType $type, EntityManagerInterface $em, JSONRequestParser $parser, ValidatorInterface $validator): JsonResponse {

        $desc = $parser->get('desc');
        $payload = $parser->get_array('payload');

        $valid = match ($type) {
            NotificationSubscriptionType::Invalid => false,
            NotificationSubscriptionType::WebPush =>
                Arr::has($payload, 'endpoint') && $validator->validate( Arr::get($payload, 'endpoint'), [
                    new Url()
                ] )->count() === 0 &&
                Arr::has($payload, 'keys.p256dh') && $validator->validate( Arr::get($payload, 'keys.p256dh'), [
                    new Length(min: 1, max: 256)
                ] )->count() === 0 &&
                Arr::has($payload, 'keys.auth') && $validator->validate( Arr::get($payload, 'keys.auth'), [
                    new Length(min: 1, max: 256)
                ] )->count() === 0 &&
                (!Arr::has($payload, 'content-encoding') || $validator->validate( Arr::get($payload, 'content-encoding', ''), [
                    new Length(min: 1, max: 256)
                ] )->count() === 0)
        };

        if (!$valid) return new JsonResponse(status: Response::HTTP_BAD_REQUEST);

        $payload = match ($type) {
            NotificationSubscriptionType::Invalid => [],
            NotificationSubscriptionType::WebPush => [
                'endpoint' => Arr::get( $payload, 'endpoint' ),
                'content-encoding' => Arr::get( $payload, 'content-encoding', 'aesgcm' ),
                'keys' => [
                    'p256dh' => Arr::get($payload, 'keys.p256dh'),
                    'auth' => Arr::get($payload, 'keys.auth'),
                ]
            ]
        };

        $subscription = (new NotificationSubscription())
            ->setType( $type )
            ->setCreatedAt( new \DateTime())
            ->setSubscription( $payload )
            ->setDescription( $desc );

        $hash = $subscription->calculateHash();
        if ($em->getRepository(NotificationSubscription::class)->count([
            'type' => $type,
            'subscriptionHash' => $hash
        ]) > 0) return new JsonResponse(status: Response::HTTP_CONFLICT);

        $em->persist( $subscription->setSubscriptionHash( $hash )->setUser( $this->getUser() ) );
        $em->flush();
        return new JsonResponse(['subscription' => $this->renderNotifications( $subscription )]);
    }

    #[Route(path: '/webpush/{id}', name: 'delete_webpush', defaults: ['type' => NotificationSubscriptionType::WebPush->value], methods: ['DELETE'])]
    public function delete(NotificationSubscriptionType $type, EntityManagerInterface $em, NotificationSubscription $subscription): JsonResponse {

        if ($subscription->getType() !== $type || $subscription->getUser() !== $this->getUser())
            return new JsonResponse(status: Response::HTTP_NOT_FOUND);

        $this->getUser()->getNotificationSubscriptions()->removeElement( $subscription );
        $em->remove( $subscription );
        $em->persist( $this->getUser() );
        $em->flush();
        return new JsonResponse();
    }

    #[Route(path: '/webpush/{id}/test', name: 'test_webpush', defaults: ['type' => NotificationSubscriptionType::WebPush->value], methods: ['POST'])]
    public function test(NotificationSubscriptionType $type, NotificationSubscription $subscription, PushMessageSender $sender): JsonResponse {

        if ($subscription->getType() !== $type || $subscription->getUser() !== $this->getUser())
            return new JsonResponse(status: Response::HTTP_NOT_FOUND);

        $notification = new PushNotification('Test message!', [
            PushNotification::BODY => 'This is a test message.'
        ]);

        $response = null;
        $responses = $sender->push( $notification->createMessage(), [$subscription] );
        foreach ($responses as $r) $response = $r;

        return new JsonResponse([
            'status' => $response?->getStatusCode() ?? -1,
            'success' => $response?->isSuccessFul() ?? false,
            'expired' => $response?->isExpired() ?? false
        ]);
    }
}
