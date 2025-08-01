document.addEventListener('DOMContentLoaded', function() {
  const tabContent = document.getElementById('tab-content');
  setTimeout(function() {
    tabContent.classList.remove('translate-x-full', 'opacity-0');
    tabContent.classList.add('translate-x-0', 'opacity-100');
  }, 100);
});