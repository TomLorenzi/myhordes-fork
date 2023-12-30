<?php

namespace App\Controller\REST\User\Soul;

use App\Controller\CustomAbstractCoreController;
use App\Entity\Award;
use App\Entity\CitizenProfession;
use App\Entity\Emotes;
use App\Entity\ForumModerationSnippet;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route(path: '/rest/v1/user/soul/editor', name: 'rest_user_soul_editor_', condition: "request.headers.get('Accept') === 'application/json'")]
#[IsGranted('ROLE_USER')]
class EditorController extends CustomAbstractCoreController
{

    /**
     * @param Packages $assets
     * @return JsonResponse
     */
    #[Route(path: '', name: 'base', methods: ['GET'])]
    public function index(Packages $assets): JsonResponse {
        return new JsonResponse([
            'strings' => [
                'header' => [
                    'title' => $this->translator->trans('Titel', [], 'global'),
                    'tag' => $this->translator->trans('Tag', [], 'forum'),
                    'add_tag' => $this->translator->trans('Tag hinzufügen (optional)', [], 'global'),
                    'no_tag' => $this->translator->trans('Kein Tag', [], 'global'),
                    'version' => $this->translator->trans('Version', [], 'global'),
                    'language' => $this->translator->trans('Sprache', [], 'global'),
                ],
                'sections' => [
                    'preview' => $this->translator->trans('Vorschau', [], 'global'),
                    'message' => $this->translator->trans('Deine Nachricht', [], 'global'),
                ],
                'common' => [
                    'insert' => $this->translator->trans('Einfügen', [], 'global'),
                    'abort' => $this->translator->trans('Abbrechen', [], 'global'),
                    'ctrl' => $this->translator->trans('STRG', [], 'global'),
                    'enter' => $this->translator->trans('ENTER', [], 'global'),
                    'send' => $this->translator->trans('Absenden', [], 'global'),
                ],
                'controls' => [
                    'b' => $this->translator->trans('Fett', [], 'global'),
                    'i' => $this->translator->trans('Kursiv', [], 'global'),
                    'u' => $this->translator->trans('Unterstreichen', [], 'global'),
                    's' => $this->translator->trans('Durchstreichen', [], 'global'),
                    'c' => $this->translator->trans('Keine Formatierung', [], 'global'),
                    'big' => $this->translator->trans('Groß', [], 'global'),
                    'bad' => $this->translator->trans('Verräter', [], 'global'),
                    'link' => $this->translator->trans('Link einfügen', [], 'global'),
                    'link-url' => $this->translator->trans('Link-URL', [], 'global'),
                    'link-text' => $this->translator->trans('Link-Text', [], 'global'),
                    'image' => $this->translator->trans('Bild einfügen', [], 'global'),
                    'image-url' => $this->translator->trans('Bild-URL', [], 'global'),
                    'image-text' => $this->translator->trans('Bildtitel', [], 'global'),
                    'admannounce' => $this->translator->trans('Admin Ankündigung', [], 'global'),
                    'modannounce' => $this->translator->trans('Mod Ankündigung', [], 'global'),
                    'announce' => $this->translator->trans('Orakel Ankündigung', [], 'global'),
                    '*' => $this->translator->trans('Listenpunkt', [], 'global'),
                    '0' => $this->translator->trans('Num. Listenpunkt', [], 'global'),
                    'quote' => $this->translator->trans('Zitat', [], 'global'),
                    'spoiler' => $this->translator->trans('Spoiler', [], 'global'),
                    'aparte' => $this->translator->trans('Vertraulich', [], 'global'),
                    'glory' => $this->translator->trans('Ruhm', [], 'global'),
                    'code' => $this->translator->trans('Code', [], 'global'),
                    'hr' => $this->translator->trans('Linie', [], 'global'),
                    'rp' => $this->translator->trans('Rollenspiel', [], 'global'),
                    'collapse' => $this->translator->trans('Einklappen', [], 'global'),
                    'poll' => $this->translator->trans('Umfrage einfügen', [], 'global'),
                    '@' => $this->translator->trans('Spielernamen einfügen', [], 'global'),
                    '@-dialog' => $this->translator->trans('Spieler auswählen', [], 'global'),
                    '@-placeholder' => $this->translator->trans('Gib den Namen des Spielers ein', [], 'global'),

                    'emotes_img' => $assets->getUrl('build/images/forum/smile.gif'),
                    'games_img' => $assets->getUrl('build/images/item/item_dice.gif'),
                    'rp_img' => $assets->getUrl('build/images/forum/rp.png'),
                    'mod_img' => $assets->getUrl('build/images/assets/img/icons/mod.png'),
                ]
            ],
        ]);
    }

