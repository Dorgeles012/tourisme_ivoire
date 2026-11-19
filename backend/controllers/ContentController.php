<?php
declare(strict_types=1);

/** Public, non-sensitive revision marker for content already exposed by read APIs. */
final class ContentController {
    public static function updates(): never {
        $queries = [
            'destinations' => "SELECT COUNT(*) AS published_count, MAX(updated_at) AS latest_change FROM destinations WHERE statut_publication='publie' AND deleted_at IS NULL AND is_demo=0",
            'galleries' => "SELECT COUNT(*) AS published_count, MAX(m.updated_at) AS latest_change FROM medias m JOIN destinations d ON d.id=m.destination_id WHERE m.statut='actif' AND d.statut_publication='publie' AND d.deleted_at IS NULL AND d.is_demo=0",
            'patrimoines' => "SELECT COUNT(*) AS published_count, MAX(updated_at) AS latest_change FROM patrimoines WHERE statut='publie' AND is_demo=0",
            'videos' => "SELECT COUNT(*) AS published_count, MAX(updated_at) AS latest_change FROM videos WHERE statut='publie' AND deleted_at IS NULL AND is_demo=0",
            'quizzes' => "SELECT COUNT(*) AS published_count, MAX(updated_at) AS latest_change FROM quiz WHERE statut='publie' AND is_demo=0",
            'itineraires' => "SELECT COUNT(*) AS published_count, MAX(i.updated_at) AS latest_change FROM itineraires i LEFT JOIN transporteurs t ON t.id=i.transporteur_id WHERE i.statut='actif' AND i.disponibilite<>'indisponible' AND i.is_demo=0 AND (t.id IS NULL OR (t.is_demo=0 AND t.statut='actif'))",
            'actualites' => "SELECT COUNT(*) AS published_count, MAX(updated_at) AS latest_change FROM actualites WHERE statut='publie' AND deleted_at IS NULL AND is_demo=0 AND (published_at IS NULL OR published_at<=NOW())",
            'promotions' => "SELECT COUNT(*) AS published_count, MAX(updated_at) AS latest_change FROM promotions WHERE statut='publie' AND deleted_at IS NULL AND is_demo=0 AND date_debut<=NOW() AND date_fin>=NOW()",
            'hebergements' => "SELECT COUNT(*) AS published_count, MAX(updated_at) AS latest_change FROM hebergements WHERE statut='publie' AND deleted_at IS NULL AND is_demo=0",
            'activites' => "SELECT COUNT(*) AS published_count, MAX(updated_at) AS latest_change FROM activites_touristiques WHERE statut='publie' AND deleted_at IS NULL AND is_demo=0",
        ];
        $modules = [];
        foreach ($queries as $name => $sql) {
            $row = db()->query($sql)->fetch();
            $modules[$name] = [
                'published_count' => (int)($row['published_count'] ?? 0),
                'latest_change' => $row['latest_change'] ?? null,
            ];
        }
        $version = hash('sha256', json_encode($modules, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        json_response(['data' => [
            'version' => $version,
            'checked_at' => gmdate('c'),
            'modules' => $modules,
        ]]);
    }
}
