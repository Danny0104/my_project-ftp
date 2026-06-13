/**
 * Platform responsive shell — drawer sidebars, tablet collapse, modal scroll lock.
 */
(function () {
    'use strict';

    var DRAWER_MAX = 1023.98;
    var TABLET_MIN = 1024;
    var TABLET_MAX = 1199.98;

    function isDrawerViewport() {
        return window.innerWidth <= DRAWER_MAX;
    }

    function isTabletViewport() {
        return window.innerWidth >= TABLET_MIN && window.innerWidth <= TABLET_MAX;
    }

    function lockScroll() {
        if (window.ftLayoutShell) {
            window.ftLayoutShell.lockBodyScroll();
        } else {
            document.body.style.overflow = 'hidden';
        }
    }

    function unlockScroll() {
        if (window.ftLayoutShell) {
            window.ftLayoutShell.unlockBodyScroll();
        } else {
            document.body.style.overflow = '';
        }
    }

    function initStudentTabletCollapse() {
        var collapseBtn = document.getElementById('ftpSidebarCollapse');
        if (!collapseBtn) {
            return;
        }

        function syncTabletExpanded() {
            if (isTabletViewport()) {
                var expanded = !document.body.classList.contains('ftp-sidebar-collapsed');
                document.body.classList.toggle('ftp-sidebar-tablet-expanded', expanded);
            } else {
                document.body.classList.remove('ftp-sidebar-tablet-expanded');
            }
        }

        function applyTabletDefault() {
            if (!isTabletViewport() || isDrawerViewport()) {
                return;
            }
            try {
                if (sessionStorage.getItem('ftp_tablet_sidebar') === 'expanded') {
                    document.body.classList.remove('ftp-sidebar-collapsed');
                } else if (localStorage.getItem('ftp_sidebar_collapsed') !== '0') {
                    document.body.classList.add('ftp-sidebar-collapsed');
                }
            } catch (e) { /* ignore */ }
            syncTabletExpanded();
        }

        collapseBtn.addEventListener('click', function () {
            window.setTimeout(function () {
                if (isTabletViewport()) {
                    try {
                        var expanded = !document.body.classList.contains('ftp-sidebar-collapsed');
                        sessionStorage.setItem('ftp_tablet_sidebar', expanded ? 'expanded' : 'collapsed');
                    } catch (e) { /* ignore */ }
                }
                syncTabletExpanded();
            }, 0);
        });

        applyTabletDefault();
        window.addEventListener('resize', function () {
            applyTabletDefault();
            syncTabletExpanded();
        });
    }

    function initOrgDrawerClose() {
        var sidebar = document.getElementById('orgSidebar');
        var closeBtn = document.getElementById('orgSidebarClose');
        if (!sidebar || !closeBtn) {
            return;
        }

        function close() {
            sidebar.classList.remove('is-open');
            var overlay = document.getElementById('orgSidebarOverlay');
            if (overlay) {
                overlay.classList.remove('is-open');
            }
            unlockScroll();
        }

        closeBtn.addEventListener('click', close);

        sidebar.querySelectorAll('.org-nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (isDrawerViewport()) {
                    close();
                }
            });
        });
    }

    function initModalScrollLock() {
        document.addEventListener('show.bs.modal', function () {
            document.body.classList.add('ft-modal-open');
        });
        document.addEventListener('hidden.bs.modal', function () {
            if (!document.querySelector('.modal.show')) {
                document.body.classList.remove('ft-modal-open');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (document.body.classList.contains('ftp-dashboard-layout')) {
            initStudentTabletCollapse();
        }
        if (document.body.classList.contains('org-dashboard-layout')) {
            initOrgDrawerClose();
        }
        initModalScrollLock();
    });
})();
