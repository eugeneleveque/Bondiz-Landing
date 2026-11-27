/*
 * Fait apparaître les éléments en fondu, de bas en haut, quand ils entrent à
 * l'écran. Les éléments visibles en même temps arrivent l'un après l'autre.
 */
(() => {
    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const selecteurs = [
        // Héros
        '.bz-heros__texte > *', '.bz-heros__visuel',
        // Blocs communs
        '.bz-carte', '.bz-bande .bz-titre', '.bz-bande .bz-texte', '.bz-bande .bz-bouton',
        // Accueil et téléchargement
        '.bz-tuile', '.bz-partenaires__telephone', '.bz-partenaires__carte',
        '.bz-telecharger > *',
        // Partenaires
        '.bz-catalogue > .bz-titre', '.bz-catalogue__intro', '.bz-activite',
        '.bz-lieux', '.bz-carrousel',
        '.bz-avantages__texte', '.bz-avantage', '.bz-profils', '.bz-etape', '.bz-demande',
        // À propos
        '.bz-etapes', '.bz-equipe__photo', '.bz-conclusion > *',
        // Contact, articles, pages légales
        '.bz-coordonnee', '.bz-contact__formulaire',
        '.bz-filtres', '.bz-article', '.bz-lettre__fantome', '.bz-lettre__formulaire',
        '.bz-legal__carte',
        // Page d'un article
        '.bz-page > .container > *',
    ].join(',');

    const trouves = new Set(document.querySelectorAll(selecteurs));

    // Un élément dont un parent est déjà animé ne l'est pas une seconde fois.
    const elements = [...trouves].filter((element) => {
        for (let parent = element.parentElement; parent; parent = parent.parentElement) {
            if (trouves.has(parent)) {
                return false;
            }
        }
        return true;
    });

    const reveler = (element, delai) => {
        observateur.unobserve(element);
        element.style.setProperty('--bz-delai', `${delai}ms`);
        element.classList.replace('bz-a-reveler', 'bz-revele');

        // Une fois arrivé, l'élément retrouve ses styles d'origine.
        const nettoyer = (evenement) => {
            if (evenement.target !== element || evenement.animationName !== 'bz-apparition') {
                return;
            }
            element.classList.remove('bz-revele');
            element.style.removeProperty('--bz-delai');
            element.removeEventListener('animationend', nettoyer);
        };
        element.addEventListener('animationend', nettoyer);
    };

    const observateur = new IntersectionObserver((entrees) => {
        entrees
            .filter((entree) => entree.isIntersecting)
            .forEach((entree, rang) => reveler(entree.target, Math.min(rang, 5) * 90));
    }, { rootMargin: '0px 0px -8% 0px' });

    elements.forEach((element) => {
        element.classList.add('bz-a-reveler');
        observateur.observe(element);
    });

    // Navigation au clavier : un élément qui reçoit le focus s'affiche aussitôt.
    document.addEventListener('focusin', (evenement) => {
        const cache = evenement.target.closest('.bz-a-reveler');
        if (cache) {
            reveler(cache, 0);
        }
    });
})();
