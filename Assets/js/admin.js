// Admin/js/admin_dashboard.js

document.addEventListener('DOMContentLoaded', function() {
    // Tab Management
    initTabManagement();

    // Modal Handling
    initModals();

    // Stats Cards Interaction
    initStatsCards();

    // Sidebar Toggle for Mobile
    initSidebarToggle();
});

// Tab Management
function initTabManagement() {
    // Get active tab from URL
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');

    // Set active tab based on URL parameter
    if (tabParam) {
        const tabElement = document.querySelector(`[data-bs-target="#${tabParam}"]`);
        if (tabElement) {
            const tab = new bootstrap.Tab(tabElement);
            tab.show();

            // Update URL without page reload
            history.replaceState(null, '', `?tab=${tabParam}`);
        }
    }

    // Add click handlers for tabs to update URL
    const tabLinks = document.querySelectorAll('.nav-tabs .nav-link');
    tabLinks.forEach(link => {
        link.addEventListener('shown.bs.tab', function(event) {
            const tabId = event.target.getAttribute('data-bs-target').replace('#', '');
            history.replaceState(null, '', `?tab=${tabId}`);

            // Store last active tab in localStorage
            localStorage.setItem('lastAdminTab', tabId);
        });
    });

    // Restore last active tab if exists
    const lastTab = localStorage.getItem('lastAdminTab');
    if (lastTab && !tabParam) {
        const tabElement = document.querySelector(`[data-bs-target="#${lastTab}"]`);
        if (tabElement) {
            const tab = new bootstrap.Tab(tabElement);
            tab.show();
        }
    }
}

// Modal Handling
function initModals() {
    // Decline Modal
    const declineModal = document.getElementById('declineModal');
    if (declineModal) {
        declineModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const productId = button.getAttribute('data-id');
            const productName = button.getAttribute('data-name') || 'this product';

            const modalTitle = declineModal.querySelector('.modal-title');
            const productIdInput = declineModal.querySelector('#decline_id');
            const reasonTextarea = declineModal.querySelector('#decline_reason');

            // Update modal content
            modalTitle.textContent = `Decline: ${productName}`;
            productIdInput.value = productId;
            reasonTextarea.value = '';
            reasonTextarea.focus();
        });
    }

    // Confirmation for actions
    document.addEventListener('click', function(e) {
        if (e.target.closest('.confirm-action')) {
            e.preventDefault();
            const actionText = e.target.getAttribute('data-action') || 'perform this action';
            const actionUrl = e.target.getAttribute('href') || e.target.form?.action;

            if (confirm(`Are you sure you want to ${actionText}?`)) {
                if (actionUrl) {
                    window.location.href = actionUrl;
                } else if (e.target.form) {
                    e.target.form.submit();
                }
            }
        }
    });
}

// Stats Cards Interaction
function initStatsCards() {
    const statsCards = document.querySelectorAll('.stats-card');

    statsCards.forEach(card => {
        card.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            if (targetTab) {
                const tabElement = document.querySelector(`[data-bs-target="#${targetTab}"]`);
                if (tabElement) {
                    const tab = new bootstrap.Tab(tabElement);
                    tab.show();
                }
            }
        });
    });
}

// Sidebar Toggle for Mobile
function initSidebarToggle() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar-column');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');

            // Add overlay when sidebar is open
            if (sidebar.classList.contains('show')) {
                createOverlay();
            } else {
                removeOverlay();
            }
        });
    }

    // Close sidebar when clicking on overlay
    document.addEventListener('click', function(event) {
        if (sidebar && sidebar.classList.contains('show')) {
            const isClickInsideSidebar = sidebar.contains(event.target);
            const isClickOnToggle = sidebarToggle && sidebarToggle.contains(event.target);

            if (!isClickInsideSidebar && !isClickOnToggle) {
                sidebar.classList.remove('show');
                removeOverlay();
            }
        }
    });

    // Close sidebar on window resize (if resized to desktop)
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && sidebar) {
            sidebar.classList.remove('show');
            removeOverlay();
        }
    });
}

function createOverlay() {
    let overlay = document.getElementById('sidebarOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'sidebarOverlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            display: block;
            animation: fadeIn 0.3s ease;
        `;
        document.body.appendChild(overlay);

        // Close sidebar when clicking overlay
        overlay.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar-column');
            if (sidebar) {
                sidebar.classList.remove('show');
            }
            removeOverlay();
        });
    }

    // Add CSS for fade animation
    if (!document.getElementById('sidebarOverlayStyles')) {
        const style = document.createElement('style');
        style.id = 'sidebarOverlayStyles';
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }

            @keyframes fadeOut {
                from { opacity: 1; }
                to { opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
}

function removeOverlay() {
    const overlay = document.getElementById('sidebarOverlay');
    if (overlay) {
        overlay.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => {
            if (overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
        }, 300);
    }
}
