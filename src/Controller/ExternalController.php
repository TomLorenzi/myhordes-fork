<?php

namespace App\Controller;

use App\Entity\BuildingPrototype;
use App\Entity\Citizen;
use App\Entity\ExternalApp;
use App\Entity\ItemPrototype;
use App\Entity\Town;
use App\Entity\User;
use App\Entity\Zone;
use App\Entity\ZonePrototype;
use App\Service\ActionHandler;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\CrowService;
use App\Service\DeathHandler;
use App\Service\GameFactory;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\LogTemplateHandler;
use App\Service\PictoHandler;
use App\Service\RandomGenerator;
use App\Service\TimeKeeperService;
use App\Service\UserHandler;
use App\Service\ZoneHandler;
use App\Translation\T;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;

class ExternalController extends InventoryAwareController
{
    protected $game_factory;
    protected $zone_handler;
    protected $item_factory;
    protected $death_handler;
    protected $entity_manager;

    /**
     * BeyondController constructor.
     * @param EntityManagerInterface $em
     * @param InventoryHandler $ih
     * @param CitizenHandler $ch
     * @param ActionHandler $ah
     * @param TimeKeeperService $tk
     * @param DeathHandler $dh
     * @param TranslatorInterface $translator
     * @param GameFactory $gf
     * @param RandomGenerator $rg
     * @param ItemFactory $if
     * @param ZoneHandler $zh
     * @param LogTemplateHandler $lh
     */
    public function __construct(
        EntityManagerInterface $em, InventoryHandler $ih, CitizenHandler $ch, ActionHandler $ah, TimeKeeperService $tk, DeathHandler $dh, PictoHandler $ph,
        TranslatorInterface $translator, GameFactory $gf, RandomGenerator $rg, ItemFactory $if, LogTemplateHandler $lh, ConfMaster $conf, ZoneHandler $zh, UserHandler $uh, CrowService $armbrust)
    {
        parent::__construct($em, $ih, $ch, $ah, $dh, $ph, $translator, $lh, $tk, $rg, $conf, $zh, $uh, $armbrust);
        $this->game_factory = $gf;
        $this->item_factory = $if;
        $this->zone_handler = $zh;
        $this->entity_manager = $em;
    }

    /**
     * @var Request
     */
    private $request;

    /**
     * @Route("/jx/disclaimer/{id}", name="disclaimer", condition="request.isXmlHttpRequest()")
     * @return Response
     */
    public function disclaimer(Request $request, int $id): Response {
        $app = $this->entity_manager->getRepository(ExternalApp::class)->find($id);
        if (!$app || $app->getTesting())
            return $this->redirect($this->generateUrl( 'initial_landing' ));

        /** @var User $user */
        $user = $this->getUser();
        $key = $user->getExternalId();

        return $this->render( 'ajax/public/disclaimer.html.twig', [
            'ex' => $app,
            'key' => $key
        ] );
    }

    /**
     * @Route("/api/x/json/{type}", name="api_x_json", defaults={"_format"="json"}, methods={"POST"})
     * @return Response
     */
    public function api_json($type = 'town'): Response
    {
        $request = Request::createFromGlobals();
        $this->request = $request;

        // Try POST data
        $app_key = $request->request->get('appkey');
        $user_key = $request->request->get('userkey');

        // Symfony 5 has a bug on treating request data.
        // If POST didn't work, access GET data.
        if (trim($app_key) == '') {
            $app_key = $request->query->get('appkey');
        }
        if (trim($user_key) == '') {
            $user_key = $request->query->get('userkey');
        }

        // If still no key, none was sent correctly.
        if (trim($app_key) == '') {
            return $this->json(['Error' => 'Access denied', 'ErrorCode' => '403', 'ErrorMessage' => 'No app key found in request.']);
        }
        if (trim($app_key) == '') {
            return $this->json(['Error' => 'Access denied', 'ErrorCode' => '403', 'ErrorMessage' => 'No user key found in request.']);
        }

        // Get the app.
        /** @var ExternalApp $app */
        $app = $this->entity_manager->getRepository(ExternalApp::class)->findOneBy(['secret' => $app_key]);
        if (!$app) {
            return $this->json(['Error' => 'Access denied', 'ErrorCode' => '403', 'ErrorMessage' => 'Access not allowed for application.']);
        }

        // Get the user.
        /** @var User $user */
        $user = $this->entity_manager->getRepository(User::class)->findOneBy(['externalId' => $user_key]);
        if (!$user) {
            return $this->json(['Error' => 'Access denied', 'ErrorCode' => '403', 'ErrorMessage' => 'Access not allowed by user.']);
        }

        // All fine, let's populate the response.
        switch ($type) {
            case 'town':
                if($user->getActiveCitizen()) {
                    $data = $this->generateData($user);
                } else {
                    $data = [
                        'Error' => "Access denied",
                        'ErrorCode' => "403",
                        'ErrorMessage' => "No incarnate user found."
                    ];
                }
                break;

            case 'items':
                $data = $this->getItemsData();
                break;

            case 'constructions':
                $data = $this->getConstructionsData();
                break;

            case 'ruins':
                $data = $this->getRuinsData();
                break;
        }
        return $this->json( $data );
    }

