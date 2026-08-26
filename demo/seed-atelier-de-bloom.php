<?php

declare(strict_types=1);

/**
 * Contenu de démonstration — Atelier de Bloom, fleuriste à Nantes.
 *
 * Écrit l'identité, les pages, le menu et les actualités du site de
 * démonstration, à partir des illustrations déposées dans public/assets/img.
 *
 * Ce script est propre à ce site : il ne remplace pas bin/cockpit-init.php,
 * qui crée les comptes, les rôles et les clés, et qu'il faut avoir lancé
 * avant. Il vit hors de bin/ pour la même raison que templates-client/ vit
 * hors de templates/ : une mise à jour du socle ne le rencontre jamais.
 *
 * Réexécutable : chaque élément est réécrit tel qu'il est décrit ici, le
 * dépôt faisant foi. Ce qui a été saisi à la main dans l'administration est
 * donc perdu — c'est voulu pour une démonstration, à ne pas transposer tel
 * quel sur un site en production.
 *
 * Usage : php demo/seed-atelier-de-bloom.php
 */

if (PHP_SAPI !== 'cli') {
    exit("Ce script s'exécute en ligne de commande.\n");
}

$root = dirname(__DIR__);
$adminDir = "{$root}/public/admin";

if (!is_file("{$adminDir}/bootstrap.php")) {
    fwrite(STDERR, "\n  Cockpit n'est pas installé. Lancer d'abord : php bin/install-cockpit.php\n\n");
    exit(1);
}

require "{$adminDir}/bootstrap.php";

/** @var Lime\App $app */
$app = Cockpit::instance($adminDir);

set_exception_handler(static function (Throwable $e): void {
    fwrite(STDERR, "\n  Échec : {$e->getMessage()}\n  {$e->getFile()}:{$e->getLine()}\n\n");
    exit(1);
});

function step(string $message): void
{
    echo "  {$message}\n";
}

/**
 * Enregistre une image du dossier public/assets/img comme média de Cockpit.
 *
 * Passer par la routine d'envoi de Cockpit plutôt que d'écrire dans la base :
 * c'est elle qui déclenche la fabrication des copies allégées, sans
 * lesquelles le site servirait l'original à tout le monde.
 *
 * @return array<string, mixed>|null Le média enregistré, tel qu'il est posé
 *                                   sur un champ image.
 */
function media(Lime\App $app, string $root, string $file, string $title, string $description): ?array
{
    $existing = $app->dataStorage->findOne('assets', ['title' => $title]);

    if (is_array($existing)) {
        return $existing;
    }

    $source = "{$root}/public/assets/img/{$file}";

    if (!is_file($source)) {
        fwrite(STDERR, "\n  Image absente : {$source}\n\n");
        exit(1);
    }

    // L'envoi déplace le fichier : lui en confier une copie.
    $temporary = $app->path('#tmp:').'/'.$file;
    copy($source, $temporary);

    $result = $app->module('assets')->upload([
        'name' => [$file],
        'full_path' => [$file],
        'type' => [mime_content_type($temporary) ?: 'image/jpeg'],
        'tmp_name' => [$temporary],
        'error' => [0],
        'size' => [filesize($temporary)],
    ], [], false);

    $asset = $result['assets'][0] ?? null;

    if (!is_array($asset)) {
        fwrite(STDERR, "\n  L'envoi de {$file} a échoué.\n\n");
        exit(1);
    }

    $asset['title'] = $title;
    $asset['description'] = $description;

    $app->dataStorage->save('assets', $asset);

    return $asset;
}

echo "\nContenu de démonstration — Atelier de Bloom\n\n";

$content = $app->module('content');

// ── 1. Les illustrations ──────────────────────────────────────────────────
//
// Six aquarelles, réutilisées d'une section à l'autre : une démonstration
// montre des gabarits, pas un catalogue.

$img = [];

