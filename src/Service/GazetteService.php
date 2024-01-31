<?php


namespace App\Service;

use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\CouncilEntry;
use App\Entity\CouncilEntryTemplate;
use App\Entity\Gazette;
use App\Entity\GazetteEntryTemplate;
use App\Entity\GazetteLogEntry;
use App\Entity\Town;
use App\Entity\Zone;
use App\Translation\T;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Contracts\Translation\TranslatorInterface;

class GazetteService
{
    private EntityManagerInterface $entity_manager;
    private LogTemplateHandler $log;
    private TranslatorInterface $translator;
    private RandomGenerator $rand;

    public function __construct(
        EntityManagerInterface $em, LogTemplateHandler $lh, TranslatorInterface $translator, RandomGenerator $rand)
    {
        $this->entity_manager = $em;
        $this->log = $lh;
        $this->translator = $translator;
        $this->rand = $rand;
    }

    public function check_gazettes(Town $town) {
        $need = [ $town->getDay() => true, $town->getDay() + 1 => true, $town->getDay() + 2 => true ];

        foreach ($town->getGazettes() as $gazette)
            if (isset($need[$gazette->getDay()])) $need[$gazette->getDay()] = false;

        foreach ($need as $day => $create)
            if ($create) $town->addGazette((new Gazette())->setDay($day));

    }

    protected function parseLog( $template, array $variables, $lowercase = false, $domain = 'gazette' ): String {
        $variableTypes = $template->getVariableTypes();
        $transParams = $this->log->parseTransParams($variableTypes, $variables);

        try {
            if ($lowercase) {
                $proto = $this->translator->trans($template->getText(), [], $domain);
                $lowercase = (substr($proto, 0, 1) !== strtolower(substr($proto, 0, 1)));
            }
            $text = $this->translator->trans($template->getText(), $transParams, $domain);
            if ($lowercase)
                $text = strtolower(substr($text, 0, 1)) . substr($text, 1);
        }
        catch (Exception $e) {
            $text = "null";
        }

        return $text;
    }

    public function parseGazetteLog(GazetteLogEntry $gazetteLogEntry, $lowercase = false): string
    {
        $flavour = [
            GazetteEntryTemplate::FollowUpTypeDoubt => [
                null,
                $this->translator->trans('Nun, das sage ich...', [],'gazette'),
                $this->translator->trans('Das ist doch Unsinn...', [], 'gazette'),
                $this->translator->trans('Die einen sagen dies, die anderen das...', [], 'gazette'),
                $this->translator->trans('Ich sags ja nur...', [], 'gazette'),
                $this->translator->trans('Komm schon, glaub mir.', [], 'gazette'),
            ],
            GazetteEntryTemplate::FollowUpTypeBad => [
                null,
                $this->translator->trans('Was für eine Organisation...', [], 'gazette'),
                $this->translator->trans('Wir werden nicht lange durchhalten, das sage ich euch.', [], 'gazette'),
                $this->translator->trans('Noch so eine Nacht und wir werden nicht mehr hier sein, um über so etwas zu reden.', [], 'gazette'),
                $this->translator->trans('Das war knapp.', [], 'gazette'),
                $this->translator->trans('Pfff...', [], 'gazette'),
            ],
        ];
        $txt = $this->parseLog($gazetteLogEntry->getTemplate(), $gazetteLogEntry->getVariables(), $lowercase);

        if ($txt && $gazetteLogEntry->getTemplate() && $gazetteLogEntry->getFollowUp() > 0 &&
            isset($flavour[ $gazetteLogEntry->getTemplate()->getFollowUpType() ]) && isset($flavour[ $gazetteLogEntry->getTemplate()->getFollowUpType() ][ $gazetteLogEntry->getFollowUp() ]))

            $txt .= " {$flavour[ $gazetteLogEntry->getTemplate()->getFollowUpType() ][ $gazetteLogEntry->getFollowUp() ]}";
        return $txt;
    }

    public function parseCouncilLog(CouncilEntry $councilEntry): string
    {
        return $this->parseLog($councilEntry->getTemplate(), $councilEntry->getVariables(), false, 'council');
    }

    protected function generate_wind_entry(Gazette $gazette, int $day): string {
        if ($gazette->getWindDirection() === 0) return '';
        $townTemplate = $this->rand->pick( $this->entity_manager->getRepository(GazetteEntryTemplate::class)->findBy(['type' => GazetteEntryTemplate::TypeGazetteWind]) );
        if ($townTemplate === null) return '';

        $variables = [];

        switch ($gazette->getWindDirection()) {
            case Zone::DirectionNorthWest:
                $variables['sector'] = T::__('Nordwesten', 'game');
                $variables['sector2'] = T::__('im Nordwesten', 'game');
                break;
            case Zone::DirectionNorth:
                $variables['sector'] = T::__('Norden', 'game');
                $variables['sector2'] = T::__('im Norden', 'game');
                break;
            case Zone::DirectionNorthEast:
                $variables['sector'] = T::__('Nordosten', 'game');
                $variables['sector2'] = T::__('im Nordosten', 'game');
                break;
            case Zone::DirectionWest:
                $variables['sector'] = T::__('Westen', 'game');
                $variables['sector2'] = T::__('im Westen', 'game');
                break;
            case Zone::DirectionEast:
                $variables['sector'] = T::__('Osten', 'game');
                $variables['sector2'] = T::__('im Osten', 'game');
                break;
            case Zone::DirectionSouthWest:
                $variables['sector'] = T::__('Südwesten', 'game');
                $variables['sector2'] = T::__('im Südwesten', 'game');
                break;
            case Zone::DirectionSouth:
                $variables['sector'] = T::__('Süden', 'game');
                $variables['sector2'] = T::__('im Süden', 'game');
                break;
            case Zone::DirectionSouthEast:
                $variables['sector'] = T::__('Südosten', 'game');
                $variables['sector2'] = T::__('im Südosten', 'game');
                break;
        }
        $news = new GazetteLogEntry();
        $news->setDay($day)->setGazette($gazette)->setTemplate($townTemplate)->setVariables($variables);
        $this->entity_manager->persist($news);
        return $this->parseGazetteLog($news);
    }

