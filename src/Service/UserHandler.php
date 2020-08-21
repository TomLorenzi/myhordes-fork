<?php

namespace App\Service;

use App\Entity\Avatar;
use App\Entity\CauseOfDeath;
use App\Entity\Changelog;
use App\Entity\HeroSkillPrototype;
use App\Entity\Picto;
use App\Entity\User;
use Imagick;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class UserHandler
{
    const NoError                            = 0;
    const ErrorAvatarBackendUnavailable      = ErrorHelper::BaseAvatarErrors +  1;
    const ErrorAvatarTooLarge                = ErrorHelper::BaseAvatarErrors +  2;
    const ErrorAvatarFormatUnsupported       = ErrorHelper::BaseAvatarErrors +  3;
    const ErrorAvatarImageBroken             = ErrorHelper::BaseAvatarErrors +  4;
    const ErrorAvatarResolutionUnacceptable  = ErrorHelper::BaseAvatarErrors +  5;
    const ErrorAvatarProcessingFailed        = ErrorHelper::BaseAvatarErrors +  6;
    const ErrorAvatarInsufficientCompression = ErrorHelper::BaseAvatarErrors +  7;

    const ImageProcessingForceImagick = 0;
    const ImageProcessingPreferImagick = 1;
    const ImageProcessingDisableImagick = 2;


    private $entity_manager;
    private $roles;
    private $container;

    public function __construct( EntityManagerInterface $em, RoleHierarchyInterface $roles,ContainerInterface $c)
    {
        $this->entity_manager = $em;
        $this->container = $c;
        $this->roles = $roles;
    }

    public function getPoints(User $user){
        $pictos = $this->entity_manager->getRepository(Picto::class)->findNotPendingByUser($user);
        $points = 0;

        if($user->getAllSoulPoints() >= 100) {
            $points += 13;
        }
        if($user->getAllSoulPoints() >= 500) {
            $points += 33;
        }
        if($user->getAllSoulPoints() >= 1000) {
            $points += 66;
        }
        if($user->getAllSoulPoints() >= 2000) {
            $points += 132;
        }
        if($user->getAllSoulPoints() >= 3000) {
            $points += 198;
        }

        foreach ($pictos as $picto) {
            switch($picto["name"]){
                case "r_heroac_#00": case "r_explor_#00":
                    if ($picto["c"] >= 15)
                        $points += 3.5;
                    if ($picto["c"] >= 30)
                        $points += 6.5;
                    break;
                case "r_cookr_#00": case "r_cmplst_#00": case "r_camp_#00": case "r_drgmkr_#00":
                    if ($picto["c"] >= 10)
                        $points += 3.5;
                    if ($picto["c"] >= 25)
                        $points += 6.5;
                    break;
                case "r_animal_#00":
                    if ($picto["c"] >= 30)
                        $points += 3.5;
                    if ($picto["c"] >= 60)
                        $points += 6.5;
                    break;
                case "r_chstxl_#00": case "r_ruine_#00":
                    if ($picto["c"] >= 5)
                        $points += 3.5;
                    if ($picto["c"] >= 10)
                        $points += 6.5;
                    break;
                case "r_build_#00":
                    if ($picto["c"] >= 100)
                        $points += 3.5;
                    if ($picto["c"] >= 200)
                        $points += 6.5;
                    break;
                case "status_clean_#00":
                    if ($picto["c"] >= 20)
                        $points += 3.5;
                    if ($picto["c"] >= 75)
                        $points += 6.5;
                    break;
                case "r_ebuild_#00":
                    if ($picto["c"] >= 1)
                        $points += 3.5;
                    if ($picto["c"] >= 3)
                        $points += 6.5;
                    break;
                case "r_digger_#00":
                    if ($picto["c"] >= 50)
                        $points += 3.5;
                    if ($picto["c"] >= 300)
                        $points += 6.5;
                    break;
                case "r_deco_#00":
                    if ($picto["c"] >= 100)
                        $points += 3.5;
                    if ($picto["c"] >= 250)
                        $points += 6.5;
                    break;
                case "r_explo2_#00":
                    if ($picto["c"] >= 5)
                        $points += 3.5;
                    if ($picto["c"] >= 15)
                        $points += 6.5;
                    break;
                case "r_guide_#00":
                    if ($picto["c"] >= 300)
                        $points += 3.5;
                    if ($picto["c"] >= 1000)
                        $points += 6.5;
                    break;
                case "r_theft_#00": case "r_jtamer_#00": case "r_jrangr_#00": case "r_jguard_#00": case "r_jermit_#00":
                case "r_jtech_#00": case "r_jcolle_#00":
                    if ($picto["c"] >= 10)
                        $points += 3.5;
                    if ($picto["c"] >= 30)
                        $points += 6.5;
                    break;
                case "r_maso_#00": case "r_guard_#00":
                    if ($picto["c"] >= 20)
                        $points += 3.5;
                    if ($picto["c"] >= 40)
                        $points += 6.5;
                    break;
                case "r_surlst_#00":
                    if ($picto["c"] >= 10)
                        $points += 3.5;
                    if ($picto["c"] >= 15)
                        $points += 6.5;
                    if ($picto["c"] >= 30)
                        $points += 10;
                    if ($picto["c"] >= 50)
                        $points += 13;
                    if ($picto["c"] >= 100)
                        $points += 16.5;
                    break;
                case "r_suhard_#00":
                    if ($picto["c"] >= 5)
                        $points += 3.5;
                    if ($picto["c"] >= 10)
                        $points += 6.5;
                    if ($picto["c"] >= 20)
                        $points += 10;
                    if ($picto["c"] >= 40)
                        $points += 13;
                    break;
                case "r_doutsd_#00":
                    if($picto["c"] >= 20)
                        $points += 3.5;
                    break;
                case "r_door_#00":
                    if($picto["c"] >= 1)
                        $points += 3.5;
                    if($picto["c"] >= 5)
                        $points += 6.5;
                    break;
                case "r_wondrs_#00":
                    if($picto["c"] >= 20)
                        $points += 3.5;
                    if($picto["c"] >= 50)
                        $points += 6.5;
                    break;
                case "r_rp_#00":
                    if($picto["c"] >= 5)
                        $points += 3.5;
                    if($picto["c"] >= 10)
                        $points += 6.5;
                    if($picto["c"] >= 20)
                        $points += 10;
                    if($picto["c"] >= 30)
                        $points += 13;
                    if($picto["c"] >= 40)
                        $points += 16.5;
                    if($picto["c"] >= 60)
                        $points += 20;
                    break;
                case "r_winbas_#00":
                    if($picto["c"] >= 2)
                        $points += 13;
                    if($picto["c"] >= 5)
                        $points += 20;
                    break;
                case "r_wintop_#00":
                    if($picto["c"] >= 1)
                        $points += 20;
                    break;
                case "small_zombie_#00":
                    if($picto["c"] >= 100)
                        $points += 3.5;
                    if($picto["c"] >= 200)
                        $points += 6.5;
                    if($picto["c"] >= 300)
                        $points += 10;
                    if($picto["c"] >= 800)
                        $points += 13;
                    break;
            }
        }

        return $points;
    }

    public function hasSkill(User $user, $skill){
        if(is_string($skill)) {
            $skill = $this->entity_manager->getRepository(HeroSkillPrototype::class)->findOneBy(['name' => $skill]);
            if($skill === null)
                return false;
        }

        $skills = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getUnlocked($user->getAllHeroDaysSpent());
        return in_array($skill, $skills);
    }

    public function hasSeenLatestChangelog(User $user, ?string $fallback_lang): bool {

        $lang = $user->getLanguage() ?? $fallback_lang ?? 'de';
        $latest_cl = $this->entity_manager->getRepository(Changelog::class)->findBy(['lang' => $lang], ['date' => 'DESC'], 1);
        if (empty($latest_cl)) return true;

        $seen_cl = $user->getLatestChangelog();
        if ($seen_cl === null) return false;

        return $latest_cl[0] === $seen_cl;
    }

    public function setSeenLatestChangelog(User $user, ?string $fallback_lang) {

        $lang = $user->getLanguage() ?? $fallback_lang ?? 'de';
        $latest_cl = $this->entity_manager->getRepository(Changelog::class)->findBy(['lang' => $lang], ['date' => 'DESC'], 1);
        if (empty($latest_cl)) return;

        $user->setLatestChangelog($latest_cl[0]);
        $this->entity_manager->persist($user);
    }

    public function deleteUser(User $user) {
        $user->setEmail("$ deleted <{$user->getId()}>")->setName("$ deleted <{$user->getId()}>")->setPassword(null)->setRightsElevation(0);
        if ($user->getAvatar()) {
            $this->entity_manager->remove($user->getAvatar());
            $user->setAvatar(null);
        }
        $citizen = $user->getActiveCitizen();
        if ($citizen) {
            $r = [];
            $this->container->get(DeathHandler::class)->kill( $citizen, CauseOfDeath::Headshot, $r );
            foreach ($r as $re) $this->entity_manager->remove($re);
        }
    }

    /**
     * Checks if the given user has specified roles. The relation type is controlled by $any. If $roles is an empty
     * array, this function will return true in AND mode and false in OR mode.
     * @param User $user User to check
     * @param array $roles Roles to check for
     * @param bool $any Set true to check if any of the given roles apply (OR); set false to check if all roles apply
     * (AND).
     * @return bool
     */
    public function hasRoles(User $user, array $roles, bool $any = false): bool {
        $effectiveRoles = $this->roles->getReachableRoleNames( $user->getRoles() );
        foreach ($roles as $role) if ($any === in_array( $role, $effectiveRoles )) return $any;
        return !$any;
    }

    /**
     * Checks if the user has a specific role.
     * @param User $user User to check
     * @param string $role Role to check for
     * @return bool True if the user has the given role; false otherwise.
     */
    public function hasRole(User $user, string $role) {
        return in_array( $role, $this->roles->getReachableRoleNames( $user->getRoles() ) );
    }

    /**
     * Returns a list of grant-able roles
     * @return string[]
     */
    public function admin_validRoles(): array {
        return ['ROLE_ORACLE', 'ROLE_CROW', 'ROLE_ADMIN', 'ROLE_SUPER'];
    }

    /**
     * Checks if a principal user can perform administrative actions on a specific user account
     * @param User $principal User to perform the administrative action
     * @param User $target User to be administered
     * @return bool
     */
    public function admin_canAdminister( User $principal, User $target ): bool {
        // Only crows and admins can administer
        if (!$this->hasRoles( $principal, ['ROLE_CROW','ROLE_ADMIN'], true )) return false;

        // Crows / Admins can administer themselves
        if ($principal === $target) return true;

        // Nobody can administer a super admin
        if ($this->hasRole( $target, 'ROLE_SUPER' )) return false;

        // Only super admins can administer admins
        if ($this->hasRole( $target, 'ROLE_ADMIN' ) && !$this->hasRole( $principal, 'ROLE_SUPER')) return false;

        // Only admins can administer crows
        if ($this->hasRole( $target, 'ROLE_CROW' ) && !$this->hasRole( $principal, 'ROLE_ADMIN')) return false;

        return true;
    }

    /**
     * Checks if the given user can grant a specific role
     * @param User $principal
     * @param string $role
     * @return bool
     */
    public function admin_canGrant( User $principal, string $role ): bool {
        // Only admins can grant roles
        if (!$this->hasRole( $principal, 'ROLE_ADMIN' )) return false;

        // Make sure only valid roles can be granted
        if (!in_array($role, $this->admin_validRoles())) return false;

        // Only super admins can grant admin role
        if ($role === 'ROLE_ADMIN' && !$this->hasRole( $principal, 'ROLE_SUPER' )) return false;

        // Super admin role can be granted by admins only if no super admin exists yet
        if ($role === 'ROLE_SUPER' &&  !$this->hasRole( $principal, 'ROLE_SUPER' ) &&
            $this->entity_manager->getRepository(User::class)->findByLeastElevationLevel(User::ROLE_SUPER)
        ) return false;

        return true;
    }

    public function setUserBaseAvatar( User $user, $payload, int $imagick_setting = self::ImageProcessingForceImagick, string $ext = null, int $x = 100, int $y = 100 ): int {

        // Processing limit: 3MB
        if (strlen( $payload ) > 3145728) return self::ErrorAvatarTooLarge;

        if ($imagick_setting === self::ImageProcessingForceImagick && !extension_loaded('imagick')) return self::ErrorAvatarBackendUnavailable;

        if (extension_loaded('imagick') && $imagick_setting !== self::ImageProcessingDisableImagick) {
            $im_image = new Imagick();
            $processed_image_data = null;

            try {
                if (!$im_image->readImageBlob($payload))
                    return self::ErrorAvatarImageBroken;

                if (!in_array($im_image->getImageFormat(), ['GIF','JPEG','BMP','PNG','WEBP']))
                    return self::ErrorAvatarFormatUnsupported;

                if ($im_image->getImageFormat() === 'GIF') {
                    $im_image->coalesceImages();
                    $im_image->resetImagePage('0x0');
                    $im_image->setFirstIterator();
                }

                $w = $im_image->getImageWidth();
                $h = $im_image->getImageHeight();

                if ($w / $h < 0.1 || $h / $w < 0.1 || $h < 16 || $w < 16)
                    return self::ErrorAvatarResolutionUnacceptable;

                if ( (max($w,$h) > 200 || min($w,$h < 90)) &&
                    !$im_image->resizeImage(
                        min(200,max(90,$w,$h)),
                        min(200,max(90,$w,$h)),
                        imagick::FILTER_SINC, 1, true )
                ) return self:: ErrorAvatarProcessingFailed;

                if ($im_image->getImageFormat() === 'GIF')
                    $im_image->setFirstIterator();

                $w_final = $im_image->getImageWidth();
                $h_final = $im_image->getImageHeight();

                switch ($im_image->getImageFormat()) {
                    case 'JPEG':
                        $im_image->setImageCompressionQuality ( 90 );
                        break;
                    case 'PNG':
                        $im_image->setOption('png:compression-level', 9);
                        break;
                    case 'GIF':
                        $im_image->setOption('optimize', true);
                        break;
                    default: break;
                }

                $processed_image_data = $im_image->getImagesBlob();
                $processed_format = strtolower( $im_image->getImageFormat() );
            } catch (Exception $e) {
                return self::ErrorAvatarProcessingFailed;
            }
        } else {
            $processed_image_data = $payload;
            $processed_format = $ext;
            $w_final = $x ?: 100;
            $h_final = $y ?: 100;
        }

        if (strlen($processed_image_data) > 1048576) return self::ErrorAvatarInsufficientCompression;

        $name = md5( $processed_image_data );
        if (!($avatar = $user->getAvatar())) {
            $avatar = new Avatar();
            $user->setAvatar($avatar);
        }

        $avatar
            ->setChanged(new DateTime())
            ->setFilename( $name )
            ->setSmallName( $name )
            ->setFormat( $processed_format )
            ->setImage( $processed_image_data )
            ->setX( $w_final )
            ->setY( $h_final )
            ->setSmallImage( null );

        return self::NoError;
    }

    public function setUserSmallAvatar( User $user, $payload = null, ?int $x = null, ?int $y = null, ?int $dx = null, ?int $dy = null ): int {
        $avatar = $user->getAvatar();

        if (!$avatar || $avatar->isClassic())
            return self::ErrorAvatarFormatUnsupported;

        // Processing limit: 3MB
        if ($payload !== null && strlen( $payload ) > 3145728) return self::ErrorAvatarTooLarge;
        if ($payload === null && !extension_loaded('imagick')) return self::ErrorAvatarBackendUnavailable;

        if ($payload === null) {
            if (
                $x < 0 || $dx < 0 || $x + $dx > $avatar->getX() ||
                $y < 0 || $dy < 0 || $y + $dy > $avatar->getY()
            ) return self::ErrorAvatarFormatUnsupported;

            $im_image = new Imagick();
            $processed_image_data = null;

            try {
                if (!$im_image->readImageBlob(stream_get_contents( $avatar->getImage() )))
                    return self::ErrorAvatarImageBroken;

                $im_image->setFirstIterator();

                if (!$im_image->cropImage( $dx, $dy, $x, $y ))
                    return self::ErrorAvatarProcessingFailed;

                $im_image->setFirstIterator();

                $iw = $im_image->getImageWidth(); $ih = $im_image->getImageHeight();
                if ($iw < 90 || $ih < 30 || ($ih/$iw != 3)) {
                    $new_height = max(30,$ih);
                    $new_width = $new_height * 3;
                    if (!$im_image->resizeImage(
                        $new_width, $new_height,
                        imagick::FILTER_SINC, 1, true ))
                        return self::ErrorAvatarProcessingFailed;
                }

                if ($im_image->getImageFormat() === 'GIF')
                    $im_image->setOption('optimize', true);

                $processed_image_data = $im_image->getImagesBlob();

            } catch (Exception $e) {
                return self::ErrorAvatarProcessingFailed;
            }
        } else
            $processed_image_data = $payload;

        if (strlen($processed_image_data) > 1048576) return self::ErrorAvatarInsufficientCompression;

        $name = md5( (new DateTime())->getTimestamp() );

        $avatar
            ->setSmallName( $name )
            ->setSmallImage( $processed_image_data );

        return self::NoError;
    }
}