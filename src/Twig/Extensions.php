<?php


namespace App\Twig;


use Adbar\Dot;
use App\Entity\Award;
use App\Entity\AwardPrototype;
use App\Entity\Hook;
use App\Entity\Item;
use App\Entity\Town;
use App\Entity\TownSlotReservation;
use App\Entity\ItemProperty;
use App\Entity\ItemPrototype;
use App\Entity\User;
use App\Enum\UserSetting;
use App\Service\ConfMaster;
use App\Service\EventProxyService;
use App\Service\GameFactory;
use App\Service\LogTemplateHandler;
use App\Service\UserHandler;
use App\Structures\MyHordesConf;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Extensions extends AbstractExtension implements GlobalsInterface
{
    private TranslatorInterface $translator;
    private UrlGeneratorInterface $router;
    private UserHandler $userHandler;
    private EntityManagerInterface $entityManager;
    private GameFactory $gameFactory;
    private ConfMaster $conf;
    private EventProxyService $events;

    public function __construct(TranslatorInterface $ti, UrlGeneratorInterface $r, UserHandler $uh, EntityManagerInterface $em, GameFactory $gf, ConfMaster $c, EventProxyService $events) {
        $this->translator = $ti;
        $this->router = $r;
        $this->userHandler = $uh;
        $this->entityManager = $em;
        $this->gameFactory = $gf;
        $this->conf = $c;
        $this->events = $events;

    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('instance_of', [$this, 'instance_of']),
            new TwigFilter('to_date',  [$this, 'create_date']),
            new TwigFilter('is_granted',  [$this, 'check_granted']),
            new TwigFilter('has_unlocked',  [$this, 'check_unlocked']),
            new TwigFilter('bin_contains',  [$this, 'check_flag_1']),
            new TwigFilter('bin_overlaps',  [$this, 'check_flag_2']),
            new TwigFilter('restricted',  [$this, 'user_is_restricted']),
            new TwigFilter('restricted_until',  [$this, 'user_restricted_until']),
            new TwigFilter('whitelisted',  [$this, 'town_whitelisted']),
            new TwigFilter('explain',  [$this, 'town_conf_explained']),
            new TwigFilter('openFor',  [$this, 'town_openFor']),
            new TwigFilter('conf',  [$this, 'town_conf']),
            new TwigFilter('items',  [$this, 'item_prototypes_with']),
            new TwigFilter('group_titles',  [$this, 'group_titles']),
            new TwigFilter('watchpoint',  [$this, 'fetch_watch_points']),
            new TwigFilter('related',  [$this, 'user_relation']),
            new TwigFilter('filesize',  [$this, 'format_filesize']),
            new TwigFilter('dogname',  [$this, 'dogname']),
            new TwigFilter('color',  [$this, 'color']),
            new TwigFilter('textcolor',  [$this, 'color_tx']),
            new TwigFilter('translated_title',  [$this, 'translatedTitle']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('instance_of', 	[$this, 'instance_of']),
            new TwigFunction('to_date',     	[$this, 'create_date']),
            new TwigFunction('help_btn',  	[$this, 'help_btn'], ['is_safe' => array('html')]),
            new TwigFunction('help_lnk',  	[$this, 'help_lnk'], ['is_safe' => array('html')]),
            new TwigFunction('tooltip',   	[$this, 'tooltip'], ['is_safe' => array('html')]),
            new TwigFunction('conf',      	[$this, 'conf']),
			new TwigFunction('hook', 			[ExtensionsRuntime::class, 'execute_hooks'], ['is_safe' => array('html')]),
			new TwigFunction('hostname',  	[$this, 'gethostname']),
        ];
    }

    public function getGlobals(): array
    {
        return [];
    }

    public function instance_of($object, string $classname): bool {
        return $classname === 'object' ? is_object($object) : is_a($object, $classname);
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

    /**
     * @param User $u
     * @return string
     */
    public function dogname(User $u): string {
        return LogTemplateHandler::generateDogName( $u->getActiveCitizen() ? $u->getActiveCitizen()->getId() : 0, $this->translator );
    }

    /**
     * @param string $bin
     * @return string
     */
    public function color(string $bin): string {
        return '#' . bin2hex($bin);
    }

    /**
     * @param string $color
     * @return string
     */
    public function color_tx(string $color): string {
        if (empty($color)) return '#000';
        if (mb_substr($color,0,1) === '#') $color = mb_substr($color,1);

        switch (mb_strlen($color)) {
            case 3: case 4: $color = "{$color[0]}{$color[0]}{$color[1]}{$color[1]}{$color[2]}{$color[2]}"; break;
            case 6: case 8: $color = substr($color, 0, 6); break;
            default: $color = 'ffffff';
        }

        return (
            hexdec( substr($color, 0, 2) ) * .299 +
            hexdec( substr($color, 2, 2) ) * .587 +
            hexdec( substr($color, 4, 2) ) * .114
        ) > 150 ? '#000000' : '#ffffff';


    }

    /**
     * @param User $u
     * @param string $feature
     * @return bool
     */
    public function check_unlocked(User $u, string $feature): bool {
        return $this->userHandler->checkFeatureUnlock($u, $feature, false);
    }

    public function help_btn(string $tooltipContent): string {
        return "<a class='help-button'><div class='tooltip help'>$tooltipContent</div>" . $this->translator->trans("Hilfe", [], "global") . "</a>";
    }

    public function help_lnk(string $name, string $controller = null, array $args = []): string {
        $link = $controller !== null ? $this->router->generate($controller, $args) : "";
        return "<span class='helpLink'><span class='helptitle'>" . $this->translator->trans("Spielhilfe:", [], "global") . "</span> <a class='link' x-ajax-href='$link' target='_blank'>$name</a></span>";
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

    public function user_relation(User $user, User $other, int $relation): bool {
        return $this->userHandler->checkRelation($user,$other,$relation);
    }

    public function format_filesize(int $size): string {
        if     ($size >= 1099511627776) return round($size/1099511627776, 0) . ' TB';
        elseif ($size >= 1073741824)    return round($size/1073741824, 1) . ' GB';
        elseif ($size >= 1048576)       return round($size/1048576, 2) . ' MB';
        elseif ($size >= 1024)          return round($size/1024, 0) . ' KB';
        else                            return $size . ' B';
    }

    public function town_whitelisted(Town $town, ?User $user = null): bool {
        return $user
            ? ($this->entityManager->getRepository(TownSlotReservation::class)->count(['town' => $town, 'user' => $user]) === 1)
            : ($this->entityManager->getRepository(TownSlotReservation::class)->count(['town' => $town]) > 0);
    }

    public function town_openFor(Town $town, User $user = null): bool {
        return $this->gameFactory->userCanEnterTown($town,$user,$this->entityManager->getRepository(TownSlotReservation::class)->count(['town' => $town]) > 0);
    }

    public function town_conf(Town $town, ?string $property = null, mixed $default = null): mixed {
        $c = $this->conf->getTownConfiguration( $town );
        return $property === null ? $c->raw() : $c->get($property,$default);
    }

    public function conf(): MyHordesConf {
        return $this->conf->getGlobalConf();
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

    /**
     * @param Award[] $awards
     * @return array
     */
    public function group_titles(array $awards): array {

        $g = [];
        $p = [];

        foreach ($awards as $award) {
            $id = 'custom';
            if ($award->getPrototype() !== null) {
                if ($award->getPrototype()->getAssociatedPicto() !== null) {
                    $p[$id = $award->getPrototype()->getAssociatedPicto()->getId()] = $award->getPrototype()->getAssociatedPicto();
                } else $id = 'single';
            }

            if (!isset($g[$id])) $g[$id] = [];
            $g[$id][] = $award;
        }

        uksort($g, function($a,$b) use (&$p) {
            if ($a === 'custom' && $b === 'custom') return 0;
            if ($a === 'custom') return -1;
            if ($b === 'custom') return 1;
            if ($a === 'single' && $b === 'single') return 0;
            if ($a === 'single') return -1;
            if ($b === 'single') return 1;
            return $p[$b]->getRare() <=> $p[$a]->getRare() ?: $p[$a]->getPriority() <=> $p[$b]->getPriority() ?: $p[$a]->getId() <=> $p[$b]->getId();
        });

        foreach ($g as &$list) usort( $list, function( Award $a, Award $b ) {
            if (!$a->getPrototype() && !$b->getPrototype()) return $a->getId() <=> $b->getId();
            else if (!$a->getPrototype()) return -1;
            else if (!$b->getPrototype()) return  1;
            else return $a->getPrototype()->getUnlockQuantity() <=> $b->getPrototype()->getUnlockQuantity();
        } );
        return $g;
    }

    /**
     * @param Item|ItemPrototype $item
     * @return int
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function fetch_watch_points(Item|ItemPrototype $item): int {

        if ($this->instance_of($item, ItemPrototype::class)) return $item->getWatchpoint();
        if ($item->getInventory() === null) return $item->getPrototype()->getWatchpoint();

        $town = null;
        if ($item->getInventory()->getTown()) $town = $item->getInventory()->getTown();
        else if ($item->getInventory()->getCitizen()) $town = $item->getInventory()->getCitizen()->getTown();
        else if ($item->getInventory()->getZone()) $town = $item->getInventory()->getZone()->getTown();
        else if ($item->getInventory()->getHome()) $town = $item->getInventory()->getHome()->getCitizen()->getTown();
        else if ($item->getInventory()->getRuinZone()) $town = $item->getInventory()->getRuinZone()->getZone()->getTown();
        else if ($item->getInventory()->getRuinZoneRoom()) $town = $item->getInventory()->getRuinZoneRoom()->getZone()->getTown();

        if ($town === null) return $item->getPrototype()->getWatchpoint();
        return $this->events->buildingQueryNightwatchDefenseBonus( $town, $item );
    }

    public function translatedTitle(string|Award|AwardPrototype|User $subject, User $object, ?User $object2 = null): string {

        /** @var $owner User */
        /** @var $me User */
        [$base, $owner, $me] = match (true) {
            is_string( $subject ) => [$subject, $object, $object2],
            is_a( $subject, Award::class ) => [$subject->getPrototype()?->getTitle(), $object, $object2],
            is_a( $subject, AwardPrototype::class ) => [$subject->getTitle(), $object, $object2],
            is_a( $subject, User::class ) => [$subject->getActiveTitle()?->getPrototype()?->getTitle(), $subject, $object],
            default => [null,null,null]
        };

        if ($base === null) return '';
        $lang = match ($owner?->getSetting( UserSetting::TitleLanguage )) {
            null, '_them' => $me?->getLanguage(),
            '_me' => $owner?->getLanguage() ?? $me?->getLanguage(),
            'de', 'en', 'fr', 'es' => $owner?->getSetting( UserSetting::TitleLanguage ),
            default => null
        };

        if($owner?->getPreferredPronounTitle() == User::PRONOUN_FEMALE){
            $base = $base . "_f";
        }

        return $this->translator->trans($base, [], 'game', $lang);
    }

	public function gethostname(): string {
		return getenv('LOAD_HOST') ?: gethostname();
	}

    public function town_conf_explained(?array $conf): array {
        if (empty($conf)) return [];

        $cache = [];
        foreach ((new Dot($conf))->flatten() as $key => $value)
            $cache[] = match (true) {
                ($key === 'open_town_limit' || $key === 'open_town_grace') => $this->translator->trans('Abweichende Bedingungen für den automatischen Stadtabbruch', [], 'ghost'),
                ($key === 'stranger_day_limit' || $key === 'stranger_citizen_limit') => $this->translator->trans('Abweichende Bedingungen für den Mysteriösen Fremden', [], 'ghost'),
                ($key === 'lock_door_until_full') => $value
                    ? $this->translator->trans('Stadttor ist bis zum Start der Stadt verschlossen', [], 'ghost')
                    : $this->translator->trans('Stadttor kann vor dem Start der Stadt geöffnet werden', [], 'ghost'),
                (
                    str_starts_with( $key, 'map_params.buried_ruins.digs.' ) ||
                    str_starts_with( $key, 'map_params.dig_chances.' ) ||
                    str_starts_with( $key, 'explorable_ruin_params.dig_chance' ) ||
                    str_starts_with( $key, 'explorable_ruin_params.plan_limits.' )
                ) => $this->translator->trans('Angepasste allgemeine Fundchancen', [], 'ghost'),
                (
                    str_starts_with( $key, 'map.' ) ||
                    str_starts_with( $key, 'margin_custom.' ) ||
                    str_starts_with( $key, 'ruins.' ) ||
                    str_starts_with( $key, 'explorable_ruins.' ) ||
                    str_starts_with( $key, 'map_params.' )
                ) => $this->translator->trans('Angepasste Karteneinstellungen', [], 'ghost'),
                str_starts_with( $key, 'well.' ) => $this->translator->trans('Angepasste Brunnenmenge', [], 'ghost'),
                str_starts_with( $key, 'population.' ) => $this->translator->trans('Angepasste Einwohnerzahl', [], 'ghost'),
                str_starts_with( $key, 'zone_items.' ) => $this->translator->trans('Angepasste Ergiebigkeit von Zonen', [], 'ghost'),
                str_starts_with( $key, 'ruin_items.' ) => $this->translator->trans('Angepasste Ergiebigkeit von Ruinen', [], 'ghost'),
                str_starts_with( $key, 'overrides.' ) => $this->translator->trans('Angepasste Fundraten', [], 'ghost'),
                str_starts_with( $key, 'explorable_ruin_params.' ) => $this->translator->trans('Angepasste Karteneinstellungen für begehbare Ruinen', [], 'ghost'),
                (
                    str_starts_with( $key, 'initial_buildings.' ) ||
                    str_starts_with( $key, 'unlocked_buildings.' ) ||
                    str_starts_with( $key, 'disabled_buildings.' )
                ) => $this->translator->trans('Angepasste Baustellen', [], 'ghost'),
                (
                    str_starts_with( $key, 'disabled_jobs.' ) ||
                    str_starts_with( $key, 'disabled_roles.' )
                ) => $this->translator->trans('Angepasste Berufe und Rollen', [], 'ghost'),
                str_starts_with( $key, 'spiritual_guide.' ) => $this->translator->trans('Angepasste Konfiguration für Spirituelle Führer', [], 'ghost'),
                str_starts_with( $key, 'bank_abuse.' ) => $this->translator->trans('Angepasste Banksperre', [], 'ghost'),
                str_starts_with( $key, 'times.' ) => $this->translator->trans('Angepasste Dauer für automatische Aktionen', [], 'ghost'),
                (
                    str_starts_with( $key, 'initial_chest.' ) ||
                    str_starts_with( $key, 'distribute_items.' ) ||
                    str_starts_with( $key, 'distribution_distance.' )
                ) => $this->translator->trans('Abweichende initiale Verteilung von Gegenständen ', [], 'ghost'),
                str_starts_with( $key, 'instant_pictos.' ) => $this->translator->trans('Abweichende Verteilung von Auszeichnungen', [], 'ghost'),
                (
                    str_starts_with( $key, 'estimation.' ) ||
                    str_starts_with( $key, 'modifiers.watchtower_estimation_threshold' ) ||
                    str_starts_with( $key, 'modifiers.watchtower_estimation_offset' )
                ) => $this->translator->trans('Abweichendes Verhalten der Wachturmabschätzung', [], 'ghost'),
                ($key === 'modifiers.allow_redig') => $value
                    ? $this->translator->trans('Erneutes Buddeln aktiviert', [], 'ghost')
                    : $this->translator->trans('Erneutes Buddeln deaktiviert', [], 'ghost'),
                ($key === 'modifiers.preview_item_assemblage') => $value
                    ? $this->translator->trans('Vorschau für das Zusammenbauen von Gegenständen aktiviert', [], 'ghost')
                    : $this->translator->trans('Vorschau für das Zusammenbauen von Gegenständen deaktiviert', [], 'ghost'),
                str_starts_with( $key, 'modifiers.poison.' ) => $this->translator->trans('Abweichendes Verhalten von Gift', [], 'ghost'),
                str_starts_with( $key, 'modifiers.citizen_attack' ) => $this->translator->trans('Abweichendes Verhalten von Angriffen zwischen Bürgern', [], 'ghost'),
                str_starts_with( $key, 'modifiers.complaints' ) => $this->translator->trans('Abweichendes Verhalten von Beschwerden', [], 'ghost'),
                ($key === 'modifiers.carry_extra_bag') => $value
                    ? $this->translator->trans('Tragen von mehreren Taschen möglich', [], 'ghost')
                    : $this->translator->trans('Tragen von mehreren Taschen unmöglich', [], 'ghost'),
                ($key === 'modifiers.building_attack_damage') => $value
                    ? $this->translator->trans('Gebäudeschaden aktiv', [], 'ghost')
                    : $this->translator->trans('Gebäudeschaden inaktiv', [], 'ghost'),
                str_starts_with( $key, 'modifiers.camping.' ) => $this->translator->trans('Abweichendes Campingverhalten', [], 'ghost'),
                str_starts_with( $key, 'modifiers.daytime.' ) => $this->translator->trans('Abweichende Tageszeit', [], 'ghost'),
                str_starts_with( $key, 'modifiers.' ) => $this->translator->trans('Abweichende Balancing-Einstellungen', [], 'ghost'),

                str_starts_with( $key, 'features.camping' ) => $this->translator->trans('Camping', [], 'ghost') . ' ' . ($value ? $this->translator->trans('aktiv', [], 'global') : $this->translator->trans('inaktiv', [], 'global')),
                str_starts_with( $key, 'features.words_of_heros' ) => $this->translator->trans('Heldentafel', [], 'game') . ' ' . ($value ? $this->translator->trans('aktiv', [], 'global') : $this->translator->trans('inaktiv', [], 'global')),
                str_starts_with( $key, 'features.escort.enabled' ) => $this->translator->trans('Eskorte', [], 'game') . ' ' . ($value ? $this->translator->trans('aktiv', [], 'global') : $this->translator->trans('inaktiv', [], 'global')),
                str_starts_with( $key, 'features.nightmode' ) => $this->translator->trans('Nachtmodus', [], 'ghost') . ' ' . ($value ? $this->translator->trans('aktiv', [], 'global') : $this->translator->trans('inaktiv', [], 'global')),
                str_starts_with( $key, 'features.shaman' ) => $this->translator->trans('Abweichender Schamanenmodus', [], 'ghost'),
                str_starts_with( $key, 'features.xml_feed' ) => $this->translator->trans('Externe APIs', [], 'ghost') . ' ' . ($value ? $this->translator->trans('aktiv', [], 'global') : $this->translator->trans('inaktiv', [], 'global')),
                str_starts_with( $key, 'features.citizen_alias' ) => $this->translator->trans('Bürger-Aliase', [], 'ghost') . ' ' . ($value ? $this->translator->trans('aktiv', [], 'global') : $this->translator->trans('inaktiv', [], 'global')),
                str_starts_with( $key, 'features.ghoul_mode' ) => $this->translator->trans('Abweichender Ghulmodus', [], 'ghost'),
                str_starts_with( $key, 'features.hungry_ghouls' ) => $this->translator->trans('Hungrige Ghule', [], 'ghost') . ' ' . ($value ? $this->translator->trans('aktiv', [], 'global') : $this->translator->trans('inaktiv', [], 'global')),
                str_starts_with( $key, 'features.all_poison' ) => $this->translator->trans('Paradies der Giftmörder', [], 'ghost') . ' ' . ($value ? $this->translator->trans('aktiv', [], 'global') : $this->translator->trans('inaktiv', [], 'global')),
                str_starts_with( $key, 'features.shun' ) => $this->translator->trans('Beschwerden', [], 'ghost') . ' ' . ($value ? $this->translator->trans('aktiv', [], 'global') : $this->translator->trans('inaktiv', [], 'global')),
                str_starts_with( $key, 'features.nightwatch.enabled' ) => $this->translator->trans('Nachtwache', [], 'ghost') . ' ' . ($value ? $this->translator->trans('aktiv', [], 'global') : $this->translator->trans('inaktiv', [], 'global')),
                str_starts_with( $key, 'features.nightwatch.instant' ) => $this->translator->trans('Sofortige Nachtwache', [], 'ghost') . ' ' . ($value ? $this->translator->trans('aktiv', [], 'global') : $this->translator->trans('inaktiv', [], 'global')),
                str_starts_with( $key, 'features.attacks' ) => $this->translator->trans('Abweichende Angriffsstärke', [], 'ghost'),
                str_starts_with( $key, 'features.give_all_pictos' ) => $this->translator->trans('Alle Auszeichnungen', [], 'ghost') . ' ' . ($value ? $this->translator->trans('aktiv', [], 'global') : $this->translator->trans('inaktiv', [], 'global')),
                str_starts_with( $key, 'features.enable_pictos' ) => $this->translator->trans('Auszeichnungen', [], 'ghost') . ' ' . ($value ? $this->translator->trans('aktiv', [], 'global') : $this->translator->trans('inaktiv', [], 'global')),
                str_starts_with( $key, 'features.give_soulpoints' ) => $this->translator->trans('Seelenpunkte', [], 'ghost') . ' ' . ($value ? $this->translator->trans('aktiv', [], 'global') : $this->translator->trans('inaktiv', [], 'global')),
                str_starts_with( $key, 'features.' ) => $this->translator->trans('Abweichende Funktions-Einstellungen', [], 'ghost'),


                default => null,
            };

        $cache = array_unique( array_filter( $cache ) );
        if (empty($cache)) $cache[] = $this->translator->trans('Abweichende allgemeine Einstellungen', [], 'ghost');
        return $cache;
    }
}
