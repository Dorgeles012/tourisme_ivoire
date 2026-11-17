(() => {
  'use strict';

  const isLoginPage = document.body.classList.contains('login-page');
  const copy = {
    confirmTitle: 'Confirmer cette action ?',
    confirmMessage: 'Voulez-vous poursuivre cette action ?',
    confirmButton: 'Confirmer',
    cancelButton: 'Annuler',
    loadingTitle: 'Traitement en cours…',
    validationTitle: 'Vérification',
  };

  document.addEventListener('DOMContentLoaded', () => {
    const notices = [...document.querySelectorAll('.notice')];
    if (notices.length && window.Swal) {
      const isError = notices.some((notice) => notice.classList.contains('error'));
      const type = isError ? 'error' :
        notices.some((notice) => notice.classList.contains('warning')) ? 'warning' :
        notices.some((notice) => notice.classList.contains('info')) ? 'info' : 'success';
      const message = notices.map((notice) => notice.textContent.trim()).filter(Boolean).join('\n');
      notices.forEach((notice) => notice.remove());
      Swal.fire({
        icon: type,
        title: type === 'success' ? 'Succès' : type === 'error' ? 'Erreur' : type === 'warning' ? 'Attention' : 'Information',
        text: message,
        confirmButtonColor: '#e67e22',
      });
    }

    if (!window.Swal) return;

    const invalidForms = new WeakSet();
    document.addEventListener('invalid', (event) => {
      const field = event.target;
      if (!(field instanceof HTMLElement)) return;
      event.preventDefault();
      const form = field.closest('form');
      if (!form || invalidForms.has(form)) return;
      invalidForms.add(form);
      Swal.fire({
        icon: 'error',
        title: copy.validationTitle,
        text: field.validationMessage || 'Vérifiez les champs du formulaire.',
        confirmButtonColor: '#e67e22',
      }).then(() => field.focus());
      setTimeout(() => invalidForms.delete(form), 0);
    }, true);

    document.addEventListener('submit', async (event) => {
      const form = event.target;
      if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;
      if (form.method.toLowerCase() !== 'post' || form.classList.contains('sidebar-logout')) return;
      const needsConfirmation = !isLoginPage && !form.hasAttribute('data-confirm');

      if (needsConfirmation && form.dataset.confirmed !== 'true') {
        event.preventDefault();
        const result = await Swal.fire({
          icon: 'warning',
          title: form.dataset.confirmTitle || copy.confirmTitle,
          text: form.dataset.confirmMessage || copy.confirmMessage,
          showCancelButton: true,
          confirmButtonText: copy.confirmButton,
          cancelButtonText: copy.cancelButton,
          confirmButtonColor: '#e67e22',
          cancelButtonColor: '#8892a6',
        });
        if (!result.isConfirmed) return;
        form.dataset.confirmed = 'true';
        if (event.submitter instanceof HTMLElement) {
          form.requestSubmit(event.submitter);
        } else {
          form.requestSubmit();
        }
        return;
      }

      if (needsConfirmation) delete form.dataset.confirmed;
      Swal.fire({
        title: copy.loadingTitle,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => Swal.showLoading(),
      });
    });
  });
})();
