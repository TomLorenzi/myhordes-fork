<?php
namespace App\Controller;

use App\Entity\AwardPrototype;
use App\Entity\Building;
use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\ExpeditionRoute;
use App\Entity\ExternalApp;
use App\Entity\Gazette;
use App\Entity\GazetteLogEntry;
use App\Entity\Item;
use App\Entity\LogEntryTemplate;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\Town;
use App\Entity\User;
use App\Entity\ZombieEstimation;
use App\Entity\Zone;
use App\Entity\ZoneTag;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\TownHandler;
use App\Service\ZoneHandler;
use App\Structures\TownConf;
use App\Structures\TownDefenseSummary;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\Criteria;
use Exception;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;

class ExternalXML2Controller extends ExternalController {

    /**
     * Check if the appkey and userkey has been given
     *
     * @return Response|User Error or the user linked to the user_key
     */
    private function check_keys() {
        $request = Request::createFromGlobals();

        // Try POST data
        $app_key = $request->query->get('appkey');
        $user_key = $request->query->get('userkey');

        // Symfony 5 has a bug on treating request data.
        // If POST didn't work, access GET data.
        if (trim($app_key) == '') {
            $app_key = $request->request->get('appkey');
        }
        if (trim($user_key) == '') {
            $user_key = $request->request->get('userkey');
        }

        // If still no key, none was sent correctly.
        if (trim($app_key) == '') {
            return new Response($this->arrayToXml( ["Error" => "Access Denied", "ErrorCode" => "403", "ErrorMessage" => "No app key found in request."], '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
        }

        if(trim($user_key) == '')
            return new Response($this->arrayToXml( ["Error" => "Access Denied", "ErrorCode" => "403", "ErrorMessage" => "No user key found in request."], '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));

        // Get the app.
        /** @var ExternalApp $app */
        $app = $this->entity_manager->getRepository(ExternalApp::class)->findOneBy(['secret' => $app_key]);
        if (!$app) {
            return new Response($this->arrayToXml( ["Error" => "Access Denied", "ErrorCode" => "403", "ErrorMessage" => "Access not allowed for application."], '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
        }

        // Get the user.
        /** @var User $user */
        $user = $this->entity_manager->getRepository(User::class)->findOneBy(['externalId' => $user_key]);
        if (!$user) {
            return new Response($this->arrayToXml( ["Error" => "Access Denied", "ErrorCode" => "403", "ErrorMessage" => "Access not allowed for user."], '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
        }

        return $user;
    }

    /**
     * @Route("/api/x/v2/xml", name="api_x2_xml", defaults={"_format"="xml"}, methods={"GET","POST"})
     * @return Response
     */
    public function api_xml(): Response {
        $user = $this->check_keys();

        if($user instanceof Response)
            return $user;

        $endpoints = [];
        $endpoints['user'] = $this->generateUrl('api_x2_xml_user', [], UrlGeneratorInterface::ABSOLUTE_URL);
        if ($user->getAliveCitizen()) $endpoints['town'] = $this->generateUrl("api_x2_xml_town", ['townId' => $user->getAliveCitizen()->getTown()->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $array = [
            "endpoint_list" => $endpoints
        ];



        // All fine, let's populate the response.
        $response = new Response($this->arrayToXml( $array, '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
        $response->headers->set('Content-Type', 'text/xml');
        return $response;
    }

    /**
     * @Route("/api/x/v2/xml/user", name="api_x2_xml_user", defaults={"_format"="xml"}, methods={"GET","POST"})
     * @param $trans TranslatorInterface
     * @param $zh ZoneHandler
     * @param $ch CitizenHandler
     * @return Response
     */
    public function api_xml_user(TranslatorInterface $trans, ZoneHandler $zh, CitizenHandler $ch): Response {
        $user = $this->check_keys();

        if($user instanceof Response)
            return $user;

        try {
            $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
        } catch (Exception $e) {
            $now = date('Y-m-d H:i:s');
        }

        $request = Request::createFromGlobals();

        // Try POST data
        $language = $request->query->get('lang');

        if (trim($language) == '') {
            $language = $request->request->get('lang');
        }

        if(!in_array($language, ['en', 'fr', 'de', 'es', 'all'])) {
            $language = $user->getLanguage() ?? 'de';
        }

        $trans->setLocale($language);

        // Base data.
        $data = $this->getHeaders($language);

        /** @var Citizen $citizen */
        $citizen = $user->getActiveCitizen();
        if($citizen !== null){
            /** @var Town $town */
            $town = $citizen->getTown();
            $data['hordes']['headers']['owner'] = [
                'citizen' => [
                    "attributes" => [
                        'dead' => intval(!$citizen->getAlive()),
                        'hero' => $citizen->getProfession()->getHeroic(),
                        'name' => $user->getUsername(),
                        'avatar' => $user->getAvatar() !== null ? $user->getId() . "/" . $user->getAvatar()->getFilename() . "." . $user->getAvatar()->getFormat() : "",
                        'x' => $citizen->getZone() !== null ? $citizen->getZone()->getX() : '0',
                        'y' => $citizen->getZone() !== null ? $citizen->getZone()->getY() : '0',
                        'id' => $user->getId(),
                        'ban' => $citizen->getBanished(),
                        'job' => $citizen->getProfession()->getName(),
                        'out' => intval($citizen->getZone() !== null),
                        'baseDef' => '0'
                    ],
                    "value" => $citizen->getHome()->getDescription()
                ],
                "myZone" => []
            ];
            /** @var Zone $zone */
            $zone = $citizen->getZone();
            if($zone !== null){
                $cp = 0;
                foreach ($zone->getCitizens() as $c)
                    if ($c->getAlive())
                        $cp += $ch->getCP($c);
                $data['hordes']['headers']['owner']['myZone'] = [
                    "attributes" => [
                        'dried' => intval($zone->getDigs() == 0),
                        'h' => $cp,
                        'z' => $zone->getZombies()
                    ],
                    'list' => [
                        'name' => 'item',
                        'items' => []
                    ]
                ];
                
                /** @var Item $item */
                foreach($zone->getFloor()->getItems() as $item) {
                    $data['hordes']['headers']['owner']['myZone']['list']['items'][] = [
                        'attributes' => [
                            'name' => $trans->trans($item->getPrototype()->getLabel(), [], 'items'),
                            'count' => 1,
                            'id' => $item->getPrototype()->getId(),
                            'cat' => $item->getPrototype()->getCategory()->getName(),
                            'img' => $this->asset->getUrl( "build/images/item/item_{$item->getPrototype()->getIcon()}.gif"), // TODO: Fix img name to reflect real generated name
                            'broken' => intval($item->getBroken())
                        ]
                    ];
                }
            }
            $data['hordes']['headers']['game'] = [
                'attributes' => [
                    'days' => $town->getDay(),
                    'quarantine' => $town->getAttackFails() >= 3,
                    'datetime' => $now->format('Y-m-d H:i:s'),
                    'id' => $town->getId(),
                ],
            ];
        
            $data['hordes']['data'] = [
                'rewards' => [
                    'list' => [
                        'name' => 'r', 
                        'items' => []
                    ]
                ],
                'maps' => [
                    'list' => [
                        'name' => 'm', 
                        'items' => []
                    ]
                ]
            ];
    
            $pictos = $this->entity_manager->getRepository(Picto::class)->findNotPendingByUser($user);
    
            foreach ($pictos as $picto){
                /** @var Picto $picto */
                $node = [
                    'attributes' => [
                        'name' => $trans->trans($picto['label'], [], 'game'),
                        'rare' => intval($picto['rare']),
                        'n' => $picto['c'],
                        'img' => $this->asset->getUrl( "build/images/pictos/{$picto['icon']}.gif"), // TODO: Fix img name to reflect real generated name
                        'desc' => $trans->trans($picto['description'], [], "game"),
                    ],
                    'list' => [
                        'name' => 'title',
                        'items' => []
                    ],
                ];
                $criteria = new Criteria();
                $criteria->where($criteria->expr()->gte('unlockQuantity', $picto['c']));
                $criteria->where($criteria->expr()->eq('associatedPicto', $this->entity_manager->getRepository(PictoPrototype::class)->find($picto['id'])));
                $titles = $this->entity_manager->getRepository(AwardPrototype::class)->matching($criteria);
                foreach($titles as $title){
                    /** @var AwardPrototype $title */
                    $node['list']['items'][] = [
                        'attributes' => [
                            'name' => $trans->trans($title->getTitle(), [], 'game')
                        ]
                    ];
                }
                $data['hordes']['data']['rewards']['list']['items'][] = $node;
            }
    
            foreach($user->getPastLifes() as $pastLife){
                /** @var CitizenRankingProxy $pastLife */
                if($pastLife->getCitizen() && $pastLife->getCitizen()->getAlive()) continue;
                $data['hordes']['data']['maps']['list']['items'][] = [
                    'attributes' => [
                        'name' => $pastLife->getTown()->getName(),
                        'season' => $pastLife->getTown()->getSeason() ? $pastLife->getTown()->getSeason()->getNumber() : 0,
                        'score' => $pastLife->getPoints(),
                        'd' => $pastLife->getDay(),
                        'id' => $pastLife->getTown()->getId(),
                        'v1' => 0,
                        'origin' => ($pastLife->getTown()->getSeason() && $pastLife->getTown()->getSeason()->getNumber() === 0)
                            ? strtolower($pastLife->getTown()->getLanguage()) . "-{$pastLife->getTown()->getSeason()->getSubNumber()}"
                            : '',
                    ], 
                    'value' => $pastLife->getLastWords()
                ];
            }
        } else {
            $data['hordes']['error']['attributes'] = ['code' => "not_in_game"];
            $data['hordes']['status']['attributes'] = ['open' => "1", "msg" => ""];
        }

        $response = new Response($this->arrayToXml( $data['hordes'], '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
        $response->headers->set('Content-Type', 'text/xml');
        return $response;
    }

    /**
     * @Route("/api/x/v2/xml/town/{townId}", name="api_x2_xml_town", defaults={"_format"="xml"}, methods={"GET","POST"})
     * @param int $townId
     * @param $trans TranslatorInterface
     * @param $zh ZoneHandler
     * @param $ch CitizenHandler
     * @param TownHandler $th
     * @param ConfMaster $conf
     * @return Response
     */
    public function api_xml_town(int $townId, TranslatorInterface $trans, ZoneHandler $zh, CitizenHandler $ch, TownHandler $th, ConfMaster $conf): Response {
        $user = $this->check_keys();

        if($user instanceof Response)
            return $user;

        try {
            $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
        } catch (Exception $e) {
            $now = date('Y-m-d H:i:s');
        }

        /** @var Town $town */
        $town = $this->entity_manager->getRepository(Town::class)->find($townId);
        if($town === null)
            return new Response($this->arrayToXml( ["Error" => "Not Found", "ErrorCode" => "404", "ErrorMessage" => "Town not found"], '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));


        /** @var User $user */
        if (!$user->getAliveCitizen() || $user->getAliveCitizen()->getTown() !== $town )
            return new Response($this->arrayToXml( ["Error" => "Access Denied", "ErrorCode" => "403", "ErrorMessage" => "User is not within this towns domain"], '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));

        $request = Request::createFromGlobals();

        // Try POST data
        $language = $request->query->get('lang');

        if (trim($language) == '') {
            // No POST data, we use GET datas
            $language = $request->request->get('lang');
        }

        if(!in_array($language, ['en', 'fr', 'de', 'es', 'all'])) {
            // Still no data, we use the user lang, or the deutsch as latest fallback
            $language = $user->getLanguage() ?? 'de';
        }

        if($language !== 'all') {
            $trans->setLocale($language);
        }

        // Base data.
        $data = $this->getHeaders($language);

        /** @var Citizen $citizen */
        $citizen = $user->getAliveCitizen();

        if($citizen !== null) {
            $activeOffset = $town->getMapOffset();
            $data['hordes']['headers']['owner'] = [
                'citizen' => [
                    "attributes" => [
                        'dead' => intval(!$citizen->getAlive()),
                        'hero' => $citizen->getProfession()->getHeroic(),
                        'name' => $user->getUsername(),
                        'avatar' => $user->getAvatar()!= null ? $user->getId() . "/" . $user->getAvatar()->getFilename() . "." . $user->getAvatar()->getFormat() : '', // TODO: Fix avatar URL
                        'x' => $citizen->getZone() !== null ? $activeOffset['x'] + $citizen->getZone()->getX() : $activeOffset['x'],
                        'y' => $citizen->getZone() !== null ? $activeOffset['y'] - $citizen->getZone()->getY() : $activeOffset['y'],
                        'id' => $citizen->getUser()->getId(),
                        'ban' => $citizen->getBanished(),
                        'job' => $citizen->getProfession()->getName(),
                        'out' => intval($citizen->getZone() !== null),
                        'baseDef' => '0'
                    ],
                    "value" => $citizen->getHome()->getDescription()
                ]
            ];

            /** @var Zone $zone */
            $zone = $citizen->getZone();
            if($zone !== null){
                $cp = 0;
                foreach ($zone->getCitizens() as $c)
                    if ($c->getAlive())
                        $cp += $ch->getCP($c);

                $data['hordes']['headers']['owner']['myZone'] = [
                    "attributes" => [
                        'dried' => intval($zone->getDigs() == 0),
                        'h' => $cp,
                        'z' => $zone->getZombies()
                    ],
                    'list' => [
                        'name' => 'item',
                        'items' => []
                    ]
                ];
                /** @var Item $item */
                foreach($zone->getFloor()->getItems() as $item) {
                    $data['hordes']['headers']['owner']['myZone']['list']['items'][] = [
                        'attributes' => [
                            'name' => $trans->trans($item->getPrototype()->getLabel(), [], 'items'),
                            'count' => 1,
                            'id' => $item->getPrototype()->getId(),
                            'cat' => $item->getPrototype()->getCategory()->getName(),
                            'img' => $this->asset->getUrl( "build/images/item/item_{$item->getPrototype()->getIcon()}.gif"),
                            'broken' => intval($item->getBroken())
                        ]
                    ];
                }
            }
            $data['hordes']['headers']['game'] = [
                'attributes' => [
                    'days' => $town->getDay(),
                    'quarantine' => intval($town->getAttackFails() >= 3),
                    'datetime' => $now->format('Y-m-d H:i:s'),
                    'id' => $town->getId(),
                ],
            ];

            $offset = $town->getMapOffset();

            /** @var TownDefenseSummary $def */
            $def = new TownDefenseSummary();
            $th->calculate_town_def($town, $def);

            $data['hordes']['data'] = [
                'city' => [
                    'attributes' => [
                        'city' => $town->getName(),
                        'door' => intval($town->getDoor()),
                        'water' => $town->getWell(),
                        'chaos' => intval($town->getChaos()),
                        'devast' => intval( $town->getDevastated()),
                        'hard' => intval($town->getType()->getName() === 'panda'),
                        'x' => $offset['x'],
                        'y' => $offset['y'],
                        'region' => $town->getLanguage()
                    ],
                    'list' => [
                        'name' => 'building',
                        'items' => [

                        ]
                    ],
                    'defense' => [
                        'attributes' => [
                            'base' => 10,
                            'items' => $def->item_defense,
                            'citizen_guardians' => $def->guardian_defense,
                            'citizen_homes' => $def->house_defense,
                            'upgrades' => $def->building_def_vote,
                            'buildings' => $def->building_def_base,
                            'total' => $def->sum(),
                            'itemsMul' => $th->getBuilding($town, 'item_meca_parts_#00', true) ? (1.0 + 1+$th->getBuilding($town, 'item_meca_parts_#00', true)->getLevel()) * 0.5 : 1.0
                        ]
                    ]
                ],
                'bank' => [
                    'list' => [
                        'name' => 'item', 
                        'items' => [

                        ]
                    ]
                ],
                'expeditions' => [
                    'list' => [
                        'name' => 'expedition',
                        'items' => []
                    ]
                ],
                'citizens' => [
                    'list' => [
                        'name' => 'citizen', 
                        'items' => [
                            
                        ]
                    ]
                ],
                'cadavers' => [
                    'list' => [
                        'name' => 'cadaver', 
                        'items' => [
                            
                        ]
                    ]
                ],
                'map' => [
                    'attributes' => [
                        'hei' => $town->getMapSize(),
                        'wid' => $town->getMapSize()
                    ],
                    'list' => [
                        'name' => 'zone', 
                        'items' => [
                            
                        ]
                    ]
                ],
                'upgrades' => [
                    'attributes' => [
                        'total' => 0,
                    ],
                    'list' => [
                        'name' => 'up', 
                        'items' => [
                            
                        ]
                    ]
                ],
                'estimations' => [
                    'list' => [
                        'name' => 'e', 
                        'items' => [
                            
                        ]
                    ]
                ]
            ];

            // Town buildings
            foreach($town->getBuildings() as $building){
                /** @var Building $building */
                if(!$building->getComplete()) continue;

                $buildingXml = [
                    'attributes' => [
                        'name' => $trans->trans($building->getPrototype()->getLabel(), [], 'buildings'),
                        'temporary' => intval($building->getPrototype()->getTemp()),
                        'id' => $building->getPrototype()->getId(),
                        'img' => $this->asset->getUrl("build/images/building/{$building->getPrototype()->getIcon()}.gif")
                    ], 
                    'value' => $trans->trans($building->getPrototype()->getDescription(), [], 'buildings')
                ];


                if($building->getPrototype()->getParent() !== null) {
                    $buildingXml['attributes']['parent'] = $building->getPrototype()->getParent()->getId();
                }

                $data['hordes']['data']['city']['list']['items'][] = $buildingXml;

                if($building->getPrototype()->getMaxLevel() > 0 && $building->getLevel() > 0){
                    $data['hordes']['data']['upgrades']['attributes']['total'] += $building->getLevel();
                    $data['hordes']['data']['upgrades']['list']['items'][] = [
                        'attributes' => [
                            'name' => $trans->trans($building->getPrototype()->getLabel(), [], 'buildings'),
                            'level' => $building->getLevel(),
                            'buildingid' => $building->getPrototype()->getId(),
                        ], 
                        'value' => $trans->trans($building->getPrototype()->getUpgradeTexts()[$building->getLevel() - 1], [], 'buildings')
                    ];
                }
            }

            // Current gazette
            /** @var Gazette $gazette */
            $gazette = $town->findGazette( $town->getDay() );
            if ($gazette !== null) {
                $gazette_logs = $this->entity_manager->getRepository(GazetteLogEntry::class)->findByFilter($gazette);
                $text = '';
                while (count($gazette_logs) > 0) {
                    $text .= '<p>' . $this->parseGazetteLog(array_shift($gazette_logs)) . '</p>';
                }
                $data['hordes']['data']['city']['news'] = [
                    'attributes' => [
                        'z' => $gazette->getAttack(),
                        'def' => $gazette->getDefense()
                    ], 
                    'content' => $text
                ];
            }

            // The town bank
            foreach($town->getBank()->getItems() as $bankItem){
                /** @var Item $bankItem */
                $data['hordes']['data']['bank']['list']['items'][] = [
                    'attributes' => [
                        'name' => $trans->trans($bankItem->getPrototype()->getLabel(), [], 'items'),
                        'count' => $bankItem->getCount(),
                        'id' => $bankItem->getPrototype()->getId(),
                        'cat' => $bankItem->getPrototype()->getCategory()->getName(),
                        'img' => $this->asset->getUrl( "build/images/item/item_{$bankItem->getPrototype()->getIcon()}.gif"),
                        'broken' => intval($bankItem->getBroken())
                    ]
                ];
            }

            // Expeditions
            $expeditions = $this->entity_manager->getRepository(ExpeditionRoute::class)->findByTown( $town );
            foreach($expeditions as $expedition) {
                /** @var ExpeditionRoute $expedition */
                $expe = [
                    'attributes' => [
                        'name' => str_replace('"', "'", $expedition->getLabel()),
                        'author' => $expedition->getOwner()->getUser()->getUsername(),
                        'length' => $expedition->getLength(),
                        'authorId' => $expedition->getOwner()->getUser()->getId()
                    ],
                    'list' => [
                        'name' => 'point',
                        'items' => []
                    ]
                ];

                foreach($expedition->getData() as $point){
                    $expe['list']['items'][] = [
                        'attributes' => [
                            'x' => $offset['x'] + $point[0],
                            'y' => $offset['y'] - $point[1]
                        ]
                    ];
                }
                $data['hordes']['data']['expeditions']['list']['items'][] = $expe;
            }

            // Citizens
            foreach($town->getCitizens() as $citizen){
                /** @var Citizen $citizen */
                if($citizen->getAlive()){
                    $data['hordes']['data']['citizens']['list']['items'][] = [
                        'attributes' => [
                            'dead' => '0',
                            'hero' => intval($citizen->getProfession()->getHeroic()),
                            'name' => $citizen->getUser()->getUsername(),
                            'avatar' => $citizen->getUser()->getAvatar() !== null ? $citizen->getUser()->getId() . "/" . $citizen->getUser()->getAvatar()->getFilename() . "." . $citizen->getUser()->getAvatar()->getFormat() : '',
                            'x' => $citizen->getZone() !== null ? $offset['x'] + $citizen->getZone()->getX() : $offset['x'],
                            'y' => $citizen->getZone() !== null ? $offset['y'] - $citizen->getZone()->getY() : $offset['y'],
                            'id' => $citizen->getUser()->getId(),
                            'ban' => intval($citizen->getBanished()),
                            'job' => $citizen->getProfession()->getName(),
                            'out' => intval($citizen->getZone() !== null),
                            'baseDef' => '???'
                        ],
                        'value' => $citizen->getHome()->getDescription()
                    ];
                } else {
                    $data['hordes']['data']['cadavers']['list']['items'][] = [
                        'attributes' => [
                            'name' => $citizen->getUser()->getUsername(),
                            'dtype' => $citizen->getCauseOfDeath()->getRef(),
                            'id' => $citizen->getUser()->getId(),
                            'day' => $citizen->getSurvivedDays(),
                        ],
                        'value' => $citizen->getLastWords()
                    ];
                }
            }

            // Map
            foreach($town->getZones() as $zone) {
                /** @var Zone $zone */
                if($zone->getDiscoveryStatus() != Zone::DiscoveryStateNone) {
                    $danger = 0;
                    if($zone->getZombies() > 0 && $zone->getZombies() <= 2) {
                        $danger = 1;
                    } else if($zone->getZombies() > 2 && $zone->getZombies() <= 5) {
                        $danger = 2;
                    } else if ($zone->getZombies() > 5) {
                        $danger = 3;
                    }
                    
                    $item = [
                        'attributes' => [
                            'x' => $offset['x'] + $zone->getX(),
                            'y' => $offset['y'] - $zone->getY(),
                            'nvt' => intval($zone->getDiscoveryStatus() != Zone::DiscoveryStateCurrent)
                        ]
                    ];
                    
                    if($danger > 0) {
                        $item['attributes']['danger'] = $danger;
                    }

                    if($zone->getTag() !== null && $zone->getTag()->getRef() !== ZoneTag::TagNone) {
                        $item['attributes']['tag'] = $zone->getTag()->getRef();
                    }

                    if($zone->getPrototype() !== null) {
                        $item['building'] = [
                            'attributes' => [
                                'name' => $zone->getBuryCount() > 0 ? $trans->trans('Verschüttete Ruine', [], 'game') : $trans->trans($zone->getPrototype()->getLabel(), [], 'game'),
                                'type' => $zone->getBuryCount() > 0 ? -1 : $zone->getPrototype()->getId(),
                                'dig' => $zone->getBuryCount()
                            ],
                            'value' => $zone->getBuryCount() > 0 ? $trans->trans('Die Zone ist vollständig mit verrottender Vegetation, Sand und allem möglichen Schrott bedeckt. Du bist dir sicher, dass es hier etwas zu finden gibt, aber zunächst musst du diesen gesamten Sektor aufräumen um ihn vernünftig durchsuchen zu können.', [], 'game') : $trans->trans($zone->getPrototype()->getDescription(), [], 'game')
                        ];
                    }

                    $data['hordes']['data']['map']['list']['items'][] = $item;
                }
            }

            $has_zombie_est    = !empty($th->getBuilding($town, 'item_tagger_#00'));
            if ($has_zombie_est){
                // Zombies estimations
                for ($i = $town->getDay() + 1 ;  $i > 0 ; $i--) {
                    $quality = $th->get_zombie_estimation_quality( $town, $town->getDay() - $i, $z_today_min, $z_today_max );
                    $watchtrigger = $conf->getTownConfiguration($town)->get(TownConf::CONF_MODIFIER_WT_THRESHOLD, 33);
                    if($watchtrigger >= $quality) continue;

                    $data['hordes']['data']['estimations']['list']['items'][] = [
                        'attributes' => [
                            'day' => $i,
                            'max' => $z_today_max,
                            'min' => $z_today_min,
                            'maxed' => intval($quality >= 100)
                        ]
                    ];
                }
            }
        }  else {
            $data['hordes']['error']['attributes'] = ['code' => "not_in_game"];
            $data['hordes']['status']['attributes'] = ['open' => "1", "msg" => ""];
        }

        $response = new Response($this->arrayToXml( $data['hordes'], '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
        $response->headers->set('Content-Type', 'text/xml');
        return $response;
    }

    private function arrayToXml($array, $rootElement = null, $xml = null, $node = null): string {
        $_xml = $xml;
        // If there is no Root Element then insert root
        if ($_xml === null) {
            $_xml = new SimpleXMLElement($rootElement !== null ? $rootElement : '<root/>');
        }
        // Visit all key value pair
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $name = $node ?? $k;
                $child = $_xml->addChild($name);
                
                if (array_key_exists('attributes', $v)) {
                    foreach ($v['attributes'] as $a => $b) {
                        $child->addAttribute($a, $b);
                    }
                    unset($v['attributes']);
                }
                if (array_key_exists('list', $v)) {
                    $this->arrayToXml($v['list']['items'], $name, $child, $v['list']['name']);
                    unset($v['list']);
                }
                if(array_key_exists('value', $v)) {
                    $child[0] = $v["value"];
                    unset($v["value"]);
                }
            }
            // If there is nested array then
            if (is_array($v)) {
                // Call function for nested array
                if (!empty($v)) {
                    $this->arrayToXml($v, $name, $child);
                }
            } else {
                // Simply add child element.
                $_xml->addChild($k, $v);
            }
        }
        return $_xml->asXML();
    }

    protected function parseGazetteLog(GazetteLogEntry $gazetteLogEntry) {
        return $this->parseLog($gazetteLogEntry->getLogEntryTemplate(), $gazetteLogEntry->getVariables());
    }

    protected function parseLog(LogEntryTemplate $template, array $variables ): String {
        $variableTypes = $template->getVariableTypes();
        $transParams = $this->logTemplateHandler->parseTransParams($variableTypes, $variables, true);

        try {
            $text = $this->translator->trans($template->getText(), $transParams, 'game');
        }
        catch (Exception $e) {
            $text = "null";
        }

        return $text;
    }

    protected function getHeaders($language) {
        return [
            'hordes' => [
                'headers' => [
                    'attributes' => [
                        'link' => "//" . Request::createFromGlobals()->headers->get('host') . Request::createFromGlobals()->getPathInfo(),
                        'iconurl' => "//" . Request::createFromGlobals()->headers->get('host'), // TODO: Give base path
                        'avatarurl' => "//" . Request::createFromGlobals()->headers->get('host') . '/cdn/avatar/', // TODO: Find a way to set this dynamic (see WebController::avatar for reference)
                        'secure' => '1',
                        'author' => 'MyHordes',
                        'language' => $language,
                        'version' => '0.1',
                        'generator' => 'symfony',
                    ],
                ]
            ]
        ];
    }

}
?>