foreach ([
    'devanture' => ['01-boutique-atelier-de-bloom.jpg', 'Devanture de la boutique',
        'La devanture vert sombre de l’Atelier de Bloom, rue Kléber, encadrée de lierre et de pots de fleurs.'],
    'embleme' => ['02-embleme-atelier-de-bloom.jpg', 'Emblème de l’atelier',
        'L’emblème de l’Atelier de Bloom : le nom dans un cartouche ovale, surmonté d’une branche d’olivier.'],
    'bouquet' => ['03-bouquet-signature.jpg', 'Bouquet signature',
        'Bouquet rond de pivoines roses et de fleurs blanches, noué d’un large ruban vert.'],
    'nantes' => ['04-nantes-illustration.jpg', 'Les quais de Nantes',
        'Les quais de la Loire à Nantes, un voilier amarré devant les façades claires et le dôme.'],
    'kraft' => ['05-composition-florale.jpg', 'Composition en papier kraft',
        'Bouquet couché dans un papier kraft noué de ficelle, à côté d’une paire de ciseaux.'],
    'atelier' => ['06-fleuriste-atelier.jpg', 'À l’atelier',
        'Une fleuriste en tablier compose un bouquet sur l’établi de l’atelier, devant des bocaux de fleurs.'],
] as $cle => [$fichier, $titre, $description]) {
    $img[$cle] = media($app, $root, $fichier, $titre, $description);
}

step(count($img).' illustrations enregistrées comme médias, avec leurs copies allégées.');

// ── 2. L'identité du site ─────────────────────────────────────────────────
//
// Les couleurs reprennent l'enseigne. Elles n'atteignent le site que si elles
// tiennent le contraste exigé de 4,5:1 sur blanc — celles-ci sont à 8,2:1 et
// 13,5:1.
//
// L'image de partage est laissée vide à dessein : aucune des illustrations ne
// fait les 1200 × 630 pixels attendus par les réseaux sociaux. Le site prend
// alors l'image de la page, puis le logo.

$content->saveItem('settings', array_merge($content->item('settings') ?? [], [
    'nom' => 'Atelier de Bloom',
    'slogan' => 'Fleuriste à Nantes',
    'description' => 'Fleuriste à Nantes : bouquets de saison, compositions sur mesure et '
        .'abonnements, composés à la main rue Kléber.',
    'logo' => $img['embleme'],
    'imagePartage' => null,
    'siret' => '000 000 000 00000',
    'couleurPrincipale' => '#2c5744',
    'couleurTexte' => '#26312b',
    'email' => 'bonjour@atelier-de-bloom.test',
    // Numéro réservé à la fiction par l'ARCEP : il ne sonne chez personne.
    'telephone' => '02 61 91 00 42',
    'adresse' => "8 rue Kléber\n44000 Nantes",
    'horaires' => [
        'lundi' => '',
        'mardi' => '9h30 – 13h, 14h30 – 19h',
        'mercredi' => '9h30 – 13h, 14h30 – 19h',
        'jeudi' => '9h30 – 13h, 14h30 – 19h',
        'vendredi' => '9h30 – 13h, 14h30 – 19h',
        'samedi' => '9h – 19h',
        'dimanche' => '9h – 13h',
    ],
    'reseaux' => [
        ['nom' => 'Instagram', 'url' => 'https://www.instagram.com/atelier.de.bloom'],
    ],
]));

step('Identité du site renseignée : nom, couleurs, coordonnées et horaires.');

// ── 3. Les sections réutilisées d'une page à l'autre ──────────────────────

$blocContact = [
    'type' => 'contact',
    'titre' => 'Passer nous voir',
    'texte' => '<p>La boutique est ouverte du mardi au dimanche matin. Les commandes se '
        .'passent sur place, par téléphone ou par écrit.</p>',
    'afficherHoraires' => true,
];

$blocTemoignages = [
    'type' => 'temoignages',
    'titre' => 'Ce qu’en disent nos clients',
    'introduction' => 'Quelques retours reçus après une commande.',
    'temoignages' => [
        [
            'citation' => 'J’ai commandé un bouquet la veille pour le lendemain matin. Il était '
                .'prêt à l’heure dite, et il a tenu douze jours.',
            'auteur' => 'Hélène M.',
            'fonction' => 'Nantes, quartier Graslin',
        ],
        [
            'citation' => 'Ils ont composé les tables et le bouquet de mariée à partir de trois '
                .'photos et d’une conversation. Nous n’avons eu à reprendre sur rien.',
            'auteur' => 'Sofiane et Claire B.',
            'fonction' => 'Mariage en juin',
        ],
        [
            'citation' => 'Une composition nous arrive chaque lundi à l’accueil. Les clients la '
                .'remarquent, et l’équipe aussi.',
            'auteur' => 'Marion T.',
            'fonction' => 'Responsable de site, Nantes Erdre',
        ],
    ],
];

