<?php

namespace App\Controller;

use App\Service\ConfMaster;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class CustomAbstractController extends AbstractController {

    protected ConfMaster $conf;

    public function __construct(ConfMaster $conf) {
        $this->conf = $conf;
    }
    
    protected function render(string $view, array $parameters = [], Response $response = null): Response
    {
        $parameters = array_merge($parameters, [
            'custom_css' => $this->conf->getCurrentEvent()['css']
        ]);

        return parent::render($view, $parameters, $response);
    }
}