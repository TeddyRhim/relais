# Relais

Mini-SaaS de réception et de rejeu de webhooks. Une organisation crée des sources ayant chacune une URL de réception ; le service stocke chaque webhook reçu, vérifie sa signature HMAC et permet de le rejouer vers une adresse cible, avec nouvelles tentatives automatiques. Plans avec quotas, facturation derrière une interface (faux fournisseur), API par clés.

Laravel 12, PHP 8.2, Blade + Tailwind (Breeze), Pest. Base : SQLite en local et en tests, PostgreSQL en CI et visé en production.

## Commandes
- Tests : `php artisan test --no-ansi` (Pest). Lint : `vendor/bin/pint` (ajouter `--test` pour vérifier sans modifier).
- Serveur : `php artisan serve` ; assets : `npm run dev` ou `npm run build` ; rejeux : `php artisan queue:work` (file en base, pas de Redis).
- Données d'exemple : `php artisan relais:demo` (demande les identifiants ou lit `DEMO_EMAIL` et `DEMO_PASSWORD`).
- Passer `--no-ansi` à `artisan` et `composer` : la sortie colorée pollue les résultats.
- Tester sur PostgreSQL : `DB_CONNECTION=pgsql` et les variables de connexion d'une base dédiée aux tests (jamais une base de données réelle).

## Règles du projet
- Aucun emoji. Aucun secret en clair : `.env` ignoré, `.env.example` sans valeur réelle. Aucun mot de passe par défaut : le compte de démonstration est créé par une commande qui demande les identifiants ou les lit dans l'environnement.
- Isolation des clients : toute table de données client porte `organization_id`, filtré automatiquement par un scope global. Chaque nouvelle table et chaque nouvelle route doit avoir un test prouvant qu'une autre organisation ne peut pas y accéder.
- Facturation derrière l'interface `BillingProvider` ; le faux fournisseur sert aux tests et à la démonstration. Les clés d'un vrai fournisseur viennent de l'environnement, jamais du dépôt.
- Données d'exemple fictives uniquement (captures, seeders, README).
- Une branche par sujet, messages de commit en français sur une ligne, tests verts avant fusion.

## Architecture
- Isolation : le trait `App\Tenancy\BelongsToOrganization` ajoute `OrganizationScope` et remplit `organization_id` ; le contexte vient du singleton `CurrentOrganization`, posé par le middleware `SetCurrentOrganization` (placé avant `SubstituteBindings` pour que le binding des routes soit scopé). Sans contexte, aucune ligne n'est visible (échec fermé). En job ou en commande : `app(CurrentOrganization::class)->run($org, fn)`.
- Organisations : un utilisateur appartient à une seule organisation (`users.organization_id`, `users.role` propriétaire ou membre). Invitations par lien à jeton hashé, valables 7 jours. Le propriétaire gère sources, secret, cible libre de rejeu, invitations et plan ; le membre voit et rejoue vers la cible de la source.
- Réception : `POST /h/{token}` (`routes/ingest.php`, sans session ni CSRF, limitée par jeton et par IP). Signature valide, invalide ou absente ; option « signature obligatoire » par source.
- Rejeu : `App\Jobs\SendReplay`, 5 tentatives espacées de 10 s, 1 min, 5 min et 30 min. Garde contre les requêtes vers le réseau interne : `App\Services\UrlGuard`. Réponse lue de façon bornée, redirections non suivies.
- Quotas : `App\Billing\Usage` lit le compteur mensuel `usage_counters` (alimenté à chaque événement reçu, indépendant de la suppression des sources). Au quota, la réception répond 429 sans stocker. Le webhook de facturation `POST /billing/webhook` est signé et horodaté (5 minutes) ; il est fermé si `BILLING_WEBHOOK_SECRET` est vide.
- API : clés Sanctum créées dans `/api-keys` (préfixe `rl_`, capacité `events:read`, expiration 90 jours, 10 clés au plus par utilisateur), `GET /api/v1/sources`, `/events`, `/events/{id}`. Dans les tests, appeler `app('auth')->forgetGuards()` entre deux requêtes avec des clés différentes.
- Interface : en français (`lang/fr.json`, `APP_LOCALE=fr`). Les variantes `badge-*` sont dans la `safelist` de `tailwind.config.js` (classes composées dynamiquement).
- En-têtes de sécurité globaux (`SecurityHeaders`), `trustHosts` sur l'hôte de `APP_URL`, faux fournisseur de facturation refusé en production sauf `RELAIS_ALLOW_FAKE_BILLING=true`.

## Pièges
- Les tests appellent `withoutVite()` : aucun build d'assets requis.
- Hors contexte d'organisation, un modèle scopé renvoie zéro ligne : dans les tests, utiliser `withoutGlobalScopes()` ou `CurrentOrganization::run`.
- Le message `Module "openssl" is already loaded` d'un PHP mal configuré est inoffensif.

## Reste à faire
- Idempotence du webhook de facturation (mémoriser les événements déjà traités) et implémentation d'un vrai fournisseur de paiement derrière `BillingProvider`.
- Liaison DNS tardive du rejeu : fixer l'IP validée dans le client HTTP, ou passer par un proxy sortant, avant toute exposition à des utilisateurs non fiables.
- `trustProxies` avec les adresses réelles et `SESSION_SECURE_COOKIE=true` derrière HTTPS ; limite de taille des corps appliquée aussi au niveau du serveur web.
- Verrouillage de la concurrence du quota (dépassement possible de quelques unités sous forte charge).
