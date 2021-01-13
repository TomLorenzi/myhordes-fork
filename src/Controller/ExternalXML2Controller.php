<?php
namespace App\Controller;

use App\Entity\AwardPrototype;
use App\Entity\Building;
use App\Entity\BuildingPrototype;
use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\ExpeditionRoute;
use App\Entity\ExternalApp;
use App\Entity\Gazette;
use App\Entity\GazetteLogEntry;
use App\Entity\Item;
use App\Entity\ItemCategory;
use App\Entity\ItemPrototype;
use App\Entity\LogEntryTemplate;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\Town;
use App\Entity\TwinoidImport;
use App\Entity\User;
use App\Entity\Zone;
use App\Entity\ZonePrototype;
use App\Entity\ZoneTag;
use App\Structures\SimpleXMLExtended;
use App\Structures\TownConf;
use App\Structures\TownDefenseSummary;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\Criteria;
use Exception;
use Symfony\Component\Config\Util\Exception\InvalidXmlException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;

class ExternalXML2Controller extends ExternalController {

    /**
     * Check if the userkey and/or appkey has been given
     * @param bool $must_be_secure If the request must have an app_key
     * @return Response|User Error or the user linked to the user_key
     */
    private function check_keys($must_be_secure = false) {
        /** @var Request $request */
        $request = $this->container->get('request_stack')->getCurrentRequest();

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

        $data = $this->getHeaders(null, $this->getRequestLanguage($request));

        if ($this->time_keeper->isDuringAttack()) {
            $data['error']['attributes'] = ['code' => "horde_attacking"];
            $data['status']['attributes'] = ['open' => "0", "msg" => $this->translator->trans("Die Seite wird von Horden von Zombies belagert!", [], 'global')];
            return new Response($this->arrayToXml( $data, '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
        }

        if(trim($user_key) == '') {
            $data['error']['attributes'] = ['code' => "missing_key"];
            $data['status']['attributes'] = ['open' => "1", "msg" => ""];
            return new Response($this->arrayToXml( $data, '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
        }

        // If still no key, none was sent correctly.
        if ($must_be_secure) {
            if(trim($app_key) == '') {
                $data['error']['attributes'] = ['code' => "only_available_to_secure_request"];
                $data['status']['attributes'] = ['open' => "1", "msg" => ""];
                return new Response($this->arrayToXml( $data, '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
            }

            // Get the app.
            /** @var ExternalApp $app */
            $app = $this->entity_manager->getRepository(ExternalApp::class)->findOneBy(['secret' => $app_key]);
            if (!$app) {
                $data['error']['attributes'] = ['code' => "only_available_to_secure_request"];
                $data['status']['attributes'] = ['open' => "1", "msg" => ""];
                return new Response($this->arrayToXml( $data, '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
            }
        }

        // Get the user.
        /** @var User $user */
        $user = $this->entity_manager->getRepository(User::class)->findOneBy(['externalId' => $user_key]);
        if (!$user) {
            $data['error']['attributes'] = ['code' => "user_not_found"];
            $data['status']['attributes'] = ['open' => "1", "msg" => ""];
            return new Response($this->arrayToXml( $data, '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
        }

        return $user;
    }

    /**
     * @Route("/api/x/v2/xml", name="api_x2_xml", defaults={"_format"="xml"}, methods={"GET","POST"})
     * @return Response The XML that contains the list of accessible enpoints
     */
    public function api_xml(): Response {
        $user = $this->check_keys();

        if($user instanceof Response)
            return $user;
        $endpoints = [];
        if ($this->isSecureRequest()) {
            $endpoints['user'] = $this->generateUrl('api_x2_xml_user', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $endpoints['items']= $this->generateUrl('api_x2_xml_items', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $endpoints['buildings']= $this->generateUrl('api_x2_xml_buildings', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $endpoints['ruins']= $this->generateUrl('api_x2_xml_ruins', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $endpoints['pictos']= $this->generateUrl('api_x2_xml_pictos', [], UrlGeneratorInterface::ABSOLUTE_URL);
        }
        if ($user->getActiveCitizen()) $endpoints['town'] = $this->generateUrl("api_x2_xml_town", [], UrlGeneratorInterface::ABSOLUTE_URL);

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
     * Get the XML content for the soul of a user
     * @param Request $request The current HTTP Request
     * @return Response Return the XML content for the soul of the user
     */
    public function api_xml_user(Request $request): Response {
        $user = $this->check_keys(true);

        if($user instanceof Response)
            return $user;

        try {
            $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
        } catch (Exception $e) {
            $now = date('Y-m-d H:i:s');
        }


        $language = $this->getRequestLanguage($request,$user);
        if($language !== 'all')
            $this->translator->setLocale($language);

        // Base data.
        $data = $this->getHeaders($user, $language);

        $data['data'] = [
            'attributes' => [
                'cache-date' => $now->format('Y-m-d H:i:s'),
                'cache-fast' => 0,
            ],
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
            ],
            'imported-maps' => [
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
                    'rare' => intval($picto['rare']),
                    'n' => $picto['c'],
                    'img' => $this->getIconPath($this->asset->getUrl( "build/images/pictos/{$picto['icon']}.gif")),
                ],
                'list' => [
                    'name' => 'title',
                    'items' => []
                ],
            ];
            if($language !== "all") {
                $node['attributes']['name'] = $this->translator->trans($picto['label'], [], 'game');
                $node['attributes']['desc'] = $this->translator->trans($picto['description'], [], 'game');
            } else {
                foreach ($this->available_langs as $lang) {
                    $node['attributes']["name-$lang"] = $this->translator->trans($picto['label'], [], 'game', $lang);
                    $node['attributes']["desc-$lang"] = $this->translator->trans($picto['description'], [], 'game', $lang);
                }
            }
            
            $criteria = new Criteria();
            $criteria->andWhere($criteria->expr()->lte('unlockQuantity', $picto['c']));
            $criteria->andWhere($criteria->expr()->eq('associatedPicto', $this->entity_manager->getRepository(PictoPrototype::class)->find($picto['id'])));

            $titles = $this->entity_manager->getRepository(AwardPrototype::class)->matching($criteria);
            foreach($titles as $title){
                /** @var AwardPrototype $title */
                $nodeTitle = [
                    'attributes' => [
                    ]
                ];
                if($language !== 'all'){
                    $nodeTitle['attributes']["name"] = $this->translator->trans($title->getTitle(), [], 'game');
                } else {
                    foreach ($this->available_langs as $lang) {
                        $nodeTitle['attributes']["name-$lang"] = $this->translator->trans($title->getTitle(), [], 'game', $lang);
                    }
                }
                $node['list']['items'][] = $nodeTitle;
            }
            $data['data']['rewards']['list']['items'][] = $node;
        }

        $mainAccount = null;
        foreach ($user->getTwinoidImports() as $twinoidImport){
            /** @var TwinoidImport $twinoidImport */
            if($twinoidImport->getMain()) {
                switch($twinoidImport->getScope()){
                    case "www.hordes.fr":
                        $mainAccount = 'fr';
                        break;
                    case "www.die2nite.com":
                        $mainAccount = 'en';
                        break;
                    case "www.dieverdammten.de":
                        $mainAccount = 'de';
                        break;
                    case "www.zombinoia.com":
                        $mainAccount = 'es';
                        break;
                }
            }
        }

        foreach($user->getPastLifes() as $pastLife){
            /** @var CitizenRankingProxy $pastLife */
            if($pastLife->getCitizen() && $pastLife->getCitizen()->getAlive()) continue;
            $node = "maps";
            if ($pastLife->getTown()->getImported() && $pastLife->getTown()->getLanguage() != $mainAccount){
                $node = "imported-maps";
            }
            $data['data'][$node]['list']['items'][] = [
                'attributes' => [
                    'name' => $pastLife->getTown()->getName(),
                    'season' => ($pastLife->getTown()->getSeason()) ? ($pastLife->getTown()->getSeason()->getNumber() === 0) ? $pastLife->getTown()->getSeason()->getSubNumber() : $pastLife->getTown()->getSeason()->getNumber() : 0,
                    'score' => $pastLife->getPoints(),
                    'd' => $pastLife->getDay(),
                    'id' => $pastLife->getTown()->getBaseID() !== null ? $pastLife->getTown()->getBaseID() : $pastLife->getTown()->getId(),
                    'v1' => 0,
                    'origin' => ($pastLife->getTown()->getSeason() && $pastLife->getTown()->getSeason()->getNumber() === 0)
                        ? strtolower($pastLife->getTown()->getLanguage())
                        : '',
                ],
                'cdata_value' => html_entity_decode($pastLife->getLastWords())
            ];
        }

        $response = new Response($this->arrayToXml( $data, '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
        $response->headers->set('Content-Type', 'text/xml');
        return $response;
    }

    /**
     * @Route("/api/x/v2/xml/town", name="api_x2_xml_town", defaults={"_format"="xml"}, methods={"GET","POST"})
     * Get the XML content for the town of a user
     * @param Request $request The current HTTP Request
     * @return Response
     */
    public function api_xml_town(Request $request): Response {
        $user = $this->check_keys(false);

        if($user instanceof Response)
            return $user;

        try {
            $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
        } catch (Exception $e) {
            $now = date('Y-m-d H:i:s');
        }

        $language = $this->getRequestLanguage($request, $user);
        if ($language !== 'all')
            $this->translator->setLocale($language);

        // Base data.
        $data = $this->getHeaders($user, $language);

        /** @var User $user */
        /** @var Citizen $citizen */
        if (!$user->getActiveCitizen()) {
            $data['error']['attributes'] = ['code' => "not_in_game"];
            $data['status']['attributes'] = ['open' => "1", "msg" => ""];
        } else {
            $town = $user->getActiveCitizen()->getTown();
    
            $data['headers']['game'] = [
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
            $this->town_handler->calculate_town_def($town, $def);

            $data['data'] = [
                'attributes' => [
                    'cache-date' => $now->format('Y-m-d H:i:s'),
                    'cache-fast' => 0,
                ],
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
                            'itemsMul' => $this->town_handler->getBuilding($town, 'item_meca_parts_#00', true) ? (1.0 + 1+$this->town_handler->getBuilding($town, 'item_meca_parts_#00', true)->getLevel()) * 0.5 : 1.0
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
                        'temporary' => intval($building->getPrototype()->getTemp()),
                        'id' => $building->getPrototype()->getId(),
                        'img' => $this->getIconPath($this->asset->getUrl("build/images/building/{$building->getPrototype()->getIcon()}.gif"))
                    ]
                ];

                if ($language !== 'all') {
                    $buildingXml['attributes']['name'] = $this->translator->trans($building->getPrototype()->getLabel(), [], 'buildings');
                    $buildingXml['cdata_value'] = $this->translator->trans($building->getPrototype()->getDescription(), [], 'buildings');
                } else {
                    foreach ($this->available_langs as $lang) {
                        $buildingXml['attributes']["name-$lang"] = $this->translator->trans($building->getPrototype()->getLabel(), [], 'buildings', $lang);
                        $buildingXml["value-$lang"] = ['cdata_value'=> $this->translator->trans($building->getPrototype()->getDescription(), [], 'buildings', $lang)];
                    }
                }

                if($building->getPrototype()->getParent() !== null) {
                    $buildingXml['attributes']['parent'] = $building->getPrototype()->getParent()->getId();
                }

                $data['data']['city']['list']['items'][] = $buildingXml;

                if($building->getPrototype()->getMaxLevel() > 0 && $building->getLevel() > 0){
                    $data['data']['upgrades']['attributes']['total'] += $building->getLevel();
                    $updateXml = [
                        'attributes' => [
                            'level' => $building->getLevel(),
                            'buildingId' => $building->getPrototype()->getId(),
                        ],
                    ];

                    if ($language !== 'all') {
                        $updateXml['attributes']['name'] = $this->translator->trans($building->getPrototype()->getLabel(), [], 'buildings');
                        $updateXml['cdata_value'] = $this->translator->trans($building->getPrototype()->getUpgradeTexts()[$building->getLevel() - 1], [], 'buildings');
                    } else {
                        foreach ($this->available_langs as $lang) {
                            $updateXml['attributes']["name-$lang"] = $this->translator->trans($building->getPrototype()->getLabel(), [], 'buildings', $lang);
                            $updateXml["value-$lang"] = ['cdata_value'=> $this->translator->trans($building->getPrototype()->getUpgradeTexts()[$building->getLevel() - 1], [], 'buildings', $lang)];
                        }
                    }
                    $data['data']['upgrades']['list']['items'][] = $updateXml;
                }
            }

            // Current gazette
            /** @var Gazette $gazette */
            if ($town->getDay() > 1){
                $gazette = $town->findGazette( $town->getDay() );
                if ($gazette !== null) {
                    $gazette_logs = $this->entity_manager->getRepository(GazetteLogEntry::class)->findByFilter($gazette);

                    $data['data']['city']['news'] = [
                        'attributes' => [
                            'z' => $gazette->getAttack(),
                            'def' => $gazette->getDefense()
                        ]
                    ];
                    if($language !== "all") {
                        $text = '';
                        while (count($gazette_logs) > 0) {
                            $text .= '<p>' . $this->parseGazetteLog(array_shift($gazette_logs)) . '</p>';
                        }
                        $data['data']['city']['news']['content'] = [
                            'cdata_value' => $text
                        ];
                    } else {
                        foreach($this->available_langs as $lang) {
                            $logs = $gazette_logs;
                            $text = '';
                            while (count($logs) > 0) {
                                $text .= '<p>' . $this->parseGazetteLog(array_shift($logs), $lang) . '</p>';
                            }
                            $data['data']['city']['news']["content-$lang"] = [
                                'cdata_value' => $text
                            ];
                        }
                    }
                }

            }

            // The town bank
            foreach($town->getBank()->getItems() as $bankItem) {
                /** @var Item $bankItem */
                $str = "{$bankItem->getPrototype()->getId()}-" . intval($bankItem->getBroken());
                if (!isset($data['data']['bank']['list']['items'][$str])) {
                    $cat = $bankItem->getPrototype()->getCategory();
                    while ($cat->getParent()) $cat = $cat->getParent();

                    $itemXml = [
                        'attributes' => [
                            'count' => $bankItem->getCount(),
                            'id' => $bankItem->getPrototype()->getId(),
                            'cat' => $cat->getName(),
                            'img' => $this->getIconPath($this->asset->getUrl("build/images/item/item_{$bankItem->getPrototype()->getIcon()}.gif")),
                            'broken' => intval($bankItem->getBroken())
                        ]
                    ];
                    if ($language !== 'all') {
                        $itemXml['attributes']['name'] = $this->translator->trans($bankItem->getPrototype()->getLabel(), [], 'items');
                    } else {
                        foreach ($this->available_langs as $lang) {
                            $itemXml['attributes']["name-$lang"] = $this->translator->trans($bankItem->getPrototype()->getLabel(), [], 'items', $lang);
                        }
                    }
                    $data['data']['bank']['list']['items'][$str] = $itemXml;

                } else $data['data']['bank']['list']['items'][$str]['attributes']['count'] += $bankItem->getCount();
            }
            usort( $data['data']['bank']['list']['items'],
                fn($a,$b) => $a['attributes']['id'] <=> $b['attributes']['id'] ?? $b['attributes']['broken'] <=> $a['attributes']['broken']);

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
                $data['data']['expeditions']['list']['items'][] = $expe;
            }

            // Citizens
            foreach($town->getCitizens() as $citizen){
                /** @var Citizen $citizen */
                if($citizen->getAlive()){
                    $citizenNode = [
                        'attributes' => [
                            'dead' => '0',
                            'hero' => intval($citizen->getProfession()->getHeroic()),
                            'name' => $citizen->getUser()->getUsername(),
                            'avatar' => $citizen->getUser()->getAvatar() !== null ? $citizen->getUser()->getId() . "/" . $citizen->getUser()->getAvatar()->getFilename() . "." . $citizen->getUser()->getAvatar()->getFormat() : '',
                            'id' => $citizen->getUser()->getId(),
                            'ban' => intval($citizen->getBanished()),
                            'job' => $citizen->getProfession()->getName() !== 'none' ? $citizen->getProfession()->getName() : '',
                            'out' => intval($citizen->getZone() !== null),
                            'baseDef' => $citizen->getHome()->getPrototype()->getDefense()
                        ],
                        'cdata_value' => $citizen->getHome()->getDescription()
                    ];
                    if (!$citizen->getTown()->getChaos()){
                        $citizenNode['attributes']['x'] = $citizen->getZone() !== null ? $offset['x'] + $citizen->getZone()->getX() : $offset['x'];
                        $citizenNode['attributes']['y'] = $citizen->getZone() !== null ? $offset['y'] - $citizen->getZone()->getY() : $offset['y'];
                    }
                    $data['data']['citizens']['list']['items'][] = $citizenNode;
                } else {
                    $cadaver = [
                        'attributes' => [
                            'name' => $citizen->getUser()->getUsername(),
                            'dtype' => $citizen->getCauseOfDeath()->getRef(),
                            'id' => $citizen->getUser()->getId(),
                            'day' => $citizen->getSurvivedDays() <= 0 ? '1' : $citizen->getSurvivedDays(),
                        ]

                    ];
                    if($citizen->getDisposed() !== null) {
                        $type = "unknown";
                        switch($citizen->getDisposed()){
                            case Citizen::Thrown:
                                $type = 'garbage';
                                break;
                            case Citizen::Watered:
                                $type = 'water';
                                break;
                            case Citizen::Cooked:
                                $type = "cook";
                                break;
                            case Citizen::Ghoul:
                                $type = "ghoul";
                                break;
                        }

                        $cadaver['cleanup'] = [
                            'attributes' => [
                                'type' => $type,
                                'user' => $citizen->getDisposedBy()->count() > 0 ? $citizen->getDisposedBy()[0]->getUser()->getName() : ""
                            ]
                        ];
                    }
                    if($citizen->getLastWords() !== null) {
                        $cadaver['msg'] = [
                            'cdata_value' => $citizen->getLastWords()
                        ];
                    }
                    $data['data']['cadavers']['list']['items'][] = $cadaver;
                }
            }

            usort( $data['data']['citizens']['list']['items'], fn($a,$b) => $a['attributes']['name'] <=> $b['attributes']['name']);
            usort( $data['data']['cadavers']['list']['items'], fn($a,$b) => $a['attributes']['day'] <=> $b['attributes']['day'] ?? $a['attributes']['name'] <=> $b['attributes']['name']);

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
                    if ($zone->getDiscoveryStatus() == Zone::DiscoveryStateCurrent) {
                        if($danger > 0) {
                            $item['attributes']['danger'] = $danger;
                        }
                    }

                    if($zone->getTag() !== null && $zone->getTag()->getRef() !== ZoneTag::TagNone) {
                        $item['attributes']['tag'] = $zone->getTag()->getRef();
                    }

                    if($zone->getPrototype() !== null) {
                        $zoneXml = [
                            'attributes' => [
                                'type' => $zone->getBuryCount() > 0 ? -1 : $zone->getPrototype()->getId(),
                                'dig' => $zone->getBuryCount()
                            ]
                        ];
                        if ($language !== 'all') {
                            $zoneXml['attributes']['name'] = $zone->getBuryCount() > 0 ? $this->translator->trans('Verschüttete Ruine', [], 'game') : $this->translator->trans($zone->getPrototype()->getLabel(), [], 'game');
                            $zoneXml['cdata_value'] = $zone->getBuryCount() > 0 ? $this->translator->trans('Die Zone ist vollständig mit verrottender Vegetation, Sand und allem möglichen Schrott bedeckt. Du bist dir sicher, dass es hier etwas zu finden gibt, aber zunächst musst du diesen gesamten Sektor aufräumen um ihn vernünftig durchsuchen zu können.', [], 'game') : $this->translator->trans($zone->getPrototype()->getDescription(), [], 'game');
                        } else {
                            foreach ($this->available_langs as $lang) {
                                $zoneXml['attributes']["name-$lang"] = $zone->getBuryCount() > 0 ? $this->translator->trans('Verschüttete Ruine', [], 'game', $lang) : $this->translator->trans($zone->getPrototype()->getLabel(), [], 'game', $lang);
                                $zoneXml["value-$lang"] = ['cdata_value'=> $zone->getBuryCount() > 0 ? $this->translator->trans('Die Zone ist vollständig mit verrottender Vegetation, Sand und allem möglichen Schrott bedeckt. Du bist dir sicher, dass es hier etwas zu finden gibt, aber zunächst musst du diesen gesamten Sektor aufräumen um ihn vernünftig durchsuchen zu können.', [], 'game') : $this->translator->trans($zone->getPrototype()->getDescription(), [], 'game', $lang)];
                            }
                        }
                        $item['building'] = $zoneXml;
                    }

                    $data['data']['map']['list']['items'][] = $item;
                }
            }

            $has_zombie_est    = !empty($this->town_handler->getBuilding($town, 'item_tagger_#00'));
            if ($has_zombie_est){
                // Zombies estimations
                for ($i = $town->getDay() + 1 ;  $i > 0 ; $i--) {
                    $quality = $this->town_handler->get_zombie_estimation_quality( $town, $town->getDay() - $i, $z_today_min, $z_today_max );
                    $watchtrigger = $this->conf->getTownConfiguration($town)->get(TownConf::CONF_MODIFIER_WT_THRESHOLD, 33);
                    if($watchtrigger >= $quality) continue;

                    $data['data']['estimations']['list']['items'][] = [
                        'attributes' => [
                            'day' => $i,
                            'max' => $z_today_max,
                            'min' => $z_today_min,
                            'maxed' => intval($quality >= 100)
                        ]
                    ];
                }
            }
        }

        $response = new Response($this->arrayToXml( $data, '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
        $response->headers->set('Content-Type', 'text/xml');
        return $response;
    }

    /**
     * @Route("/api/x/v2/xml/items", name="api_x2_xml_items", defaults={"_format"="xml"}, methods={"GET","POST"})
     * Returns the lists of items currently used in the game
     * @param Request $request
     * @return Response
     */
    public function api_xml_items(Request $request): Response {
        $user = $this->check_keys(true);

        try {
            $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
        } catch (Exception $e) {
            $now = date('Y-m-d H:i:s');
        }

        if($user instanceof Response)
            return $user;

        $language = $this->getRequestLanguage($request,$user);
        if($language !== 'all')
            $this->translator->setLocale($language);

        // Base data.
        $data = $this->getHeaders($user, $language);

        $items = $this->entity_manager->getRepository(ItemPrototype::class)->findAll();

        $data['data'] = [
            'attributes' => [
                'cache-date' => $now->format('Y-m-d H:i:s'),
                'cache-fast' => 0,
            ],
            'items' => [
                'list' => [
                    'name' => 'item',
                    'items' => [
                    ]
                ],
            ],
        ];

        /** @var ItemPrototype $item */
        foreach ($items as $item) {
            /** @var ItemCategory $cat */
            $cat = $item->getCategory();
            while ($cat->getParent()) $cat = $cat->getParent();

            $itemXml = [
                'attributes' => [
                    'id' => $item->getId(),
                    'cat' => $cat->getName(),
                    'img' => $this->getIconPath($this->asset->getUrl("build/images/item/item_{$item->getIcon()}.gif")),
                    'deco' => $item->getDeco(),
                    'heavy' => intval($item->getHeavy()),
                    'guard' => intval($item->getWatchpoint())
                ]
            ];

            if ($language !== 'all') {
                $itemXml['attributes']['name'] = $this->translator->trans($item->getLabel(), [], 'items');
                $itemXml['cdata_value'] = $this->translator->trans($item->getDescription(), [], 'items');
            } else {
                foreach ($this->available_langs as $lang) {
                    $itemXml['attributes']["name-$lang"] = $this->translator->trans($item->getLabel(), [], 'items', $lang);
                    $itemXml["value-$lang"]['cdata_value'] = $this->translator->trans($item->getDescription(), [], 'items', $lang);
                }
            }
            $data['data']['items']['list']['items'][] = $itemXml;
        }

        $response = new Response($this->arrayToXml( $data, '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
        $response->headers->set('Content-Type', 'text/xml');
        return $response;
    }

    /**
     * @Route("/api/x/v2/xml/buildings", name="api_x2_xml_buildings", defaults={"_format"="xml"}, methods={"GET","POST"})
     * Returns the lists of buildings currently used in the game
     * @param Request $request
     * @return Response
     */
    public function api_xml_buildings(Request $request): Response {
        $user = $this->check_keys(true);

        try {
            $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
        } catch (Exception $e) {
            $now = date('Y-m-d H:i:s');
        }

        if($user instanceof Response)
            return $user;

        $language = $this->getRequestLanguage($request,$user);
        if($language !== 'all')
            $this->translator->setLocale($language);

        // Base data.
        $data = $this->getHeaders($user, $language);

        $buildings = $this->entity_manager->getRepository(BuildingPrototype::class)->findAll();

        $data['data'] = [
            'attributes' => [
                'cache-date' => $now->format('Y-m-d H:i:s'),
                'cache-fast' => 0,
            ],
            'buildings' => [
                'list' => [
                    'name' => 'building',
                    'items' => [
                    ]
                ],
            ],
        ];

        /** @var BuildingPrototype $building */
        foreach ($buildings as $building) {
            $buildingXml = [
                'attributes' => [
                    'temporary' => intval($building->getTemp()),
                    'id' => $building->getId(),
                    'img' => $this->getIconPath($this->asset->getUrl("build/images/building/{$building->getIcon()}.gif"))
                ]
            ];

            if ($language !== 'all') {
                $buildingXml['attributes']['name'] = $this->translator->trans($building->getLabel(), [], 'buildings');
                $buildingXml['cdata_value'] = $this->translator->trans($building->getDescription(), [], 'buildings');
            } else {
                foreach ($this->available_langs as $lang) {
                    $buildingXml['attributes']["name-$lang"] = $this->translator->trans($building->getLabel(), [], 'buildings', $lang);
                    $buildingXml["value-$lang"] = ['cdata_value'=> $this->translator->trans($building->getDescription(), [], 'buildings', $lang)];
                }
            }

            if($building->getParent() !== null) {
                $buildingXml['attributes']['parent'] = $building->getParent()->getId();
            }
            $data['data']['buildings']['list']['items'][] = $buildingXml;
        }

        $response = new Response($this->arrayToXml( $data, '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
        $response->headers->set('Content-Type', 'text/xml');
        return $response;
    }

    /**
     * @Route("/api/x/v2/xml/ruins", name="api_x2_xml_ruins", defaults={"_format"="xml"}, methods={"GET","POST"})
     * Returns the lists of ruins currently used in the game
     * @param Request $request
     * @return Response
     */
    public function api_xml_ruins(Request $request): Response {
        $user = $this->check_keys(true);

        try {
            $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
        } catch (Exception $e) {
            $now = date('Y-m-d H:i:s');
        }

        if($user instanceof Response)
            return $user;

        $language = $this->getRequestLanguage($request,$user);
        if($language !== 'all')
            $this->translator->setLocale($language);

        // Base data.
        $data = $this->getHeaders($user, $language);

        $ruins = $this->entity_manager->getRepository(ZonePrototype::class)->findAll();

        $data['data'] = [
            'attributes' => [
                'cache-date' => $now->format('Y-m-d H:i:s'),
                'cache-fast' => 0,
            ],
            'ruins' => [
                'list' => [
                    'name' => 'ruin',
                    'items' => [
                    ]
                ],
            ],
        ];

        /** @var ZonePrototype $ruin */
        foreach ($ruins as $ruin) {
            $ruinXml = [
                'attributes' => [
                    'id' => $ruin->getId(),
                    'explorable' => intval($ruin->getExplorable())
                ]
            ];
            if ($language !== 'all') {
                $ruinXml['attributes']['name'] = $this->translator->trans($ruin->getLabel(), [], 'game');
                $ruinXml['cdata_value'] = $this->translator->trans($ruin->getExplorableDescription() ?? $ruin->getDescription(), [], 'game');
            } else {
                foreach ($this->available_langs as $lang) {
                    $ruinXml['attributes']["name-$lang"] = $this->translator->trans($ruin->getLabel(), [], 'game', $lang);
                    $ruinXml["value-$lang"] = ['cdata_value'=> $this->translator->trans($ruin->getExplorableDescription() ?? $ruin->getDescription(), [], 'game', $lang)];
                }
            }

            $data['data']['ruins']['list']['items'][] = $ruinXml;
        }

        $response = new Response($this->arrayToXml( $data, '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
        $response->headers->set('Content-Type', 'text/xml');
        return $response;
    }

    /**
     * @Route("/api/x/v2/xml/pictos", name="api_x2_xml_pictos", defaults={"_format"="xml"}, methods={"GET","POST"})
     * Returns the lists of pictos currently used in the game
     * @param Request $request
     * @return Response
     */
    public function api_xml_pictos(Request $request): Response {
        $user = $this->check_keys(true);

        try {
            $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
        } catch (Exception $e) {
            $now = date('Y-m-d H:i:s');
        }

        if($user instanceof Response)
            return $user;

        $language = $this->getRequestLanguage($request,$user);
        if($language !== 'all')
            $this->translator->setLocale($language);

        // Base data.
        $data = $this->getHeaders($user, $language);

        $pictos = $this->entity_manager->getRepository(PictoPrototype::class)->findAll();

        $data['data'] = [
            'attributes' => [
                'cache-date' => $now->format('Y-m-d H:i:s'),
                'cache-fast' => 0,
            ],
            'pictos' => [
                'list' => [
                    'name' => 'picto',
                    'items' => [
                    ]
                ],
            ],
        ];

        /** @var PictoPrototype $ruin */
        foreach ($pictos as $picto) {
            $pictoXml = [
                'attributes' => [
                    'id' => $picto->getId(),
                ]
            ];
            if ($language !== 'all') {
                $pictoXml['attributes']['name'] = $this->translator->trans($picto->getLabel(), [], 'game');
                $pictoXml['cdata_value'] = $this->translator->trans($picto->getDescription(), [], 'game');
            } else {
                foreach ($this->available_langs as $lang) {
                    $pictoXml['attributes']["name-$lang"] = $this->translator->trans($picto->getLabel(), [], 'game', $lang);
                    $pictoXml["value-$lang"] = ['cdata_value'=> $this->translator->trans($picto->getDescription(), [], 'game', $lang)];
                }
            }

            $data['data']['pictos']['list']['items'][] = $pictoXml;
        }

        $response = new Response($this->arrayToXml( $data, '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
        $response->headers->set('Content-Type', 'text/xml');
        return $response;
    }

    /**
     * @Route("/api/x/v2/xml/titles", name="api_x2_xml_titles", defaults={"_format"="xml"}, methods={"GET","POST"})
     * Returns the lists of titles currently used in the game
     * @param Request $request
     * @return Response
     */
    public function api_xml_titles(Request $request): Response {
        $user = $this->check_keys(true);

        try {
            $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
        } catch (Exception $e) {
            $now = date('Y-m-d H:i:s');
        }

        if($user instanceof Response)
            return $user;

        $language = $this->getRequestLanguage($request,$user);
        if($language !== 'all')
            $this->translator->setLocale($language);

        // Base data.
        $data = $this->getHeaders($user, $language);

        $awards = $this->entity_manager->getRepository(AwardPrototype::class)->findAll();

        $data['data'] = [
            'attributes' => [
                'cache-date' => $now->format('Y-m-d H:i:s'),
                'cache-fast' => 0,
            ],
            'titles' => [
                'list' => [
                    'name' => 'title',
                    'items' => [
                    ]
                ],
            ],
        ];

        /** @var AwardPrototype $ruin */
        foreach ($awards as $award) {
            $awardXml = [
                'attributes' => [
                    'id' => $award->getId(),
                    'picto' => $award->getAssociatedPicto()->getId(),
                    'unlock_quantity' => $award->getUnlockQuantity(),
                ]
            ];
            if ($language !== 'all') {
                $awardXml['attributes']['name'] = $this->translator->trans($award->getTitle(), [], 'game');
            } else {
                foreach ($this->available_langs as $lang) {
                    $awardXml['attributes']["name-$lang"] = $this->translator->trans($award->getTitle(), [], 'game', $lang);
                }
            }

            $data['data']['titles']['list']['items'][] = $awardXml;
        }

        $response = new Response($this->arrayToXml( $data, '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
        $response->headers->set('Content-Type', 'text/xml');
        return $response;
    }

    private function arrayToXml($array, $rootElement = null, $xml = null, $node = null): string {
        $_xml = $xml;
        // If there is no Root Element then insert root
        if ($_xml === null) {
            $_xml = new SimpleXMLExtended($rootElement !== null ? $rootElement : '<root/>');
        }
        // Visit all key value pair
        foreach ($array as $k => $v) {

            $name = null;
            $child = null;

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

                if(array_key_exists('value', $v) && array_key_exists('cdata_value', $v))
                    throw new InvalidXmlException("You cannot have both value and cdata_value in a node");

                if(array_key_exists('value', $v)) {
                    $child[0] = $v["value"];
                    unset($v["value"]);
                }
                if(array_key_exists('cdata_value', $v)) {
                    $child[0]->addCData($v["cdata_value"]);
                    unset($v["cdata_value"]);
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

    /**
     * Returns the text corresponding to the entry
     * @param GazetteLogEntry $gazetteLogEntry The gazette log entry to parse
     * @param string|null $lang Lang to translate the text into (null to use the default)
     * @return string The string corresponding to the GazetteLogEntry
     */
    protected function parseGazetteLog(GazetteLogEntry $gazetteLogEntry, string $lang = null): string
    {
        return $this->parseLog($gazetteLogEntry->getLogEntryTemplate(), $gazetteLogEntry->getVariables(), $lang);
    }

    protected function parseLog(LogEntryTemplate $template, array $variables, string $lang = null): string {
        $variableTypes = $template->getVariableTypes();
        $transParams = $this->logTemplateHandler->parseTransParams($variableTypes, $variables);

        try {
            $text = $this->translator->trans($template->getText(), $transParams, 'game', $lang);
        }
        catch (Exception $e) {
            $text = "null";
        }

        return $text;
    }

    protected function getHeaders(?User $user = null, string $language = 'de'): array {
        $base_url = Request::createFromGlobals()->getHost() . Request::createFromGlobals()->getBasePath();
        $icon_path = $base_url . '/build/images/';

        $headers = [
            'headers' => [
                'attributes' => [
                    'link' => "//" . $base_url . Request::createFromGlobals()->getPathInfo(),
                    'iconurl' => "//" . $icon_path,
                    'avatarurl' => "//" . $base_url . '/cdn/avatar/',
                    'secure' => intval($this->isSecureRequest()),
                    'author' => 'MyHordes',
                    'language' => $language,
                    'version' => '0.1',
                    'generator' => 'symfony',
                ],
            ]
        ];

        if($user && $this->isSecureRequest()){
            if ($citizen = $user->getActiveCitizen()) {
                try {
                    $now = new \DateTime('now', new DateTimeZone('Europe/Paris'));
                } catch (Exception $e) {
                    $now = date('Y-m-d H:i:s');
                }

                $offset = $citizen->getTown()->getMapOffset();

                /** @var Town $town */
                $town = $citizen->getTown();
                $headers['headers']['owner'] = [
                    'citizen' => [
                        "attributes" => [
                            'dead' => intval(!$citizen->getAlive()),
                            'hero' => $citizen->getProfession()->getHeroic(),
                            'name' => $user->getUsername(),
                            'avatar' => $user->getAvatar() !== null ? $user->getId() . "/" . $user->getAvatar()->getFilename() . "." . $user->getAvatar()->getFormat() : "",
                            'id' => $user->getId(),
                            'ban' => intval($citizen->getBanished()),
                            'job' => $citizen->getProfession()->getName(),
                            'out' => intval($citizen->getZone() !== null),
                            'baseDef' => '0'
                        ],
                        "cdata_value" => $citizen->getHome()->getDescription()
                    ],
                ];
                if(!$citizen->getTown()->getChaos()){
                    $headers['headers']['owner']['citizen']['attributes']['x'] = $offset['x'] + ($citizen->getZone() !== null ? $citizen->getZone()->getX() : 0);
                    $headers['headers']['owner']['citizen']['attributes']['y'] = $offset['y'] - ($citizen->getZone() !== null ? $citizen->getZone()->getY() : 0);
                }
                /** @var Zone $zone */
                $zone = $citizen->getZone();
                if($zone !== null){
                    $cp = 0;
                    foreach ($zone->getCitizens() as $c) {
                        if ($c->getAlive()) {
                            $cp += $this->citizen_handler->getCP($c);
                        }
                    }

                    $headers['headers']['owner']['myZone'] = [
                        "attributes" => [
                            'dried' => intval($zone->getDigs() <= 0),
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
                        if ($item->getHidden()) continue;
                        $str = "{$item->getPrototype()->getId()}-" . intval($item->getBroken());
                        if (!isset($headers['headers']['owner']['myZone']['list']['items'][$str])) {

                            $headers['headers']['owner']['myZone']['list']['items'][$str] = [
                                'attributes' => [
                                    'count' => 1,
                                    'id' => $item->getPrototype()->getId(),
                                    'cat' => $item->getPrototype()->getCategory()->getName(),
                                    'img' => $this->getIconPath($this->asset->getUrl( "build/images/item/item_{$item->getPrototype()->getIcon()}.gif")),
                                    'broken' => intval($item->getBroken())
                                ]
                            ];

                            if($language !== "all")
                                $headers['headers']['owner']['myZone']['list']['items'][$str]['attributes']['name'] = $this->translator->trans($item->getPrototype()->getLabel(), [], 'items');
                            else foreach ($this->available_langs as $lang)
                                $headers['headers']['owner']['myZone']['list']['items'][$str]['attributes']["name-$lang"] = $this->translator->trans($item->getPrototype()->getLabel(), [], 'items', $lang);

                        } else $headers['headers']['owner']['myZone']['list']['items'][$str]['attributes']['count']++;
                    }

                    usort( $headers['headers']['owner']['myZone']['list']['items'],
                        fn($a,$b) => $a['attributes']['id'] <=> $b['attributes']['id'] ?? $b['attributes']['broken'] <=> $a['attributes']['broken']);
                }

                $headers['headers']['game'] = [
                    'attributes' => [
                        'days' => $town->getDay(),
                        'quarantine' => intval($town->getQuarantine()),
                        'datetime' => $now->format('Y-m-d H:i:s'),
                        'id' => $town->getId(),
                    ],
                ];
            } else {
                $headers['headers']['owner'] = [
                    'citizen' => [
                        "attributes" => [
                            'dead' => 1,
                            'hero' => 1,
                            'name' => $user->getUsername(),
                            'avatar' => $user->getAvatar() !== null ? $user->getId() . "/" . $user->getAvatar()->getFilename() . "." . $user->getAvatar()->getFormat() : "",
                            'id' => $user->getId(),
                        ],
                        "cdata_value" => ""
                    ]
                ];
            }
        }

        return $headers;
    }

}

