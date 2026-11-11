<?php
declare(strict_types=1);
final class BadgeService {
    public static function evaluate(PDO $pdo, int $userId): void {
        $evaluators = [
            'visites' => 'SELECT COUNT(*) FROM visites WHERE utilisateur_id=?',
            'quiz_termines' => 'SELECT COUNT(*) FROM participations_quiz WHERE utilisateur_id=? AND termine_at IS NOT NULL',
            'quiz_score_parfait' => 'SELECT COUNT(*) FROM participations_quiz WHERE utilisateur_id=? AND termine_at IS NOT NULL AND total_questions>0 AND bonnes_reponses=total_questions',
            'quiz_geographie' => "SELECT COUNT(*) FROM participations_quiz p JOIN quiz q ON q.id=p.quiz_id WHERE p.utilisateur_id=? AND p.termine_at IS NOT NULL AND q.theme='geographie'",
            'videos_publiees' => "SELECT COUNT(*) FROM videos WHERE utilisateur_id=? AND statut='publie' AND deleted_at IS NULL",
            'profil_complet' => "SELECT IF(nom<>'' AND email<>'' AND telephone IS NOT NULL AND telephone<>'',1,0) FROM utilisateurs WHERE id=?",
            'visites_nature' => "SELECT COUNT(DISTINCT v.destination_id) FROM visites v JOIN destinations d ON d.id=v.destination_id JOIN categories c ON c.id=d.categorie_id WHERE v.utilisateur_id=? AND c.slug IN ('parcs-nationaux','reserves-naturelles','cascades','montagnes')",
            'visites_patrimoine' => "SELECT COUNT(DISTINCT v.destination_id) FROM visites v JOIN destinations d ON d.id=v.destination_id JOIN categories c ON c.id=d.categorie_id WHERE v.utilisateur_id=? AND c.slug IN ('monuments','sites-historiques','sites-culturels')",
            'destination_slug' => "SELECT COUNT(*) FROM visites v JOIN destinations d ON d.id=v.destination_id WHERE v.utilisateur_id=? AND d.slug='quartier-historique-grand-bassam'",
            'contenus_culture' => 'SELECT COUNT(*) FROM consultations_patrimoine WHERE utilisateur_id=?',
        ];
        $badges = $pdo->query("SELECT id,condition_type,condition_valeur,points FROM badges WHERE statut='actif'")->fetchAll();
        foreach ($badges as $badge) {
            $definition = $evaluators[$badge['condition_type']] ?? null;
            if ($definition === null) continue;
            $count = $pdo->prepare($definition);
            $count->execute([$userId]);
            if ((int)$count->fetchColumn() < (int)$badge['condition_valeur']) continue;
            $insert = $pdo->prepare('INSERT IGNORE INTO utilisateurs_badges(utilisateur_id,badge_id) VALUES(?,?)');
            $insert->execute([$userId,$badge['id']]);
            $points = (int)$badge['points'];
            if ($insert->rowCount() > 0 && $points > 0) {
                $pdo->prepare('UPDATE passeports SET niveau=1+FLOOR((points_total+?)/100),points_total=points_total+? WHERE utilisateur_id=?')->execute([$points,$points,$userId]);
                $pdo->prepare("INSERT INTO historique_points(utilisateur_id,points_delta,motif,reference_type,reference_id) VALUES(?,?,'Badge obtenu','badge',?)")->execute([$userId,$points,$badge['id']]);
            }
        }
    }
}
