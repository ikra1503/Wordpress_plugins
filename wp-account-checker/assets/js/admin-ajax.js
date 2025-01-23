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

    
    $('#wac-account-form').on('submit', function (e) {
        e.preventDefault();

        const accountNumber = $('#account_number').val();
        const editId = $('#edit_id').val();

        $.ajax({
            url: wac_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'wac_save_account_number',
                account_number: accountNumber,
                edit_id: editId,
                nonce: wac_ajax_obj.nonce // Ensure nonce is being passed here
            },
            success: function (response) {
                if (response.success) {
                    $('#wac-account-list').html(response.data);
                    $('#account_number').val('');
                    $('#edit_id').val('');
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function (xhr, status, error) {
                alert('Error: ' + xhr.responseText);
            }
        });

    });


    // Edit account number
    $(document).on('click', '.wac-edit-account', function () {
        const accountId = $(this).data('id');
        const accountNumber = $(this).data('account');

        $('#edit_id').val(accountId);
        $('#account_number').val(accountNumber);
    });

    // Delete account number
    $(document).on('click', '.wac-delete-account', function () {
        const deleteId = $(this).data('id');

        if (confirm('Are you sure you want to delete this account number?')) {

            $.ajax({
                url: wac_ajax_obj.ajax_url,
                type: 'POST',
                data: {
                    action: 'wac_delete_account_number',
                    delete_id: deleteId,
                    nonce: wac_ajax_obj.nonce
                },
                success: function (response) {
                    if (response.success) {
                        $('#wac-account-list').html(response.data);
                    } else {
                        alert('Error: ' + response.data);
                    }
                }
            });
        }
    });
});


//delete selectedand delelte selected all

jQuery(document).ready(function ($) {
    // Handle Delete Selected
    $('#wac-delete-selected').on('click', function () {
        var selectedIds = [];
        $('.select-account:checked').each(function () {
            selectedIds.push($(this).data('id'));
        });

        if (selectedIds.length > 0) {
            var confirmDelete = confirm("Are you sure you want to delete the selected account numbers?");
            if (confirmDelete) {
                $.ajax({
                    type: 'POST',
                    url: wac_ajax_obj.ajax_url,
                    data: {
                        action: 'wac_delete_selected_accounts',
                        nonce: wac_ajax_obj.nonce,
                        selected_ids: selectedIds
                    },
                    success: function (response) {
                        if (response.success) {
                            $('#wac-account-list').html(response.data);
                        }
                    }
                });
            }
        } else {
            alert("No accounts selected.");
        }
    });

    // Handle Delete All
    $('#wac-delete-all').on('click', function () {
        var confirmDelete = confirm("Are you sure you want to delete all account numbers?");
        if (confirmDelete) {
            $.ajax({
                type: 'POST',
                url: wac_ajax_obj.ajax_url,
                data: {
                    action: 'wac_delete_all_accounts',
                    nonce: wac_ajax_obj.nonce
                },
                success: function (response) {
                    if (response.success) {
                        $('#wac-account-list').html(response.data);
                    }
                }
            });
        }
    });
});



