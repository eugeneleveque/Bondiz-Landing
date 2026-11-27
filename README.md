# Bondiz

Site web de Bondiz : la landing de présentation et le blog dans un seul dépôt.

| URL | Contenu | Techno |
|---|---|---|
| `/` | Accueil | Blog (Symfony) |
| `/posts`, `/post/{id}` | Articles | Blog (Symfony) |
| `/a-propos` | Pourquoi avons-nous créé Bondiz ? | Landing (React) |
| `/login`, `/admin` | Administration (EasyAdmin), sans inscription publique | Blog (Symfony) |
| `/mentions-legales`, `/politique-de-confidentialite`, `/cookies` | Pages RGPD | Blog (Symfony) |
| `/downloads/bondiz.apk` | Téléchargement de l'application Android | Fichier statique |

## Architecture

```
navigateur ──► bondiz-landing (nginx, port 3012)
                 ├─ /a-propos, /assets, /downloads  → build React (Vite)
                 └─ tout le reste ──► blog-nginx ──► php (Symfony, PHP-FPM) ──► database (MariaDB)
```

```
.
├── src/, public/, index.html   Landing React + TypeScript (Vite)
├── Dockerfile                  Stages : development, build-stage, production-stage (nginx)
├── nginx.conf                  Reverse proxy : landing sur /a-propos, blog sur le reste
├── blog/                       Blog Symfony 7.4 (Doctrine, EasyAdmin, Twig), historique git conservé
├── compose.yaml                Stack complète en local (production-like)
├── compose.dev.yaml            Landing seule avec hot reload (Vite)
├── docker-compose.prod.yaml    Stack déployée sur le VPS (images du registry)
└── .github/workflows/          CI/CD (build des images + déploiement)
```

Le header est volontairement identique des deux côtés : `blog/templates/base.html.twig` (Twig) et `src/components/Navbar.tsx` (React). Toute modification doit être faite aux deux endroits.

## Lancer le projet

Prérequis : Docker et Docker Compose.

### Stack complète (landing + blog)

```bash
docker compose up -d --build
```

Le site est accessible sur http://localhost:3012. Les migrations Doctrine sont appliquées automatiquement au démarrage de `php`.

Variables optionnelles (fichier `.env` à la racine, valeurs par défaut entre parenthèses) : `APP_SECRET` (`change-me-in-production`), `DEFAULT_URI` (`http://localhost:3012`), `MYSQL_ROOT_PASSWORD` (`root`), `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD` (`app`).

Si vous recréez seulement le conteneur `php`, redémarrez les proxys, qui gardent l'ancienne adresse en cache :

```bash
docker compose restart blog-nginx bondiz-landing
```

### Landing seule (développement React)

```bash
docker compose -f compose.dev.yaml up -d --build
```

Accessible sur http://localhost:5173 avec hot reload. Sans Docker : `npm install` puis `npm run dev`.

Scripts npm : `dev`, `build` (`tsc -b && vite build`), `lint`, `preview`.

### Blog seul (développement Symfony)

Voir `blog/README.md` et le `Makefile` du dossier `blog/`. Les URL sont alors servies à la racine du blog.

## Créer un compte administrateur

Le site n'a pas d'inscription publique. Le compte se crée en ligne de commande (le mot de passe est demandé, 12 caractères minimum) :

```bash
docker compose exec php php bin/console app:admin vous@exemple.fr
```

La même commande change le mot de passe d'un compte existant. Connectez-vous ensuite sur `/login`, puis ouvrez `/admin`.

## Créer des partenaires depuis l'administration

L'onglet **Partenaires** de `/admin` écrit dans Strapi. Définir la même variable `BLOG_API_SECRET` (32 caractères minimum, `openssl rand -base64 32`) dans le `.env` du blog et dans celui de `strapi-mydsu`, puis redémarrer les deux. Sans elle, l'onglet affiche une erreur et l'API refuse les appels.

Les identifiants sont envoyés par e-mail au partenaire. En local, les messages sont visibles sur http://localhost:8025 ; en production il faut un `MAILER_DSN` valide, sinon le mot de passe s'affiche à l'écran.

## CI/CD

Les workflows sont dans `.github/workflows/` :

- **`build.yaml`** : sur push ou PR vers `main`, construit les 3 images (`bondiz-landing`, `bondiz-blog`, `bondiz-blog-nginx`). Seul un push sur `main` les publie dans le registry.
- **`deploy.yaml`** : après un build réussi sur `main`, copie `docker-compose.prod.yaml` sur le VPS (`/home/debian/bondiz-blog`) puis fait `pull` et `up -d`.

Secrets GitHub requis : `REGISTRY_NAME`, `REGISTRY_USERNAME`, `REGISTRY_PASSWORD`, `VPS_SSH_KEY`, `VPS_IP`, `VPS_USER`, `MYSQL_ROOT_PASSWORD`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`, `APP_SECRET`, `DEFAULT_URI`.

Le nom de projet Compose (`bondiz-blog`) et le dossier du VPS sont conservés pour réutiliser le volume MariaDB existant.

## Notes

- `public/downloads/bondiz.apk` doit être présent avant le build de l'image pour que le bouton de téléchargement fonctionne.
- Cookies : le blog utilise tarteaucitron (consentement) avant de charger Google Analytics.
