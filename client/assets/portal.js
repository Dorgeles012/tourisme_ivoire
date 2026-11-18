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

document.addEventListener('submit', (event) => {
  const form = event.target;
  if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) return;
  if (!window.confirm(form.dataset.confirm)) event.preventDefault();
});

const offlineLibrary = document.querySelector('[data-offline-library]');
if (offlineLibrary) {
  const storageKey = 'tourisme_ivoire_offline_destinations';
  const status = offlineLibrary.querySelector('[data-offline-status]');
  const list = offlineLibrary.querySelector('[data-offline-items]');
  const saveButton = offlineLibrary.querySelector('[data-offline-save]');
  const clearButton = offlineLibrary.querySelector('[data-offline-clear]');
  if (!status || !list || !saveButton || !clearButton) {
    throw new Error('Interface de bibliothèque hors ligne incomplète.');
  }

  const setStatus = (message) => {
    status.textContent = message;
  };

  const readSaved = () => {
    const raw = localStorage.getItem(storageKey);
    if (!raw) return [];
    const saved = JSON.parse(raw);
    if (!Array.isArray(saved)) throw new TypeError('Format de bibliothèque invalide.');
    return saved;
  };

  const render = () => {
    list.replaceChildren();
    const saved = readSaved();
    if (saved.length === 0) {
      const empty = document.createElement('p');
      empty.className = 'empty';
      empty.textContent = 'Aucune destination enregistrée sur ce navigateur.';
      list.append(empty);
      return;
    }

    saved.forEach((destination) => {
      const row = document.createElement('article');
      row.className = 'list-row';
      const details = document.createElement('div');
      const title = document.createElement('strong');
      title.textContent = destination.nom || 'Destination';
      const location = document.createElement('p');
      location.textContent = [destination.localite, destination.categorie]
        .filter(Boolean)
        .join(' · ');
      const description = document.createElement('p');
      description.textContent = destination.description || '';
      details.append(title, location, description);
      row.append(details);
      if (Number.isInteger(Number(destination.id)) && Number(destination.id) > 0) {
        const link = document.createElement('a');
        link.href = `?page=destination&id=${encodeURIComponent(destination.id)}`;
        link.textContent = 'Voir la fiche';
        row.append(link);
      }
      list.append(row);
    });
  };

  try {
    render();
  } catch (error) {
    setStatus(`Impossible de lire la bibliothèque locale : ${error.message}`);
  }

  saveButton.addEventListener('click', async () => {
    saveButton.disabled = true;
    setStatus('Téléchargement des destinations…');
    try {
      const response = await fetch('?page=offline_library&offline_data=1', {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
      });
      const result = await response.json();
      if (!response.ok) {
        throw new Error(result.error || 'Le téléchargement a échoué.');
      }
      const destinations = result.destinations;
      if (!Array.isArray(destinations)) throw new TypeError('Liste de destinations invalide.');
      localStorage.setItem(storageKey, JSON.stringify(destinations));
      render();
      setStatus(`${destinations.length} destination(s) enregistrée(s) sur ce navigateur.`);
    } catch (error) {
      setStatus(`Enregistrement impossible : ${error.message}`);
    } finally {
      saveButton.disabled = false;
    }
  });

  clearButton.addEventListener('click', () => {
    if (!window.confirm('Vider la bibliothèque hors ligne de ce navigateur ?')) return;
    try {
      localStorage.removeItem(storageKey);
      render();
      setStatus('Bibliothèque hors ligne vidée.');
    } catch (error) {
      setStatus(`Suppression impossible : ${error.message}`);
    }
  });
}
