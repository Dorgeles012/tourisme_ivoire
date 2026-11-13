# Tourisme Ivoire

Application Flutter multiplateforme, API PHP/PDO et interface d’administration pour découvrir le patrimoine ivoirien. Le projet utilise MySQL/MariaDB; il n’utilise pas Firebase ni Supabase.

## Pré-requis

- Flutter/Dart compatibles avec la contrainte du [`pubspec.yaml`](./pubspec.yaml)
- PHP 8.2+ avec PDO MySQL, JSON, OpenSSL, Fileinfo et Mbstring
- MySQL 8+ ou MariaDB compatible avec les scripts
- Apache avec `mod_rewrite` (ou un serveur configuré pour rediriger les routes vers `backend/public/index.php`)

## Base de données et migrations

Flutter communique avec l’API PHP en HTTP; seul le backend PHP se connecte à MySQL. Ne mettez jamais les identifiants MySQL dans Flutter.

Pour une base neuve vide, importez `database/tourisme_ivoire.sql` dans phpMyAdmin, ou configurez `backend/.env` puis lancez `php backend/migrate.php` depuis la racine du projet. Le lanceur applique le schéma et les migrations PHP versionnées. `php backend/migrate.php --status` indique les migrations en attente.

Le SQL d’installation crée 40 tables InnoDB en utf8mb4 sans données de démonstration. L’import est non destructif; il ne supprime pas une ancienne base. Les rôles utilisent `utilisateurs.role`, avec une clé étrangère vers `roles.code` et des contraintes SQL limitant le catalogue à `Sadmin` et `Client`. Sur une base existante, `php backend/migrate.php` copie les associations de rôles, valide qu’elles ne sont pas multiples ou personnalisées, puis archive l’ancienne table `utilisateurs_roles` et les valeurs historiques `is_demo` afin de conserver les données et permettre le retour via `database/migrations/20261113_001_user_role_and_demo_cleanup.down.sql`. Les migrations suivantes ajoutent le stockage des codes d’ouverture à usage unique du back-office, limitent le catalogue aux deux rôles autorisés et archivent par renommage les tables sans usage constaté; les scripts de retour sont fournis dans `database/migrations/`.

Avant toute mise à niveau d’une base qui contient des données, exporte et vérifie une sauvegarde complète, et sauvegarde aussi `backend/public/uploads`. Par exemple, depuis PowerShell avec les outils MySQL installés :

```powershell
mysqldump --single-transaction --routines --triggers --events --default-character-set=utf8mb4 -h 127.0.0.1 -u root -p tourisme_ivoire > sauvegarde_tourisme_ivoire.sql
if ($LASTEXITCODE -ne 0) { throw "Échec de la sauvegarde MySQL" }
```

Vérifie que le fichier exporté est présent et non vide, et teste sa restauration dans une base de test avant d’appliquer les migrations. Les modifications DDL MySQL ne sont pas annulables par transaction; le script `.down.sql` n’est pas un substitut à une sauvegarde externe. `php backend/migrate.php --status` est une consultation en lecture seule et ne prouve ni l’existence d’une sauvegarde ni la validité des données.

## Backend PHP

1. Placer le projet dans `htdocs/tourisme_ivoire` ou créer un VirtualHost dont la racine publique est `backend/public`.
2. Copier `backend/.env.example` vers `backend/.env`; définir les paramètres MySQL et remplacer `JWT_SECRET` par une valeur aléatoire de 32 caractères minimum. Le répertoire PHP `upload_tmp_dir` et `backend/public/uploads/avatars` doivent être accessibles en écriture au processus PHP; les réponses API ne doivent pas afficher les avis/erreurs PHP.
3. Activer PDO MySQL, OpenSSL, Fileinfo et Mbstring; activer `mod_rewrite` si nécessaire. Exécuter `php backend/migrate.php` depuis la racine du projet pour créer/mettre à jour la base.
4. Pour une installation locale, configurer `CORS_ORIGIN` avec l’origine exacte de l’application web, `MAIL_FROM` avec l’adresse d’envoi. Cette valeur permet d’envoyer les codes de réinitialisation par courriel. En production, utiliser HTTPS et désactiver les erreurs détaillées.
5. Après la création de la base, lancez `php backend/create_sadmin.php` depuis la racine du projet. La commande demande le nom, l’adresse e-mail et un mot de passe saisi de façon masquée, puis crée le premier compte `Sadmin` dans `utilisateurs` et lui attribue ce rôle. Un seul compte Sadmin initial peut être créé par cette commande.
6. L’administration est accessible à `/admin/login.php` quand le VirtualHost sert `backend/public`. Après connexion dans Flutter, un compte Sadmin ouvre le back-office via un code aléatoire à usage unique, valable 60 secondes et transmis dans le fragment d’URL (jamais dans la requête HTTP); un Sadmin avec une session Flutter restaurée est redirigé de la même façon. L’application utilise seulement les rôles `Sadmin` et `Client`. Le Sadmin peut créer un compte depuis le module « Utilisateurs » et attribuer/promouvoir un autre Sadmin; les inscriptions publiques reçoivent toujours le rôle `Client`.

Le back-office permet notamment de gérer les utilisateurs et leurs rôles, les destinations, les contenus patrimoniaux, les visites virtuelles, les images, les quiz, les vidéos, les catégories, les réservations et les notifications. Les visites virtuelles ne peuvent être activées que si leur destination ou patrimoine associé est publié. Les demandes de réinitialisation de mot de passe sont limitées à trois par compte et par heure; chaque nouveau code invalide les précédents.

Les contrôleurs sont sous `backend/controllers`, la configuration et les services dans `backend/config` et `backend/services`. Les demandes de transport sont des réservations sans paiement intégré.

## Application Flutter

À la racine du projet :

```powershell
flutter pub get
flutter analyze
flutter test
flutter run --dart-define=API_BASE_URL=http://10.0.2.2/tourisme_ivoire/backend/public/
```

L’adresse `10.0.2.2` est celle de l’émulateur Android pour joindre la machine hôte. Remplacez-la par l’adresse de votre serveur sur un appareil physique. La valeur par défaut de l’API convient à l’émulateur Android.

## Fonctionnalités et limites d’intégration

L’application comprend l’authentification (inscription Client, photo de profil facultative JPEG/PNG/WebP jusqu’à 5 Mo), l’exploration et les favoris, le transport et les demandes de réservation, les vidéos et interactions, les quiz, le passeport/badges, les notifications, les paramètres d’accessibilité, le patrimoine, la carte externe et une bibliothèque hors ligne pour le texte des destinations.

Le streaming en direct, la traduction vocale, la réalité augmentée, les notifications push et les médias hors ligne nécessitent des fournisseurs/configurations qui ne sont pas inclus. Ils ne sont donc pas présentés comme fonctionnels. Les données de démonstration doivent être vérifiées et publiées par un éditeur avant d’apparaître comme informations officielles.

http://127.0.0.1:8000/admin/login.php

php -S 127.0.0.1:8000 -t backend/public