    protected function decompose_requirements( GazetteEntryTemplate $g ): array {

        $base = ($g->getRequirement() < 100) ? $g->getRequirement() * 10 : $g->getRequirement();
        $class = intval(floor($base / 100));
        $arg1 = intval(floor(($base-$class*100) / 10));
        $arg2 = intval($base-$class*100-$arg1*10);

        switch ($class) {
            case 1:case 2:case 3:case 5:
                return [ $class * 10, $arg1 ];
            case 4:case 6:
                return [ $class*10 + $arg1, $arg2 ];
            default:
                return [0,0];
        }
    }

    /**
     * @param Gazette $gazette
     * @param array $criteria
     * @param Citizen[] $survivors
     * @param Citizen[] $death_inside
     * @param Citizen[] $death_outside
     * @return GazetteEntryTemplate|null
     */
    protected function select_gazette_template( Gazette $gazette, array $criteria, array $survivors = [], array $death_inside = [], array $death_outside = [] ): ?GazetteEntryTemplate {
        $templates = array_filter( $this->entity_manager->getRepository(GazetteEntryTemplate::class)->findBy($criteria), function(GazetteEntryTemplate $g) use ($gazette,$survivors,$death_inside,$death_outside) {
            list($class,$arg) = $this->decompose_requirements( $g );
            switch ( $class ) {
                case GazetteEntryTemplate::BaseRequirementCitizen:        return count( $survivors )    >= $arg;
                case GazetteEntryTemplate::BaseRequirementCadaver:        return count( $death_inside ) >= $arg;
                case GazetteEntryTemplate::BaseRequirementCitizenCadaver: return count( $survivors )    >= $arg && count( $death_inside ) >= $arg;
                case GazetteEntryTemplate::BaseRequirementCitizenInTown:  return count( array_filter( $survivors, fn(Citizen $c) => $c->getZone() === null ) ) >= $arg;
                case GazetteEntryTemplate::RequiresMultipleDehydrations:  return count( $survivors ) >= $arg && count( array_filter( $death_outside, fn(Citizen $c) => $c->getCauseOfDeath()->getRef() === CauseOfDeath::Dehydration ) ) >= 2;
                case GazetteEntryTemplate::RequiresMultipleSuicides:      return count( $survivors ) >= $arg && count( array_filter( $death_outside, fn(Citizen $c) => $c->getCauseOfDeath()->getRef() === CauseOfDeath::Cyanide ) ) >= 2;
                case GazetteEntryTemplate::RequiresMultipleInfections:    return count( $survivors ) >= $arg && count( array_filter( $death_outside, fn(Citizen $c) => $c->getCauseOfDeath()->getRef() === CauseOfDeath::Infection ) ) >= 2;
                case GazetteEntryTemplate::RequiresMultipleVanished:      return count( $survivors ) >= $arg && count( array_filter( $death_outside, fn(Citizen $c) => $c->getCauseOfDeath()->getRef() === CauseOfDeath::Vanished ) ) >= 2;
                case GazetteEntryTemplate::RequiresMultipleHangings:      return count( $survivors ) >= $arg && count( array_filter( $death_outside, fn(Citizen $c) => $c->getCauseOfDeath()->getRef() === CauseOfDeath::Hanging ) ) >= 2;
                case GazetteEntryTemplate::RequiresMultipleCrosses:       return count( $survivors ) >= $arg && count( array_filter( $death_outside, fn(Citizen $c) => $c->getCauseOfDeath()->getRef() === CauseOfDeath::ChocolateCross ) ) >= 2;
                case GazetteEntryTemplate::RequiresMultipleRedSouls:      return count( $survivors ) >= $arg && count( array_filter( $death_outside, fn(Citizen $c) => $c->getCauseOfDeath()->getRef() === CauseOfDeath::Haunted ) ) >= 2;
                case GazetteEntryTemplate::RequiresInvasion:              return $gazette->getInvasion() > 0;
                case GazetteEntryTemplate::RequiresAttackDeaths:          return count( $survivors ) >= $arg && $gazette->getDeaths() > 0;
                default: return true;
            }
        });

        return $this->rand->pick( $templates );
    }

