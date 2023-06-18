<?php
/** @noinspection PhpRouteMissingInspection */

namespace App\Controller;

use App\Annotations\GateKeeperProfile;
use App\Entity\ExternalApp;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ExternalController
 * @package App\Controller
 * @GateKeeperProfile(allow_during_attack=true, record_user_activity=false)
 */
class ExternalController extends CustomAbstractController {

    protected function decodeUrl(?string $url): ?string {
        if ($url === null) return $url;
        while (($a = strpos( $url, '{' )) !== false && ($b = strpos( $url, '}' )) && $a < $b) {

            [$word, $options] = array_merge(explode(':', substr( $url, $a+1, $b - $a - 1 ), 2), [null]);

            $replacement = '';
            switch ($word) {
                case 'lang':
                    if (!$options) $replacement = $this->getUser()->getLanguage() ?? 'en';
                    else {
                        foreach (explode('|', $options) as $option) {
                            [$lang, $value] = array_merge(explode(';', $option, 2), [null]);
                            if ($lang === '*' || $this->getUser()->getLanguage() === $lang) {
                                $replacement = $value ?? $lang;
                                break;
                            }
                        }
                    }
                    break;
            }

            $url = substr( $url, 0, $a ) . $replacement . substr( $url, $b + 1 );
        }

        return str_replace(['{','}'], '', $url);
    }

    /**
     * @Route("/jx/disclaimer/{id<\d+>}", name="disclaimer", condition="request.isXmlHttpRequest()")
     * @param ExternalApp $app
     * @return Response
     */
    public function disclaimer(ExternalApp $app): Response {
        $user = $this->getUser();
        if (!$user || ($app->getTesting() && $app->getOwner() !== $user && !$this->isGranted('ROLE_ADMIN')))
            return $this->redirectToRoute('initial_landing');

        $key = $user->getExternalId();

        return $this->render('ajax/public/disclaimer.html.twig', [
                                                                   'ex'  => $app,
                                                                   'key' => $key,
                                                                   'url' => $this->decodeUrl( $app->getUrl() ),
                                                                   'devurl' => $this->decodeUrl( $app->getDevurl() )
                                                               ]
        );
    }

    /**
     * @Route("/jx/json_docs", name="json_docs", condition="request.isXmlHttpRequest()")
     * @return Response
     */
    public function json_documentation(): Response {
        return $this->render('ajax/public/jsonapidocs.html.twig', []);
    }

    /**
     * @Route("/jx/xml_docs", name="xml_docs", condition="request.isXmlHttpRequest()")
     * @return Response
     */
    public function xml_documentation(): Response {
        return $this->render('ajax/public/xmlapidocs.html.twig', []);
    }
}
