// Desirable Futures with Robots — light interactions only.

(() => {
  // Sticky-header border on scroll.
  const header = document.querySelector(".site-header");
  if (header) {
    const onScroll = () => {
      header.classList.toggle("is-scrolled", window.scrollY > 16);
    };
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
  }

  // Reveal-on-scroll for top-level sections and their key children.
  const revealTargets = document.querySelectorAll(
    ".section, .section__title, .prose, .featured-question, .whatif, .step, .approach-counter, .series-stat, .materials-list > li, .horizon-event, .horizon-purpose, .coordinators li, .join__lede, .join__actions, .hero__title, .hero__lede, .hero__meta"
  );
  revealTargets.forEach((el) => el.classList.add("reveal"));

  if ("IntersectionObserver" in window) {
    const io = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            io.unobserve(entry.target);
          }
        }
      },
      { threshold: 0.08, rootMargin: "0px 0px -8% 0px" }
    );
    revealTargets.forEach((el) => io.observe(el));
  } else {
    revealTargets.forEach((el) => el.classList.add("is-visible"));
  }
})();