    /**
     * @param Gazette $gazette
     * @param GazetteEntryTemplate $g
     * @param Citizen[] $survivors
     * @param Citizen[] $death_inside
     * @param Citizen[] $death_outside
     * @param Citizen|null $featured
     * @return array
     */
    protected function fill_template_variables( Gazette $gazette, GazetteEntryTemplate $g, array $survivors, array $death_inside, array $death_outside, ?Citizen $featured = null ): array {
        $variables = [];

        list($class,$arg) = $this->decompose_requirements( $g );

        $_add_elements = function( $name, array $list, ?Citizen $featured, $n, &$var ) {
            $elems = array_filter( $list, fn(Citizen $c) => $c !== $featured );
            if ($n > 0) shuffle($elems);
            if ($featured !== null) array_unshift( $elems, $featured );

            for ($i = 1; $i <= $n; $i++)
                $var["$name$i"] = (array_shift($elems))->getId();
            $var["{$name}s"] = count($elems);
        };

        foreach ($g->getVariableTypes() as ['name' => $name])
            switch ($name) {
                case 'poison':
                    $variables['poison'] = $this->rand->pick( [
                        T::__('Arsen','gazette'),T::__('Cyanolin','gazette'),T::__('Neurotwinin','gazette'),
                        T::__('Rizin','gazette'),T::__('Botulinumtoxin','gazette'),T::__('Virunoir','gazette'),
                        T::__('Chlorotwinat','gazette'),T::__('Kaliumchlorid','gazette'),T::__('Kurare','gazette'),
                        T::__('Zyanid (poison)','gazette'),T::__('Phenol','gazette'),T::__('Ponasulfat','gazette')
                    ] );
                    break;
                case 'location':
                    $variables['location'] = $this->rand->pick( [
                        T::__('in der Nähe des Gemüsegartens','gazette'),T::__('in der Nähe der südlichen Stadtmauer','gazette'),
                        T::__('am Stadttor','gazette'),T::__('am Aussichtsturm','gazette'),
                        T::__('am westlichen Flügel der Stadt','gazette'),T::__('an der nördlichen Stadtmauer','gazette'),
                        T::__('an unserer schwach gesicherten Flanke','gazette'),T::__('in der Nähe des Brunnens','gazette'),
                        T::__('an der östlichen Stadtmauer','gazette'),T::__('in der Stadtmauer am Stadttor','gazette'),
                    ] );
                    break;
                case 'mascot':
                    $variables['mascot'] = $this->rand->pick( [
                        T::__('Drops','gazette'),T::__('Whitie','gazette'),T::__('Fettwanst','gazette'),
                        T::__('Gizmo','gazette'),T::__('Romero','gazette'),T::__('Krümel','gazette'),
                        T::__('Trolli','gazette'),T::__('Warpie','gazette'),T::__('Panda','gazette'),
                        T::__('Urmel','gazette'),T::__('Nuffi','gazette'),T::__('Kampfzwerg','gazette'),
                        T::__('Brummbrumm','gazette'),T::__('Quälgeist','gazette'),T::__('Wuschel','gazette'),
                    ] );
                    break;
                case 'animal':
                    $variables['animal'] = $this->rand->pick( [
                        T::__('Ratte','gazette'),T::__('Ziege','gazette'),T::__('Pudel','gazette'),
                        T::__('Hamster','gazette'),T::__('Köter','gazette'),T::__('Schwein','gazette'),
                        T::__('Erdmännchen','gazette'),T::__('Wasserschwein','gazette'),T::__('Sumpfschildkröte','gazette'),
                        T::__('Kätzchen','gazette'),
                    ] );
                    break;
                case 'item':
                    $variables['item'] = $this->rand->pick( [
                        T::__('einen Haufen Gerümpel','gazette'),T::__('einen Holzstapel','gazette'),
                        T::__('einen Abfallberg', 'gazette'),
                        T::__('einen Haufen Schrott','gazette'),T::__('einen toter Baumstamm','gazette'),
                        T::__('eine vergessene Leiter','gazette'),T::__('einen Berg von Kisten','gazette'),
                        T::__('einen Turm aus Trümmern','gazette'),T::__('einen Haufen Plunder','gazette'),
                        T::__('einen Steinhaufen','gazette'),T::__('einen Haufen Ramsch','gazette'),
                    ] );
                    break;
                case 'random':
                    $variables['random'] = mt_rand(15,99);
                    break;
                case 'randomHour':
                    $variables['randomHour'] = mt_rand(0,12);
                    break;
            }

        switch ($class) {
            case GazetteEntryTemplate::BaseRequirementCitizen:
                $_add_elements( 'citizen', $survivors, $featured !== null && $featured->getAlive() ? $featured : null, $arg, $variables );
                break;

            case GazetteEntryTemplate::BaseRequirementCadaver:
                $_add_elements( 'cadaver', $death_inside, $featured !== null && !$featured->getAlive() ? $featured : null, $arg, $variables );
                break;

            case GazetteEntryTemplate::BaseRequirementCitizenCadaver:
                $_add_elements( 'citizen', $survivors, $featured !== null && $featured->getAlive() ? $featured : null, $arg, $variables );
                $_add_elements( 'cadaver', $death_inside, $featured !== null && !$featured->getAlive() ? $featured : null, $arg, $variables );
                break;

            case GazetteEntryTemplate::RequiresAttack:
                $attack = $gazette->getAttack();
                $variables['attack'] = $attack < 2000 ? 10 * (round($attack / 10)) : 100 * (round($attack / 100));
                break;

            case GazetteEntryTemplate::RequiresInvasion:
                $attack = $gazette->getAttack();
                $variables['attack'] = $attack < 2000 ? 10 * (round($attack / 10)) : 100 * (round($attack / 100));
                $variables['invasion'] = $gazette->getInvasion();
                break;

            case GazetteEntryTemplate::RequiresAttackDeaths:
                $attack = $gazette->getAttack();
                $variables['attack'] = $attack < 2000 ? 10 * (round($attack / 10)) : 100 * (round($attack / 100));
                $variables['deaths'] = $variables['cadavers'] = $gazette->getDeaths();
                if ($arg > 0) $_add_elements( 'citizen', $survivors, $featured !== null && $featured->getAlive() ? $featured : null, $arg, $variables );
                break;

            case GazetteEntryTemplate::RequiresDefense:
                $defense = $gazette->getDefense();
                $variables['defense'] = $defense < 2000 ? 10 * (round($defense / 10)) : 100 * (round($defense / 100));
                break;

            case GazetteEntryTemplate::RequiresDeaths:
                $variables['deaths'] = $variables['cadavers'] = $gazette->getDeaths();
                break;

            case GazetteEntryTemplate::RequiresMultipleDehydrations:
                $_add_elements('cadaver', array_filter( $death_outside, fn(Citizen $c) => $c->getCauseOfDeath()->getRef() === CauseOfDeath::Dehydration ), null, 0, $variables);
                if ($arg > 0) $_add_elements( 'citizen', $survivors, $featured !== null && $featured->getAlive() ? $featured : null, $arg, $variables );
                break;

            case GazetteEntryTemplate::RequiresMultipleSuicides:
                $_add_elements('cadaver', array_filter( $death_outside, fn(Citizen $c) => $c->getCauseOfDeath()->getRef() === CauseOfDeath::Cyanide ), null, 0, $variables);
                if ($arg > 0) $_add_elements( 'citizen', $survivors, $featured !== null && $featured->getAlive() ? $featured : null, $arg, $variables );
                break;

            case GazetteEntryTemplate::RequiresMultipleInfections:
                $_add_elements('cadaver', array_filter( $death_outside, fn(Citizen $c) => $c->getCauseOfDeath()->getRef() === CauseOfDeath::Infection ), null, 0, $variables);
                if ($arg > 0) $_add_elements( 'citizen', $survivors, $featured !== null && $featured->getAlive() ? $featured : null, $arg, $variables );
                break;

            case GazetteEntryTemplate::RequiresMultipleVanished:
                $_add_elements('cadaver', array_filter( $death_outside, fn(Citizen $c) => $c->getCauseOfDeath()->getRef() === CauseOfDeath::Vanished ), null, 0, $variables);
                if ($arg > 0) $_add_elements( 'citizen', $survivors, $featured !== null && $featured->getAlive() ? $featured : null, $arg, $variables );
                break;

            case GazetteEntryTemplate::RequiresMultipleHangings:
                $_add_elements('cadaver', array_filter( $death_outside, fn(Citizen $c) => $c->getCauseOfDeath()->getRef() === CauseOfDeath::Hanging ), null, 0, $variables);
                if ($arg > 0) $_add_elements( 'citizen', $survivors, $featured !== null && $featured->getAlive() ? $featured : null, $arg, $variables );
                break;

            case GazetteEntryTemplate::RequiresMultipleCrosses:
                $_add_elements('cadaver', array_filter( $death_outside, fn(Citizen $c) => $c->getCauseOfDeath()->getRef() === CauseOfDeath::ChocolateCross ), null, 0, $variables);
                if ($arg > 0) $_add_elements( 'citizen', $survivors, $featured !== null && $featured->getAlive() ? $featured : null, $arg, $variables );
                break;

            case GazetteEntryTemplate::RequiresMultipleRedSouls:
                $_add_elements('cadaver', array_filter( $death_outside, fn(Citizen $c) => $c->getCauseOfDeath()->getRef() === CauseOfDeath::Haunted ), null, 0, $variables);
                if ($arg > 0) $_add_elements( 'citizen', $survivors, $featured !== null && $featured->getAlive() ? $featured : null, $arg, $variables );
                break;

            case GazetteEntryTemplate::BaseRequirementCitizenInTown:
                $citizens = array_filter( $survivors, fn(Citizen $c) => $c->getZone() === null && $c !== $featured );
                shuffle($citizens);
                if ($featured !== null && $featured->getAlive() && $featured->getZone() === null) array_unshift( $citizens, $featured );

                for ($i = 1; $i <= $arg; $i++)
                    $variables['citizen' . $i] = (array_shift($citizens))->getId();
                $variables['citizens'] = count($citizens);

                break;
        }

        return $variables;
    }

