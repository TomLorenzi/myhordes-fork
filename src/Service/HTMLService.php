<?php

namespace App\Service;

use App\Entity\Award;
use App\Entity\Citizen;
use App\Entity\Emotes;
use App\Entity\ForumUsagePermissions;
use App\Entity\Post;
use App\Entity\Town;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Symfony\Component\Asset\Packages;
use Symfony\Contracts\Translation\TranslatorInterface;

class HTMLService {

    private EntityManagerInterface $entity_manager;
    private PermissionHandler $perm;
    private TranslatorInterface $translator;
    private RandomGenerator $rand;
    private Packages $asset;

    const ModulationNone    = 0;
    const ModulationDrunk   = 1 << 1;
    const ModulationTerror  = 1 << 2;
    const ModulationHead    = 1 << 3;


    public function __construct(EntityManagerInterface $em, PermissionHandler $perm, TranslatorInterface $trans, RandomGenerator $rand, Packages $a)
    {
        $this->entity_manager = $em;
        $this->perm = $perm;
        $this->translator = $trans;
        $this->rand = $rand;
        $this->asset = $a;
    }

    protected const HTML_LIB = [
        'tags' => [
            'core' => [
                'br' => [],
                'b' => [],
                'strong' => [],
                'i' => [],
                'em' => [],
                'u' => [],
                'del' => [],
                'strike' => [],
                's' => [],
                'q' => [],
                'p'  => [],
            ],
            'core_rp' => [
                'div' => [ 'class' ],
            ],
            'extended' => [
                'blockquote' => [],
                'pre' => [],
                'hr' => [],
                'ul' => [],
                'ol' => [],
                'li' => [],
                'div' => [ 'class', 'x-a', 'x-b' ],
                'span' => [ 'class' ],
                'a' => [ 'href', 'title' ],
                'figure' => [ 'style' ],
            ],
            'oracle' => [],
            'crow' => [],
            'admin' => [
                'img' => [ 'alt', 'src', 'title']
            ]
        ],
        'attribs' => [
            'core' => [],
            'core_rp' => [
                'div.class' => [
                    'dice-4', 'dice-6', 'dice-8', 'dice-10', 'dice-12', 'dice-20', 'dice-100',
                    'letter-a', 'letter-v', 'letter-c',
                    'rps', 'coin', 'card'
                ],
            ],
            'extended' => [
                'div.class' => [
                    'clear',
                    'glory', 'spoiler', 'sideNote',
                    'dice-4', 'dice-6', 'dice-8', 'dice-10', 'dice-12', 'dice-20', 'dice-100',
                    'letter-a', 'letter-v', 'letter-c',
                    'rps', 'coin', 'card',
                    'citizen', 'rpText',
                ],
                'span.class' => [
                    'quoteauthor','bad','big','rpauthor','inline-code',
                ]
            ],
            'oracle' => [
                'div.class' => [
                    'oracleAnnounce'
                ]
            ],
            'crow' => [
                'div.class' => [
                    'modAnnounce', 'html'
                ]
            ],
            'admin' => [
                'div.class' => [
                    'adminAnnounce',
                ]
            ]
        ]
    ];

    protected const HTML_IMMUTABLE = [
        'img.*' => true,
        '*.class' => [
            'clear', 'dice-4', 'dice-6', 'dice-8', 'dice-10', 'dice-12', 'dice-20', 'dice-100',
            'letter-a', 'letter-v', 'letter-c', 'rps', 'coin', 'card', 'citizen', 'html',
            'oracleAnnounce', 'modAnnounce', 'adminAnnounce'
        ]
    ];

