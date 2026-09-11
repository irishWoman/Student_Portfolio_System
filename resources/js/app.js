/**
 * Front-end entry point.
 *
 * Livewire ships its own runtime, so this file only carries the few behaviours
 * that are nicer in plain JS: the save toast and copy-to-clipboard on the OJT
 * supervisor link.
 */

// --- Save toast --------------------------------------------------------------
document.addEventListener('livewire:init', () => {
    Livewire.on('section-saved', () => showToast('Saved'));
    Livewire.on('section-submitted', () => showToast('Section sent for review'));
    Livewire.on('evaluation-saved', () => showToast('Assessment recorded'));
    Livewire.on('levels-validated', () => showToast('Levels validated'));
});

function showToast(message) {
    const existing = document.querySelector('#toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.id = 'toast';
    toast.className =
        'fixed bottom-6 right-6 z-50 bg-navy-800 text-white text-sm px-4 py-2 shadow-lg';
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => toast.remove(), 2200);
}

// --- Copy buttons ------------------------------------------------------------
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-copy]');
    if (!button) return;

    navigator.clipboard.writeText(button.dataset.copy).then(() => {
        const original = button.textContent;
        button.textContent = 'Copied';
        setTimeout(() => (button.textContent = original), 1500);
    });
});
