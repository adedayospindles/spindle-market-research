(function () {
    'use strict';

    function initTicker(ticker) {
        if (ticker.dataset.cbahTickerReady === '1') {
            return;
        }

        var wrapper = ticker.querySelector('.cbah-ticker-wrapper');
        var track = ticker.querySelector('.cbah-ticker-track');
        var group = ticker.querySelector('.cbah-ticker-group-primary') || ticker.querySelector('.cbah-ticker-group');

        if (!wrapper || !track || !group) {
            return;
        }

        ticker.dataset.cbahTickerReady = '1';

        var speed = parseFloat(ticker.getAttribute('data-ticker-speed')) || 60;
        speed = Math.max(10, Math.min(200, speed));
        var position = 0;
        var lastTime = 0;
        var groupWidth = 0;
        var paused = false;
        var frameId = null;
        var retryTimer = null;

        function getGroupWidth() {
            return Math.max(
                group.getBoundingClientRect().width || 0,
                group.scrollWidth || 0
            );
        }

        function ensureContent() {
            groupWidth = getGroupWidth();
            if (!groupWidth) {
                return false;
            }

            // Keep enough repeated content to cover the visible ticker even when
            // only one or two market symbols have been entered. The primary group
            // remains the measurement reference so the loop always wraps at the
            // exact same distance.
            var requiredWidth = Math.max(wrapper.clientWidth * 2, groupWidth * 2);
            var currentWidth = track.scrollWidth || 0;
            var safety = 0;

            while (currentWidth < requiredWidth && safety < 20) {
                var clone = group.cloneNode(true);
                clone.classList.remove('cbah-ticker-group-primary');
                clone.classList.add('cbah-ticker-group-clone');
                clone.setAttribute('aria-hidden', 'true');
                track.appendChild(clone);
                currentWidth = track.scrollWidth || (currentWidth + groupWidth);
                safety++;
            }

            return true;
        }

        function measure() {
            if (!ensureContent()) {
                ticker.classList.remove('cbah-ticker-js');
                return false;
            }

            // Only disable the CSS fallback after the content has a real width.
            ticker.classList.add('cbah-ticker-js');
            position = -((Math.abs(position) % groupWidth));
            track.style.transform = 'translate3d(' + position + 'px, 0, 0)';
            return true;
        }

        function tick(time) {
            if (!lastTime) {
                lastTime = time;
            }

            var delta = Math.min((time - lastTime) / 1000, 0.1);
            lastTime = time;

            if (!paused && groupWidth > 0) {
                position -= speed * delta;

                if (position <= -groupWidth) {
                    position += groupWidth;
                }

                track.style.transform = 'translate3d(' + position + 'px, 0, 0)';
            }

            frameId = window.requestAnimationFrame(tick);
        }

        ticker.addEventListener('mouseenter', function () {
            paused = true;
        });

        ticker.addEventListener('mouseleave', function () {
            paused = false;
            lastTime = 0;
        });

        window.addEventListener('resize', measure, { passive: true });

        if (window.ResizeObserver) {
            new ResizeObserver(measure).observe(wrapper);
            new ResizeObserver(measure).observe(group);
        }

        var measured = measure();

        // Fonts, Elementor layout and responsive containers can finish sizing
        // after DOMContentLoaded. Retry briefly so the ticker never stays hidden
        // simply because its first measurement occurred too early.
        if (!measured || !groupWidth) {
            retryTimer = window.setInterval(function () {
                if (measure() && groupWidth) {
                    window.clearInterval(retryTimer);
                    retryTimer = null;
                }
            }, 100);

            window.setTimeout(function () {
                if (retryTimer) {
                    window.clearInterval(retryTimer);
                    retryTimer = null;
                }
            }, 5000);
        }

        frameId = window.requestAnimationFrame(tick);

        ticker._cbahTickerDestroy = function () {
            if (frameId) {
                window.cancelAnimationFrame(frameId);
            }
            if (retryTimer) {
                window.clearInterval(retryTimer);
            }
        };
    }

    function initAll() {
        document.querySelectorAll('.cbah-price-ticker').forEach(initTicker);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
