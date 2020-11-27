<?php

namespace App\Controller;

use App\Service\ConfMaster;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class CustomAbstractController extends AbstractController {

    protected ConfMaster $conf;
    protected $current_event;

    public function __construct(ConfMaster $conf) {
        $this->conf = $conf;
        $this->current_event = $this->conf->getCurrentEvent();
    }
    
    protected function render(string $view, array $parameters = [], Response $response = null): Response
    {
        if ($this->current_event !== null) {
            $parameters = array_merge($parameters, [
                'custom_css' => $this->current_event['css']
            ]);
        }

        return parent::render($view, $parameters, $response);
    }
}