
Portal = {
    init: function () {
    },
    notify: function (message, type) {
        document.getElementById('messages').innerHTML += '<div class="message ' + type + '"><p>' + message + '</p></div>';
        document.querySelectorAll('#messages .message').forEach(msg => {
            requestAnimationFrame(() => msg.classList.add('show'));


            setTimeout(() => {
                msg.classList.remove('show');

                setTimeout(() => msg.remove(), 250);
            }, 3000);
        });
    },

    readonlyForm: function(form)
    {
        Portal.notify('This service is in read-only mode. You cannot edit it.', 'warning');
        for (const control of form.querySelectorAll('input, select, textarea, .control')) {
            control.disabled = true;
            control.classList.add('disabled');
            control.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                Portal.notify('This service is in read-only mode. You cannot edit it.', 'warning');
            });

        }
    },

    Service: {
        doCancel: function(id) {
            confirmAction('Are you sure you want to cancel this service?')
            .then(confirmed => {
                if (confirmed) {
                    window.location.href = '/service/cancel?id=' + id;
                }
            });
        },
        doUncancel: function(id) {
            confirmAction('Are you sure you want to restore this service?')
            .then(confirmed => {
                if (confirmed) {
                    window.location.href = '/service/uncancel?id=' + id;
                }
            });
        },
        doCopy: function(id) {
            confirmAction('Are you sure you want to copy this service?')
            .then(confirmed => {
                if (confirmed) {
                    window.location.href = '/service/copy?id=' + id;
                }
            });
        },
        deleteAttachment    : function(service_id, attachmentId) {
            confirmAction('Are you sure you want to delete this attachment?')
            .then(confirmed => {
                if (confirmed) {
                    window.location.href = '/service/attachment/delete?service_id=' + service_id + '&attachment_id=' + attachmentId;
                }
            });
        }
    },
    Block: {
        deleteAttachment    : function(block_id, attachmentId) {
            confirmAction('Are you sure you want to delete this attachment?')
            .then(confirmed => {
                if (confirmed) {
                    window.location.href = '/block/deleteAttachment?block_id=' + block_id + '&attachment_id=' + attachmentId;
                }
            });
        }
    }

}

//Actions menu
document.addEventListener('DOMContentLoaded', () => {

    // Mobile hamburger menu
    const burger = document.getElementById('navToggle');
    const topNav = document.querySelector('.top-nav');
    
    if (burger && topNav) {
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
    let openMenu = null;
    document.querySelectorAll('.action-btn').forEach(btn => {
        const menu = btn.parentElement.querySelector('.actions-menu');
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

    // Filter dialog functionality
    const filterToggleBtn = document.getElementById('filterToggleBtn');
    const filterDialog = document.getElementById('filterDialog');
    const filterDialogClose = document.getElementById('filterDialogClose');

    if (filterToggleBtn && filterDialog) {
        filterToggleBtn.addEventListener('click', () => {
            filterDialog.classList.add('open');
        });

        filterDialogClose?.addEventListener('click', () => {
            filterDialog.classList.remove('open');
        });

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



    

    

});

