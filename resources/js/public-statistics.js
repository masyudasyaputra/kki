const counters = document.querySelectorAll('[data-stat-count]');
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

if (counters.length && 'IntersectionObserver' in window && !reducedMotion.matches) {
    const formatter = new Intl.NumberFormat('id-ID');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(({ target, isIntersecting }) => {
            if (!isIntersecting) return;
            observer.unobserve(target);

            const total = Number(target.dataset.statCount);
            if (!Number.isSafeInteger(total) || total <= 0) return;

            const duration = 1200;
            let started;
            const animate = (time) => {
                started ??= time;
                const progress = reducedMotion.matches ? 1 : Math.min((time - started) / duration, 1);
                const eased = 1 - (1 - progress) ** 3;
                target.textContent = formatter.format(Math.round(total * eased));
                if (progress < 1) requestAnimationFrame(animate);
            };
            requestAnimationFrame(animate);
        });
    }, { threshold: 0.5 });

    counters.forEach((counter) => observer.observe(counter));
}
