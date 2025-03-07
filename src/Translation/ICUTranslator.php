<?php

namespace App\Translation;

use App\Entity\Citizen;
use App\Entity\User;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\DisallowedRawHtml\DisallowedRawHtmlExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\MarkdownConverter;
use Symfony\Bundle\SecurityBundle\Security;
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

    static array $gender_map = [ 0 => 'none', 1 => 'male', 2 => 'female' ];

    public function trans(?string $id, array $parameters = [], string $domain = null, string $locale = null): string
    {
        /** @var User $u */
        $u = $this->_security->getUser();
        $pass_trough = [
            'ref__icu' => $u !== null ? ($u->getUseICU() ? 'on' : 'off') : 'off',
            'ref__gender' => 'none'
        ];

        $got_citizen = false;
        $no_md = false;

        foreach ($parameters as $key => $value) {

            if ($key === '__no_md') {
                $no_md = $value;
                continue;
            }

            $key = str_replace(['{','}'],'', $key);
            if (is_a( $value, User::class )) {
                /** @var User $value */
                $pass_trough["{$key}__gender"] = static::$gender_map[(int)$value->getPreferredPronoun()];
                if (!$got_citizen) {
                    $pass_trough["ref__gender"] = $pass_trough["{$key}__gender"];
                    $got_citizen = true;
                }
                $pass_trough[$key] = $value->getName();
            } elseif (is_a( $value, Citizen::class )) {
                /** @var Citizen $value */
                $pass_trough["{$key}__gender"] = static::$gender_map[(int)$value->getUser()->getPreferredPronoun()];
                if (!$got_citizen) {
                    $pass_trough["ref__gender"] = $pass_trough["{$key}__gender"];
                    $got_citizen = true;
                }
                $pass_trough[$key] = $value->getName();
            } elseif (!is_array( $value )) {
                $pass_trough[$key] = $value;
                $pass_trough["{$key}__copy"] = $value;
            }

            if (isset($parameters["{$key}__tag"])) {
                $attr = '';
                if (isset($parameters["{$key}__attr"]))
                    $attr = is_array( $parameters["{$key}__attr"] )
                        ? implode(' ', array_map( fn(string $key, mixed $value) => "$key=\"$value\"", array_keys( $parameters["{$key}__attr"] ), array_values( $parameters["{$key}__attr"] ) ))
                        : $parameters["{$key}__attr"];
                $pass_trough[$key] = "<{$parameters["{$key}__tag"]} $attr class=\"" . ($parameters["{$key}__class"] ?? '') . "\">{$pass_trough[$key]}</{$parameters["{$key}__tag"]}>";
            }
        }

        $string = $this->_decorated->trans($id,$pass_trough,$domain,$locale);

        if (!$no_md) {
            $config = [
                'html_input' => "allow"
            ];
            $environment = new Environment($config);
            $environment->addExtension(new CommonMarkCoreExtension());
            $environment->addExtension(new DisallowedRawHtmlExtension());
            $environment->addExtension(new StrikethroughExtension());
            $environment->addExtension(new TableExtension());
            $environment->addExtension(new TaskListExtension());

            # We put the string in 1 line and replace multiple spaces with only one
            $string = preg_replace("#\n#mi", "", $string);
            $string = preg_replace("# {2,}#mi", " ", $string);

            $converter = new MarkdownConverter($environment);
            $string = $converter->convert($string);
            $string = preg_replace('#<p>(.*)</p>#i', '$1', $string);
            $string = html_entity_decode($string);
        }

        return trim($string);
    }

    public function getCatalogue(string $locale = null): MessageCatalogueInterface
    {
        return $this->_decorated->getCatalogue($locale);
    }

    public function setLocale(string $locale): void
    {
        $this->_decorated->setLocale($locale);
    }

    public function getLocale(): string {
        return $this->_decorated->getLocale();
    }

    public function getCatalogues(): array
    {
        return $this->_decorated->getCatalogues();
    }
}
