import './bootstrap';

// Icon libraries (admin + optional frontend shortcodes)
import '@fortawesome/fontawesome-free/css/all.css';
import { createIcons, icons as lucideIcons } from 'lucide';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Expose a tiny helper so Blade can re-render Lucide placeholders (admin previews, dynamic inserts, etc.)
function renderLucide(root = document) {
    try {
        // Only do work if we have any placeholders
        if (!root || !root.querySelector || !root.querySelector('[data-lucide]')) return;
        createIcons({ icons: lucideIcons });
    } catch (e) {
        // ignore
    }
}

window.ImpartLucide = {
    render: renderLucide,
};

// Initial pass
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => renderLucide(document));
} else {
    renderLucide(document);
}

// Admin pickers (runs only when their containers exist)
import './admin/icon-library';

// Forms builder (admin only; no-op unless builder container exists)
import './admin/forms-builder';
