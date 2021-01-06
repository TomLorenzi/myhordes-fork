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
use App\Entity\TwinoidImport;
use App\Entity\User;
use App\Entity\Zone;
use App\Entity\ZoneTag;
use App\Service\CitizenHandler;
use App\Service\TownHandler;
use App\Service\ZoneHandler;
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

        $data = $this->getHeaders();

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
        $endpoints['user'] = $this->generateUrl('api_x2_xml_user', [], UrlGeneratorInterface::ABSOLUTE_URL);
        if ($user->getAliveCitizen()) $endpoints['town'] = $this->generateUrl("api_x2_xml_town", [], UrlGeneratorInterface::ABSOLUTE_URL);

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
     * @param $zh ZoneHandler
     * @param $ch CitizenHandler
     * @return Response Return the XML content for the soul of the user
     */
    public function api_xml_user(ZoneHandler $zh, CitizenHandler $ch, Request $request): Response {
        $user = $this->check_keys(true);

        $icon_asset_path = Request::createFromGlobals()->getBasePath() . '/build/images/';

        if($user instanceof Response)
            return $user;

        try {
            $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
        } catch (Exception $e) {
            $now = date('Y-m-d H:i:s');
        }

        // Try POST data
        $language = $request->query->get('lang');

        if (trim($language) == '') {
            $language = $request->request->get('lang');
        }

        if(!in_array($language, ['en', 'fr', 'de', 'es', 'all'])) {
            $language = $user->getLanguage() ?? 'de';
        }

        if($language !== 'all')
            $this->translator->setLocale($language);

        // Base data.
        $data = $this->getHeaders($user);

        /** @var Citizen $citizen */
        $citizen = $user->getActiveCitizen();

        $data['data'] = [
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
                    'name' => $this->translator->trans($picto['label'], [], 'game'),
                    'rare' => intval($picto['rare']),
                    'n' => $picto['c'],
                    'img' => str_replace($icon_asset_path, '', $this->asset->getUrl( "build/images/pictos/{$picto['icon']}.gif")),
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
                        $nodeTitle['attributes']["name-$lang"] = $this->translator->trans($title->getTitle(), [], 'game');
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
     * @param $zh ZoneHandler
     * @param $ch CitizenHandler
     * @param TownHandler $th
     * @return Response
     */
    public function api_xml_town(ZoneHandler $zh, CitizenHandler $ch, TownHandler $th, Request $request): Response {
        $user = $this->check_keys(false);

        $icon_asset_path = Request::createFromGlobals()->getBasePath() . '/build/images/';

        if($user instanceof Response)
            return $user;

        try {
            $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
        } catch (Exception $e) {
            $now = date('Y-m-d H:i:s');
        }

        // Try POST data
        $language = $request->query->get('lang');

        if (trim($language) == '') {
            // No POST data, we use GET datas
            $language = $request->request->get('lang');
        }

        if(trim($language == ''))
            $language = $request->getLocale();

        if(!in_array($language, ['en', 'fr', 'de', 'es', 'all'])) {
            // Still no data, we use the user lang, or the deutsch as latest fallback
            $language = $user->getLanguage() ?? 'de';
        }

        if($language !== 'all') {
            $this->translator->setLocale($language);
        }

        // Base data.
        $data = $this->getHeaders($user);

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
            $th->calculate_town_def($town, $def);

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
                        'name' => $this->translator->trans($building->getPrototype()->getLabel(), [], 'buildings'),
                        'temporary' => intval($building->getPrototype()->getTemp()),
                        'id' => $building->getPrototype()->getId(),
                        'img' => str_replace($icon_asset_path, '', $this->asset->getUrl("build/images/building/{$building->getPrototype()->getIcon()}.gif"))
                    ], 
                    'cdata_value' => $this->translator->trans($building->getPrototype()->getDescription(), [], 'buildings')
                ];


                if($building->getPrototype()->getParent() !== null) {
                    $buildingXml['attributes']['parent'] = $building->getPrototype()->getParent()->getId();
                }

                $data['data']['city']['list']['items'][] = $buildingXml;

                if($building->getPrototype()->getMaxLevel() > 0 && $building->getLevel() > 0){
                    $data['data']['upgrades']['attributes']['total'] += $building->getLevel();
                    $data['data']['upgrades']['list']['items'][] = [
                        'attributes' => [
                            'name' => $this->translator->trans($building->getPrototype()->getLabel(), [], 'buildings'),
                            'level' => $building->getLevel(),
                            'buildingId' => $building->getPrototype()->getId(),
                        ], 
                        'cdata_value' => $this->translator->trans($building->getPrototype()->getUpgradeTexts()[$building->getLevel() - 1], [], 'buildings')
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
                $data['data']['city']['news'] = [
                    'attributes' => [
                        'z' => $gazette->getAttack(),
                        'def' => $gazette->getDefense()
                    ], 
                    'content' => $text
                ];
            }

            // The town bank
            foreach($town->getBank()->getItems() as $bankItem) {
                /** @var Item $bankItem */
                $str = "{$bankItem->getPrototype()->getId()}-" . intval($bankItem->getBroken());
                if (!isset($data['data']['bank']['list']['items'][$str])) {
                    $cat = $bankItem->getPrototype()->getCategory();
                    while ($cat->getParent()) $cat = $cat->getParent();
                    $data['data']['bank']['list']['items'][$str] = [
                        'attributes' => [
                            'name' => $this->translator->trans($bankItem->getPrototype()->getLabel(), [], 'items'),
                            'count' => $bankItem->getCount(),
                            'id' => $bankItem->getPrototype()->getId(),
                            'cat' => $cat->getName(),
                            'img' => str_replace($icon_asset_path, '', $this->asset->getUrl("build/images/item/item_{$bankItem->getPrototype()->getIcon()}.gif")),
                            'broken' => intval($bankItem->getBroken())
                        ]
                    ];
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
                    $data['data']['citizens']['list']['items'][] = [
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
                            'baseDef' => $citizen->getHome()->getPrototype()->getDefense()
                        ],
                        'cdata_value' => $citizen->getHome()->getDescription()
                    ];
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
                        }
                        $cadaver['cleanup'] = [
                            'attributes' => [
                                'type' => $type,
                                'user' => $citizen->getDisposedBy()[0]->getUser()->getName()
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
                        $item['building'] = [
                            'attributes' => [
                                'name' => $zone->getBuryCount() > 0 ? $this->translator->trans('Verschüttete Ruine', [], 'game') : $this->translator->trans($zone->getPrototype()->getLabel(), [], 'game'),
                                'type' => $zone->getBuryCount() > 0 ? -1 : $zone->getPrototype()->getId(),
                                'dig' => $zone->getBuryCount()
                            ],
                            'cdata_value' => $zone->getBuryCount() > 0 ? $this->translator->trans('Die Zone ist vollständig mit verrottender Vegetation, Sand und allem möglichen Schrott bedeckt. Du bist dir sicher, dass es hier etwas zu finden gibt, aber zunächst musst du diesen gesamten Sektor aufräumen um ihn vernünftig durchsuchen zu können.', [], 'game') : $this->translator->trans($zone->getPrototype()->getDescription(), [], 'game')
                        ];
                    }

                    $data['data']['map']['list']['items'][] = $item;
                }
            }

            $has_zombie_est    = !empty($th->getBuilding($town, 'item_tagger_#00'));
            if ($has_zombie_est){
                // Zombies estimations
                for ($i = $town->getDay() + 1 ;  $i > 0 ; $i--) {
                    $quality = $th->get_zombie_estimation_quality( $town, $town->getDay() - $i, $z_today_min, $z_today_max );
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

    private function arrayToXml($array, $rootElement = null, $xml = null, $node = null): string {
        $_xml = $xml;
        // If there is no Root Element then insert root
        if ($_xml === null) {
            $_xml = new SimpleXMLExtended($rootElement !== null ? $rootElement : '<root/>');
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

    protected function getHeaders(User $user = null) {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $language = $request->query->get('lang');

        if (trim($language) == '') {
            $language = $request->request->get('lang');
        }

        if(empty($language))
            $language = $request->getLocale() ?? 'de';

        $base_url = Request::createFromGlobals()->getHost() . Request::createFromGlobals()->getBasePath();
        $icon_path = $base_url . '/build/images/';
        $icon_asset_path = Request::createFromGlobals()->getBasePath() . '/build/images/';

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
                            'x' => $offset['x'] + ($citizen->getZone() !== null ? $citizen->getZone()->getX() : 0),
                            'y' => $offset['y'] - ($citizen->getZone() !== null ? $citizen->getZone()->getY() : 0),
                            'id' => $user->getId(),
                            'ban' => intval($citizen->getBanished()),
                            'job' => $citizen->getProfession()->getName(),
                            'out' => intval($citizen->getZone() !== null),
                            'baseDef' => '0'
                        ],
                        "cdata_value" => $citizen->getHome()->getDescription()
                    ],
                    // "myZone" => []
                ];
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

                        $str = "{$item->getPrototype()->getId()}-" . intval($item->getBroken());
                        if (!isset($headers['headers']['owner']['myZone']['list']['items'][$str])) {

                            $headers['headers']['owner']['myZone']['list']['items'][$str] = [
                                'attributes' => [
                                    'count' => 1,
                                    'id' => $item->getPrototype()->getId(),
                                    'cat' => $item->getPrototype()->getCategory()->getName(),
                                    'img' => str_replace($icon_asset_path, '', $this->asset->getUrl( "build/images/item/item_{$item->getPrototype()->getIcon()}.gif")),
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

