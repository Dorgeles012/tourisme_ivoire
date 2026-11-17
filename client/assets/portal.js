(() => {
  const body = document.body;
  const saved = localStorage.getItem('ti-client-theme');
  if (saved === 'dark') body.classList.add('dark');
  document.querySelector('.theme-toggle')?.addEventListener('click', () => {
    body.classList.toggle('dark');
    localStorage.setItem('ti-client-theme', body.classList.contains('dark') ? 'dark' : 'light');
  });
  const menu = document.querySelector('.menu-toggle');
  menu?.addEventListener('click', () => {
    const side = document.querySelector('.sidebar');
    const open = side?.classList.toggle('open') ?? false;
    menu.setAttribute('aria-expanded', String(open));
  });
  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.getAttribute('data-confirm') || 'Confirmer cette action ?')) event.preventDefault();
    });
  });
})();
