<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/admin/bootstrap.php';
$admin = admin_require_login();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_check_csrf();
    $threadId = filter_var($_POST['thread_id'] ?? null, FILTER_VALIDATE_INT);
    $message = trim((string)($_POST['message'] ?? ''));
    $status = in_array($_POST['status'] ?? '', ['en_cours','resolu'], true) ? (string)$_POST['status'] : 'en_cours';
    if (!$threadId || $threadId < 1 || $message === '' || mb_strlen($message) > 5000) {
        admin_flash('Un message valide est requis (5000 caractères maximum).', 'error');
        header('Location: support.php'); exit;
    }
    $pdo = db(); $pdo->beginTransaction();
    try {
        $check = $pdo->prepare('SELECT utilisateur_id FROM demandes_assistance WHERE id=? FOR UPDATE');
        $check->execute([$threadId]); $ownerId = $check->fetchColumn();
        if (!$ownerId) throw new RuntimeException('Demande introuvable.');
        $pdo->prepare("INSERT INTO messages_assistance(demande_id,sender_id,sender_role,body) VALUES(?,?,'Sadmin',?)")->execute([$threadId, (int)$admin['id'], $message]);
        $pdo->prepare('UPDATE demandes_assistance SET status=? WHERE id=?')->execute([$status, $threadId]);
        $pdo->prepare('INSERT INTO notifications(utilisateur_id,titre,contenu,type,cible_url) VALUES(?,?,?,?,?)')->execute([(int)$ownerId, 'Réponse de l’assistance', mb_strimwidth($message, 0, 160, '…'), 'assistance', 'messages?id=' . $threadId]);
        $pdo->commit(); admin_flash('Réponse transmise au client.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Admin support reply failed: ' . $e->getMessage()); admin_flash('La réponse n’a pas pu être enregistrée.', 'error');
    }
    header('Location: support.php?id=' . (int)$threadId); exit;
}
$csrf = admin_csrf();
$threads = db()->query('SELECT t.id,t.subject,t.status,t.created_at,t.updated_at,u.nom,u.email,(SELECT COUNT(*) FROM messages_assistance m WHERE m.demande_id=t.id) AS message_count FROM demandes_assistance t JOIN utilisateurs u ON u.id=t.utilisateur_id ORDER BY FIELD(t.status,\'ouvert\',\'en_cours\',\'resolu\'),t.updated_at DESC LIMIT 100')->fetchAll();
$selectedId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$thread = null; $messages = [];
if ($selectedId && $selectedId > 0) {
    $stmt = db()->prepare('SELECT t.*,u.nom,u.email FROM demandes_assistance t JOIN utilisateurs u ON u.id=t.utilisateur_id WHERE t.id=?');
    $stmt->execute([$selectedId]); $thread = $stmt->fetch() ?: null;
    if ($thread) { $stmt = db()->prepare('SELECT m.sender_role,m.body,m.created_at,u.nom FROM messages_assistance m JOIN utilisateurs u ON u.id=m.sender_id WHERE m.demande_id=? ORDER BY m.created_at,m.id'); $stmt->execute([$selectedId]); $messages = $stmt->fetchAll(); }
}
?><!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Assistance clients — Administration</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:#f4f6f9}.support-wrap{padding:28px}.support-card{background:#fff;border:1px solid #e6eaf0;border-radius:14px;padding:20px;margin-bottom:16px}.bubble{padding:13px;border-radius:12px;background:#f1f5f2;margin:10px 0}.bubble.admin{background:#e9f2fb}</style></head><body><div class="d-flex"><div><?php require __DIR__ . '/_sidebar.php'; ?></div><main class="support-wrap flex-grow-1"><h1 class="h3 mb-4">Assistance clients</h1><?php if ($message = admin_flash()): ?><div class="alert alert-info"><?= admin_escape($message) ?></div><?php endif; ?><section class="support-card"><h2 class="h5">Demandes reçues</h2><?php if (!$threads): ?><p class="text-secondary mb-0">Aucune demande d’assistance.</p><?php else: ?><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Sujet</th><th>Client</th><th>Statut</th><th>Dernière activité</th><th></th></tr></thead><tbody><?php foreach ($threads as $item): ?><tr><td><?= admin_escape($item['subject']) ?></td><td><?= admin_escape($item['nom']) ?><br><small><?= admin_escape($item['email']) ?></small></td><td><?= admin_escape($item['status']) ?></td><td><?= admin_escape($item['updated_at']) ?></td><td><a class="btn btn-sm btn-outline-success" href="support.php?id=<?= (int)$item['id'] ?>">Ouvrir (<?= (int)$item['message_count'] ?>)</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section><?php if ($thread): ?><section class="support-card"><h2 class="h5"><?= admin_escape($thread['subject']) ?></h2><p class="text-secondary">Client : <?= admin_escape($thread['nom']) ?> · <?= admin_escape($thread['email']) ?></p><?php foreach ($messages as $item): ?><div class="bubble <?= $item['sender_role'] === 'Sadmin' ? 'admin' : '' ?>"><b><?= admin_escape($item['sender_role'] === 'Sadmin' ? 'Assistance' : $item['nom']) ?></b><small class="text-secondary"> · <?= admin_escape($item['created_at']) ?></small><div class="mt-2"><?= nl2br(admin_escape($item['body'])) ?></div></div><?php endforeach; ?><form method="post" class="mt-4"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="thread_id" value="<?= (int)$thread['id'] ?>"><label class="form-label" for="message">Votre réponse</label><textarea class="form-control" id="message" name="message" maxlength="5000" required rows="4"></textarea><div class="d-flex gap-2 mt-3"><select class="form-select" name="status" aria-label="Statut après réponse"><option value="en_cours">En cours</option><option value="resolu">Résolu</option></select><button class="btn btn-success" type="submit">Envoyer au client</button></div></form></section><?php endif; ?></main></div></body></html>
