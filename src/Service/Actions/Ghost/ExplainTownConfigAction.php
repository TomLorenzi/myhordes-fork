<?php

namespace App\Service\Actions\Ghost;

use Adbar\Dot;
use App\Entity\Town;
use Exception;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class ExplainTownConfigAction
{
    public function __construct(
        private TranslatorInterface $translator,
    ) { }

    /**
     * @param Town|array $town
     * @return string[]
     */
    public function __invoke(
        Town|array $town,
    ): array
    {
        $conf = is_a($town, Town::class) ? $town->getConf() : $town;
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