    /**
     * @param User $user
     * @param EntityManagerInterface $em
     * @param Packages $assets
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    #[Route(path: '/{id}/unlocks/emotes', name: 'list_emotes', methods: ['GET'])]
    public function list_emotes(
        User $user,
        EntityManagerInterface $em,
        Packages $assets,
        RoleHierarchyInterface $roles,
        TagAwareCacheInterface $gameCachePool
    ): JsonResponse {

        if ($user !== $this->getUser()) return new JsonResponse([], Response::HTTP_FORBIDDEN);

        $emotes = $gameCachePool->get("mh_app_unlocks_emotes_{$user->getId()}_{$user->getLanguage()}", function (ItemInterface $item) use ($user, $em) {

            $item->expiresAfter(360)->tag(["user-{$user->getId()}-emote-unlocks",'emote-unlocks']);

            $repo = $em->getRepository(Emotes::class);
            $emotes = $repo->getDefaultEmotes();

            $awards = $em->getRepository(Award::class)->getAwardsByUser($user);

            foreach($awards as $entry) {
                /** @var $entry Award */
                if (!$entry->getPrototype() || $entry->getPrototype()->getAssociatedTag() === null) continue;
                $emote = $repo->findByTag($entry->getPrototype()->getAssociatedTag());
                if(!in_array($emote, $emotes)) {
                    $emotes[] = $emote;
                }
            }

            $data = [];
            foreach($emotes as $entry) {
                /** @var $entry Emotes */
                if ($entry === null) continue;
                $data[$entry->getTag()] = [
                    'tag' => $entry->getTag(),
                    'path' => $entry->getI18n() ? str_replace('{lang}', $user->getLanguage() ?? 'de', $entry->getPath()) : $entry->getPath(),
                    'orderIndex' => $entry->getOrderIndex()
                ];
            }

            return $data;
        });

        $snippets = [];
        if ($this->isGranted('ROLE_ELEVATED') || $this->isGranted('ROLE_ANIMAC')) {
            $entities = $em->getRepository(ForumModerationSnippet::class)->findBy( ['role' => [...$roles->getReachableRoleNames( $user->getRoles() ), '*']] );
            foreach ($entities as $snippet) {
                $key = $snippet->getLang() === $user->getLanguage() ? "%%{$snippet->getShort()}" : "%{$snippet->getLang()}%{$snippet->getShort()}";
                $snippets[$key] = [
                    'lang' => $snippet->getLang(),
                    'key' => $key,
                    'value' => $snippet->getText(),
                    'role' => str_replace('ROLE_', '', $snippet->getRole()),
                ];
            }
        }

        return new JsonResponse([
            'result' => array_map(fn(array $a) => array_merge($a, ['url' => $assets->getUrl( $a['path'] )]), $emotes),
            'mock' => array_filter([
                ':)' => ':smile:',
                '=)' => ':smile:',
                '🙂' => ':smile:',
                '😊' => ':smile:',
                '🙃' => ':smile:',
                '🫠' => ':smile:',
                '😇' => ':smile:',
                '🥲' => ':smile:',
                '😏' => ':smile:',
                '😌' => ':smile:',
                '🤤' => ':smile:',
                '🤠' => ':smile:',
                '😎' => ':smile:',
                '🤓' => ':smile:',
                '😗' => ':smile:',
                '☺' => ':smile:',
                '😚' => ':smile:',
                '😙' => ':smile:',
                '😽' => ':smile:',
                '👶' => ':smile:',
                '🧒' => ':smile:',
                '👦' => ':smile:',
                '👧' => ':smile:',
                '🧑' => ':smile:',
                '👩' => ':smile:',
                '🧒' => ':smile:',

                ':d' => ':lol:',
                '=d' => ':lol:',
                '😀' => ':lol:',
                '😃' => ':lol:',
                '😄' => ':lol:',
                '😁' => ':lol:',
                '😆' => ':lol:',
                '😅' => ':lol:',
                '🤣' => ':lol:',
                '😂' => ':lol:',
                '😸' => ':lol:',
                '😹' => ':lol:',
                '😺' => ':lol:',

                ':(' => ':sad:',
                '=(' => ':sad:',
                '😒' => ':sad:',
                '😔' => ':sad:',
                '😟' => ':sad:',
                '🙁' => ':sad:',
                '☹' => ':sad:',
                '😕' => ':sad:',
                '🫤' => ':sad:',
                '😦' => ':sad:',
                '😥' => ':sad:',

                ';)' => ':blink:',
                '😉' => ':blink:',
                '😘' => ':blink:',
                '😜' => ':blink:',

                ':o' => ':surprise:',
                '=o' => ':surprise:',
                '😮' => ':surprise:',
                '😯' => ':surprise:',
                '😲' => ':surprise:',

                '🤔' => ':thinking:',
                '🧐' => ':thinking:',

                '😨' => ':horror:',
                '😰' => ':horror:',
                '😱' => ':horror:',
                '🙀' => ':horror:',

                '😷' => ':sick:',
                '🤒' => ':sick:',
                '🤕' => ':sick:',
                '🤢' => ':sick:',
                '🤮' => ':sick:',
                '🤧' => ':sick:',
                '🥴' => ':sick:',

                '🤐' => ':neutral:',
                '🤨' => ':neutral:',
                '😐' => ':neutral:',
                '😑' => ':neutral:',
                '😶' => ':neutral:',
                '🫥' => ':neutral:',

                '💀' => ':death:',
                '☠' => ':death:',

                '💤' => ':sleep:',
                '😪' => ':sleep:',
                '😴' => ':sleep:',

                '😡' => ':rage:',
                '😠' => ':rage:',
                '🤬' => ':rage:',

                '=>' => ':arrowright:',
            ], fn(string $v) => array_key_exists( $v, $emotes )),
            'snippets' => empty($snippets) ? null : [
                'base' => $user->getLanguage(),
                'list' => $snippets,
            ],
        ]);
    }

    /**
     * @param User $user
     * @param Packages $assets
     * @return JsonResponse
     */
    #[Route(path: '/{id}/unlocks/games', name: 'list_games', methods: ['GET'])]
    public function list_games(
        User $user,
        Packages $assets
    ): JsonResponse {

        if ($user !== $this->getUser()) return new JsonResponse([], Response::HTTP_FORBIDDEN);

        $data = [
            'd4' => 'dice4',
            'd6' => 'dice6',
            'd8' => 'dice8',
            'd10' => 'dice10',
            'd12' => 'dice12',
            'd20' => 'dice20',
            'd100' => 'dice100',
            'letter' => 'lta',
            'consonant' => 'ltc',
            'rps' => 'rps',
            'coin' => 'coin',
            'card' => 'card',
        ];

        return new JsonResponse([
            'result' => array_map( fn(string $k, string $v, int $o) => [
                'tag' => '{' . $k . '}',
                'path' => "build/images/forum/{$v}.png",
                'url' => $assets->getUrl( "build/images/forum/{$v}.png" ),
                'orderIndex' => $o
            ], array_keys($data), array_values($data), array_keys(array_values($data)) )
        ]);

    }

    /**
     * @param User $user
     * @param EntityManagerInterface $em
     * @param Packages $assets
     * @return JsonResponse
     */
    #[Route(path: '/{id}/unlocks/rp', name: 'list_rp', methods: ['GET'])]
    public function list_rp(
        User $user,
        EntityManagerInterface $em,
        Packages $assets
    ): JsonResponse {

        if ($user !== $this->getUser()) return new JsonResponse([], Response::HTTP_FORBIDDEN);

        $professions = $em->getRepository(CitizenProfession::class)->findAll();

        $result = [];
        foreach ($professions as $profession)
            $result[] = [
                'tag' => '{citizen,' . $profession->getName() . '}',
                'path' => "/build/images/professions/{$profession->getIcon()}.gif",
                'url' => $assets->getUrl( "build/images/professions/{$profession->getIcon()}.gif" ),
                'orderIndex' => $profession->getName() === 'none' ? 9999 : (100 + $profession->getId())
            ];

        $data = [
            '' => 'icons/small_human',
            'hero' => 'professions/hero',
            'dead' => 'professions/death',
            'shunned' => 'icons/banished',
        ];

        return new JsonResponse([
            'result' => array_merge($result, array_map( fn(string $k, string $v, int $o) => [
                'tag' => $k === '' ? '{einwohner}' :  "{einwohner,$k}",
                'path' => "build/images/{$v}.gif",
                'url' => $assets->getUrl( "build/images/{$v}.gif" ),
                'orderIndex' => $o
            ], array_keys($data), array_values($data), array_keys(array_values($data)) ))
        ]);

    }

}