    private function generateData(User $user): array
    {
        try {
            $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
        } catch (Exception $e) {
            $now = date('Y-m-d H:i:s');
        }

        /** @var Citizen $citizen */
        $citizen = $user->getActiveCitizen();
        /** @var Town $town */
        $town = $citizen->getTown();
        /** @var Zone $citizen_zone */
        $citizen_zone = $citizen->getZone();

        $x_min = $x_max = $y_min = $y_max = 0;
        foreach ( $town->getZones() as $zone ) {
            /** @var Zone $zone */
            $x_min = min($zone->getX(), $x_min);
            $x_max = max($zone->getX(), $x_max);
            $y_min = min($zone->getY(), $y_min);
            $y_max = max($zone->getY(), $y_max);
        }

        // Base data.
        $data = [
            'headers' => [
                'link' => $this->request->getRequestUri(),
                'iconurl' => '',
                'avatarurl' => '',
                'author' => 'MyHordes',
                'language' => $town->getLanguage(),
                'version' => '0.1',
                'generator' => 'symfony',
            ],
            'game' => [
                'days' => $town->getDay(),
                'quarantine' => $town->getDevastated(),
                'datetime' => $now->format('Y-m-d H:i:s'),
                'id' => $town->getId(),
            ],
            'data' => [
                    'attributes' => [
                        'cache-date' => $now->format('Y-m-d H:i:s'),
                        'cache-fast' => 0,
                    ],
                    'city' => [
                        'attributes' => [
                            'city' => $town->getName(),
                            'door' => $town->getDoor(),
                            'hard' => $town->getType()->getId() == 3 ? 1 : 0,
                            'water' => $town->getWell(),
                            'chaos' => $town->getChaos(),
                            'devast' => $town->getDevastated(),
                            'x' => 0,
                            'y' => 0,
                        ],
                        'list' => [
                            'name' => 'building',
                            'items' => [],
                        ],
                        'defense' => [
                            'attributes' => [],
                        ],
                    ],
                    'bank' => [
                        'list' => [
                            'name' => 'item',
                            'items' => [],
                        ],
                    ],
                    'expeditions' => [],
                    'citizens' => [
                        'list' => [
                            'name' => 'citizen',
                            'items' => [],
                        ],
                    ],
                    'cadavers' => [
                        'list' => [
                            'name' => 'cadaver',
                            'items' => [],
                        ],
                    ],
                    'map' => [
                        'attributes' => [
                        ],
                        'list' => [
                            'name' => 'zone',
                            'items' => [],
                        ],
                    ],
                    'upgrades' => [
                        'attributes' => [
                            'total' => 0,
                        ],
                    ],
                    'estimations' => [
                        'list' => [
                            'name' => 'e',
                            'items' => [],
                        ],
                    ],
                ],
        ];

        // Add zones.
        foreach ( $town->getZones() as $zone ) {
            /** @var Zone $zone */
            $attributes = $this->zone_handler->getZoneAttributes($zone);
            $zone_data = [
                'attributes' => [
                    'x' => $zone->getX(),
                    'y' => $zone->getY(),
                    'nvt' => $zone->getDiscoveryStatus(),
                ],
            ];
            if (array_key_exists('danger', $attributes)) {
                $zone_data['attributes']['danger'] = $attributes['danger'];
            }
            if (array_key_exists('building', $attributes)) {
                $zone_data['building'] = [ 'attributes' => $attributes['building'] ];
            }
            $data['hordes']['data']['map']['list']['items'][] = $zone_data;
        }
        $data['hordes']['data']['map']['attributes']['hei'] = abs($y_min) + abs($y_max) + 1;
        $data['hordes']['data']['map']['attributes']['wid'] = abs($x_min) + abs($x_max) + 1;
        $data['hordes']['data']['map']['attributes']['offsety'] = abs($y_min);
        $data['hordes']['data']['map']['attributes']['offsetx'] = abs($x_min);

        // Add buildings.
        foreach ( $town->getBuildings() as $building ) {
            if ($building->getComplete()) {
                $building_data = [
                    'attributes' => [
                        'name' => $building->getPrototype()->getLabel(),
                        'temporary' => $building->getPrototype()->getTemp(),
                        'id' => $building->getPrototype()->getId(),
                        'img' => $building->getPrototype()->getIcon(),
                    ],
                ];
                $data['hordes']['data']['city']['list']['items'][] = $building_data;
            }
        }

        // Add bank items.
        $inventory = $town->getBank();
        foreach ( $inventory->getItems() as $item ) {
            $item_data = [
                'attributes' => [
                    'name' => $item->getPrototype()->getLabel(),
                    'count' => $item->getCount(),
                    'id' => $item->getPrototype()->getId(),
                    'img' => $item->getPrototype()->getIcon(),
                    'cat' => $item->getPrototype()->getCategory()->getLabel(),
                    'broken' => $item->getBroken(),
                ],
            ];
            $data['hordes']['data']['bank']['list']['items'][] = $item_data;
        }

        // Add citizens.
        foreach ( $town->getCitizens() as $citizen ) {
            if ($citizen->getAlive()) {
                $citizen_data = [
                    'attributes' => [
                        'dead' => 0,
                        'hero' => $citizen->getProfession()->getHeroic(),
                        'name' => $citizen->getUser()->getUsername(),
                        'avatar' => '',
                        'x' => !is_null($citizen->getZone()) ? $citizen->getZone()->getX() : 0,
                        'y' => !is_null($citizen->getZone()) ? $citizen->getZone()->getY() : 0,
                        'id' => $citizen->getId(),
                        'ban' => $citizen->getBanished(),
                        'job' => $citizen->getProfession()->getName(),
                        'out' => !is_null($citizen->getZone()),
                        'baseDef' => 0,
                    ],
                ];
                $data['hordes']['data']['citizens']['list']['items'][] = $citizen_data;
                if ($citizen == $user->getActiveCitizen()) {
                    $data['hordes']['headers']['owner'] = [
                        'citizen' => $citizen_data,
                    ];
                    if ($citizen->getZone()) {
                        $myzone = $citizen->getZone();
                        $data['hordes']['headers']['owner']['myZone'] = [
                            'attributes' => [
                                'dried' => $myzone->getDigs() > 0 ? 0 : 1,
                                'z' => $myzone->getZombies(),
                            ],
                            'list' => [
                                'name' => 'item',
                                'items' => [],
                            ],
                        ];
                        if($myzone->getPrototype()) {
                            $building = $myzone->getPrototype();
                            $data['hordes']['headers']['owner']['myZone']['attributes']['building'] = [
                                'dried' => $myzone->getRuinDigs() > 0 ? 1 : 0,
                                'id' => $building->getId(),
                                'label' => $building->getLabel()
                            ];
                        } else {
                            $data['hordes']['headers']['owner']['myZone']['attributes']['building'] = false;
                        }
                        $inventory = $myzone->getFloor();
                        foreach ( $inventory->getItems() as $item ) {
                            $item_data = [
                                'attributes' => [
                                    'name' => $item->getPrototype()->getLabel(),
                                    'count' => $item->getCount(),
                                    'id' => $item->getPrototype()->getId(),
                                    'img' => $item->getPrototype()->getIcon(),
                                    'cat' => $item->getPrototype()->getCategory()->getLabel(),
                                    'broken' => $item->getBroken(),
                                ],
                            ];
                            $data['hordes']['headers']['owner']['myZone']['list']['items'][] = $item_data;
                        }
                    }
                }
            }
            else {
                $citizen_data = [
                    'attributes' => [
                        'name' => $citizen->getUser()->getUsername(),
                        'id' => $citizen->getId(),
                        'dtype' => $citizen->getCauseOfDeath()->getId(),
                        'day' => $citizen->getSurvivedDays(),
                    ],
                ];
                $data['hordes']['data']['cadavers']['list']['items'][] = $citizen_data;
            }
        }

        return $data ?? [];
    }

