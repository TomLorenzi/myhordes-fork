<?php

namespace App\Controller\REST\User\Settings;

use App\Entity\NotificationSubscription;
use App\Entity\User;
use App\Enum\NotificationSubscriptionType;
use App\Enum\UserSetting;
use App\Service\JSONRequestParser;
use App\Service\User\UserCapabilityService;
use ArrayHelpers\Arr;
use BenTools\WebPushBundle\Model\Message\PushNotification;
use BenTools\WebPushBundle\Model\Response\PushResponse;
use BenTools\WebPushBundle\Sender\PushMessageSender;
use Doctrine\ORM\EntityManagerInterface;
use Minishlink\WebPush\Encryption;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
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

    private function getToggleOptions(UserCapabilityService $capability, TranslatorInterface $trans) {
        $base = [
            [
                'type' => UserSetting::PushNotifyMeOnPM->value,
                'text' => $trans->trans('Push-Benachrichrichtigung über neue private Nachrichten erhalten', [], 'soul' ),
                'help' => null,
            ],
            [
                'type' => UserSetting::PushNotifyOnFriendTownJoin->value,
                'text' => $trans->trans('Push-Benachrichrichtigung erhalten, wenn Freunde einer Stadt beitreten', [], 'soul' ),
                'help' => $trans->trans('Damit du benachrichtigt wirst, wenn ein Freund eine Stadt betritt, muss dieser dich ebenfalls als Freund hinzugefügt haben. Du wirst zudem nur benachrichtigt, wenn du dieser Stadt ebenfalls beitreten könntest (dich also beispielsweise nicht bereits in einer anderen Stadt aufhälst).', [], 'soul' )
            ],
            [
                'type' => UserSetting::PushNotifyOnAnnounce->value,
                'delay' => true,
                'text' => $trans->trans('Push-Benachrichrichtigung für offizielle Ankündigungen auf MyHordes erhalten.', [], 'soul' ),
                'help' => null
            ],
            [
                'type' => UserSetting::PushNotifyOnEvent->value,
                'delay' => true,
                'text' => $trans->trans('Push-Benachrichrichtigung für Community-Events erhalten.', [], 'soul' ),
                'help' => $trans->trans('Wenn du diese Einstellung aktivierst, bekommst du eine Benachrichtung, wenn ein neues Community-Event in den Event-Kalender aufgenommen wurde.', [], 'soul' )
            ],
        ];

        if ( !empty($capability->getOfficialGroups( $this->getUser() )) )
            $base[] = [
                'type' => UserSetting::PushNotifyOnOfficialGroupChat->value,
                'text' => $trans->trans('Push-Benachrichrichtigung über eine neue Nachricht in meinen offiziellen Gruppen erhalten', [], 'soul' ),
                'help' => null,
            ];

        if ($capability->hasRole( $this->getUser(), 'ROLE_CROW', true ))
            $base[] = [
                'type' => UserSetting::PushNotifyOnModReport->value,
                'text' => $trans->trans('Push-Benachrichrichtigung für Meldungen an die Moderation erhalten', [], 'soul' ),
                'help' => null,
            ];

        return $base;
    }

    /**
     * @param Packages $assets
     * @param TranslatorInterface $trans
     * @return JsonResponse
     */
    #[Route(path: '/', name: 'index', methods: ['GET'])]
    public function index(Packages $assets, UserCapabilityService $capability, TranslatorInterface $trans): JsonResponse {
        return new JsonResponse([
            'strings' => [
                'common' => [
                    'help' => $trans->trans('Hilfe', [], 'global'),
                    'infoText1' => $trans->trans('Wenn du möchtest, kannst du auch dann Benachrichtigungen auf deinen Computer oder Smartphone bekommen, wenn du gerade nicht auf MyHordes unterwegs bist. So bist du immer auf dem neusten Stand und kannst keine wichtigen Meldungen mehr verpassen!', [], 'global'),
                    'infoText2' => $trans->trans('Um diese Funktion zu verwenden, musst du MyHordes die Erlaubnis geben, dir Benachrichtigungen auf dein Gerät zu schicken. Klicke auf "Benachrichtigungen auf diesem Gerät erhalten" und bestätige die Sicherheitsabfrage deines Browsers, um diese Funktion zu aktivieren.', [], 'global'),
                    'infoText3' => $trans->trans('Wenn du keine weiteren Benachrichtigungen erhalten möchtest, kannst du diese Funktion jederzeit vollständig oder für einzelne Geräte deaktivieren.', [], 'global'),
                    'unsupported' => $trans->trans('Dieses Gerät unterstützt keine Push-Benachrichtigungen.', [], 'global'),
                    'rejected'  => $trans->trans('Um diese Funktion zu verwenden, musst du MyHordes die Erlaubnis geben, dir Benachrichtigungen auf dein Gerät zu schicken.', [], 'global'),

                    'delayed' => $trans->trans('Die erhälst die gekennzeichneten Benachrichtigungen nur, wenn sie deiner gewählten Sprache entsprechen. Wenn du die entsprechenden Einstellungen änderst oder deine Sprache wechselst, kann es bis zu 24 Stunden dauern, bis die Änderungen für Benachrichtigungen übernommen werden.', [], 'global'),

                    'error_put_400' => $trans->trans('MyHordes ist nicht in der Lage, die von diesem Gerät angebotene Schnittstelle anzusprechen. Bitte versuche, dein Gerät oder Browser auf die neuste Version zu aktualisieren, oder verwende einen anderen Browser.', [], 'global'),
                    'error_put_409' => $trans->trans('Dieses Gerät ist bereits für Push-Benachrichtigungen eines anderen MyHordes-Account registriert. Ein Gerät kann nicht mehreren Accounts zugeordnet werden.', [], 'global'),
                ],
                'actions' => [
                    'add' => $trans->trans('Benachrichtigungen auf diesem Gerät erhalten', [], 'global'),
                    'registered' => $trans->trans('Dieses Gerät ist jetzt registriert und kann von MyHordes Benachrichtigungen empfangen.', [], 'global'),
                    'removed' => $trans->trans('Gerät erfolgreich entfernt. Du wirst nun keine weiteren Benachrichtigungen auf diesem Gerät erhalten.', [], 'global'),
                    'edit' => $trans->trans('Bitte gib eine neue Beschreibung für dieses Gerät ein.', [], 'global'),
                    'test_ok' => $trans->trans('Die Nachricht wurde erfolgreich übermittelt und sollte in Kürze auf dem entsprechenden Gerät erscheinen.', [], 'global'),
                    'test_expired' => $trans->trans('Es sieht so aus als ob MyHordes keine Berechtigung mehr hat, Nachrichten an dieses Gerät zu senden. Möglicherweise musst du dieses Gerät entfernen und die Einrichtung darauf erneut ausführen.', [], 'global'),
                    'test_error' => $trans->trans('Beim Senden der Nachricht ist ein Fehler aufgetreten. Fehlercode: {code}.', [], 'global'),
                ],
                'table' => [
                    'none' => $trans->trans('Es sind keine Empfänger für Benachrichtigungen registriert.', [], 'global'),
                    'device' => $trans->trans('Gerät', [], 'global'),
                    'edit' => $trans->trans('Beschreibung bearbeiten', [], 'global'),
                    'edit_icon' => $assets->getUrl('build/images/forum/edit.png'),
                    'remove' => $trans->trans('Gerät entfernen', [], 'global'),
                    'remove_icon' => $assets->getUrl('build/images/icons/small_trash_red.png'),
                    'test' => $trans->trans('Testnachricht senden', [], 'global'),
                    'test_icon' => $assets->getUrl('build/images/icons/small_talk.gif'),
                    'expired' => $trans->trans('Berechtigung zurückgezogen!', [], 'global'),
                    'expired_icon' => $assets->getUrl('build/images/icons/warning_anim.gif'),
                ],
                'settings' => [
                    'headline' => $trans->trans('Einstellungen für Push-Benachrichtigungen', [], 'soul' ),
                    'toggle' => $this->getToggleOptions( $capability, $trans )
                ]
            ]
        ]);
    }

    private function renderNotifications(NotificationSubscription $subscription): ?array {
        return [
            'id' => $subscription->getId(),
            'hash' => $subscription->getSubscriptionHash(),
            'desc' => $subscription->getDescription(),
            'expired' => $subscription->isExpired(),
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
    public function put(NotificationSubscriptionType $type, EntityManagerInterface $em, JSONRequestParser $parser, ValidatorInterface $validator, PushMessageSender $sender, TranslatorInterface $trans): JsonResponse {

        $desc = $parser->trimmed('desc', '' ) ?: 'WebPush';
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

        $subscription->setSubscriptionHash( $hash );

        $response = null;

        $notification = new PushNotification('Test Notification', [
            PushNotification::DATA => ['test' => true]
        ]);

        $attempt_encryption_padding = [
            0,
            1024,
            2048,
            2847,                                           // Max encryption for Firefox mobile
            Encryption::MAX_COMPATIBILITY_PAYLOAD_LENGTH,   // Compatibility encryption padding
            Encryption::MAX_PAYLOAD_LENGTH,                 // Default encryption padding
        ];

        $use_padding = null;
        $code = -2;
        while (!empty($attempt_encryption_padding)) {
            $use_padding = array_pop($attempt_encryption_padding);
            $responses = $sender
                ->setMaxPaddingLength($use_padding)
                ->push( $notification->createMessage(), [$subscription] );
            foreach ($responses as $r) $response = $r;

            $code = $response?->getStatusCode() ?? -1;
            // Got PAYLOAD TOO LARGE error; attempt using smaller encryption padding
            if ($code === PushResponse::PAYLOAD_SIZE_TOO_LARGE) continue;
            else break;
        }

        if ($code >= 200 && $code <= 299) {
            $em->persist( $subscription->setMaxPaddingLength($use_padding)->setSubscriptionHash( $hash )->setUser( $this->getUser() ) );
            $em->flush();
            return new JsonResponse(['subscription' => $this->renderNotifications( $subscription )]);
        } else return new JsonResponse([
            'error' => 'message',
            'message' => $trans->trans('Bei der Kommunikation mit deinem Gerät ist ein Fehler aufgetreten. Fehlercode: {code}. Bitte versuche es zu einem späteren Zeitpunkt erneut.', [
                'code' => $code
            ], 'global')
        ], status: Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    #[Route(path: '/webpush/{id}', name: 'edit_webpush', defaults: ['type' => NotificationSubscriptionType::WebPush->value], methods: ['PATCH'])]
    public function edit(NotificationSubscriptionType $type, NotificationSubscription $subscription, JSONRequestParser $parser, EntityManagerInterface $em): JsonResponse {

        if ($subscription->getType() !== $type || $subscription->getUser() !== $this->getUser())
            return new JsonResponse(status: Response::HTTP_NOT_FOUND);

        $desc = $parser->trimmed('desc', '' );
        if (!$desc) return new JsonResponse(status: Response::HTTP_BAD_REQUEST);

        $em->persist( $subscription->setDescription( mb_substr($desc, 0, 160) ) );
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
    public function test(NotificationSubscriptionType $type, NotificationSubscription $subscription, PushMessageSender $sender, TranslatorInterface $trans): JsonResponse {

        if ($subscription->getType() !== $type || $subscription->getUser() !== $this->getUser())
            return new JsonResponse(status: Response::HTTP_NOT_FOUND);

        $notification = new PushNotification($trans->trans('Testbenachrichtiung', [], 'global'), [
            PushNotification::BODY => $trans->trans('Hallo! Ich bin eine Testbenachrichtigung von MyHordes.', [], 'global')
        ]);

        $response = null;
        $responses = $sender
            ->setMaxPaddingLength(min($subscription->getMaxPaddingLength() ?? Encryption::MAX_PAYLOAD_LENGTH, Encryption::MAX_PAYLOAD_LENGTH))
            ->push( $notification->createMessage(), [$subscription] );

        foreach ($responses as $r) $response = $r;

        $code = $response?->getStatusCode() ?? -1;
        return new JsonResponse([
            'status' => $code,
            'success' => $code >= 200 && $code <= 299,
            'expired' => $response?->isExpired() ?? false
        ]);
    }
}
