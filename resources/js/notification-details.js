document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.getElementById('notification-detail-modal');
    if (!dialog) return;
    const content = document.getElementById('notification-detail-content');
    let trigger;
    let activeRequest;
    const headers = {
        Accept: 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
    };
    const notifyRead = (detail) => window.dispatchEvent(new CustomEvent('notifications-read', { detail }));
    const state = (message) => {
        content.replaceChildren();
        const title = document.createElement('h2');
        title.id = 'notification-detail-title';
        title.textContent = 'Notification Detail';
        title.className = 'text-lg font-semibold';
        const text = document.createElement('p');
        text.className = 'mt-3 text-sm text-slate-300';
        text.textContent = message;
        content.append(title, text);
    };

    window.openRoomSenseNotification = async (id, opener, url) => {
        activeRequest?.abort();
        const request = new AbortController();
        activeRequest = request;
        trigger = opener?.closest('nav') ? opener.closest('nav').querySelector('[aria-label="Notification Center"]') : opener;
        state('Memuat detail notifikasi…');
        if (!dialog.open) dialog.showModal();
        try {
            const response = await fetch(url || `/notifications/${encodeURIComponent(id)}/open`, {
                method: 'POST', headers, signal: request.signal,
            });
            if (!response.ok) throw new Error('Request failed');
            const result = await response.json();
            if (activeRequest !== request) return;
            // This HTML comes from the owned-notification Blade partial; all payload text is escaped there.
            if (dialog.open) content.innerHTML = result.html;
            notifyRead({ id: String(id), count: result.count });
        } catch (error) {
            if (error.name !== 'AbortError' && activeRequest === request && dialog.open) {
                state('Detail belum dapat dimuat. Tutup dialog lalu coba lagi.');
            }
        }
    };
    dialog.querySelector('[data-close-notification]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => {
        if (trigger?.isConnected) trigger.focus();
        else document.querySelector('[aria-label="Notification Center"]')?.focus();
    });
    // Native dialog supplies Escape handling, modal semantics, and keyboard focus containment.
    document.addEventListener('click', (event) => {
        const link = event.target.closest('[data-notification-detail]');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        window.openRoomSenseNotification(link.dataset.notificationDetail, link, link.dataset.openUrl);
    });
    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('[data-mark-read], [data-mark-all-read]');
        if (!form) return;
        event.preventDefault();
        const button = form.querySelector('button');
        button.disabled = true;
        try {
            const response = await fetch(form.action, { method: 'PATCH', headers });
            if (!response.ok) throw new Error('Request failed');
            const result = await response.json();
            notifyRead({ id: form.closest('[data-notification-row]')?.dataset.notificationRow, all: form.hasAttribute('data-mark-all-read'), count: result.count });
        } catch {
            const error = document.createElement('p');
            error.textContent = 'Status baca belum berhasil disimpan. Silakan coba lagi.';
            error.setAttribute('role', 'alert');
            error.className = 'text-xs text-rose-400';
            form.append(error);
        } finally {
            button.disabled = false;
        }
    });
    window.addEventListener('notifications-read', ({ detail }) => {
        document.querySelectorAll('[data-unread-count]').forEach((badge) => {
            badge.textContent = detail.count;
            badge.hidden = detail.count === 0;
        });
        document.querySelectorAll('[data-notification-row]').forEach((row) => {
            if (!detail.all && row.dataset.notificationRow !== detail.id) return;
            row.classList.remove('bg-blue-500/5');
            row.querySelector('[data-mark-read]')?.remove();
            if (new URLSearchParams(location.search).get('filter') === 'unread') row.remove();
        });
        if (detail.count === 0) document.querySelector('[data-mark-all-read]')?.remove();
    });
});
