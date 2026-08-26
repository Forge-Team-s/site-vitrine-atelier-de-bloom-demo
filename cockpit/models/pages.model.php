<?php

/**
 * Site pages.
 *
 * Fixed structure: the customer fills it in, never edits it. Installed to
 * public/admin/storage/content/ by bin/install-cockpit.php.
 *
 * A page is a list of blocks. One block type = one entry in the `type` list,
 * a few fields shown by a `condition`, and a Twig partial of the same name in
 * templates/blocs/. Adding a type is documented in the README.
 *
 * Publication is not a field: Cockpit tracks it natively on `_state`
 * (1 published, 0 unpublished, -1 archived) with its own button in the admin.
 * The public site only ever serves items with `_state` = 1.
 */

// Headings only, plus links and lists: the page title is the only level-one
// heading, and the toolbar must not offer anything that would break that.
$toolbar = 'format | link | listBullet listOrdered';

// Conditions are evaluated in the admin against the block being edited.
$isHero = "data.type === 'hero'";
$isTexteImage = "data.type === 'texte-image'";
$isContact = "data.type === 'contact'";
$isFormulaire = "data.type === 'formulaire'";
$isTemoignages = "data.type === 'temoignages'";

// Types propres à ce site — voir templates-client/blocs/.
$isGalerie = "data.type === 'galerie'";
$isPrestations = "data.type === 'prestations'";
$isEtapes = "data.type === 'etapes'";
$isAppelAction = "data.type === 'appel-action'";

$hasImage = "['hero', 'texte-image', 'appel-action'].includes(data.type)";
$hasTexte = "['texte-image', 'contact', 'formulaire', 'appel-action'].includes(data.type)";
$hasBouton = "['hero', 'appel-action'].includes(data.type)";

// Une seule phrase d'introduction, partagée par les sections qui ouvrent sur
// une liste. Déclarer deux champs du même nom au même niveau n'en garderait
// qu'un : la condition s'élargit, le champ reste unique.
$hasIntroduction = "['temoignages', 'galerie', 'prestations', 'etapes'].includes(data.type)";

