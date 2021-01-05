<?php

namespace App\Controller\Admin;

use App\Entity\AntiSpamDomains;
use App\Entity\Changelog;
use App\Entity\User;
use App\Entity\UserPendingValidation;
use App\Response\AjaxResponse;
use App\Service\AdminActionHandler;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 */
class AdminSpamController extends AdminActionController
{
    /**
     * @Route("jx/admin/spam/domains", name="admin_spam_domain_view")
     * @return Response
     */
    public function spam_view(): Response
    {
        $n = $this->entity_manager->getRepository(AntiSpamDomains::class)->createQueryBuilder('a')
            ->select('count(a.id)')->getQuery()->getSingleScalarResult();

        return $this->render( 'ajax/admin/spam/domains.html.twig', $this->addDefaultTwigArgs(null, ['n' => $n]));
    }

    /**
     * @Route("jx/admin/spam/domains/search", name="admin_spam_domain_search")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function spam_search(JSONRequestParser $parser): Response
    {
        $query = $parser->get('query','');
        if (mb_strlen($query) < 3) $query = '';
        $results = empty($query) ? [] : $this->entity_manager->getRepository(AntiSpamDomains::class)->createQueryBuilder('a')
            ->andWhere('a.domain LIKE :val')->setParameter('val', "%{$query}%")
            ->getQuery()->getResult();

        return $this->render( 'ajax/admin/spam/domain_list.html.twig', ['domains' => $results]);
    }

    /**
     * @Route("api/admin/spam/domains/add", name="admin_add_spam_domain")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function spam_domain_add(JSONRequestParser $parser): Response
    {
        if (!$this->isGranted('ROLE_ADMIN'))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $body = $parser->get('list', '');
        $separator = "\r\n";
        $line = strtok($body, $separator);

        $repo = $this->entity_manager->getRepository(AntiSpamDomains::class);

        while ($line !== false) {

            if (empty($line)) continue;
            if ($line[0] === '@' || $line[0] === '.') $line = substr($line, 1);

            if (!$repo->findOneBy(['domain' => $line]))
                $this->entity_manager->persist((new AntiSpamDomains())->setDomain($line));


            $line = strtok( $separator );
        }

        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @Route("api/admin/spam/domains/remove", name="admin_remove_spam_domain")
     * @param JSONRequestParser $parser
     * @return Response
     */
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
