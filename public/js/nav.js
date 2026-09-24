// public/js/nav.js — loaded once in <head>, survives Turbo page swaps.
(function () {
  function sync() {
    var nav = document.getElementById('nav');
    if (nav) nav.classList.toggle('scrolled', window.scrollY > 10);
  }
  window.addEventListener('scroll', sync, { passive: true });
  document.addEventListener('turbo:load', sync);
  sync();
})();