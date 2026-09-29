const toggle = document.querySelector('.shared-site-header .menu-toggle');
const mobileMenu = document.querySelector('.shared-site-header .mobile-menu');
if (window.rbSeoLanguagePaths) {
  document.querySelectorAll('.shared-site-header .languages a[lang], .shared-site-header .mobile-menu-languages a[lang]').forEach((link) => {
    const destination = window.rbSeoLanguagePaths[link.lang];
    if (destination) link.href = destination;
  });
}
if (toggle && mobileMenu) {
  toggle.addEventListener('click', () => {
    const open = toggle.getAttribute('aria-expanded') !== 'true';
    toggle.setAttribute('aria-expanded', String(open));
    mobileMenu.hidden = !open;
  });
}
document.querySelectorAll('.shared-site-header .mobile-products-trigger').forEach((button) => {
  button.addEventListener('click', () => {
    const open = button.getAttribute('aria-expanded') !== 'true';
    button.setAttribute('aria-expanded', String(open));
    const menu = document.getElementById(button.getAttribute('aria-controls') || '');
    if (menu) menu.hidden = !open;
  });
});
