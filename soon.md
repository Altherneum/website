# User-Agent, IP, route, etc logging
- Hash of user data sometime change by mistake
- Add /api/logs endpoint, on each post on this endpoint, store to BDD data
  - `window.onerror` → POST `/api/js-error` (log dans `security.log` ou fichier dédié)

# Durcir `php.ini` (prod)
- **Description** : Config par défaut insécurisée.
- **Modifications** :
```ini
expose_php = Off
session.cookie_httponly = 1
session.cookie_samesite = Strict
session.cookie_secure = 1     ; seulement si HTTPS en prod
```
- **Fichier** : `php.ini` (lignes 14, 98, 99, 100)

# Headers de sécurité HTTP (`.htaccess` pour Apache / config Nginx)
- **Description** : Aucun header CSP, HSTS, X-Frame-Options, etc.
- **Fichier cible** : `public/.htaccess` (nouveau)
- **Contenu minimal** :
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]

Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "SAMEORIGIN"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
Header always set Content-Security-Policy "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self'; frame-ancestors 'self';"

<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/* "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>

<FilesMatch "^\.|composer\.json|composer\.lock|\.env|install\.sh|start\.sh|php\.ini|README\.md|agent\.md">
    Require all denied
</FilesMatch>
```

### Page /github/statistiques, ajouter le repo website
- **Ajouter le repo `website`** dans la section « Références » de la page `/github/statistiques` ainsi que sur le dépôt GitHub Altherneum. Le repo est actuellement hébergé sur mon profil personnel.

# `security.txt` (RFC 9116)
- **Description** : Standard pour contact sécurité (bug bounty, disclosure).
- **Fichier** : `public/.well-known/security.txt` (nouveau, créer dossier)
- **Contenu** :
```
Contact: mailto:contact@altherneum.fr
Expires: 2026-12-31T23:59:59.000Z
Preferred-Languages: fr, en
Canonical: https://doc.altherneum.fr/.well-known/security.txt
```

# `humans.txt` (fun, tradition)
- **Description** : Crédits humains (dev, design, tools).
- **Fichier** : `public/humans.txt` (nouveau)

# Health check endpoint (`/health`)
- **Description** : Monitoring uptime (Uptime Kuma, Prometheus, etc.).
- **Fichier** : `public/src/pages/health/index.php` (nouveau)
- **Réponse** : JSON `{ "status": "ok", "db": "connected", "time": "..." }` + HTTP 200/503.

# Préconnect / DNS-prefetch pour assets externes
- **Description** : Si CDN, polices Google, APIs tierces → `<link rel="preconnect" href="...">` dans head.

# Backup automatique BDD (cron)
- **Description** : `mysqldump` journalier → compression → stockage externe (S3, Drive, etc.).

# Internationalisation (i18n) FR/EN
- **Description** : Fichiers `lang/fr.php`, `lang/en.php` + détection `Accept-Language` ou param `?lang=en`.

# XSS stocké dans le dashboard admin (données visiteur non échappées)
- `public/src/php/api/data.php` (l. 50-312) stocke sans nettoyage des champs **entièrement
  contrôlés par n'importe quel visiteur** : `user_agent`, `oscpu`, `platform`, `path`,
  `languages`, `gpu`, `plugins`, `source`, etc.
- `public/src/php/api/stats-dashboard.php` (l. 20-26) les renvoie bruts.
- `public/src/pages/outils/stats/stats.js` (l. 129-146) les injecte via `innerHTML`.
- Conséquence : POST `/api/data` avec `user_agent: "<img src=x onerror=...>"` → exécution dans
  la session de l'admin qui ouvre `/outils/stats`.
- **Correctif** : échapper à l'affichage (`textContent`/`escapeHtml`) et/ou assainir en entrée.

# Contournement CSRF sur le changement de mot de passe
- `public/src/pages/password/update/update.php` : l. 31-34 met `$can_update = false` si le token
  est invalide, puis l. 40 écrase la valeur : `$can_update = checkPassword(...)`.
- Le résultat de la validation CSRF est donc **perdu** : un POST sans token valide passe.
- **Correctif** : ne jamais réécrire la variable CSRF, ex.
  `$can_update = validateCsrfToken() && checkPassword(...);` en gardant les blocs explicites.

# XSS stocké via le corps des issues GitHub
- `public/src/pages/github/issues/issues.js` l. 175-176 : `parseMarkdown(body)` → `innerHTML`.
- N'importe qui peut ouvrir une issue contenant du HTML brut → XSS sur `/github/issues`.
- **Correctif** : assainir le HTML produit par `parseMarkdown` (allow-list) avant injection.

# Pas de régénération d'ID de session à la connexion
- `public/src/pages/login/login.php` l. 62-64 ne fait pas `session_regenerate_id(true)`.
- `php.ini` l. 90 : `session.use_strict_mode = 0`.
- Risque de **fixation de session**.
- **Correctif** : régénérer l'ID après `password_verify` (et à l'inscription).

# `register.php` : `$_SESSION["id"]` non défini
- `public/src/pages/register/register.php` l. 124 : `$_SESSION["id"] = $id;` alors que `$id`
  n'existe pas → session avec `id` vide/`null`, `loggedin=true`.
- Impacts : `intval($_SESSION["id"]) = 0` (upload dans `uploads/profile_pictures/0/`),
  warnings à l'accueil, logique utilisateur incohérente.
- **Correctif** : récupérer `$pdo->lastInsertId()` après l'INSERT.

# Autre erreurs légères
5. **Mot de passe tronqué** : `login.php` l. 37 applique `trim()` au mot de passe ; un espace
   légitime devient impossible. → ne pas trimmer les mots de passe.
6. **`$token;` mort** dans `csrf.php` l. 19 ; `$file_type` inutilisé dans `picture.php` l. 55.
7. **Page Settings cassée** : `public/src/pages/settings/index.php` l. 3 inclut
   `settings/settings.php` **qui n'existe pas** → warning + script absent.
8. **`php.ini`** : `expose_php = On` (l. 14) et cookies de session non durcis (l. 98-100).
   Le `Secure` forcé dans `index.php` empêche aussi le login en `http://localhost` (connu).