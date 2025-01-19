<?php

namespace App\Controller\REST\Admin;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\ServerSettings;
use App\Enum\ServerSetting;
use App\Service\JSONRequestParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/rest/v1/admin/settings', name: 'rest_admin_settings_', condition: "request.headers.get('Accept') === 'application/json'")]
#[IsGranted('ROLE_ADMIN')]
#[GateKeeperProfile('skip')]
class ServerSettingsController extends CustomAbstractCoreController
{
    /**
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @return JsonResponse
     * @throws \Exception
     */
    #[Route(path: '', name: 'mod', methods: ['PATCH'])]
    public function modify(EntityManagerInterface $em, JSONRequestParser $parser): JsonResponse {
        if (!$parser->has_all(['setting','value'], false))
            return new JsonResponse([], Response::HTTP_UNPROCESSABLE_ENTITY);

        $setting = ServerSetting::tryFrom( $parser->get_int('setting') );
        if (!$setting || $setting->type() === 'void') return new JsonResponse([], Response::HTTP_BAD_REQUEST);

        $settingObject = $em->getRepository(ServerSettings::class)->findOneBy(['setting' => $setting->value]) ?? (new ServerSettings())->setSetting( $setting );
        $em->persist( $settingObject->setData( $setting->encodeValue( $parser->get('value') ) ) );
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}
