<?php

namespace App\Controller;

use App\Annotations\GateKeeperProfile;
use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\User;
use App\Service\JSONRequestParser;
use App\Service\RandomGenerator;
use App\Service\TimeKeeperService;
use App\Service\UserHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LandingController
 * @package App\Controller
 */
#[GateKeeperProfile(allow_during_attack: true)]
class LandingController extends CustomAbstractController
{

    /**
     * @param EntityManagerInterface $em
     * @param TimeKeeperService $tk
     * @param UserHandler $userHandler
     * @return Response
     */
    #[Route(path: 'jx/landing', name: 'initial_landing', condition: 'request.isXmlHttpRequest()')]
    public function main_landing(EntityManagerInterface $em, TimeKeeperService $tk, UserHandler $userHandler): Response
    {
        if ($tk->isDuringAttack()) {
            $response = new Response('', 302, [
                'X-AJAX-Control' => 'navigate',
                'X-AJAX-Navigate' => $this->generateUrl('maintenance_attack')
            ]);
            return $response;
        }


        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user)
            return $this->redirectToRoute('public_welcome');
        elseif (!$user->getValidated())
            return $this->redirectToRoute('public_validate');
        elseif ($user->tosBlocked())
            return $this->redirectToRoute('public_accept_tos');
        elseif ($em->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirectToRoute('soul_death');
        else {
            // The user is properly authenticated has no pending death pages to confirm
            // Check if there is some news for him to see
            if (!$userHandler->hasSeenLatestChangelog($user, $this->getUserLanguage()))
                return $this->redirectToRoute('soul_future');
            elseif ($em->getRepository(Citizen::class)->findActiveByUser($user))
                return $this->redirectToRoute('game_landing');
            elseif ($this->isGranted('ROLE_CROW') && $this->isGranted('ROLE_DUMMY'))
                return $this->redirectToRoute('forum_list');
            else
                return $this->redirectToRoute('ghost_welcome');
        }
    }

    /**
     * @param EntityManagerInterface $em
     * @param TimeKeeperService $tk
     * @param RandomGenerator $rand
     * @return Response
     */
    #[Route(path: 'jx/offline/attack_processing', name: 'maintenance_attack', condition: 'request.isXmlHttpRequest()')]
    public function maintenance_attack(EntityManagerInterface $em, TimeKeeperService $tk, RandomGenerator $rand, JSONRequestParser $parser): Response
    {
        if (!$tk->isDuringAttack() && !$parser->has('refresh'))
            return $this->redirect($this->generateUrl('initial_landing'));

        $attack_messages = [
            'de' => ['Den Kater drücken', 'Einen "Wake the Dead" trinken', 'Schlafen gehen', 'Testament schreiben', 'Einen Strick knüpfen', 'Acco verfluchen', 'Mich übers Meta-Cap aufregen', 'Einfach mal 22 Minuten warten...', 'Kopf gegen Wand', 'Hoffen, nicht einzuschlafen', 'Alles verdrängen', 'Den Raben huldigen', 'Nach Drogen suchen', 'An den Fingernägeln knabbern', 'Gliedmaßen überprüfen', 'Die Sandburg höher bauen!', 'Leise meine letzten Worte murmeln', 'In Borderlands Boten blättern', 'Wüstenfunk abhören!', 'Mir vorstellen, wie ein Järpentisch aussehen könnte', 'Acco rupfen, teeren, federn, nochmal rupfen', 'Hektisch die Patronen suchen', 'Die Bibel in Blindenschrift lesen', '28 Tage warten', 'Däumchen drehen', 'Ins Nachbarzelt krabbeln', 'Auf Dayan warten', 'Das aktuelle Leichsblatt verfassen', 'Gemütlich die Camperstellung halten', 'Auf den kreischenden Wecker starren', 'Leichsblatt lesen', 'Einen Abendspaziergang unternehmen', 'Meinen knuddeligen Kater knuddeln', 'Meine Ratte füttern', 'Ein Bild vom Weltuntergang malen', 'Ein Ka-tet gründen', 'Den scharlachroten König verfluchen!', 'Leiche eines Reisenden anknabbern', 'Am Daumen lutschen', 'Einen Järpen-Tisch schnitzen', 'Den Järpen-Tisch in der Wüste verstecken', 'In der Nase bohren und "La Paloma" singen', 'Den ekligen Hautfetzen überziehen', 'Ein Rabenkostüm anziehen', 'Leute im Kostüm erschrecken', 'In Ruhe warten...', 'Das Licht löschen', 'Plüschtier knuddeln', 'Einen Tunnel weg von der Stadt graben', 'Einen Dieb in den Fleischkäfig stecken', 'Laut singen "Einer geht noch..."', 'Dem Hund das Sprechen beibringen', 'Den Schlaf in Empfang nehmen'],
            'en' => ['Hold the door', 'Scream', 'Hide under the bed', 'Clench your cheeks', 'Wait under the sheets', 'Call for help', 'Hug a broken guitar', 'Pray for your life', 'Sing at the top of your voice, alone', 'Yell "help"', 'Curl up in the foetal position', 'Hug your rucksack', 'Grind your teeth', 'Scream and panic', 'Hide behind the wreckage', 'Hide in a box', 'Wait calmly...', 'Cry nervously', 'Wail like a banshee'],
            'es' => ['¡Tengo esposa e hijos!', '¡Nooooooo!', 'Esconderse bajo la cama', 'Apretar el puño', '¡Maaadre mía!', '¡Auxiliooo!', '¡Que se coman a mis vecinos!', '¿Por qué a mí?', '¡Me muero!', 'Llorar en posición fetal', '¡Mi carne apesta!', '¡Yo solo pasaba por aquí!', '¡Adiós mundo cruel!', '¡No se lleven a mi amigo!', '¡Me vengaré!', '¡Malditos zombies!', '¡Soy demasiado joven!', '¡Ay que miedo!', '¡Esta vez no podrán conmigo!'],
            'fr' => ['Tenir la porte', 'Hurler', 'Se cacher sous le lit', 'Serrer les fesses', 'Attendre sous les draps', 'Appeler à l\'aide', 'Se cramponner à un bout de bois', 'Prier pour sa vie', 'S\'égosiller tout seul chez soi', 'Brailler « au secours »', 'Pleurer en position foetale', 'Se cramponner à son sac à dos', 'Claquer des dents', 'Paniquer et hurler', 'Se planquer derrière des détritus', 'Ramper sous un carton', 'Attendre calmement...', 'Pleurer nerveusement', 'Vociférer comme un dément']
        ];

        $button_texts = $rand->pick($attack_messages[ $this->getUserLanguage() ?? 'en' ] ?? $attack_messages['en'], 2);

        return $this->render( 'ajax/public/maintenance_attack.html.twig', ['button_texts' => $button_texts, 'attack_running' => $tk->isDuringAttack(), 'clock' => ['attack' => 0]] );
    }


}
