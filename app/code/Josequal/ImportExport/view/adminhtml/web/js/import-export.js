define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    return function (config) {
        $(document).ready(function () {

            // File Upload Handling
            $('.upload-area').on('click', function() {
                $('#import_file').click();
            });

            $('#import_file').on('change', function() {
                var fileName = this.files[0]?.name || '';
                if (fileName) {
                    $('.upload-text').text(fileName);
                    $('.upload-hint').text($t('File selected successfully'));
                    $('.upload-area').addClass('file-selected');
                }
            });

            // Drag and Drop
            $('.upload-area').on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('drag-over');
            });

            $('.upload-area').on('dragleave', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over');
            });

            $('.upload-area').on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over');

                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    $('#import_file')[0].files = files;
                    $('#import_file').trigger('change');
                }
            });

            // Import Form Submission
            $('#import-form').on('submit', function(e) {
                e.preventDefault();

                var formData = new FormData(this);
                var $submitBtn = $(this).find('button[type="submit"]');
                var originalText = $submitBtn.text();

                $submitBtn.prop('disabled', true).text($t('Importing...'));

                // Show progress bar
                $('.progress-bar').show();
                $('.progress-fill').css('width', '0%');

                // Simulate progress
                var progress = 0;
                var progressInterval = setInterval(function() {
                    progress += Math.random() * 15;
                    if (progress > 90) progress = 90;
                    $('.progress-fill').css('width', progress + '%');
                }, 200);

                $.ajax({
                    url: config.importUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        clearInterval(progressInterval);
                        $('.progress-fill').css('width', '100%');

                        setTimeout(function() {
                            if (response.success) {
                                showNotification($t('Import completed successfully!'), 'success');
                            } else {
                                showNotification(response.message || $t('Import failed!'), 'error');
                            }

                            $submitBtn.prop('disabled', false).text(originalText);
                            $('.progress-bar').hide();
                        }, 500);
                    },
                    error: function() {
                        clearInterval(progressInterval);
                        $('.progress-fill').css('width', '100%');

                        setTimeout(function() {
                            showNotification($t('Import failed! Please try again.'), 'error');
                            $submitBtn.prop('disabled', false).text(originalText);
                            $('.progress-bar').hide();
                        }, 500);
                    }
                });
            });

            // Export Button
            $('.export-btn').on('click', function(e) {
                e.preventDefault();

                var $btn = $(this);
                var originalText = $btn.text();

                $btn.prop('disabled', true).text($t('Exporting...'));

                // Show progress bar
                $('.progress-bar').show();
                $('.progress-fill').css('width', '0%');

                // Simulate progress
                var progress = 0;
                var progressInterval = setInterval(function() {
                    progress += Math.random() * 20;
                    if (progress > 90) progress = 90;
                    $('.progress-fill').css('width', progress + '%');
                }, 150);

                // Simulate export completion
                setTimeout(function() {
                    clearInterval(progressInterval);
                    $('.progress-fill').css('width', '100%');

                    setTimeout(function() {
                        showNotification($t('Export completed successfully!'), 'success');
                        $btn.prop('disabled', false).text(originalText);
                        $('.progress-bar').hide();

                        // Trigger download
                        window.location.href = $btn.attr('href');
                    }, 500);
                }, 2000);
            });

            // Notification System
            function showNotification(message, type) {
                var $notification = $('<div class="notification ' + type + '">' + message + '</div>');
                $('body').append($notification);

                $notification.addClass('show');

                setTimeout(function() {
                    $notification.removeClass('show');
                    setTimeout(function() {
                        $notification.remove();
                    }, 300);
                }, 3000);
            }

            // Add notification styles
            $('<style>')
                .prop('type', 'text/css')
                .html(`
                    .notification {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        padding: 15px 20px;
                        border-radius: 8px;
                        color: white;
                        font-weight: 600;
                        z-index: 9999;
                        transform: translateX(100%);
                        transition: transform 0.3s ease;
                        max-width: 300px;
                    }

                    .notification.show {
                        transform: translateX(0);
                    }

                    .notification.success {
                        background: linear-gradient(135deg, #28a745, #20c997);
                    }

                    .notification.error {
                        background: linear-gradient(135deg, #dc3545, #fd7e14);
                    }

                    .notification.warning {
                        background: linear-gradient(135deg, #ffc107, #fd7e14);
                    }
                `)
                .appendTo('head');
        });
    };
});
