<?php

namespace App\Controller;

use App\Entity\Citizen;
use App\Entity\ExternalApp;
use App\Entity\Town;
use App\Entity\User;
use App\Entity\Zone;
use App\Exception\DynamicAjaxResetException;
use App\Service\ActionHandler;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\DeathHandler;
use App\Service\ErrorHelper;
use App\Service\GameFactory;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\RandomGenerator;
use App\Service\TimeKeeperService;
use App\Service\UserFactory;
use App\Response\AjaxResponse;
use App\Service\ZoneHandler;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validation;
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
        EntityManagerInterface $em, InventoryHandler $ih, CitizenHandler $ch, ActionHandler $ah, TimeKeeperService $tk, DeathHandler $dh,
        TranslatorInterface $translator, GameFactory $gf, RandomGenerator $rg, ItemFactory $if, ZoneHandler $zh, LogTemplateHandler $lh, ConfMaster $conf)
    {
        parent::__construct($em, $ih, $ch, $ah, $dh, $translator, $lh, $tk, $rg, $conf, $zh);
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
     * @Route("/jx/disclaimer/{id}", name="disclaimer")
     * @return Response
     */
    public function disclaimer(Request $request, int $id): Response {
        $app = $this->entity_manager->getRepository(ExternalApp::class)->find($id);
        if (!$app) {
            $error = true;
        }

        return $this->render( 'ajax/public/disclaimer.html.twig', $this->addDefaultTwigArgs(null, [
            'ex' => $app,
            'error' => $error ?? false,
        ]) );
    }

    /**
     * @Route("/api/x/json", name="api_x_json")
     * @return Response
     */
    public function api_json(Request $request): Response
    {
        $this->request = $request;
        $data = $this->generateData();
        $test_array = [
            'citizen' => $this->getUser()->getUsername(),
        ];
        return $this->json( $data );
    }

    /**
     * @Route("/api/x/xml", name="api_x_xml")
     * @return Response
     */
    public function api_xml(Request $request): Response
    {
        $this->request = $request;
        $test_array = [
            'citizen' => $this->getUser()->getUsername(),
        ];
        return $this->json( $test_array );
    }

    private function generateData(): array
    {
        try {
            $now = new DateTime('now', new DateTimeZone('America/New_York'));
        } catch (Exception $e) {
            $now = date('Y-m-d H:i:s');
        }
        /** @var User $user */
        $user = $this->getUser();
        /** @var Citizen $citizen */
        $citizen = $user->getActiveCitizen();
        /** @var Town $town */
        $town = $citizen->getTown();
        /** @var Zone $citizen_zone */
        $citizen_zone = $citizen->getZone();

        // Base data.
        $data = [
            'hordes' => [
                'headers' => [
                    'attributes' => [
                        'link' => $this->request->getRequestUri(),
                        'iconurl' => '',
                        'avatarurl' => '',
                        'secure' => 0,
                        'author' => 'MyHordes',
                        'language' => $town->getLanguage(),
                        'version' => '0.1',
                        'generator' => 'symfony',
                    ],
                    'game' => [
                        'attributes' => [
                            'days' => $town->getDay(),
                            'quarantine' => $town->getDevastated(),
                            'datetime' => $now->format('Y-m-d H:i:s'),
                            'id' => $town->getId(),
                        ],
                    ],
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
                            'x' => 0, //TODO get Town Offset
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

}
