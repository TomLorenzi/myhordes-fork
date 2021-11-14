<?php


namespace App\Service;

use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
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

    protected function parseLog( $template, array $variables ): String {
        $variableTypes = $template->getVariableTypes();
        $transParams = $this->log->parseTransParams($variableTypes, $variables);

        try {
            $text = $this->translator->trans($template->getText(), $transParams, 'game');
        }
        catch (Exception $e) {
            $text = "null";
        }

        return $text;
    }

    public function parseGazetteLog(GazetteLogEntry $gazetteLogEntry): string
    {
        $flavour = [
            GazetteEntryTemplate::FollowUpTypeDoubt => [
                null,
                $this->translator->trans('Nun, das sage ich...', [],'game'),
                $this->translator->trans('Das ist doch Unsinn...', [], 'game'),
                $this->translator->trans('Die einen sagen dies, die anderen das...', [], 'game'),
                $this->translator->trans('Ich sags ja nur...', [], 'game'),
                $this->translator->trans('Komm schon, glaub mir.', [], 'game'),
            ],
            GazetteEntryTemplate::FollowUpTypeBad => [
                null,
                $this->translator->trans('Was für eine Organisation...', [], 'game'),
                $this->translator->trans('Wir werden nicht lange durchhalten, das sage ich euch.', [], 'game'),
                $this->translator->trans('Noch so eine Nacht und wir werden nicht mehr hier sein, um über so etwas zu reden.', [], 'game'),
                $this->translator->trans('Das war knapp.', [], 'game'),
                $this->translator->trans('Pfff...', [], 'game'),
            ],
        ];
        $txt = $this->parseLog($gazetteLogEntry->getTemplate(), $gazetteLogEntry->getVariables());

        if ($txt && $gazetteLogEntry->getTemplate() && $gazetteLogEntry->getFollowUp() > 0 &&
            isset($flavour[ $gazetteLogEntry->getTemplate()->getFollowUpType() ]) && isset($flavour[ $gazetteLogEntry->getTemplate()->getFollowUpType() ][ $gazetteLogEntry->getFollowUp() ]))

            $txt .= " {$flavour[ $gazetteLogEntry->getTemplate()->getFollowUpType() ][ $gazetteLogEntry->getFollowUp() ]}";
        return $txt;
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
        $class = intval(floor($g->getRequirement() / 10));
        switch ($class) {
            case 1:case 2:case 3:case 5:
                return [ $class * 10, $g->getRequirement() % 10 ];
            case 4:
                return [ $g->getRequirement(), 0 ];
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
                case GazetteEntryTemplate::RequiresMultipleDehydrations:  return count( array_filter( $death_outside, fn(Citizen $c) => $c->getCauseOfDeath()->getRef() === CauseOfDeath::Dehydration ) ) >= 2;
                case GazetteEntryTemplate::RequiresMultipleSuicides:      return count( array_filter( $death_outside, fn(Citizen $c) => $c->getCauseOfDeath()->getRef() === CauseOfDeath::Cyanide ) ) >= 2;
                case GazetteEntryTemplate::RequiresInvasion:              return $gazette->getInvasion() > 0;
                case GazetteEntryTemplate::RequiresAttackDeaths:          return $gazette->getDeaths() > 0;
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

        switch ($class) {
            case GazetteEntryTemplate::BaseRequirementCitizen:
                $citizens = array_filter( $survivors, fn(Citizen $c) => $c !== $featured );
                shuffle($citizens);
                if ($featured !== null && $featured->getAlive()) array_unshift( $citizens, $featured );

                for ($i = 1; $i <= $arg; $i++)
                    $variables['citizen' . $i] = (array_shift($citizens))->getId();
                $variables['citizens'] = count($citizens);

                break;

            case GazetteEntryTemplate::BaseRequirementCadaver:
                $cadavers = array_filter( $death_inside, fn(Citizen $c) => $c !== $featured );
                shuffle($cadavers);
                if ($featured !== null && !$featured->getAlive()) array_unshift( $cadavers, $featured );

                for ($i = 1; $i <= $arg; $i++)
                    $variables['cadaver' . $i] = (array_shift($cadavers))->getId();
                $variables['cadavers'] = count($cadavers);

                break;

            case GazetteEntryTemplate::BaseRequirementCitizenCadaver:
                $citizens = array_filter( $survivors, fn(Citizen $c) => $c !== $featured );
                shuffle($citizens);
                if ($featured !== null && $featured->getAlive()) array_unshift( $citizens, $featured );

                $cadavers = array_filter( $death_inside, fn(Citizen $c) => $c !== $featured );
                shuffle($cadavers);
                if ($featured !== null && !$featured->getAlive()) array_unshift( $cadavers, $featured );

                for ($i = 1; $i <= $arg; $i++)
                    $variables['citizen' . $i] = (array_shift($citizens))->getId();
                $variables['citizens'] = count($citizens);
                for ($i = 1; $i <= $arg; $i++)
                    $variables['cadaver' . $i] = (array_shift($cadavers))->getId();
                $variables['cadavers'] = count($cadavers);
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
                $variables['deaths'] = $gazette->getDeaths();

                break;

            case GazetteEntryTemplate::RequiresDefense:
                $defense = $gazette->getDefense();
                $variables['defense'] = $defense < 2000 ? 10 * (round($defense / 10)) : 100 * (round($defense / 100));

                break;

            case GazetteEntryTemplate::RequiresDeaths:
                $variables['deaths'] = $gazette->getDeaths();

                break;

            case GazetteEntryTemplate::RequiresMultipleDehydrations:
                $cadavers = array_filter( $death_outside, fn(Citizen $c) => $c->getCauseOfDeath()->getRef() === CauseOfDeath::Dehydration );
                $variables['cadavers'] = count($cadavers);

                break;

            case GazetteEntryTemplate::RequiresMultipleSuicides:
                $cadavers = array_filter( $death_outside, fn(Citizen $c) => $c->getCauseOfDeath()->getRef() === CauseOfDeath::Cyanide );
                $variables['cadavers'] = count($cadavers);

                break;

            case GazetteEntryTemplate::RequiresMultipleInfections:
                $cadavers = array_filter( $death_outside, fn(Citizen $c) => $c->getCauseOfDeath()->getRef() === CauseOfDeath::Infection );
                $variables['cadavers'] = count($cadavers);

                break;

            case GazetteEntryTemplate::RequiresMultipleVanished:
                $cadavers = array_filter( $death_outside, fn(Citizen $c) => $c->getCauseOfDeath()->getRef() === CauseOfDeath::Vanished );
                $variables['cadavers'] = count($cadavers);

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

            $d_list = array_filter( $d_list, fn($v,$k) => $v >= 2 && in_array($k,[CauseOfDeath::Cyanide,CauseOfDeath::Dehydration,CauseOfDeath::Infection,CauseOfDeath::Vanished]), ARRAY_FILTER_USE_BOTH );
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
                    CauseOfDeath::Vanished
                ]) ) );

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
        if ( $gazette->getReactorExplosion() || $town->getDevastated() ) return false;

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
            $text = "<p>" . $this->translator->trans('Heute Morgen ist kein Artikel erschienen...', [], 'game') . "</p>";
            if ($town->isOpen()){
                $text .= "<p>" . $this->translator->trans('Die Stadt wird erst starten, wenn sie <strong>{population} Bürger hat</strong>.', ['{population}' => $town->getPopulation()], 'game') . "</p>" . "<a class='help-button'>" . "<div class='tooltip help'>" . $this->translator->trans("Falls sich dieser Zustand auch um Mitternacht noch nicht geändert hat, findet kein Zombieangriff statt. Der Tag wird dann künstlich verlängert.", [], 'global') . "</div>" . $this->translator->trans("Hilfe", [], 'global') . "</a>";
            } else {
                $text .= $this->translator->trans('Fangt schon mal an zu beten, Bürger - die Zombies werden um Mitternacht angreifen!', [], 'game');
            }
        } else {
            $gazette_logs = $this->entity_manager->getRepository(GazetteLogEntry::class)->findBy(['gazette' => $gazette]);
            while (count($gazette_logs) > 0) {
                /** @var GazetteLogEntry $log */
                $log = array_shift($gazette_logs);
                if ($log->getTemplate() === null)
                    continue;
                $type = $log->getTemplate()->getType();
                if($type !== GazetteEntryTemplate::TypeGazetteWind)
                    $text .= '<p>' . $this->parseGazetteLog($log) . '</p>';
                else
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
}