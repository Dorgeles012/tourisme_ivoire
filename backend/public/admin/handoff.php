<?php
header("Content-Security-Policy: default-src 'none'; script-src 'unsafe-inline'; style-src 'unsafe-inline'; connect-src 'self'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'");
header('Cache-Control: no-store, max-age=0');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="referrer" content="no-referrer">
<title>Ouverture du back-office — Tourisme Ivoire</title>
<style>body{font:16px system-ui,sans-serif;max-width:36rem;margin:15vh auto;padding:1rem;color:#24313d}h1{color:#c45b32}#status{padding:1rem;background:#f5f2ec;border-radius:.75rem}</style>
</head>
<body>
<main><h1>Tourisme Ivoire</h1><p id="status" role="status">Ouverture sécurisée du back-office…</p></main>
<script>
(() => {
  const status = document.getElementById('status');
  const code = window.location.hash.slice(1);
  history.replaceState(null, '', window.location.pathname + window.location.search);
  if (!/^[a-f0-9]{64}$/.test(code)) {
    status.textContent = 'Lien invalide ou expiré. Revenez dans l’application et réessayez.';
    return;
  }
  fetch('../api/admin/handoff/consume', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
    body: JSON.stringify({code})
  }).then(async response => {
    const payload = await response.json();
    if (!response.ok || !payload.data || !payload.data.redirect) throw new Error('handoff');
    window.location.replace(payload.data.redirect);
  }).catch(() => {
    status.textContent = 'La session administrateur n’a pas pu être ouverte. Revenez dans l’application et réessayez.';
  });
})();
</script>
</body>
</html>
