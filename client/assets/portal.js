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

const profileEditDialog = document.querySelector('[data-profile-edit-dialog]');
document.addEventListener('click', (event) => {
  const target = event.target;
  if (!(target instanceof Element)) return;

  const openButton = target.closest('[data-profile-edit-open]');
  if (openButton) {
    if (!(profileEditDialog instanceof HTMLDialogElement)) {
      throw new Error('Fenêtre de modification du profil introuvable.');
    }
    profileEditDialog.showModal();
    profileEditDialog.querySelector('#profileEditNom')?.focus();
    return;
  }

  if (!(profileEditDialog instanceof HTMLDialogElement)) return;
  if (target.closest('[data-profile-edit-close]') || target === profileEditDialog) {
    profileEditDialog.close();
  }
});

document.addEventListener('submit', (event) => {
  const form = event.target;
  if (!(form instanceof HTMLFormElement)) return;
  if (form.hasAttribute('data-client-validation') && !form.checkValidity()) {
    event.preventDefault();
    const invalidField = form.querySelector(':invalid');
    invalidField?.focus();
    window.clientAlert('error', invalidField?.validationMessage || 'Vérifiez les champs du formulaire.');
    return;
  }
  if (!form.dataset.confirm) return;
  if (form.dataset.clientConfirmAccepted === 'true') {
    delete form.dataset.clientConfirmAccepted;
    return;
  }
  event.preventDefault();
  void window.clientConfirm(form.dataset.confirm).then((confirmed) => {
    if (!confirmed) return;
    form.dataset.clientConfirmAccepted = 'true';
    if (event.submitter instanceof HTMLElement && typeof form.requestSubmit === 'function') {
      form.requestSubmit(event.submitter);
    } else if (typeof form.requestSubmit === 'function') {
      form.requestSubmit();
    } else {
      form.submit();
    }
  });
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
    const message = `Impossible de lire la bibliothèque locale : ${error.message}`;
    setStatus(message);
    window.clientAlert('error', message);
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
      const message = `${destinations.length} destination(s) enregistrée(s) sur ce navigateur.`;
      setStatus(message);
      window.clientAlert('success', message);
    } catch (error) {
      const message = `Enregistrement impossible : ${error.message}`;
      setStatus(message);
      window.clientAlert('error', message);
    } finally {
      saveButton.disabled = false;
    }
  });

  clearButton.addEventListener('click', async () => {
    const confirmed = await window.clientConfirm('Vider la bibliothèque hors ligne de ce navigateur ?');
    if (!confirmed) return;
    try {
      localStorage.removeItem(storageKey);
      render();
      const message = 'Bibliothèque hors ligne vidée.';
      setStatus(message);
      window.clientAlert('success', message);
    } catch (error) {
      const message = `Suppression impossible : ${error.message}`;
      setStatus(message);
      window.clientAlert('error', message);
    }
  });
}


// Extracted from client/pages/dashboard.php
(function () {
    const slides = document.querySelectorAll('#heroBanner .hero-slide');
    if (slides.length < 2) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    let current = 0;
    setInterval(function () {
      slides[current].classList.remove('active');
      current = (current + 1) % slides.length;
      slides[current].classList.add('active');
    }, 3000);
  })();