    protected function getAllowedHTML(int $permissions, bool $extended = true, array $all_ext = []): array {
        $mods_enabled = ['core'];
        if ($extended) $mods_enabled[] = 'extended';
        $mods_enabled = array_merge($mods_enabled, $all_ext);

        if ($this->perm->isPermitted($permissions, ForumUsagePermissions::PermissionFormattingOracle))
            $mods_enabled[] = 'oracle';
        if ($this->perm->isPermitted($permissions, ForumUsagePermissions::PermissionFormattingModerator))
            $mods_enabled[] = 'crow';
        if ($this->perm->isPermitted($permissions, ForumUsagePermissions::PermissionFormattingAdmin))
            $mods_enabled[] = 'admin';

        $r = [];
        $a = [];

        $html_extension = function(array &$base, string $tag, array $list) {
            if (isset($base[$tag]))
                $base[$tag] = array_merge($base[$tag], $list);
            else
                $base[$tag] = $list;
        };

        foreach ($mods_enabled as $mod) {
            if (isset(self::HTML_LIB['tags'][$mod] )) foreach (self::HTML_LIB['tags'][$mod] as $k => $v) $html_extension($r,$k,$v);
            if (isset(self::HTML_LIB['attribs'][$mod] )) foreach (self::HTML_LIB['attribs'][$mod] as $k => $v) $html_extension($a,$k,$v);
        }

        return ['nodes' => $r, 'attribs' => $a];
    }

    protected function htmlValidator( array $allowedNodes, ?DOMNode $node, ?int &$text_length = null, int $depth = 0 ): bool {
        if (!$node || $depth > 32) return false;
        if ($text_length === null) $text_length = 0;

        if ($node->nodeType === XML_ELEMENT_NODE) {

            // Element not allowed.
            if (!in_array($node->nodeName, array_keys($allowedNodes['nodes'])) && !($depth === 0 && $node->nodeName === 'body')) {
                $node->parentNode->removeChild( $node );
                return true;
            }

            // Attributes not allowed.
            $remove_attribs = [];
            for ($i = 0; $i < $node->attributes->length; $i++) {
                if (!in_array($node->attributes->item($i)->nodeName, $allowedNodes['nodes'][$node->nodeName]))
                    $remove_attribs[] = $node->attributes->item($i)->nodeName;
                elseif (isset($allowedNodes['attribs']["{$node->nodeName}.{$node->attributes->item($i)->nodeName}"])) {
                    // Attribute values not allowed
                    $allowed_entries = $allowedNodes['attribs']["{$node->nodeName}.{$node->attributes->item($i)->nodeName}"];
                    $node->attributes->item($i)->nodeValue = implode( ' ', array_filter( explode(' ', $node->attributes->item($i)->nodeValue), function (string $s) use ($allowed_entries) {
                        return in_array( $s, $allowed_entries );
                    }));
                }
            }

            foreach ($remove_attribs as $attrib)
                $node->removeAttribute($attrib);

            $children = [];
            foreach ( $node->childNodes as $child )
                $children[] = $child;

            foreach ( $children as $child )
                if (!$this->htmlValidator( $allowedNodes, $child, $text_length, $depth+1 ))
                    return false;

            return true;

        } elseif ($node->nodeType === XML_TEXT_NODE) {
            $text_length += mb_strlen($node->textContent);
            return true;
        }
        else return false;
    }

