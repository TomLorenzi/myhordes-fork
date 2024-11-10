<?php

namespace App\Controller\Messages;

use App\Controller\CustomAbstractController;
use App\Entity\Announcement;
use App\Entity\Award;
use App\Entity\Citizen;
use App\Entity\Emotes;
use App\Entity\Forum;
use App\Entity\ForumUsagePermissions;
use App\Entity\Post;
use App\Entity\Town;
use App\Entity\User;
use App\Service\CitizenHandler;
use App\Service\ErrorHelper;
use App\Service\HookExecutor;
use App\Service\HTMLService;
use App\Service\InventoryHandler;
use App\Service\PermissionHandler;
use App\Service\RandomGenerator;
use App\Service\TimeKeeperService;
use App\Service\ConfMaster;
use App\Service\UserHandler;
use App\Structures\ForumPermissionAccessor;
use App\Structures\HTMLParserInsight;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @method User getUser
 */
#[Route(path: '/', condition: 'request.isXmlHttpRequest()')]
#[IsGranted('ROLE_USER')]
class MessageController extends CustomAbstractController
{
    const int ErrorForumNotFound     = ErrorHelper::BaseForumErrors + 1;
    const int ErrorPostTextLength    = ErrorHelper::BaseForumErrors + 2;
    const int ErrorPostTitleLength   = ErrorHelper::BaseForumErrors + 3;
    const int ErrorPMItemLimitHit    = ErrorHelper::BaseForumErrors + 4;
    const int ErrorForumLimitHit     = ErrorHelper::BaseForumErrors + 5;
    const int ErrorGPMMemberLimitHit = ErrorHelper::BaseForumErrors + 6;
    const int ErrorGPMThreadLimitHit = ErrorHelper::BaseForumErrors + 7;
    const int ErrorPMItemChaosOut    = ErrorHelper::BaseForumErrors + 8;
    const int ErrorPostTitleTextMissing  = ErrorHelper::BaseForumErrors + 9;
    const int ErrorPostTextTooLong   = ErrorHelper::BaseForumErrors + 10;

    protected HTMLService $html;
    protected RandomGenerator $rand;
    protected Packages $asset;
    protected PermissionHandler $perm;
    protected UserHandler $userHandler;

    public function __construct(HTMLService $html, RandomGenerator $r, TranslatorInterface $t, Packages $a, EntityManagerInterface $em, InventoryHandler $ih, TimeKeeperService $tk, PermissionHandler $p, ConfMaster $conf, CitizenHandler $ch, UserHandler $uh, HookExecutor $hookExecutor)
    {
        parent::__construct($conf, $em, $tk, $ch, $ih, $t, $hookExecutor);
        $this->asset = $a;
        $this->rand = $r;
        $this->perm = $p;
        $this->userHandler = $uh;
        $this->html = $html;
    }