// ── 4. Les pages ──────────────────────────────────────────────────────────

$pages = [];

$pages['accueil'] = [
    'titre' => 'Atelier de Bloom',
    'slug' => 'accueil',
    'seoTitre' => 'Atelier de Bloom, fleuriste à Nantes',
    'seoDescription' => 'Bouquets de saison composés à la main rue Kléber, à Nantes. '
        .'Compositions pour mariages, abonnements pour les entreprises.',
    '_state' => 1,
    'blocs' => [

        // Le bandeau porte le titre de la page, donc le seul <h1> : c'est le
        // gabarit page.html.twig qui s'en charge, rien à saisir ici.
        [
            'type' => 'hero',
            'titre' => '',
            'accroche' => 'Un atelier de fleuriste rue Kléber, à Nantes. Des fleurs de saison '
                .'coupées chez six producteurs de Loire-Atlantique, composées à la main, à la commande.',
            'image' => $img['devanture'],
            'alt' => $img['devanture']['description'],
            'boutonTexte' => 'Découvrir nos créations',
            'boutonLien' => '/creations',
        ],

        [
            'type' => 'texte-image',
            'titre' => 'Un atelier, pas une chaîne',
            'texte' => '<p>Chaque bouquet est composé à la main, le jour où il est retiré. Nous '
                .'travaillons avec six producteurs situés à moins de quarante kilomètres de la '
                .'boutique, et nous n’achetons que ce qui est en fleur.</p>'
                .'<h3>Ce que cela change</h3>'
                .'<p>Les variétés suivent réellement les saisons, et les fleurs tiennent plus '
                .'longtemps : elles ont voyagé une heure, pas trois jours.</p>',
            'image' => $img['atelier'],
            'alt' => $img['atelier']['description'],
            'positionImage' => 'droite',
        ],

        [
            'type' => 'prestations',
            'titre' => 'Ce que nous composons',
            'introduction' => 'Trois façons de faire appel à l’atelier. Le détail de chacune est '
                .'sur la page des prestations.',
            'prestations' => [
                [
                    'nom' => 'Bouquet du jour',
                    'prix' => 'à partir de 22 €',
                    'resume' => 'Composé le matin même avec les arrivages du jour. À emporter ou '
                        .'à faire livrer dans Nantes.',
                    'image' => $img['bouquet'],
                    'alt' => $img['bouquet']['description'],
                    'lienTexte' => 'Voir le détail',
                    'lien' => '/prestations',
                ],
                [
                    'nom' => 'Mariages et cérémonies',
                    'prix' => 'sur devis',
                    'resume' => 'Bouquet de mariée, tables, arche et boutonnières. Un rendez-vous '
                        .'d’une heure, puis une proposition chiffrée.',
                    'image' => $img['kraft'],
                    'alt' => $img['kraft']['description'],
                    'lienTexte' => 'Voir le détail',
                    'lien' => '/prestations',
                ],
                [
                    'nom' => 'Abonnement bureaux',
                    'prix' => 'à partir de 45 € par semaine',
                    'resume' => 'Une composition renouvelée chaque semaine, livrée et installée '
                        .'à l’accueil de vos locaux.',
                    'image' => $img['atelier'],
                    'alt' => $img['atelier']['description'],
                    'lienTexte' => 'Voir le détail',
                    'lien' => '/prestations',
                ],
            ],
        ],

        [
            'type' => 'galerie',
            'titre' => 'Quelques créations',
            'introduction' => 'Un aperçu de ce qui sort de l’atelier au fil des semaines.',
            'colonnes' => 'trois',
            'images' => [
                [
                    'image' => $img['bouquet'],
                    'alt' => $img['bouquet']['description'],
                    'legende' => 'Bouquet signature, pivoines et ruban de soie',
                ],
                [
                    'image' => $img['kraft'],
                    'alt' => $img['kraft']['description'],
                    'legende' => 'Composition couchée, papier kraft et ficelle',
                ],
                [
                    'image' => $img['atelier'],
                    'alt' => $img['atelier']['description'],
                    'legende' => 'L’établi, un lundi matin',
                ],
            ],
        ],

        $blocTemoignages,

        [
            'type' => 'appel-action',
            'titre' => 'Une commande à passer ?',
            'texte' => '<p>Dites-nous l’occasion, la date et le budget : nous proposons une '
                .'composition et un prix avant de commencer quoi que ce soit.</p>',
            'image' => $img['nantes'],
            'alt' => $img['nantes']['description'],
            'boutonTexte' => 'Nous écrire',
            'boutonLien' => '/nous-ecrire',
            'boutonSecondaireTexte' => 'Voir les prestations',
            'boutonSecondaireLien' => '/prestations',
        ],
    ],
];