    protected function enrichLogEntry( GazetteLogEntry $g ): GazetteLogEntry {
        if (!$g->getTemplate()) return $g;
        if ($g->getTemplate()->getFollowUpType() !== 0)
            $g->setFollowUp( max(0, mt_rand(-9,5)) );
        return $g;
    }

    /**
     * @param Gazette $gazette
     * @param Town $town
     * @param int $day
     * @param Citizen[] $survivors
     * @param Citizen[] $death_inside
     * @return bool
     */
    protected function gazette_section_town( Gazette $gazette, Town $town, int $day, array $survivors = [], array $death_inside = [] ): bool {
        if ( $gazette->getReactorExplosion() ) $criteria = [ 'type' => GazetteEntryTemplate::TypeGazetteReactor ];
        elseif ( $town->getDevastated() ) $criteria = [ 'name' => 'gazetteTownDevastated' ];
        elseif (count($death_inside) > 0 && count($death_inside) < 5 && $gazette->getDoor()) $criteria = [ 'type' => GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen ];
        elseif ($gazette->getDay() === 2 && count($death_inside) === 0) $criteria = [ 'type' => GazetteEntryTemplate::TypeGazetteDayOne ];
        else $criteria = [ 'type' => GazetteEntryTemplate::TypeGazetteNoDeaths + (min(count($death_inside), 3)) ];

        if ($townTemplate = $this->select_gazette_template($gazette, $criteria, $survivors, $death_inside)) {
            $variables = $this->fill_template_variables($gazette, $townTemplate, $survivors, $death_inside, []);
            $this->entity_manager->persist( $this->enrichLogEntry( (new GazetteLogEntry())->setDay($day)->setGazette($gazette)->setTemplate($townTemplate)->setVariables($variables) ) );
            return true;
        } else return false;
    }

