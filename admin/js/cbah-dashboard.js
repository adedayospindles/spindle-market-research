(function () {
    'use strict';

    function initTradingViewTicker() {
        var config = window.CBAH_ADMIN_DATA && window.CBAH_ADMIN_DATA.tradingView;
        if (!config || !Array.isArray(config.symbols)) {
            return;
        }

        var containers = document.querySelectorAll('.tradingview-widget-container');
        if (!containers.length) {
            return;
        }

        containers.forEach(function (container) {
            if (container.dataset.cbahTradingviewLoaded === '1') {
                return;
            }

            var widget = container.querySelector('.tradingview-widget-container__widget');
            if (!widget) {
                return;
            }

            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = 'https://s3.tradingview.com/external-embedding/embed-widget-ticker-tape.js';
            script.async = true;
            script.text = JSON.stringify({
                symbols: config.symbols,
                showSymbolLogo: false,
                colorTheme: 'light',
                isTransparent: false,
                displayMode: 'adaptive',
                locale: 'en'
            });

            container.appendChild(script);
            container.dataset.cbahTradingviewLoaded = '1';
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTradingViewTicker);
    } else {
        initTradingViewTicker();
    }
}());
