<?php


namespace App\EventSubscriber;

use App\Entity\User;
use App\Enum\Configuration\MyHordesSetting;
use App\Service\ConfMaster;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LanguageSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ConfMaster $conf,
        private readonly Security $security
    ){ }
    public function onKernelRequest(RequestEvent $event) {
        if (!$event->getRequest()->isXmlHttpRequest()) {
            $pathInfos = explode( '/', $event->getRequest()->getPathInfo());
            if (count($pathInfos) >= 2) {
                [, $first_path_segment] = explode('/', $event->getRequest()->getPathInfo());
                $allLangsCodes = array_map(function ($item) {
                    return $item['code'];
                }, array_filter($this->conf->getGlobalConf()->get(MyHordesSetting::Languages), function ($item) {
                    return $item['generate'];
                }));

                if (in_array($first_path_segment, $allLangsCodes)) {
                    $event->getRequest()->setLocale($first_path_segment);
                    $event->getRequest()->getSession()->set('_user_lang', $first_path_segment);
                    return;
                }
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

    public function applyLanguage(ControllerEvent $event) {
        /** @var User $user */
        $user = $this->security->getUser();
		if ($user === null) return;

        if ($user->getLanguage() && $event->getRequest()->getLocale() !== $user?->getLanguage())
            $event->getRequest()->getSession()->set('_user_lang', $user->getLanguage());

		$path = $event->getRequest()->getPathInfo();
		if (strstr($path, 'admin') && ($user?->getAdminLang() ?? $user?->getLanguage()) && $event->getRequest()->getLocale() !== ($user?->getAdminLang() ?? $user?->getLanguage())) {
			$event->getRequest()->getSession()->set('_admin_lang', $user->getAdminLang() ?? $user->getLanguage());
		}
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST =>    [['onKernelRequest', 20]],
            KernelEvents::CONTROLLER => [['applyLanguage', -20]],
        ];
    }
}