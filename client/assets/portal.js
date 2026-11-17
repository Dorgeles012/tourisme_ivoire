'use strict';

document.addEventListener('click', (event) => {
  const button = event.target.closest('[data-password-toggle]');
  if (!button) return;
  const input = document.getElementById(button.dataset.passwordToggle);
  if (!(input instanceof HTMLInputElement)) return;

  const visible = input.type === 'password';
  input.type = visible ? 'text' : 'password';
  button.classList.toggle('is-visible', visible);
  button.setAttribute('aria-pressed', String(visible));
  button.setAttribute('aria-label', visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
  button.textContent = visible ? 'Masquer' : 'Afficher';
});
