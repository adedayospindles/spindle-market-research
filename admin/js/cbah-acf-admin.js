jQuery(document).ready(function($) {
            if (typeof acf === 'undefined') return;

            // 1. AUTO-COLLAPSE ACCORDION FOR SECTOR REPORTS
            $(document).on('click', '.acf-field[data-name="sector_breakdown_reports"] .acf-row-handle', function() {
                var $clickedRow = $(this).closest('.acf-row');
                setTimeout(function() {
                    if (!$clickedRow.hasClass('-collapsed')) {
                        $clickedRow.siblings('.acf-row:not(.-clone)').addClass('-collapsed');
                    }
                }, 10);
            });

            // 2. CSV IMPORTER FOR TOP GAINERS & TOP LOSERS
            function injectCsvButtons() {
                var $gainers = $('.acf-field[data-name="market_gainers"] > .acf-label');
                var $losers  = $('.acf-field[data-name="market_losers"] > .acf-label');

                if ($gainers.length && !$gainers.find('.cbah-csv-wrap').length) {
                    $gainers.append(`
                        <div class="cbah-csv-wrap" class="cbah-csv-wrap">
                            <label class="cbah-csv-label">
                                📁 Import Gainers CSV
                                <input type="file" class="cbah-csv-input" data-target="market_gainers" accept=".csv" class="cbah-csv-file">
                            </label>
                            <span class="cbah-csv-status" class="cbah-csv-status"></span>
                        </div>
                    `);
                }

                if ($losers.length && !$losers.find('.cbah-csv-wrap').length) {
                    $losers.append(`
                        <div class="cbah-csv-wrap" class="cbah-csv-wrap">
                            <label class="cbah-csv-label">
                                📁 Import Losers CSV
                                <input type="file" class="cbah-csv-input" data-target="market_losers" accept=".csv" class="cbah-csv-file">
                            </label>
                            <span class="cbah-csv-status" class="cbah-csv-status"></span>
                        </div>
                    `);
                }
            }

            injectCsvButtons();

            // Read and Process CSV File
            $(document).on('change', '.cbah-csv-input', function(e) {
                var file = e.target.files[0];
                if (!file) return;

                var targetName = $(this).data('target');
                var $repeaterField = $('.acf-field[data-name="' + targetName + '"]');
                var $status = $(this).closest('.cbah-csv-wrap').find('.cbah-csv-status');

                var reader = new FileReader();
                reader.onload = function(evt) {
                    var lines = evt.target.result.split(/\r\n|\n/);
                    var count = 0;

                    lines.forEach(function(line, idx) {
                        var cols = line.split(',');
                        if (cols.length < 3) return;

                        var symbol = cols[0].trim();
                        var price  = cols[1].trim();
                        var pct    = cols[2].trim();

                        // Skip header row if "price" is not a number
                        if (idx === 0 && isNaN(parseFloat(price))) return;

                        if (symbol !== '') {
                            // Trigger ACF Add Row
                            var $addBtn = $repeaterField.find('> .acf-input > .acf-repeater > .acf-actions .button[data-event="add-row"]');
                            $addBtn.click();

                            // Target the newly added row (last row)
                            var $lastRow = $repeaterField.find('> .acf-input > .acf-repeater > .acf-table > tbody > .acf-row:not(.-clone)').last();

                            $lastRow.find('.acf-field[data-name="ticker"] input').val(symbol).trigger('change');
                            $lastRow.find('.acf-field[data-name="price"] input').val(price).trigger('change');
                            $lastRow.find('.acf-field[data-name="percentage"] input').val(pct).trigger('change');

                            count++;
                        }
                    });

                    $status.text('✓ Imported ' + count + ' items!').show().fadeOut(5000);
                };

                reader.readAsText(file);
                $(this).val(''); // Reset input value
            });
        });
