jQuery(document).ready(function($) {
    // Function to check the selected payment method and set the account number
    function setHiberniaAccountNumber() {
        // Check if the "Invoice" method is selected
        if ($('#payment_method_invoice').is(':checked')) {
            // If the "Invoice" method is selected, clear the account number and show the field
            $('#hibernia_acc_no').val('');  // Ensure it's cleared if Invoice is selected
            $('#hibernia_acc_no').closest('.payment_box').show();
        } else {
            // If "Invoice" is not selected (like COD), set the account number to 'p2010' and hide the field
            $('#hibernia_acc_no').val('GUEST');  // Set 'p2010' for other methods (e.g., COD)
            $('#hibernia_acc_no').closest('.payment_box').hide(); // Optionally hide the input field
        }
    }

    // Run the function on document ready to check the initial selection
    setHiberniaAccountNumber();

    // Listen for changes on the payment method radio buttons to update the account number
    $('input[name="payment_method"]').on('change', function() {
        setHiberniaAccountNumber(); // Update the account number when payment method changes
    });

    // Initialize the loader HTML and append it to the body
    $('body').append(`
        <div id="custom-loader" class="loader" style="display: none;">
            <div class="loader-spinner"></div>
        </div>
    `);

    // Show loader when AJAX starts and hide when it stops
    $(document).ajaxStart(function() {
        $('#custom-loader').show();  // Show the loader when AJAX starts
    });

    $(document).ajaxStop(function() {
        $('#custom-loader').hide();  // Hide the loader when AJAX stops
    });

    // Bind the 'blur' event on the account number field to validate input
    $('#hibernia_acc_no').on('blur', function(e) {
        e.preventDefault();
        const accountNumber = $(this).val();
        const paymentMethod = $('input[name="payment_method"]:checked').val(); // Get selected payment method

        // If "Invoice" is selected, proceed with account number validation
        if (accountNumber && $('#payment_method_invoice').is(':checked')) {
            $.ajax({
                url: wac_checkout_ajax_obj.ajax_url,
                type: 'POST',
                data: {
                    action: 'wac_validate_account_number',
                    account_number: accountNumber,
                    payment_method: paymentMethod, // Pass the selected payment method
                    nonce: wac_checkout_ajax_obj.nonce
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Account number verified');
                        $('#place_order').prop('disabled', false); // Enable "Place Order" button
                    } else {
                        alert('The account number you entered is not valid for direct debit. Please contact our customer service.');
                        $('#place_order').prop('disabled', true); // Disable "Place Order" button
                    }
                },
                error: function(xhr, status, error) {
                    console.log("AJAX Error:", error);
                    alert('An error occurred while validating the account number. Please try again.');
                }
            });
        } else {
            // If account number is empty or Invoice is not selected, disable the "Place Order" button
            $('#place_order').prop('disabled', true);
        }
    });
});
