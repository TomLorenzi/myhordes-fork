<?php
namespace App\Controller\API;

use App\Annotations\ExternalAPI;
use App\Annotations\GateKeeperProfile;
use App\Entity\Citizen;
use App\Entity\Town;
use App\Entity\User;
use App\Entity\Zone;
use App\Enum\ExternalAPIError;
use App\Enum\ExternalAPIInterface;
use DateTime;
use DateTimeZone;
use Exception;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class XMLv1Controller
 * @package App\Controller
 */
#[GateKeeperProfile(allow_during_attack: true, record_user_activity: false)]
class XMLv1Controller extends CoreController {

    /**
     * @param User|null $user
     * @return Response
     */
    #[Route(path: 'api/x/xml', name: 'api_x_xml', defaults: ['_format' => 'xml'], methods: ['GET', 'POST'])]
    #[ExternalAPI(user: true, app: true, api: ExternalAPIInterface::XMLv1)]
    public function api_xml(?User $user): Response {
        // All fine, let's populate the response.
        $data = $this->generateLegacyData($user);
        $response = new Response($this->arrayToXml( $data['hordes'], '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
        #$response = new Response(print_r($data, 1));
        $response->headers->set('Content-Type', 'text/xml');
        return $response;
    }

    private function generateLegacyData(User $user): array {
        try {
            $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
        } catch (Exception) {
            $now = date('Y-m-d H:i:s');
        }

        /** @var Citizen $citizen */
        $citizen = $user->getActiveCitizen();

        if (!$citizen) return [];

        /** @var Town $town */
        $town = $citizen->getTown();

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
            'hordes' => [
                'headers' => [
                    'attributes' => [
                        'link' => Request::createFromGlobals()->getRequestUri(),
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
                            'quarantine' => intval($town->getQuarantine()),
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
                            'x' => 0 - $x_min,
                            'y' => $y_max,
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
                            'hei' => abs($y_min) + abs($y_max) + 1,
                            'wid' => abs($x_min) + abs($x_max) + 1,
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
            ],
        ];

        // Add zones.
        foreach ( $town->getZones() as $zone ) {
            /** @var Zone $zone */
            if ($zone->getDiscoveryStatus() != 0) {
                $attributes = $this->zone_handler->getZoneAttributes($zone);
                $zone_data = [
                    'attributes' => [
                        'x' => $zone->getX() - $x_min,
                        'y' => $y_max - $zone->getY(),
                        'nvt' => $zone->getDiscoveryStatus() == 1 ? 1 : 0,
                    ],
                ];
                if (array_key_exists('danger', $attributes)) {
                    $zone_data['attributes']['danger'] = $attributes['danger'];
                }
                if (array_key_exists('building', $attributes)) {
                    $zone_data['building'] = ['attributes' => $attributes['building']];
                }
                $data['hordes']['data']['map']['list']['items'][] = $zone_data;
            }
        }

        // Add buildings.
        foreach ( $town->getBuildings() as $building ) {
            if ($building->getComplete()) {
                $building_data = [
                    'attributes' => [
                        'name' => $this->translator->trans($building->getPrototype()->getLabel(), [], "game"),
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
                    'name' => $this->translator->trans($item->getPrototype()->getLabel(), [], "game"),
                    'count' => $item->getCount(),
                    'id' => $item->getPrototype()->getId(),
                    'img' => $item->getPrototype()->getIcon(),
                    'cat' => $item->getPrototype()->getCategory()->getParent() ? $item->getPrototype()->getCategory()->getParent()->getName() : $item->getPrototype()->getCategory()->getName(),
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
                        'name' => $citizen->getName(),
                        'avatar' => '',
                        'x' => $town->getChaos() ? null : (!is_null($citizen->getZone()) ? $citizen->getZone()->getX() - $x_min : -$x_min),
                        'y' => $town->getChaos() ? null : (!is_null($citizen->getZone()) ? $y_max - $citizen->getZone()->getY() : $y_max),
                        'id' => $citizen->getId(),
                        'ban' => $citizen->getBanished(),
                        'job' => $citizen->getProfession()->getName(),
                        'out' => !is_null($citizen->getZone()),
                        'baseDef' => 0,
                    ],
                ];
                $data['hordes']['data']['citizens']['list']['items'][] = $citizen_data;
                if ($citizen === $user->getActiveCitizen()) {
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

                        if (!$town->getChaos()) {
                            $inventory = $myzone->getFloor();
                            foreach ( $inventory->getItems() as $item ) {
                                $item_data = [
                                    'attributes' => [
                                        'name' => $item->getPrototype()->getLabel(),
                                        'count' => $item->getCount(),
                                        'id' => $item->getPrototype()->getId(),
                                        'img' => $item->getPrototype()->getIcon(),
                                        'cat' => $item->getPrototype()->getCategory()->getParent() ? $item->getPrototype()->getCategory()->getParent()->getName() : $item->getPrototype()->getCategory()->getName(),
                                        'broken' => $item->getBroken(),
                                    ],
                                ];
                                $data['hordes']['headers']['owner']['myZone']['list']['items'][] = $item_data;
                            }
                        }
                    }
                }
            } else {
                $citizen_data = [
                    'attributes' => [
                        'name' => $citizen->getName(),
                        'id' => $citizen->getId(),
                        'dtype' => $citizen->getCauseOfDeath()->getId(),
                        'day' => $citizen->getSurvivedDays(),
                    ],
                ];
                $data['hordes']['data']['cadavers']['list']['items'][] = $citizen_data;
            }
        }

        return $data;
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

    public function on_error(ExternalAPIError $message, string $language): Response
    {
        $data = ['error' => [], 'status' => []];
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
}