    private function getItemsData(): array
    {
        // Base data.
        $data = [];

        // Add items.
        $items = $this->entity_manager->getRepository(ItemPrototype::class)->findAll();
        /** @var ItemPrototype $item */
        foreach ( $items as $item ) {
            $item_data = [
                'all' => [
                    'id' => $item->getId(),
                    'name' => $item->getName(),
                    'icon' => $item->getIcon(),
                    'category' => $item->getCategory()->getName(),
                    'parent_category' => $item->getCategory()->getParent() ? $item->getCategory()->getParent()->getName() : null,
                    'heavy' => $item->getHeavy(),
                    'decoration' => $item->getDeco(),
                    'nightwatch' => $item->getWatchpoint(),

                ],
                'de' => [
                    'label' => $item->getLabel(),
                    'description' => $item->getDescription(),
                    'category' => $item->getCategory()->getLabel(),
                    'parent_category' => $item->getCategory()->getParent() ? $item->getCategory()->getParent()->getLabel() : null,
                ],
                'fr' => [
                    'label' => $this->translator->trans($item->getLabel(), [], 'items', 'fr'),
                    'description' => $this->translator->trans($item->getDescription(), [], 'items', 'fr'),
                    'category' => $this->translator->trans($item->getCategory()->getLabel(), [], 'items', 'fr'),
                    'parent_category' => $item->getCategory()->getParent() ? $this->translator->trans($item->getCategory()->getParent()->getLabel(), [], 'items', 'fr') : null,
                ],
                'en' => [
                    'label' => $this->translator->trans($item->getLabel(), [], 'items', 'en'),
                    'description' => $this->translator->trans($item->getDescription(), [], 'items', 'en'),
                    'category' => $this->translator->trans($item->getCategory()->getLabel(), [], 'items', 'en'),
                    'parent_category' => $item->getCategory()->getParent() ? $this->translator->trans($item->getCategory()->getParent()->getLabel(), [], 'items', 'en') : null,
                ],
                'es' => [
                    'label' => $this->translator->trans($item->getLabel(), [], 'items', 'es'),
                    'description' => $this->translator->trans($item->getDescription(), [], 'items', 'es'),
                    'category' => $this->translator->trans($item->getCategory()->getLabel(), [], 'items', 'es'),
                    'parent_category' => $item->getCategory()->getParent() ? $this->translator->trans($item->getCategory()->getParent()->getLabel(), [], 'items', 'es') : null,
                ],
            ];
            $data[$item->getId()] = $item_data;
        }

        return $data ?? [];
    }

