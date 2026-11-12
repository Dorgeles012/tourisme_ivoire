# Tourisme Ivoire

Application Flutter multiplateforme, API PHP/PDO et interface d’administration pour découvrir le patrimoine ivoirien. Le projet utilise MySQL/MariaDB; il n’utilise pas Firebase ni Supabase.

## Pré-requis

- Flutter/Dart compatibles avec la contrainte du [`pubspec.yaml`](./pubspec.yaml)
- PHP 8.2+ avec PDO MySQL, JSON, OpenSSL, Fileinfo et Mbstring
- MySQL 8+ ou MariaDB compatible avec les scripts
- Apache avec `mod_rewrite` (ou un serveur configuré pour rediriger les routes vers `backend/public/index.php`)

## Base de données et migrations

Flutter communique avec l’API PHP en HTTP; seul le backend PHP se connecte à MySQL. Ne mettez jamais les identifiants MySQL dans Flutter.

Pour une base neuve vide, importez database/tourisme_ivoire_vide.sql dans phpMyAdmin, ou configurez backend/.env et lancez php backend/migrate.php. La migration applique uniquement la création de la base, les tables, index et vues. Elle n’exécute aucun script de données. php backend/migrate.php --status affiche son état.

Le SQL d’installation crée 48 tables InnoDB en utf8mb4 sans INSERT. Les scripts de remplissage historiques database/03–13 et 16 sont exclus. Voir database/INSTALLATION_VIDE.md pour l’import XAMPP, les tables et leurs relations. L’import est non destructif; il ne supprime pas une ancienne base.

## Backend PHP

1. Placer le projet dans `htdocs/tourisme_ivoire` ou créer un VirtualHost dont la racine publique est `backend/public`.
2. Copier `backend/.env.example` vers `backend/.env`; définir les paramètres MySQL et remplacer `JWT_SECRET` par une valeur aléatoire de 32 caractères minimum.
3. Activer PDO MySQL, OpenSSL, Fileinfo et Mbstring; activer `mod_rewrite` si nécessaire. Exécuter `php backend/migrate.php` depuis la racine du projet pour créer/mettre à jour la base.
4. Pour une installation locale, configurer `CORS_ORIGIN` avec l’origine exacte de l’application web. En production, utiliser HTTPS et désactiver les erreurs détaillées.
5. L’administration est accessible à `/admin/login.php` quand le VirtualHost sert `backend/public`. Les rôles administrateur et éditeur sont créés par les scripts SQL; n’utilisez pas les comptes de démonstration en production.

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

L’application comprend l’authentification, l’exploration et les favoris, le transport et les demandes de réservation, les vidéos et interactions, les quiz, le passeport/badges, les notifications, les paramètres d’accessibilité, le patrimoine, la carte externe et une bibliothèque hors ligne pour le texte des destinations.

Le streaming en direct, la traduction vocale, la réalité augmentée, les notifications push et les médias hors ligne nécessitent des fournisseurs/configurations qui ne sont pas inclus. Ils ne sont donc pas présentés comme fonctionnels. Les données de démonstration doivent être vérifiées et publiées par un éditeur avant d’apparaître comme informations officielles.
