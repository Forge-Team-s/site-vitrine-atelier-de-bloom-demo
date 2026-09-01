# Déployer et mettre à jour le site

Deux voies mènent le code sur l'hébergement. Le dépôt Git est la bonne ; le FTP est celle
qu'on prend quand l'hébergement ne laisse pas le choix. Les deux sont décrites ici parce que
les deux se rencontrent, et parce que le passage de la seconde à la première est lui-même une
opération à mener proprement.

Le site de démonstration est passé par les deux : livré par FTP, puis adopté par un dépôt sans
interruption de service. La marche à suivre au [§ 1.1](#11-adopter-un-dossier-déjà-livré-par-ftp)
est celle qui a servi.

## Choisir

| | Dépôt Git sur l'hébergement | FTP |
|---|---|---|
| **Demande** | un accès SSH et `git` sur l'hébergement | rien |
| **Une mise à jour** | deux commandes, quelle que soit sa taille | une liste de fichiers, dans un ordre qui compte |
| **Savoir ce qui tourne** | `git log -1`, `cat VERSION` | ce qu'on croit avoir envoyé |
| **Voir ce qui a dérivé** | `git status` | rien |
| **Revenir en arrière** | `git revert`, `git checkout <version>` | renvoyer les fichiers à la main |
| **Risque propre** | un `git checkout -f` de trop écrase une correction faite sur place | un transfert interrompu laisse le site à moitié à jour, sans le dire |

Le vrai départage n'est pas la commodité, c'est la **connaissance de l'état déployé**. Sans
dépôt, personne ne sait ce que contient l'hébergement — ni si quelqu'un y a corrigé un fichier
en direct un soir de panne.

Une objection revient : le dépôt pose `docs/`, `demo/` et `tests/` sur l'hébergement. Elle
tombe dès qu'on regarde où pointe la racine web. Elle est sur `public/` : tout ce qui est au-
dessus est sur le disque et **n'est jamais servi**. Si l'on y tient quand même, voir
[§ 1.4](#14-ne-poser-que-le-nécessaire).

---

# 1. La voie du dépôt

## 1.1 Adopter un dossier déjà livré par FTP

Le cas courant : le site tourne, il a été envoyé par FTP, on veut passer au dépôt sans le
couper. On ne clone pas — `git clone` refuse un dossier non vide, et cloner à côté pour
recopier `.env`, `public/admin/` et `var/` à la main est exactement la manœuvre où l'on perd
une clé d'API.

On initialise **sur place**. Aucun fichier n'est touché.

```bash
cd ~/www/site
git init
git remote add origin https://github.com/<compte>/<depot-du-site>.git
git remote add socle  https://github.com/jean-ely-pro/site-vitrine-cockpit-php.git
git fetch origin
git fetch socle --tags
```

À ce stade, trois choses ne s'accordent pas encore : l'arbre de travail contient les vrais
fichiers, l'index est vide, et `HEAD` désigne une branche sans commit. `git fetch` a rempli
`.git/` d'objets sans rien dire du dossier.

```bash
git symbolic-ref HEAD                  # refs/heads/main, ou refs/heads/master ?
git reset origin/main                  # --mixed : l'arbre de travail n'est pas touché
git status
```

`git reset` en mode par défaut fait deux choses et deux seulement : il place la branche sur le
commit, et il remplit l'**index** avec l'arbre de ce commit. Il ne réécrit **aucun fichier**.
Le site continue de servir les mêmes octets pendant toute l'opération.

Le but est la commande suivante. `git status` compare l'arbre de travail à l'index : ce qu'il
affiche est **la dérive**, c'est-à-dire tout ce qui a été modifié sur le serveur depuis le
dernier envoi. C'est le dernier moment où on peut la voir — le premier `git checkout` l'écrasera
sans un mot.

> [!WARNING]
> Ne pas employer ici `git reset --hard` ni `git checkout -f`. Ils alignent le disque sur le
> commit **en écrasant** : la dérive disparaît et l'on ne saura jamais ce qu'elle contenait.

Deux pièges à l'affichage :

- **`git init` a pu créer `master`.** `git reset` écrit dans la branche que `HEAD` désigne.
  Si c'est `master`, `git branch -m main` après coup.
- **Les fins de ligne.** `.gitattributes` impose `* text=auto eol=lf`. Un client FTP réglé en
  mode ASCII, ou des fichiers partis de Windows en CRLF, font apparaître **tous** les fichiers
  texte comme modifiés. Pour trancher :

  ```bash
  git diff --stat                      # tout
  git diff --ignore-cr-at-eol --stat   # la dérive réelle
  ```

  Second vide : il n'y a que des fins de ligne, rien à sauver.

Puis, selon ce que dit `git status` :

| | |
|---|---|
| propre | la prod correspond au dépôt, on enchaîne |
| des fichiers modifiés | lire `git diff` **avant tout**. Chaque ligne est une décision : garder (la committer) ou jeter (`git checkout -- le/fichier`) |
| des fichiers supprimés | des fichiers du dépôt absents de l'hébergement — typiquement un envoi FTP incomplet. Le prochain `checkout` les remettra |

Enfin, donner un suivi à la branche, une fois pour toutes :

```bash
git branch --set-upstream-to=origin/main
```

## 1.2 Installer sur un hébergement vierge

```bash
cd ~/www
git clone https://github.com/<compte>/<depot-du-site>.git site
cd site
composer install --no-dev
php bin/install-cockpit.php
cp .env.example .env                   # puis le renseigner
php bin/cockpit-init.php               # note les mots de passe et les clés affichés
```

La racine web de l'hébergement doit pointer sur `~/www/site/public`, jamais sur `~/www/site`.

## 1.3 Mettre à jour ensuite

```bash
cd ~/www/site
git fetch origin
git merge --ff-only origin/main
```

`--ff-only` plutôt que `git pull` : si l'avance n'est pas directe, c'est qu'il y a une dérive
locale, et l'on veut le savoir plutôt que de déclencher une fusion sur un serveur en service.

Puis les deux réflexes d'après-fusion, décrits au [§ 3](#3-après-toute-mise-à-jour).

## 1.4 Ne poser que le nécessaire

Si l'on ne veut pas de `docs/`, `demo/` et `tests/` sur l'hébergement, `git sparse-checkout`
répond exactement à ça, sans gêner les fusions à venir :

```bash
git sparse-checkout init --cone
git sparse-checkout set bin cockpit public src templates templates-client
```

En mode `cone`, les fichiers de la racine — `composer.json`, `VERSION`, `.env.example` — sont
toujours présents. `composer test` ne fonctionnera plus sur l'hébergement, ce qui est sans
conséquence : les tests se passent sur le poste.

---

# 2. La voie FTP

## 2.1 Ce qu'on envoie

Uniquement les fichiers que la version modifie, obtenus sur le poste :

```bash
git diff --name-status <version-en-ligne> <version-visée>
```

De cette liste, **ne partent pas** vers l'hébergement : `docs/`, `tests/`, `demo/`,
`README.md`, `CHANGELOG.md`, `LICENSE`, `.env.example`, `.gitignore`. Ils n'y servent à rien.
`VERSION` part, en revanche : c'est la seule trace de ce qui tourne.

Exemple réel — le passage en 2.0.0, sept fichiers :

| Fichier | |
|---|---|
| `public/medias/.htaccess` | nouveau — crée le dossier au passage |
| `public/.htaccess` | refuse `/admin/storage`, envoie `X-Robots-Tag` sur l'admin |
| `public/index.php` | `MEDIA_BASE_URL` par défaut à `/medias` |
| `cockpit/config.php` | `paths` et `fileStorage` pointent sur `public/medias` |
| `bin/install-cockpit.php` | crée `public/medias/variantes/` |
| `src/Seo/Sitemap.php` | `robots.txt` ne nomme plus `/admin` |
| `VERSION` | `2.0.0` |

## 2.2 L'ordre compte

C'est la différence de fond avec le dépôt. Avec Git, le code entre d'un bloc ; en FTP il entre
fichier par fichier, et le site tourne pendant ce temps. Envoyer `public/index.php` avant
d'avoir déplacé les médias coupe les images le temps du transfert.

**Règle :** préparer d'abord ce dont le nouveau code aura besoin, envoyer le code ensuite.
Le [§ 4](#4-le-cas-dune-version-majeure--lexemple-de-la-200) le montre sur un cas réel.

## 2.3 Ce qui ne passe jamais par FTP

Quatre choses, qui demandent une ligne de commande — donc un accès SSH, ou le terminal web de
l'hébergeur :

```bash
php bin/install-cockpit.php --force    # recopie cockpit/config.php et les modèles
php bin/purge-cache.php                # sinon l'ancien HTML continue d'être servi
chmod -R 755 public/medias             # droits d'écriture
# et l'édition de .env
```

Sans aucun accès en ligne de commande, il reste des replis :

| À la place de | Repli |
|---|---|
| `install-cockpit.php --force` | envoyer `cockpit/config.php` **aussi** vers `public/admin/config/config.php`, et créer `public/medias/variantes/` par FTP |
| `purge-cache.php` | supprimer le contenu de `public/cache/` par FTP, et `public/assets/css/couleurs.css` |
| `chmod` | le panneau de l'hébergeur, ou le client FTP |

Le premier repli ne vaut que si la version de Cockpit n'a pas changé. Si elle a changé, il faut
une ligne de commande — ou réinstaller depuis le poste et tout renvoyer.

---

# 3. Après toute mise à jour

Deux réflexes, quelle que soit la voie :

| Si la mise à jour touche | Lancer |
|---|---|
| `cockpit/` — configuration, modèles ou addons | `php bin/install-cockpit.php --force` |
| `templates/`, `templates-client/`, une feuille de style | `php bin/purge-cache.php` |

Le premier est **sans condition**. La documentation du socle l'a longtemps présenté comme
nécessaire seulement quand la version de Cockpit changeait ; c'était faux, et corrigé en 2.0.5.
`public/admin/` n'est pas versionné : rien d'autre que ce script n'y recopie `cockpit/config.php`,
et il ne fait rien du tout quand la version installée correspond déjà.

Puis contrôler, depuis le poste et non depuis le serveur — `src/Audit/` et le script ne sont
pas censés être sur l'hébergement :

```bash
php bin/verifier-accessibilite.php https://domaine-du-client.tld
```

## Ce que la mise à jour ne touche jamais

Ces chemins sont ignorés par Git. Ni un `checkout`, ni un `merge` ne les modifient — et ils ne
doivent jamais partir par FTP :

| Chemin | |
|---|---|
| `.env` | clés d'API, adresse du site |
| `public/admin/` | Cockpit lui-même, et son secret |
| `public/medias/` | les images envoyées depuis l'administration |
| `var/` | la base SQLite : comptes, rôles, clés, contenu |
| `vendor/` | dépendances Composer |
| `public/cache/`, `public/assets/css/couleurs.css` | régénérés |

Les trois premiers ne se régénèrent pas. Une sauvegarde les prend tous :

```bash
tar czf ~/sauvegarde-$(date +%F).tgz var public/admin/storage .env
```

---

# 4. Le cas d'une version majeure — l'exemple de la 2.0.0

Une version majeure demande une intervention manuelle : déplacer des fichiers, modifier `.env`,
migrer des données. Le socle décrit chaque fois la marche à suivre sous la version, dans
[CHANGELOG.md](../CHANGELOG.md). La 2.0.0 sort les médias de `public/admin/storage/uploads`
pour les servir depuis `public/medias`, afin qu'aucune page publique ne nomme l'administration.

Sauvegarder d'abord, dans les deux voies.

## Par le dépôt

```bash
cd ~/www/site
mkdir -p public/medias
cp -a public/admin/storage/uploads/* public/medias/     # d'abord les médias
git fetch origin && git merge --ff-only origin/main     # puis le code, d'un bloc
php bin/install-cockpit.php --force
# .env : MEDIA_BASE_URL=/medias
chmod -R 755 public/medias
php bin/purge-cache.php
```

## Par FTP

Même principe, découpé — les médias avant le code, pour que le site bascule sur un `/medias`
déjà rempli.

1. **FTP** — `public/medias/.htaccess`, seul. Le dossier naît de cet envoi ; rien ne change pour
   le site, qui sert encore ses images depuis `/admin/storage/uploads`.
2. **Ligne de commande** — recopier, puis compter avant de croire :

   ```bash
   cp -a public/admin/storage/uploads/* public/medias/
   find public/medias -type f ! -name '.htaccess' | wc -l
   find public/admin/storage/uploads -type f ! -name '.htaccess' | wc -l
   ```

   Les deux comptes doivent être égaux. `*` ne reprend pas les fichiers cachés, et c'est voulu :
   l'ancien `.htaccess` écraserait celui qu'on vient d'envoyer.
3. **FTP** — les six autres fichiers. Le site bascule sur `/medias`, déjà rempli.
4. **Ligne de commande** — `install-cockpit.php --force`, `.env`, `chmod`, `purge-cache.php`.
5. Une fois les contrôles passés : `rm -rf public/admin/storage/uploads`.

## Contrôler

```bash
B=https://domaine-du-client.tld
curl -s  $B/ | grep -c 'admin/storage'          # 0
curl -s  $B/robots.txt | grep -c admin          # 0
curl -sI $B/admin | grep -i x-robots-tag        # noindex, nofollow
curl -sI $B/admin/storage/ | head -1            # 403
curl -s  $B/ | grep -oE 'src="[^"]*medias[^"]*"' | head -1
curl -sI $B/medias/ | head -1                   # 403 — pas de liste de fichiers
```

Puis, dans l'administration : envoyer une image, vérifier qu'elle arrive dans `public/medias/`
et que l'aperçu s'affiche.

Les enregistrements d'images portent un chemin relatif, jamais une adresse : **aucune
modification de la base de données n'est nécessaire**, ni à l'aller, ni au retour.

---

# 5. Revenir en arrière

## Par le dépôt

```bash
git log --oneline --first-parent -5      # trouver le commit de fusion
git show <fusion>:VERSION                # confirmer : la version qu'on quitte
git show <fusion>^1:VERSION              # et celle où l'on retourne
git revert -m 1 <fusion>
```

`-m 1` désigne le premier parent, la ligne du site — celle qu'il faut conserver.

## Par FTP

Renvoyer les mêmes fichiers, pris au commit précédent :

```bash
git show <commit-précédent>:public/index.php > /tmp/index.php
```

Dans les deux cas, la partie manuelle d'une version majeure se défait à la main. Pour la 2.0.0 :

```bash
php bin/install-cockpit.php --force      # recrée storage/uploads et sa règle d'accès
mv public/medias/2026 public/medias/variantes public/admin/storage/uploads/
# .env : MEDIA_BASE_URL=/admin/storage/uploads
php bin/purge-cache.php
```

Nommer les dossiers un à un plutôt qu'écrire `public/medias/*` : `public/medias/.htaccess` doit
rester où il est.

> [!IMPORTANT]
> **Adresses déjà publiées.** Les images servies sous `/medias` pendant la période 2.0.0
> répondent 404 après le retour. Sur un site en ligne depuis plusieurs semaines, poser la
> redirection dans `public/.htaccess` **avant** de purger, au-dessus des règles du cache.

---

# 6. Mettre à jour depuis le socle

Le dépôt du site reçoit les corrections du socle avant de les déployer. La procédure générale
est dans [docs/mise-a-jour-socle.md](../docs/mise-a-jour-socle.md) — elle suppose un ancêtre
commun, c'est-à-dire un site créé depuis le modèle GitHub.

**Ce dépôt-ci n'en a pas** : il a été créé par copie. `git merge v2.0.5` y répond
« refusing to merge unrelated histories », et forcer la fusion mettrait en conflit chaque
fichier propre à la démonstration. La reprise se fait donc fichier par fichier :

```bash
git fetch socle --tags
git log  --oneline v2.0.5..vX.Y.Z        # ce que la version apporte
git diff --stat     v2.0.5..vX.Y.Z       # quels fichiers, exactement
git checkout -b maj-socle-vX.Y.Z origin/main
git checkout vX.Y.Z -- <les fichiers de la liste>
composer test
```

Le point d'attention est à l'avant-dernière ligne : **ne jamais reprendre du socle un fichier
qui appartient au site.**

| Appartient au site — ne pas écraser | Appartient au socle — se reprend tel quel |
|---|---|
| `templates-client/`, `public/assets/css/client.css`, `demo/` | `src/`, `bin/`, `tests/`, `templates/`, `public/assets/css/site.css`, `docs/` |
| `cockpit/models/pages.model.php` | `cockpit/config.php`, `cockpit/addons/` |

En 2.0.4 comme en 2.0.5, les deux ensembles ne se recoupaient pas. Ça ne restera pas vrai
indéfiniment : la liste des fichiers modifiés se lit **avant** de la reprendre.

Le rang du numéro annonce ce que la version demande — majeur : une intervention manuelle,
décrite sous la version dans [CHANGELOG.md](../CHANGELOG.md) ; mineur et correctif : rien de
plus que la reprise et les réflexes du [§ 3](#3-après-toute-mise-à-jour).
