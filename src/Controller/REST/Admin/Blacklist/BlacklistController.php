<?php

namespace App\Controller\REST\Admin\Blacklist;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\AntiSpamDomains;
use App\Entity\User;
use App\Enum\DomainBlacklistType;
use App\Service\JSONRequestParser;
use App\Service\User\UserAccountService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/rest/v1/admin/spam', name: 'rest_admin_blacklist_spam_', condition: "request.headers.get('Accept') === 'application/json'")]
#[IsGranted('ROLE_CROW')]
#[GateKeeperProfile('skip')]
class BlacklistController extends CustomAbstractCoreController
{
    private function decode(JSONRequestParser $parser, ?DomainBlacklistType &$type, ?string &$value): bool {

        if (!$parser->has_all(['type','value'], true)) return false;
        $type = $parser->get_enum( 'type', DomainBlacklistType::class );
        if (!$type || $type === DomainBlacklistType::EmailDomain)
            return false;

        $value = $type->convert( $parser->get('value') );
        return true;
    }

    /**
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @param TranslatorInterface $translator
     * @return JsonResponse
     */
    #[Route(path: '/identifier', name: 'check', methods: ['POST'])]
    public function check(EntityManagerInterface $em, JSONRequestParser $parser, TranslatorInterface $translator): JsonResponse {
        if (!$this->decode( $parser, $type, $value ))
            return new JsonResponse([], Response::HTTP_UNPROCESSABLE_ENTITY);

        $entity = $em->getRepository( AntiSpamDomains::class )->findOneBy([ 'type' => $type, 'domain' => $value ]);
        if (!$entity) $this->addFlash('error', $translator->trans('Nicht gefunden.', [], 'admin'));
        else $this->addFlash('notice', $translator->trans('Eintrag gefunden.', [], 'admin'));

        return new JsonResponse([ 'success' => true ]);
    }

    /**
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @param TranslatorInterface $translator
     * @return JsonResponse
     */
    #[Route(path: '/identifier', name: 'add', methods: ['PUT'])]
    #[IsGranted('ROLE_SUB_ADMIN')]
    public function add(EntityManagerInterface $em, JSONRequestParser $parser, TranslatorInterface $translator): JsonResponse {
        if (!$this->decode( $parser, $type, $value ))
            return new JsonResponse([], Response::HTTP_UNPROCESSABLE_ENTITY);

        $entity =
            $em->getRepository( AntiSpamDomains::class )->findOneBy([ 'type' => $type, 'domain' => $value ]) ??
            (new AntiSpamDomains())->setType( $type )->setDomain( $value );

        $em->persist( $entity->setUntil(null) );
        $em->flush();
        $this->addFlash('notice', $translator->trans('Erfolgreich hinzugefügt.', [], 'admin'));

        return new JsonResponse([ 'success' => true ]);
    }

    /**
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @param TranslatorInterface $translator
     * @return JsonResponse
     */
    #[Route(path: '/identifier', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_SUB_ADMIN')]
    public function delete(EntityManagerInterface $em, JSONRequestParser $parser, TranslatorInterface $translator): JsonResponse {
        if (!$this->decode( $parser, $type, $value ))
            return new JsonResponse([], Response::HTTP_UNPROCESSABLE_ENTITY);

        $entity = $em->getRepository( AntiSpamDomains::class )->findOneBy([ 'type' => $type, 'domain' => $value ]);
        if (!$entity) {
            $this->addFlash('error', $translator->trans('Nicht gefunden.', [], 'admin'));
            return new JsonResponse([ 'success' => true ]);
        }

        $em->remove( $entity );
        $em->flush();
        $this->addFlash('notice', $translator->trans('Erfolgreich entfernt.', [], 'admin'));

        return new JsonResponse([ 'success' => true ]);
    }

    /**
     * @param User $user
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @param TranslatorInterface $translator
     * @param UserAccountService $service
     * @return JsonResponse
     */
    #[Route(path: '/ips/user/{id}', name: 'add_user_ips', methods: ['PUT'])]
    public function addUserIPs(User $user, EntityManagerInterface $em, JSONRequestParser $parser, TranslatorInterface $translator, UserAccountService $service): JsonResponse {

        foreach ($service->getKnownIPsForUser($user) as $ip) {
            $goal = new DateTime('+48hours');
            $entity =
                $em->getRepository( AntiSpamDomains::class )->findOneBy([ 'type' => DomainBlacklistType::IPAddress, 'domain' => DomainBlacklistType::IPAddress->convert($ip) ]) ??
                (new AntiSpamDomains())->setType( DomainBlacklistType::IPAddress )->setDomain( DomainBlacklistType::IPAddress->convert($ip) )->setUntil( $goal );

            if ($entity->getUntil() !== null && $entity->getUntil() < $goal)
                $entity->setUntil($goal);

            $em->persist( $entity );
        }

        $em->flush();
        $this->addFlash('notice', $translator->trans('Erfolgreich hinzugefügt.', [], 'admin'));

        return new JsonResponse([ 'success' => true ]);
    }
}
