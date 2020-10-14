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
                            'dead' => !$citizen->getAlive(),
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
                'rewards' => [],
                'maps' => []
            ];

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
                        $child[0] = "<![CDATA[{$v["value"]}]]>";
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
