        <button type="button" class="anniversary-widget" data-anniversary-open
                aria-haspopup="dialog" aria-controls="anniversary-modal"
                aria-label="Open the RoidsSeek anniversary offer">
            <span class="anniversary-widget__pulse" aria-hidden="true"></span>
            <span class="anniversary-widget__frame">
                <img src="<?= e(asset('images/roidsseek-anniversary.png')) ?>" alt=""
                     class="anniversary-widget__img" width="500" height="500" loading="lazy" decoding="async">
                <span class="anniversary-widget__badge" aria-hidden="true">Anniversary</span>
            </span>
        </button>

        <div class="anniversary-modal" id="anniversary-modal" hidden>
            <div class="anniversary-modal__backdrop" data-anniversary-close></div>
            <div class="anniversary-modal__dialog" role="dialog" aria-modal="true"
                 aria-labelledby="anniversary-modal-title" aria-describedby="anniversary-modal-copy">
                <button type="button" class="anniversary-modal__close" data-anniversary-close
                        aria-label="Close anniversary offer">
                    <svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true" focusable="false">
                        <path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                    </svg>
                </button>

                <div class="anniversary-modal__media">
                    <img src="<?= e(asset('images/roidsseek-anniversary.png')) ?>"
                         alt="RoidsSeek anniversary celebration" width="500" height="500" decoding="async">
                </div>

                <div class="anniversary-modal__body">
                    <p class="anniversary-modal__eyebrow">Limited-time celebration</p>
                    <h2 class="anniversary-modal__title" id="anniversary-modal-title">Celebrate with RoidsSeek</h2>
                    <p class="anniversary-modal__copy" id="anniversary-modal-copy">
                        Our anniversary offers are live while stocks last. Browse the latest deals and
                        apply the festive promo code at checkout for free shipping.
                    </p>
                    <div class="anniversary-modal__actions">
                        <a class="anniversary-modal__btn anniversary-modal__btn--primary" href="<?= e(url('sale')) ?>">Shop the anniversary sale</a>
                        <button type="button" class="anniversary-modal__btn" data-anniversary-close>Keep browsing</button>
                    </div>
                </div>
            </div>
        </div>

        <footer>
    <div class="footer-container">
        <div class="container">
            <div class="row copyright_container">
                <div class="col-md-4 col-12">
                    <p class="copyright_link">
                        Copyright &copy; <?= date('Y') ?> <?= e(\App\Support\Site::name() !== '' ? \App\Support\Site::name() : '') ?> All Rights Reserved
                    </p>
                </div>
                <div class="col-md-8 col-12">
                    <div class="footer-nav">
                        <a href="<?= e(url('all')) ?>">All products</a>
                        <a href="<?= e(url('articles')) ?>">Guides</a>
                        <a href="<?= e(url('faq')) ?>">FAQ</a>
                        <a href="<?= e(url('contact')) ?>">Contact</a>
                        <?php if (\App\Support\Site::get('hasPhysicalLocation')): ?>
                            <a href="<?= e(url('location')) ?>">Visit us</a>
                        <?php endif; ?>
                        <?php foreach (\App\Support\Nav::pages() as $footerPage): ?>
                            <a href="<?= e(url('page', ['slug' => $footerPage['slug']])) ?>"><?= e($footerPage['title']) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
    body.anniversary-modal-open {
        overflow: hidden;
    }

    .anniversary-widget {
        position: fixed;
        right: clamp(12px, 2.2vw, 24px);
        bottom: clamp(12px, 2.2vw, 24px);
        z-index: 12000;
        width: clamp(78px, 9vw, 108px);
        padding: 0;
        border: 0;
        background: transparent;
        color: inherit;
        font: inherit;
        cursor: pointer;
        -webkit-tap-highlight-color: transparent;
        animation: anniversaryFadeIn 0.8s ease-out both;
    }

    .anniversary-widget:focus-visible {
        outline: 2px solid #c8a54e;
        outline-offset: 4px;
        border-radius: 22px;
    }

    .anniversary-widget__frame {
        position: relative;
        display: block;
        padding: 3px;
        border-radius: 20px;
        background: linear-gradient(145deg, #f3d98a, #c8a54e 45%, #7c1d2b);
        box-shadow: 0 16px 32px -14px rgb(9 14 24 / 62%);
        animation: anniversaryFloat 4.2s ease-in-out 1s infinite;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .anniversary-widget:hover .anniversary-widget__frame,
    .anniversary-widget:focus-visible .anniversary-widget__frame {
        transform: scale(1.06);
        box-shadow: 0 22px 40px -14px rgb(9 14 24 / 70%);
    }

    .anniversary-widget__img {
        display: block;
        width: 100%;
        height: auto;
        border-radius: 17px;
        background: #fff;
    }

    .anniversary-widget__pulse {
        position: absolute;
        inset: -6px;
        border: 2px solid rgb(200 165 78 / 60%);
        border-radius: 26px;
        animation: anniversaryPulse 2.8s ease-out infinite;
        pointer-events: none;
    }

    .anniversary-widget__badge {
        position: absolute;
        left: 50%;
        bottom: -9px;
        transform: translateX(-50%);
        padding: 4px 10px;
        border-radius: 999px;
        background: linear-gradient(135deg, #7c1d2b, #b3303f);
        color: #fff;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: 0.12em;
        line-height: 1;
        text-transform: uppercase;
        white-space: nowrap;
        box-shadow: 0 8px 18px -8px rgb(9 14 24 / 70%);
    }

    .anniversary-modal {
        position: fixed;
        inset: 0;
        z-index: 12990;
        display: grid;
        place-items: center;
        padding: 20px;
    }

    .anniversary-modal[hidden] {
        display: none;
    }

    .anniversary-modal__backdrop {
        position: absolute;
        inset: 0;
        background:
            radial-gradient(120% 90% at 50% 0%, rgb(124 29 43 / 38%), transparent 62%),
            rgb(9 14 24 / 78%);
        -webkit-backdrop-filter: blur(6px);
        backdrop-filter: blur(6px);
        animation: anniversaryFade 0.22s ease-out both;
    }

    .anniversary-modal__dialog {
        position: relative;
        z-index: 1;
        width: min(100%, 560px);
        max-height: calc(100vh - 40px);
        overflow: auto;
        border-radius: 22px;
        background: #fff;
        box-shadow: 0 40px 90px -30px rgb(3 8 20 / 72%);
        animation: anniversaryModalIn 0.32s cubic-bezier(0.2, 0.9, 0.3, 1) both;
    }

    .anniversary-modal__close {
        position: absolute;
        top: 12px;
        right: 12px;
        z-index: 3;
        display: grid;
        place-items: center;
        width: 36px;
        height: 36px;
        padding: 0;
        border: 1px solid rgb(255 255 255 / 45%);
        border-radius: 50%;
        background: rgb(9 14 24 / 52%);
        color: #fff;
        cursor: pointer;
        -webkit-backdrop-filter: blur(4px);
        backdrop-filter: blur(4px);
        transition: background 0.18s ease, transform 0.18s ease;
    }

    .anniversary-modal__close:hover {
        background: rgb(9 14 24 / 80%);
        transform: scale(1.05);
    }

    .anniversary-modal__close:focus-visible {
        outline: 2px solid #fff;
        outline-offset: 2px;
    }

    .anniversary-modal__media {
        display: grid;
        place-items: center;
        padding: 18px;
        background: radial-gradient(120% 100% at 50% 0%, #1d3350, #101a2b 70%);
    }

    .anniversary-modal__media img {
        display: block;
        width: 100%;
        max-width: 420px;
        height: auto;
        max-height: 48vh;
        object-fit: contain;
        border-radius: 14px;
        box-shadow: 0 24px 50px -20px rgb(0 0 0 / 65%);
    }

    .anniversary-modal__body {
        padding: 22px clamp(20px, 4vw, 30px) clamp(22px, 4vw, 28px);
        text-align: center;
    }

    .anniversary-modal__eyebrow {
        margin: 0 0 6px;
        color: #a8862f;
        font-size: 11.5px;
        font-weight: 800;
        letter-spacing: 0.16em;
        text-transform: uppercase;
    }

    .anniversary-modal__title {
        margin: 0 0 10px;
        color: #131a24;
        font-size: clamp(21px, 3.4vw, 26px);
        font-weight: 800;
        line-height: 1.24;
        letter-spacing: -0.01em;
    }

    .anniversary-modal__copy {
        margin: 0 auto 18px;
        max-width: 42ch;
        color: #4a5560;
        font-size: 14.5px;
        line-height: 1.6;
    }

    .anniversary-modal__actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 10px;
    }

    .anniversary-modal__btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 44px;
        padding: 0 18px;
        border: 1px solid #e2e6ea;
        border-radius: 12px;
        background: #f4f6f8;
        color: #3a4653;
        font: inherit;
        font-size: 14px;
        font-weight: 700;
        line-height: 1;
        text-decoration: none;
        cursor: pointer;
        transition: transform 0.16s ease, box-shadow 0.16s ease, background 0.16s ease, border-color 0.16s ease;
    }

    .anniversary-modal__btn:hover {
        background: #eef1f4;
        border-color: #d3dae1;
        color: #26313c;
        text-decoration: none;
    }

    .anniversary-modal__btn:focus-visible {
        outline: 2px solid #2f5d8c;
        outline-offset: 2px;
    }

    .anniversary-modal__btn--primary {
        border-color: #7c1d2b;
        background: linear-gradient(135deg, #7c1d2b, #b3303f);
        color: #fff;
        box-shadow: 0 12px 24px -12px rgb(124 29 43 / 75%);
    }

    .anniversary-modal__btn--primary:hover {
        background: linear-gradient(135deg, #651622, #972937);
        border-color: #651622;
        color: #fff;
        transform: translateY(-1px);
    }

    @keyframes anniversaryFadeIn {
        from {
            opacity: 0;
            transform: translateY(14px) scale(0.96);
        }
        to {
            opacity: 1;
            transform: none;
        }
    }

    @keyframes anniversaryFloat {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-7px);
        }
    }

    @keyframes anniversaryPulse {
        0% {
            opacity: 0.7;
            transform: scale(1);
        }
        70%, 100% {
            opacity: 0;
            transform: scale(1.18);
        }
    }

    @keyframes anniversaryFade {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes anniversaryModalIn {
        from {
            opacity: 0;
            transform: translateY(18px) scale(0.96);
        }
        to {
            opacity: 1;
            transform: none;
        }
    }

    @media (max-width: 520px) {
        .anniversary-widget {
            width: 74px;
        }

        .anniversary-widget__badge {
            font-size: 8px;
            padding: 3px 8px;
        }

        .anniversary-modal {
            padding: 12px;
        }

        .anniversary-modal__dialog {
            max-height: calc(100vh - 24px);
        }

        .anniversary-modal__media {
            padding: 12px;
        }

        .anniversary-modal__body {
            padding: 18px 18px 22px;
        }

        .anniversary-modal__actions {
            flex-direction: column;
        }

        .anniversary-modal__btn {
            width: 100%;
        }
    }

    @media print {
        .anniversary-widget,
        .anniversary-modal {
            display: none !important;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .anniversary-widget,
        .anniversary-widget__frame,
        .anniversary-widget__pulse,
        .anniversary-modal__backdrop,
        .anniversary-modal__dialog {
            animation: none;
        }

        .anniversary-widget__frame,
        .anniversary-modal__btn,
        .anniversary-modal__close {
            transition: none;
        }
    }
</style>

<script>
(function () {
    var widget = document.querySelector('[data-anniversary-open]');
    var modal = document.getElementById('anniversary-modal');
    if (!widget || !modal) {
        return;
    }

    var dialog = modal.querySelector('.anniversary-modal__dialog');
    var closeButtons = modal.querySelectorAll('[data-anniversary-close]');
    var lastFocus = null;

    function focusable() {
        if (!dialog) {
            return [];
        }

        return dialog.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])');
    }

    function onKeydown(event) {
        if (event.key === 'Escape') {
            event.preventDefault();
            close();
            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        var items = focusable();
        if (items.length === 0) {
            return;
        }

        var first = items[0];
        var last = items[items.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    function open() {
        lastFocus = document.activeElement;
        modal.hidden = false;
        document.body.classList.add('anniversary-modal-open');
        document.addEventListener('keydown', onKeydown);

        var target = modal.querySelector('.anniversary-modal__close');
        if (target) {
            target.focus();
        }
    }

    function close() {
        if (modal.hidden) {
            return;
        }

        modal.hidden = true;
        document.body.classList.remove('anniversary-modal-open');
        document.removeEventListener('keydown', onKeydown);

        if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus();
        }
    }

    widget.addEventListener('click', open);

    for (var i = 0; i < closeButtons.length; i++) {
        closeButtons[i].addEventListener('click', close);
    }
})();
</script>
