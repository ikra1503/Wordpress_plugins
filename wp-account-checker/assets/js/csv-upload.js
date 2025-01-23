jQuery(document).ready(function ($) {

    $('body').append(`
        <div id="custom-loader" class="loader" style="display: none;">
            <div class="loader-spinner"></div>
        </div>
    `);

    // Handle AJAX request start and end to show/hide the loader
    $(document).ajaxStart(function () {
        $('#custom-loader').show();  // Show the loader when AJAX starts
    });

    $(document).ajaxStop(function () {
        $('#custom-loader').hide();  // Hide the loader when AJAX stops
    });

    
    $('#wac-csv-upload-form').on('submit', function (e) {
        e.preventDefault();

        var formData = new FormData(this);
        formData.append('action', 'wac_handle_csv_upload');
        formData.append('nonce', wac_csv_ajax_obj.nonce);

        // Show the loading bar
        $('#loading-container').show(); // Make sure you have this element in your HTML
        $('#loading-bar').css('width', '0%'); // Reset progress

        $.ajax({
            url: wac_csv_ajax_obj.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function () {
                var xhr = new XMLHttpRequest();

                // Monitor the progress of the file upload
                xhr.upload.addEventListener('progress', function (event) {
                    if (event.lengthComputable) {
                        var percent = (event.loaded / event.total) * 100;
                        $('#loading-bar').css('width', percent + '%'); // Update progress bar width
                    }
                }, false);

                return xhr;
            },
            success: function (response) {
                if (response.success) {
                    $('#wac-csv-upload-result').html('<div class="updated"><p>' + response.data.message + '</p></div>');
                    // Optionally, refresh the account numbers list
                    $('#wac-account-list').html(response.data.account_numbers_list);
                    location.reload();
                } else {
                    $('#wac-csv-upload-result').html('<div class="error"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function () {
                $('#wac-csv-upload-result').html('<div class="error"><p>There was an error processing the CSV file.</p></div>');
            },
            complete: function () {
                // Hide the loading bar when the request is complete
                $('#loading-container').fadeOut();
            }
        });
    });
});