    /**
     * @param Gazette $gazette
     * @param Town $town
     * @param int $day
     * @param Citizen[] $survivors
     * @param Citizen[] $death_inside
     * @param Citizen[] $death_outside
     * @return bool
     */
    protected function gazette_section_individual_deaths( Gazette $gazette, Town $town, int $day, array $survivors = [], array $death_inside = [], array $death_outside = [] ): bool {
        if (!$town->getDevastated() && !$gazette->getReactorExplosion() && count($death_outside) > 0) {

            $type = null; $featured_cadaver = null;

            // Check for multi deaths
            $d_list = [];
            foreach ($death_outside as $citizen)
                if ($citizen->getCauseOfDeath() && !isset($d_list[$citizen->getCauseOfDeath()->getRef()])) $d_list[$citizen->getCauseOfDeath()->getRef()] = 1;
                else $d_list[$citizen->getCauseOfDeath()->getRef()]++;

            $d_list = array_filter( $d_list, fn($v,$k) => $v >= 2 && in_array($k,[CauseOfDeath::Cyanide,CauseOfDeath::Dehydration,CauseOfDeath::Infection,CauseOfDeath::Vanished,CauseOfDeath::Hanging,CauseOfDeath::ChocolateCross,CauseOfDeath::Haunted]), ARRAY_FILTER_USE_BOTH );
            $focus = $this->rand->pick( array_keys($d_list) );

            if ($focus !== null) switch ($focus) {
                case CauseOfDeath::Cyanide:
                    $type = GazetteEntryTemplate::TypeGazetteMultiSuicide; break;
                case CauseOfDeath::Dehydration:
                    $type = GazetteEntryTemplate::TypeGazetteMultiDehydration; break;
                case CauseOfDeath::Infection:
                    $type = GazetteEntryTemplate::TypeGazetteMultiInfection; break;
                case CauseOfDeath::Vanished:
                    $type = GazetteEntryTemplate::TypeGazetteMultiVanished; break;
                case CauseOfDeath::Hanging:
                    $type = GazetteEntryTemplate::TypeGazetteMultiHanging; break;
                case CauseOfDeath::ChocolateCross:
                    $type = GazetteEntryTemplate::TypeGazetteMultiChocolateCross; break;
                case CauseOfDeath::Haunted:
                    $type = GazetteEntryTemplate::RequiresMultipleRedSouls; break;
            }

            // Check for individual deaths
            if ($type === null) {
                $featured_cadaver = $this->rand->pick( array_filter( $death_outside, fn(Citizen $c) => in_array($c->getCauseOfDeath()->getRef(), [
                    CauseOfDeath::Cyanide,
                    CauseOfDeath::Strangulation,
                    CauseOfDeath::Addiction,
                    CauseOfDeath::Dehydration,
                    CauseOfDeath::Poison,
                    CauseOfDeath::Haunted,
                    CauseOfDeath::Infection,
                    CauseOfDeath::Vanished,
                    CauseOfDeath::Hanging,
                    CauseOfDeath::ChocolateCross
                ]) ) );

                if ($featured_cadaver === null)
                    return false;

                switch ($featured_cadaver->getCauseOfDeath()->getRef()) {
                    case CauseOfDeath::Cyanide:
                    case CauseOfDeath::Strangulation:
                        $type = GazetteEntryTemplate::TypeGazetteSuicide;
                        break;

                    case CauseOfDeath::Addiction:
                        $type = GazetteEntryTemplate::TypeGazetteAddiction;
                        break;

                    case CauseOfDeath::Dehydration:
                        $type = GazetteEntryTemplate::TypeGazetteDehydration;
                        break;

                    case CauseOfDeath::Poison:
                        $type = GazetteEntryTemplate::TypeGazettePoison;
                        break;

                    case CauseOfDeath::Haunted:
                        $type = GazetteEntryTemplate::TypeGazetteRedSoul;
                        break;

                    case CauseOfDeath::Infection:
                        $type = GazetteEntryTemplate::TypeGazetteInfection;
                        break;

                    case CauseOfDeath::Vanished:
                        $type = GazetteEntryTemplate::TypeGazetteVanished;
                        break;
                    case CauseOfDeath::Hanging:
                        $type = GazetteEntryTemplate::TypeGazetteHanging;
                        break;
                    case CauseOfDeath::ChocolateCross:
                        $type = GazetteEntryTemplate::TypeGazetteChocolateCross;
                        break;
                }
            }

            if ($type === null) return false;

            $criteria = [ 'type' => $type ];

            if ($townTemplate = $this->select_gazette_template($gazette, $criteria, $survivors, $death_outside, $death_outside)) {
                $variables = $this->fill_template_variables($gazette, $townTemplate, $survivors, $death_outside, $death_outside, $featured_cadaver);
                $this->entity_manager->persist( $this->enrichLogEntry( (new GazetteLogEntry())->setDay($day)->setGazette($gazette)->setTemplate($townTemplate)->setVariables($variables) ) );
                return true;
            } else return false;

        } else return false;
    }

