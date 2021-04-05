<?php


namespace App\Twig;


use App\Entity\ItemProperty;
use App\Entity\ItemPrototype;
use App\Entity\User;
use App\Service\UserHandler;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Extensions extends AbstractExtension  implements GlobalsInterface
{
    private TranslatorInterface $translator;
    private UrlGeneratorInterface $router;
    private UserHandler $userHandler;
    private EntityManagerInterface $entityManager;

    public function __construct(TranslatorInterface $ti, UrlGeneratorInterface $r, UserHandler $uh, EntityManagerInterface $em) {
        $this->translator = $ti;
        $this->router = $r;
        $this->userHandler = $uh;
        $this->entityManager = $em;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('instance_of', [$this, 'instance_of']),
            new TwigFilter('to_date',  [$this, 'create_date']),
            new TwigFilter('is_granted',  [$this, 'check_granted']),
            new TwigFilter('bin_contains',  [$this, 'check_flag_1']),
            new TwigFilter('bin_overlaps',  [$this, 'check_flag_2']),
            new TwigFilter('restricted',  [$this, 'user_is_restricted']),
            new TwigFilter('restricted_until',  [$this, 'user_restricted_until']),
            new TwigFilter('items',  [$this, 'item_prototypes_with']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('instance_of', [$this, 'instance_of']),
            new TwigFunction('to_date',  [$this, 'create_date']),
            new TwigFunction('help_btn', [$this, 'help_btn'], ['is_safe' => array('html')]),
            new TwigFunction('help_lnk', [$this, 'help_lnk'], ['is_safe' => array('html')]),
            new TwigFunction('tooltip', [$this, 'tooltip'], ['is_safe' => array('html')]),
        ];
    }

    public function getGlobals(): array
    {
        return [];
    }

    public function instance_of($object, $classname): bool {
        return is_a($object, $classname);
    }

    /**
     * @param string $object
     * @return DateTime
     * @throws Exception
     */
    public function create_date(string $object): DateTime {
        return new DateTime($object);
    }

    /**
     * @param User $u
     * @param string $role
     * @return bool
     */
    public function check_granted(User $u, string $role): bool {
        return $this->userHandler->hasRole($u,$role);
    }

    public function help_btn(string $tooltipContent): string {
        return "<a class='help-button'><div class='tooltip help'>$tooltipContent</div>" . $this->translator->trans("Hilfe", [], "global") . "</a>";
    }

    public function help_lnk(string $name, string $controller = null, array $args = []): string {
        $link = $controller !== null ? $this->router->generate($controller, $args) : "";
        return "<span class='helpLink'>" . $this->translator->trans("Spielhilfe:", [], "global") . " <a class='link' x-ajax-href='$link' target='_blank'>$name</a></span>";
    }

    public function tooltip(string $content, string $classes = null): string {
        return "<div class='tooltip $classes'>$content</div>";
    }

    public function check_flag_1(int $val, int $mask): bool {
        return ($val & $mask) === $mask;
    }

    public function check_flag_2(int $val, int $mask): bool {
        return $val & $mask;
    }

    public function user_is_restricted(User $user, ?int $mask = null): bool {
        return $this->userHandler->isRestricted($user,$mask);
    }

    public function user_restricted_until(User $user, ?int $mask = null): ?DateTime {
        return $this->userHandler->getActiveRestrictionExpiration($user,$mask);
    }

    /**
     * @param string $tag
     * @return ItemPrototype[]
     */
    public function item_prototypes_with(string $tag): array {
        /** @var ItemProperty|null $p */
        $p = $this->entityManager->getRepository(ItemProperty::class)->findOneByName($tag);
        if ($p === null) return [];
        return $p->getItemPrototypes()->getValues();
    }
}