    protected function preparePost(User $user, ?Forum $forum, $post, ?Town $town = null, ?HTMLParserInsight &$insight = null, bool $is_update = false): bool {
        if (!$town && $forum && $forum->getTown())
            $town = $forum->getTown();

        $p = $forum ? $this->perm->getEffectivePermissions($this->getUser(), $forum) : (
            ($this->isGranted("ROLE_SUB_ADMIN")  * ForumUsagePermissions::PermissionFormattingAdmin) |
            ($this->isGranted("ROLE_CROW")   * ForumUsagePermissions::PermissionFormattingModerator) |
            (($this->isGranted("ROLE_ORACLE") || $this->isGranted("ROLE_ANIMAC")) * ForumUsagePermissions::PermissionFormattingOracle)
        );

        $is_announcement = is_a($post, Announcement::class);

        $tx = $post->getText();
        $this->html->htmlPrepare($user, $p, true, $tx, $town, $insight, allow_all_emotes: $is_announcement);

        $distorted = false;
        if ($town && $user->getActiveCitizen() && $town->getCitizens()->contains($user->getActiveCitizen()) && (!is_a( $post, Post::class) || $post->getType() === 'USER' || $post->getType() === 'GLORY')) {
            $citizen = $user->getActiveCitizen();
            $tx = $this->html->htmlDistort( $tx,
                    ($this->citizen_handler->hasStatusEffect($citizen, 'drunk') ? HTMLService::ModulationDrunk : HTMLService::ModulationNone) |
                    ($this->citizen_handler->hasStatusEffect($citizen, 'terror') ? HTMLService::ModulationTerror : HTMLService::ModulationNone) |
                    ($this->citizen_handler->hasStatusEffect($citizen, 'wound1') ? HTMLService::ModulationHead : HTMLService::ModulationNone)
                , $town->getRealLanguage( $this->generatedLangsCodes ) ?? $this->getUserLanguage(  ), $distorted );
        }

        $post->setText($tx);
        if ($distorted && is_a( $post, Post::class )) $post->setEditingMode( Post::EditorLocked );

        if ($post instanceof Post)
            $post->setSearchText( strip_tags( $tx ) );

        if ($post instanceof Post && !$is_update) {
            if ($post->getType() !== 'CROW' && $post->getType() !== 'ANIM'){
                $citizen = $user->getActiveCitizen();
                // Town forum message
                if($forum !== null && $forum->getTown()) {
                    if ($citizen && $citizen->getTown() === $forum->getTown()) {

                        if ($citizen->getZone() && ($citizen->getZone()->getX() !== 0 || $citizen->getZone()->getY() !== 0))  {
                            if($citizen->getTown()->getChaos()){
                                $note = '{outside}';
                            } else {
                                $note = "[{$citizen->getZone()->getX()}, {$citizen->getZone()->getY()}]";
                            }
                        }
                        else {
                            $note = '{at_00}';
                        }

                        $post
                        ->setNoteIcons(["build/images/professions/{$citizen->getProfession()->getIcon()}.gif", 'build/images/icons/item_map.gif'])
                        ->setNote("<span>$note</span>");
                    }
                } elseif ($post->getType() === 'USER' || $post->getType() === 'GLORY') { // World forum message
                    if($citizen && $citizen->getTown()?->getRankingEntry() !== null) {
                        $town_name = $citizen->getTown()->getName();
                        $town_link = $this->generateUrl('soul_view_town', ['sid' => $user->getId(), 'idtown' => $citizen->getTown()?->getRankingEntry()->getId()]);
                        $post->setNoteIcons(['build/images/soul/small_falsecity.gif']);
                        $post->setNote("<span class=\"pointer hide-sm hide-md\" x-ajax-href=\"{$town_link}\">$town_name</span>");
                    } else {
                        $post->setNoteIcons(['build/images/emotes/buried.gif']);
                        $note = '{ancient}';
                        $post->setNote("<span class='hide-sm hide-md'>$note</span>");
                    }
                }
            }
        }

        return true;
    }

    protected function getEmotesByUser(User $user, bool $url_only = false): array {
        $repo = $this->entity_manager->getRepository(Emotes::class);
        $emotes = $repo->getDefaultEmotes();
        $awards = $this->entity_manager->getRepository(Award::class)->getAwardsByUser($user);
        $results = array();

        foreach($awards as $entry) {
            /** @var $entry Award */
            if (!$entry->getPrototype() || $entry->getPrototype()->getAssociatedTag() === null) continue;
            $emote = $repo->findByTag($entry->getPrototype()->getAssociatedTag());
            if(!in_array($emote, $emotes)) {
                $emotes[] = $emote;
            }
        }

        foreach($emotes as $entry) {
            /** @var $entry Emotes */
            if ($entry === null) continue;
            $results[$entry->getTag()] = [
                'display' => $url_only ? $entry->getPath() : "<img alt='{$entry->getTag()}' src='{$this->asset->getUrl( $entry->getPath() )}'/>",
                'i18n' => $entry->getI18n(),
                'orderIndex' => $entry->getOrderIndex()
            ];
        }
        return $results;
    }

    protected function getPermissionObject($forumOrPermission = null): ForumPermissionAccessor {
        $p = 0;
        if (is_int($forumOrPermission)) $p = $forumOrPermission;
        elseif (is_a($forumOrPermission, Forum::class)) $p = $this->perm->getEffectivePermissions($this->getUser(), $forumOrPermission);

        return new ForumPermissionAccessor($p, $this->perm);
    }
}