    /**
     * @param Gazette $gazette
     * @param Town $town
     * @param int $day
     * @param Citizen[] $survivors
     * @param Citizen[] $death_inside
     * @param Citizen[] $death_outside
     * @return bool
     */
    protected function gazette_section_role_deaths( Gazette $gazette, Town $town, int $day, array $survivors = [], array $death_inside = [], array $death_outside = [] ): bool {
        if (!$town->getDevastated() && !$gazette->getReactorExplosion() && count($death_outside) > 0) {

            $type = null;

            $shaman = $guide = $cata = $ghoul = false;
            foreach ($gazette->getVotesNeeded() as $role) {
                $shaman = $shaman || $role->getName() === 'shaman';
                $guide  = $guide  || $role->getName() === 'guide';
                $cata   = $cata   || $role->getName() === 'cata';
                $ghoul  = $ghoul  || $role->getName() === 'ghoul';
            }

            if ($shaman && $guide) $type = GazetteEntryTemplate::TypeGazetteGuideShamanDeath;
            elseif ($shaman)       $type = GazetteEntryTemplate::TypeGazetteShamanDeath;
            elseif ($guide)        $type = GazetteEntryTemplate::TypeGazetteGuideDeath;

            if ($type === null) return false;

            $criteria = [ 'type' => $type ];

            if ($townTemplate = $this->select_gazette_template($gazette, $criteria, $survivors, $death_inside, $death_outside)) {
                $variables = $this->fill_template_variables($gazette, $townTemplate, $survivors, $death_inside, $death_outside);
                $this->entity_manager->persist( $this->enrichLogEntry( (new GazetteLogEntry())->setDay($day)->setGazette($gazette)->setTemplate($townTemplate)->setVariables($variables) ) );
                return true;
            } else return false;
        } else return false;
    }

    protected function gazette_section_flavour( Gazette $gazette, Town $town, int $day, array $survivors = [], array $death_inside = [] ): bool {
        if ( $gazette->getReactorExplosion() || $town->getDevastated() || $gazette->getDeaths() > 0 ) return false;

        $criteria = [ 'type' => GazetteEntryTemplate::TypeGazetteFlavour ];

        if ($townTemplate = $this->select_gazette_template($gazette, $criteria, $survivors, $death_inside)) {
            $variables = $this->fill_template_variables($gazette, $townTemplate, $survivors, $death_inside, []);
            $this->entity_manager->persist( $this->enrichLogEntry( (new GazetteLogEntry())->setDay($day)->setGazette($gazette)->setTemplate($townTemplate)->setVariables($variables) ) );
            return true;
        } else return false;
    }

    public function ensureGazette(Town $town, ?int $day = null, ?bool &$created = null): ?Gazette {
        $day = min( $town->getDay(), $day ?? $town->getDay() );
        $gazette = $town->findGazette( $day, true );

        /** @var GazetteLogEntry[] $gazette_logs */
        $gazette_logs = $this->entity_manager->getRepository(GazetteLogEntry::class)->findBy(['gazette' => $gazette]);
        $wind = "";

        $created = false;
        if (count($gazette_logs) == 0) {
            $created = true;
            $death_inside = $gazette->getVictimBasedOnLocation(true);
            $death_outside = $gazette->getVictimBasedOnLocation(false);

            if (count($death_inside) > $gazette->getDeaths()) {
                $gazette->setDeaths(count($death_inside));
            }

            if ($day > 1)  {
                $survivors = [];
                foreach ($town->getCitizens() as $citizen) {
                    if(!$citizen->getAlive()) continue;
                    $survivors[] = $citizen;
                }

                $sections = 0;

                // 1. TOWN
                if ($this->gazette_section_town( $gazette, $town, $day, $survivors, $death_inside ))
                    $sections++;

                // 2. DEATHS
                if ($this->gazette_section_individual_deaths( $gazette, $town, $day, $survivors, $death_inside, $death_outside ))
                    $sections++;

                // 3. FLAVOURS
                if ($sections < 2 && $this->rand->chance(0.2))
                    $this->gazette_section_flavour( $gazette, $town, $day, $survivors, $death_inside );

                // 4. ELECTION
                $this->gazette_section_role_deaths( $gazette, $town, $day, $survivors, $death_inside, $death_outside );

                // 5. SEARCH TOWER
                $wind = $this->generate_wind_entry($gazette,$day);
            }
        }

        // Ensure the town has a wind direction log if applicable
        if (empty($wind) && $gazette->getWindDirection() !== 0)
            $this->generate_wind_entry($gazette,$day);

        return $gazette;
    }

