<?php
declare(strict_types=1);

final class MailService {
    public static function send(string $to, string $subject, string $html, string $text): bool {
        $autoload = APP_ROOT . '/vendor/autoload.php';
        if (!is_file($autoload)) { error_log('Email delivery unavailable: Composer dependencies are not installed.'); return false; }
        require_once $autoload;
        $host = trim((string)env_value('SMTP_HOST', ''));
        $username = trim((string)env_value('SMTP_USERNAME', 'Tra bi dorgeles'));
        $password = (string)env_value('SMTP_PASSWORD', '');
        $port = filter_var(env_value('SMTP_PORT'), FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>65535]]);
        $from = trim((string)env_value('MAIL_FROM', ''));
        $fromName = trim((string)env_value('MAIL_FROM_NAME', 'Tourisme Ivoire'));
        $encryption = strtolower(trim((string)env_value('SMTP_ENCRYPTION', 'tls')));
        if ($host === '' || $username === '' || $password === '' || !$port || !filter_var($from,FILTER_VALIDATE_EMAIL) || !in_array($encryption,['tls','ssl','none'],true)) {
            error_log('Email delivery unavailable: SMTP configuration is incomplete or invalid.'); return false;
        }
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP(); $mail->Host=$host; $mail->SMTPAuth=true; $mail->Username=$username; $mail->Password=$password;
            $mail->Port=(int)$port; $mail->CharSet='UTF-8';
            if ($encryption === 'tls') $mail->SMTPSecure=\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            elseif ($encryption === 'ssl') $mail->SMTPSecure=\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            else $mail->SMTPSecure='';
            $mail->setFrom($from,$fromName); $mail->addAddress($to); $mail->isHTML(true);
            $mail->Subject=$subject; $mail->Body=self::layout($html); $mail->AltBody=$text;
            $mail->send(); return true;
        } catch (Throwable $e) {
            $detail = str_replace([$username, $password], '[redacted]', $e->getMessage());
            error_log('Email delivery failed (' . get_class($e) . '): ' . preg_replace('/[\r\n]+/',' ', $detail));
            return false;
        }
    }
    public static function welcome(string $to, string $name): bool {
        $safe=htmlspecialchars($name,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        return self::send($to,'Bienvenue sur Tourisme Ivoire','<h1>Bienvenue, '.$safe.' !</h1><p>Votre compte est prêt. Découvrez les destinations et préparez votre prochain voyage.</p>'.self::button('Découvrir le site',self::siteUrl()),'Bienvenue sur Tourisme Ivoire. Votre compte est prêt.');
    }
    public static function resetCode(string $to, string $token): bool {
        $safe=htmlspecialchars($token,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $url=rtrim(self::siteUrl(),'/').'/client/index.php?page=reset';
        return self::send($to,'Réinitialisation de votre mot de passe','<h1>Réinitialiser votre mot de passe</h1><p>Utilisez ce code dans l’application ou sur le site. Il expire dans une heure.</p><p style="font-size:22px;font-weight:700;letter-spacing:3px">'.$safe.'</p>'.self::button('Ouvrir la page de réinitialisation',$url),'Code de réinitialisation (valide une heure) : '.$token.'
Page : '.$url);
    }
    public static function passwordChanged(string $to): bool {
        return self::send($to,'Votre mot de passe a été modifié','<h1>Mot de passe modifié</h1><p>Le mot de passe de votre compte Tourisme Ivoire vient d’être modifié.</p><p>Si vous n’êtes pas à l’origine de cette action, contactez notre assistance.</p>','Le mot de passe de votre compte Tourisme Ivoire vient d’être modifié.');
    }
    private static function siteUrl(): string { $url=rtrim(trim((string)env_value('SITE_URL','')),'/'); return preg_match('~^https?://[^\s]+$~i',$url)?$url:'#'; }
    private static function button(string $label,string $url): string { $safe=htmlspecialchars($url,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); return '<p><a href="'.$safe.'" style="display:inline-block;background:#ea580c;color:#fff;text-decoration:none;padding:12px 20px;border-radius:8px">'.htmlspecialchars($label,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</a></p>'; }
    private static function layout(string $content): string { return '<!doctype html><html lang="fr"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><body style="margin:0;background:#f7f4f1;font-family:Arial,sans-serif;color:#29231f"><div style="max-width:600px;margin:24px auto;padding:32px;background:#fff;border-radius:12px;line-height:1.6"><div style="color:#ea580c;font-weight:bold;margin-bottom:24px">TOURISME IVOIRE</div>'.$content.'<hr style="border:0;border-top:1px solid #eee;margin:28px 0"><small style="color:#777">Ce message concerne votre compte Tourisme Ivoire.</small></div></body></html>'; }
}
