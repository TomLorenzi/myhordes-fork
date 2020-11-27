<?php

namespace App\Controller;

use App\Service\ConfMaster;
use App\Structures\EventConf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class CustomAbstractController extends AbstractController {

    protected ConfMaster $conf;
    protected EventConf $current_event;

    public function __construct(ConfMaster $conf) {
        $this->conf = $conf;
        $this->current_event = $this->conf->getCurrentEvent();
    }
    
    protected function render(string $view, array $parameters = [], Response $response = null): Response
    {
        if ($this->current_event->active()) {
            $parameters = array_merge($parameters, [
                'custom_css' => $this->current_event->get(EventConf::EVENT_CSS, 'event')
            ]);
        }

        return parent::render($view, $parameters, $response);
    }
}