$pages['creations'] = [
    'titre' => 'Nos créations',
    'slug' => 'creations',
    'seoTitre' => 'Nos créations florales — Atelier de Bloom',
    'seoDescription' => 'Bouquets, compositions et créations sur mesure de l’Atelier de Bloom, '
        .'composés à la main à Nantes avec des fleurs de saison.',
    '_state' => 1,
    'blocs' => [

        // Pas de bandeau ici : le titre de la page est alors affiché au-dessus
        // des sections, et reste le seul <h1>.
        [
            'type' => 'texte-image',
            'titre' => 'Des fleurs de saison, coupées le matin',
            'texte' => '<p>Nous ne tenons pas de catalogue. Ce qui suit montre ce qui est sorti '
                .'de l’atelier ces dernières semaines : la même commande, passée en octobre ou '
                .'en avril, ne donnera pas le même bouquet.</p>'
                .'<h3>Choisir sans photo</h3>'
                .'<p>Indiquez plutôt des couleurs, une occasion et un budget. Nous composons '
                .'avec ce qui est beau ce jour-là, et nous envoyons une photo avant le retrait '
                .'si vous le souhaitez.</p>',
            'image' => $img['bouquet'],
            'alt' => $img['bouquet']['description'],
            'positionImage' => 'gauche',
        ],

        [
            'type' => 'galerie',
            'titre' => 'Le carnet de l’atelier',
            'introduction' => 'Six créations, de la boutique au bouquet fini.',
            'colonnes' => 'trois',
            'images' => [
                [
                    'image' => $img['bouquet'],
                    'alt' => $img['bouquet']['description'],
                    'legende' => 'Pivoines, renoncules et feuillage d’eucalyptus',
                ],
                [
                    'image' => $img['kraft'],
                    'alt' => $img['kraft']['description'],
                    'legende' => 'Composition à offrir, prête à emporter',
                ],
                [
                    'image' => $img['atelier'],
                    'alt' => $img['atelier']['description'],
                    'legende' => 'Le tri des tiges, avant composition',
                ],
                [
                    'image' => $img['devanture'],
                    'alt' => $img['devanture']['description'],
                    'legende' => 'La devanture au printemps',
                ],
                [
                    'image' => $img['nantes'],
                    'alt' => $img['nantes']['description'],
                    'legende' => 'Nos livraisons, dans Nantes intra-muros',
                ],
                [
                    'image' => $img['embleme'],
                    'alt' => $img['embleme']['description'],
                    'legende' => 'La marque, dessinée à la main',
                ],
            ],
        ],

        [
            'type' => 'etapes',
            'titre' => 'Comment se passe une commande',
            'introduction' => 'De la première demande au bouquet posé sur la table.',
            'etapes' => [
                [
                    'titre' => 'Vous nous dites',
                    'texte' => 'L’occasion, la date, les couleurs que vous aimez et le budget. '
                        .'Par téléphone, en boutique ou par le formulaire.',
                ],
                [
                    'titre' => 'Nous proposons',
                    'texte' => 'Une composition et un prix, sous deux jours ouvrés. Rien n’est '
                        .'engagé tant que vous n’avez pas répondu.',
                ],
                [
                    'titre' => 'Nous composons',
                    'texte' => 'Le matin du retrait, avec les fleurs arrivées la veille au soir '
                        .'de chez nos producteurs.',
                ],
                [
                    'titre' => 'Vous récupérez',
                    'texte' => 'En boutique, rue Kléber, ou livré dans Nantes le matin même '
                        .'pour les commandes passées l’avant-veille.',
                ],
            ],
        ],

        [
            'type' => 'appel-action',
            'titre' => 'Une idée en tête ?',
            'texte' => '<p>Décrivez-la en quelques lignes. Nous répondons sous deux jours ouvrés, '
                .'avec une proposition chiffrée.</p>',
            'image' => $img['kraft'],
            'alt' => $img['kraft']['description'],
            'boutonTexte' => 'Nous écrire',
            'boutonLien' => '/nous-ecrire',
        ],
    ],
];

