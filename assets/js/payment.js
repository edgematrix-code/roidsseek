/**
 * payment.js - interactions for the crypto payment page.
 *
 * Copy buttons, hash-paste helper, the payment-window countdown and the
 * double-submit guard. Everything degrades gracefully: with JavaScript off the
 * address is still selectable, the form still posts, and the deadline is still
 * printed as text.
 */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ------------------------------------------------------------------ copy
    function copyText(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }

        // Fallback for non-HTTPS origins and older browsers.
        return new Promise(function (resolve, reject) {
            var area = document.createElement('textarea');
            area.value = text;
            area.setAttribute('readonly', '');
            area.style.position = 'fixed';
            area.style.top = '-1000px';
            document.body.appendChild(area);
            area.select();
            try {
                var ok = document.execCommand('copy');
                document.body.removeChild(area);
                ok ? resolve() : reject(new Error('copy failed'));
            } catch (error) {
                document.body.removeChild(area);
                reject(error);
            }
        });
    }

    function flashCopied(button, label) {
        var original = button.getAttribute('data-copy-label') || button.textContent.trim();
        button.setAttribute('data-copy-label', original);
        button.classList.add('is-copied');
        button.textContent = label;
        window.setTimeout(function () {
            button.classList.remove('is-copied');
            button.textContent = original;
        }, 1800);
    }

    document.querySelectorAll('[data-copy]').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = document.querySelector(button.getAttribute('data-copy'));
            if (!target) { return; }
            copyText(target.textContent.trim()).then(function () {
                flashCopied(button, 'Copied');
            }, function () {
                flashCopied(button, 'Press Ctrl+C');
            });
        });
    });

    // ----------------------------------------------------------------- paste
    var pasteButton = document.querySelector('[data-paste-into]');
    if (pasteButton) {
        var canRead = !!(navigator.clipboard && navigator.clipboard.readText);
        if (!canRead) {
            pasteButton.hidden = true;
        } else {
            pasteButton.addEventListener('click', function () {
                var field = document.querySelector(pasteButton.getAttribute('data-paste-into'));
                if (!field) { return; }
                navigator.clipboard.readText().then(function (text) {
                    field.value = text.trim();
                    field.focus();
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                }, function () {
                    flashCopied(pasteButton, 'Press Ctrl+V');
                });
            });
        }
    }

    // ------------------------------------------------------------- countdown
    var timer = document.querySelector('[data-countdown]');
    if (timer) {
        var total = parseInt(timer.getAttribute('data-total-seconds'), 10) || 0;
        // Remaining time is computed on the server, so a skewed client clock
        // cannot shorten or extend the window.
        var remaining = parseInt(timer.getAttribute('data-remaining-seconds'), 10);
        if (isNaN(remaining)) { remaining = total; }

        var label = timer.querySelector('[data-countdown-label]');
        var bar = timer.querySelector('[data-countdown-bar]');
        var live = timer.querySelector('[data-countdown-live]');
        var form = document.querySelector('[data-payment-form]');
        var expiredNote = document.querySelector('[data-expired-note]');
        var lastAnnounced = null;

        function pad(value) {
            return value < 10 ? '0' + value : String(value);
        }

        function render() {
            if (remaining <= 0) {
                timer.classList.remove('is-warning', 'is-critical');
                timer.classList.add('is-expired');
                if (label) { label.textContent = 'Expired'; }
                if (bar) { bar.style.width = '0%'; }
                if (live && lastAnnounced !== 0) {
                    live.textContent = 'The payment window has expired.';
                    lastAnnounced = 0;
                }
                if (expiredNote) { expiredNote.hidden = false; }
                if (form) {
                    form.querySelectorAll('input, button, textarea').forEach(function (field) {
                        field.disabled = true;
                    });
                    // The sticky mobile bar submits through the form attribute.
                    document.querySelectorAll('[form="' + form.id + '"]').forEach(function (field) {
                        field.disabled = true;
                    });
                }
                return false;
            }

            var minutes = Math.floor(remaining / 60);
            var seconds = remaining % 60;
            if (label) { label.textContent = pad(minutes) + ':' + pad(seconds); }

            var ratio = total > 0 ? Math.max(0, Math.min(1, remaining / total)) : 0;
            if (bar) { bar.style.width = (ratio * 100).toFixed(2) + '%'; }

            timer.classList.toggle('is-warning', remaining < 300 && remaining >= 60);
            timer.classList.toggle('is-critical', remaining < 60);

            // Announce once a minute rather than every tick.
            var minuteBucket = Math.ceil(remaining / 60);
            if (live && minuteBucket !== lastAnnounced && remaining >= 60) {
                live.textContent = minuteBucket + ' minute' + (minuteBucket === 1 ? '' : 's') + ' remaining.';
                lastAnnounced = minuteBucket;
            }

            return true;
        }

        if (render()) {
            var tick = window.setInterval(function () {
                remaining -= 1;
                if (!render()) { window.clearInterval(tick); }
            }, 1000);
        }

        if (reduceMotion && bar) { bar.style.transition = 'none'; }
    }

    // ------------------------------------------------------- mobile summary
    // The summary is a <details> element: fully open on desktop, collapsed to
    // a total bar on phones. Native <details> handles the toggling.
    var summary = document.querySelector('details[data-summary]');
    if (summary && window.matchMedia && window.matchMedia('(max-width: 959px)').matches) {
        summary.removeAttribute('open');
    }

    // ------------------------------------------------------- submit guard
    var form = document.querySelector('[data-payment-form]');
    if (form) {
        form.addEventListener('submit', function () {
            var button = form.querySelector('[data-submit]');
            if (!button || button.disabled) { return; }
            button.disabled = true;
            button.classList.add('is-loading');
            var text = button.querySelector('[data-submit-label]');
            if (text) { text.textContent = 'Processing...'; }
            // Re-enable if the browser restores the page from cache.
            window.setTimeout(function () {
                if (!document.hidden) { return; }
            }, 0);
        });

        window.addEventListener('pageshow', function (event) {
            if (!event.persisted) { return; }
            var button = form.querySelector('[data-submit]');
            if (!button) { return; }
            button.disabled = false;
            button.classList.remove('is-loading');
            var text = button.querySelector('[data-submit-label]');
            if (text) { text.textContent = 'I have transferred the amount'; }
        });
    }

    // --------------------------------------------------------------- QR code
    var qrTarget = document.querySelector('[data-qr]');
    if (qrTarget && window.CryptoQR) {
        var value = qrTarget.getAttribute('data-qr');
        if (value) {
            try {
                qrTarget.innerHTML = window.CryptoQR.svg(value, {
                    scale: 4,
                    margin: 1,
                    label: qrTarget.getAttribute('data-qr-label') || 'Payment address QR code'
                });
            } catch (error) {
                // Too long for the encoder: keep the address-only layout.
                qrTarget.hidden = true;
            }
        }
    }
})();