// Extracted from client/pages/explore.php
(function () {
  // ===== Debounce sur la recherche =====
  const form  = document.getElementById('exploreForm');
  const input = document.getElementById('exploreSearch');
  if (form && input) {
    let timer = null;
    input.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(function () {
        if (typeof form.requestSubmit === 'function') form.requestSubmit();
        else form.submit();
      }, 350);
    });
  }

  // ===== Ouverture / fermeture du panneau géo =====
  const overlay   = document.getElementById('geoOverlay');
  const openBtn   = document.querySelector('[data-open-geo]');
  const closeBtn  = document.querySelector('[data-close-geo]');
  const applyBtn  = document.querySelector('[data-apply-geo]');
  const clearBtn  = document.querySelector('[data-clear-geo]');
  const district  = document.getElementById('geoDistrict');
  const regionSel = document.getElementById('geoRegion');
  const regionStep= document.getElementById('geoRegionStep');
  const deptSel   = document.getElementById('geoDept');
  const deptStep  = document.getElementById('geoDeptStep');

  if (openBtn && overlay) {
    openBtn.addEventListener('click', function () { overlay.hidden = false; });
  }
  if (closeBtn && overlay) {
    closeBtn.addEventListener('click', function () { overlay.hidden = true; });
  }
  if (overlay) {
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) overlay.hidden = true;
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') overlay.hidden = true;
    });
  }

  // ===== Affichage conditionnel région/département =====
  if (district && regionStep && deptStep) {
    district.addEventListener('change', function () {
      const isAutonome = district.selectedOptions[0]
        && district.selectedOptions[0].dataset.type === 'autonome';
      const hasDistrict = district.value !== '';

      if (regionStep) regionStep.hidden = !hasDistrict || isAutonome;
      if (deptStep)   deptStep.hidden   = !hasDistrict;

      if (regionSel) regionSel.value = '';
      if (deptSel)   deptSel.value   = '';
    });
  }

  // ===== Application / suppression des filtres =====
  if (applyBtn) {
    applyBtn.addEventListener('click', function () {
      const url = new URL(window.location.href);
      const map = {
        district_id:    district ? district.value : '',
        region_id:      regionSel ? regionSel.value : '',
        departement_id: deptSel ? deptSel.value : '',
      };
      for (const key in map) {
        if (map[key]) url.searchParams.set(key, map[key]);
        else url.searchParams.delete(key);
      }
      url.searchParams.delete('p');
      window.location.href = url.toString();
    });
  }

  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      const url = new URL(window.location.href);
      url.searchParams.delete('district_id');
      url.searchParams.delete('region_id');
      url.searchParams.delete('departement_id');
      url.searchParams.delete('p');
      window.location.href = url.toString();
    });
  }
})();


// Extracted from client/pages/login.php
document.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var input = document.getElementById(btn.dataset.passwordToggle);
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? 'Masquer' : 'Afficher';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        btn.setAttribute('aria-label', show ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        input.focus();
      });
    });


// Extracted from client/pages/register.php
document.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var input = document.getElementById(btn.dataset.passwordToggle);
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? 'Masquer' : 'Afficher';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        btn.setAttribute('aria-label', show ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        input.focus();
      });
    });

    (function () {
      var input = document.getElementById('password');
      var bars  = document.querySelectorAll('.password-strength .strength-bar');
      if (!input || !bars.length) return;

      input.addEventListener('input', function () {
        var v = input.value;
        var score = 0;
        if (v.length >= 10) score++;
        if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
        if (/\d/.test(v)) score++;
        if (/[^A-Za-z0-9]/.test(v)) score++;

        bars.forEach(function (bar, i) {
          bar.classList.remove('active-1', 'active-2', 'active-3', 'active-4');
          if (i < score) bar.classList.add('active-' + score);
        });
      });
    })();


// Extracted from client/pages/reset.php
document.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var input = document.getElementById(btn.dataset.passwordToggle);
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? 'Masquer' : 'Afficher';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        btn.setAttribute('aria-label', show ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        input.focus();
      });
    });


// Extracted from client/pages/videos.php
(function () {
  // ===== Autoplay intelligent + tap-to-play/pause =====
  const slides = document.querySelectorAll('.video-slide');

  // Observer : lance la vidéo visible, met en pause les autres
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        const video = entry.target.querySelector('.video-player');
        if (!video) return;
        if (entry.isIntersecting && entry.intersectionRatio >= 0.6) {
          video.play().then(function () {
            entry.target.classList.remove('is-paused');
          }).catch(function () { /* autoplay refusé → reste en pause */ });
        } else {
          video.pause();
        }
      });
    }, { threshold: [0, 0.6, 1] });

    slides.forEach(function (slide) { io.observe(slide); });
  }

  // Tap sur la vidéo → play/pause
  slides.forEach(function (slide) {
    const video = slide.querySelector('.video-player');
    if (!video) return;
    slide.addEventListener('click', function (e) {
      if (e.target.closest('a, button, form')) return;
      if (video.paused) {
        video.play().catch(function () {});
        slide.classList.remove('is-paused');
      } else {
        video.pause();
        slide.classList.add('is-paused');
      }
    });
  });

  // Clic sur le bouton like → animation + soumission du formulaire
  document.querySelectorAll('.video-action-form').forEach(function (form) {
    form.addEventListener('submit', function () {
      const btn = form.querySelector('.video-action-btn');
      if (btn) btn.classList.add('is-liked');
    });
  });
})();


