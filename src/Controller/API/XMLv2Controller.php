<?php
namespace App\Controller\API;

use App\Annotations\ExternalAPI;
use App\Annotations\GateKeeperProfile;
use App\Entity\AwardPrototype;
use App\Entity\Building;
use App\Entity\BuildingPrototype;
use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\CitizenRole;
use App\Entity\ExpeditionRoute;
use App\Entity\Gazette;
use App\Entity\Item;
use App\Entity\ItemCategory;
use App\Entity\ItemPrototype;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\Town;
use App\Entity\TwinoidImport;
use App\Entity\User;
use App\Entity\Zone;
use App\Entity\ZonePrototype;
use App\Entity\ZoneTag;
use App\Enum\ExternalAPIError;
use App\Enum\ExternalAPIInterface;
use App\Structures\SimpleXMLExtended;
use App\Structures\TownConf;
use App\Structures\TownDefenseSummary;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\Criteria;
use Exception;
use Symfony\Component\Config\Util\Exception\InvalidXmlException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class XMLv2Controller
 * @package App\Controller
 */
#[GateKeeperProfile(allow_during_attack: true, record_user_activity: false)]
class XMLv2Controller extends CoreController {

    public function on_error(ExternalAPIError $message, string $language): Response {
        $data = $this->getHeaders(null, $language);
        switch ($message) {
            case ExternalAPIError::UserKeyNotFound:
                $data['error']['attributes'] = ['code' => "missing_key"];
                $data['status']['attributes'] = ['open' => "1", "msg" => ""];
                break;
            case ExternalAPIError::UserKeyInvalid:
                $data['error']['attributes'] = ['code' => "user_not_found"];
                $data['status']['attributes'] = ['open' => "1", "msg" => ""];
                break;
            case ExternalAPIError::AppKeyNotFound: case ExternalAPIError::AppKeyInvalid:
            $data['error']['attributes'] = ['code' => "only_available_to_secure_request"];
            $data['status']['attributes'] = ['open' => "1", "msg" => ""];
            break;
            case ExternalAPIError::HordeAttacking:
                $data['error']['attributes'] = ['code' => "horde_attacking"];
                $data['status']['attributes'] = ['open' => "0", "msg" => $this->translator->trans("Die Seite wird von Horden von Zombies belagert!", [], 'global')];
                break;
            case ExternalAPIError::RateLimitReached:
                $data['error']['attributes'] = ['code' => "rate_limit_reached"];
                $data['status']['attributes'] = ['open' => "1", "msg" => ""];
                break;
        }

        $response = new Response($this->arrayToXml( $data, '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
        $response->headers->set('Content-Type', 'text/xml');
        return $response;
    }

    /**
     * @return Response The XML that contains the list of accessible endpoints
     */
    #[Route(path: 'api/x/v2/xml', name: 'api_x2_xml', defaults: ['_format' => 'xml'], methods: ['GET', 'POST'])]
    #[ExternalAPI(user: true, app: false, api: ExternalAPIInterface::XMLv2)]
    public function api_xml(?User $user, ?string $app_key): Response {
        $endpoints = [];
        if ($app_key) {
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
     * @return Response Return the XML content for the soul of the user
     */
    #[Route(path: 'api/x/v2/xml/user', name: 'api_x2_xml_user', defaults: ['_format' => 'xml'], methods: ['GET', 'POST'])]
    #[ExternalAPI(user: true, app: true, api: ExternalAPIInterface::XMLv2)]
    public function api_xml_user(?User $user, string $language): Response {
        try {
            $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
        } catch (Exception) {
            $now = date('Y-m-d H:i:s');
        }

        if($language !== 'all')
            $this->translator->setLocale($language);
        else $this->translator->setLocale('en');

        // Base data.
        $data = $this->getHeaders($user, $language, true);

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

        $pictos = $this->pictoService->accumulateAllPictos( $user, include_imported: true );
        $comments = $this->pictoService->accumulateAllPictoComments( $user );

        foreach ($pictos as $picto){
            /** @var Picto $picto */
            $node = [
                'attributes' => [
                    'rare' => intval($picto->getPrototype()->getRare()),
                    'n' => intval($picto->getCount()),
                    'img' => $this->getIconPath($this->asset->getUrl( "build/images/pictos/{$picto->getPrototype()->getIcon()}.gif")),
                ],
                'list-0' => [
                    'name' => 'title',
                    'items' => []
                ],
                'list-1' => [
                    'name' => 'comment',
                    'items' => []
                ],
            ];
            if($language !== "all") {
                $node['attributes']['name'] = $this->translator->trans($picto->getPrototype()->getLabel(), [], 'game');
                $node['attributes']['desc'] = $this->translator->trans($picto->getPrototype()->getDescription(), [], 'game');
            } else {
                foreach ($this->languages as $lang) {
                    $node['attributes']["name-$lang"] = $this->translator->trans($picto->getPrototype()->getLabel(), [], 'game', $lang);
                    $node['attributes']["desc-$lang"] = $this->translator->trans($picto->getPrototype()->getDescription(), [], 'game', $lang);
                }
            }
            
            $criteria = new Criteria();
            $criteria->andWhere($criteria->expr()->lte('unlockQuantity', $picto->getCount()));
            $criteria->andWhere($criteria->expr()->eq('associatedPicto', $picto->getPrototype()));

            $titles = $this->entity_manager->getRepository(AwardPrototype::class)->matching($criteria);
            foreach($titles as $title){

                if (empty($title->getTitle())) continue;

                /** @var AwardPrototype $title */
                $nodeTitle = [
                    'attributes' => [
                    ]
                ];
                if($language !== 'all'){
                    $nodeTitle['attributes']["name"] = $this->translator->trans($title->getTitle(), [], 'game');
                } else {
                    foreach ($this->languages as $lang) {
                        $nodeTitle['attributes']["name-$lang"] = $this->translator->trans($title->getTitle(), [], 'game', $lang);
                    }
                }
                $node['list-0']['items'][] = $nodeTitle;
            }
            $node['list-1']['items'] = array_map( fn( string $s ) => ['attributes' => [], 'cdata_value' => $s], $comments[ $picto->getPrototype()->getId() ] ?? []);
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

            if ($pastLife->getTown()->getSeason() === null)
                $phase = 'alpha';
            elseif ($pastLife->getTown()->getSeason()->getNumber() === 0 && $pastLife->getTown()->getSeason()->getSubNumber() <= 14)
                $phase = 'import';
            elseif ($pastLife->getTown()->getSeason()->getNumber() === 0 && $pastLife->getTown()->getSeason()->getSubNumber() >= 14)
                $phase = 'beta';
            else
                $phase = 'native';

            $data['data'][$node]['list']['items'][] = [
                'attributes' => [
                    'name' => $pastLife->getTown()->getName(),
                    'season' => ($pastLife->getTown()->getSeason()) ? ($pastLife->getTown()->getSeason()->getNumber() === 0) ? $pastLife->getTown()->getSeason()->getSubNumber() : $pastLife->getTown()->getSeason()->getNumber() : 0,
                    'score' => $pastLife->getPoints(),
                    'd' => $pastLife->getDay(),
                    'id' => $pastLife->getTown()->getBaseID() !== null ? $pastLife->getTown()->getBaseID() : $pastLife->getTown()->getId(),
                    'v1' => 0,
                    'origin' => ($pastLife->getTown()->getSeason() && $pastLife->getTown()->getSeason()->getNumber() === 0 && $pastLife->getTown()->getSeason()->getSubNumber() <= 14)
                        ? strtolower($pastLife->getTown()->getLanguage())
                        : '',
                    'phase' => $phase
                ],
                'cdata_value' => html_entity_decode(str_replace('{gotKilled}', $this->translator->trans('...der Mörder .. ist.. IST.. AAARGHhh..', [], 'game'), $pastLife->getLastWords() ?? ''))
            ];
        }

        $response = new Response($this->arrayToXml( $data, '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
        $response->headers->set('Content-Type', 'text/xml');
        return $response;
    }

    /**
     * @param User|null $user
     * @param string $language
     * @param string|null $app_key
     * @return Response
     */
    #[Route(path: 'api/x/v2/xml/town', name: 'api_x2_xml_town', defaults: ['_format' => 'xml'], methods: ['GET', 'POST'])]
    #[ExternalAPI(user: true, app: false, api: ExternalAPIInterface::XMLv2)]
    public function api_xml_town(?User $user, string $language, ?string $app_key): Response {
        try {
            $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
        } catch (Exception) {
            $now = date('Y-m-d H:i:s');
        }

        // Base data.
        $data = $this->getHeaders($user, $language, !!$app_key);

        /** @var User $user */
        if (!$user->getActiveCitizen()) {
            $data['error']['attributes'] = ['code' => "not_in_game"];
            $data['status']['attributes'] = ['open' => "1", "msg" => ""];
        } else {
            //if ($user->getRightsElevation() >= User::USER_LEVEL_ADMIN && $request->query->has('town')) {
            //    $town = $this->entity_manager->getRepository(Town::class)->find($request->query->get('town'));
            //    if ($town === null) {
            //        $data['error']['attributes'] = ['code' => "town_not_found"];
            //        $data['status']['attributes'] = ['open' => "1", "msg" => ""];
            //        $response = new Response($this->arrayToXml( $data, '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
            //        $response->headers->set('Content-Type', 'text/xml');
            //        return $response;
            //    }
            //} else {
                $town = $user->getActiveCitizen()->getTown();
            //}

            if (!$this->conf->getTownConfiguration($town)->get(TownConf::CONF_FEATURE_XML, true)) {
                $data['error']['attributes'] = ['code' => "disabled_feed"];
                $data['status']['attributes'] = ['open' => "1", "msg" => ""];
                $response = new Response($this->arrayToXml( $data, '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
                $response->headers->set('Content-Type', 'text/xml');
                return $response;
            }

            $data['headers']['game'] = [
                'attributes' => [
                    'days' => $town->getDay(),
                    'quarantine' => intval($town->getAttackFails() >= 3),
                    'datetime' => $now->format('Y-m-d H:i:s'),
                    'id' => $town->getId(),
                ],
            ];

            $offset = $town->getMapOffset();

            $def = new TownDefenseSummary();
            $this->town_handler->calculate_town_def($town, $def);

            $item_def_factor = 1;

            $building = $this->town_handler->getBuilding($town, 'item_meca_parts_#00');
            if ($building) {
                $item_def_factor += (1+$building->getLevel()) * 0.5;
            }

			$map_x = $map_y = null;

            $town->getMapSize($map_x,$map_y);

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
                            'items' => $this->inventory_handler->countSpecificItems($town->getBank(), $this->inventory_handler->resolveItemProperties( 'defence' ), false, false),
                            'citizen_guardians' => $def->guardian_defense,
                            'citizen_homes' => $def->house_defense,
                            'upgrades' => $def->building_def_vote,
                            'buildings' => $def->building_def_base,
                            'total' => $def->sum(),
                            'itemsMul' => $item_def_factor
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
                        'hei' => $map_y,
                        'wid' => $map_x
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

            // Town roles
            /** @var Citizen $latest_guide */
            $latest_guide = $this->entity_manager->getRepository(Citizen::class)->findLastOneByRoleAndTown($this->entity_manager->getRepository(CitizenRole::class)->findOneBy(['name' => 'guide']), $town);
            if ($latest_guide && $latest_guide->getAlive()) {
                $data['data']['city']['attributes']['guide'] = $latest_guide->getUser()->getId();
            }

            /** @var Citizen $latest_shaman */
            $latest_shaman = $this->entity_manager->getRepository(Citizen::class)->findLastOneByRoleAndTown($this->entity_manager->getRepository(CitizenRole::class)->findOneBy(['name' => 'shaman']), $town);
            if ($latest_shaman && $latest_shaman->getAlive()) {
                $data['data']['city']['attributes']['shaman'] = $latest_shaman->getUser()->getId();
            }

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
                    foreach ($this->generatedLangsCodes as $lang) {
                        $buildingXml['attributes']["name-$lang"] = $this->translator->trans($building->getPrototype()->getLabel(), [], 'buildings', $lang);
                        $buildingXml["value-$lang"] = ['cdata_value'=> $this->translator->trans($building->getPrototype()->getDescription(), [], 'buildings', $lang)];
                    }
                }

                if ($building->getPrototype()->getParent() !== null) {
                    $buildingXml['attributes']['parent'] = $building->getPrototype()->getParent()->getId();
                }

                $data['data']['city']['list']['items'][] = $buildingXml;

                if ($building->getPrototype()->getMaxLevel() > 0 && $building->getLevel() > 0) {
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
                        foreach ($this->generatedLangsCodes as $lang) {
                            $updateXml['attributes']["name-$lang"] = $this->translator->trans($building->getPrototype()->getLabel(), [], 'buildings', $lang);
                            $updateXml["value-$lang"] = ['cdata_value'=> $this->translator->trans($building->getPrototype()->getUpgradeTexts()[$building->getLevel() - 1], [], 'buildings', $lang)];
                        }
                    }
                    $data['data']['upgrades']['list']['items'][] = $updateXml;
                }
            }

            // Current gazette
            if ($town->getDay() > 1){
                $gazette = $this->gazette_service->renderGazette($town);
                if (!empty($gazette)) {
                    $data['data']['city']['news'] = [
                        'attributes' => [
                            'z' => $gazette['attack'],
                            'def' => $gazette['defense']
                        ]
                    ];
                    if($language !== "all") {
                        $data['data']['city']['news']['content'] = [
                            'cdata_value' => $gazette['text'] . '<p>' . $gazette['wind'] . '</p>'
                        ];
                    } else {
                        foreach($this->generatedLangsCodes as $lang) {
                            $gazette = $this->gazette_service->renderGazette($town, null, true, $lang);
                            $data['data']['city']['news']["content-$lang"] = [
                                'cdata_value' => $gazette['text'] . '<p>' . $gazette['wind'] . '</p>'
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
                        foreach ($this->generatedLangsCodes as $lang) {
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
                        'author' => $expedition->getOwner()->getUser()->getName(),
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
                if ($citizen->getAlive()) {
                    $citizenNode = [
                        'attributes' => [
                            'dead' => (int)!$citizen->getAlive(),
                            'hero' => intval($citizen->getProfession()->getHeroic()),
                            'name' => $citizen->getUser()->getName(),
                            'avatar' => $citizen->getUser()->getAvatar() !== null ? $citizen->getUser()->getId() . "/" . $citizen->getUser()->getAvatar()->getFilename() . "." . $citizen->getUser()->getAvatar()->getFormat() : '',
                            'id' => $citizen->getUser()->getId(),
                            'ban' => intval($citizen->getBanished()),
                            'job' => $citizen->getProfession()->getName() !== 'none' ? $citizen->getProfession()->getName() : '',
                            'out' => intval($citizen->getZone() !== null),
                            'baseDef' => $citizen->getAlive() ? $citizen->getHome()->getPrototype()->getDefense() : 0,
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
                            'name' => $citizen->getUser()->getName(),
                            'dtype' => $citizen->getCauseOfDeath()->getRef(),
                            'id' => $citizen->getUser()->getId(),
                            'day' => $citizen->getDayOfDeath() <= 0 ? '1' : $citizen->getSurvivedDays(),
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
                                'user' => $citizen->getDisposedBy()->count() > 0 ? $citizen->getDisposedBy()[0]->getName() : ""
                            ]
                        ];
                    }
                    if($citizen->getLastWords() !== null) {
                        $cadaver['msg'] = [
                            'cdata_value' => str_replace('{gotKilled}', $this->translator->trans('...der Mörder .. ist.. IST.. AAARGHhh..', [], 'game'), $citizen->getLastWords())
                        ];
                    }
                    $data['data']['cadavers']['list']['items'][] = $cadaver;
                }
            }

            usort( $data['data']['citizens']['list']['items'], fn($a,$b) => strtolower($a['attributes']['name']) <=> strtolower($b['attributes']['name']));
            usort( $data['data']['cadavers']['list']['items'], fn($a,$b) => $a['attributes']['day'] <=> $b['attributes']['day'] ?? strtolower($a['attributes']['name']) <=> strtolower($b['attributes']['name']));

            // Map
            foreach($town->getZones() as $zone) {
                /** @var Zone $zone */

                if ($zone->getDiscoveryStatus() === Zone::DiscoveryStateNone) continue;

                $danger = 0;
                if ($zone->getZombies() > 0 && $zone->getZombies() <= 2) {
                    $danger = 1;
                } else if ($zone->getZombies() > 2 && $zone->getZombies() <= 5) {
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

                    if ($zone->getZombieStatus() == Zone::ZombieStateExact && $zone->getZombies() > 0) {
                        $item['attributes']['z'] = $zone->getZombies();
                    }
                }

                if ($zone->getTag() !== null && $zone->getTag()->getRef() !== ZoneTag::TagNone) {
                    $item['attributes']['tag'] = $zone->getTag()->getRef();
                }

                if ($zone->getPrototype() !== null) {
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
                        foreach ($this->generatedLangsCodes as $lang) {
                            $zoneXml['attributes']["name-$lang"] = $zone->getBuryCount() > 0 ? $this->translator->trans('Verschüttete Ruine', [], 'game', $lang) : $this->translator->trans($zone->getPrototype()->getLabel(), [], 'game', $lang);
                            $zoneXml["value-$lang"] = ['cdata_value'=> $zone->getBuryCount() > 0 ? $this->translator->trans('Die Zone ist vollständig mit verrottender Vegetation, Sand und allem möglichen Schrott bedeckt. Du bist dir sicher, dass es hier etwas zu finden gibt, aber zunächst musst du diesen gesamten Sektor aufräumen um ihn vernünftig durchsuchen zu können.', [], 'game') : $this->translator->trans($zone->getPrototype()->getDescription(), [], 'game', $lang)];
                        }
                    }
                    $item['building'] = $zoneXml;
                }

                $data['data']['map']['list']['items'][] = $item;

            }

            $has_zombie_est  = ($this->town_handler->getBuilding($town, 'item_tagger_#00') !== null);
            $has_zombie_est_tomorrow = ($this->town_handler->getBuilding($town, 'item_tagger_#02') !== null);
            if ($has_zombie_est){
                // Zombies estimations
                $estims = $this->town_handler->get_zombie_estimation($town);
                $watchtrigger = $this->conf->getTownConfiguration($town)->get(TownConf::CONF_MODIFIER_WT_THRESHOLD, 33);

                if($watchtrigger / 100 <= $estims[0]->getEstimation()) {
                    $data['data']['estimations']['list']['items'][] = [
                        'attributes' => [
                            'day' => ($town->getDay()),
                            'max' => $estims[0]->getMax(),
                            'min' => $estims[0]->getMin(),
                            'maxed' => intval($estims[0]->getEstimation() >= 1)
                        ]
                    ];
                    if ($estims[0]->getEstimation() >= 1 && $has_zombie_est_tomorrow) {
                        $data['data']['estimations']['list']['items'][] = [
                            'attributes' => [
                                'day' => ($town->getDay() + 1),
                                'max' => $estims[1]->getMax(),
                                'min' => $estims[1]->getMin(),
                                'maxed' => intval($estims[1]->getEstimation() >= 1)
                            ]
                        ];
                    }
                }
            }
        }

        $response = new Response($this->arrayToXml( $data, '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
        $response->headers->set('Content-Type', 'text/xml');
        return $response;
    }

    /**
     * @param User|null $user
     * @param string $language
     * @return Response
     */
    #[Route(path: 'api/x/v2/xml/items', name: 'api_x2_xml_items', defaults: ['_format' => 'xml'], methods: ['GET', 'POST'])]
    #[ExternalAPI(user: true, app: true, api: ExternalAPIInterface::XMLv2)]
    public function api_xml_items(?User $user, string $language): Response {

        try {
            $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
        } catch (Exception) {
            $now = date('Y-m-d H:i:s');
        }

        if ($language !== 'all')
            $this->translator->setLocale($language);

        // Base data.
        $data = $this->getHeaders($user, $language, true);

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
                foreach ($this->generatedLangsCodes as $lang) {
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
     * @param User|null $user
     * @param string $language
     * @return Response
     */
    #[Route(path: 'api/x/v2/xml/buildings', name: 'api_x2_xml_buildings', defaults: ['_format' => 'xml'], methods: ['GET', 'POST'])]
    #[ExternalAPI(user: true, app: true, api: ExternalAPIInterface::XMLv2)]
    public function api_xml_buildings(?User $user, string $language): Response {

        try {
            $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
        } catch (Exception) {
            $now = date('Y-m-d H:i:s');
        }

        if($language !== 'all')
            $this->translator->setLocale($language);

        // Base data.
        $data = $this->getHeaders($user, $language, true);

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
                foreach ($this->generatedLangsCodes as $lang) {
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
     * @param User|null $user
     * @param string $language
     * @return Response
     */
    #[Route(path: 'api/x/v2/xml/ruins', name: 'api_x2_xml_ruins', defaults: ['_format' => 'xml'], methods: ['GET', 'POST'])]
    #[ExternalAPI(user: true, app: true, api: ExternalAPIInterface::XMLv2)]
    public function api_xml_ruins(?User $user, string $language): Response {
        try {
            $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
        } catch (Exception) {
            $now = date('Y-m-d H:i:s');
        }

        if($language !== 'all')
            $this->translator->setLocale($language);

        // Base data.
        $data = $this->getHeaders($user, $language, true);

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
                foreach ($this->generatedLangsCodes as $lang) {
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
     * @param User|null $user
     * @param string $language
     * @return Response
     */
    #[Route(path: 'api/x/v2/xml/pictos', name: 'api_x2_xml_pictos', defaults: ['_format' => 'xml'], methods: ['GET', 'POST'])]
    #[ExternalAPI(user: true, app: true, api: ExternalAPIInterface::XMLv2)]
    public function api_xml_pictos(?User $user, string $language): Response {

        try {
            $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
        } catch (Exception) {
            $now = date('Y-m-d H:i:s');
        }

        if($language !== 'all')
            $this->translator->setLocale($language);

        // Base data.
        $data = $this->getHeaders($user, $language, true);

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

        /** @var PictoPrototype $picto */
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
                foreach ($this->generatedLangsCodes as $lang) {
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
     * @param User|null $user
     * @param string $language
     * @return Response
     */
    #[Route(path: 'api/x/v2/xml/titles', name: 'api_x2_xml_titles', defaults: ['_format' => 'xml'], methods: ['GET', 'POST'])]
    #[ExternalAPI(user: true, app: true, api: ExternalAPIInterface::XMLv2)]
    public function api_xml_titles(?User $user, string $language): Response {

        try {
            $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
        } catch (Exception) {
            $now = date('Y-m-d H:i:s');
        }

        if($language !== 'all')
            $this->translator->setLocale($language);

        // Base data.
        $data = $this->getHeaders($user, $language, true);

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

        /** @var AwardPrototype $award */
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
                foreach ($this->generatedLangsCodes as $lang) {
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
                for ($i = 0; $i <= 9; $i++)
                    if (array_key_exists("list-$i", $v)) {
                        $this->arrayToXml($v["list-$i"]['items'], $name, $child, $v["list-$i"]['name']);
                        unset($v["list-$i"]);
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

    protected function getHeaders(?User $user = null, string $language = 'de', bool $secure = false): array {
        $base_url = Request::createFromGlobals()->getHost() . Request::createFromGlobals()->getBasePath();
        $icon_path = $base_url . '/build/images/';

        $headers = [
            'headers' => [
                'attributes' => [
                    'link' => "//" . $base_url . Request::createFromGlobals()->getPathInfo(),
                    'iconurl' => "//" . $icon_path,
                    'avatarurl' => "//" . $base_url . '/cdn/avatar/',
                    'secure' => intval($secure),
                    'author' => 'MyHordes',
                    'language' => $language,
                    'version' => '2.1.0',
                    'generator' => 'symfony',
                ],
            ]
        ];

        if($user && $secure){
            if ($citizen = $user->getActiveCitizen()) {
                try {
                    $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
                } catch (Exception) {
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
                            'name' => $user->getName(),
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
                if($zone !== null && ($zone->getX() !== 0 || $zone->getY() !== 0)){
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
                            else foreach ($this->generatedLangsCodes as $lang)
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
                            'name' => $user->getName(),
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

