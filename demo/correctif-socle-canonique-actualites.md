# Le canonique des fiches d'actualité — corrigé dans le socle

> **Corrigé le 26/08/2026.** Ce document a servi à remonter le défaut au socle ; il y a été
> corrigé par la PR #26 (`35191d7`), puis repris ici. `src/Controller/NewsAction.php`,
> `src/View/ViewContext.php` et `tests/Site/ContextePageTest.php` sont désormais identiques à
> `socle/main`. Vérifié sur le HTML servi : une fiche revendique bien sa propre adresse, et
> l'entrée de menu « Actualités » garde son `aria-current`.
>
> Conservé comme trace de l'analyse — la section « Le garde-fou à ajouter avec » explique
> pourquoi aucun test ne voyait le défaut, ce que la correction retenue a réglé en montant
> `ViewContext` avec ses vraies dépendances.

Le correctif touchait `src/`, qui appartient au socle : le corriger directement ici aurait fait
diverger le site et produit un conflit à la prochaine `git merge socle/main`. Il a donc été
décrit pour être appliqué en amont, d'où tous les sites le reçoivent.

## Le défaut

Chaque fiche d'actualité revendique `/actualites` — l'adresse de la liste — au lieu de la
sienne. `bin/verifier-accessibilite.php` le signale sur chaque fiche :

```
/actualites/pivoines-de-saison           1 à corriger
    · adresse revendiquée http://localhost:8080/actualites,
      alors que la page répond sur http://localhost:8080/actualites/pivoines-de-saison
```

Deux conséquences, invisibles à l'écran puisque la page se rend parfaitement :

- les moteurs traitent chaque fiche comme un doublon de la liste, et n'en indexent aucune ;
- tout partage d'une fiche affiche l'aperçu de la liste et y renvoie.

## La cause

`NewsAction::item()` rend une fiche sous le slug de la liste :

```php
// src/Controller/NewsAction.php
$this->twig->render('actualite.html.twig', $this->context->forPage(
    trim(Route::NEWS, '/'),          // ← 'actualites', pour toutes les fiches
    ['article' => $article],
)),
```

`ViewContext::forPage()` se sert de ce slug pour **deux** choses à la fois :

```php
'slug' => $slug,                                                  // état actif du menu
'canonique' => SocialMeta::canonical($this->siteUrl, $this->path($slug)),  // adresse revendiquée
```

Pour la liste, les deux coïncident. Pour une fiche, elles divergent : l'entrée de menu
« Actualités » doit rester active, mais l'adresse revendiquée doit être celle de la fiche.
C'est pour cela qu'il ne suffit pas de passer `'actualites/'.$slug` en argument : le menu
perdrait son `aria-current` sur chaque fiche.

## Le correctif

Séparer le slug de navigation du chemin canonique, en laissant l'un se déduire de l'autre
tant qu'on n'en dit rien — aucun appelant existant n'est à modifier.

### `src/View/ViewContext.php`

```php
    /**
     * @param array<string, mixed> $extra What this page adds to the common set.
     * @param string|null $path Where this page is served, when that is not the
     *                          slug itself: a news item is reached under the
     *                          news slug, but claims an address of its own.
     * @return array<string, mixed>
     */
    public function forPage(string $slug, array $extra = [], ?string $path = null): array
    {
        // …

        return array_merge([
            'site' => $settings,
            'menu' => $this->content->menu(),
            'afficherActualites' => $this->content->hasArticles(),

            // The slug drives the menu's active entry; the path drives the
            // address the page claims. They part company on a news item.
            'slug' => $slug,
            'canonique' => SocialMeta::canonical($this->siteUrl, $path ?? $this->path($slug)),

            // …
        ], $extra);
    }
```

### `src/Controller/NewsAction.php`

```php
        return new Response(
            $this->twig->render('actualite.html.twig', $this->context->forPage(
                trim(Route::NEWS, '/'),
                ['article' => $article],
                Route::NEWS.'/'.$slug,
            )),
            200,
            Response::cacheable('text/html; charset=utf-8'),
        );
```

`$slug` a déjà été confronté à `NewsAction::SLUG` quelques lignes plus haut, et l'article a
été trouvé sous ce slug : le chemin construit est donc bien celui où la page répond.

## Le garde-fou à ajouter avec

Pourquoi ce défaut a tenu : **aucun test n'instancie `ViewContext`** — vérifié, `grep -rn
"new ViewContext" tests/` ne renvoie rien. `MetaSocialesTest` rend bien les gabarits, mais
en lui passant `canonique` à la main : il vérifie ce que le gabarit fait d'une valeur, jamais
d'où cette valeur sort. Le seul contrôle qui voit le défaut est
`bin/verifier-accessibilite.php`, qui ne tourne pas avec `composer test`.

Écrire le test demande donc de monter `ViewContext`, dont les quatre dépendances
(`Repository`, `ContactForm`, `MediaUrls`, `Colours`) sont des classes finales sans
interface. Deux voies, par coût croissant :

**1. Le moins cher — rendre le calcul testable seul.** Sortir de `ViewContext` la décision
« quelle adresse cette page revendique-t-elle », en méthode statique, et la tester
directement. C'est cette décision qui était fausse, pas son câblage :

```php
    #[Test]
    public function une_fiche_dactualite_revendique_sa_propre_adresse(): void
    {
        // Le slug reste celui de la liste, pour que l'entrée de menu
        // « Actualités » garde son état actif ; le chemin, lui, est celui de
        // la fiche. Les confondre les fait toutes revendiquer la liste.
        $this->assertSame(
            'https://exemple.test/actualites/mon-article',
            SocialMeta::canonical('https://exemple.test', '/actualites/mon-article'),
        );
    }
```

Utile, mais partiel : il ne prouve pas que `NewsAction` passe bien ce chemin-là.

**2. Le test qui attrape vraiment le défaut** — monter `ViewContext` avec ses quatre
dépendances réelles, `Repository` lisant une source de contenu de test. C'est le seul qui
échouerait sur le code actuel. Il ouvre la voie à d'autres tests sur le contexte de page,
aujourd'hui entièrement non couvert ; c'est aussi ce qui en fait un travail à part entière,
à décider en amont.

En attendant l'un ou l'autre, le contrôle reste `bin/verifier-accessibilite.php`, à passer
sur chaque mise en ligne — ce que la documentation demande déjà.

## Vérifier après application

```bash
composer test
php bin/purge-cache.php
php bin/verifier-accessibilite.php http://localhost:8080
```

Les fiches d'actualité doivent passer à « conforme », et l'entrée « Actualités » du menu
garder son `aria-current="page"` lorsqu'on est sur une fiche :

```bash
curl -s http://localhost:8080/actualites/pivoines-de-saison | grep -o 'aria-current="page"'
curl -s http://localhost:8080/actualites/pivoines-de-saison | grep -o '<link rel="canonical"[^>]*>'
```
