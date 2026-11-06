import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

document.addEventListener('error', (event) => {
    const image = event.target;

    if (!(image instanceof HTMLImageElement) || !image.classList.contains('product-image-with-fallback')) {
        return;
    }

    if (image.dataset.fallbackSrc && !image.dataset.fallbackAttempted) {
        image.dataset.fallbackAttempted = 'true';
        image.src = image.dataset.fallbackSrc;

        return;
    }

    const placeholder = document.createElement('div');
    placeholder.className = 'product-card-image-placeholder';
    placeholder.setAttribute('role', 'img');
    placeholder.setAttribute('aria-label', image.alt || 'Image indisponible');
    placeholder.textContent = 'Image indisponible';
    image.replaceWith(placeholder);
}, true);

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
