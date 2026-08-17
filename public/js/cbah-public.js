const chartData = (window.CBAH_PUBLIC_DATA && window.CBAH_PUBLIC_DATA.chartData) || { hist: {} };

        function cbahLoadReport(row) {
            document.querySelectorAll('.cbah-interactive-table tr').forEach(r => r.classList.remove('active-row'));
            row.classList.add('active-row');
            document.getElementById('rep-preview-title').innerText = row.getAttribute('data-title');
            document.getElementById('rep-preview-desc').innerText = row.getAttribute('data-desc');
            
            let file = row.getAttribute('data-file');
            let actionWrap = document.getElementById('rep-preview-actions');
            if(file && file !== '#') { 
                actionWrap.style.display = 'flex'; 
                document.getElementById('rep-preview-dl-btn').href = file; 
                document.getElementById('rep-preview-view-btn').href = file; 
            } else { actionWrap.style.display = 'none'; }
        }
        
        function cbahLoadSectorPost(row) {
            document.querySelectorAll('#sector-reports-list tr').forEach(r => r.classList.remove('active-row'));
            row.classList.add('active-row');
            document.getElementById('sec-preview-title').innerText = row.getAttribute('data-title');
            
            let mainPdf = row.getAttribute('data-mainpdf');
            let actionWrap = document.getElementById('sec-preview-actions');
            if(mainPdf && mainPdf !== '#') { 
                actionWrap.style.display = 'flex'; 
                document.getElementById('sec-preview-main-btn').href = mainPdf; 
                document.getElementById('sec-preview-view-btn').href = mainPdf; 
            } else { actionWrap.style.display = 'none'; }
            
            let payload = row.querySelector('.hidden-payload');
            document.getElementById('sec-preview-content').innerHTML = payload.querySelector('.sp-content').innerHTML;
            
            let sectorCards = payload.querySelectorAll('.cbah-sector-toggle-card');
            let tabsBar = document.getElementById('sec-tabs-bar');
            let panesContainer = document.getElementById('sec-panes-container');
            
            tabsBar.innerHTML = '';
            panesContainer.innerHTML = '';

            if(sectorCards.length > 0) {
                tabsBar.style.display = 'flex';
                sectorCards.forEach((card, index) => {
                    let sectorName = card.getAttribute('data-name');
                    let tabId = 'sec-pane-' + index;
                    
                    let tabSpan = document.createElement('span');
                    tabSpan.className = 'cbah-sec-tab ' + (index === 0 ? 'active' : '');
                    tabSpan.setAttribute('data-target', tabId);
                    tabSpan.innerText = sectorName;
                    tabSpan.style.cssText = "cursor:pointer; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; font-weight:700; padding-bottom:10px; margin-bottom:-1px; border-bottom:2px solid " + (index === 0 ? '#3b82f6' : 'transparent') + "; color:" + (index === 0 ? '#0f172a' : '#64748b') + ";";
                    tabsBar.appendChild(tabSpan);

                    let paneDiv = document.createElement('div');
                    paneDiv.id = tabId;
                    paneDiv.className = 'cbah-sec-pane';
                    paneDiv.style.cssText = "display:" + (index === 0 ? 'block' : 'none') + "; background:#ffffff; padding:20px; border:1px solid #e2e8f0; border-radius:6px; color:#334155; font-size:13px; line-height:1.6;";
                    paneDiv.innerHTML = card.innerHTML;
                    panesContainer.appendChild(paneDiv);
                });

                document.querySelectorAll('.cbah-sec-tab').forEach(tab => {
                    tab.addEventListener('click', function() {
                        document.querySelectorAll('.cbah-sec-tab').forEach(t => { 
                            t.classList.remove('active');
                            t.style.color = '#64748b'; 
                            t.style.borderBottomColor = 'transparent'; 
                        });
                        this.classList.add('active');
                        this.style.color = '#0f172a'; 
                        this.style.borderBottomColor = '#3b82f6';
                        
                        document.querySelectorAll('.cbah-sec-pane').forEach(p => p.style.display = 'none');
                        document.getElementById(this.dataset.target).style.display = 'block';
                    });
                });
            } else {
                tabsBar.style.display = 'none';
                panesContainer.innerHTML = '<p style="color:#64748b; font-style:italic;">No sector breakdowns provided for this report.</p>';
            }
        }
        
        function cbahLoadMacroPost(row) {
            document.querySelectorAll('#macro-reports-list tr').forEach(r => r.classList.remove('active-row'));
            row.classList.add('active-row');
            document.getElementById('mac-preview-title').innerText = row.getAttribute('data-title');
            
            let file = row.getAttribute('data-file');
            let actionWrap = document.getElementById('mac-preview-actions');
            if(file && file !== '#') { 
                actionWrap.style.display = 'flex'; 
                document.getElementById('mac-preview-dl-btn').href = file; 
                document.getElementById('mac-preview-view-btn').href = file; 
            } else { actionWrap.style.display = 'none'; }
            
            let payload = row.querySelector('.hidden-payload');
            document.getElementById('mac-preview-content').innerHTML = payload.querySelector('.mac-content').innerHTML;
            document.getElementById('mac-val-inf').innerText = payload.querySelector('.mac-inf').innerText;
            document.getElementById('mac-val-int').innerText = payload.querySelector('.mac-int').innerText;
            document.getElementById('mac-val-crude').innerText = payload.querySelector('.mac-crude').innerText;
            document.getElementById('mac-val-gov').innerHTML = payload.querySelector('.mac-gov').innerHTML;

            document.querySelectorAll('.cbah-mac-tab').forEach(t => {
                t.classList.remove('active');
                t.style.color = '#64748b'; 
                t.style.borderBottomColor = 'transparent';
            });
            let firstTab = document.querySelector('.cbah-mac-tab[data-target="mac-tab-indicators"]');
            if(firstTab) {
                firstTab.classList.add('active');
                firstTab.style.color = '#0f172a'; 
                firstTab.style.borderBottomColor = '#3b82f6';
            }
            
            document.querySelectorAll('.cbah-mac-pane').forEach(p => p.style.display = 'none');
            let indPane = document.getElementById('mac-tab-indicators');
            if(indPane) indPane.style.display = 'block';
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
            document.getElementById('market-data-highlight').innerHTML = `<h3>${val}</h3><p style="color:#10b981; font-weight:600;">Active Symbol</p><p style="color:#64748b; font-size:12px;">Displaying live charts via TradingView engine.</p>`;
            const chartContainer = document.getElementById('market-data-chart');
            chartContainer.innerHTML = '<div id="tv_chart_container" style="height:400px; width:100%;"></div>';

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
            document.querySelectorAll('.cbah-nav-item').forEach(item => {
                item.addEventListener('click', function() {
                    document.querySelectorAll('.cbah-nav-item, .cbah-tab-pane').forEach(el => el.classList.remove('active'));
                    this.classList.add('active');
                    document.getElementById(this.dataset.tab).classList.add('active');
                });
            });

            // Macro Mini Tabs Logic
            document.querySelectorAll('.cbah-mac-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    document.querySelectorAll('.cbah-mac-tab').forEach(t => { 
                        t.classList.remove('active');
                        t.style.color = '#64748b'; 
                        t.style.borderBottomColor = 'transparent'; 
                    });
                    
                    this.classList.add('active');
                    this.style.color = '#0f172a'; 
                    this.style.borderBottomColor = '#3b82f6';
                    
                    document.querySelectorAll('.cbah-mac-pane').forEach(p => p.style.display = 'none');
                    document.getElementById(this.dataset.target).style.display = 'block';
                });
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
                        if(filter === 'all') { tr.style.display = ''; } 
                        else { tr.style.display = (tr.getAttribute('data-type') === filter) ? '' : 'none'; }
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
                                    font: { family: "'Josefin Sans', sans-serif", size: 12 },
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
                                ticks: { font: { family: "'Josefin Sans', sans-serif", size: 11 }, color: '#334155' }
                            },
                            y: {
                                title: {
                                    display: true,
                                    text: '%',
                                    align: 'end', // Positions the '%' sign cleanly at the top of the axis line
                                    font: { family: "'Josefin Sans', sans-serif", size: 12, weight: 'bold' },
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
                                    font: { family: "'Josefin Sans', sans-serif", size: 12 }, 
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
                                <td style="text-align:center; color:#64748b;">${item.price}</td>
                                <td style="text-align:right; padding-right: 10px;" class="cbah-txt-green">+${cleanPct}%</td>
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
                                <td style="text-align:center; color:#64748b;">${item.price}</td>
                                <td style="text-align:right; padding-right: 10px;" class="cbah-txt-red">-${cleanPct}%</td>
                            </tr>`; 
                        });
                    } else { lHtml = '<tr><td colspan="3" class="cbah-empty">No Data</td></tr>'; }
                    const loserTable = document.getElementById('cbah_table_losers');
                    if(loserTable) loserTable.innerHTML = lHtml;
                });
            }, 30000);
        });
