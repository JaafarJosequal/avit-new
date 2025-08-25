define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/translate'
], function ($, modal, $t) {
    'use strict';

    return function (config) {
        $(document).ready(function () {
            // Import button click
            $('#import-categories-btn').on('click', function () {
                $('.import-section').show();
                $('.export-section').hide();
            });

            // Export button click
            $('#export-categories-btn').on('click', function () {
                $('.export-section').show();
                $('.import-section').hide();
            });

            // Download sample button click
            $('#download-sample-btn').on('click', function () {
                window.location.href = config.sampleCsvUrl;
            });

            // Import form submit
            $('#import-form').on('submit', function (e) {
                e.preventDefault();

                var formData = new FormData();
                var fileInput = $('#import_file')[0];

                if (fileInput.files.length === 0) {
                    alert($t('Please select a file to import.'));
                    return;
                }

                formData.append('import_file', fileInput.files[0]);

                $.ajax({
                    url: config.importUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    showLoader: true,
                    success: function (response) {
                        if (response.success) {
                            alert($t('Import completed successfully! %1 categories imported.').replace('%1', response.imported_count));
                            if (response.errors && response.errors.length > 0) {
                                console.log('Import errors:', response.errors);
                            }
                        } else {
                            alert($t('Import failed: %1').replace('%1', response.message));
                        }
                    },
                    error: function () {
                        alert($t('Import failed. Please try again.'));
                    }
                });
            });

            // Export form submit
            $('#export-form').on('submit', function (e) {
                e.preventDefault();

                var formData = $(this).serialize();
                var exportUrl = config.exportUrl + '?' + formData;

                // Create a temporary form to submit
                var tempForm = $('<form>', {
                    'method': 'POST',
                    'action': exportUrl
                });

                $('body').append(tempForm);
                tempForm.submit();
                tempForm.remove();
            });
        });
    };
});
