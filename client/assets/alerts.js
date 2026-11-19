'use strict';

(function () {
  const alertTypes = ['success', 'error', 'warning', 'info', 'question'];
  const titles = {
    success: 'Succès',
    error: 'Erreur',
    warning: 'Attention',
    info: 'Information',
    question: 'Confirmation',
  };
  const queue = [];
  let queueActive = false;

  const renderFallback = (alert) => {
    const container = document.createElement('div');
    container.className = 'client-alert-fallback';
    container.setAttribute('role', alert.type === 'error' ? 'alert' : 'status');
    const title = document.createElement('strong');
    title.textContent = alert.title;
    const message = document.createElement('p');
    message.textContent = alert.message;
    const close = document.createElement('button');
    close.type = 'button';
    close.textContent = 'Fermer';
    close.addEventListener('click', () => container.remove());
    container.append(title, message, close);
    document.body.append(container);
    window.setTimeout(() => container.remove(), 9000);
  };

  const drainQueue = async () => {
    if (queueActive) return;
    queueActive = true;
    while (queue.length) {
      const alert = queue.shift();
      if (typeof window.Swal?.fire !== 'function') {
        renderFallback(alert);
        continue;
      }

      try {
        await window.Swal.fire({
          icon: alert.type,
          title: alert.title,
          text: alert.message,
          confirmButtonText: 'Compris',
          timer: alert.timer,
          timerProgressBar: true,
          showClass: { popup: 'client-swal-show' },
          hideClass: { popup: 'client-swal-hide' },
          customClass: {
            popup: 'client-swal-popup',
            title: 'client-swal-title',
            htmlContainer: 'client-swal-message',
            confirmButton: 'client-swal-confirm',
          },
        });
      } catch (error) {
        console.error('Impossible d’afficher une alerte SweetAlert2.', error);
        renderFallback(alert);
      }
    }
    queueActive = false;
  };

  window.clientAlert = (type, message, options = {}) => {
    const alertType = alertTypes.includes(type) ? type : 'info';
    const alertMessage = String(message ?? '').trim();
    const alertTitle = String(options.title || titles[alertType]).trim();
    if (!alertMessage && !alertTitle) return;

    queue.push({
      type: alertType,
      message: alertMessage,
      title: alertTitle,
      timer: Number.isFinite(options.timer) ? options.timer : 6000,
    });
    void drainQueue();
  };

  window.clientConfirm = async (message, title = 'Confirmer cette action') => {
    if (typeof window.Swal?.fire !== 'function') {
      window.clientAlert('warning', 'La confirmation ne peut pas être ouverte. Réessayez après avoir rechargé la page.');
      return false;
    }

    try {
      const result = await window.Swal.fire({
        icon: 'warning',
        title,
        text: String(message ?? ''),
        showCancelButton: true,
        confirmButtonText: 'Confirmer',
        cancelButtonText: 'Annuler',
        reverseButtons: true,
        focusCancel: true,
        showClass: { popup: 'client-swal-show' },
        hideClass: { popup: 'client-swal-hide' },
        customClass: {
          popup: 'client-swal-popup',
          title: 'client-swal-title',
          htmlContainer: 'client-swal-message',
          confirmButton: 'client-swal-confirm',
          cancelButton: 'client-swal-cancel',
        },
      });
      return result.isConfirmed;
    } catch (error) {
      console.error('Impossible d’ouvrir la confirmation SweetAlert2.', error);
      window.clientAlert('error', 'La confirmation a échoué. Aucune action n’a été effectuée.');
      return false;
    }
  };

  document.querySelectorAll(
    '[data-client-swal-message], [data-client-alert], .notice[role="alert"], .notice[role="status"], .flash[role="alert"], .profile-error-note, .passport-error p'
  ).forEach((node) => {
    const heading = node.matches('h1, h2, h3') ? node : node.querySelector('h1, h2, h3');
    const declaredType = node.dataset.clientAlertType
      || node.dataset.clientSwalType
      || (node.classList.contains('success') || node.classList.contains('flash-success')
        ? 'success'
        : node.classList.contains('warning') || node.classList.contains('flash-warning')
          ? 'warning'
          : node.classList.contains('info') || node.classList.contains('flash-info')
            ? 'info'
            : 'error');
    const type = alertTypes.includes(declaredType) ? declaredType : 'error';
    const title = heading?.textContent.trim() || titles[type];
    const message = node === heading
      ? ''
      : heading
        ? node.textContent.replace(heading.textContent, '').trim()
        : node.textContent.trim();
    window.clientAlert(type, message, { title });
    node.hidden = true;
  });
})();
