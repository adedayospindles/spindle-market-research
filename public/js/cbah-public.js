const chartData = (window.CBAH_PUBLIC_DATA && window.CBAH_PUBLIC_DATA.chartData) || { hist: {} };

function cbahSetVisible(element, visible) {
    if (!element) return;
    element.classList.toggle('cbah-is-visible', !!visible);
    element.classList.toggle('cbah-is-hidden', !visible);
}

        function cbahLoadReport(row) {
            document.querySelectorAll('.cbah-interactive-table tr').forEach(r => r.classList.remove('active-row'));
            row.classList.add('active-row');
            document.getElementById('rep-preview-title').innerText = row.getAttribute('data-title');
            document.getElementById('rep-preview-desc').innerText = row.getAttribute('data-desc');
            
            let file = row.getAttribute('data-file');
            let actionWrap = document.getElementById('rep-preview-actions');
            if(file && file !== '#') { 
                cbahSetVisible(actionWrap, true); 
                document.getElementById('rep-preview-dl-btn').href = file; 
                document.getElementById('rep-preview-view-btn').href = file; 
            } else { cbahSetVisible(actionWrap, false); }
        }
        
        function cbahLoadSectorPost(row) {
            document.querySelectorAll('#sector-reports-list tr').forEach(r => r.classList.remove('active-row'));
            row.classList.add('active-row');
            document.getElementById('sec-preview-title').innerText = row.getAttribute('data-title');
            
            let mainPdf = row.getAttribute('data-mainpdf');
            let actionWrap = document.getElementById('sec-preview-actions');
            if(mainPdf && mainPdf !== '#') { 
                cbahSetVisible(actionWrap, true); 
                document.getElementById('sec-preview-main-btn').href = mainPdf; 
                document.getElementById('sec-preview-view-btn').href = mainPdf; 
            } else { cbahSetVisible(actionWrap, false); }
            
            let payload = row.querySelector('.hidden-payload');
            document.getElementById('sec-preview-content').innerHTML = payload.querySelector('.sp-content').innerHTML;
            
            let sectorCards = payload.querySelectorAll('.cbah-sector-toggle-card');
            let tabsBar = document.getElementById('sec-tabs-bar');
            let panesContainer = document.getElementById('sec-panes-container');
            
            tabsBar.innerHTML = '';
            panesContainer.innerHTML = '';

            if(sectorCards.length > 0) {
                tabsBar.classList.add('cbah-is-visible');
                sectorCards.forEach((card, index) => {
                    let sectorName = card.getAttribute('data-name');
                    let tabId = 'sec-pane-' + index;
                    
                    let tabSpan = document.createElement('span');
                    tabSpan.className = 'cbah-sec-tab ' + (index === 0 ? 'active' : '');
                    tabSpan.setAttribute('data-target', tabId);
                    tabSpan.innerText = sectorName;
                    tabsBar.appendChild(tabSpan);

                    let paneDiv = document.createElement('div');
                    paneDiv.id = tabId;
                    paneDiv.className = 'cbah-sec-pane';
                    if (index === 0) { paneDiv.classList.add('active'); }
                    paneDiv.innerHTML = card.innerHTML;
                    panesContainer.appendChild(paneDiv);
                });

                document.querySelectorAll('.cbah-sec-tab').forEach(tab => {
                    tab.addEventListener('click', function() {
                        document.querySelectorAll('.cbah-sec-tab').forEach(t => {
                            t.classList.remove('active');
                        });
                        this.classList.add('active');
                        
                        document.querySelectorAll('.cbah-sec-pane').forEach(p => p.classList.remove('active'));
                        document.getElementById(this.dataset.target).classList.add('active');
                    });
                });
            } else {
                tabsBar.classList.remove('cbah-is-visible');
                panesContainer.innerHTML = '<p class="cbah-empty-sector">No sector breakdowns provided for this report.</p>';
            }
        }
        
        function cbahLoadMacroPost(row) {
            const macroSection = row.closest('.cbah-tab-pane') || document;
            macroSection.querySelectorAll('#macro-reports-list tr').forEach(r => r.classList.remove('active-row'));
            row.classList.add('active-row');
            document.getElementById('mac-preview-title').innerText = row.getAttribute('data-title');
            
            let file = row.getAttribute('data-file');
            let actionWrap = document.getElementById('mac-preview-actions');
            if(file && file !== '#') { 
                cbahSetVisible(actionWrap, true); 
                document.getElementById('mac-preview-dl-btn').href = file; 
                document.getElementById('mac-preview-view-btn').href = file; 
            } else { cbahSetVisible(actionWrap, false); }
            
            let payload = row.querySelector('.hidden-payload');
            document.getElementById('mac-preview-content').innerHTML = payload.querySelector('.mac-content').innerHTML;
            document.getElementById('mac-val-inf').innerText = payload.querySelector('.mac-inf').innerText;
            document.getElementById('mac-val-int').innerText = payload.querySelector('.mac-int').innerText;
            document.getElementById('mac-val-crude').innerText = payload.querySelector('.mac-crude').innerText;
            document.getElementById('mac-val-gov').innerHTML = payload.querySelector('.mac-gov').innerHTML;

            const macroPreview = row.closest('.cbah-reports-preview') || document;
            macroPreview.querySelectorAll('.cbah-mac-tab').forEach(t => {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
            let firstTab = macroPreview.querySelector('.cbah-mac-tab[data-target="mac-tab-indicators"]');
            if(firstTab) {
                firstTab.classList.add('active');
                firstTab.setAttribute('aria-selected', 'true');
            }
            
            macroPreview.querySelectorAll('.cbah-mac-pane').forEach(p => p.classList.remove('active'));
            let indPane = macroPreview.querySelector('#mac-tab-indicators');
            if(indPane) indPane.classList.add('active');
        }
        
        function cbahTriggerSearch(ticker) {
            const searchInput = document.getElementById('cbah-stock-search');
            if(searchInput) {
                searchInput.value = ticker;
                cbahExecuteStockSearch(ticker);
            }
        }

        function cbahExecuteStockSearch(val) {
            if (!val) return;
            val = val.toUpperCase();
            const highlight = document.getElementById('market-data-highlight');
            if (highlight) {
                highlight.innerHTML = '';
                const heading = document.createElement('h3');
                heading.textContent = val;
                heading.className = 'cbah-market-symbol-title';
                const meta = document.createElement('p');
                meta.textContent = 'Active Symbol';
                meta.className = 'cbah-active-symbol';
                highlight.appendChild(heading);
                highlight.appendChild(meta);
            }
            const chartContainer = document.getElementById('market-data-chart');
            chartContainer.innerHTML = '<div id="tv_chart_container" class="cbah-tv-chart-container"></div>';

            new TradingView.widget({
                "autosize": true,
                "symbol": "NSENG:" + val,
                "interval": "D",
                "timezone": "Africa/Lagos",
                "theme": "light",
                "style": "1",
                "locale": "en",
                "container_id": "tv_chart_container"
            });
        }

        document.addEventListener("DOMContentLoaded", function() {
            // 1. Sidebar Tabs
            document.querySelectorAll('.cbah-app-wrapper').forEach(app => {
                app.querySelectorAll('.cbah-nav-item').forEach(item => {
                    item.addEventListener('click', function() {
                        const target = app.querySelector('#' + CSS.escape(this.dataset.tab));
                        if (!target) return;
                        app.querySelectorAll('.cbah-nav-item, .cbah-tab-pane').forEach(el => el.classList.remove('active'));
                        this.classList.add('active');
                        target.classList.add('active');
                    });
                });
            });

            // Macro Mini Tabs Logic
            document.addEventListener('click', function(event) {
                const tab = event.target.closest('.cbah-mac-tab');
                if (!tab) return;

                const targetId = tab.getAttribute('data-target');
                const preview = tab.closest('.cbah-reports-preview');
                const targetPane = preview ? preview.querySelector('#' + CSS.escape(targetId)) : document.getElementById(targetId);
                if (!targetPane) return;

                const tabScope = preview || document;
                tabScope.querySelectorAll('.cbah-mac-tab').forEach(t => {
                    t.classList.remove('active');
                    t.setAttribute('aria-selected', 'false');
                });
                tabScope.querySelectorAll('.cbah-mac-pane').forEach(p => p.classList.remove('active'));

                tab.classList.add('active');
                tab.setAttribute('aria-selected', 'true');
                targetPane.classList.add('active');
            });

            // Auto-load First Items for Master-Detail views
            let firstReport = document.querySelector('#tab-reports .cbah-interactive-table tbody tr');
            if(firstReport && !firstReport.querySelector('.cbah-empty')) { cbahLoadReport(firstReport); }

            let firstSectorPost = document.querySelector('#sector-reports-list tr');
            if(firstSectorPost && !firstSectorPost.querySelector('.cbah-empty')) { cbahLoadSectorPost(firstSectorPost); }

            let firstMacroPost = document.querySelector('#tab-macro-reports .cbah-interactive-table tbody tr');
            if(firstMacroPost && !firstMacroPost.querySelector('.cbah-empty')) { cbahLoadMacroPost(firstMacroPost); }

            // 2. Fixed Income Filter
            const fixedFilter = document.getElementById('cbah-fixed-filter');
            if(fixedFilter) {
                fixedFilter.addEventListener('change', function() {
                    let filter = this.value;
                    document.querySelectorAll('#cbah_table_fixed tr').forEach(tr => {
                        cbahSetVisible(tr, filter === 'all' || tr.getAttribute('data-type') === filter);
                    });
                });
            }

            // 3. Stock Search Listeners
            const searchInput = document.getElementById('cbah-stock-search');
            const searchBtn   = document.getElementById('cbah-search-btn');

            if (searchBtn && searchInput) {
                searchBtn.addEventListener('click', () => cbahExecuteStockSearch(searchInput.value));
                searchInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') cbahExecuteStockSearch(searchInput.value);
                });
            }

            // Load initial chart
            cbahExecuteStockSearch('MTNN');

            // Set 5D as active pill by default
            const pills = document.querySelectorAll('#cbah-history-pills span');
            pills.forEach(p => {
                p.classList.remove('active');
                if(p.getAttribute('data-period') === '5d') {
                    p.classList.add('active');
                }
            });

            // 4. Charts Initialization (5D default initial state)
            const cbahChartFont = getComputedStyle(document.documentElement).getPropertyValue('--cbah-body-font').trim() || '"Josefin Sans", sans-serif';
            const marketCanvas = document.getElementById('cbahMarketChart');
            let marketChart = null;
            if (marketCanvas) {
                marketChart = new Chart(marketCanvas.getContext('2d'), {
                    type: 'line',
                    data: { labels: Array.from({length: chartData.hist['5d'].length}, () => ''), datasets: [{ data: chartData.hist['5d'], borderColor: '#1a2245', backgroundColor: 'rgba(26, 34, 69, 0.08)', fill: true, tension: 0.2, pointRadius: 2 }] },
                    options: { 
                        responsive: true, 
                        maintainAspectRatio: false, 
                        plugins: { legend: false }, 
                        scales: {
                            x: { display: false },
                            y: {
                                ticks: {
                                    font: { family: cbahChartFont, size: 12 },
                                    color: '#334155'
                                }
                            }
                        } 
                    }
                });
            }

            const sectorCanvas = document.getElementById('cbahSectorChart');
            if (sectorCanvas) {
                let sectorChart = new Chart(sectorCanvas.getContext('2d'), {
                    type: 'bar',
                    data: { 
                        labels: ['Banking', 'Insurance', 'Industrial', 'Oil & Gas', 'Consumer Goods'], 
                        datasets: [{ 
                            data: [
                                chartData.sectors.banking,
                                chartData.sectors.insurance,
                                chartData.sectors.industrial,
                                chartData.sectors.oil,
                                chartData.sectors.consumer_goods
                            ], 
                            backgroundColor: ['#1e293b', '#3b82f6', '#10b981', '#f59e0b', '#6366f1'], 
                            borderRadius: 3 
                        }] 
                    },
                    options: { 
                        responsive: true, 
                        maintainAspectRatio: false, 
                        layout: {
                            padding: { top: 10 }
                        },
                        plugins: { 
                            legend: false,
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let value = context.parsed.y !== null ? context.parsed.y : context.raw;
                                        return ' ' + value + '%';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: { font: { family: cbahChartFont, size: 11 }, color: '#334155' }
                            },
                            y: {
                                title: {
                                    display: true,
                                    text: '%',
                                    align: 'end', // Positions the '%' sign cleanly at the top of the axis line
                                    font: { family: cbahChartFont, size: 12, weight: 'bold' },
                                    color: '#334155',
                                    padding: { bottom: 4 }
                                },
                                grid: {
                                    // Highlights the zero baseline clearly for negative/positive tracking
                                    color: function(context) {
                                        if (context.tick.value === 0) {
                                            return '#64748b'; // Darker line for zero
                                        }
                                        return '#f1f5f9'; // Standard grid lines
                                    },
                                    lineWidth: function(context) {
                                        return context.tick.value === 0 ? 1.5 : 1;
                                    }
                                },
                                ticks: { 
                                    font: { family: cbahChartFont, size: 12 }, 
                                    color: '#334155'
                                }
                            }
                        }
                    }
                });
            }

            const turnoverCanvas = document.getElementById('cbahTurnoverChart');
            if (turnoverCanvas) {
                let turnoverChart = new Chart(turnoverCanvas.getContext('2d'), {
                    type: 'doughnut',
                    data: { labels: ['Vol', 'Val'], datasets: [{ data: [chartData.turnover.volume, chartData.turnover.value], backgroundColor: ['#94a3b8', '#1a2245'], borderWidth: 0 }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: false }, cutout: '70%' }
                });
            }

            // Chart History Pills Event Listener
            document.querySelectorAll('#cbah-history-pills span').forEach(pill => {
                pill.addEventListener('click', function() {
                    document.querySelectorAll('#cbah-history-pills span').forEach(p => p.classList.remove('active'));
                    this.classList.add('active');
                    
                    let period = this.getAttribute('data-period');
                    let newData = chartData.hist[period] || chartData.hist['1d'];
                    
                    if (marketChart && newData && Array.isArray(newData)) {
                        marketChart.data.labels = Array.from({length: newData.length}, () => '');
                        marketChart.data.datasets[0].data = newData;
                        marketChart.update();
                    }
                });
            });

            // 5. LIVE AJAX SYNC
            setInterval(function() {
                fetch(window.CBAH_PUBLIC_DATA.ajaxUrl + '?action=cbah_get_live_metrics')
                .then(response => response.json())
                .then(res => {
                    if ( ! res.success ) return;
                    let d = res.data;
                    
                    let gHtml = '';
                    if (d.gainers && d.gainers.length > 0) {
                        d.gainers.forEach(item => { 
                            let cleanPct = item.percentage.toString().replace(/[\+\-\%\s]/g, '');
                            gHtml += `<tr>
                                <td><strong>${item.ticker}</strong></td>
                                <td class="cbah-table-price-value">${item.price}</td>
                                <td class="cbah-table-value cbah-txt-green">+${cleanPct}%</td>
                            </tr>`; 
                        });
                    } else { gHtml = '<tr><td colspan="3" class="cbah-empty">No Data</td></tr>'; }
                    const gainerTable = document.getElementById('cbah_table_gainers');
                    if(gainerTable) gainerTable.innerHTML = gHtml;

                    let lHtml = '';
                    if (d.losers && d.losers.length > 0) {
                        d.losers.forEach(item => { 
                            let cleanPct = item.percentage.toString().replace(/[\+\-\%\s]/g, '');
                            lHtml += `<tr>
                                <td><strong>${item.ticker}</strong></td>
                                <td class="cbah-table-price-value">${item.price}</td>
                                <td class="cbah-table-value cbah-txt-red">-${cleanPct}%</td>
                            </tr>`; 
                        });
                    } else { lHtml = '<tr><td colspan="3" class="cbah-empty">No Data</td></tr>'; }
                    const loserTable = document.getElementById('cbah_table_losers');
                    if(loserTable) loserTable.innerHTML = lHtml;
                });
            }, 30000);
        });
