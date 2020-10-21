<?php
namespace App\Controller;

use App\Entity\Citizen;
use App\Entity\ExternalApp;
use App\Entity\Town;
use App\Entity\User;
use App\Entity\Zone;
use DateTime;
use DateTimeZone;
use Exception;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class ExternalXMLController extends ExternalController {

    /**
     * @Route("/api/x/xml", name="api_x_xml", defaults={"_format"="xml"}, methods={"POST"})
     * @return Response
     */
    public function api_xml(): Response {
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
        $data = $this->generateLegacyData($user);
        $response = new Response($this->arrayToXml( $data['hordes'], '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
        #$response = new Response(print_r($data, 1));
        $response->headers->set('Content-Type', 'text/xml');
        return $response;
    }

    private function generateLegacyData(User $user): array {
        try {
            $now = new DateTime('now', new DateTimeZone('America/New_York'));
        } catch (Exception $e) {
            $now = date('Y-m-d H:i:s');
        }

        /** @var Citizen $citizen */
        $citizen = $user->getActiveCitizen();
        /** @var Town $town */
        $town = $citizen->getTown();
        /** @var Zone $citizen_zone */
        $citizen_zone = $citizen->getZone();

        $language = $town->getLanguage() ?? 'de';

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
                        'name' => $citizen->getUser()->getUsername(),
                        'avatar' => '',
                        'x' => !is_null($citizen->getZone()) ? $citizen->getZone()->getX() - $x_min : -$x_min,
                        'y' => !is_null($citizen->getZone()) ? $y_max - $citizen->getZone()->getY() : $y_max,
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
            } else {
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
}