return [
    'name' => 'pages',
    'label' => 'Pages',
    'info' => 'Les pages du site.',
    'type' => 'collection',
    'group' => null,
    'preview' => [],
    'meta' => [
        'unique' => ['slug'],
    ],
    '_created' => 1754179200,
    '_modified' => 1754352000,

    'fields' => [
        [
            'name' => 'titre',
            'type' => 'text',
            'label' => 'Titre de la page',
            'info' => 'Apparaît en titre principal en haut de la page et dans le menu.',
            'required' => true,
            'localize' => false,
            'multiple' => false,
            'group' => 'Contenu',
            'width' => '1-1',
            'opts' => [],
        ],
        [
            'name' => 'slug',
            'type' => 'text',
            'label' => 'Adresse de la page',
            'info' => "Termine l'adresse de la page : « services » donne /services. Lettres minuscules et tirets.",
            'required' => true,
            'localize' => false,
            'multiple' => false,
            'group' => 'Contenu',
            'width' => '1-1',
            'opts' => ['placeholder' => 'services'],
        ],

        // ── Blocs ─────────────────────────────────────────────────────────
        [
            'name' => 'blocs',
            'type' => 'set',
            'label' => 'Contenu de la page',
            'info' => 'Les sections affichées sous le titre, dans cet ordre.',
            'required' => false,
            'localize' => false,
            'multiple' => true,
            'group' => 'Contenu',
            'width' => '1-1',
            'opts' => [
                'display' => '${data.titre || data.type || \'Section\'}',
                'fields' => [
                    [
                        'name' => 'type',
                        'type' => 'select',
                        'label' => 'Type de section',
                        'required' => true,
                        'width' => '1-1',
                        'opts' => [
                            'options' => [
                                ['value' => 'hero', 'label' => 'Bandeau d’ouverture'],
                                ['value' => 'texte-image', 'label' => 'Texte et image'],
                                ['value' => 'contact', 'label' => 'Coordonnées'],
                                ['value' => 'formulaire', 'label' => 'Formulaire de contact'],
                                ['value' => 'temoignages', 'label' => 'Témoignages'],
                                ['value' => 'galerie', 'label' => 'Galerie de créations'],
                                ['value' => 'prestations', 'label' => 'Prestations'],
                                ['value' => 'etapes', 'label' => 'Étapes d’une commande'],
                                ['value' => 'appel-action', 'label' => 'Invitation à commander'],
                            ],
                        ],
                    ],
                    [
                        'name' => 'titre',
                        'type' => 'text',
                        'label' => 'Titre de la section',
                        'info' => 'Apparaît en tête de la section.',
                        'width' => '1-1',
                        'opts' => [],
                    ],
                    [
                        'name' => 'accroche',
                        'type' => 'text',
                        'label' => 'Accroche',
                        'info' => 'La phrase affichée sous le titre du bandeau.',
                        'width' => '1-1',
                        'condition' => $isHero,
                        'opts' => ['multiline' => true, 'maxlength' => 200],
                    ],
                    [
                        'name' => 'texte',
                        'type' => 'wysiwyg',
                        'label' => 'Texte',
                        'info' => 'Le corps de la section.',
                        'width' => '1-1',
                        'condition' => $hasTexte,
                        'opts' => ['toolbar' => $toolbar],
                    ],
                    [
                        'name' => 'image',
                        'type' => 'asset',
                        'label' => 'Image',
                        'info' => 'Apparaît dans la section. Format paysage conseillé.',
                        'width' => '1-2',
                        'condition' => $hasImage,
                        'opts' => ['filter' => ['type' => 'image']],
                    ],
                    [
                        'name' => 'alt',
                        'type' => 'text',
                        'label' => 'Description de l’image',
                        'info' => 'Lue à voix haute par les lecteurs d’écran, et affichée si l’image ne charge pas. Décrire ce que l’on voit.',
                        'width' => '1-2',
                        'condition' => $hasImage,
                        'opts' => ['maxlength' => 150],
                    ],
                    [
                        'name' => 'positionImage',
                        'type' => 'select',
                        'label' => 'Position de l’image',
                        'width' => '1-2',
                        'condition' => $isTexteImage,
                        'opts' => [
                            'options' => [
                                ['value' => 'droite', 'label' => 'À droite du texte'],
                                ['value' => 'gauche', 'label' => 'À gauche du texte'],
                            ],
                        ],
                    ],
                    [
                        'name' => 'boutonTexte',
                        'type' => 'text',
                        'label' => 'Texte du bouton',
                        'info' => 'Laisser vide pour ne pas afficher de bouton.',
                        'width' => '1-2',
                        'condition' => $hasBouton,
                        'opts' => ['maxlength' => 40],
                    ],
                    [
                        'name' => 'boutonLien',
                        'type' => 'text',
                        'label' => 'Adresse du bouton',
                        'info' => 'Adresse d’une page du site, par exemple /services.',
                        'width' => '1-2',
                        'condition' => $hasBouton,
                        'opts' => ['placeholder' => '/services'],
                    ],
                    // ── Témoignages ─────────────────────────────────────
                    // Exemple commenté d'un type de section : voir le partial
                    // templates/blocs/temoignages.html.twig et
                    // docs/guide-integration.md.
                    [
                        'name' => 'introduction',
                        'type' => 'text',
                        'label' => 'Phrase d’introduction',
                        'info' => 'Apparaît sous le titre de la section, avant la liste.',
                        'width' => '1-1',
                        'condition' => $hasIntroduction,
                        'opts' => ['multiline' => true, 'maxlength' => 200],
                    ],
                    [
                        'name' => 'temoignages',
                        'type' => 'set',
                        'label' => 'Témoignages',
                        'info' => 'Chaque entrée devient un témoignage affiché dans la section.',
                        'multiple' => true,
                        'width' => '1-1',
                        'condition' => $isTemoignages,
                        'opts' => [
                            'display' => '${data.auteur || \'Témoignage\'}',
                            'fields' => [
                                [
                                    'name' => 'citation',
                                    'type' => 'text',
                                    'label' => 'Ce que dit la personne',
                                    'required' => true,
                                    'width' => '1-1',
                                    'opts' => ['multiline' => true, 'maxlength' => 400],
                                ],
                                [
                                    'name' => 'auteur',
                                    'type' => 'text',
                                    'label' => 'Nom',
                                    'width' => '1-2',
                                    'opts' => [],
                                ],
                                [
                                    'name' => 'fonction',
                                    'type' => 'text',
                                    'label' => 'Fonction ou ville',
                                    'info' => 'Affichée sous le nom, en plus discret.',
                                    'width' => '1-2',
                                    'opts' => [],
                                ],
                                [
                                    'name' => 'portrait',
                                    'type' => 'asset',
                                    'label' => 'Portrait',
                                    'info' => 'Facultatif. Image carrée conseillée.',
                                    'width' => '1-2',
                                    'opts' => ['filter' => ['type' => 'image']],
                                ],
                                [
                                    'name' => 'alt',
                                    'type' => 'text',
                                    'label' => 'Description du portrait',
                                    'info' => 'Obligatoire dès qu’un portrait est choisi.',
                                    'width' => '1-2',
                                    'opts' => ['maxlength' => 150],
                                ],
                            ],
                        ],
                    ],

                    // ── Galerie de créations ────────────────────────────
                    // Propre à ce site : templates-client/blocs/galerie.html.twig.
                    [
                        'name' => 'colonnes',
                        'type' => 'select',
                        'label' => 'Images par ligne',
                        'info' => 'Sur un écran large. Sur un téléphone, les images se suivent quoi qu’il arrive.',
                        'width' => '1-2',
                        'condition' => $isGalerie,
                        'opts' => [
                            'default' => 'trois',
                            'options' => [
                                ['value' => 'deux', 'label' => 'Deux'],
                                ['value' => 'trois', 'label' => 'Trois'],
                                ['value' => 'quatre', 'label' => 'Quatre'],
                            ],
                        ],
                    ],
                    [
                        'name' => 'images',
                        'type' => 'set',
                        'label' => 'Images de la galerie',
                        'info' => 'Chaque entrée devient une vignette, dans cet ordre.',
                        'multiple' => true,
                        'width' => '1-1',
                        'condition' => $isGalerie,
                        'opts' => [
                            'display' => '${data.legende || data.alt || \'Image\'}',
                            'fields' => [
                                // « image » et « alt » vont ensemble et portent
                                // ces noms-là : c'est ainsi que l'administration
                                // reconnaît une image laissée sans description.
                                [
                                    'name' => 'image',
                                    'type' => 'asset',
                                    'label' => 'Image',
                                    'required' => true,
                                    'width' => '1-2',
                                    'opts' => ['filter' => ['type' => 'image']],
                                ],
                                [
                                    'name' => 'alt',
                                    'type' => 'text',
                                    'label' => 'Description de l’image',
                                    'info' => 'Lue à voix haute par les lecteurs d’écran. Décrire ce que l’on voit.',
                                    'width' => '1-2',
                                    'opts' => ['maxlength' => 150],
                                ],
                                [
                                    'name' => 'legende',
                                    'type' => 'text',
                                    'label' => 'Légende',
                                    'info' => 'Facultative. Affichée sous l’image, à la vue de tous.',
                                    'width' => '1-1',
                                    'opts' => ['maxlength' => 90],
                                ],
                            ],
                        ],
                    ],

                    // ── Prestations ─────────────────────────────────────
                    // Propre à ce site : templates-client/blocs/prestations.html.twig.
                    [
                        'name' => 'prestations',
                        'type' => 'set',
                        'label' => 'Prestations',
                        'info' => 'Chaque entrée devient une carte affichée dans la section.',
                        'multiple' => true,
                        'width' => '1-1',
                        'condition' => $isPrestations,
                        'opts' => [
                            'display' => '${data.nom || \'Prestation\'}',
                            'fields' => [
                                [
                                    'name' => 'nom',
                                    'type' => 'text',
                                    'label' => 'Nom de la prestation',
                                    'required' => true,
                                    'width' => '1-2',
                                    'opts' => ['maxlength' => 60],
                                ],
                                [
                                    'name' => 'prix',
                                    'type' => 'text',
                                    'label' => 'Prix',
                                    'info' => 'Texte libre : « à partir de 28 € » se lit mieux qu’un nombre seul. Laisser vide pour ne rien afficher.',
                                    'width' => '1-2',
                                    'opts' => ['maxlength' => 40],
                                ],
                                [
                                    'name' => 'resume',
                                    'type' => 'text',
                                    'label' => 'Description',
                                    'width' => '1-1',
                                    'opts' => ['multiline' => true, 'maxlength' => 300],
                                ],
                                [
                                    'name' => 'image',
                                    'type' => 'asset',
                                    'label' => 'Image',
                                    'info' => 'Facultative. Format carré ou paysage conseillé.',
                                    'width' => '1-2',
                                    'opts' => ['filter' => ['type' => 'image']],
                                ],
                                [
                                    'name' => 'alt',
                                    'type' => 'text',
                                    'label' => 'Description de l’image',
                                    'info' => 'Obligatoire dès qu’une image est choisie.',
                                    'width' => '1-2',
                                    'opts' => ['maxlength' => 150],
                                ],
                                [
                                    'name' => 'lienTexte',
                                    'type' => 'text',
                                    'label' => 'Texte du lien',
                                    'info' => 'Laisser vide pour ne pas afficher de lien.',
                                    'width' => '1-2',
                                    'opts' => ['maxlength' => 40, 'placeholder' => 'En savoir plus'],
                                ],
                                [
                                    'name' => 'lien',
                                    'type' => 'text',
                                    'label' => 'Adresse du lien',
                                    'info' => 'Adresse d’une page du site, par exemple /nous-ecrire.',
                                    'width' => '1-2',
                                    'opts' => ['placeholder' => '/nous-ecrire'],
                                ],
                            ],
                        ],
                    ],

                    // ── Étapes d'une commande ───────────────────────────
                    // Propre à ce site : templates-client/blocs/etapes.html.twig.
                    [
                        'name' => 'etapes',
                        'type' => 'set',
                        'label' => 'Étapes',
                        'info' => 'Numérotées automatiquement, dans l’ordre où elles sont rangées ici.',
                        'multiple' => true,
                        'width' => '1-1',
                        'condition' => $isEtapes,
                        'opts' => [
                            'display' => '${data.titre || \'Étape\'}',
                            'fields' => [
                                [
                                    'name' => 'titre',
                                    'type' => 'text',
                                    'label' => 'Titre de l’étape',
                                    'required' => true,
                                    'width' => '1-1',
                                    'opts' => ['maxlength' => 60],
                                ],
                                [
                                    'name' => 'texte',
                                    'type' => 'text',
                                    'label' => 'Ce qui se passe',
                                    'width' => '1-1',
                                    'opts' => ['multiline' => true, 'maxlength' => 300],
                                ],
                            ],
                        ],
                    ],

                    // ── Invitation à commander ──────────────────────────
                    // Propre à ce site : templates-client/blocs/appel-action.html.twig.
                    // Son texte, son image et son premier bouton sont les champs
                    // communs, dont les conditions ont été élargies plus haut.
                    [
                        'name' => 'boutonSecondaireTexte',
                        'type' => 'text',
                        'label' => 'Texte du second bouton',
                        'info' => 'Facultatif. Laisser vide pour n’afficher qu’un seul bouton.',
                        'width' => '1-2',
                        'condition' => $isAppelAction,
                        'opts' => ['maxlength' => 40],
                    ],
                    [
                        'name' => 'boutonSecondaireLien',
                        'type' => 'text',
                        'label' => 'Adresse du second bouton',
                        'width' => '1-2',
                        'condition' => $isAppelAction,
                        'opts' => ['placeholder' => '/creations'],
                    ],

                    [
                        'name' => 'afficherHoraires',
                        'type' => 'boolean',
                        'label' => 'Afficher les horaires',
                        'info' => 'Les horaires saisis dans « Identité du site » apparaissent sous les coordonnées.',
                        'width' => '1-1',
                        'condition' => $isContact,
                        'opts' => ['default' => true],
                    ],
                ],
            ],
        ],

        // ── Référencement ─────────────────────────────────────────────────
        [
            'name' => 'seoTitre',
            'type' => 'text',
            'label' => 'Titre dans les résultats de recherche',
            'info' => "Apparaît en bleu dans Google et dans l'onglet du navigateur. Vide, le titre de la page est repris.",
            'required' => false,
            'localize' => false,
            'multiple' => false,
            'group' => 'Référencement',
            'width' => '1-1',
            'opts' => ['maxlength' => 60, 'showCount' => true],
        ],
        [
            'name' => 'seoDescription',
            'type' => 'text',
            'label' => 'Résumé dans les résultats de recherche',
            'info' => 'Apparaît sous le titre dans Google. Environ 155 caractères.',
            'required' => false,
            'localize' => false,
            'multiple' => false,
            'group' => 'Référencement',
            'width' => '1-1',
            'opts' => ['multiline' => true, 'maxlength' => 160, 'showCount' => true],
        ],
    ],
];
