<?php

namespace App\Translation;

use App\Entity\Citizen;
use App\Entity\User;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ICUTranslator implements TranslatorInterface, TranslatorBagInterface, LocaleAwareInterface
{
    private TranslatorInterface $_decorated;
    private Security $_security;

    public function __construct(TranslatorInterface $translator, Security $security) {
        $this->_decorated = $translator;
        $this->_security = $security;
    }

    static $gender_map = [ 0 => 'none', 1 => 'male', 2 => 'female' ];

    public function trans(?string $id, array $parameters = [], string $domain = null, string $locale = null): string
    {
        /** @var User $u */
        $u = $this->_security->getUser();
        $pass_trough = ['{__icu}' => $u ? $u->getUseICU() : false];
        foreach ($parameters as $key => $value) {
            if (is_a( $value, User::class )) {
                /** @var User $value */
                $pass_trough[substr($key,0,-1) . '__gender}'] = static::$gender_map[(int)$value->getPreferredPronoun()];
                $pass_trough[$key] = $value->getName();
            } elseif (is_a( $value, Citizen::class )) {
                /** @var Citizen $value */
                $pass_trough[substr($key,0,-1) . '__gender}'] = static::$gender_map[(int)$value->getUser()->getPreferredPronoun()];
                $pass_trough[$key] = $value->getName();
            } else $pass_trough[$key] = $value;
        }
        return $this->_decorated->trans($id,$pass_trough,$domain,$locale);
    }

    public function getCatalogue(string $locale = null): MessageCatalogueInterface
    {
        return $this->_decorated->getCatalogue($locale);
    }

    public function setLocale(string $locale)
    {
        $this->_decorated->setLocale($locale);
    }

    public function getLocale(): string {
        return $this->_decorated->getLocale();
    }
}
