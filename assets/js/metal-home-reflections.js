/* Two opposite gold reflections; two revolutions, then rest. */
(() => {
  const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const animations = [];
  function syncMotion() {
    animations.splice(0).forEach(animation => animation.cancel());
    if (motion.matches) return;
    document.querySelectorAll('.metal-reflection-beam').forEach((beam, index) => {
      if (typeof beam.animate !== 'function') return;
      const start = index % 2 ? 180 : 0;
      const direction = index % 2 ? -1 : 1;
      animations.push(beam.animate([
        { transform: `rotate(${start}deg)` },
        { transform: `rotate(${start + direction * 360}deg)` }
      ], { duration: 12000, iterations: 2, easing: 'linear' }));
    });
  }
  syncMotion();
  motion.addEventListener?.('change', syncMotion);
})();
