
Portal = {
    init: function () {
        // Initialization logic if needed
    },

    notify: function (message, type) {
        const messagesContainer = document.getElementById('messages');
        if (!messagesContainer) return;

        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        
        // Use textContent to prevent XSS vulnerabilities
        const paragraph = document.createElement('p');
        paragraph.textContent = message;
        messageDiv.appendChild(paragraph);
        
        messagesContainer.appendChild(messageDiv);

        requestAnimationFrame(() => messageDiv.classList.add('show'));

        setTimeout(() => {
            messageDiv.classList.remove('show');
            setTimeout(() => messageDiv.remove(), 250);
        }, 3000);
    },

    readonlyForm: function(form) {
        if (!form) return;

        Portal.notify('This service is in read-only mode. You cannot edit it.', 'warning');
        const controls = form.querySelectorAll('input, select, textarea, .control');
        
        controls.forEach(control => {
            control.disabled = true;
            control.classList.add('disabled');
            control.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                Portal.notify('This service is in read-only mode. You cannot edit it.', 'warning');
            });
        });
    },

    Service: {
        doCancel: function(id) {
            confirmAction('Are you sure you want to cancel this service?')
                .then(confirmed => {
                    if (confirmed) {
                        window.location.href = `/service/cancel?id=${id}`;
                    }
                });
        },

        doUncancel: function(id) {
            confirmAction('Are you sure you want to restore this service?')
                .then(confirmed => {
                    if (confirmed) {
                        window.location.href = `/service/uncancel?id=${id}`;
                    }
                });
        },

        doCopy: function(id) {
            confirmAction('Are you sure you want to copy this service?')
                .then(confirmed => {
                    if (confirmed) {
                        window.location.href = `/service/copy?id=${id}`;
                    }
                });
        },

        deleteAttachment: function(service_id, attachmentId) {
            confirmAction('Are you sure you want to delete this attachment?')
                .then(confirmed => {
                    if (confirmed) {
                        window.location.href = `/service/attachment/delete?service_id=${service_id}&attachment_id=${attachmentId}`;
                    }
                });
        }
    },

    Block: {
        deleteAttachment: function(block_id, attachmentId) {
            confirmAction('Are you sure you want to delete this attachment?')
                .then(confirmed => {
                    if (confirmed) {
                        window.location.href = `/block/deleteAttachment?block_id=${block_id}&attachment_id=${attachmentId}`;
                    }
                });
        }
    }
};

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initializeMobileMenu();
    initializeActionsMenu();
    initializeFilterDialog();
});

// Mobile hamburger menu
function initializeMobileMenu() {
    const burger = document.getElementById('navToggle');
    const topNav = document.querySelector('.top-nav');
    
    if (!burger || !topNav) return;

    burger.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = burger.classList.toggle('open');
        topNav.classList.toggle('open');
        burger.setAttribute('aria-expanded', isOpen);
    });

    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
        if (topNav.classList.contains('open') && 
            !topNav.contains(e.target) && 
            !burger.contains(e.target)) {
            burger.classList.remove('open');
            topNav.classList.remove('open');
            burger.setAttribute('aria-expanded', 'false');
        }
    });

    // Close menu when clicking a link (except dropdowns)
    topNav.querySelectorAll('a.top-link').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                burger.classList.remove('open');
                topNav.classList.remove('open');
                burger.setAttribute('aria-expanded', 'false');
            }
        });
    });
    
    // Keyboard accessibility for burger
    burger.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            burger.click();
        }
    });
}

// Actions menu
function initializeActionsMenu() {
    let openMenu = null;

    document.querySelectorAll('.action-btn').forEach(btn => {
        const menu = btn.parentElement.querySelector('.actions-menu');
        if (!menu) return;

        btn.addEventListener('click', e => {
            e.stopPropagation();
            if (openMenu && openMenu !== menu) {
                openMenu.classList.remove('show');
            }
            menu.classList.toggle('show');
            openMenu = menu.classList.contains('show') ? menu : null;
        });
    });

    document.addEventListener('click', () => {
        if (openMenu) {
            openMenu.classList.remove('show');
            openMenu = null;
        }
    });
}

// Filter dialog functionality
function initializeFilterDialog() {
    const filterToggleBtn = document.getElementById('filterToggleBtn');
    const filterDialog = document.getElementById('filterDialog');
    const filterDialogClose = document.getElementById('filterDialogClose');

    if (!filterToggleBtn || !filterDialog) return;

    filterToggleBtn.addEventListener('click', () => {
        filterDialog.classList.add('open');
    });

    if (filterDialogClose) {
        filterDialogClose.addEventListener('click', () => {
            filterDialog.classList.remove('open');
        });
    }

    // Close dialog when clicking outside
    filterDialog.addEventListener('click', (e) => {
        if (e.target === filterDialog) {
            filterDialog.classList.remove('open');
        }
    });

    // Close dialog on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && filterDialog.classList.contains('open')) {
            filterDialog.classList.remove('open');
        }
    });
}

