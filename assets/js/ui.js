(function () {
    'use strict';

    var body = document.body;
    var nav = document.querySelector('.nav-menu');
    var quickSearch = document.getElementById('quick-search');

    function openMenu() {
        if (!nav) { return; }
        nav.classList.add('show-nav');
        body.classList.add('inactive-body');
    }

    function closeMenu() {
        if (!nav) { return; }
        nav.classList.remove('show-nav');
        body.classList.remove('inactive-body');
    }

    document.querySelectorAll('.mob-menu').forEach(function (el) {
        el.addEventListener('click', openMenu);
    });

    document.querySelectorAll('.nav-menu .close-btn').forEach(function (el) {
        el.addEventListener('click', closeMenu);
    });

    // Dropdowns open on click only (desktop and mobile alike).
    document.querySelectorAll('.nav-menu .has-children').forEach(function (trigger) {
        var item = trigger.parentElement;
        var menu = item.querySelector('.dropdown-menu');
        if (!menu) { return; }

        function closeAll() {
            document.querySelectorAll('.dropdown-menu.show-sub-nav').forEach(function (other) {
                other.classList.remove('show-sub-nav');
            });
        }

        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            var isOpen = menu.classList.contains('show-sub-nav');
            closeAll();
            if (!isOpen) { menu.classList.add('show-sub-nav'); }
        });

        item.addEventListener('click', function (event) {
            if (event.target.closest('.has-children')) { return; }
            if (event.target.closest('.dropdown-menu')) { return; }
            closeAll();
        });
    });

    // Clicking anywhere outside the open dropdown closes it.
    document.addEventListener('click', function (event) {
        if (event.target.closest('.nav-menu .dropdown-menu') || event.target.closest('.nav-menu .has-children')) {
            return;
        }
        document.querySelectorAll('.dropdown-menu.show-sub-nav').forEach(function (open) {
            open.classList.remove('show-sub-nav');
        });
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.dropdown-menu.show-sub-nav').forEach(function (open) {
                open.classList.remove('show-sub-nav');
            });
        }
    });

    function openSearch() {
        if (!quickSearch) { return; }
        quickSearch.classList.add('is-open');
        var input = document.getElementById('textbox');
        if (input) { setTimeout(function () { input.focus(); }, 60); }
    }

    function closeSearch() {
        if (!quickSearch) { return; }
        quickSearch.classList.remove('is-open');
    }

    var searchBtn = document.getElementById('searchBtn');
    if (searchBtn) {
        searchBtn.addEventListener('click', function (event) {
            event.preventDefault();
            openSearch();
        });
    }

    if (quickSearch) {
        var overlay = quickSearch.querySelector('.quick-search-overlay');
        if (overlay) { overlay.addEventListener('click', closeSearch); }
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && quickSearch.classList.contains('is-open')) {
                closeSearch();
            }
        });
    }

    // Category facet collapse.
    document.querySelectorAll('.collapse-icons').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            var list = trigger.parentElement.querySelector('.category-sub-menu');
            if (!list) { return; }
            var open = list.style.display !== 'none';
            list.style.display = open ? 'none' : 'block';
            trigger.classList.toggle('collapse-opened', !open);
        });
    });

    // Mobile filter drawer.
    var openFilter = document.querySelector('.open-filter-btn');
    var categoryMenu = document.querySelector('.category-menu');
    document.querySelectorAll('.close-filter-btn').forEach(function (el) {
        el.addEventListener('click', function () {
            if (categoryMenu) { categoryMenu.classList.remove('show-category-menu'); }
        });
    });
    if (openFilter && categoryMenu) {
        openFilter.addEventListener('click', function (event) {
            event.preventDefault();
            categoryMenu.classList.add('show-category-menu');
        });
    }

    // Auto-submit filter form when a checkbox/radio changes.
    document.querySelectorAll('.js-auto-filter').forEach(function (form) {
        form.addEventListener('change', function (event) {
            if (event.target.matches('input[type="checkbox"], input[type="radio"]')) {
                form.submit();
            }
        });
    });

    // Keep the cart counter in sync when the page is restored from bfcache.
    window.addEventListener('pageshow', function () {
        body.classList.remove('inactive-body');
    });
})();