    public function renderGazette( Town $town, ?int $day = null, bool $allow_dynamic_creation = false, ?string $lang = null ): array {
        $origLang = $this->translator->getLocale();
        if($lang !== null) {
            $this->translator->setLocale($lang);
        }

        $day = min( $town->getDay(), $day ?? $town->getDay() );
        $death_outside = $death_inside = [];

        /** @var Gazette $gazette */
        $gazette = $this->ensureGazette($town, $day, $created);

        if ($created && $allow_dynamic_creation)
            $this->entity_manager->flush();

        foreach ($gazette->getVictims() as $citizen) {
            if ($citizen->getAlive()) continue;
            if ($citizen->getCauseOfDeath()->getRef() == CauseOfDeath::NightlyAttack)
                $death_inside[] = $citizen;
            else
                $death_outside[] = $citizen;
        }

        $text = '';
        $wind = '';

        if ($day === 1) {
            $text = "<p>" . $this->translator->trans('Heute Morgen ist kein Artikel erschienen...', [], 'gazette') . "</p>";
            if ($town->isOpen() && !$town->getForceStartAhead()){
                $text .= "<p>" . $this->translator->trans('Die Stadt wird erst starten, wenn sie <strong>{population} Bürger hat</strong>.', ['{population}' => $town->getPopulation()], 'gazette') . "</p>" . "<a class='help-button'>" . "<div class='tooltip help'>" . $this->translator->trans("Falls sich dieser Zustand auch um Mitternacht noch nicht geändert hat, findet kein Zombieangriff statt. Der Tag wird dann künstlich verlängert.", [], 'global') . "</div>" . $this->translator->trans("Hilfe", [], 'global') . "</a>";
            } else {
                if($town->getForceStartAhead())
                    $text .= "<p>" . $this->translator->trans('Unsere Späher berichten, dass sie einen Mysteriösen Fremden in der Umgebung der Stadt gesichtet haben...', [], 'gazette') . '</p>';
                $text .= $this->translator->trans('Fangt schon mal an zu beten, Bürger - die Zombies werden um Mitternacht angreifen!', [], 'gazette');
            }
        } else {
            $gazette_logs = $this->entity_manager->getRepository(GazetteLogEntry::class)->findBy(['gazette' => $gazette]);
            $num = 0;

            $in_between = [
                T::__('Eilmeldung:', 'gazette'),
                T::__('Liebe Bürgerinnen und Bürger,', 'gazette'),
                T::__('Neue Nachrichten:', 'gazette'),
                T::__('Eine kleine Anekdote:', 'gazette'),
                T::__('Das muss hier auch noch erzählt werden:', 'gazette'),
                T::__('Folgendes:', 'gazette'),
                T::__('Das Neueste aus der Gerüchteküche:', 'gazette'),
                T::__('Die Gerüchteküche brodelt mal wieder:', 'gazette'),
                T::__('Klatsch und Tratsch:', 'gazette'),
                T::__('Aktuelles in Kürze:', 'gazette'),
                T::__('WICHTIG:', 'gazette'),
                T::__('Hinweis an die Bevölkerung:', 'gazette'),
                T::__('Zur Aufheiterung:', 'gazette'),
                T::__('Gut zu wissen:', 'gazette'),
                T::__('Mal was anderes:', 'gazette'),
                T::__('Das neueste Geschwätz:', 'gazette'),
            ];
            $in_between_mod = (ceil(count($in_between)/10) + 1) * 10;

            while (count($gazette_logs) > 0) {
                /** @var GazetteLogEntry $log */
                $log = array_shift($gazette_logs);
                if ($log->getTemplate() === null)
                    continue;
                $num++;
                $type = $log->getTemplate()->getType();
                if ($type !== GazetteEntryTemplate::TypeGazetteWind) {
                    if ($num === 2)
                        $inbetween = $in_between[$log->getId() % $in_between_mod] ?? '';
                    else $inbetween = '';
                    $text .= '<p>' . ($inbetween !== '' ? ($this->translator->trans($inbetween,[],'gazette') . ' ') : '') . $this->parseGazetteLog($log, $inbetween !== '') . '</p>';
                } else
                    $wind = $this->parseGazetteLog($log);
            }
        }


        $textClass = "day$day";

        $days = [
            'final' => $day % 5,
            'repeat' => floor($day / 5),
        ];

        if($origLang !== $lang) {
            $this->translator->setLocale($origLang);
        }

        return [
            'name' => $town->getName(),
            'open' => $town->isOpen(),
            'day' => $day,
            'days' => $days,
            'devast' => $town->getDevastated(),
            'chaos' => $town->getChaos(),
            'door' => $gazette->getDoor(),
            'reactorExplosion' => $gazette->getReactorExplosion(),
            'death_outside' => $death_outside,
            'death_inside' => $death_inside,
            'attack' => $gazette->getAttack(),
            'defense' => $gazette->getDefense(),
            'invasion' => $gazette->getInvasion(),
            'deaths' => count($death_inside),
            'terror' => $gazette->getTerror(),
            'text' => $text,
            'textClass' => $textClass,
            'wind' => $wind,
            'windDirection' => intval($gazette->getWindDirection()),
            'waterlost' => intval($gazette->getWaterlost()),
        ];
    }

