/*
 * Carrousels horizontaux (page « Nos partenaires »).
 * Le défilement est géré en CSS (scroll-snap) ; ce script ajoute seulement
 * les points de navigation et les tient à jour.
 */
document.querySelectorAll('[data-carrousel]').forEach((carrousel) => {
    const piste = carrousel.querySelector('[data-carrousel-piste]');
    const zonePoints = carrousel.querySelector('[data-carrousel-points]');
    const vignettes = Array.from(piste.children);
    let points = [];

    const pasDefilement = () => (vignettes[1] ? vignettes[1].offsetLeft - vignettes[0].offsetLeft : piste.clientWidth);

    const indexCourant = () => {
        const fin = piste.scrollWidth - piste.clientWidth;
        if (piste.scrollLeft >= fin - 2) {
            return points.length - 1;
        }
        return Math.round(piste.scrollLeft / pasDefilement());
    };

    const majPoints = () => {
        const courant = indexCourant();
        points.forEach((point, i) => point.setAttribute('aria-current', String(i === courant)));
    };

    const construirePoints = () => {
        zonePoints.replaceChildren();
        points = [];

        // Tout tient à l'écran : pas besoin de points.
        if (piste.scrollWidth <= piste.clientWidth + 2) {
            return;
        }

        // Un point par position de défilement possible.
        const fin = piste.scrollWidth - piste.clientWidth;
        const nombre = Math.min(vignettes.length, Math.ceil(fin / pasDefilement()) + 1);

        for (let i = 0; i < nombre; i++) {
            const point = document.createElement('button');
            point.type = 'button';
            point.className = 'bz-carrousel__point';
            point.setAttribute('aria-label', `Afficher la vignette ${i + 1}`);
            point.addEventListener('click', () => {
                piste.scrollTo({ left: i * pasDefilement(), behavior: 'smooth' });
            });
            points.push(point);
        }
        zonePoints.append(...points);
        majPoints();
    };

    piste.addEventListener('scroll', majPoints, { passive: true });
    window.addEventListener('resize', construirePoints);
    construirePoints();
});
