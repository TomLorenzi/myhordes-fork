<?php

namespace App\Controller\REST\Admin;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\AttackSchedule;
use App\Entity\Avatar;
use App\Entity\Citizen;
use App\Entity\OfficialGroup;
use App\Entity\OfficialGroupMessageLink;
use App\Entity\ServerSettings;
use App\Entity\User;
use App\Entity\UserGroupAssociation;
use App\Entity\UserSwapPivot;
use App\Enum\ServerSetting;
use App\Enum\UserAccountType;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\PermissionHandler;
use App\Structures\MyHordesConf;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/rest/v1/admin/perch', name: 'rest_admin_crow_management_', condition: "request.headers.get('Accept') === 'application/json'")]
#[IsGranted('ROLE_ADMIN')]
#[GateKeeperProfile('skip')]
class CrowManagementController extends CustomAbstractCoreController
{
    /**
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @return JsonResponse
     * @throws \Exception
     */
    #[Route(path: '', name: 'put', methods: ['PUT'])]
    public function create(
        EntityManagerInterface $em,
        JSONRequestParser $parser,
        UserPasswordHasherInterface $passwordEncoder,
        KernelInterface $kernel,
        PermissionHandler $perm,
        RouterInterface $router
    ): JsonResponse {
        if (!$parser->has_all(['prefix','name'], not_empty: true))
            return new JsonResponse([], Response::HTTP_UNPROCESSABLE_ENTITY);

        $prefix = $parser->trimmed('prefix', null, ['Corvus', 'Corbilla']);
        $name = $parser->trimmed('name');
        $user_id = $parser->get_int('user', null);
        $langs = $parser->get_array( 'lang' );

        if (!$prefix || mb_strlen($name) < 3 || mb_strlen($name) > 16)
            return new JsonResponse([], Response::HTTP_UNPROCESSABLE_ENTITY);

        if (!empty($user_id)) {
            /** @var User $user */
            $user = $em->getRepository(User::class)->find($user_id);
            if (!$user || $user->isDisabled() || $user->getRightsElevation() > 0 )
                return new JsonResponse([], Response::HTTP_NOT_ACCEPTABLE);
        } else $user = null;

        // Get existing crow users
        $all_crows_mails = $em->getRepository(User::class)->createQueryBuilder('u')
            ->select('u.email')
            ->where('u.email LIKE :crow_pattern')->setParameter('crow_pattern', "p__.crow@localhost")
            ->getQuery()
            ->getSingleColumnResult();

        // Get unused email address
        $i = 1;
        $mail = "";
        while (in_array( $mail = 'p' . str_pad( "$i", 2, "0", STR_PAD_LEFT ) . '.crow@localhost', $all_crows_mails ))
            $i++;

        $avatar_data = file_get_contents("{$kernel->getProjectDir()}/assets/img/forum/crow/crow.png");
        $avatar_small_data = file_get_contents("{$kernel->getProjectDir()}/assets/img/forum/crow/crow.small.png");

        $name = "$prefix $name";

        // Create crow user
        $crow = (new User)
            ->setName(substr( $name, 0, 16 ))
            ->setDisplayName($name)
            ->setEmail($mail)
            ->setTosver(1)
            ->setValidated(true)
            ->setRightsElevation(User::USER_LEVEL_CROW)
            ->setAvatar( (new Avatar())
                ->setChanged(new \DateTime())
                ->setFilename( md5( $avatar_data ) )
                ->setSmallName( md5( $avatar_small_data ) )
                ->setFormat( 'png' )
                ->setImage( $avatar_data )
                ->setSmallImage( $avatar_small_data )
                ->setX( 100 )
                ->setY( 100 )
            );

        $crow->setPassword($passwordEncoder->hashPassword($crow, bin2hex(random_bytes(16))));
        $em->persist($crow);

        // Assign crow as alt account to user
        if ($user)
            $em->persist( (new UserSwapPivot())->setPrincipal( $user )->setSecondary( $crow ));

        // Add crow to their nests
        foreach ($langs as $lang) {
            $group_meta = $em->getRepository(OfficialGroup::class)->findOneBy(['lang' => $lang, 'semantic' => OfficialGroup::SEMANTIC_MODERATION]);
            if (!$group_meta) continue;

            $base_group = $group_meta->getUsergroup();

            $perm->associate( $crow, $base_group, UserGroupAssociation::GroupAssociationTypeOfficialGroupMember );
            $messages = array_map(fn(OfficialGroupMessageLink $m) => $m->getMessageGroup(), $em->getRepository(OfficialGroupMessageLink::class)->findBy(['officialGroup' => $group_meta]));
            foreach ($messages as $message)
                $perm->associate( $crow, $message, UserGroupAssociation::GroupAssociationTypeOfficialGroupMessageMember )
                    ->setRef1( $message->getRef2() < (time() - 5184000) ? $message->getRef1() : 0  )->setRef2( 0 );

            if ($user) {
                $perm->associate( $user, $base_group, UserGroupAssociation::GroupAssociationTypeOfficialGroupMember );
                foreach ($messages as $message)
                    if (!$perm->userInGroup($user, $message))
                        $perm->associate( $user, $message, UserGroupAssociation::GroupAssociationTypeOfficialGroupMessageMember )
                            ->setRef1( $message->getRef2() < (time() - 5184000) ? $message->getRef1() : 0  )->setRef2( 0 );
            }

        }

        $em->flush();

        return new JsonResponse([
            'success' => true,
            'url' => $router->generate( 'admin_users_account_view', ['id' => $crow->getId()] )
        ]);
    }
}
