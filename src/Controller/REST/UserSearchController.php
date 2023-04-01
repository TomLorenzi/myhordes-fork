<?php

namespace App\Controller\REST;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\BuildingPrototype;
use App\Entity\Citizen;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRankingProxy;
use App\Entity\TownClass;
use App\Entity\TwinoidImportPreview;
use App\Entity\User;
use App\Enum\UserAccountType;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
use App\Service\GameFactory;
use App\Service\GameProfilerService;
use App\Service\JSONRequestParser;
use App\Service\TownHandler;
use App\Service\UserHandler;
use App\Structures\EventConf;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/rest/v1/user-search", name="rest_user_search_", condition="request.headers.get('Accept') === 'application/json'")
 * @IsGranted("ROLE_USER")
 * @GateKeeperProfile("skip")
 */
class UserSearchController extends CustomAbstractCoreController
{

    private function build_search_skip_list( JSONRequestParser $parser ): array {
        $user = $this->getUser();

        $searchSkip = $parser->get_array('exclude', []);

        if (!$parser->get_int('withSelf', 0))
            $searchSkip[] = $user->getId();

        if (!$parser->get_int('withFriends', 1))
            $searchSkip = array_merge($searchSkip, array_map( fn(User $u) => $u->getId(), $user->getFriends()->getValues() ), [$user->getId()]);

        return array_unique( $searchSkip );
    }

    private function renderUser(User $u, ?string $alias = null): array {
        return [
            'type' => 'user',
            'id' => $u->getId(),
            'name' => $alias ?? $u->getName(),
            'soul' => $this->generateUrl( 'soul_visit', ['id' => $u->getId()] ),
            'avatarHTML' => $this->render( 'ajax/soul/playeravatar.html.twig', ['user' => $u, 'small' => true, 'attributes' => ['style' => 'margin-right: 0']])->getContent(),
            'avatarHTMLLarge' => $this->render( 'ajax/soul/playeravatar.html.twig', ['user' => $u, 'small' => false, 'attributes' => ['style' => 'margin-right: 0']])->getContent(),
        ];
    }

    /**
     * @param User[] $users
     * @return array
     */
    private function renderUsers(array $users): array {
        return [
            'type' => 'group',
            'id' => -1,
            'name' => $this->translator->trans('Gruppe aus {num} Spielern', ['num' => count($users)], 'global'),
            'members' => array_map( fn(User $u) => $this->renderUser( $u ), $users )
        ];
    }

    /**
     * @Route("/find", name="find", methods={"POST"})
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @return JsonResponse
     */
    public function find(EntityManagerInterface $em, JSONRequestParser $parser): JsonResponse {
        if (!$parser->has('name', true))
            return new JsonResponse([], Response::HTTP_UNPROCESSABLE_ENTITY);

        $searchName = $parser->get('name', '');
        if ( mb_strlen($searchName) < 3 ) return new JsonResponse([], Response::HTTP_UNPROCESSABLE_ENTITY);

        $searchSkip = $this->build_search_skip_list( $parser );

        $filters = match ( $parser->trimmed('context') ) {
            'forum-search' => UserAccountType::usable(),
            default => true
        };

        $limit = $parser->get_int('limit', -1);
        if ($limit === 0) return new JsonResponse([], Response::HTTP_UNPROCESSABLE_ENTITY);

        $users = $em->getRepository(User::class)->findBySoulSearchQuery($searchName, $parser->get_int('limit', -1), $searchSkip, $filters);

        $aliased_users = [];
        if ($parser->get_int('alias', false) && $town = $this->getUser()->getActiveCitizen()?->getTown())
            foreach ($town->getCitizens() as $citizen)
                if ($citizen->getAlias() && !in_array( $citizen->getUser()->getId(), $searchSkip ) && !in_array( $citizen->getUser(), $users ) && str_contains( mb_strtolower( $citizen->getAlias() ), mb_strtolower( $searchName ) ) )
                    $aliased_users[] = $citizen;

        return new JsonResponse(
            array_merge(
                array_map( fn(Citizen $u) => $this->renderUser($u->getUser(), $u->getAlias()), $aliased_users ),
                array_map( fn(User $u) => $this->renderUser($u), $users )
            ),
        );
    }

    /**
     * @Route("/findList", name="findList", methods={"POST"})
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @return JsonResponse
     */
    public function findList(EntityManagerInterface $em, JSONRequestParser $parser): JsonResponse {
        if (!$parser->has('names', true))
            return new JsonResponse([], Response::HTTP_UNPROCESSABLE_ENTITY);

        $searchSkip = $this->build_search_skip_list( $parser );

        $filters = match ( $parser->trimmed('context') ) {
            'forum-search' => UserAccountType::usable(),
            default => true
        };

        $limit = $parser->get_int('limit', -1);
        if ($limit === 0) return new JsonResponse([], Response::HTTP_UNPROCESSABLE_ENTITY);

        $searchNames = $parser->get_array( 'names', [] );
        $users = [];
        foreach ($searchNames as $searchName) {
            $trimmed = trim($searchName);
            $r = mb_strlen($trimmed) >= 3 ? $em->getRepository(User::class)->findOneByNameOrDisplayName($trimmed, $filters) : null;
            if ($r && !in_array($r->getId(), $searchSkip)) $users[] = $r;
        }

        return new JsonResponse(
            [ $this->renderUsers( $users ) ]
        );
    }
}
