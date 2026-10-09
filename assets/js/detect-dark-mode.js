// Apply the saved color mode as early as possible.
// layouts.lang performs the same bootstrap inline before first paint; this file
// keeps pages that load the legacy detector directly consistent.
(function () {
  try {
    var colorMode = localStorage.getItem('color-mode');
    if (!colorMode || !['light', 'dark', 'auto'].includes(colorMode)) {
      colorMode = 'auto';
      localStorage.setItem('color-mode', colorMode);
    }

    var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    var useDark = colorMode === 'dark' || (colorMode === 'auto' && prefersDark);
    var root = document.documentElement;

    root.classList.remove('light', 'dark', 'auto');
    root.classList.add(colorMode);
    if (useDark) root.classList.add('dark');
    root.classList.add('pk-theme-ready');
  } catch (error) {
    document.documentElement.classList.add('dark', 'pk-theme-ready');
  }
})();
