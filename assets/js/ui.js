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

    // ------------------------------------------------------------- cart modal
    // Lets the modal's controls work without a full page reload: the server
    // re-prices the basket and sends back fresh markup. Without JavaScript the
    // same forms post normally and the page reloads with the modal open.
    document.documentElement.classList.add('js');

    function syncModalLock() {
        var modal = document.getElementById('cart-modal');
        body.classList.toggle('cart-modal-open', !!modal && modal.classList.contains('is-open'));
        if (modal) {
            modal.setAttribute('aria-hidden', modal.classList.contains('is-open') ? 'false' : 'true');
        }
    }

    function postCartForm(form, submitter) {
        // FormData(form) leaves out the submit button that was clicked, so the
        // delivery toggle would post without name="service" and the server
        // would silently keep the previous service. Pass the submitter through
        // so its name/value pair is part of the payload.
        var body;
        try {
            body = submitter ? new FormData(form, submitter) : new FormData(form);
        } catch (error) {
            body = new FormData(form);
            if (submitter && submitter.name) {
                body.append(submitter.name, submitter.value);
            }
        }

        return fetch(form.action, {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function (response) {
            return response.ok ? response.json() : null;
        }).catch(function () { return null; });
    }

    function applyCartUpdate(data) {
        if (!data) { return; }

        if (data.html) {
            var current = document.getElementById('cart-modal');
            var holder = document.createElement('div');
            holder.innerHTML = data.html.trim();
            var next = holder.firstElementChild;
            if (current && next) {
                current.replaceWith(next);
                syncModalLock();
            }
        }

        if (typeof data.count === 'number') {
            document.querySelectorAll('#shopping-cart-headr').forEach(function (link) {
                link.setAttribute('data-count', String(data.count));
                var badge = link.querySelector('.items');
                if (badge) { badge.textContent = String(data.count); }
            });
        }
    }

    // If the AJAX round-trip fails (offline, a proxy that rejects XHR, an older
    // script still cached), post the form the classic way so the click is never
    // silently ignored. The clicked button's name/value travels as a hidden
    // field because form.submit() does not send a submitter.
    function submitCartFallback(form, submitter) {
        if (submitter && submitter.name) {
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = submitter.name;
            hidden.value = submitter.value;
            form.appendChild(hidden);
        }
        form.submit();
    }

    function submitCart(form, submitter) {
        postCartForm(form, submitter).then(function (data) {
            if (data) {
                applyCartUpdate(data);
            } else {
                submitCartFallback(form, submitter);
            }
        });
    }

    function closeCartModal() {
        var modal = document.getElementById('cart-modal');
        if (!modal) { return; }
        var close = modal.querySelector('[data-cart-close][href]');
        modal.classList.remove('is-open');
        syncModalLock();
        if (!close) { return; }
        fetch(close.getAttribute('href'), {
            credentials: 'same-origin',
            keepalive: true,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).catch(function () {});
    }

    syncModalLock();

    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-cart-close]')) {
            event.preventDefault();
            closeCartModal();
            return;
        }

        var step = event.target.closest('[data-cart-delta]');
        if (!step) { return; }
        event.preventDefault();

        var form = step.closest('.js-cart-qty');
        var input = form ? form.querySelector('.cart-modal__qty-input') : null;
        if (!form || !input) { return; }

        var next = (parseInt(input.value, 10) || 0) + parseInt(step.getAttribute('data-cart-delta'), 10);
        input.value = String(Math.max(0, next));
        submitCart(form);
    });

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || !form.classList) { return; }
        if (!form.classList.contains('js-cart-service') && !form.classList.contains('js-cart-qty')) { return; }
        event.preventDefault();
        submitCart(form, event.submitter || null);
    });

    document.addEventListener('keydown', function (event) {
        var modal = document.getElementById('cart-modal');
        if (event.key !== 'Escape' || !modal || !modal.classList.contains('is-open')) { return; }
        closeCartModal();
    });
})();

