<?php
namespace App\Controller;

use App\Entity\AwardPrototype;
use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\ExternalApp;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\Town;
use App\Entity\User;
use App\Entity\Zone;
use App\Service\CitizenHandler;
use App\Service\ZoneHandler;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\Criteria;
use Exception;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;

class ExternalXMLController extends ExternalController {
    
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
     * @Route("/api/x/xml", name="api_x_xml", defaults={"_format"="xml"}, methods={"POST"})
     * @return Response
     */
    public function api_xml(): Response {
        $check = $this->check_keys();

        if($check instanceof Response)
            return $check;
        
        $array = [
            "endpoint_list" => [
                "User infos : " . $this->generateUrl('api_x_xml_user')
            ]
        ];

        // All fine, let's populate the response.
        $response = new Response($this->arrayToXml( $array, '<hordes xmlns:dc="http://purl.org/dc/elements/1.1" xmlns:content="http://purl.org/rss/1.0/modules/content/" />' ));
        $response->headers->set('Content-Type', 'text/xml');
        return $response;
    }

    /**
     * @Route("/api/x/xml/user", name="api_x_xml_user", defaults={"_format"="xml"}, methods={"POST"})
     * @param $trans TranslatorInterface
     * @param $zh ZoneHandler
     * @param $ch CitizenHandler
     * @return Response
     */
    public function api_xml_users(TranslatorInterface $trans, ZoneHandler $zh, CitizenHandler $ch): Response {
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
        $data = [
            'hordes' => [
                'headers' => [
                    'attributes' => [
                        'link' => Request::createFromGlobals()->getBaseUrl() . Request::createFromGlobals()->getPathInfo(),
                        'iconurl' => '',
                        'avatarurl' => '/cdn/avatar/', // Find a way to set this dynamic (see WebController::avatar for reference)
                        'secure' => intval(Request::createFromGlobals()->isSecure()),
                        'author' => 'MyHordes',
                        'language' => $language,
                        'version' => '0.1',
                        'generator' => 'symfony',
                    ],
                ]
            ]
        ];

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
                        'avatar' => $user->getId() . "/" . $user->getAvatar()->getFilename() . "." . $user->getAvatar()->getFormat(),
                        'x' => $citizen->getZone() !== null ? $citizen->getZone()->getX() : '0',
                        'y' => $citizen->getZone() !== null ? $citizen->getZone()->getY() : '0',
                        'id' => $citizen->getId(),
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
                            'img' => $item->getPrototype()->getName(),
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
        }

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
                    'img' => $picto['icon']
                ],
                'list' => [
                    'name' => 'title',
                    'items' => []
                ]
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
            if($pastLife->getCitizen()->getAlive()) continue;
            $data['hordes']['data']['maps']['list']['items'][] = [
                'attributes' => [
                    'name' => $pastLife->getTown()->getName(),
                    'season' => $pastLife->getTown()->getSeason(),
                    'score' => $pastLife->getPoints(),
                    'd' => $pastLife->getDay(),
                    'id' => $pastLife->getTown()->getId(),
                    'v1' => 0
                ], 
                'value' => $pastLife->getLastWords()
            ];
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


}
?>
