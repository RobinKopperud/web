document.addEventListener('DOMContentLoaded', function() {
  var toggle = document.getElementById('menuToggle');
  var nav = document.querySelector('.nav');
  if (toggle && nav) {
    toggle.addEventListener('click', function() {
      nav.classList.toggle('open');
    });
  }
});
