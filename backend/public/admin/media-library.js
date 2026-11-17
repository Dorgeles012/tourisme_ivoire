(() => {
  'use strict';
  const words = {
    confirm: 'Confirmer le téléversement ?',
    confirmText: 'Les fichiers seront enregistrés dans le dossier de leur catégorie.',
    send: 'Téléverser', cancel: 'Annuler', uploading: 'Téléversement en cours…',
    success: 'Téléversement terminé', done: 'Image(s) enregistrée(s) dans la bibliothèque.',
    error: 'Échec du téléversement', cancelTitle: 'Téléversement annulé', cancelled: 'La requête a été interrompue.',
    invalid: 'Sélectionnez une catégorie et de 1 à 4 images JPG, PNG ou WebP de 8 Mo maximum.',
    copied: 'Chemin copié', copyError: 'Impossible de copier le chemin.',
    editTitle: 'Enregistrer les modifications ?', editText: 'La catégorie et les informations du média seront mises à jour.',
    deleteTitle: 'Supprimer cette image ?', deleteText: 'Le fichier sera supprimé du serveur et de la bibliothèque. Cette action est définitive.',
    saved: 'Modifications enregistrées.', deleted: 'Image supprimée.',
  };
  const iconColor = '#e67e22';
  const notify = (icon, title, text) => Swal.fire({ icon, title, text, confirmButtonColor: iconColor });
  const parseJsonResponse = (raw) => {
    try { return JSON.parse(raw); } catch (_) {
      // PHP peut émettre un diagnostic de démarrage avant le JSON si son
      // dossier temporaire d’upload est inaccessible; récupérer le JSON utile.
      const first = raw.indexOf('{');
      const last = raw.lastIndexOf('}');
      if (first < 0 || last < first) return null;
      try { return JSON.parse(raw.slice(first, last + 1)); } catch (_) { return null; }
    }
  };
  const flash = document.getElementById('media-flash');
  if (flash && window.Swal) {
    try {
      const data = JSON.parse(flash.textContent || '{}');
      const key = typeof data.text === 'string' && data.text.startsWith('media:') ? data.text.slice(6) : data.text;
      const map = {
        upload_success: words.done, update_success: words.saved, delete_success: words.deleted,
        delete_file_warning: 'La ligne a été supprimée, mais le fichier physique doit être retiré manuellement.',
        post_too_large: 'La requête dépasse la limite post_max_size configurée dans PHP.',
        csrf: 'Jeton de sécurité invalide. Rechargez la page et réessayez.',
      };
      notify(data.type || 'success', data.type === 'error' ? 'Erreur' : 'Terminé', map[key] || key || 'Opération terminée.');
    } catch (_) { notify('error', 'Erreur', 'La réponse du serveur est illisible.'); }
  }

  const input = document.getElementById('media-files');
  const drop = document.getElementById('drop-zone');
  const preview = document.getElementById('preview-grid');
  const category = document.getElementById('media-category');
  const destination = document.getElementById('media-destination');
  const profileUser = document.getElementById('media-profile-user');
  const main = document.getElementById('media-main');
  const allowedCategories = ['destinations', 'sites_touristiques'];
  const setDestinationState = () => {
    if (!destination) return;
    const allowed = allowedCategories.includes(category?.value || '');
    destination.disabled = !allowed;
    if (!allowed) destination.value = '';
    if (main) {
      main.disabled = !allowed || !destination.value;
      if (main.disabled) main.checked = false;
    }
  };
  const setProfileState = () => {
    if (!profileUser) return;
    profileUser.disabled = category?.value !== 'profils';
    if (profileUser.disabled) profileUser.value = '';
  };
  if (category) category.addEventListener('change', () => { setDestinationState(); setProfileState(); });
  if (destination) destination.addEventListener('change', setDestinationState);
  setDestinationState(); setProfileState();

  const showFiles = (files) => {
    if (!preview) return;
    preview.replaceChildren();
    [...files].forEach((file) => {
      const card = document.createElement('div');
      card.className = 'preview-item';
      const image = document.createElement('img');
      const label = document.createElement('span');
      label.textContent = `${file.name} · ${(file.size / 1048576).toFixed(1)} Mo`;
      if (file.type.startsWith('image/')) {
        const objectUrl = URL.createObjectURL(file);
        image.src = objectUrl;
        image.onload = () => URL.revokeObjectURL(objectUrl);
        card.append(image);
      }
      card.append(label);
      preview.append(card);
    });
  };
  if (input) input.addEventListener('change', () => showFiles(input.files || []));
  if (drop && input) {
    ['dragenter', 'dragover'].forEach((eventName) => drop.addEventListener(eventName, (event) => {
      event.preventDefault();
      drop.classList.add('dragging');
    }));
    ['dragleave', 'drop'].forEach((eventName) => drop.addEventListener(eventName, (event) => {
      event.preventDefault();
      drop.classList.remove('dragging');
    }));
    drop.addEventListener('drop', (event) => {
      const files = [...(event.dataTransfer?.files || [])];
      if (!files.length) return;
      const transfer = new DataTransfer();
      files.slice(0, 4).forEach((file) => transfer.items.add(file));
      input.files = transfer.files;
      showFiles(input.files);
      if (files.length > 4) notify('warning', 'Limite de fichiers', 'Quatre images maximum peuvent être envoyées en une fois.');
    });
  }

  const uploadForm = document.getElementById('media-upload-form');
  if (uploadForm) uploadForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const files = [...(input?.files || [])];
    const validMime = ['image/jpeg', 'image/png', 'image/webp'];
    if (input?.accept.includes('image/avif')) validMime.push('image/avif');
    if (!category?.value || files.length < 1 || files.length > 4 || files.some((file) => !validMime.includes(file.type) || file.size > 8 * 1024 * 1024)) {
      return notify('error', 'Vérifiez votre sélection', words.invalid);
    }
    const confirmed = await Swal.fire({
      icon: 'question', title: words.confirm, text: words.confirmText,
      showCancelButton: true, confirmButtonText: words.send, cancelButtonText: words.cancel,
      confirmButtonColor: iconColor, cancelButtonColor: '#777',
    });
    if (!confirmed.isConfirmed) return;

    const xhr = new XMLHttpRequest();
    const data = new FormData(uploadForm);
    let completed = false;
    const modalPromise = Swal.fire({
      title: words.uploading,
      html: '<progress id="swal-upload-progress" value="0" max="100" style="width:100%;accent-color:#e67e22"></progress><p id="swal-upload-label" style="margin:.7rem 0 0">0 %</p>',
      showCancelButton: true, showConfirmButton: false, cancelButtonText: words.cancel,
      allowOutsideClick: false, allowEscapeKey: false,
      didOpen: () => Swal.getCancelButton()?.focus(),
    });
    modalPromise.then((result) => {
      if (!result.dismiss || completed) return;
      xhr.abort();
    });
    xhr.open('POST', 'image.php', true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.upload.onprogress = (progress) => {
      if (!progress.lengthComputable) return;
      const value = Math.round((progress.loaded / progress.total) * 100);
      const bar = document.getElementById('swal-upload-progress');
      const label = document.getElementById('swal-upload-label');
      if (bar) bar.value = value;
      if (label) label.textContent = `${value} %`;
    };
    xhr.onload = async () => {
      completed = true;
      Swal.close();
      const result = parseJsonResponse(xhr.responseText);
      if (xhr.status >= 200 && xhr.status < 300 && result?.success) {
        await notify('success', words.success, `${result.count || files.length} ${words.done}`);
        window.location.href = 'image.php';
      } else {
        notify('error', words.error, result?.message || 'Réponse serveur invalide. Vérifiez les journaux PHP.');
      }
    };
    xhr.onerror = () => { completed = true; Swal.close(); notify('error', words.error, 'Connexion interrompue. Réessayez.'); };
    xhr.onabort = () => { completed = true; Swal.close(); notify('info', words.cancelTitle, words.cancelled); };
    xhr.send(data);
  });

  document.querySelectorAll('[data-delete-media]').forEach((form) => form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const result = await Swal.fire({ icon: 'warning', title: words.deleteTitle, text: words.deleteText,
      showCancelButton: true, confirmButtonText: 'Supprimer', cancelButtonText: words.cancel,
      confirmButtonColor: '#c0392b', cancelButtonColor: '#777' });
    if (!result.isConfirmed) return;
    Swal.fire({ title: 'Suppression en cours…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    HTMLFormElement.prototype.submit.call(form);
  }));

  const editForm = document.getElementById('media-edit-form');
  const editCategory = document.getElementById('edit-category');
  const editDestination = document.getElementById('edit-destination');
  const editProfileUser = document.getElementById('edit-profile-user');
  const mainCheckbox = editForm?.querySelector('[name="principale"]');
  const syncEditDestination = () => {
    if (!editDestination) return;
    const allowed = allowedCategories.includes(editCategory?.value || '');
    editDestination.disabled = !allowed;
    if (!allowed) editDestination.value = '';
    if (mainCheckbox) {
      mainCheckbox.disabled = !allowed || !editDestination.value || editForm.elements.statut.value !== 'actif';
      if (mainCheckbox.disabled) mainCheckbox.checked = false;
    }
  };
  editCategory?.addEventListener('change', syncEditDestination);
  editDestination?.addEventListener('change', syncEditDestination);
  editForm?.elements.statut?.addEventListener('change', syncEditDestination);
  syncEditDestination();
  const syncEditProfile = () => {
    if (!editProfileUser) return;
    editProfileUser.disabled = editCategory?.value !== 'profils';
    if (editProfileUser.disabled) editProfileUser.value = '';
  };
  editCategory?.addEventListener('change', syncEditProfile);
  syncEditProfile();
  if (editForm) editForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const result = await Swal.fire({ icon: 'question', title: words.editTitle, text: words.editText,
      showCancelButton: true, confirmButtonText: 'Enregistrer', cancelButtonText: words.cancel,
      confirmButtonColor: iconColor, cancelButtonColor: '#777' });
    if (!result.isConfirmed) return;
    Swal.fire({ title: 'Enregistrement…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    HTMLFormElement.prototype.submit.call(editForm);
  });

  document.querySelectorAll('[data-copy]').forEach((button) => button.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(button.dataset.copy || '');
      notify('success', words.copied, button.dataset.copy || '');
    } catch (_) { notify('error', words.error, words.copyError); }
  }));
})();