    static array $node_block_cache = [];
    static array $node_block_ord = [];
    public function generateCouncilNodeList(Town $town, int $day, int $rootNodeSemantic, array $citizenData, array $flags = []) {
        $cache_key = $town->getId() . '.' . $day;

        if (!isset(static::$node_block_cache[$cache_key])) static::$node_block_cache[$cache_key] = [];
        if (!isset(static::$node_block_ord[$cache_key]))   static::$node_block_ord[$cache_key] = 0;

        foreach ( $this->entity_manager->getRepository(CouncilEntry::class)->findBy(['town' => $town, 'day' => $day]) as $previous_node) {
            if ($previous_node->getTemplate()->getText() !== null) static::$node_block_cache[$cache_key][] = $previous_node->getTemplate();
            static::$node_block_ord[$cache_key] = max(static::$node_block_ord[$cache_key], $previous_node->getOrd());
        }

        foreach ( array_keys($citizenData) as $key )
            if (!str_starts_with($key, '_') && !isset($citizenData["_$key"]))
                $citizenData["_$key"] = $citizenData[$key];

        $ord = static::$node_block_ord[$cache_key];

        $implement_template = function (?CouncilEntry $parent, array $parents, array $siblings, array $templates, array &$variable_stack) use ($cache_key, $town, $day, &$ord, &$flags): ?CouncilEntry {
            $variable_stack_copy = $variable_stack;
            $node = $this->rand->pick(
                array_filter(
                    array_diff( $templates, static::$node_block_cache[$cache_key])
                    , function(CouncilEntryTemplate $template) use (&$variable_stack_copy, $parents, $siblings, &$flags) {

                        $has_main = false; $main_source = null;
                        $cache = [];
                        foreach ($template->getVariableDefinitions() as $name => $def) {
                            foreach ($def['flags'] ?? [] as $flagname => $flagvalue)
                                if ( ($flags[$flagname] ?? false) !== (bool)$flagvalue ) {
                                    //echo "REJECTING {$template->getName()}: Failed flag '$flagname' test.\n";
                                    return false;
                                }

                            if (isset($def['from'])) {
                                $cache[$def['from']] = 1 + ($cache[$def['from']] ?? 0);
                                if (($def['consume'] ?? false) && str_starts_with($def['from'], '_')) {
                                    //echo "REJECTING {$template->getName()}: Consumes from protected group.\n";
                                    return false;
                                }

                                if ($name === 'main') {
                                    $has_main = true;
                                    $main_source = $def['from'];
                                }
                            }
                        }

                        if ($template->getVocal()) {
                            if (!$has_main || !isset( $variable_stack_copy[$main_source] )) {
                                //echo "REJECTING {$template->getName()}: Undefined MAIN for vocal (" . ($main_source !== null ? $main_source : 'no source') . ")\n";
                                return false;
                            }

                            if (str_ends_with($main_source, '?')) {
                                $blocked_names = [];
                                /** @var CouncilEntry $name_blocker */
                                foreach ( array_merge($parents,$siblings) as $name_blocker )
                                    if ($name_blocker->getCitizen()) $blocked_names[] = $name_blocker->getCitizen();
                                //echo 'Before: ' . count($variable_stack_copy[$main_source]) . ', after ' . count( array_diff($variable_stack_copy[$main_source], $blocked_names) ) . ', blocking ' . count($blocked_names) . "\n";
                                $variable_stack_copy[$main_source] = array_diff($variable_stack_copy[$main_source], $blocked_names);
                            }
                        }

                        foreach ($cache as $name => $count)
                            if (!isset( $variable_stack_copy[$name] ) || count($variable_stack_copy[$name]) < $count) {
                                //$cc = isset( $variable_stack_copy[$name] ) ? count($variable_stack_copy[$name]) : 0;
                                //echo "REJECTING {$template->getName()}: Insufficient group members ($name has {$cc}, needs {$count}).\n";
                                return false;
                            }

                        return true;
                    }
                )
            );

            /** @var CouncilEntryTemplate $node */
            if ($node) {
                if ($node->getText() !== null)
                    static::$node_block_cache[$cache_key][] = $node;

                $variables = []; $main_citizen = null;
                foreach ($node->getVariableDefinitions() as $name => $def) {
                    if (!isset($def['from'])) continue;

                    if ($name !== 'main') {
                        /** @var ?Citizen $selected_citizen */
                        $selected_citizen = ($def['consume'] ?? false) ? $this->rand->draw( $variable_stack[$def['from']] ) : $this->rand->pick( $variable_stack[$def['from']] );
                        if ($selected_citizen)
                            $variables[$name] = $selected_citizen->getId();
                    } else {
                        $blocked_names = [];
                        if (str_ends_with($def['from'], '?'))
                            /** @var CouncilEntry $name_blocker */
                            foreach ( array_merge($parents,$siblings) as $name_blocker )
                                if ($name_blocker->getCitizen()) $blocked_names[] = $name_blocker->getCitizen();
                        $main_citizen = $this->rand->pick( array_diff( $variable_stack[$def['from']], $blocked_names ) );
                        if ($main_citizen)
                            $variables['main'] = $main_citizen->getId();
                        if ($def['consume'] ?? false) unset( $variable_stack[$def['from']][ array_search( $main_citizen, $variable_stack[$def['from']], true ) ] );
                    }
                }

                foreach ($node->getVariableTypes() as $typedef) {

                    if ($typedef['type'] === 'num' && isset($variable_stack[$typedef['name']]))
                        $variables[$typedef['name']] = count($variable_stack[$typedef['name']]);

                }

                return (new CouncilEntry())
                    ->setTemplate($node)
                    ->setTown($town)
                    ->setDay($day)
                    ->setVariables( $variables )
                    ->setCitizen( $main_citizen ?? null )
                    ->setOrd(++$ord);

            } else return null;
        };

        $add_to_list = null;
        $add_to_list = function ( array $templates, ?CouncilEntry $parent = null, array $parents = [], array &$siblings = [], $once = false ) use (&$add_to_list, &$implement_template, &$citizenData) {

            if ($parent) $parents[] = $parent;
            $citizenData['_parent'] = ($parent && $parent->getCitizen()) ? [$parent->getCitizen()] : [];
            $citizenData['_siblings'] = array_filter( array_map( fn(CouncilEntry $t) => $t->getCitizen(), $siblings ) );

            /** @var CouncilEntryTemplate $template */
            foreach ($templates as $template) {

                if ($new_node = $implement_template($parent, $parents, $siblings, [$template], $citizenData)) {
                    switch ($new_node->getTemplate()->getBranchMode()) {
                        case CouncilEntryTemplate::CouncilBranchModeStructured:
                            $s = [];
                            $add_to_list($new_node->getTemplate()->getBranches()->getValues(), $new_node, $parents, $s);
                            break;
                        case CouncilEntryTemplate::CouncilBranchModeRandom:
                            $num = mt_rand($new_node->getTemplate()->getBranchSizeMin(), $new_node->getTemplate()->getBranchSizeMax());
                            $branches = $new_node->getTemplate()->getBranches()->getValues();
                            shuffle($branches);

                            $original_siblings = $siblings;
                            while ($num > 0) {
                                $add_to_list($branches, $new_node, $parents, $siblings, true);
                                $num--;
                            }
                            $siblings = $original_siblings;

                            break;
                        default:
                            break;
                    }
                    $siblings[] = $new_node;
                    $citizenData['_siblings'] = array_filter(array_map(fn(CouncilEntry $c) => $c->getCitizen(), $siblings));

                    $this->entity_manager->persist($new_node);

                    if ($once) break;
                }
            }
        };

        $s = [];
        $add_to_list(
            $this->rand->pick($this->entity_manager->getRepository(CouncilEntryTemplate::class)->findBy(['semantic' => $rootNodeSemantic]), 1, true),
            null, [], $s, true
        );

        static::$node_block_ord[$cache_key] = $ord;
    }
}