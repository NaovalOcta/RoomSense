// RoomSense — app.js
// Vanilla JS for minor interactivity (confirm dialogs, auto-dismiss alerts)
import './echo';

document.addEventListener('DOMContentLoaded', () => {

    // ── Auto-dismiss flash alerts after 5 seconds ──
    document.querySelectorAll('[data-auto-dismiss]').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            el.style.opacity    = '0';
            el.style.transform  = 'translateY(-8px)';
            setTimeout(() => el.remove(), 500);
        }, 5000);
    });

    // ── Confirm before destructive actions ──
    document.querySelectorAll('[data-confirm]').forEach(btn => {
        btn.addEventListener('click', e => {
            const message = btn.dataset.confirm || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });

    // ── Submit hidden form on data-form-submit click ──
    document.querySelectorAll('[data-form-id]').forEach(btn => {
        btn.addEventListener('click', () => {
            const form = document.getElementById(btn.dataset.formId);
            if (form) form.submit();
        });
    });

});