$pages['prestations'] = [
    'titre' => 'Nos prestations',
    'slug' => 'prestations',
    'seoTitre' => 'Prestations : bouquets, mariages, abonnements',
    'seoDescription' => 'Bouquet du jour, deuil, mariages et cérémonies, abonnements floraux '
        .'pour les entreprises : les prestations de l’Atelier de Bloom, à Nantes.',
    '_state' => 1,
    'blocs' => [

        [
            'type' => 'prestations',
            'titre' => 'Quatre façons de faire appel à l’atelier',
            'introduction' => 'Les prix indiqués sont ceux d’une composition courante. Une '
                .'demande particulière est chiffrée avant d’être engagée.',
            'prestations' => [
                [
                    'nom' => 'Bouquet du jour',
                    'prix' => 'à partir de 22 €',
                    'resume' => 'Composé le matin avec les arrivages. Trois tailles, du bouquet '
                        .'de la semaine à la brassée. Livraison dans Nantes intra-muros pour 8 €.',
                    'image' => $img['bouquet'],
                    'alt' => $img['bouquet']['description'],
                    'lienTexte' => 'Commander',
                    'lien' => '/nous-ecrire',
                ],
                [
                    'nom' => 'Mariages et cérémonies',
                    'prix' => 'sur devis, à partir de 450 €',
                    'resume' => 'Bouquet de mariée, boutonnières, centres de table, arche. Un '
                        .'rendez-vous d’une heure à l’atelier, puis une proposition chiffrée '
                        .'sous une semaine.',
                    'image' => $img['kraft'],
                    'alt' => $img['kraft']['description'],
                    'lienTexte' => 'Demander un rendez-vous',
                    'lien' => '/nous-ecrire',
                ],
                [
                    'nom' => 'Abonnement bureaux',
                    'prix' => 'à partir de 45 € par semaine',
                    'resume' => 'Une composition renouvelée chaque semaine, livrée et installée. '
                        .'Sans engagement de durée, suspendue le temps des congés.',
                    'image' => $img['atelier'],
                    'alt' => $img['atelier']['description'],
                    'lienTexte' => 'Demander un devis',
                    'lien' => '/nous-ecrire',
                ],
                [
                    'nom' => 'Deuil',
                    'prix' => 'à partir de 80 €',
                    'resume' => 'Coussins, gerbes et raquettes, préparés dans la journée. Nous '
                        .'nous chargeons de la livraison au funérarium ou à l’église.',
                    'image' => $img['devanture'],
                    'alt' => $img['devanture']['description'],
                    'lienTexte' => 'Nous appeler',
                    'lien' => '/nous-ecrire',
                ],
            ],
        ],

        [
            'type' => 'etapes',
            'titre' => 'Les délais',
            'introduction' => 'Ce qu’il faut prévoir, selon ce que vous demandez.',
            'etapes' => [
                [
                    'titre' => 'Le jour même',
                    'texte' => 'Bouquet du jour et compositions de deuil, dans la limite des '
                        .'arrivages du matin.',
                ],
                [
                    'titre' => 'Deux jours',
                    'texte' => 'Toute composition de plus de trente tiges, et toute livraison '
                        .'à l’extérieur de Nantes.',
                ],
                [
                    'titre' => 'Deux mois',
                    'texte' => 'Mariages et cérémonies, le temps de réserver les variétés '
                        .'auprès des producteurs.',
                ],
            ],
        ],

        $blocTemoignages,

        $blocContact,
    ],
];

