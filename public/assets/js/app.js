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

document.querySelectorAll('[data-modal-open]').forEach((button) => {
    button.addEventListener('click', () => {
        const modal = document.getElementById(button.getAttribute('data-modal-open'));
        if (modal) {
            modal.hidden = false;
            document.body.classList.add('modal-open');
        }
    });
});

document.querySelectorAll('[data-modal-close]').forEach((button) => {
    button.addEventListener('click', () => {
        const modal = button.closest('[data-modal]');
        if (modal) {
            modal.hidden = true;
            document.body.classList.remove('modal-open');
        }
    });
});

document.querySelectorAll('[data-modal]').forEach((modal) => {
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.hidden = true;
            document.body.classList.remove('modal-open');
        }
    });
});

document.querySelectorAll('[data-drop-zone]').forEach((zone) => {
    const input = zone.querySelector('input[type="file"]');
    const label = zone.querySelector('[data-file-name]');

    if (!input) {
        return;
    }

    const setFileName = () => {
        if (label && input.files.length > 0) {
            label.textContent = input.files[0].name;
        }
    };

    input.addEventListener('change', setFileName);

    ['dragenter', 'dragover'].forEach((eventName) => {
        zone.addEventListener(eventName, (event) => {
            event.preventDefault();
            zone.classList.add('is-dragging');
        });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
        zone.addEventListener(eventName, (event) => {
            event.preventDefault();
            zone.classList.remove('is-dragging');
        });
    });

    zone.addEventListener('drop', (event) => {
        if (event.dataTransfer && event.dataTransfer.files.length > 0) {
            input.files = event.dataTransfer.files;
            setFileName();
        }
    });
});
