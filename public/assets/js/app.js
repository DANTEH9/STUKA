const sidebarButton = document.querySelector('[data-sidebar-toggle]');

if (sidebarButton) {
    sidebarButton.addEventListener('click', () => {
        document.body.classList.toggle('sidebar-open');
    });
}

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.body.classList.remove('sidebar-open');
    }
});

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        const message = form.getAttribute('data-confirm') || 'Continue?';
        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });
});
