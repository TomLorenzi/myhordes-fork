<?php

namespace App\Controller\REST;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\TownClass;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/rest/v1/town-creator", name="rest_town_creator_", condition="request.headers.get('Accept') === 'application/json'")
 * @IsGranted("ROLE_USER")
 * @GateKeeperProfile("skip")
 */
class TownCreatorController extends CustomAbstractCoreController
{

    /**
     * @Route("", name="base", methods={"GET"})
     * @return JsonResponse
     */
    public function index(): JsonResponse {

        return new JsonResponse([
            'strings' => [
                'common' => [
                    'need_selection' => "[ {$this->translator->trans('Bitte auswählen', [], 'global')} ]",
                ],

                'head' => [
                    'section' => $this->translator->trans('Grundlegende Einstellungen', [], 'ghost'),

                    'town_name' => $this->translator->trans('Name der Stadt', [], 'ghost'),
                    'town_name_hint' => $this->translator->trans('Leer lassen, um Stadtnamen automatisch zu generieren.', [], 'ghost'),

                    'lang' => $this->translator->trans('Sprache', [], 'global'),
                    'langs' => array_merge(
                        array_map(fn($lang) => ['code' => $lang['code'], 'label' => $this->translator->trans( $lang['label'], [], 'global' )], $this->generatedLangs),
                        [ [ 'code' => 'multi', 'label' => $this->translator->trans('Mehrsprachig', [], 'global') ] ]
                    ),

                    'type' => $this->translator->trans('Stadttyp', [], 'ghost'),
                    'base' => $this->translator->trans('Vorlage', [], 'ghost'),

                    'settings' => [
                        'section' => $this->translator->trans('Einstellungen', [], 'ghost'),

                        'disable_api' => $this->translator->trans('Externe APIs deaktivieren', [], 'ghost'),
                        'disable_api_help' => $this->translator->trans('Externe Anwendungen für diese Stadt deaktivieren.', [], 'ghost'),

                        'alias' => $this->translator->trans('Bürger-Aliase', [], 'ghost'),
                        'alias_help' => $this->translator->trans('Ermöglicht dem Bürger, einen Alias anstelle seines üblichen Benutzernamens zu wählen.', [], 'ghost'),

                        'ffa' => $this->translator->trans('Seelenpunkt-Beschränkung deaktivieren', [], 'ghost'),
                        'ffa_help' => $this->translator->trans('Jeder Spieler kann dieser Stadt beitreten, unabhängig davon wie viele Seelenpunkte er oder sie bereits erworben hat.', [], 'ghost'),
                    ]
                ],

                'difficulty' => [
                    'section' => $this->translator->trans('Schwierigkeitslevel', [], 'ghost'),

                    'well' => $this->translator->trans('Wasservorrat', [], 'ghost'),
                    'well_help' => $this->translator->trans('Normale Werte liegen zwischen 100 und 180. Wert ist in Pandämonium-Städten reduziert. Maximum ist 300', [], 'ghost'),
                    'well_presets' => [
                        ['value' => 'normal', 'label' => $this->translator->trans('Normal', [], 'ghost')],
                        ['value' => 'low', 'label' => $this->translator->trans('Gering', [], 'ghost')],
                        ['value' => '_fixed', 'label' => $this->translator->trans('Eigener Wert', [], 'ghost')],
                        ['value' => '_range', 'label' => $this->translator->trans('Eigener Bereich', [], 'ghost')],
                    ]
                ]
            ],
            'config' => [
                'elevation' => $this->getUser()->getRightsElevation(),
                'default_lang' => $this->getUserLanguage()
            ]

        ]);
    }


    /**
     * @Route("/town-types", name="town-types", methods={"GET"})
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function town_types(EntityManagerInterface $em): JsonResponse {

        $towns = $em->getRepository(TownClass::class)->findAll();

        return new JsonResponse(array_map(
            function(TownClass $town) {

                return [
                    'id' => $town->getId(),
                    'preset' => $town->getHasPreset(),
                    'name' => $this->translator->trans( $town->getLabel(), [], 'game' ),
                    'help' => $this->translator->trans( $town->getHelp(), [], 'game' ),
                ];

            }, $em->getRepository(TownClass::class)->findAll() )
        );
    }

    /**
     * @Route("/town-rules/{id}", name="town-rules", methods={"GET"})
     * @param TownClass $townClass
     * @return JsonResponse
     */
    public function town_type_rules(TownClass $townClass): JsonResponse {
        if ($townClass->getHasPreset()) {

            $preset = $this->conf->getTownConfigurationByType($townClass)->getData();

            $preset['wellPreset'] = $townClass->getName() === TownClass::HARD ? 'low' : 'normal';
            unset( $preset['well'] );

            return new JsonResponse( $preset );
        }

        return new JsonResponse([], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

}