    private function getConstructionsData(): array
    {
        // Base data.
        $data = [];

        // Add constructions.
        $constructions = $this->entity_manager->getRepository(BuildingPrototype::class)->findAll();
        /** @var BuildingPrototype $item */
        foreach ( $constructions as $item ) {
            $item_data = [
                'all' => [
                    'id' => $item->getId(),
                    'name' => $item->getName(),
                    'icon' => $item->getIcon(),
                    'blueprint' => $item->getBlueprint(),
                    'ap' => $item->getAp(),
                    'defense' => $item->getDefense(),
                    'temporary' => $item->getTemp(),
                    'max_level' => $item->getMaxLevel(),
                    'parent_id' => $item->getParent() ? $item->getParent()->getId() : null,

                ],
                'de' => [
                    'label' => $item->getLabel(),
                    'description' => $item->getDescription(),
                ],
                'fr' => [
                    'label' => $this->translator->trans($item->getLabel(), [], 'buildings', 'fr'),
                    'description' => $this->translator->trans($item->getDescription(), [], 'buildings', 'fr'),
                ],
                'en' => [
                    'label' => $this->translator->trans($item->getLabel(), [], 'buildings', 'en'),
                    'description' => $this->translator->trans($item->getDescription(), [], 'buildings', 'en'),
                ],
                'es' => [
                    'label' => $this->translator->trans($item->getLabel(), [], 'buildings', 'es'),
                    'description' => $this->translator->trans($item->getDescription(), [], 'buildings', 'es'),
                ],
            ];
            $data[$item->getId()] = $item_data;
        }

        return $data ?? [];
    }

    private function getRuinsData(): array
    {
        // Base data.
        $data = [];

        // Add ruins.
        $ruins = $this->entity_manager->getRepository(ZonePrototype::class)->findAll();
        /** @var ZonePrototype $item */
        foreach ( $ruins as $item ) {
            $item_data = [
                'all' => [
                    'id' => $item->getId(),
                    'icon' => $item->getIcon(),
                    'camping_level' => $item->getCampingLevel(),
                    'min_distance' => $item->getMinDistance(),
                    'max_distance' => $item->getMaxDistance(),
                ],
                'de' => [
                    'label' => $item->getLabel(),
                    'description' => $item->getDescription(),
                ],
                'fr' => [
                    'label' => $this->translator->trans($item->getLabel(), [], 'game', 'fr'),
                    'description' => $this->translator->trans($item->getDescription(), [], 'game', 'fr'),
                ],
                'en' => [
                    'label' => $this->translator->trans($item->getLabel(), [], 'game', 'en'),
                    'description' => $this->translator->trans($item->getDescription(), [], 'game', 'en'),
                ],
                'es' => [
                    'label' => $this->translator->trans($item->getLabel(), [], 'game', 'es'),
                    'description' => $this->translator->trans($item->getDescription(), [], 'game', 'es'),
                ],
            ];
            $data[$item->getId()] = $item_data;
        }

        return $data ?? [];
    }
}
