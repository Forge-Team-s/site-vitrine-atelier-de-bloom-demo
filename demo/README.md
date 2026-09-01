# Démonstration — Atelier de Bloom

Site de démonstration monté sur le socle : un fleuriste imaginaire installé rue Kléber, à
Nantes. Il sert à montrer ce que le socle produit une fois habillé, et à éprouver les types
de section propres à un site client.

Tout ce qui est propre à cette démonstration vit hors du socle :

| Chemin | Contenu |
|---|---|
| `templates-client/blocs/*.html.twig` | les quatre types de section créés pour ce site |
| `public/assets/css/client.css` | la maquette |
| `demo/seed-atelier-de-bloom.php` | le contenu : identité, pages, menu, actualités |
| `public/assets/img/*.jpg` | les six illustrations, sources des médias |

`cockpit/models/pages.model.php` est le seul fichier partagé modifié — comme le prévoit
[docs/guide-integration.md](../docs/guide-integration.md).

## Monter la démonstration

```bash
composer install
php bin/install-cockpit.php
cp .env.example .env
php bin/cockpit-init.php                  # note les mots de passe affichés
php demo/seed-atelier-de-bloom.php        # contenu de la démonstration
composer serve                            # site  → http://localhost:8080
composer serve-admin                      # admin → http://localhost:8090
```

Le script de contenu est **réexécutable** : il réécrit chaque élément tel qu'il est décrit
dans le fichier, le dépôt faisant foi. Ce qui a été saisi à la main dans l'administration
est donc perdu — c'est voulu pour une démonstration, et à ne pas transposer tel quel sur un
site en production.

## Les quatre types de section créés

Un fichier dans `templates-client/blocs/` porte le nom du type ; ses champs sont déclarés
dans `cockpit/models/pages.model.php` avec une condition `data.type === '…'`. Les deux vont
ensemble, et `composer test` refuse qu'ils se séparent.

| Type | Rôle | Champs propres |
|---|---|---|
| `galerie` | les créations, en grille | introduction, images par ligne, liste d'images légendées |
| `prestations` | ce que l'atelier propose, en cartes | introduction, liste : nom, prix, description, image, lien |
| `etapes` | le déroulé d'une commande, numéroté | introduction, liste : titre, texte |
| `appel-action` | l'invitation posée en fin de page | texte, image, deux boutons |

Trois d'entre eux réutilisent des champs déjà là plutôt que d'en déclarer de nouveaux :
`introduction` est partagée par les quatre sections qui ouvrent sur une liste, et
`appel-action` reprend le texte, l'image et le bouton du bandeau. Un champ déclaré deux fois
au même niveau n'en laisserait qu'un — c'est la **condition** qui s'élargit, jamais le champ
qui se duplique.

Ce qu'ils illustrent, au-delà de leur contenu :

- **Les numéros d'étape sont dessinés par le CSS**, avec un compteur. La balise `<ol>` porte
  déjà l'ordre : un numéro écrit dans le gabarit serait annoncé deux fois.
- **Le nom de la prestation complète l'intitulé de son lien**, masqué à l'œil mais lu par
  les lecteurs d'écran : hors contexte, quatre liens « Voir le détail » ne disent rien.
- **Les titres de carte se calculent**, `titre ? niveau + 1 : 2`, pour qu'aucune section ne
  saute un niveau de titre selon l'endroit où le client la pose.
- **Le bandeau d'ouverture n'a pas été recopié** : les illustrations sont presque carrées et
  s'accommodent mal d'une pleine largeur, ce que `client.css` corrige en les posant à côté
  du texte. Le gabarit livré reste intact, donc il continue de recevoir les corrections du
  socle.

## Les illustrations

Six aquarelles, réutilisées d'une section à l'autre. Le script les enregistre comme médias
de Cockpit, ce qui déclenche la fabrication des copies allégées : les servir depuis
`public/assets/img` court-circuiterait ce mécanisme.

**Elles sont petites** — de 445 à 509 pixels de large. Le socle ne fabrique jamais de copie
plus large que l'original : deux d'entre elles, sous les 480 pixels, n'ont donc aucune copie
et sont servies telles quelles, en JPEG. La maquette n'affiche aucune image au-delà de sa
largeur native, mais un écran à forte densité de pixels le verra. Des sources plus larges,
autour de 1600 pixels, lèveraient les deux réserves d'un coup.

Pour la même raison, l'**image de partage** de l'identité est laissée vide : aucune des six
n'atteint les 1200 × 630 pixels attendus par les réseaux sociaux. Le site reprend alors
l'image de la page, puis le logo — le repli prévu par le socle.

## Ce qui est faux, et volontairement

Un site de démonstration ne doit joindre personne pour de vrai.

| Donnée | Valeur | Pourquoi |
|---|---|---|
| Téléphone | `02 61 91 00 42` | plage réservée à la fiction par l'ARCEP : elle ne sonne nulle part |
| Adresse e-mail | `bonjour@atelier-de-bloom.test` | `.test` est un domaine réservé, jamais routé |
| SIRET | `000 000 000 00000` | manifestement fictif |
| Hébergeur | « Hébergeur de démonstration » | à remplacer sur une vraie mise en ligne |

## Vérifier

```bash
composer test                                            # 234 tests
php bin/verifier-accessibilite.php http://localhost:8080 # sur le HTML servi
php bin/purge-cache.php                                  # après tout changement de gabarit ou de CSS
```

Les dix adresses du site sont conformes, sans réserve.

Deux défauts relevés en montant cette démonstration ont été corrigés **dans le socle**, puis
repris ici — les fichiers concernés sont identiques à `socle/main`, donc ils fusionneront sans
conflit :

| Défaut | Correction |
|---|---|
| Chaque fiche d'actualité revendiquait `/actualites` au lieu de son adresse | socle PR #26 — `forPage()` accepte un chemin distinct du slug de navigation |
| `public/assets/css/` manquait aux droits d'écriture de l'installation | socle PR #27 — le vérificateur contrôle désormais que `couleurs.css` répond 200 |

Le premier est décrit en détail dans
[correctif-socle-canonique-actualites.md](correctif-socle-canonique-actualites.md), conservé
comme trace de l'analyse.

## Reste à faire pour une vraie mise en ligne

- Remplacer l'identité, les coordonnées et l'hébergeur par les vrais.
- Fournir des illustrations plus larges, et une image de partage en 1200 × 630.
- Activer la double authentification sur le compte d'administration.
- Renseigner `SITE_URL` avec le domaine réel, puis relancer le vérificateur : c'est le seul
  contrôle qui attrape une adresse revendiquée fausse.

Poser le site sur un hébergement, et le tenir à jour ensuite, est décrit dans
[deploiement.md](deploiement.md) : la voie du dépôt Git, celle du FTP quand l'hébergement ne
laisse pas le choix, et le passage de la seconde à la première sans couper le service.