$pages['nous-ecrire'] = [
    'titre' => 'Nous écrire',
    'slug' => 'nous-ecrire',
    'seoTitre' => 'Nous écrire — Atelier de Bloom, Nantes',
    'seoDescription' => 'Poser une question, demander un devis ou commander un bouquet à '
        .'l’Atelier de Bloom, rue Kléber à Nantes.',
    '_state' => 1,
    'blocs' => [
        [
            'type' => 'formulaire',
            'titre' => 'Votre message',
            'texte' => '<p>Une commande, une question, un mariage à préparer ? Écrivez-nous en '
                .'quelques lignes : l’occasion, la date et le budget suffisent pour commencer. '
                .'Nous répondons sous deux jours ouvrés.</p>',
        ],
        $blocContact,
    ],
];

foreach ($pages as $slug => $data) {

    $existing = $content->item('pages', ['slug' => $slug]);

    // Réécrire la page à l'identique de ce fichier, en gardant son identifiant
    // pour que les entrées de menu qui la désignent continuent de pointer
    // dessus.
    if (is_array($existing)) {
        $data['_id'] = $existing['_id'];
    }

    $content->saveItem('pages', $data);

    step("Page « {$data['titre']} » écrite.");
}

// La page « Services » du contenu livré n'a plus d'objet : ses deux sections
// sont reprises, en mieux, par « Nos prestations ».
if ($content->item('pages', ['slug' => 'services']) !== null) {
    $content->remove('pages', ['slug' => 'services']);
    step('Page « Services » du contenu de démonstration livré retirée.');
}

// ── 5. Le menu ────────────────────────────────────────────────────────────

$entrees = [];

foreach ([
    'accueil' => 'Accueil',
    'creations' => 'Créations',
    'prestations' => 'Prestations',
    'nous-ecrire' => 'Nous écrire',
] as $slug => $libelle) {

    $page = $content->item('pages', ['slug' => $slug]);

    if ($page !== null) {
        $entrees[] = [
            'libelle' => $libelle,
            'page' => ['_model' => 'pages', '_id' => $page['_id']],
        ];
    }
}

$content->saveItem('menu', array_merge($content->item('menu') ?? [], ['entrees' => $entrees]));

step(count($entrees).' entrées de menu écrites.');

// ── 6. Les actualités ─────────────────────────────────────────────────────

