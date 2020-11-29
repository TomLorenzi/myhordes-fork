<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\ConfMaster;
use App\Structures\EventConf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CustomAbstractController
 * @package App\Controller
 * @method User getUser()
 */
class CustomAbstractController extends AbstractController {

    protected ConfMaster $conf;

    public function __construct(ConfMaster $conf) {
        $this->conf = $conf;
    }
    
    protected function render(string $view, array $parameters = [], Response $response = null): Response
    {
        if ($this->getUser() && $this->getUser()->getActiveCitizen())
            $current_event = $this->conf->getCurrentEvent($this->getUser()->getActiveCitizen()->getTown());
        else $current_event = $this->conf->getCurrentEvent();

        if ($current_event->active()) {
            $parameters = array_merge($parameters, [
                'custom_css' => $current_event->get(EventConf::EVENT_CSS, 'event')
            ]);
        }

        return parent::render($view, $parameters, $response);
    }
}