<?php


namespace App\Service;

use App\Entity\AccountRestriction;
use App\Entity\Award;
use App\Entity\AwardPrototype;
use App\Entity\Building;
use App\Entity\BuildingPrototype;
use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\CitizenHome;
use App\Entity\CitizenHomePrototype;
use App\Entity\CitizenProfession;
use App\Entity\Complaint;
use App\Entity\Item;
use App\Entity\ItemGroup;
use App\Entity\ItemGroupEntry;
use App\Entity\ItemPrototype;
use App\Entity\LogEntryTemplate;
use App\Entity\Town;
use App\Entity\TownLogEntry;
use App\Entity\User;
use App\Entity\Zone;
use App\Translation\T;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LogTemplateHandler
{
    private TranslatorInterface $trans;
    private Packages $asset;
    private EntityManagerInterface $entity_manager;
    private UrlGeneratorInterface $url;

    public function __construct(TranslatorInterface $t, Packages $a, EntityManagerInterface $em, UrlGeneratorInterface $url)
    {
        $this->trans = $t;
        $this->asset = $a;
        $this->entity_manager = $em;
        $this->url = $url;
    }

    public function wrap(?string $obj, ?string $class = null): string {
        //if (!($obj || $obj != 0)) {var_dump($obj); die;}
        return ($obj === "0" || $obj) ? ("<span" . ($class ? " class='$class'" : '') . ">$obj</span>") : '';
    }

    /**
     * @param Item|ItemPrototype|ItemGroupEntry|Citizen|CitizenProfession|Building|BuildingPrototype|CauseOfDeath|CitizenHome|CitizenHomePrototype|array $obj
     * @param bool $small
     * @param bool $broken
     * @param Citizen|null $ref
     * @return string
     */
    public function iconize($obj, bool $small = false, bool $broken = false, ?Citizen $ref = null): string {
        if (is_array($obj) && count($obj) === 2) return $this->iconize( $obj['item'], $small) . ' x ' . $obj['count'];

        if ($obj instanceof Item) {
            $str = $this->iconize( $obj->getPrototype(), $small, $obj->getBroken() );
            return $str;
        }
        if ($obj instanceof Building)    return $this->iconize( $obj->getPrototype(), $small );
        if ($obj instanceof CitizenHome) return $this->iconize( $obj->getPrototype(), $small );

        if ($small) {
            if ($obj instanceof CitizenProfession) return "<img alt='' src='{$this->asset->getUrl( "build/images/professions/{$obj->getIcon()}.gif" )}' />";
        }

        if ($obj instanceof ItemPrototype) {
            $text = "<img alt='' src='{$this->asset->getUrl( "build/images/item/item_{$obj->getIcon()}.gif" )}' /> {$this->trans->trans($obj->getLabel(), [], 'items')}";
            if($broken)
                $text .= " (" . $this->trans->trans("Kaputt", [], 'items') . ")";
            return $text;
        }
        if ($obj instanceof ItemGroupEntry)       return "<img alt='' src='{$this->asset->getUrl( "build/images/item/item_{$obj->getPrototype()->getIcon()}.gif" )}' /> {$this->trans->trans($obj->getPrototype()->getLabel(), [], 'items')} <i>x {$obj->getChance()}</i>";
        if ($obj instanceof BuildingPrototype)    return "<img alt='' src='{$this->asset->getUrl( "build/images/building/{$obj->getIcon()}.gif" )}' /> {$this->trans->trans($obj->getLabel(), [], 'buildings')}";
        if ($obj instanceof Citizen)              return $obj->getName();
        if ($obj instanceof CitizenProfession)    return "<img alt='' src='{$this->asset->getUrl( "build/images/professions/{$obj->getIcon()}.gif" )}' /> {$this->trans->trans($obj->getLabel(), ['ref' => $ref], 'game')}";
        if ($obj instanceof CitizenHomePrototype) return "<img alt='' src='{$this->asset->getUrl( "build/images/home/{$obj->getIcon()}.gif" )}' /> {$this->trans->trans($obj->getLabel(), [], 'buildings')}";
        if ($obj instanceof CauseOfDeath)         return $this->trans->trans($obj->getLabel(), [], 'game');
        return "";
    }

    public function fetchVariableObject (string $type, ?int $key) {
        if ($key === null) return null;
        $object = null;
        switch ($type) {
            case 'citizen':
                $object = $this->entity_manager->getRepository(Citizen::class)->find($key);
                break;
            case 'item':
                $object = $this->entity_manager->getRepository(ItemPrototype::class)->find($key);
                break;
            case 'itemGroup':
                $object = $this->entity_manager->getRepository(ItemGroup::class)->find($key);
                break;
            case 'home':
                $object = $this->entity_manager->getRepository(CitizenHomePrototype::class)->find($key);
                break;
            case 'plan':
                $object = $this->entity_manager->getRepository(BuildingPrototype::class)->find($key);
                break;
            case 'profession':
            case 'professionFull':
                $object = $this->entity_manager->getRepository(CitizenProfession::class)->find($key);
                break;
            case 'cod':
                $object = $this->entity_manager->getRepository(CauseOfDeath::class)->find($key);
                break;
        }
        return $object;
    }

    public static function generateDogName(int $numeric, TranslatorInterface $trans): string {
        $dog_names_prefix = [
            T::__('TDG_PRE_00_','names'), T::__('TDG_PRE_10_','names'),T::__('TDG_PRE_20_','names'), T::__('TDG_PRE_30_','names'),T::__('TDG_PRE_40_','names'), T::__('TDG_PRE_50_','names'),
            T::__('TDG_PRE_01_','names'), T::__('TDG_PRE_11_','names'),T::__('TDG_PRE_21_','names'), T::__('TDG_PRE_31_','names'),T::__('TDG_PRE_41_','names'), T::__('TDG_PRE_51_','names'),
            T::__('TDG_PRE_02_','names'), T::__('TDG_PRE_12_','names'),T::__('TDG_PRE_22_','names'), T::__('TDG_PRE_32_','names'),T::__('TDG_PRE_42_','names'), T::__('TDG_PRE_52_','names'),
            T::__('TDG_PRE_03_','names'), T::__('TDG_PRE_13_','names'),T::__('TDG_PRE_23_','names'), T::__('TDG_PRE_33_','names'),T::__('TDG_PRE_43_','names'), T::__('TDG_PRE_53_','names'),
            T::__('TDG_PRE_04_','names'), T::__('TDG_PRE_14_','names'),T::__('TDG_PRE_24_','names'), T::__('TDG_PRE_34_','names'),T::__('TDG_PRE_44_','names'), T::__('TDG_PRE_54_','names'),
            T::__('TDG_PRE_05_','names'), T::__('TDG_PRE_15_','names'),T::__('TDG_PRE_25_','names'), T::__('TDG_PRE_35_','names'),T::__('TDG_PRE_45_','names'), T::__('TDG_PRE_55_','names'),
            T::__('TDG_PRE_06_','names'), T::__('TDG_PRE_16_','names'),T::__('TDG_PRE_26_','names'), T::__('TDG_PRE_36_','names'),T::__('TDG_PRE_46_','names'), T::__('TDG_PRE_56_','names'),
            T::__('TDG_PRE_07_','names'), T::__('TDG_PRE_17_','names'),T::__('TDG_PRE_27_','names'), T::__('TDG_PRE_37_','names'),T::__('TDG_PRE_47_','names'), T::__('TDG_PRE_57_','names'),
            T::__('TDG_PRE_08_','names'), T::__('TDG_PRE_18_','names'),T::__('TDG_PRE_28_','names'), T::__('TDG_PRE_38_','names'),T::__('TDG_PRE_48_','names'), T::__('TDG_PRE_58_','names'),
            T::__('TDG_PRE_09_','names'), T::__('TDG_PRE_19_','names'),T::__('TDG_PRE_29_','names'), T::__('TDG_PRE_39_','names'),T::__('TDG_PRE_49_','names'), T::__('TDG_PRE_59_','names'),
        ];

        $dog_names_suffix = [
            T::__('TDG_SUF_00','names'), T::__('TDG_SUF_10','names'),T::__('TDG_SUF_20','names'), T::__('TDG_SUF_30','names'),T::__('TDG_SUF_40','names'), T::__('TDG_SUF_50','names'),
            T::__('TDG_SUF_01','names'), T::__('TDG_SUF_11','names'),T::__('TDG_SUF_21','names'), T::__('TDG_SUF_31','names'),T::__('TDG_SUF_41','names'), T::__('TDG_SUF_51','names'),
            T::__('TDG_SUF_02','names'), T::__('TDG_SUF_12','names'),T::__('TDG_SUF_22','names'), T::__('TDG_SUF_32','names'),T::__('TDG_SUF_42','names'), T::__('TDG_SUF_52','names'),
            T::__('TDG_SUF_03','names'), T::__('TDG_SUF_13','names'),T::__('TDG_SUF_23','names'), T::__('TDG_SUF_33','names'),T::__('TDG_SUF_43','names'), T::__('TDG_SUF_53','names'),
            T::__('TDG_SUF_04','names'), T::__('TDG_SUF_14','names'),T::__('TDG_SUF_24','names'), T::__('TDG_SUF_34','names'),T::__('TDG_SUF_44','names'), T::__('TDG_SUF_54','names'),
            T::__('TDG_SUF_05','names'), T::__('TDG_SUF_15','names'),T::__('TDG_SUF_25','names'), T::__('TDG_SUF_35','names'),T::__('TDG_SUF_45','names'), T::__('TDG_SUF_55','names'),
            T::__('TDG_SUF_06','names'), T::__('TDG_SUF_16','names'),T::__('TDG_SUF_26','names'), T::__('TDG_SUF_36','names'),T::__('TDG_SUF_46','names'), T::__('TDG_SUF_56','names'),
            T::__('TDG_SUF_07','names'), T::__('TDG_SUF_17','names'),T::__('TDG_SUF_27','names'), T::__('TDG_SUF_37','names'),T::__('TDG_SUF_47','names'), T::__('TDG_SUF_57','names'),
            T::__('TDG_SUF_08','names'), T::__('TDG_SUF_18','names'),T::__('TDG_SUF_28','names'), T::__('TDG_SUF_38','names'),T::__('TDG_SUF_48','names'), T::__('TDG_SUF_58','names'),
            T::__('TDG_SUF_09','names'), T::__('TDG_SUF_19','names'),T::__('TDG_SUF_29','names'), T::__('TDG_SUF_39','names'),T::__('TDG_SUF_49','names'), T::__('TDG_SUF_59','names'),
        ];

        list(,$preID,$sufID) = unpack('l2', md5("dog-for-{$numeric}", true));
        return "{$trans->trans($dog_names_prefix[abs($preID % count($dog_names_prefix))],[],'names')}{$trans->trans($dog_names_suffix[abs($sufID % count($dog_names_suffix))],[],'names')}";
    }
    
    public function parseTransParams(array $variableTypes, array $variables): ?array {
        $transParams = [];

        $reference_citizen = null;
        foreach ($variableTypes as $typeEntry)
            if (isset($typeEntry['type']) && $typeEntry['type'] === 'citizen' && $reference_citizen === null)
                $reference_citizen = $this->fetchVariableObject( $typeEntry['type'], $variables[$typeEntry['name']] );

        foreach ($variableTypes as $typeEntry) {
            try {
                $wrap_fun = ( $typeEntry['raw'] ?? false ) ? fn($a,$b=null) => $a : fn($a,$b=null) => $this->wrap($a,$b);

                if (!isset($typeEntry['type']) || !isset($typeEntry['name'])) continue;

                // ICU-aware
                if ($typeEntry['type'] === 'citizen') {
                    if ( $variables[$typeEntry['name']] === -141089 )
                        $transParams[$typeEntry['name']] = $this->trans->trans( 'Mysteriöser Fremder', [], 'game' );
                    else $transParams[$typeEntry['name']] = $this->fetchVariableObject( $typeEntry['type'], $variables[$typeEntry['name']] );
                    $transParams["{$typeEntry['name']}__tag"] = 'span';
                } elseif ($typeEntry['type'] === 'profession' || $typeEntry['type'] === 'professionFull') {
                    if ( $variables[$typeEntry['name']] === -141089 )
                        $transParams[$typeEntry['name']] = "<img alt='' src='{$this->asset->getUrl( "build/images/professions/stranger.gif" )}' />" . ( $typeEntry['type'] === 'profession' ? '' : ' ???' );
                    else $transParams[$typeEntry['name']] = $this->iconize( $this->fetchVariableObject( $typeEntry['type'], $variables[$typeEntry['name']] ), $typeEntry['type'] === 'profession', $variables['broken'] ?? false, $reference_citizen );
                    $transParams["{$typeEntry['name']}__tag"] = 'span';
                    if ($typeEntry['type'] === 'professionFull') $transParams["{$typeEntry['name']}__class"] = 'jobName';
                }
                // Non ICU-aware
                elseif ($typeEntry['type'] === 'user') {
                    $user = $this->entity_manager->getRepository(User::class)->find( $variables[$typeEntry['name']] );
                    $userName = match ($user->getId()) {
                        66 => $this->trans->trans('Der Rabe', [], 'global'),
                        67 => $this->trans->trans('Animateur-Team', [], 'global'),
                        default => $user->getName()
                    };
                    $transParams['{'.$typeEntry['name'].'}'] =
                        $user ? "<span class=\"username\" x-user-id=\"{$user->getId()}\">{$userName}</span>" : '<span class="username">???</span>';
                }
                elseif ($typeEntry['type'] === 'itemGroup') {
                    $itemGroupEntries  = $this->fetchVariableObject($typeEntry['type'], $variables[$typeEntry['name']])->getEntries()->getValues();
                    $transParams['{'.$typeEntry['name'].'}'] = implode( ', ', array_map( function(ItemGroupEntry $e) use ($wrap_fun) { return $wrap_fun( $this->iconize( $e ), 'tool' ); }, $itemGroupEntries ));
                }
                elseif ($typeEntry['type'] === 'list') {
                    $listType = $typeEntry['listType'];
                    $listArray = array_map( function($e) use ($listType) { if(array_key_exists('count', $e)) {return array('item' => $this->fetchVariableObject($listType, $e['id']),'count' => $e['count']);}
                        else { return $this->fetchVariableObject($listType, $e['id']); } }, $variables[$typeEntry['name']] );
                    if (isset($listArray)) {
                        $transParams['{'.$typeEntry['name'].'}'] = implode( ', ', array_map( function($e) use ($wrap_fun) { return $wrap_fun( $this->iconize( $e ), 'tool' ); }, $listArray ) );
                    }
                    else
                        $transParams['{'.$typeEntry['name'].'}'] = "null";
                }
                elseif ($typeEntry['type'] === 'num' || $typeEntry['type'] === 'string') {
                    $transParams['{'.$typeEntry['name'].'}'] = $wrap_fun($variables[$typeEntry['name']] ?? 0);
                    if($typeEntry['type'] === 'num')
                        $transParams['{raw_'.$typeEntry['name'].'}'] = $variables[$typeEntry['name']];
                }
                elseif ($typeEntry['type'] === 'transString') {
                    $transParams['{'.$typeEntry['name'].'}'] = $wrap_fun( $this->trans->trans($variables[$typeEntry['name']], [], $typeEntry['from'] ?? 'game') );
                }
                elseif ($typeEntry['type'] === 'dogname') {
                    $transParams['{'.$typeEntry['name'].'}'] = $wrap_fun( self::generateDogName((int)$variables[$typeEntry['name']], $this->trans) );
                }
                elseif ($typeEntry['type'] === 'ap') {
                    $transParams['{'.$typeEntry['name'].'}'] = "<div class='ap'>{$variables[$typeEntry['name']]}</div>";
                }   
                elseif ($typeEntry['type'] === 'chat') {
                    $transParams['{'.$typeEntry['name'].'}'] = $variables[$typeEntry['name']];
                }
                elseif ($typeEntry['type'] === 'item') {
                    $transParams['{'.$typeEntry['name'].'}'] = $wrap_fun( $this->iconize( $this->fetchVariableObject( $typeEntry['type'], $variables[$typeEntry['name']] ), false, $variables['broken'] ?? false ), 'tool' );
                }
                elseif ($typeEntry['type'] === 'link_post') {
                    $transParams['{'.$typeEntry['name'].'}'] = "<a target='_blank' href='{$this->url->generate('forum_jump_view', ['pid' => $variables[$typeEntry['name']] ?? 0])}'>{$this->trans->trans('Anzeigen', [], 'global')}</a>";
                }
                elseif ($typeEntry['type'] === 'ne-string') {
                    $transParams['{'.$typeEntry['name'].'}'] = $wrap_fun(empty($variables[$typeEntry['name']]) ? '-' : $variables[$typeEntry['name']]);
                }
                elseif ($typeEntry['type'] === 'title-list') {
                    $transParams['{'.$typeEntry['name'].'}'] = "<div class='list'>";
                    $transParams['{'.$typeEntry['name'].'}'] .= implode('', array_map( fn($e) => $this->wrap($this->trans->trans($e, [], 'game')), $variables[$typeEntry['name']] ));
                    $transParams['{'.$typeEntry['name'].'}'] .= "</div>";
                }
                elseif ($typeEntry['type'] === 'title-icon-list') {
                    $transParams['{'.$typeEntry['name'].'}'] = "<div class='list'>";
                    $transParams['{'.$typeEntry['name'].'}'] .= implode('', array_map( fn($e) => "<img alt='$e' src='{$this->asset->getUrl( "build/images/icons/title/$e.gif" )}' />", $variables[$typeEntry['name']] ));
                    $transParams['{'.$typeEntry['name'].'}'] .= "</div>";
                }
                elseif ($typeEntry['type'] === 'accountRestrictionMask') {

                    $transParams['{'.$typeEntry['name'].'}'] = "<div class='list'>";
                    $mask = (int)$variables[$typeEntry['name']];
                    $binmatch = fn( $val, $res ) => ($val & $res) === $res;
                    if ( $binmatch($mask, AccountRestriction::RestrictionForum) ) $transParams['{'.$typeEntry['name'].'}'] .= $this->wrap($this->trans->trans( 'In Foren posten', [], 'soul' ));
                    if ( $binmatch($mask, AccountRestriction::RestrictionTownCommunication) ) $transParams['{'.$typeEntry['name'].'}'] .= $this->wrap($this->trans->trans( 'Kommunikation in der Stadt', [], 'soul' ));
                    elseif ( $binmatch($mask, AccountRestriction::RestrictionBlackboard) ) $transParams['{'.$typeEntry['name'].'}'] .= $this->wrap($this->trans->trans( 'Schwarzes Brett', [], 'soul' ));
                    if ( $binmatch($mask, AccountRestriction::RestrictionGlobalCommunication) ) $transParams['{'.$typeEntry['name'].'}'] .= $this->wrap($this->trans->trans( 'Stadtübergreifende Kommunikation', [], 'soul' ));
                    if ( $binmatch($mask, AccountRestriction::RestrictionComments) ) $transParams['{'.$typeEntry['name'].'}'] .= $this->wrap($this->trans->trans( 'Kommentare', [], 'soul' ));
                    if ( $binmatch($mask, AccountRestriction::RestrictionOrganization) ) $transParams['{'.$typeEntry['name'].'}'] .= $this->wrap($this->trans->trans( 'Gruppenorganisation', [], 'soul' ));
                    if ( $binmatch($mask, AccountRestriction::RestrictionGameplay) ) $transParams['{'.$typeEntry['name'].'}'] .= $this->wrap($this->trans->trans( 'Spielen', [], 'soul' ));
                    if ( $binmatch($mask, AccountRestriction::RestrictionProfile) ) $transParams['{'.$typeEntry['name'].'}'] .= $this->wrap($this->trans->trans( 'Ändern des Profils', [], 'soul' ));
                    else {
                        if ( $binmatch($mask, AccountRestriction::RestrictionProfileAvatar) ) $transParams['{'.$typeEntry['name'].'}'] .= $this->wrap($this->trans->trans( 'Ändern des Avatars', [], 'soul' ));
                        if ( $binmatch($mask, AccountRestriction::RestrictionProfileTitle) ) $transParams['{'.$typeEntry['name'].'}'] .= $this->wrap($this->trans->trans( 'Ändern des Profiltitels', [], 'soul' ));
                        if ( $binmatch($mask, AccountRestriction::RestrictionProfileDescription) ) $transParams['{'.$typeEntry['name'].'}'] .= $this->wrap($this->trans->trans( 'Ändern der Profilbeschreibung', [], 'soul' ) );
                        if ( $binmatch($mask, AccountRestriction::RestrictionProfileDisplayName) ) $transParams['{'.$typeEntry['name'].'}'] .= $this->wrap($this->trans->trans( 'Ändern des Spielernamens', [], 'soul' ));
                    }
                    $transParams['{'.$typeEntry['name'].'}'] .= "</div>";
                }
                elseif ($typeEntry['type'] === 'title-custom-list') {
                    $transParams['{'.$typeEntry['name'].'}'] = "<div class='list'>";
                    $transParams['{'.$typeEntry['name'].'}'] .= implode('', array_map( function($e) {
                        $a = $e ? $this->entity_manager->getRepository(Award::class)->find($e) : null;
                        if (!$a) return '???';
                        elseif ($a->getCustomTitle()) return $this->wrap( $a->getCustomTitle() );
                        elseif ($a->getCustomIcon()) return "<img alt='$e' src='{$this->url->generate('app_web_customicon', ['uid' => $a->getUser()->getId(), 'aid' => $a->getId(), 'name' => $a->getCustomIconName(), 'ext' => $a->getCustomIconFormat()])}' />";
                        else return '????';
                    }, $variables[$typeEntry['name']] ?? [] ));
                    $transParams['{'.$typeEntry['name'].'}'] .= "</div>";
                } elseif ($typeEntry['type'] === 'award-list') {
                    $prototypes = array_filter( array_map( fn(int $i) => $i > 0 ? $this->entity_manager->getRepository(AwardPrototype::class)->find($i) : null, $variables[$typeEntry['name']] ), fn($a) => $a !== null );
                    $customs = array_filter( array_map( fn(int $i) => $i < 0 ? $this->entity_manager->getRepository(Award::class)->find(-$i) : null, $variables[$typeEntry['name']] ), fn(?Award $a) => $a !== null && $a->getPrototype() === null );

                    $official_titles = array_filter( $prototypes, fn(AwardPrototype $p) => $p->getTitle() !== null );
                    $official_icons  = array_filter( $prototypes, fn(AwardPrototype $p) => $p->getIcon() !== null );
                    $unique_titles   = array_filter( $customs, fn(Award $a) => $a->getCustomTitle() !== null );
                    $unique_icons    = array_filter( $customs, fn(Award $a) => $a->getCustomIcon() !== null );

                    $transParams['{'.$typeEntry['name'].'}'] = '';
                    if (!empty($official_titles)) $transParams['{'.$typeEntry['name'].'}'] .= '<p><h5>' . $this->trans->trans('Titel', [], 'global') . '</h5><div class="list">' .
                        implode('', array_map( fn(AwardPrototype $e) =>
                            '<span>' .
                                $this->wrap($this->trans->trans($e->getTitle(), [], 'game')) .
                                ($e->getAssociatedPicto() ? '<div class="tooltip">' . $this->trans->trans($e->getAssociatedPicto()->getLabel(), [], 'game') . ' x ' . $e->getUnlockQuantity() . '</div>' : '') .
                            '</span>'
                        , $official_titles )) .
                    '</div></p>';

                    if (!empty($official_icons)) $transParams['{'.$typeEntry['name'].'}'] .= '<p><h5>' . $this->trans->trans('Icons', [], 'global') . '</h5><div class="list">' .
                        implode('', array_map( fn(AwardPrototype $e) =>
                            "<span><img alt='' src='{$this->asset->getUrl( "build/images/icons/title/{$e->getIcon()}.gif" )}' />" .
                                ($e->getAssociatedPicto() ? '<div class="tooltip">' . $this->trans->trans($e->getAssociatedPicto()->getLabel(), [], 'game') . ' x ' . $e->getUnlockQuantity() . '</div>' : '') .
                            '</span>'
                            , $official_icons )) .
                        '</div></p>';

                    if (!empty($unique_titles)) $transParams['{'.$typeEntry['name'].'}'] .= '<p><h5>' . $this->trans->trans('Einzigartige Titel', [], 'global') . '</h5><div class="list">' .
                        implode('', array_map( fn(Award $e) => $this->wrap( $e->getCustomTitle() ), $unique_titles )) .
                        '</div></p>';

                    if (!empty($unique_icons)) $transParams['{'.$typeEntry['name'].'}'] .= '<p><h5>' . $this->trans->trans('Einzigartige Icons', [], 'global') . '</h5><div class="list">' .
                        implode('', array_map( fn(Award $e) => "<img alt='' src='{$this->url->generate('app_web_customicon', ['uid' => $e->getUser()->getId(), 'aid' => $e->getId(), 'name' => $e->getCustomIconName(), 'ext' => $e->getCustomIconFormat()])}' />", $unique_icons )) .
                        '</div></p>';
                }

                elseif ($typeEntry['type'] === 'duration') {
                    $i = (int)$variables[$typeEntry['name']];
                    if ($i <= 0) $transParams['{'.$typeEntry['name'].'}'] = $wrap_fun($this->trans->trans('Dauerhaft', [], 'global'));
                    else {
                        $d = floor($i / 86400); $i -= ($d * 86400);
                        $h = floor($i /  3600); $i -= ($h *  3600);
                        $m = floor($i /    60); $i -= ($m *    60);

                        $stack = [];
                        if ($d > 0) $stack[] = $d > 1 ? $this->trans->trans('{n} Tage', ['{n}' => $d], 'global') : $this->trans->trans('1 Tag', [], 'global');
                        if ($h > 0) $stack[] = $h > 1 ? $this->trans->trans('{n} Stunden', ['{n}' => $h], 'global') : $this->trans->trans('1 Stunde', [], 'global');
                        if ($m > 0) $stack[] = $m > 1 ? $this->trans->trans('{n} Minuten', ['{n}' => $m], 'global') : $this->trans->trans('1 Minute', [], 'global');
                        if ($i > 0) $stack[] = $i > 1 ? $this->trans->trans('{n} Sekunden', ['{n}' => $i], 'global') : $this->trans->trans('1 Sekunde', [], 'global');
                        $transParams['{'.$typeEntry['name'].'}'] = $wrap_fun( implode(', ', $stack) );
                    }
                }
                else {
                    $transParams['{'.$typeEntry['name'].'}'] = $wrap_fun( $this->iconize( $this->fetchVariableObject( $typeEntry['type'], $variables[$typeEntry['name']] ), $typeEntry['type'] === 'profession', $variables['broken'] ?? false ), $typeEntry['type'] === 'professionFull' ? 'jobName': '' );
                }
            }
            catch (Exception|\Error $e) {
                $transParams['{'.$typeEntry['name'].'}'] = "_error_";
            }
        }

        return $transParams;
    }

    public function bankItemLog( Citizen $citizen, ItemPrototype $item, bool $toBank, bool $broken = false ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'item' => $item->getId(), 'broken' => $broken);
        if ($toBank)
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'bankGive']);
        else
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'bankTake']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function strangerBankItemLog( Town $town, ItemPrototype $item, ?DateTimeInterface $time = null ): TownLogEntry {
        $variables = array('citizen' => -141089, 'item' => $item->getId(), 'broken' => false);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'bankGive']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( $time ?? new DateTime('now') );
    }

    public function bankItemStealLog( Citizen $citizen, ItemPrototype $item, bool $anonymous, bool $broken = false ): TownLogEntry {
        if ($anonymous) {
            $variables = array('item' => $item->getId(), 'broken' => $broken);
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'bankStealSuccess']);
        } else {
            $variables = array('citizen' => $citizen->getId(), 'item' => $item->getId(), 'broken' => $broken);
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'bankStealFail']);
        }

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $anonymous ? null : $citizen );
    }

    public function bankItemTamerLog( Citizen $citizen, ItemPrototype $item, bool $broken = false ): TownLogEntry {

        $variables = array('citizen' => $citizen->getId(), 'item' => $item->getId(), 'broken' => $broken, 'dogname' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'bankGiveTamer']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function beyondTamerSendLog( Citizen $citizen, int $items ): TownLogEntry {

        $variables = array('citizen' => $citizen->getId(), 'count' => $items);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'beyondTamerSend']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function beyondItemLog( Citizen $citizen, ItemPrototype $item, bool $toFloor, bool $broken = false, $hide = false ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'item' => $item->getId(), 'broken' => $broken);
        if ($toFloor)
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $hide ? 'itemFloorHide' : 'itemFloorDrop']);
        else
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'itemFloorTake']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function wellLog( Citizen $citizen, bool $tooMuch ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        if ($tooMuch)
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'wellTakeMuch']);
        else
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'wellTake']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function wellAdd( Citizen $citizen, ?ItemPrototype $item, int $count ): TownLogEntry {
        if (isset($item)) {
            $variables = array('citizen' => $citizen->getId(), 'item' => $item->getId(), 'num' => $count);
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'wellAddItem']);
        }   
        else {
            $variables = array('citizen' => $citizen->getId(), 'num' => $count);
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'wellAdd']);
        }
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function wellAddShaman( Citizen $citizen, int $count ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'num' => $count);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'wellAddShaman']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function failureShaman( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'failureShaman']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function constructionsInvest( Citizen $citizen, BuildingPrototype $proto, int $ap, $slave_bonus = false ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'plan' => $proto->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $slave_bonus ? 'constructionsInvestSlave' : 'constructionsInvest']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function strangerConstructionsInvest( Town $town, BuildingPrototype $proto, ?DateTimeInterface $time = null ): TownLogEntry {
        $variables = array('citizen' => -141089, 'plan' => $proto->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'constructionsInvest']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( $time ?? new DateTime('now') );
    }

    public function constructionsInvestAP( Citizen $citizen, BuildingPrototype $proto, int $ap ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'plan' => $proto->getId(), 'ap' => $ap);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'constructionsInvestAP']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function constructionsInvestRepair( Citizen $citizen, BuildingPrototype $proto, int $ap, $slave_bonus = false ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'plan' => $proto->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $slave_bonus ? 'constructionsInvestRepairSlave' : 'constructionsInvestRepair']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function strangerConstructionsInvestRepair( Town $town, BuildingPrototype $proto, ?DateTimeInterface $time = null ): TownLogEntry {
        $variables = array('citizen' => -141089, 'plan' => $proto->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'constructionsInvestRepair']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( $time ?? new DateTime('now') );
    }

    public function constructionsInvestRepairAP( Citizen $citizen, BuildingPrototype $proto, int $ap ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'plan' => $proto->getId(), 'ap' => $ap);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'constructionsInvestRepairAP']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function constructionsDamage( Town $town, BuildingPrototype $proto, int $damage ): TownLogEntry {
        $variables = array('plan' => $proto->getId(), 'damage' => $damage);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $proto->getName() === 'small_arma_#00' ? 'constructionsDamageReactor' : 'constructionsDamage']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( null );
    }

    public function constructionsDestroy( Town $town, BuildingPrototype $proto, int $damage ): TownLogEntry {
        $variables = array('plan' => $proto->getId(), 'damage' => $damage);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $proto->getName() === 'small_arma_#00' ? 'constructionsDestroyReactor' : 'constructionsDestroy']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( null );
    }

    public function fireworkExplosion( Town $town, BuildingPrototype $proto ): TownLogEntry {
        $variables = array('plan' => $proto->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'fireworkExplosion']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( null );
    }

    public function constructionsNewSite( Citizen $citizen, BuildingPrototype $proto ): TownLogEntry {
        if ($proto->getParent()){
            $variables = array('citizen' => $citizen->getId(), 'plan' => $proto->getId(), 'parent' => $proto->getParent()->getId());
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'constructionsNewSiteDepend']);
        }
        else {
            $variables = array('citizen' => $citizen->getId(), 'plan' => $proto->getId());
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'constructionsNewSite']);
        }   
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function constructionsBuildingComplete( Citizen $citizen, BuildingPrototype $proto ): TownLogEntry {
        $list = $proto->getResources() ? $proto->getResources()->getEntries()->getValues() : [];
        if (!empty($list)){
            $varlist = array_map( function(ItemGroupEntry $e) { return array('id' => $e->getPrototype()->getId(), 'count' => $e->getChance()); }, $list );

            $variables = array('plan' => $proto->getId(), 'list' => $varlist);
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'constructionsBuildingComplete']);
        }
        else {
            $variables = array('plan' => $proto->getId());
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'constructionsBuildingCompleteNoResources']);
        }
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function constructionsBuildingCompleteSpawnItems( Building $building, $items ): TownLogEntry {
        $proto = $building->getPrototype();
        $variables = array('building' => $proto->getId(), 
            'list' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);}
              else { return array('id' => $e[0]->getId()); } }, $items ));
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'constructionsBuildingCompleteSpawnItems']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $building->getTown() )
            ->setDay( $building->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function constructionsBuildingCompleteWell( Building $building, int $water ): TownLogEntry {
        $variables = array('building' => $building->getPrototype()->getId(), 'num' => $water);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'constructionsBuildingCompleteWell']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $building->getTown() )
            ->setDay( $building->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function constructionsBuildingCompleteZombieKill( Building $building ): TownLogEntry {
        $variables = array('building' => $building->getPrototype()->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'constructionsBuildingCompleteZombieKill']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $building->getTown() )
            ->setDay( $building->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function doorControl( Citizen $citizen, bool $open ): TownLogEntry {
        if ($open)
            $action = T::__("geöffnet", 'game');
        else 
            $action = T::__("geschlossen", 'game');
        $variables = array('citizen' => $citizen->getId(), 'action' => $action);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'doorControl']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function doorControlAuto( Town $town, bool $open, ?DateTimeInterface $time ): TownLogEntry {
        if ($open)
            $action = T::__("geöffnet", 'game');
        else 
            $action = T::__("geschlossen", 'game');
        $variables = array('action' => $action);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'doorControlAuto']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( $time ?? new DateTime('now') );
    }

    public function doorPass( Citizen $citizen, bool $in ): TownLogEntry {
        if ($in)
            $action = T::__("betreten", 'game');
        else 
            $action = T::__("verlassen", 'game');
        $variables = array('citizen' => $citizen->getId(), 'action' => $action);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'doorPass']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function citizenTeleport( Citizen $citizen, Zone $zone ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenTeleport']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setZone( $zone )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function citizenJoin( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenJoin']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function citizenProfession( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'profession' => $citizen->getProfession()->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenProfession']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function citizenJoinProfession( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'profession' => $citizen->getProfession()->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenJoinProfession']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function strangerJoinProfession( Town $town, ?DateTimeInterface $time = null ): TownLogEntry {
        $variables = array('citizen' => -141089, 'profession' => -141089);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenJoinProfession']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( $time ?? new DateTime('now') );
    }

    public function citizenZombieAttackRepelled( Citizen $citizen, int $def, int $zombies ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'num' => $zombies);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenZombieAttackRepelled']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function citizenDeathsDuringAttack( Town $town, int $deaths ): TownLogEntry {
        $variables = array('deaths' => $deaths);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDeathsDuringAttack']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function citizenDeath( Citizen $citizen, int $zombies = 0, ?Zone $zone = null, ?int $day = null ): TownLogEntry {
        switch ($citizen->getCauseOfDeath()->getRef()) {
            case CauseOfDeath::NightlyAttack:
                $variables = array('citizen' => $citizen->getId(), 'num' => $zombies);
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDeathNightlyAttack']);
                break;
            case CauseOfDeath::Vanished:
                $variables = array('citizen' => $citizen->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDeathVanished']);
                break;
            case CauseOfDeath::Cyanide:
                $variables = array('citizen' => $citizen->getId(), 'cod' => $citizen->getCauseOfDeath()->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDeathCyanide']);
                break;
            case CauseOfDeath::Poison: case CauseOfDeath::GhulEaten:
                $variables = array('citizen' => $citizen->getId(), 'cod' => $citizen->getCauseOfDeath()->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDeathPoison']);
                break;
            case CauseOfDeath::Hanging: case CauseOfDeath::FleshCage:
                $variables = array('citizen' => $citizen->getId(), 'cod' => $citizen->getCauseOfDeath()->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDeathHanging']);
                break;
            case CauseOfDeath::ChocolateCross:
                $variables = array('citizen' => $citizen->getId(), 'cod' => $citizen->getCauseOfDeath()->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDeathCross']);
                break;
            case CauseOfDeath::Headshot:
                $variables = array('citizen' => $citizen->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDeathHeadshot']);
                break;
            default: 
                $variables = array('citizen' => $citizen->getId(), 'cod' => $citizen->getCauseOfDeath()->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDeathDefault']);
        }

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $day ?? $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setZone( $zone );
    }

    public function strangerDeath( Town $town, ?DateTimeInterface $time = null ): TownLogEntry {

        $variables = array('citizen' => -141089, 'cod' => $this->entity_manager->getRepository(CauseOfDeath::class)->findOneBy( ['ref' => CauseOfDeath::Unknown] )->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDeathDefault']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( $time ?? new DateTime('now') );
    }

    public function citizenDeathOnWatch( Citizen $citizen, int $zombies = 0, ?Zone $zone = null, ?int $day = null ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDeathOnWatch']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $day ?? $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setZone( $zone );
    }

    public function homeUpgrade( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'home' => $citizen->getHome()->getPrototype()->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'homeUpgrade']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function workshopConvert( Citizen $citizen, array $items_in, array $items_out ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 
            'list1' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);}
              else { return array('id' => $e[0]->getId()); } }, $items_in ),
            'list2' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);}
              else { return array('id' => $e[0]->getId()); } }, $items_out ));
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'workshopConvert']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function outsideMove( Citizen $citizen, Zone $zone1, Zone $zone2, bool $depart ): TownLogEntry {
        $is_zero_zone = ($zone1->getX() === 0 && $zone1->getY() === 0);

        $d_north = $zone2->getY() > $zone1->getY();
        $d_south = $zone2->getY() < $zone1->getY();
        $d_east  = $zone2->getX() > $zone1->getX();
        $d_west  = $zone2->getX() < $zone1->getX();

        $str = 'Horizont';
        if ($d_north) {
            if ($d_east)     $str = 'Nordosten';
            elseif ($d_west) $str = 'Nordwesten';
            else             $str = 'Norden';
        } elseif ($d_south) {
            if ($d_east)     $str = 'Südosten';
            elseif ($d_west) $str = 'Südwesten';
            else             $str = 'Süden';
        } elseif ($d_east)   $str = 'Osten';
        elseif ($d_west)     $str = 'Westen';

        // This breaks the sneak out capability of the ghoul. The caller of this function that would trigger this if statement is disabled.
        if ($is_zero_zone) 
        {
            $variables = array('citizen' => $citizen->getId(), 'direction' => $str);
            if ($depart) {               
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'townMoveLeave']);
            }
            else {
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'townMoveEnter']);
            }
        }
        else 
        {
            $variables = array('citizen' => $citizen->getId(), 'direction' => $str, 'profession' => $citizen->getProfession()->getId());
            if ($depart) {               
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'outsideMoveLeave']);
            }
            else {
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'outsideMoveEnter']);
            }
        }
        
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setZone( $is_zero_zone ? null : $zone1 )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function outsideMoveoutsideMoveFailInjury( Citizen $citizen ): TownLogEntry {
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'outsideMoveFailInjury']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables(['citizen' => $citizen->getId()])
            ->setTown( $citizen->getTown() )
            ->setZone( $citizen->getZone() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function outsideMoveoutsideMoveFailTerror( Citizen $citizen ): TownLogEntry {
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'outsideMoveFailTerror']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables(['citizen' => $citizen->getId()])
            ->setTown( $citizen->getTown() )
            ->setZone( $citizen->getZone() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function outsideDig( Citizen $citizen, ?ItemPrototype $item, ?DateTimeInterface $time = null ): TownLogEntry {
        $found_something = $item !== null;
        if ($found_something) {
            $variables = array('citizen' => $citizen->getId(), 'item' => $item->getId());
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'outsideDigSuccess']);
        }
        else {
            $variables = array('citizen' => $citizen->getId());
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'outsideDigFail']);
        }

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( $time ?? new DateTime('now') )
            ->setCitizen( $citizen )
            ->setZone( $citizen->getZone() );
    }

    public function outsideDigSurvivalist( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());

        return (new TownLogEntry())
            ->setLogEntryTemplate( $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'outsideDigSurvivalist']) )
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setZone( $citizen->getZone() );
    }

    public function outsideUncover( Citizen $citizen, int $count = 1, ?ItemPrototype $proto = null): TownLogEntry {
        if ($proto) {
            $variables = array('citizen' => $citizen->getId(), 'count' => $count, 'item' => $proto->getId());
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'outsideUncoverItem']);
        } else {
            $variables = array('citizen' => $citizen->getId());
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'outsideUncover']);
        }

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( $time ?? new DateTime('now') )
            ->setCitizen( $citizen )
            ->setZone( $citizen->getZone() );
    }

    public function outsideUncoverComplete( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'type' => $citizen->getZone()->getPrototype()->getLabel());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'outsideUncoverComplete']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( $time ?? new DateTime('now') )
            ->setCitizen( $citizen )
            ->setZone( $citizen->getZone() );
    }

    public function outsideFoundHiddenItems( Citizen $citizen, ?array $items ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'items' => array_map( function($e) {
            if(array_key_exists('count', $e)) {
                return array('id' => $e['item']->getPrototype()->getId(),'count' => $e['count']);
            } else {
                return array('id' => $e->getPrototype->getId());
            }
            }, $items ));
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'outsideFoundHiddenItems']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen )
            ->setZone( $citizen->getZone() );
    }

    public function dumpItems(Citizen $citizen, $items, int $defense): TownLogEntry {
        $variables = [
            'citizen' => $citizen->getId(),
            'items' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);} else { return array('id' => $e[0]->getId()); } }, $items ),
            'def' => $defense
        ];
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'dumpItems']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function constructionsBuildingCompleteAllOrNothing( Town $town, $tempDef ): TownLogEntry {
        $variables = array('def' => $tempDef);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'constructionsBuildingCompleteAllOrNothing']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyInternalAttackKill( Citizen $zombie, Citizen $victim ): TownLogEntry {
        $variables = array('zombie' => $zombie->getId(), 'victim' => $victim->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyInternalAttackKill']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $zombie->getTown() )
            ->setDay( $zombie->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $zombie )
            ->setSecondaryCitizen( $victim );
    }

    public function nightlyInternalAttackDestroy( Citizen $zombie, Building $building ): TownLogEntry {
        $variables = array('zombie' => $zombie->getId(), 'building' => $building->getPrototype()->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyInternalAttackDestroy']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $zombie->getTown() )
            ->setDay( $zombie->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $zombie );
    }

    public function nightlyInternalAttackWell( Citizen $zombie, int $units ): TownLogEntry {
        $variables = array('zombie' => $zombie->getId(), 'num' => $units);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyInternalAttackWell']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $zombie->getTown() )
            ->setDay( $zombie->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $zombie );
    }

    public function nightlyDevastationAttackWell(int $units, Town $town): TownLogEntry {
        $variables = array('num' => $units);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyDevastationAttackWell']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown($town)
            ->setDay($town->getDay())
            ->setTimestamp(new DateTime('now'));
    }

    public function nightlyInternalAttackStart(Town $town): TownLogEntry {
        $variables = array();
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyInternalAttackStart']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyInternalAttackNothing( Citizen $zombie ): TownLogEntry {
        $templateList = [
            'nightlyInternalAttackNothing1',
            'nightlyInternalAttackNothing2',
            'nightlyInternalAttackNothing3',
            'nightlyInternalAttackNothing4',
        ];
        $variables = array('zombie' => $zombie->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $templateList[array_rand($templateList,1)]]);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $zombie->getTown() )
            ->setDay( $zombie->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $zombie );
    }

    public function nightlyInternalAttackNothingSummary( Town $town, int $useless, bool $devastated = false ): TownLogEntry {
        $variables = $devastated ? [] : array('count' => $useless);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $devastated ? 'nightlyInternalAttackDevastSummary' : 'nightlyInternalAttackNothingSummary']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackCancelled( Town $town ): TownLogEntry {
        $variables = array();
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackCancelled']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackBegin( Town $town, int $num_zombies, bool $former_citizens = false, ?Citizen $specific = null ): TownLogEntry {
        $variables = array('num' => $num_zombies, 'citizen' => $specific ? $specific->getId() : null);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $former_citizens ? ($specific ? 'nightlyAttackBeginOneCitizen' : 'nightlyAttackBeginWithCitizens') : 'nightlyAttackBegin']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackBegin2( Town $town ): TownLogEntry {
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackBegin2']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables([])
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackDisappointed( Town $town, ?Citizen $specific = null ): TownLogEntry {
        $variables = array('citizen' => $specific ? $specific->getId() : null);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $specific ? 'nightlyAttackDisappointedCitizen' : 'nightlyAttackDisappointed']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackSummary( Town $town, bool $door_open, int $num_zombies, bool $watch = false ): TownLogEntry {
        if ($door_open) {
            $variables = array('num' => $num_zombies);
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackSummaryOpenDoor']);
        }
        elseif ($num_zombies > 0) {
            $variables = array('num' => $num_zombies);
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackSummarySomeZombies']);
        }
        else {
            $variables = [];
            if($watch)
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackUselessWatch']);
            else
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackSummaryNoZombies']);
        }

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackWatchersCount( Town $town, int $watchers ): TownLogEntry {
        $variables = array('watchers' => $watchers);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackWatcherCount']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackWatchers( Town $town, $watchers ): TownLogEntry {
        $citizenList = [];
        foreach ($watchers as $watcher) {
            $citizenList[] = array('id' => $watcher->getCitizen()->getId());
        }
        $variables = array('citizens' => $citizenList);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => count($watchers) === 1 ? 'nightlyAttackOneWatcher' : 'nightlyAttackWatchers']);
        
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackNoWatchers( Town $town ): TownLogEntry {
        $variables = array();
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackNoWatchers']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackWatchersZombieStopped( Town $town, int $zombies ): TownLogEntry {
        $variables = array('zombies' => $zombies);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackWatchersZombieStopped']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackWatchersZombieThrough( Town $town, int $zombies ): TownLogEntry {
        $variables = array('zombies' => $zombies);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackWatchersZombieThrough']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackWatchersZombieAllStopped( Town $town ): TownLogEntry {
        $variables = array();
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackWatchersZombieAllStopped']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackWatcherWound( Town $town, ?Citizen $citizen ): TownLogEntry {
        $variables = $citizen ? array('citizen' => $citizen->getId()) : [];
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $citizen ? 'nightlyAttackWatcherWound' : 'nightlyAttackWatchersWound']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setCitizen( $citizen )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackWatcherTerror( Town $town, ?Citizen $citizen ): TownLogEntry {
        $variables = $citizen ? array('citizen' => $citizen->getId()) : [];
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $citizen ? 'nightlyAttackWatcherTerror' : 'nightlyAttackWatchersTerror']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setCitizen( $citizen )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackLazy( Town $town, int $num_attacking_zombies ): TownLogEntry {
        $variables = array('num' => $num_attacking_zombies);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $town->getDevastated() ? 'nightlyAttackLazyDevast' : 'nightlyAttackLazy' ]);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackBuildingDefenseWater( Building $building, int $num ): TownLogEntry {
        $variables = array('building' => $building->getPrototype()->getId(), 'num' => $num);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackBuildingDefenseWater']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $building->getTown() )
            ->setDay( $building->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackBuildingBatteries( Building $building, int $num ): TownLogEntry {
        $variables = array('building' => $building->getPrototype()->getId(), 'num' => $num);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackBuildingBatteries']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $building->getTown() )
            ->setDay( $building->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackUpgradeBuildingWell( Building $building, int $num ): TownLogEntry {
        $variables = array('building' => $building->getPrototype()->getId(), 'num' => $num);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackUpgradeBuildingWell']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $building->getTown() )
            ->setDay( $building->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackUpgradeBuildingItems( Building $building, ?array $items ): TownLogEntry {
        $variables = array('building' => $building->getPrototype()->getId(), 
            'items' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);}
              else { return array('id' => $e[0]->getId()); } }, $items ));
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackUpgradeBuildingItems']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $building->getTown() )
            ->setDay( $building->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    /**
     * @param Town $town
     * @param Item|ItemPrototype $item
     * @return TownLogEntry
     */
    public function nightlyAttackProductionBlueprint( Town $town, ItemPrototype $item, BuildingPrototype $building): TownLogEntry {
        $variables = array('item' => $item->getId(), 'building' => $building->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackProductionBlueprint']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackProduction( Building $building, ?array $items = [] ): TownLogEntry {        
        $variables = array('building' => $building->getPrototype()->getId(), 
            'items' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);}
              else { return array('id' => $e[0]->getId()); } }, $items ));
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $building->getPrototype()->getName() === 'item_vegetable_tasty_#00' ? 'nightlyAttackProductionVegetables' : 'nightlyAttackProduction']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $building->getTown() )
            ->setDay( $building->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackDestroyBuilding( Town $town, Building $building ): TownLogEntry {
        $variables = array('buildingName' => $building->getPrototype()->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackDestroyBuilding']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackBankItemsDestroy( Town $town, $items, $count): TownLogEntry {
        $variables = array(
            'list' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);}
            else { return array('id' => $e[0]->getId()); } }, $items ),
            'num' => $count
        );
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackBankItemsDestroy']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function nightlyAttackDevastated( Town $town ): TownLogEntry {
        $variables = array();
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'nightlyAttackDevastated']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $town )
            ->setDay( $town->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function citizenComplaint( Complaint $complaint ): TownLogEntry {
        $variables = array('citizen' => $complaint->getCulprit()->getId());
        if ($complaint->getSeverity() > Complaint::SeverityNone) {
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenComplaintSet']);
        }
        else {
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenComplaintUnset']);
        }
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $complaint->getAutor()->getTown() )
            ->setDay( $complaint->getAutor()->getTown()->getDay() )
            ->setCitizen( $complaint->getCulprit() )
            ->setTimestamp( new DateTime('now') )
            ->setAdminOnly(true);
    }

    public function citizenBanish( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenBanish']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setCitizen( $citizen )
            ->setTimestamp( new DateTime('now') );
    }

    public function citizenDisposal( Citizen $actor, Citizen $disposed, int $action, ?array $items = [] ): TownLogEntry {
        switch ($action) {
            case Citizen::Thrown:
                $variables = array('citizen' => $actor->getId(), 'disposed' => $disposed->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDisposalDrag']);
                break;
            case Citizen::Watered:
                $variables = array('citizen' => $actor->getId(), 'disposed' => $disposed->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDisposalWater']);
                break;
            case Citizen::Cooked:
                $variables = array('citizen' => $actor->getId(), 'disposed' => $disposed->getId(), 
                    'items' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);}
                        else { return array('id' => $e[0]->getId()); } }, $items ));
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDisposalCremato']);
                break;
            case Citizen::Ghoul:
                $variables = array();
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDisposalGhoul']);
                break;
            default:
                $variables = array('citizen' => $actor->getId(), 'disposed' => $disposed->getId());
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenDisposalDefault']);
                break;
        }

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $actor->getTown() )
            ->setDay( $actor->getTown()->getDay() )
            ->setCitizen( $action != 4 ? $actor : null )
            ->setSecondaryCitizen( $disposed )
            ->setTimestamp( new DateTime('now') );
    }

    public function townSteal( Citizen $victim, ?Citizen $actor, ItemPrototype $item, bool $up, bool $santa = false, $broken = false, bool $leprechaun = false): TownLogEntry {

        if ($up){
            if($santa || $leprechaun){
                $variables = array('victim' => $victim->getId(), 'item' => $item->getId(), 'broken' => $broken);
                if($santa)
                    $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'townStealSanta']);
                else
                    $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'townStealLeprechaun']);
            } 
            else {
                if ($actor) {
                    $variables = array('actor' => $actor->getId(), 'victim' => $victim->getId(), 'item' => $item->getId(), 'broken' => $broken);
                    $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'townStealCaught']);
                }
                else {
                    $variables = array('victim' => $victim->getId(), 'item' => $item->getId(), 'broken' => $broken);
                    $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'townStealUncaught']);
                }
            }
        }
        else {
            if ($actor) {
                $variables = array('actor' => $actor->getId(), 'victim' => $victim->getId(), 'item' => $item->getId(), 'broken' => $broken);
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'townSmuggleCaught']);
            }
            else {
                $variables = array('victim' => $victim->getId(), 'item' => $item->getId(), 'broken' => $broken);
                $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'townSmuggleUncaught']);
            }
        }
            
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $victim->getTown() )
            ->setDay( $victim->getTown()->getDay() )
            ->setCitizen( $victim )
            ->setSecondaryCitizen( $actor )
            ->setTimestamp( new DateTime('now') );
    }

    public function townLoot( Citizen $victim, ?Citizen $actor, ItemPrototype $item, bool $up, bool $santa = false, $broken = false): TownLogEntry {

        $variables = array('actor' => $actor->getId(), 'victim' => $victim->getId(), 'item' => $item->getId(), 'broken' => $broken);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'townLoot']);
            
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $victim->getTown() )
            ->setDay( $victim->getTown()->getDay() )
            ->setCitizen( $victim )
            ->setSecondaryCitizen( $actor )
            ->setTimestamp( new DateTime('now') );
    }

    public function zombieKill( Citizen $citizen, ?ItemPrototype $item, int $kills, ?string $sourceAction = null ): TownLogEntry {
        if ($sourceAction === "hero_generic_punch") {
            $variables = array('citizen' => $citizen->getId(), 'kills' => $kills);
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'zombieKillHeroPunch']);
        } else if ($item) {
            $variables = array('citizen' => $citizen->getId(), 'item' => $item->getId(), 'kills' => $kills);
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'zombieKillWeapon']);
        } else {
            $variables = array('citizen' => $citizen->getId(), 'kills' => $kills);
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'zombieKillHands']);
        }

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function zombieKillHandsFail( Citizen $citizen): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'zombieKillHandsFail']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function zombieKillShaman( Citizen $citizen, int $kills ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'kills' => $kills);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'zombieKillShaman']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function beyondChat( Citizen $sender, string $message ): TownLogEntry {
        $variables = array('sender' => $sender->getId(), 'message' => $message);
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'beyondChat']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $sender->getTown() )
            ->setDay( $sender->getTown()->getDay() )
            ->setZone( $sender->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $sender );
    }

    public function beyondCampingImprovement( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'beyondCampingImprovement']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function beyondCampingItemImprovement( Citizen $citizen, ItemPrototype $item ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'item' => $item->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'beyondCampingItemImprovement']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function beyondCampingHide( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'beyondCampingHide']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function beyondCampingUnhide( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'beyondCampingUnhide']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function beyondEscortEnable( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'beyondEscortEnable']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function beyondEscortDisable( Citizen $citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'beyondEscortDisable']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function beyondEscortTakeCitizen( Citizen $citizen, Citizen $target_citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'target_citizen' => $target_citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'beyondEscortTakeCitizen']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function beyondEscortCitizenBackHome( Citizen $citizen, Citizen $leader ): TownLogEntry {
        $variables = array('escortee' => $citizen->getId(), 'leader' => $leader->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'beyondEscortCitizenBackHome']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function beyondEscortReleaseCitizen( Citizen $citizen, Citizen $target_citizen ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'target_citizen' => $target_citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'beyondEscortReleaseCitizen']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $citizen->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function heroicReturnLog( Citizen $citizen, Zone $zone ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'heroReturn']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setZone( $zone )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function heroicRescueLog( Citizen $hero, Citizen $citizen, Zone $zone ): TownLogEntry {
        $variables = array('hero' => $hero->getId(), 'citizen' => $citizen->getId(), 'pos' => "[{$zone->getX()},{$zone->getY()}]");
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'heroRescue']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $hero->getTown() )
            ->setDay( $hero->getTown()->getDay() )
            ->setZone( null )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $hero )
            ->setSecondaryCitizen( $citizen );
    }

    public function shamanHealLog( Citizen $shaman, Citizen $citizen ): TownLogEntry {
        $variables = array('shaman' => $shaman->getId(), 'citizen' => $citizen->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'shamanHeal']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $shaman->getTown() )
            ->setDay( $shaman->getTown()->getDay() )
            ->setZone( null )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $shaman )
            ->setSecondaryCitizen( $citizen );
    }

    public function citizenAttack( Citizen $attacker, Citizen $defender, bool $wounded ): TownLogEntry {
        $variables = array('attacker' => $attacker->getId(), 'defender' => $defender->getId());
        $template = $attacker->getZone()
            ? $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $wounded ? 'citizenAttackWoundedOutside' : 'citizenAttackOutside'])
            : $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $wounded ? 'citizenAttackWounded' : 'citizenAttack']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $attacker->getTown() )
            ->setDay( $attacker->getTown()->getDay() )
            ->setZone( $attacker->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $attacker )
            ->setSecondaryCitizen( $defender );
    }

    public function citizenHomeIntrusion( Citizen $intruder, Citizen $victim, bool $act ): TownLogEntry {
        $variables = array('intruder' => $intruder->getId(), 'victim' => $victim->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $act ? 'citizenIntrusionAct' : 'citizenIntrusionBase']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $intruder->getTown() )
            ->setDay( $intruder->getTown()->getDay() )
            ->setZone( $intruder->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $intruder )
            ->setSecondaryCitizen( $victim );
    }


    public function sandballAttack( Citizen $attacker, Citizen $defender, bool $wounded ): TownLogEntry {
        $variables = array('attacker' => $attacker->getId(), 'defender' => $defender->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $wounded ? 'sandballAttackWounded' : 'sandballAttack']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $attacker->getTown() )
            ->setDay( $attacker->getTown()->getDay() )
            ->setZone( $attacker->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $attacker )
            ->setSecondaryCitizen( $defender );
    }

    public function citizenTownGhoulAttack( Citizen $attacker, Citizen $defender ): TownLogEntry {
        $variables = array('attacker' => $attacker->getId(), 'defender' => $defender->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'citizenTownGhoulAttack']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $attacker->getTown() )
            ->setDay( $attacker->getTown()->getDay() )
            ->setZone( $attacker->getZone() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $attacker )
            ->setSecondaryCitizen( $defender );
    }

    public function citizenBeyondGhoulAttack( Citizen $attacker, Citizen $defender, bool $ambient  ): TownLogEntry {
        $variables = $ambient ? [] : array('attacker' => $attacker->getId(), 'defender' => $defender->getId());
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $ambient ? 'citizenBeyondGhoulAttack1' : 'citizenBeyondGhoulAttack2']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $attacker->getTown() )
            ->setDay( $attacker->getTown()->getDay() )
            ->setZone( $attacker->getZone() )
            ->setTimestamp( new DateTime('now') );
    }

    public function catapultUsage( Citizen $master, Item $item, Zone $target ): TownLogEntry {
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'catapultUsage']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables([
                'master' => $master->getId(),
                'item' => $item->getPrototype()->getId(),
                'x' => $target->getX(),
                'y' => $target->getY()
            ])
            ->setTown( $master->getTown() )
            ->setDay( $master->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') );
    }

    public function catapultImpact( Item $item, Zone $target ): TownLogEntry {
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'catapultImpact']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables([
                'item' => $item->getPrototype()->getId(),
            ])
            ->setTown( $target->getTown() )
            ->setDay( $target->getTown()->getDay() )
            ->setZone( ($target->getX() === 0 && $target->getY() === 0) ? null : $target )
            ->setTimestamp( new DateTime('now') );
    }

    public function bankBanRecovery( Citizen $citizen, $items, $gallows, $cage ): TownLogEntry {
        $variables = array('shunned' => $citizen->getId(),
            'list' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);}
            else { return array('id' => $e[0]->getId()); } }, $items ));
        if ($gallows)
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'bankBanRecoveryDeath']);
        else if ($cage)
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'bankBanRecoveryCage']);
        else
            $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'bankBanRecovery']);
        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function publicJustice( Citizen $citizen, int $def = 0 ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(), 'def' => $def);

        $template = ($def === 0)
            ? $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'banishmentKillHanging'])
            : $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'banishmentKillCage']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function houseRecycled( Citizen $citizen, $items ): TownLogEntry {
        $variables = array('citizen' => $citizen->getId(),
            'list' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);}
            else { return array('id' => $e[0]->getId()); } }, $items ));
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => empty($items) ? 'houseRecycledEmpty' : 'houseRecycledItems']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setCitizen( $citizen );
    }

    public function zoneUnderControl( Zone $zone ): TownLogEntry {
        $variables = array();
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'zoneUnderControl']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables($variables)
            ->setTown( $zone->getTown() )
            ->setDay( $zone->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setZone($zone);
    }

    public function zoneSearchInterrupted( Zone $zone, Citizen $citizen ): TownLogEntry {
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'zoneSearchInterrupted']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables(['citizen' => $citizen->getId()])
            ->setTown( $zone->getTown() )
            ->setDay( $zone->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setZone($zone);
    }

    public function zoneEscapeTimerExpired( Zone $zone, ?DateTimeInterface $time = null): TownLogEntry {
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'zoneEscapeTimerExpired']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables([])
            ->setTown( $zone->getTown() )
            ->setDay( $zone->getTown()->getDay() )
            ->setTimestamp( $time ?? new DateTime('now') )
            ->setZone($zone);
    }

    public function zoneLostControlLeaving( Zone $zone, Citizen $leaving_citizen ): TownLogEntry {
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'zoneLostControlLeaving']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables(['citizen' => $leaving_citizen->getId(),])
            ->setTown( $zone->getTown() )
            ->setDay( $zone->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setZone($zone);
    }

    public function zoneEscapeItemUsed( Citizen $citizen, ItemPrototype $item, int $duration): TownLogEntry {
        $variables = array();
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'zoneEscapeItemUsed']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setVariables([
                               'item' => $item->getId(),
                               'citizen' => $citizen->getId(),
                               'duration' => $duration,
                           ])
            ->setTown( $citizen->getTown() )
            ->setDay( $citizen->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setZone($citizen->getZone());
    }

    public function smokeBombUsage( Zone $zone ): TownLogEntry {
        $template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'smokeBombUsage']);

        return (new TownLogEntry())
            ->setLogEntryTemplate($template)
            ->setTown( $zone->getTown() )
            ->setDay( $zone->getTown()->getDay() )
            ->setTimestamp( new DateTime('now') )
            ->setZone($zone);
    }
}
