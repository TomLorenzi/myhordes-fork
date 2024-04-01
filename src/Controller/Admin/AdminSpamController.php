<?php

namespace App\Controller\Admin;

use App\Annotations\AdminLogProfile;
use App\Annotations\GateKeeperProfile;
use App\Entity\AntiSpamDomains;
use App\Enum\DomainBlacklistType;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

#[Route(path: '/', condition: 'request.isXmlHttpRequest()')]
#[GateKeeperProfile(allow_during_attack: true)]
class AdminSpamController extends AdminActionController
{
    /**
     * @return Response
     */
    #[Route(path: 'jx/admin/spam/domains', name: 'admin_spam_domain_view')]
    public function spam_view(): Response
    {
        try {
            $n = $this->entity_manager->getRepository(AntiSpamDomains::class)->createQueryBuilder('a')
                ->select('count(a.id)')->where('a.type = :type',)->setParameter('type', DomainBlacklistType::EmailDomain)->getQuery()->getSingleScalarResult();
        } catch (\Throwable $e) {
            $n = 0;
        }

        return $this->render( 'ajax/admin/spam/domains.html.twig', $this->addDefaultTwigArgs(null, ['n' => $n, 'tab' => 'domains']));
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/admin/spam/ids', name: 'admin_spam_identifiers_view')]
    public function spam_view_ids(): Response
    {
        try {
            $emails = $this->entity_manager->getRepository(AntiSpamDomains::class)->createQueryBuilder('a')
                ->select('count(a.id)')->where('a.type = :type',)->setParameter('type', DomainBlacklistType::EmailAddress)->getQuery()->getSingleScalarResult();
            $ids = $this->entity_manager->getRepository(AntiSpamDomains::class)->createQueryBuilder('a')
                ->select('count(a.id)')->where('a.type = :type',)->setParameter('type', DomainBlacklistType::EternalTwinID)->getQuery()->getSingleScalarResult();
        } catch (\Throwable $e) {
            $emails = 0;
            $ids = 0;
        }

        return $this->render( 'ajax/admin/spam/ids.html.twig', $this->addDefaultTwigArgs(null, [
            'emails' => $emails,
            'etids' => $ids,
            'tab' => 'identifiers'
        ]));
    }

    /**
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'jx/admin/spam/domains/search', name: 'admin_spam_domain_search')]
    public function spam_search(JSONRequestParser $parser): Response
    {
        $query = $parser->get('query','');
        if (mb_strlen($query) < 3) $query = '';
        $results = empty($query) ? [] : $this->entity_manager->getRepository(AntiSpamDomains::class)->createQueryBuilder('a')
            ->andWhere('a.domain LIKE :val')->setParameter('val', "%{$query}%")
            ->andWhere('a.type = :type', )->setParameter('type', DomainBlacklistType::EmailDomain)
            ->getQuery()->getResult();

        return $this->render( 'ajax/admin/spam/domain_list.html.twig', ['domains' => $results]);
    }

    /**
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/admin/spam/domains/add', name: 'admin_add_spam_domain')]
    #[AdminLogProfile(enabled: true)]
    public function spam_domain_add(JSONRequestParser $parser): Response
    {
        if (!$this->isGranted('ROLE_ADMIN'))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $body = $parser->get('list', '');
        $lines = array_unique( explode( "\r\n", $body ) );

        $repo = $this->entity_manager->getRepository(AntiSpamDomains::class);

        foreach ($lines as $line) {
            if (empty($line)) continue;
            if ($line[0] === '@' || $line[0] === '.') $line = substr($line, 1);

            $this_entry = DomainBlacklistType::EmailDomain->convert( $line );
            if (!$repo->findOneBy(['domain' => $this_entry, 'type' => DomainBlacklistType::EmailDomain]))
                $this->entity_manager->persist(
                    (new AntiSpamDomains())
                        ->setDomain($this_entry)
                        ->setType(DomainBlacklistType::EmailDomain)
                );
        }

        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/admin/spam/domains/remove', name: 'admin_remove_spam_domain')]
    #[AdminLogProfile(enabled: true)]
    public function spam_domain_remove(JSONRequestParser $parser): Response
    {
        if (!$this->isGranted('ROLE_ADMIN'))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $list = $parser->get('list', []);
        if (!is_array($list) || empty($list)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $repo = $this->entity_manager->getRepository(AntiSpamDomains::class);

        foreach ($list as $id)
            if ($entity = $repo->find((int)$id))
                $this->entity_manager->remove($entity);

        $this->entity_manager->flush();

        return AjaxResponse::success();
    }
}
