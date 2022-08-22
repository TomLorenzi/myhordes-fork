<?php

namespace MyHordes\Fixtures\Service;

use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class HordesFactDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
        $data = array_merge_recursive($data, [
            // French Facts
            ['name' => 'fr_001', 'content' => 'Avec quelques piles et un moteur , on a pu fabriquer un réacteur nucléaire ! Mac Gyver, peux-tu faire pareil ?', 'author' => 'Pyrojack', 'lang' => 'fr'],
            ['name' => 'fr_002', 'content' => 'Mais on ne sait toujours pas si les zombies font caca...', 'author' => 'Neoplasme', 'lang' => 'fr'],
            ['name' => 'fr_003', 'content' => 'Hordes est une communauté intentionnelle qui décide de survivre ensemble en ne respectant aucune rêgle.', 'author' => 'Rilis', 'lang' => 'fr'],
            ['name' => 'fr_004', 'content' => 'Mourir ou pourrir, il faut choisir.', 'author' => 'Tubasa', 'lang' => 'fr'],
            ['name' => 'fr_005', 'content' => 'Bob aimait le chanvre et piller nos bibelots, depuis que nous lui avons confectionné une "cravate" avec son chanvre, nos étagéres sont resplendissantes.', 'author' => 'Cakyas', 'lang' => 'fr'],
            ['name' => 'fr_006', 'content' => 'Serez-vous un héros légendaire, ou la crapule la plus titrée ?', 'author' => 'Colombuscore', 'lang' => 'fr'],
            ['name' => 'fr_007', 'content' => 'Les fous ! Ils se réjouissent car ce matin la pluie a rempli nos réserves d\'eau à sec. Quand tombera la nuit et que la Horde déferlera, ils regretteront de ne pas être morts de soif.', 'author' => 'Strygi', 'lang' => 'fr'],
            ['name' => 'fr_008', 'content' => 'Combien de temps vont-ils régner sans se faire pendre? J\'ai invoqué les corbeaux; des ossements et du sang de serpents... J\'ai peint sur mon visage les traits et mes colères. J\'envisage de faire le tri entre les traîtres et les collègues...', 'author' => 'Anakhr0m', 'lang' => 'fr'],
            ['name' => 'fr_009', 'content' => 'Pour les zombies, tu n\'es qu\'un Hord\'oeuvre !', 'author' => 'Mwamaw', 'lang' => 'fr'],
            ['name' => 'fr_010', 'content' => 'Nous serons bientôt dans l\'eau-de-là !', 'author' => 'Bouinbouin', 'lang' => 'fr'],
            ['name' => 'fr_011', 'content' => 'Les morts agissent la nuit, l\'hémorragie me nuit.', 'author' => 'Pyrolis', 'lang' => 'fr'],
            ['name' => 'fr_012', 'content' => 'NE PERDEZ PAS DE TEMPS A LIRE CET EPITAPHE : ILS SONT DERRIERE VOUS !!!!', 'author' => 'Rysaal', 'lang' => 'fr'],
            ['name' => 'fr_013', 'content' => 'Il a toujours dit qu\'il se sentait comme un oiseau en cage, surtout depuis que nous n\'étions plus que deux. En effet, j\'ai constaté qu\'il volait bien quand j\'ai jeté son corps par dessus la palissade.', 'author' => 'Nyarky', 'lang' => 'fr'],
            ['name' => 'fr_014', 'content' => 'Le Corbeau nous surveille, les Hordes nous tiennent en éveil, le cadavre du voisin nous réveille, les goules veillent...', 'author' => 'Bigfoot93', 'lang' => 'fr'],
            ['name' => 'fr_015', 'content' => 'L\'ennemi est bête : il croit que c\'est nous l\'ennemi alors que c\'est lui ! - (pour le droit de publication faudra demander à Desproges....3ème zombie au fond du couloir...).', 'author' => 'Calamity', 'lang' => 'fr'],
            ['name' => 'fr_016', 'content' => '"Pourquoi vous avez construit la potence ?" "On voulait faire une pinata." "Et ...qu\'est-ce qui va faire office de pinata ?"', 'author' => 'Tripeace', 'lang' => 'fr'],
            ['name' => 'fr_017', 'content' => 'Elle est où la poulette ?', 'author' => '1flaw', 'lang' => 'fr'],
            ['name' => 'fr_018', 'content' => 'Héhé ! J\'l\'ai toujours dit : "V\'nez dans d\'belles villes ! V\'nez voir des gens passionnants... et tuez-les !"', 'author' => 'Valhala', 'lang' => 'fr'],
            ['name' => 'fr_019', 'content' => 'Le pourrisseur est un fétard exigeant, il fait valser la ville mais refuse de dancer au bout d\'une corde, quand la soirée s\'éternise, il n\'hésite pas à ouvrir la porte à toutes nos anciennes connaissances pour relancer l\'ambiance !', 'author' => 'JackLeNoir', 'lang' => 'fr'],
            ['name' => 'fr_020', 'content' => 'Certains sortent avec des filles, d\'autres sortent les cadavres.', 'author' => 'Pyrolis', 'lang' => 'fr'],
            ['name' => 'fr_021', 'content' => 'Je les aurai tous, je les tuerai tous ! Cela bien avant qu\'ils ne décident tous, de me pendre haut et court..', 'author' => 'Sparte60', 'lang' => 'fr'],
            ['name' => 'fr_022', 'content' => 'Scie trouvée le jour 1. Architectoire fait le jour 2. Pluvio-canons et cimetières cadenassé terminés le jour 3... Qui aurait gardé 1 PA pour fermer les portes ?... ... !? Vie de merde.', 'author' => 'Atlas101', 'lang' => 'fr'],
            ['name' => 'fr_023', 'content' => 'Hordes rime avec dis-corde.', 'author' => 'Jemax', 'lang' => 'fr'],
            ['name' => 'fr_024', 'content' => 'Hordes, le seul jeu déconseillé au moins de 13 ans dans lequel on s\'amuse avec des pistolets à eau et des ours en peluche.', 'author' => 'Gargamel52', 'lang' => 'fr'],
            ['name' => 'fr_025', 'content' => '"Moi, Johnyzombi, autorise Motion-Twin à faire figurer cette phrase sur la page d\'accueil de son super, génial et merveilleux site Hordes.fr, pour l\'éternité."', 'author' => 'Johnyzombi', 'lang' => 'fr'],
            ['name' => 'fr_026', 'content' => 'Tu es mon prochain repas.', 'author' => 'Atr0pos', 'lang' => 'fr'],
            ['name' => 'fr_027', 'content' => '"C\'est étrange, les derniers mots de la goule avant de mourir furent : \'J\'ai contaminé toutes les femmes de la ville sauf une !\'." "Tu as raison chéri, c\'est étrange d\'en avoir laissé une..."', 'author' => 'Shazeau', 'lang' => 'fr'],
            ['name' => 'fr_028', 'content' => 'Les zombies sont lents, bêtes, laids et méchants. Juste comme nous.', 'author' => 'Benko76', 'lang' => 'fr'],
            ['name' => 'fr_029', 'content' => 'Un petit pas pour la Horde, un grand pas en arrière pour l\'humanité.', 'author' => 'Vertoss', 'lang' => 'fr'],
            ['name' => 'fr_030', 'content' => 'Hordes ma vue ! Mon Pistolet à eau est chargé !', 'author' => 'Cybernetic', 'lang' => 'fr'],
            ['name' => 'fr_031', 'content' => 'Plus le citoyen est proche de la lumière, et plus l\'ombre qui le précède se fait immense, lugubre et menaçante.', 'author' => 'Chandelle', 'lang' => 'fr'],
            ['name' => 'fr_032', 'content' => 'N\'oublie pas que ton équipier est le meilleur des boucliers.', 'author' => 'Migi', 'lang' => 'fr'],
            ['name' => 'fr_033', 'content' => 'Un bon citoyen, est un citoyen bien cuit.', 'author' => 'Aker93', 'lang' => 'fr'],
            ['name' => 'fr_034', 'content' => 'Hordes, c\'\'est simple : Sans foie, ni loi.....ni bras !!', 'author' => 'Urbanejean', 'lang' => 'fr'],
            ['name' => 'fr_035', 'content' => 'Il faut jouer Collectif..Collect..Colle..Col.. QUI EST L\'ENFOIRE QUI A FERME LA PORTE, BAN\', PENDAISON, BOUH !', 'author' => 'Jesuswas', 'lang' => 'fr'],
            ['name' => 'fr_036', 'content' => 'C\'est fou l\'effet psychologique que peut avoir un meurtre par empoisonnement dans une communauté déjà soumise au stress d\'une attaque extêrieure répétée...', 'author' => 'Zergor', 'lang' => 'fr'],
            ['name' => 'fr_037', 'content' => 'Grrr... Argh.. Grr.. Argh.. Argh.... Grrr.. Hordes, plus qu\'un jeu, un langage !', 'author' => 'Hotaku', 'lang' => 'fr'],
            ['name' => 'fr_038', 'content' => 'Tu vois, hordes se divise en 2 catégories: les Admins, qui ont un pistolet chargé et ceux qui creusent. Toi, tu creuses.', 'author' => 'Vince22', 'lang' => 'fr'],
            ['name' => 'fr_039', 'content' => 'Je suis souvent reliée à une ritournelle élastiquée avec Le Corbeau, ça se tend jusqu\'au mur du son pour ensuite se relâcher d\'un coup sec, provoquant une apoplexie de couleurs sur mon foie. J\'aime !', 'author' => 'Astrocytome', 'lang' => 'fr'],
            ['name' => 'fr_040', 'content' => '"En quelques jours, on a tous changé. Avant je maudissais la pluie, aujourd\'hui, elle me manque ..."', 'author' => 'JethroDiamond', 'lang' => 'fr'],
            ['name' => 'fr_041', 'content' => 'J\'adore l\'odeur des cadavres au petit matin...', 'author' => 'FroggiePaul', 'lang' => 'fr'],
            ['name' => 'fr_042', 'content' => 'Sacrée Grand-mère, c\'est bien dans les vieilles peaux qu\'on fait les meilleurs soupes.', 'author' => 'Foulonus', 'lang' => 'fr'],
            ['name' => 'fr_043', 'content' => 'A bientôt.', 'author' => 'Trucsansnom', 'lang' => 'fr'],
            ['name' => 'fr_044', 'content' => 'Et sonne le glas de notre ère - Lorsque se lèvent sur la dune - Des ombres, êtres de poussière - Pâle effroi d\'une nuit sans lune.', 'author' => 'JackZeuripeur', 'lang' => 'fr'],
            ['name' => 'fr_045', 'content' => 'Que vous le vouliez ou non, à partir d\'aujourd\'hui et jusqu\'à l\'heure de notre mort, nous sommes tous dans le même bateau.', 'author' => 'Cordeur-le-Marin', 'lang' => 'fr'],
            ['name' => 'fr_046', 'content' => 'Paradoxalement, boire de l\'alcool ne vous aidera pas à communiquer sur le faux-rhum.', 'author' => 'TontonClement', 'lang' => 'fr'],
            ['name' => 'fr_047', 'content' => 'Sur Hordes, les concitoyens vraiment dignes de confiance se comptent sur les doigts d\'un moignon.', 'author' => 'Nadawoo', 'lang' => 'fr'],
            ['name' => 'fr_048', 'content' => 'Allons camper ! On ne risque rien qu\'il disait ! ça c\'était avant que je jette un os charnu en direction de sa cachette pour distraire les zombies.', 'author' => 'Eldorandril', 'lang' => 'fr'],
            ['name' => 'fr_049', 'content' => '"Je te mange, donc je suis" - Deckart, philosophe zombie.', 'author' => 'Equitus', 'lang' => 'fr'],
            ['name' => 'fr_050', 'content' => 'Abraham Lincoln disait : " Vous pouvez tromper quelques personnes tout le temps. Vous pouvez tromper tout le monde un certain temps. Mais vous ne pouvez tromper tout le monde tout le temps ". Celui-là n\'a jamais joué à Hordes !', 'author' => 'Drong', 'lang' => 'fr'],
            ['name' => 'fr_051', 'content' => 'La hardie horde ordonnera-elle a ses membres de s\'entre-manger ? La confiance régnera-elle ? Vous aimez cette ambiance ? Moignon !', 'author' => 'Yunn', 'lang' => 'fr'],
            ['name' => 'fr_052', 'content' => 'Nous partîmes quarante ; mais par un prompt renfort - Nous vîmes trois mille zombies frapper aux portes.', 'author' => 'Khyrra', 'lang' => 'fr'],
            ['name' => 'fr_053', 'content' => 'Depuis cette nuit grand-mère n\'est plus la même... Atterré par son teint blafard et son regard vide, je n\'ai pas vu la morsure venir ! Heureusement qu\'elle porte un dentier.', 'author' => 'Krapaglok', 'lang' => 'fr'],
            ['name' => 'fr_054', 'content' => 'La "faim" du monde est arrivée. Nous, on est le repas....', 'author' => 'HixHess', 'lang' => 'fr'],
            ['name' => 'fr_055', 'content' => 'Hordes.fr, le seul jeu où l\'on peut manger sa belle-mère.', 'author' => 'Reuters', 'lang' => 'fr'],
            ['name' => 'fr_056', 'content' => 'En cas de coup dur, on dit souvent les femmes et les enfants d\'abord. Pas faux. C\'était les premiers à être bouffés.', 'author' => 'YvesRemort', 'lang' => 'fr'],
            ['name' => 'fr_057', 'content' => '"Comment se fait-il que Georges soit mort de soif cette nuit ??" "Bah il a barricadé de partout son taudis l\'autre jour avant l\'arrivée des mous du bulbe..." "Je vois pas le rapport ?" "ça fait trois jours qu\'il essayait de sortir..."', 'author' => 'Nemesis44', 'lang' => 'fr'],
            ['name' => 'fr_058', 'content' => 'N\'empêche, malgré ses boutons d\'acné, je l\'aime bien le petit Naruto1992... D\'ailleurs je vais en reprendre un peu !', 'author' => 'TheMonkeySan', 'lang' => 'fr'],
            ['name' => 'fr_059', 'content' => 'Le trépas des uns fait le repas des autres...', 'author' => 'Megamaster', 'lang' => 'fr'],
            ['name' => 'fr_060', 'content' => 'C\'est bientôt l\'heure. J\'entends leurs râles lugubres, leurs souffle meurtri, leur membres trainer à même le sol. L\'odeur, l\'odeur fétide et macabre qu\'ils portent avec eux.. Les corbeaux annoncent leur venue, Les Hordes arrivent..', 'author' => 'Zombinophobe', 'lang' => 'fr'],
            ['name' => 'fr_061', 'content' => 'Hordes, le seul jeu ou l\'on garde une radio chez soi dans le but de se la faire voler.', 'author' => 'HerosHine', 'lang' => 'fr'],
            ['name' => 'fr_062', 'content' => 'Veuillez rentrer en ville avant minuit, ceci est un Hordes !', 'author' => 'Speculos', 'lang' => 'fr'],
            ['name' => 'fr_063', 'content' => 'Nota pour la goule : Repas du midi, agression garantie. Repas du soir, digestion sans histoire.', 'author' => 'Cocotueur', 'lang' => 'fr'],
            ['name' => 'fr_064', 'content' => 'Jouer à Hordes augmenterait considérablement ses chances de survie en 2012.', 'author' => 'Hellmoutte', 'lang' => 'fr'],
            ['name' => 'fr_065', 'content' => 'Les zombies, ça me met Hordes moi.', 'author' => 'Kirwa', 'lang' => 'fr'],
            ['name' => 'fr_066', 'content' => 'Je préfère être une bête humaine qu\'un bête humain !', 'author' => 'Grobrak', 'lang' => 'fr'],
            ['name' => 'fr_067', 'content' => 'On attend toujours l\'ultime moment pour montrer ce qu\'on a dans le ventre. Sauf si on vous force le destin...', 'author' => 'Powsen', 'lang' => 'fr'],
            ['name' => 'fr_068', 'content' => 'Ce soir je suis le dernier survivant, je suis déshydraté, en état de manque, dépendant, infecté et blessé mais je n\'ai pas oublié de fermer les portes ! On sait jamais...', 'author' => 'Uncensored', 'lang' => 'fr'],
            ['name' => 'fr_069', 'content' => '"Tiens ce matin j\'ai croisé John qui quittait la ville." "Moi cet aprem j\'ai croisé sa moité inférieure qui tentait d\'y revenir..."', 'author' => 'Romtk', 'lang' => 'fr'],
            ['name' => 'fr_070', 'content' => 'Je suis sûr d\'aller au Paradis, car ici c\'est l\'Enfer.', 'author' => 'Okka', 'lang' => 'fr'],
            ['name' => 'fr_071', 'content' => 'Ensemble, tout devient horrible...', 'author' => 'CrazyGeorge5', 'lang' => 'fr'],
            ['name' => 'fr_072', 'content' => 'Avant, j\'étais cuisinier. L\'arrivée des Hordes a changé beaucoup de choses ... la provenance de la viande notamment.', 'author' => 'Bouchzd', 'lang' => 'fr'],
            ['name' => 'fr_073', 'content' => 'J\'ai buté un zombie, j\'ai changé de case, j\'ai rebuté un zombie, j\'ai rechangé de case, j\'ai trouvé un écrit, j\'ai rechangé de case, je suis resté bloqué, personne ne m\'a aidé, je suis mort, j\'ai changé de ville... Bref, j\'ai joué à Hordes.', 'author' => 'Angelys', 'lang' => 'fr'],
            ['name' => 'fr_074', 'content' => 'Horrible - Orgie de - Rapaces - Dévorant - Entrailles.... - Slurp!', 'author' => 'Zmx1', 'lang' => 'fr'],
            ['name' => 'fr_075', 'content' => 'C\'est la force de l\'Illusion qui transforme les hommes en Héros...', 'author' => 'Tifou-le-subtil', 'lang' => 'fr'],
            ['name' => 'fr_076', 'content' => 'Hordes c\'est un peu comme Wisteria Lane à Silent Hill. Il vous faudra survivre... surtout à vos voisins ! Au départ, vous êtes 40, mais à la fin, il n\'en restera qu\'un !', 'author' => 'Docteurhache', 'lang' => 'fr'],
            ['name' => 'fr_077', 'content' => 'Pas besoin de se creuser la tête ici, dans deux jours, une goule le fera pour vous...', 'author' => 'Artigan89', 'lang' => 'fr'],
            ['name' => 'fr_078', 'content' => '[Message édité par le Corbeau.]', 'author' => 'Le Corbeau', 'lang' => 'fr'],
            ['name' => 'fr_079', 'content' => 'L\'avantage de l\'escorte ? Avoir des amis qui courent moins vite que soi !', 'author' => 'Escaped', 'lang' => 'fr'],
            ['name' => 'fr_080', 'content' => 'Quand je suis arrivée par ici, on m\'a dit de bien faire attention à mon foie. J\'ai compris bien assez tôt qu\'il n\'était pas question d\'alcool...', 'author' => 'Genny1506', 'lang' => 'fr'],
            ['name' => 'fr_081', 'content' => 'A la fin on meurt mais bien souvent on est M\'hordes rire...', 'author' => 'Rilis', 'lang' => 'fr'],
            ['name' => 'fr_082', 'content' => 'Depuis que je joue à Hordes, j\'ai rencontré un tas de créatures étranges, sans pitié et sanguinaires... et je vous parle même pas de tous ces zombies dehors !', 'author' => '5tapl3r', 'lang' => 'fr'],
            ['name' => 'fr_083', 'content' => 'Hordes.fr est un monde sans scrupules, ton ennemi est peut-être déjà derrière toi... Ah non c\'était juste une poule.', 'author' => 'Tchikita', 'lang' => 'fr'],
            ['name' => 'fr_084', 'content' => 'Motion-twin c\'est bon, mangez en.', 'author' => 'Sunx-le-coquin', 'lang' => 'fr'],
            ['name' => 'fr_085', 'content' => 'Viscères et tripaille : ce soir c\'est ripaille !', 'author' => 'Butcher', 'lang' => 'fr'],
            ['name' => 'fr_086', 'content' => 'Votre voisin veut vous bouffer, une infection vous guette, votre seule arme est un gros chat mignon, votre gourde est peut-être empoisonnée : bref, une journée normale : Bienvenue sur Hordes !', 'author' => 'Certis', 'lang' => 'fr'],
            ['name' => 'fr_087', 'content' => 'Hordes est un jeu communautaire où tu fais crever des zombies déjà morts et où des zombies déjà morts te font crever...', 'author' => 'Elilaos173', 'lang' => 'fr'],
            ['name' => 'fr_088', 'content' => '"Bienvenue dans un monde de rêve... Enfin, pas pour toi."', 'author' => 'MrMystere', 'lang' => 'fr'],
            ['name' => 'fr_089', 'content' => '"Que choisir ? Manger l\'os charnu ou construire les appâts ? L\'individualisme ou le collectif ..? J\'ai mal choisi." ? R.I.P. - Rassasié Infecté Pendu.', 'author' => 'Paolino', 'lang' => 'fr'],
            ['name' => 'fr_090', 'content' => 'Il faut avoir des couilles pour jouer à Hordes. En les lançant au loin, vous distrairez peut-être les zombies le temps de déguerpir.', 'author' => 'Jery', 'lang' => 'fr'],
            ['name' => 'fr_091', 'content' => 'Avec des amis comme ça, on a pas besoin de zombies.', 'author' => 'Lennon', 'lang' => 'fr'],
            ['name' => 'fr_092', 'content' => 'Les yeux sont jetés, rien ne voit plus !', 'author' => 'Kissmybass', 'lang' => 'fr'],
            ['name' => 'fr_093', 'content' => 'Hordes, bienvenue dans un monde où les notions de "Savoir-vivre" prennent un tout autre sens.', 'author' => 'Aschamploo', 'lang' => 'fr'],
            ['name' => 'fr_094', 'content' => 'Hordes : Le don d\'organes n\'a jamais été si populaire.', 'author' => 'Felsefiel', 'lang' => 'fr'],
            ['name' => 'fr_095', 'content' => '" La question qui se pose pour les humains n\'est pas de savoir combien d\'entre eux survivront, mais quel sera le genre d\'existence de ceux qui survivront... " - Dernière annonce émise par radio avant l\'Armageddon...', 'author' => 'NeoRyu', 'lang' => 'fr'],
            ['name' => 'fr_096', 'content' => 'Ce qui ne nous tue pas, nous terrorise pour toujours.', 'author' => 'ChrisCool', 'lang' => 'fr'],
            ['name' => 'fr_097', 'content' => 'Hordes c\'est 6 873 550 citoyens dé-séché, arrachés, écartelés, empoisonnés, noyés, pendus, calcinés, transpercés, broyés, humiliés, lapidés, crucifiés,.... Et ils en redemandent....', 'author' => 'Golg', 'lang' => 'fr'],
            ['name' => 'fr_098', 'content' => 'Un zombie en cache TOUJOURS un autre !', 'author' => 'Poumcala', 'lang' => 'fr'],
            ['name' => 'fr_099', 'content' => 'N\'oubliez jamais que l\'horreur est humaine...', 'author' => 'Neoplasme', 'lang' => 'fr'],
            ['name' => 'fr_100', 'content' => 'Est-ce que les zombie mangent du popcorn avec leurs doigts ? Non, ils mangent le popcorn et leurs doigts séparément.', 'author' => 'Killpingou', 'lang' => 'fr'],
            ['name' => 'fr_101', 'content' => 'Oups, il me manque un PA pour ...', 'author' => 'Sil3nC', 'lang' => 'fr'],
            ['name' => 'fr_102', 'content' => 'Dehors, des hordes de morts dévorent des corps, encore... Dedans, paranoïa, panique, méfiance et suspicions. Que choisir ?', 'author' => 'Patat72', 'lang' => 'fr'],
            ['name' => 'fr_103', 'content' => 'En plus on doit écrire notre propre épitaphe... C\'est vraiment la crise...', 'author' => 'Vitolerare', 'lang' => 'fr'],
            ['name' => 'fr_104', 'content' => 'Mais vous savez, je ne crois pas qu\'il y ait de bonnes ou de mauvaises situations dans Hordes... il n\'y en a que de très mauvaises.', 'author' => 'CortezXII', 'lang' => 'fr'],
            ['name' => 'fr_105', 'content' => 'Quand la faim survenait je leur disais en plaisantant "Mange ta main, garde l\'autre pour demain". J\'ai beaucoup moins ri quand ils ont commencé à suivre mon conseil.', 'author' => 'Skuz', 'lang' => 'fr'],
            ['name' => 'fr_106', 'content' => 'Voyage de ville en ville, chante pour conjurer ta peur, geins dans le silence de la nuit, rebelle-toi contre l\'inévitable, puis oublie... oublie ce monde aride et désertique, pas de commencement, pas de fin... oups ! tu es devenu Hordien !', 'author' => 'Jarekmace', 'lang' => 'fr'],
            ['name' => 'fr_107', 'content' => 'Celui qui épicera ses nouilles sera pendu.', 'author' => 'Narcissique', 'lang' => 'fr'],
            ['name' => 'fr_108', 'content' => 'La différence entre le zombie et l\'homme ? L\'un mange de la chair humaine, dégage une odeur pestilentielle et possède un cerveau atrophié qui ne lui permet pas de s\'organiser en communauté. L\'autre tambourine à la porte tous les soirs.', 'author' => 'GagaiGeorge', 'lang' => 'fr'],
            ['name' => 'fr_109', 'content' => '"Citoyen, hors de la ville, prudence!" ça me fait bien marrer ça, comme si dedans on était mieux.', 'author' => 'Sarig', 'lang' => 'fr'],
            ['name' => 'fr_110', 'content' => 'Tu vois Hordes au début tu dis "Tiens je vais me faire une petite partie", puis "Allez une bonne petite ville !". Et à la fin tu fais des trous avec une pelle en vrai dans ton salon pour trouver de quoi survivre.', 'author' => 'Alineab', 'lang' => 'fr'],
            ['name' => 'fr_111', 'content' => 'Je suis le meilleur dans ma partie, mais c\'est pas forcément beau à voir...', 'author' => 'KadillaK', 'lang' => 'fr'],


            // English facts
            ['name' => 'en_001', 'content' => 'He who steals must be hanged! He who commits someone to be hanged should be banned! He who is banned must be eaten! This is how the law should be!', 'author' => 'Unknown', 'lang' => 'en'],
            ['name' => 'en_002', 'content' => 'One day, a pastor went on a crusade against the evil Horde. It would appear that there were more of the latter. He never returned.', 'author' => 'Unknown', 'lang' => 'en'],
            ['name' => 'en_003', 'content' => 'A coalition between friends creates more problems than it ends...', 'author' => 'Unknown', 'lang' => 'en'],
            ['name' => 'en_004', 'content' => 'This "cocktail" is disgusting, but at least it\'s expensive...', 'author' => 'PeteR', 'lang' => 'en'],
            ['name' => 'en_005', 'content' => 'I know, I know. The food is awful but at least it doesn\'t move when you\'re trying to tuck in.', 'author' => 'Unknown', 'lang' => 'en'],
            ['name' => 'en_006', 'content' => 'One night, a long time ago, old Chuck saw the Horde and lived to tell the tale. The very sight made his buttcheeks clench like a vice. They have not been unclenched since!', 'author' => 'Unknown', 'lang' => 'en'],
            ['name' => 'en_007', 'content' => 'Be positive! You\'re going to die. Every time.', 'author' => 'Unknown', 'lang' => 'en'],
            ['name' => 'en_008', 'content' => 'If you\'re on an expedition and you sense danger, stay calm, wait a second, get your Nikes on and RUN FORREST, RUUUUUUNNNN!!!', 'author' => 'Unknown', 'lang' => 'en'],
            ['name' => 'en_009', 'content' => 'Reading the newspaper is the only way to find out what\'s really been going on.', 'author' => 'Unknown', 'lang' => 'en'],
            ['name' => 'en_010', 'content' => 'Cyanide... A jagged little pill indeed, but it beats listening to "Ironic" again!', 'author' => 'Unknown', 'lang' => 'en'],
            ['name' => 'en_011', 'content' => 'If the trees knew they were making oxygen for HIM they\'d cut themselves down!', 'author' => 'BoxOffice', 'lang' => 'en'],
            ['name' => 'en_012', 'content' => 'Always keep TWO bullets in your gun. One to take the head off a zombie, and another for yourself if you miss..', 'author' => 'Unknown', 'lang' => 'en'],
            ['name' => 'en_013', 'content' => 'Sometimes behind a great hero you find an ass', 'author' => 'Unknown', 'lang' => 'en'],
            ['name' => 'en_014', 'content' => 'My favourite thing about group expeditions is letting everyone do the searching and bogarting the good stuff!!!', 'author' => 'Unknown', 'lang' => 'en'],
            ['name' => 'en_015', 'content' => 'A bone on the ground is a once lost friend, found.', 'author' => 'Unknown', 'lang' => 'en'],

            // German facts
            ['name' => 'de_001', 'content' => 'Ich liebe den Geruch von frischen Leichen am Morgen...', 'author' => 'FroggiePaul', 'lang' => 'de'],
            ['name' => 'de_002', 'content' => 'Du bist meine nächste Mahlzeit.', 'author' => 'Atr0pos', 'lang' => 'de'],
            ['name' => 'de_003', 'content' => 'Die Verdammten ist das einzige Spiel, in dem man seine Schwiegermutter fressen kann.', 'author' => 'Reuters', 'lang' => 'de'],
            ['name' => 'de_004', 'content' => 'Ich bin sicher, dass es ein Paradies gibt. Schließlich gibt es ja auch die Hölle. Genau hier...', 'author' => 'Okka', 'lang' => 'de'],
            ['name' => 'de_005', 'content' => '"Ich fresse dich, also bin ich." - Deckart, Zombiephilospoh.', 'author' => 'Equitus', 'lang' => 'de'],
            ['name' => 'de_006', 'content' => 'Bis bald.', 'author' => 'Namenloses Ding', 'lang' => 'de'],
            ['name' => 'de_007', 'content' => 'Motion Twin ist gut. Vor allem mit Kartoffeln und einem Spritzer Ketchup.', 'author' => 'GourMet', 'lang' => 'de'],
            ['name' => 'de_008', 'content' => '[Nachricht vom Raben bearbeitet]', 'author' => 'Der Rabe', 'lang' => 'de'],

            // Spanish facts
            ['name' => 'es_001', 'content' => 'Aquí, los compañeros dignos de confianza se cuentan con los dedos de una mano mutilada.', 'author' => 'Manco', 'lang' => 'es'],
        ]);
    }
}