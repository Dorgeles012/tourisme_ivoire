<?php
declare(strict_types=1);
final class SettingsController {
    public static function show(): never {
        $stmt = db()->prepare('SELECT theme,notifications_json,accessibilite_json,mode_hors_ligne FROM parametres_utilisateurs WHERE utilisateur_id=?');
        $stmt->execute([AuthMiddleware::userId()]);
        $settings = $stmt->fetch();
        if (!$settings) api_error('not_found','Paramètres utilisateur introuvables.',404);
        foreach (['notifications_json','accessibilite_json'] as $field) {
            $settings[$field] = $settings[$field] === null ? new stdClass() : json_decode($settings[$field],true,512,JSON_THROW_ON_ERROR);
        }
        $settings['mode_hors_ligne'] = (bool)$settings['mode_hors_ligne'];
        json_response(['data' => $settings]);
    }

    public static function update(): never {
        $userId = AuthMiddleware::userId();
        $data = request_json();
        $theme = $data['theme'] ?? null;
        if ($theme !== null && !in_array($theme,['systeme','clair','sombre'],true)) api_error('validation_error','Thème invalide.',422);
        foreach (['notifications','accessibilite'] as $key) {
            if (isset($data[$key]) && !is_array($data[$key])) api_error('validation_error',"$key doit être un objet JSON.",422);
        }
        $current = db()->prepare('SELECT theme,notifications_json,accessibilite_json,mode_hors_ligne FROM parametres_utilisateurs WHERE utilisateur_id=?');
        $current->execute([$userId]);
        $stored = $current->fetch();
        if (!$stored) api_error('not_found','Paramètres utilisateur introuvables.',404);
        $notifications = isset($data['notifications']) ? json_encode($data['notifications'],JSON_THROW_ON_ERROR) : $stored['notifications_json'];
        $accessibility = isset($data['accessibilite']) ? json_encode($data['accessibilite'],JSON_THROW_ON_ERROR) : $stored['accessibilite_json'];
        $offline = array_key_exists('mode_hors_ligne',$data) ? (int)(bool)$data['mode_hors_ligne'] : (int)$stored['mode_hors_ligne'];
        db()->prepare('UPDATE parametres_utilisateurs SET theme=?,notifications_json=?,accessibilite_json=?,mode_hors_ligne=? WHERE utilisateur_id=?')
            ->execute([$theme ?? $stored['theme'],$notifications,$accessibility,$offline,$userId]);
        self::show();
    }
}