$articles = [
    'pivoines-de-saison' => [
        'titre' => 'Les pivoines sont arrivées',
        'slug' => 'pivoines-de-saison',
        'date' => '2026-05-12',
        'categorie' => 'actualite',
        'resume' => 'Six semaines, pas une de plus : les pivoines de nos producteurs du '
            .'Vignoble nantais sont en boutique jusqu’à la mi-juin.',
        'image' => $img['bouquet'],
        'alt' => $img['bouquet']['description'],
        'contenu' => '<p>Les premières caisses sont arrivées lundi. Elles viennent de deux '
            .'producteurs installés à Vertou et à Monnières, à vingt minutes de la boutique.</p>'
            .'<h2>Ce que nous en faisons</h2>'
            .'<p>En bouquet rond, seules ou avec des renoncules et un feuillage clair. Elles '
            .'entrent aussi dans les compositions de mariage jusqu’à la mi-juin.</p>'
            .'<h2>Les faire durer</h2>'
            .'<p>Recoupez les tiges en biseau tous les deux jours et changez l’eau. Une pivoine '
            .'achetée en bouton s’ouvre en trois jours et tient une semaine de plus.</p>',
        '_state' => 1,
    ],
    'portes-ouvertes' => [
        'titre' => 'Portes ouvertes à l’atelier',
        'slug' => 'portes-ouvertes',
        'date' => '2026-06-06',
        'categorie' => 'evenement',
        'resume' => 'Deux jours pour visiter l’atelier, rencontrer nos producteurs et repartir '
            .'avec un bouquet composé sous vos yeux.',
        'image' => $img['atelier'],
        'alt' => $img['atelier']['description'],
        'contenu' => '<p>L’atelier ouvre ses portes le premier week-end de juin, de 10 h à 18 h. '
            .'Deux de nos producteurs de Loire-Atlantique seront présents.</p>'
            .'<h2>Au programme</h2>'
            .'<p>Démonstrations de composition à 11 h et à 16 h, vente de fleurs coupées à la '
            .'tige, et conseils d’entretien.</p>'
            .'<h2>Venir</h2>'
            .'<p>8 rue Kléber, à cinq minutes à pied de la place Graslin. Entrée libre, sans '
            .'inscription.</p>',
        '_state' => 1,
    ],
    'faire-durer-un-bouquet' => [
        'titre' => 'Faire durer un bouquet, vraiment',
        'slug' => 'faire-durer-un-bouquet',
        'date' => '2026-04-18',
        'categorie' => 'conseil',
        'resume' => 'Quatre gestes qui changent tout, et deux habitudes répandues qui abîment '
            .'les fleurs plus qu’elles ne les tiennent.',
        'image' => $img['kraft'],
        'alt' => $img['kraft']['description'],
        'contenu' => '<p>Un bouquet bien tenu double sa durée de vie. Rien de compliqué, mais '
            .'quatre gestes à faire au bon moment.</p>'
            .'<h2>Les quatre gestes</h2>'
            .'<p>Recouper les tiges en biseau à l’arrivée, retirer les feuilles qui trempent, '
            .'changer l’eau tous les deux jours, et tenir le vase loin d’une source de chaleur '
            .'ou d’une corbeille de fruits.</p>'
            .'<h2>Deux idées à oublier</h2>'
            .'<p>Le sucre et l’eau de Javel ne remplacent pas un rinçage du vase. Et l’aspirine '
            .'n’a jamais fait tenir une rose : c’est l’eau propre qui compte.</p>',
        '_state' => 1,
    ],
];

foreach ($articles as $slug => $data) {

    $existing = $content->item('articles', ['slug' => $slug]);

    if (is_array($existing)) {
        $data['_id'] = $existing['_id'];
    }

    $content->saveItem('articles', $data);

    step("Actualité « {$data['titre']} » écrite.");
}

// L'actualité livrée avec le socle porte le même sujet que nos portes
// ouvertes : la laisser ferait double emploi dans la liste.
if ($content->item('articles', ['slug' => 'portes-ouvertes-de-printemps']) !== null) {
    $content->remove('articles', ['slug' => 'portes-ouvertes-de-printemps']);
    step('Actualité de démonstration livrée retirée.');
}

// ── 7. Les mentions légales ───────────────────────────────────────────────
//
// Le site écrit lui-même /mentions-legales et /confidentialite à partir de
// l'identité et de ce singleton : rien n'est saisi deux fois.

$content->saveItem('legal', array_merge($content->item('legal') ?? [], [
    'directeurPublication' => 'Camille Roussel',
    'formeJuridique' => 'SARL au capital de 5 000 €',
    'hebergeurNom' => 'Hébergeur de démonstration',
    'hebergeurAdresse' => "1 rue de l’Exemple\n44000 Nantes",
    'dureeConservation' => 'douze mois',
]));

step('Mentions légales renseignées.');

// ── 8. Remettre le site à jour ────────────────────────────────────────────
//
// Les pages déjà rendues portent l'ancien contenu, et couleurs.css l'ancienne
// couleur : les deux sont réécrits à la prochaine visite.

require_once "{$root}/src/Cache/PageCache.php";
require_once "{$root}/src/View/Colours.php";

(new App\Cache\PageCache("{$root}/public/cache"))->clear();
(new App\View\Colours("{$root}/public/assets/css/couleurs.css"))->forget();

step('Cache des pages vidé et couleurs à réécrire.');

echo "\n  Site de démonstration prêt : http://localhost:8080/\n";
echo "  Administration            : http://localhost:8090/\n\n";
