<?php


namespace App\EventSubscriber;

use App\Service\ConfMaster;
use App\Structures\MyHordesConf;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LanguageSubscriber implements EventSubscriberInterface
{
    private ConfMaster $conf;

    public function __construct(ConfMaster $confMaster){
        $this->conf = $confMaster;
    }
    public function onKernelRequest(RequestEvent $event) {
        if (!$event->getRequest()->isXmlHttpRequest()) {
            [,$first_path_segment] = explode( '/', $event->getRequest()->getPathInfo() );
            $allLangsCodes = array_map(function($item) {return $item['code'];}, array_filter($this->conf->getGlobalConf()->get(MyHordesConf::CONF_LANGS), function($item) {
                return $item['generate'];
            }));

            if (in_array($first_path_segment, $allLangsCodes)) {
                $event->getRequest()->setLocale( $first_path_segment );
                $event->getRequest()->getSession()->set('_user_lang',$first_path_segment);
                return;
            }
        }

        // try to see if the locale has been set as a _locale routing parameter
        if ($locale = $event->getRequest()->attributes->get('_locale')) {
            $event->getRequest()->getSession()->set('_locale', $locale);
        } elseif ($event->getRequest()->getSession()->has('_user_lang')) {
            $event->getRequest()->setLocale($event->getRequest()->getSession()->get('_user_lang', null));
        } elseif ($event->getRequest()->getSession()->has('_town_lang')) {
            $event->getRequest()->setLocale($event->getRequest()->getSession()->get('_town_lang', null));
        } elseif ($event->getRequest()->getSession()->has('_locale')) {
            $event->getRequest()->setLocale($event->getRequest()->getSession()->get('_locale', null));
        } elseif ($langs = $event->getRequest()->getLanguages()) {
            $event->getRequest()->setLocale( $langs[0] );
        }

        $path = $event->getRequest()->getPathInfo();
        if (strstr($path, 'admin') && $event->getRequest()->getSession()->has('_admin_lang')) {
            $event->getRequest()->setLocale($event->getRequest()->getSession()->get('_admin_lang', null));
        }
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}