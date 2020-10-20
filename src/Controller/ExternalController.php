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
use App\Service\ErrorHelper;
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
use Error;
use Exception;
    use SimpleXMLElement;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Annotation\Route;
    use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
    use Symfony\Contracts\Translation\TranslatorInterface;
    use Symfony\Component\HttpFoundation\Request;

    class ExternalController extends InventoryAwareController {
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

        public function __construct( EntityManagerInterface $em, InventoryHandler $ih, CitizenHandler $ch,
                                     ActionHandler $ah, TimeKeeperService $tk, DeathHandler $dh,
                                     PictoHandler $ph, TranslatorInterface $translator, GameFactory $gf,
                                     RandomGenerator $rg, ItemFactory $if, LogTemplateHandler $lh,
                                     ConfMaster $conf, ZoneHandler $zh, UserHandler $uh,
                                     CrowService $armbrust ) {
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
         * @var SURLLobj
         */
        private $SURLLobj;

        /**
         * @Route("/jx/disclaimer/{id}", name="disclaimer", condition="request.isXmlHttpRequest()")
         * @return Response
         */
        public function disclaimer(Request $request, int $id): Response {
            $app = $this->entity_manager->getRepository(ExternalApp::class)->find($id);
            if(!$app||$app->getTesting())
                return $this->redirect($this->generateUrl('initial_landing'));
            /** @var User $user */
            $user = $this->getUser();
            $key = $user->getExternalId();

            return $this->render('ajax/public/disclaimer.html.twig', [
                'ex' => $app,
                'key' => $key
            ]);
        }

        /**
         * @Route("/jx/docs", name="docs", condition="request.isXmlHttpRequest()")
         * @return Response
         */
        public function documentation(Request $request): Response {
            return $this->render('ajax/public/apidocs.html.twig', []);
        }


        /**
         * @Route("/api/x/json/{type}", methods={"GET", "POST"})
         * @return Response
        */
        public function api_json($type = ''): Response {

            $APP_KEY = $this->getRequestParam('appkey');
            if($APP_KEY===false) {
                $data = ["error" => "invalid_appkey"];
                $type = 'internalerror';
            }

            /** @var ExternalApp $app */
            $app = $this->entity_manager->getRepository(ExternalApp::class)->findOneBy(['secret' => $APP_KEY]);

            if(!$app) {
                $data = ["error" => "invalid_appkey"];
                $type = 'internalerror';
            }

            switch(true) {
                case $type === 'internalerror':
                    if(!isset($data)) {
                        $data = [
                            "error" => "server_error",
                            "error_description" => "UnknownAction(default)"
                        ];
                    }
                break;
                case $type === '':
                    $data = [
                        "error" => "server_error",
                        "error_description" => "UnknownAction(default)"
                    ];
                break;
                case $type === 'status':
                    $data = [
                        "attack" => $this->time_keeper->isDuringAttack(),
                        "maintain" => is_file($this->getParameter('kernel.project_dir')."/public/maintenance/.active")
                    ];
                break;
                case $type === 'items':
                    $SURLL_request = ['items' => [
                        'languages' => ['de', 'en', 'es', 'fr'],
                        'fields' => [
                            'img',
                            'name'
                        ]
                    ]];

                    $fields = $this->getRequestParam('fields');
                    $filter = $this->getRequestParam('filters');
                    $langue = $this->getRequestParam('languages');

                    
                    if($fields!=false) {
                        $SURLL_request['items']['fields'] = $this->SURLL_preparser($fields);
                    }
                    if($filter!=false) {
                        $SURLL_request['items']['filters'] = $this->SURLL_preparser($filter);
                    }
                    if($langue!=false) {
                        $SURLL_request['items']['languages'] = $this->SURLL_preparser($langue);
                    }

                    $data = $this->getItemsData($SURLL_request);
                break;
                case $type === 'debug': $data = $this->getDebugdata(); break;
                default:
                    $USER_KEY = $this->getRequestParam('userkey');
                    if($USER_KEY===false) {
                        $data = ["error" => "invalid_userkey"];
                    } else {
                        $user = $this->entity_manager->getRepository(User::class)->findOneBy(['externalId' => $USER_KEY]);
                        if (!$user) {
                            $data = ["error" => "invalid_userkey"];
                        } else {
                            switch(true) {
                                case $type==="me":
                                    $SURLL_request = ['user' => [
                                        'filters' => $user->getId(),
                                        'languages' => ['de', 'en', 'es', 'fr'],
                                        'fields' => [
                                            'id',
                                            'isGhost'
                                        ]
                                    ]];

                                    $fields = $this->getRequestParam('fields');
                                    $langue = $this->getRequestParam('languages');

                                    if($fields!=false) {
                                        $SURLL_request['user']['fields'] = $this->SURLL_preparser($fields);
                                    }
                                    if($langue!=false) {
                                        $SURLL_request['user']['languages'] = $this->SURLL_preparser($langue);
                                    }

                                    $data = $this->getUserData($SURLL_request, $user->getId());
                                break;
                                case $type==="user":
                                    $user_id = intval($this->getRequestParam('id'));
                                    if($user_id!=false||$user_id>0) {
                                        $SURLL_request = ['user' => [
                                            'filters' => $user_id,
                                            'languages' => ['de', 'en', 'es', 'fr'],
                                            'fields' => [
                                                'id',
                                                'isGhost'
                                            ]
                                        ]];

                                        $fields = $this->getRequestParam('fields');
                                        $langue = $this->getRequestParam('languages');
                                        
                                        if($fields!=false) {
                                            $SURLL_request['user']['fields'] = $this->SURLL_preparser($fields);
                                        }
                                        if($langue!=false) {
                                            $SURLL_request['user']['languages'] = $this->SURLL_preparser($langue);
                                        }

                                        $data = $this->getUserData($SURLL_request, $user->getId());
                                    } else {
                                        $data = ["error" => "invalid_userid"];
                                    }
                                break;
                                case $type==="map":
                                    $map_id = intval($this->getRequestParam('mapId'));
                                    if($map_id!=false||$map_id>0) {
                                        $SURLL_request = ['map' => [
                                            'filters' => $map_id,
                                            'languages' => ['de', 'en', 'es', 'fr'],
                                            'fields' => ['date', 'days', 'season', 'id', 'hei', 'wid', 'bonusPts', 'conspiracy', 'custom']
                                        ]];

                                        $fields = $this->getRequestParam('fields');
                                        $langue = $this->getRequestParam('languages');

                                        if($fields!=false) {
                                            $SURLL_request['map']['fields'] = $this->SURLL_preparser($fields);
                                        }
                                        if($langue!=false) {
                                            $SURLL_request['map']['languages'] = $this->SURLL_preparser($langue);
                                        }

                                        $data = $this->getMapData($SURLL_request, $user->getId());
                                    } else {
                                        $data = ["error" => "invalid_mapid"];
                                    }
                                break;
                            }
                        }
                    }
            }
            return $this->json( $data );
        }

        private function getRequestParam($param) {
            $request = Request::createFromGlobals();
            $this->request = $request;

            $val = $request->request->get($param);
            if(trim($val)==='') {
                $val = $request->query->get($param);
            }

            if(trim($val)==='') {
                return false;
            } else {
                return $val;
            }
        }

        private function SURLL_preparser($surll_str): array {
            preg_match_all('/\.[a-z0-9\-]+|[a-z0-9\-]+|\(|\)/i', $surll_str, $surll_arr);
            $this->SURLLobj = $surll_arr[0];
            return $this->SURLL_parser();
        }

        private function SURLL_parser(): array {
            $parsed = [];
            while(count($this->SURLLobj)>0) {
                $surll_item = array_shift($this->SURLLobj);
                if($surll_item==="(") {
                    continue;
                } else if($surll_item===")") {
                    return $parsed;
                } else if($surll_item[0]===".") {
                    $last_surll_item = array_pop($parsed);
                    $name = "";
                    if(is_string($last_surll_item)) {
                        $name = $last_surll_item;
                        $last_surll_item = [];
                        $last_surll_item[$name] = [];
                    } else {
                        $name = array_keys($last_surll_item)[0];
                    }
                    $last_surll_item[$name][substr($surll_item, 1)] = $this->SURLL_parser($this->SURLLobj);
                    $parsed[] = $last_surll_item;
                } else {
                    $parsed[] = $surll_item;
                }
            }
            return $parsed;
        }

        private function getItemVal(ItemPrototype $item, $key) {
            switch(true) {
                case $key==="name":
                    return $item->getLabel();
                case $key==="desc":
                    return $item->getDescription();
                case $key==="cat":
                    return $item->getCategory()->getLabel();
                //case $key==="parent_category":
                    //return $item->getCategory()->getParent() ? $item->getCategory()->getParent()->getLabel() : false;
                default:
                    return false;
            }
        }

        private function getItemsData($SURLL_request): array {
            $data = [];
            if(isset($SURLL_request['items']['filters'])&&is_array($SURLL_request['items']['filters'])) {
                $filters = [];
                foreach($SURLL_request['items']['filters'] as $key => $val) {
                    if(is_string($val)) {
                        $filters[] = $val;
                    }
                }
                $items = $this->entity_manager->getRepository(ItemPrototype::class)->findBy(['icon' => $filters]);
            } else {
                $items = $this->entity_manager->getRepository(ItemPrototype::class)->findAll();
            }
            /** @var ItemPrototype $ItemProto */
            foreach ( $items as $ItemProto ) {
                $icon = $ItemProto->getIcon();
                $item = [];
                foreach($SURLL_request['items']['fields'] as $field) {
                    switch(true) {
                        case $field==='hid':
                            $item['hid']= $ItemProto->getId();
                        break; 
                        case $field==='img':
                            $item['img']= $icon;
                        break;
                        case $field==='uid':
                            $item['uid']= $icon;
                        break;
                        case $field==='heavy':
                            $item['heavy']= $ItemProto->getHeavy();
                        break;
                        case $field==='deco':
                            $item['deco']= $ItemProto->getDeco();
                        break;
                        case $field==='guard':
                            $item['guard']= $ItemProto->getWatchpoint();
                        break;
                    }
                }
                foreach($SURLL_request['items']['languages'] as $lang) {
                    if(!is_string($lang)||strlen($lang)!=2) continue;
                    foreach($SURLL_request['items']['fields'] as $field) {
                        $field_val = $this->getItemVal($ItemProto, $field);
                        if($field_val!=false) {
                            if(!isset($item[$field])) $item[$field]= [];
                            $item[$field][$lang]= $this->translator->trans($field_val, [], 'items', $lang);
                        }
                    }
                }
                $data[$icon] = $item;
            }
            return $data;
        }

        private function getUserData($SURLL_request, $originalUserID): array {
            $user= $this->entity_manager->getRepository(User::class)->findOneBy(['id' => $SURLL_request['user']['filters']]);
            if (!$user) {
                return ["error" => "UnknownUser"];
            }
            $current_citizen= $user->getActiveCitizen();
            $user_data = [];
            foreach($SURLL_request['user']['fields'] as $field) {
                switch(true) {
                    case $field==="id":
                        $user_data['id']= $user->getId();
                    break;
                    case $field==="name":
                        $user_data['name']= $user->getUsername();
                    break;
                    case $field==="avatar":
                        $has_avatar = $user->getAvatar();
                        if($has_avatar) {
                            $user_data['avatar']= $this->generateUrl('app_web_avatar', ['uid' => $user->getId(), 'name' => $has_avatar->getFilename(), 'ext' => $has_avatar->getFormat()], UrlGeneratorInterface::ABSOLUTE_URL);
                        } else $user_data['avatar'] = false;
                    break;
                    //case $field==="homeMessage": // It's a future feature because isn't existe now
                    //  $user_data['homeMessage']= $user->getHomeMessage();
                    //break;
                    case $field==="isGhost":
                        $user_data['isGhost']= ($current_citizen === null);
                    break;
                    case ($current_citizen && $field==="hero"):
                        $user_data['hero']= $current_citizen->getProfession()->getId()>0 ? true : false;
                    break;
                    case ($current_citizen && $field==="dead"):
                        $user_data['dead']= $current_citizen->getAlive();
                    break;
                    case ($current_citizen && $field==="job"):
                        $user_data['job']= $current_citizen->getProfession()->getName();
                    break;
                    case ($current_citizen && $field==="out"):
                        $user_data['out']= $current_citizen->getWalkingDistance()>0 ? true : false;
                    break;
                    case ($current_citizen && $field==="baseDef"):
                        $user_data['baseDef']= $current_citizen->getHome()->getAdditionalDefense();
                    break;
                    case ($current_citizen && $field==="ban"):
                        $user_data['ban']= $current_citizen->getBanished();
                    break;
                    case ($current_citizen && $field==="x"):
                        $zone = $current_citizen->getZone();
                        $user_data['x']= $zone ? $zone->getX() : 0;
                    break;
                    case ($current_citizen && $field==="y"):
                        $zone = $current_citizen->getZone();
                        $user_data['y']= $zone ? $zone->getY() : 0;
                    break;
                    case ($current_citizen && $field==="map"):
                        $user_data['map']= $this->getMapData(['map' => [
                            'filters' => $current_citizen->getTown()->getId(),
                            'languages' => ['de', 'en', 'es', 'fr'],
                            'fields' => ['date', 'days', 'season', 'id', 'hei', 'wid', 'bonusPts', 'conspiracy', 'custom']
                        ]], $originalUserID);
                    break;
                    case ($user->getId()===$originalUserID && $field==="playedMaps"):
                        $user_data['playedMaps']= "playedMaps";
                    break;
                    default:
                        if(is_array($field)) {
                            foreach($field as $ProtoFieldName => $ProtoFieldValue) {
                                switch(true) {
                                    case ($current_citizen && $ProtoFieldName==="map"):
                                        if(!isset($ProtoFieldValue['languages'])) $ProtoFieldValue['languages']= ['fr','en','de','es'];
                                        if(!isset($ProtoFieldValue['fields'])) $ProtoFieldValue['fields']= ['date', 'days', 'season', 'id', 'hei', 'wid', 'bonusPts', 'conspiracy', 'custom'];
                                        $ProtoFieldValue['filters']= $current_citizen->getTown()->getId();
                                        $user_data['map']= $this->getMapData($ProtoFieldValue, $originalUserID);
                                    break;
                                }
                            }
                        }
                }
            }
            return $user_data;
        }

        private function getMapData($SURLL_request, $originalUserID): array {
            $user= $this->entity_manager->getRepository(User::class)->findOneBy(['id' => $originalUserID]);
            $town= $this->entity_manager->getRepository(Town::class)->findOneBy(['id' => $SURLL_request['map']['filters']]);
            if (!$town) {
                return ["error" => "UnknownMap"];
            }

            $x_min = $x_max = $y_min = $y_max = 0;
            foreach ( $town->getZones() as $zone ) {
                /** @var Zone $zone */
                $x_min = min($zone->getX(), $x_min);
                $x_max = max($zone->getX(), $x_max);
                $y_min = min($zone->getY(), $y_min);
                $y_max = max($zone->getY(), $y_max);
            }

            $data = [];
            foreach($SURLL_request['map']['fields'] as $field) {
                switch(true) {
                    case $field==="id":
                        $data['id']= $town->getId();
                    break;
                    case $field==="date":
                        $now = new \DateTime();
                        $data['date']= $now->format('Y-m-d H:m:s');
                    break;
                    case $field==="wid":
                        $data['wid']= abs($x_min) + abs($x_max) + 1;
                    break;
                    case $field==="hei":
                        $data['hei']= abs($y_min) + abs($y_max) + 1;
                    break;
                    case $field==="conspiracy": //insurection
                    break;
                    case $field==="days":
                        $data['days']= $town->getDay();
                    break;
                }
            }
            return $data;
        }

        private function getDebugdata(): array {
            $town_id = intval($this->getRequestParam('tid'));
            if($town_id!=false||$town_id>0) {
                $town = $this->entity_manager->getRepository(Town::class)->findOneBy(['id' => $town_id]);
                if($town) {
                    $towns = [ $town ];
                } else {
                    $towns = $this->entity_manager->getRepository(Town::class)->findOpenTown();
                }
            } else {
                $towns = $this->entity_manager->getRepository(Town::class)->findOpenTown();
            }
            $data = [];
            /** @var Town $town */
            foreach($towns as $town) {
                $x_min = $x_max = $y_min = $y_max = 0;
                /** @var Zone $zone */
                foreach ( $town->getZones() as $zone ) {
                    $x_min = min($zone->getX(), $x_min);
                    $x_max = max($zone->getX(), $x_max);
                    $y_min = min($zone->getY(), $y_min);
                    $y_max = max($zone->getY(), $y_max);
                }
                $town_data = [
                    'id' => $town->getId(),
                    'name' => $town->getName(),
                    'day' => $town->getDay(),
                    'height' => abs($y_min)+abs($y_max)+1,
                    'width' => abs($x_min)+abs($x_max)+1,
                    'type' => ['','RNE','RE','PANDE'][$town->getType()->getId()],
                    'language' => $town->getLanguage(),
                    'zone' => []
                ];
                /** @var Zone $zone */
                foreach ( $town->getZones() as $zone ) {
                    $zone_data = [
                        'x' => $zone->getX()-$x_min,
                        'y' => $y_max-$zone->getY(),
                        'km' => $this->zone_handler->getZoneKm($zone),
                        'remaining_excavation' => $zone->getDigs(),
                        'zombies' => $zone->getZombies(),
                        'is_town' => $zone->getDistance()<1,
                        'items' => false,
                        'building' => false
                    ];
                    $item_buffer = [];
                    foreach ($zone->getFloor()->getItems() as $item) {
                        $item_uid = implode('_', [
                            $item->getPrototype()->getIcon(),
                            $item->getPoison(),
                            $item->getBroken()
                        ]);
                        if(!isset($item_buffer[$item_uid])) {
                            $item_buffer[$item_uid] = [
                                'uid' => $item->getPrototype()->getIcon(),
                                'poison' => $item->getPoison(),
                                'broken' => $item->getBroken(),
                                'count' => 1
                            ];
                        } else {
                            $item_buffer[$item_uid]['count']++;
                        }
                    }
                    foreach ($item_buffer as $item) {
                        if($zone_data['items']===false) {
                            $zone_data['items'] = [];
                        } $zone_data['items'][]= $item;
                    }
                    if($zone->getPrototype()) {
                        $zone_data['building'] = [
                            'name' => $this->translator->trans($zone->getPrototype()->getLabel(), [], "game", $town->getLanguage()),
                            'type' => $zone->getPrototype()->getId(),
                            'sandpile' => $zone->getBuryCount(),
                            'remaining_blueprint' => $zone->getBlueprint(),
                            'remaining_excavation' => $zone->getRuinDigs()
                        ];
                    }
                    $town_data['zone'][] = $zone_data;
                }
                $data[]= $town_data;
            }
            return $data;
        }

    }
?>