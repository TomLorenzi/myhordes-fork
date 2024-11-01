<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\ConfMaster;
use App\Structures\MyHordesConf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class CustomAbstractCoreController
 * @method User getUser
 */
class CustomAbstractCoreController extends AbstractController {

    protected ConfMaster $conf;
    protected TranslatorInterface $translator;

    protected array $generatedLangs;
    protected array $allLangs;
    protected array $generatedLangsCodes;
    protected array $allLangsCodes;

    public function __construct(ConfMaster $conf, TranslatorInterface $translator) {
        $this->conf = $conf;
        $this->translator = $translator;

        $this->allLangs = $this->conf->getGlobalConf()->get(MyHordesConf::CONF_LANGS);
        $this->allLangsCodes = array_map(function($item) {return $item['code'];}, $this->allLangs);

        $this->generatedLangs = array_filter($this->allLangs, function($item) {
            return $item['generate'];
        });
        $this->generatedLangsCodes = array_map(function($item) {return $item['code'];}, $this->generatedLangs);

    }

    public function getUserLanguage( bool $ignore_profile_language = false ): string {
        if (!$ignore_profile_language && $this->getUser() && $this->getUser()->getLanguage())
            return $this->getUser()->getLanguage();

        $l = $this->container->get('request_stack')->getCurrentRequest()->getPreferredLanguage( array_diff( $this->allLangsCodes, ['ach'] ) );
        if ($l) $l = explode('_', $l)[0];
        return in_array($l, $this->allLangsCodes) ? $l : 'de';
    }

    public function renderAllFlashMessages(bool $byType): array {
        try {
            $session = $this->container->get('request_stack')->getSession();
            if (!$session instanceof FlashBagAwareSessionInterface) {
                return [];
            }

            $messages = $session->getFlashBag()->all();

            return $byType ? $messages : array_reduce($messages, fn(array $c, array $m) => [...$c,...$m], []);
        } catch (\Throwable $e) {
            return [];
        }
    }
}