    /**
     * @param User $user
     * @param int $permissions
     * @param bool|array $extended
     * @param string $text
     * @param Town|null $town
     * @param int|null $tx_len
     * @param bool|null $editable
     * @return bool
     */
    public function htmlPrepare(User $user, int $permissions, $extended, string &$text, ?Town $town = null, ?int &$tx_len = null, ?bool &$editable = null): bool {

        $editable = true;

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML( "<html lang=''><head><title></title><meta charset='UTF-8' /></head><body>$text</body></html>", LIBXML_COMPACT | LIBXML_NONET | LIBXML_HTML_NOIMPLIED);
        $body = $dom->getElementsByTagName('body');
        if (!$body || $body->length > 1) return false;

        if (!$this->htmlValidator($this->getAllowedHTML($permissions,is_bool($extended) ? $extended : false, is_array($extended) ? $extended : []), $body->item(0),$tx_len))
            return false;

        $emotes = array_keys($this->get_emotes());

        $cache = [ 'citizen' => [] ];

        $handlers = [
            // This invalidates emote tags within code blocks to prevent them from being replaced when rendering the
            // post
            '//pre|//span[@class=\'inline-code\']' =>
                function (DOMNode $d) use(&$emotes) {
                    foreach ($emotes as $emote)
                        $d->nodeValue = str_replace( $emote, str_replace(':', ':​', $emote),  $d->nodeValue);
                },

            '//div[@class=\'dice-4\']'   => function (DOMNode $d) use(&$editable) { $editable = false; $d->nodeValue = mt_rand(1,4); },
            '//div[@class=\'dice-6\']'   => function (DOMNode $d) use(&$editable) { $editable = false; $d->nodeValue = mt_rand(1,6); },
            '//div[@class=\'dice-8\']'   => function (DOMNode $d) use(&$editable) { $editable = false; $d->nodeValue = mt_rand(1,8); },
            '//div[@class=\'dice-10\']'  => function (DOMNode $d) use(&$editable) { $editable = false; $d->nodeValue = mt_rand(1,10); },
            '//div[@class=\'dice-12\']'  => function (DOMNode $d) use(&$editable) { $editable = false; $d->nodeValue = mt_rand(1,12); },
            '//div[@class=\'dice-20\']'  => function (DOMNode $d) use(&$editable) { $editable = false; $d->nodeValue = mt_rand(1,20); },
            '//div[@class=\'dice-100\']' => function (DOMNode $d) use(&$editable) { $editable = false; $d->nodeValue = mt_rand(1,100); },
            '//div[@class=\'letter-a\']' => function (DOMNode $d) use(&$editable) { $editable = false; $l = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'; $d->nodeValue = $l[mt_rand(0,strlen($l)-1)]; },
            '//div[@class=\'letter-c\']' => function (DOMNode $d) use(&$editable) { $editable = false; $l = 'BCDFGHJKLMNPQRSTVWXZ'; $d->nodeValue = $l[mt_rand(0,strlen($l)-1)]; },
            '//div[@class=\'letter-v\']' => function (DOMNode $d) use(&$editable) { $editable = false; $l = 'AEIOUY'; $d->nodeValue = $l[mt_rand(0,strlen($l)-1)]; },
            '//div[@class=\'rps\']'      => function (DOMNode $d) use(&$editable) { $editable = false; $d->nodeValue = $this->rand->pick([$this->translator->trans('Schere',[],'global'),$this->translator->trans('Stein',[],'global'),$this->translator->trans('Papier',[],'global')]); },
            '//div[@class=\'coin\']'     => function (DOMNode $d) use(&$editable) { $editable = false; $d->nodeValue = $this->rand->pick([$this->translator->trans('Kopf',[],'global'),$this->translator->trans('Zahl',[],'global')]); },
            '//div[@class=\'card\']'     => function (DOMNode $d) use(&$editable) { $editable = false;
                $s_color = $this->rand->pick([$this->translator->trans('Kreuz',[],'items'),$this->translator->trans('Pik',[],'items'),$this->translator->trans('Herz',[],'items'),$this->translator->trans('Karo',[],'items')]);
                $value = mt_rand(1,12);
                $s_value = $value < 9 ? ('' . ($value+2)) : [$this->translator->trans('Bube',[],'items'),$this->translator->trans('Dame',[],'items'),$this->translator->trans('König',[],'items'),$this->translator->trans('Ass',[],'items')][$value-9];
                $d->nodeValue = $this->translator->trans('{color} {value}', ['{color}' => $s_color, '{value}' => $s_value], 'global');
            },
            '//div[@class=\'citizen\']'   => function (DOMNode $d) use ($user,$town,&$cache,&$editable) {
                $editable = false;
                $profession = $d->attributes->getNamedItem('x-a') ? $d->attributes->getNamedItem('x-a')->nodeValue : null;
                if ($profession === 'any') $profession = null;
                $group      = is_numeric($d->attributes->getNamedItem('x-b')->nodeValue) ? (int)$d->attributes->getNamedItem('x-b')->nodeValue : null;

                if ($town === null) {
                    $d->nodeValue = '???';
                    return;
                }

                if ($group === null || $group <= 0) $group = null;
                elseif (!isset( $cache['citizen'][$group] )) $cache['citizen'][$group] = null;

                $valid = array_filter( $town->getCitizens()->getValues(), function(Citizen $c) use ($profession,$group,&$cache) {
                    if (!$c->getAlive() && ($profession !== 'dead')) return false;
                    if ( $c->getAlive() && ($profession === 'dead')) return false;

                    if ($profession !== null && $profession !== 'dead') {
                        if ($profession === 'hero') {
                            if (!$c->getProfession()->getHeroic()) return false;
                        } elseif ($c->getProfession()->getName() !== $profession) return false;
                    }

                    if ($group !== null) {
                        if ($cache['citizen'][$group] !== null && $c->getId() !== $cache['citizen'][$group]) return false;
                        if ($c->getId() === $cache['citizen'][$group]) return true;
                        if (in_array($c->getId(),$cache['citizen'])) return false;
                    }

                    return true;
                } );

                if (!$valid) {
                    $d->nodeValue = '???';
                    return;
                }

                /** @var Citizen $cc */
                $cc = $this->rand->pick($valid);
                if ($group !== null) $cache['citizen'][$group] = $cc->getId();
                $d->nodeValue = $cc->getUser()->getName();
            },

            // This MUST be the last element!
            '//div[@class=\'html\']'   => function (DOMNode $d) {
                while ($d->hasChildNodes())
                    $d->parentNode->insertBefore($d->firstChild,$d);
                $d->parentNode->removeChild($d);
            },
        ];

        foreach ($handlers as $query => $handler)
            foreach ( (new DOMXPath($dom))->query($query, $body->item(0)) as $node ) {
                /** @var DOMNode $node */
                $p = $node->parentNode;
                $in_html = false;

                while ($p) {
                    if ($attribs = $p->attributes) {
                        $class_attrib = $attribs->getNamedItem('class');
                        if (in_array( 'html', $class_attrib ? explode( ' ', $class_attrib->nodeValue ) : [] )) {
                            $in_html = true;
                            break;
                        }
                    }

                    $p = $p->parentNode;
                }

                if (!$in_html) $handler($node);
            }


        $tmp_str = "";
        foreach ($body->item(0)->childNodes as $child)
            $tmp_str .= $dom->saveHTML($child);

        $tmp_str = $this->filterLockedEmotes($user, $tmp_str);
        $text = $tmp_str;

        return true;
    }

    protected const MODULATION_LIST = [
        self::ModulationDrunk => [
            'de' => ["äähh.. ",".. nicht?.. oder?",".d..di...dings...","...","...ee..",".. hey warte..","so voll...","... weisst du, weil...",".. und ich...","..häh?..","äh... dort",".. das meine ich...",".. nein...","... glaub dass....dumm anmachen!?. ..",".. krass halt.. weisst schon..... na und!...","..voll der Saustall...",".. ein ähh...",".. aber... ääh..","... HICKS...","und paaam!...",".. alles klar...","... weisst schon wie ich mein'....weisscho...",".. dingsbums..","...e sagen?... ...","hmmmm...","......glaub","... dieses Teil.. ","..oder... ","... dieses ding da ..","...siehste!",".gghhh...","... gl...Ll..aub dass... ahd...","... noch schlimmer..","... weisst..tt... t du, weil...","...auch nicht..."],
            'en' => [".. a ahh..",".. best actor in Hollywood? Easy. Steven Seagal...",".. burp...",".. dammit...",".. dump..",".. erm yeah... so yeah...",".. in the end.. you see...",".. no ..well well...wait.",".. no?.. eh?",".. so I...",".. thingummy...",".. whatsherface...",".. yeah dude...",".. you have pretty eyes...","... b'cause y'know..","... banjo..","... but don't you remember I said I was allergic to rabbits ...","... doofer..","... erm...","... I believe the plural is peniii...","... i believe...","... now lookee heere...","... the...","... thingy..","... whatsitsname....","... 2 Scotsmen, 2 Englishmen and a Canadian walk into a bar.","... beep, cleep, chimney...","... I didn't fall, there's just more gravity over here...","... well well....",".. have you met Epoq?",".. Heehee...",".. now then...",".. or ...",".. stranger and strangerer...",".. you see, he was standing ON the giraffe...",".t..T..Thing is...","an' emmm..","and eh... there were Jagerbombs...","and euuh..","arf....","beer o'clock...","dump..","HIC...","in like...","meh...","nah?.. huh?","no...","there it is... what?","what the...","y'know...","you and me. outside...","...that's numberwang!...",".. that thing there...",".. meh...  ",".... what already.....","... yep...",".. erm yeah... so yeah..."],
            'es' => ['arff...', '...¿qui-quién?...', 'que me llevaaa...', 'euh...', '... shhhh...', '... pues essso...', 'oye...', '¿quién eres?', 'y uuuh...', '... on-toy......', '... cosssa..', '... porque...', '...yo mismo...soy', '...y ent-tonces...', '... espera..', 'no...', '... diablos...', '...¡ji!..¡buaaaa!...'],
            'fr' => ["mais... euh..","...","... truc..","et euuh..",".. ouais les gars...","..hin hin...","voilà quoi...","... comment déjà...","..hein ?",".. un euh...","..Hi hi hi ! ...",".. bordel..","non ?.. Oui ?","..ou bien...",".. pis alors...","... HIPS...","... j'crois...",".. Hé hé...",",pfff...","genre..","... parce que tu vois...","... j'crois...",".t.. T.. Truc...",".. et euh... j'disais quoi...","arf.","euh...","... ou pas quoi...",".. non attend...","... ouais...",".. Qu'est-ce que...","... parceque..","... le...","euh... j'disais quoi...","huh...","..le bazar quoi...","style...",".gghhh...",".. mais... euh..","... machin..","truc","... et euuh..","..tu vois..","style...","... j'crois...",".. voilà quoi...",".. bidule...","... comment déjà..","..hein ?..",".. bordel..","... pis alors...","HIPS","huh...","... le bazar, là..","...genre...",".. le truc, là..",".. un euh ..."],
        ],
        self::ModulationTerror => [
            'de' => [],
            'en' => [[".. AH AH AH! ...",".. who's there?..","... but seriously, onions? wheeeee! ....","... My sainted trousers!","... oooh! look! shiny!!! ...","..as the bishop said to the nun, what?","..Hehehehehe! ...","..is underestimating the sneakiness..","..Leave me! ..,","..my giddy aunt..","..ouch! pointy...","..the.. voices...","..they are everywhere...",".gghhh...","exploding trousers ..","By the beard of Zeus! Ah!","bzzz, bzzzz, listen","NOOOoo!","so much more room for activities!","that's what she said..","the spiders...","alright stop, collaborate and listen...","the great unknown…","tiddly bang bang",".. Meuuuhh.. What?","....Surprise","... laugh .."],["atrocity","crazy","dandleban","decay","Father Christmas","filthy","fistlebars","flower","giant carrot","groan...","horrible","how rude","jabberwocky","mumble...","plane","poisonous monkeys","potato","redrum...","shovel","smugly","table","tomato","ultra-banana","yingiebert","Zinglebert Wangledack","zombie","banana"]],
            'es' => [['... ¡Dejadme!...', '... ¡JA JA JA!...', '... rrRRR... ¡RrAAAAaah!....', '.. voces...', '¡looca looca looca! ¡ah-ah!', '... frío.., ¡silencio!...follón', '¡NoOoOo!...', '... ¿quién está ahi?...', '...¡jiii jiji!...', 'Mounstro'],['patito', 'avión', 'abuela', 'vecino', 'pirulos', 'inmundo']],
            'fr' => [["… ou bien…",".gghhh...","..hin hin...","non ?.. Oui ?",",mon ami Pierroooot","... toujours un beau temps au nord ...","grogne...",".. Ah... Ah ah..","..rrRRR... RAAAAaah !","..froid..",".. qui est là ?..",". Pas du tout..",".. Alouetteuuh gentiiil.. Hein ?..","...ricane..",",.r.tuer...","... Mon beau sapiiin ! ....","..les.. voix...","pirouette cacahuètes ...","ainsi font font FONT !! Ah !...","..ils sont partout...","..vais tous vous...",".. roule petit patapon ...",".. AH AH AH !…",".. Laissez moi !","NOOOoon !...","..Lachez moi !..",",..hein ?","..Hi hi hi ! ...","car il y a longtemps que je t'aime...",". Pas du tout...","des araignées..."],["biloute","souffrances","fleur","dévorer","schtroumpfer","sapine","pépin","galinacée","horrible","patate","pourriture","polompolom","pelle","pomme","monstre","rigolo","poire","infection","tulipe","peur","carotte","avion","youpi-banane","immonde","papa Noël","tomate","folie"]],
        ],
        self::ModulationHead => [
            'de' => ["..Gr..","...argh..","...ggh..","hust...","RAAH! ..",".. nein...","..der...","... Ich...","..raah..",".n...","stöhn...","..nng.."],
            'en' => ["..Gr..","...argh..","...ggh..","cough...","RAAH! ..",".. no...","..the...","... I...","..raah..",".n...","groan...","..nng.."],
            'es' => [],
            'fr' => ["..raah..","...argh..","..gnn..","grogne...","..Gn..","....","tousse...","RAAH !..","...ggh..","..le...","qu.. non...","... je...",".g..."],
        ],
    ];

    public function htmlDistort( string $text, int $modulation, string $lang = 'de', ?bool &$distorted = null ): string {
        $mod_list = [];

        if ($this->rand->chance(0.05)) {
            $distorted = false;
            return $text;
        }

        foreach (static::MODULATION_LIST as $m => $langs)
            if (($m & $modulation) === $m && isset($langs[$lang]) && !empty($langs[$lang]))
                $mod_list[$m] = $langs[$lang];

        if (empty($mod_list)) {
            $distorted = false;
            return $text;
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML( "<html lang=''><head><title></title><meta charset='UTF-8' /></head><body>$text</body></html>", LIBXML_COMPACT | LIBXML_NONET | LIBXML_HTML_NOIMPLIED);
        $body = $dom->getElementsByTagName('body');

        $node_collection = [];
        $total_length = 0;

        $traverse = function(DOMNode $node, int $depth = 0) use (&$node_collection, &$traverse, &$total_length,&$dom) {

            if ($depth > 32) return;

            if ($node->nodeType === XML_ELEMENT_NODE) {

                $process = true;

                $targets = ['*',$node->nodeName];
                for ($i = 0; $i < $node->attributes->length; $i++) {

                    $n = $node->attributes->item($i)->nodeName;
                    foreach ($targets as $target) if (isset(static::HTML_IMMUTABLE["$target.$n"])) {

                        $set = static::HTML_IMMUTABLE["$target.$n"];
                        if ($set === true || in_array($node->attributes->item($i)->nodeValue, $set)) {
                            $process = false;
                            break 2;
                        }

                        if (is_array($set)) foreach (explode(' ', $node->attributes->item($i)->nodeValue) as $value)
                            if (in_array($value, $set)) {
                                $process = false;
                                break 3;
                            }


                    }

                }

                if ($process)
                    foreach ( $node->childNodes as $child )
                        $traverse($child, $depth + 1);

            } elseif ($node->nodeType === XML_TEXT_NODE) {
                $l = mb_strlen($node->textContent);

                if ($l == 0) return;

                $total_length += $l;

                while (($l = mb_strlen($node->textContent)) > 100) {

                    $m = [];
                    preg_match_all('/\s+/', mb_substr($node->textContent, 0, 110), $m, PREG_SET_ORDER|PREG_OFFSET_CAPTURE);
                    if (!empty($m)) $m = $m[array_key_last($m)];

                    $use_split = (!empty($m) && $m[array_key_last($m)][1] > 80);
                    $split = $use_split ? $m[array_key_last($m)][1] : 100;

                    $n = $dom->createTextNode( mb_substr($node->textContent, 0, $split) );
                    $node->textContent = mb_substr( $node->textContent, $split );
                    $node->parentNode->insertBefore( $n, $node );
                    $node_collection[] = [$n, mb_strlen($n->textContent)];
                }
                $node_collection[] = [$node, $l];
            }
        };

        if (isset($body[0])) $traverse($body[0]);

        if ($total_length === 0 || empty($node_collection)) {
            $distorted = false;
            return $text;
        }

        $mod_double_letters = function(array &$segments, float $chance) {
            foreach ($segments as &$segment) {
                $o = ord(strtoupper($segment));
                if ($o >= 65 && $o <= 90 && $this->rand->chance($chance))
                    $segment = chr($o) . '...' . strtolower(chr($o)) . '...' . $segment;
                else {
                    $ri = mt_rand(0, mb_strlen($segment));
                    $o = ord(strtoupper(substr($segment, $ri)));
                    if ($o >= 65 && $o <= 90 && $this->rand->chance($chance))
                        $segment = substr($segment, 0, $ri) . '...' . strtolower(chr($o)) . '...' . substr($segment, $ri);
                }
            }
        };

        $mod_insert_replace = function(array &$segments, float $replace_chance,  int $r, array $short_list, array $long_list) {
            if ($this->rand->chance($replace_chance)) $segments[$r] = $this->rand->pick( $short_list );
            else $segments = array_merge(
                array_slice($segments,0,$r),
                [$this->rand->pick( $long_list )],
                array_slice($segments,$r)
            );
        };

        foreach ($node_collection as $potential_node)
            if ($this->rand->chance( $potential_node[1] / 80.0 )) {

                // Chose a modulation
                $mod = $this->rand->pick(array_keys($mod_list));

                $segments = preg_split('/\s+/', $potential_node[0]->textContent, -1 );
                $r = mt_rand(0,count($segments) - 1);

                $distorted = true;

                if ($mod === HTMLService::ModulationHead) {

                    // Head modulation: Insert distortion in any word
                    $ri = mt_rand(0, mb_strlen($segments[$r]));
                    $segments[$r] = substr($segments[$r], 0, $ri) . $this->rand->pick($mod_list[$mod]) . substr($segments[$r], $ri);

                } elseif ($mod === HTMLService::ModulationTerror) {
                    $mod_double_letters($segments, 0.05);
                    $mod_insert_replace( $segments, 0.5, $r, $mod_list[$mod][1], $mod_list[$mod][0] );
                } else {
                    $mod_double_letters($segments, 0.02);
                    $mod_insert_replace( $segments, 0.25, $r, $mod_list[$mod], $mod_list[$mod] );
                }

                $potential_node[0]->textContent = implode(' ', $segments);
            }

        $tmp_str = "";
        foreach ($body->item(0)->childNodes as $child)
            $tmp_str .= $dom->saveHTML($child);

        return $tmp_str;
    }

    protected $emote_cache = null;
    public function get_emotes(bool $url_only = false): array {
        if ($this->emote_cache !== null) return $this->emote_cache;

        $this->emote_cache = [];
        $repo = $this->entity_manager->getRepository(Emotes::class);
        foreach($repo->findAll() as $value)
            /** @var $value Emotes */
            $this->emote_cache[$value->getTag()] = $url_only ? $value->getPath() : "<img alt='{$value->getTag()}' src='{$this->asset->getUrl( $value->getPath() )}'/>";
        return $this->emote_cache;
    }

    public function prepareEmotes(string $str): string {
        $emotes = $this->get_emotes();
        return str_replace( array_keys( $emotes ), array_values( $emotes ), $str );
    }

    protected function filterLockedEmotes(User $user, string $text): string {
        foreach ($this->getLockedEmoteTags($user) as $emote)
            $text = str_replace($emote, '', $text);
        return $text;
    }

    protected function getLockedEmoteTags(User $user): array {
        $emotes = $this->entity_manager->getRepository(Emotes::class)->getUnlockableEmotes();
        $unlocks = $this->entity_manager->getRepository(Award::class)->getAwardsByUser($user);
        $results = array();

        foreach($emotes as $emote) {
            /** @var $emote Emotes */
            $results[] = $emote->getTag();
        }

        $results = array_unique($results);

        foreach($unlocks as $entry) {
            /** @var $entry Award */
            if ($entry->getPrototype()->getAssociatedTag() && in_array($entry->getPrototype()->getAssociatedTag(), $results)) {
                unset($results[array_search($entry->getPrototype()->getAssociatedTag(), $results)]);
            }
        }

        return array_values($results);
    }
}