// ---------------------------------------------------------- first-visit modals
// The 18+ age gate and the festive promo, shown once per browser. Both
// decisions live in localStorage; when storage is unavailable (private mode) the
// gate simply appears on each visit rather than failing open.
(function () {
    'use strict';

    var root = document.getElementById('visit-modals');
    if (!root) { return; }

    var AGE_KEY = 'roidsseek.age-gate.v1';
    var PROMO_KEY = 'roidsseek.promo.v1';
    var REVIEWS_KEY = 'roidsseek.reviews.v1';
    var panels = {
        gate: document.getElementById('age-gate'),
        promo: document.getElementById('promo-modal'),
        denied: document.getElementById('age-denied'),
        reviews: document.getElementById('reviews-modal')
    };
    var current = null;
    var lastFocus = null;

    function read(key) {
        try { return window.localStorage.getItem(key); } catch (error) { return null; }
    }

    function write(key, value) {
        try { window.localStorage.setItem(key, value); } catch (error) { /* storage unavailable */ }
    }

    function show(panel) {
        if (!panel) { return; }
        if (current === null) { lastFocus = document.activeElement; }
        current = panel;
        Object.keys(panels).forEach(function (name) {
            if (panels[name]) { panels[name].hidden = panels[name] !== panel; }
        });
        root.hidden = false;
        document.body.classList.add('visit-modals-open');
        var target = panel.querySelector('[data-age-accept], .visit-btn--primary, .visit-btn--copy, .visit-btn');
        if (target) { target.focus({ preventScroll: true }); }
    }

    function close() {
        current = null;
        root.hidden = true;
        document.body.classList.remove('visit-modals-open');
        if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus({ preventScroll: true });
        }
    }

    function closePromo() {
        write(PROMO_KEY, 'seen');
        close();
    }

    function copyCode(button) {
        var code = root.querySelector('.visit-modal__code code');
        if (!code) { return; }
        var text = code.textContent.trim();
        var done = function () {
            var original = button.getAttribute('data-copied');
            button.textContent = original;
            button.classList.add('is-copied');
            window.setTimeout(function () {
                button.textContent = 'Copy';
                button.classList.remove('is-copied');
            }, 1800);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done, function () {});
            return;
        }
        var helper = document.createElement('textarea');
        helper.value = text;
        helper.setAttribute('readonly', 'readonly');
        helper.style.position = 'fixed';
        helper.style.opacity = '0';
        document.body.appendChild(helper);
        helper.select();
        try { document.execCommand('copy'); done(); } catch (error) { /* no clipboard */ }
        document.body.removeChild(helper);
    }

    root.addEventListener('click', function (event) {
        if (event.target.closest('[data-age-accept]')) {
            write(AGE_KEY, 'confirmed');
            show(panels.promo);
            return;
        }

        if (event.target.closest('[data-age-deny]')) {
            write(AGE_KEY, 'denied');
            show(panels.denied);
            return;
        }

        var copy = event.target.closest('[data-promo-copy]');
        if (copy) {
            copyCode(copy);
            return;
        }

        var promoClose = event.target.closest('[data-promo-close]');
        if (promoClose) {
            if (promoClose.getAttribute('data-show-reviews')) {
                write(PROMO_KEY, 'seen');
                show(panels.reviews);
            } else {
                closePromo();
            }
            return;
        }

        // Clicking the backdrop dismisses the offer, never the age gate.
        if (event.target.closest('[data-visit-backdrop]') && current === panels.promo) {
            closePromo();
            return;
        }

        if (event.target.closest('[data-reviews-close]')) {
            write(REVIEWS_KEY, 'seen');
            close();
            handleReviewsRedirect();
            return;
        }

        // Handle backdrop click on reviews modal - also triggers pending redirect
        if (event.target.closest('[data-visit-backdrop]') && current === panels.reviews) {
            write(REVIEWS_KEY, 'seen');
            close();
            handleReviewsRedirect();
            return;
        }

        // Following the offer's call to action: show the reviews modal first,
        // then continue to the requested page only after the visitor closes it.
        var ctaLink = event.target.closest('#promo-modal a.visit-btn[href]');
        if (ctaLink) {
            event.preventDefault();
            event.stopPropagation();
            write(PROMO_KEY, 'seen');
            write(AGE_KEY, 'confirmed');
            window._pendingPromoRedirect = ctaLink.getAttribute('href');
            show(panels.reviews);
            return;
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') { return; }
        if (current === panels.promo) { closePromo(); return; }
        if (current === panels.reviews) {
            write(REVIEWS_KEY, 'seen');
            close();
            handleReviewsRedirect();
        }
    });

    // Handle redirect after reviews modal closes (via close button, backdrop, Escape, or See More Reviews link)
    function handleReviewsRedirect() {
        if (window._pendingPromoRedirect) {
            var redirectUrl = window._pendingPromoRedirect;
            window._pendingPromoRedirect = null;
            write(REVIEWS_KEY, 'seen');
            // Small delay to ensure modal is fully closed before redirect
            window.setTimeout(function () { window.location.href = redirectUrl; }, 400);
        }
    }

    // If user clicks on the reviews modal's "See More Reviews" link,
    // navigate directly without closing modal first
    document.addEventListener('click', function (event) {
        if (event.target.closest('#reviews-modal a[href]')) {
            var link = event.target.closest('a');
            if (link && link.hasAttribute('href')) {
                event.preventDefault();
                write(REVIEWS_KEY, 'seen');
                window._pendingPromoRedirect = null;
                window.location.href = link.getAttribute('href');
            }
        }
    }, true);

    var ageState = read(AGE_KEY);
    var promoState = read(PROMO_KEY);

    if (ageState === 'denied') {
        show(panels.denied);
    } else if (ageState === 'confirmed') {
        // The reviews modal is only shown from the promo's calls to action, so
        // a returning visitor is never interrupted by a modal they closed.
        if (promoState !== 'seen') {
            show(panels.promo);
        }
    } else {
        show(panels.gate);
    }
})();