// Extracted from client/pages/video_studio.php
(function () {
  const fileInput = document.getElementById('videoFile');
  const fileWrap  = document.getElementById('studioFile');
  const fileLabel = document.getElementById('studioFileName');
  const form      = document.getElementById('studioForm');

  if (fileInput) {
    fileInput.addEventListener('change', function () {
      const file = fileInput.files && fileInput.files[0];
      if (!file) return;
      const mo = (file.size / (1024 * 1024)).toFixed(1);
      fileLabel.textContent = file.name + ' · ' + mo + ' Mo';
      fileWrap.classList.add('has-file');
    });
  }

  if (form) {
    form.addEventListener('submit', function (e) {
      const submitBtn = form.querySelector('.studio-submit');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = '⏳ Envoi en cours…';
      }
    });
  }
})();


document.addEventListener("change", (event) => {
  if (event.target.matches("[data-avatar-autosubmit]")) event.target.form?.requestSubmit();
});
document.addEventListener("click", (event) => {
  const target = event.target;
  if (!(target instanceof Element)) return;
  const trigger = target.closest("[data-confirm-click]");
  if (!trigger || trigger.dataset.clientConfirmAccepted === 'true') {
    if (trigger) delete trigger.dataset.clientConfirmAccepted;
    return;
  }
  event.preventDefault();
  void window.clientConfirm(trigger.dataset.confirmClick).then((confirmed) => {
    if (!confirmed) return;
    trigger.dataset.clientConfirmAccepted = 'true';
    trigger.click();
  });
});


// Reload public Web Client pages when a published server-side content revision changes.
(() => {
  if (document.body?.dataset.contentSync !== 'on') return;
  const storageKey = 'tourisme_ivoire_content_version';
  const hasUnsavedChanges = () => [...document.forms].some((form) =>
    [...form.elements].some((field) => {
      if (!(field instanceof HTMLElement) || !('value' in field)) return false;
      if (field instanceof HTMLInputElement && field.type === 'file') return field.files?.length > 0;
      if (field instanceof HTMLInputElement && ['checkbox', 'radio'].includes(field.type)) return field.checked !== field.defaultChecked;
      if (field instanceof HTMLSelectElement) return [...field.options].some((option) => option.selected !== option.defaultSelected);
      if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement) return field.value !== field.defaultValue;
      return false;
    }),
  );
  const checkForContentUpdates = async () => {
    try {
      const response = await fetch('?page=content_updates', {
        headers: { Accept: 'application/json' },
        cache: 'no-store',
        credentials: 'same-origin',
      });
      if (!response.ok) return;
      const result = await response.json();
      const version = result?.data?.version;
      if (typeof version !== 'string' || version.length === 0) return;
      const previous = sessionStorage.getItem(storageKey);
      if (previous && previous !== version) {
        if (hasUnsavedChanges()) return;
        sessionStorage.setItem(storageKey, version);
        window.location.reload();
        return;
      }
      sessionStorage.setItem(storageKey, version);
    } catch (_) {
      // Browsing remains available if the update marker cannot be reached.
    }
  };
  void checkForContentUpdates();
  window.setInterval(checkForContentUpdates, 60000);
})();

// Poll persisted MySQL notifications while an authenticated Web Client page is open.
(() => {
  if (document.body?.dataset.notificationSync !== 'on') return;
  const storageKey = 'tourisme_ivoire_latest_notification_id';
  const checkNotifications = async () => {
    try {
      const response = await fetch('?page=notification_updates', {
        headers: { Accept: 'application/json' },
        cache: 'no-store',
        credentials: 'same-origin',
      });
      if (!response.ok) return;
      const payload = await response.json();
      const items = payload?.data?.items;
      if (!Array.isArray(items) || items.length === 0) return;
      const newest = items.reduce((latest, item) =>
        Number(item?.id) > Number(latest?.id) ? item : latest, items[0]);
      const latestId = Number(newest?.id);
      if (!Number.isSafeInteger(latestId) || latestId < 1) return;
      const previousId = Number(sessionStorage.getItem(storageKey));
      if (previousId > 0 && latestId > previousId && newest.type === 'nouveau_contenu') {
        window.clientAlert('info', newest.titre || 'Nouveau contenu publié');
      }
      sessionStorage.setItem(storageKey, String(Math.max(previousId || 0, latestId)));
    } catch (_) {
      // Database notifications are an enhancement; browsing remains usable offline.
    }
  };
  void checkNotifications();
  window.setInterval(checkNotifications, 60000);
})();
