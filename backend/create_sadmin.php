<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/config/bootstrap.php';

function prompt(string $label): string {
    fwrite(STDOUT,$label);
    $value = fgets(STDIN);
    if ($value === false) throw new RuntimeException('Lecture de la saisie impossible.');
    return trim($value);
}

function promptHidden(string $label): string {
    if (PHP_OS_FAMILY === 'Windows') {
        fwrite(STDOUT,$label);
        $command = "powershell.exe -NoProfile -Command \"\$secret = Read-Host -AsSecureString; \$pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR(\$secret); try { [Runtime.InteropServices.Marshal]::PtrToStringBSTR(\$pointer) } finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR(\$pointer) }\"";
        exec($command,$output,$exitCode);
        if ($exitCode !== 0 || !$output) throw new RuntimeException('Saisie masquée du mot de passe impossible.');
        return rtrim(implode(PHP_EOL,$output),"\r\n");
    }

    $terminalState = trim((string)shell_exec('stty -g 2>/dev/null'));
    if ($terminalState === '') throw new RuntimeException('Lancez cette commande depuis un terminal interactif pour saisir le mot de passe en mode masqué.');
    exec('stty -echo');
    try {
        fwrite(STDOUT,$label);
        $value = fgets(STDIN);
        if ($value === false) throw new RuntimeException('Lecture du mot de passe impossible.');
        return rtrim($value,"\r\n");
    } finally {
        exec('stty ' . escapeshellarg($terminalState));
        fwrite(STDOUT,PHP_EOL);
    }
}

try {
    $pdo = db();
    $pdo->exec("CREATE TABLE IF NOT EXISTS role_permissions (
        role_id BIGINT UNSIGNED NOT NULL,
        module_key VARCHAR(40) NOT NULL,
        can_view BOOLEAN NOT NULL DEFAULT FALSE,
        can_manage BOOLEAN NOT NULL DEFAULT FALSE,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(role_id,module_key),
        FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    $insertRole = $pdo->prepare('INSERT INTO roles(code,nom,description) VALUES(?,?,?) ON DUPLICATE KEY UPDATE nom=VALUES(nom),description=VALUES(description)');
    foreach ([
        ['Sadmin','Super administrateur','Accès complet au back-office.'],
        ['Client','Client','Compte standard de l’application.'],
    ] as $role) {
        $insertRole->execute($role);
    }
    $pdo->exec("UPDATE roles SET code='Sadmin' WHERE BINARY code='sadmin'");
    $pdo->exec("UPDATE roles SET code='Client' WHERE BINARY code='client'");

    $name = prompt('Nom de l’administrateur : ');
    $email = strtolower(prompt('Adresse e-mail : '));
    if ($name==='' || mb_strlen($name)>100 || !filter_var($email,FILTER_VALIDATE_EMAIL) || mb_strlen($email)>190) {
        throw new InvalidArgumentException('Nom ou adresse e-mail invalide.');
    }
    $password = promptHidden('Mot de passe (8 caractères minimum) : ');
    $confirmation = promptHidden('Confirmer le mot de passe : ');
    if (strlen($password)<8 || !hash_equals($password,$confirmation)) {
        throw new InvalidArgumentException('Les mots de passe doivent correspondre et contenir au moins 8 caractères.');
    }
    $passwordHash = password_hash($password,PASSWORD_DEFAULT);
    if ($passwordHash===false) throw new RuntimeException('Le hachage du mot de passe a échoué.');
    unset($password,$confirmation);

    $lock = (int)$pdo->query("SELECT GET_LOCK('tourisme_ivoire:create_sadmin',10)")->fetchColumn();
    if ($lock !== 1) throw new RuntimeException('Impossible de verrouiller la création initiale du Sadmin. Réessayez.');
    try {
        $existingSadmin = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE `role`='Sadmin' AND deleted_at IS NULL")->fetchColumn();
        if ((int)$existingSadmin>0) throw new RuntimeException('Un compte Sadmin existe déjà. Aucun nouveau compte n’a été créé.');
        $existingEmail = $pdo->prepare('SELECT id FROM utilisateurs WHERE email=?');
        $existingEmail->execute([$email]);
        if ($existingEmail->fetchColumn()) throw new RuntimeException('Cette adresse e-mail est déjà utilisée.');

        $pdo->beginTransaction();
        try {
            $insertUser = $pdo->prepare("INSERT INTO utilisateurs(nom,email,`role`,mot_de_passe_hash,statut) VALUES(?,?,'Sadmin',?,'actif')");
            $insertUser->execute([$name,$email,$passwordHash]);
            $userId = (int)$pdo->lastInsertId();
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    } finally {
        $pdo->query("SELECT RELEASE_LOCK('tourisme_ivoire:create_sadmin')");
    }

    fwrite(STDOUT,"Compte Sadmin créé pour {$email}.\nVous pouvez maintenant vous connecter au back-office.\n");
} catch (Throwable $e) {
    fwrite(STDERR,"Création du Sadmin échouée : {$e->getMessage()}\n");
    exit(1);
}
