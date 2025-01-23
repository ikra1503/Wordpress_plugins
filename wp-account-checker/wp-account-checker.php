<?php
/*
Plugin Name: Invoice Account Validator
Description: Allows the admin to manage account numbers with AJAX-based CRUD functionality.
Version: 1.0

*/

// Register an admin menu item for Account Numbers
function wac_register_admin_page()
{
    add_menu_page(
        'Account Numbers',
        'Account Numbers',
        'manage_options',
        'account_numbers',
        'wac_render_admin_page',
        'dashicons-admin-generic',
        20
    );
}
add_action('admin_menu', 'wac_register_admin_page');

// Create the database table on plugin activation
function wac_create_database_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'account_numbers';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        account_number varchar(50) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'wac_create_database_table');

// Render the admin page
function wac_render_admin_page()
{
    ?>
    <div class="wrap">
        <h1>Manage Account Numbers</h1>

        <!-- CSV Upload Form -->
        <h2>Upload CSV</h2>
        <form id="wac-csv-upload-form" method="POST" enctype="multipart/form-data">
            <input type="file" name="wac_csv_file" id="wac_csv_file" accept=".csv" required>
            <input type="submit" value="Upload CSV" class="button button-primary">
            <?php wp_nonce_field('wac_csv_upload_nonce', 'wac_csv_upload_nonce_field'); ?>
        </form>
        <div id="loading-container" style="display: none; margin-top: 20px;">
            <div id="loading-bar"></div>
        </div>
        <div id="wac-csv-upload-result"></div>
        <form id="wac-account-form" method="POST">
            <input type="hidden" name="edit_id" id="edit_id">
            <p>
                <label>Account Number:</label>
                <input type="text" name="account_number" id="account_number" required>
            </p>
            <p>
                <input type="submit" value="Save" class="button button-primary">
            </p>
        </form>
        <div>
            <button id="wac-delete-selected" class="button button-secondary">Delete Selected</button>
            <button id="wac-delete-all" class="button button-secondary">Delete All</button>
        </div>
        <h2>Account Numbers</h2>
        <div id="wac-account-list">
            <?php echo wac_get_account_numbers_list(); ?>
        </div>
    </div>
    <?php
}

// Helper function to generate the account numbers list HTML
function wac_get_account_numbers_list()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'account_numbers';
    $entries = $wpdb->get_results("SELECT * FROM $table_name");

    ob_start(); ?>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Select Box</th>
                <th>Sr No.</th>
                <th>Account Number</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $sr_no = 1; ?>
            <?php foreach ($entries as $entry): ?>
                <tr>
                    <td><input type="checkbox" class="select-account" data-id="<?php echo $entry->id; ?>"></td>
                    <!-- <td><?php //echo esc_html($entry->id); ?></td> -->
                    <td><?php echo esc_html($sr_no); ?></td>
                    <td><?php echo esc_html($entry->account_number); ?></td>
                    <td><?php echo esc_html($entry->created_at); ?></td>
                    <td>
                        <button class="button wac-edit-account" data-id="<?php echo $entry->id; ?>"
                            data-account="<?php echo esc_attr($entry->account_number); ?>">Edit</button>
                        <button class="button wac-delete-account" data-id="<?php echo $entry->id; ?>">Delete</button>
                    </td>
                </tr>
                <?php $sr_no++; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    return ob_get_clean();
}

// Handle AJAX request to save (add or update) an account number
function wac_save_account_number()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wac_nonce')) {
        wp_send_json_error('Nonce verification failed.');
    }

    check_ajax_referer('wac_nonce', 'nonce');

    global $wpdb;
    $table_name = $wpdb->prefix . 'account_numbers';

    $account_number = sanitize_text_field($_POST['account_number']);
    $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : null;

    // Check if account number already exists
    $existing_account = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_name WHERE account_number = %s",
        $account_number
    ));

    // If account number exists and it's not the same as the current entry (edit), return an error
    if ($existing_account && $existing_account != $edit_id) {
        wp_send_json_error('Account number already exists.');
    }

    if ($edit_id) {
        // Update existing entry
        $wpdb->update(
            $table_name,
            ['account_number' => $account_number],
            ['id' => $edit_id],
            ['%s'],
            ['%d']
        );
    } else {
        // Insert new entry
        $wpdb->insert(
            $table_name,
            ['account_number' => $account_number],
            ['%s']
        );
    }

    // Return updated account numbers list
    wp_send_json_success(wac_get_account_numbers_list());
}

add_action('wp_ajax_wac_save_account_number', 'wac_save_account_number');

// Handle AJAX request to delete an account number
function wac_delete_account_number()
{
    check_ajax_referer('wac_nonce', 'nonce');

    global $wpdb;
    $table_name = $wpdb->prefix . 'account_numbers';
    $delete_id = intval($_POST['delete_id']);

    $wpdb->delete($table_name, ['id' => $delete_id], ['%d']);

    // Return updated account numbers list
    wp_send_json_success(wac_get_account_numbers_list());
}
add_action('wp_ajax_wac_delete_account_number', 'wac_delete_account_number');

function wac_delete_selected_accounts()
{
    check_ajax_referer('wac_nonce', 'nonce');

    global $wpdb;
    $table_name = $wpdb->prefix . 'account_numbers';
    $selected_ids = $_POST['selected_ids']; // Array of selected IDs

    foreach ($selected_ids as $id) {
        $wpdb->delete($table_name, ['id' => intval($id)], ['%d']);
    }

    wp_send_json_success(wac_get_account_numbers_list());
}
add_action('wp_ajax_wac_delete_selected_accounts', 'wac_delete_selected_accounts');


function wac_delete_all_accounts()
{
    check_ajax_referer('wac_nonce', 'nonce');

    global $wpdb;
    $table_name = $wpdb->prefix . 'account_numbers';

    $wpdb->query("DELETE FROM $table_name");

    wp_send_json_success(wac_get_account_numbers_list());
}
add_action('wp_ajax_wac_delete_all_accounts', 'wac_delete_all_accounts');

// Enqueue JavaScript and pass AJAX URL and nonce to the script
function wac_enqueue_admin_scripts($hook)
{
    if ($hook != 'toplevel_page_account_numbers') {
        return;
    }
    wp_enqueue_script('wac-admin-ajax', plugin_dir_url(__FILE__) . 'assets/js/admin-ajax.js', ['jquery'], '1.0', true);


    // Pass AJAX URL and nonce to the script
    wp_localize_script('wac-admin-ajax', 'wac_ajax_obj', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wac_nonce')
    ]);
}
add_action('admin_enqueue_scripts', 'wac_enqueue_admin_scripts');


//checkout

// Enqueue script on the WooCommerce checkout page.
function wac_enqueue_checkout_scripts()
{


    if (is_checkout()) {
        wp_enqueue_script('wac-checkout-verification', plugin_dir_url(__FILE__) . 'assets/js/checkout-custom-ajax.js', array('jquery'), '1.0', true);

        wp_localize_script('wac-checkout-verification', 'wac_checkout_ajax_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wac_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'wac_enqueue_checkout_scripts');
function wac_enqueue_admin_styles()
{
    wp_enqueue_style('wac-checkout-style', plugin_dir_url(__FILE__) . 'assets/css/styles.css');
}
add_action('admin_enqueue_scripts', 'wac_enqueue_admin_styles');

function wac_check_account_number()
{
    check_ajax_referer('wac_nonce', 'nonce');

    global $wpdb;
    $account_number = sanitize_text_field($_POST['account_number']);
    $table_name = $wpdb->prefix . 'account_numbers';

    // Check if account number exists
    $account_exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE account_number = %s",
        $account_number
    ));

    if ($account_exists > 0) {
        wp_send_json_success('Account number verified');
    } else {
        wp_send_json_error('Account number not found');
    }
}
// add_action('wp_ajax_wac_check_account_number', 'wac_check_account_number');
// add_action('wp_ajax_nopriv_wac_check_account_number', 'wac_check_account_number');


add_action('woocommerce_checkout_process', 'wac_validate_account_number');

function wac_validate_account_number() {
    // Check if the account number is set and not empty
    if (!isset($_POST['hibernia_acc_no']) || empty($_POST['hibernia_acc_no'])) {
        wc_add_notice(__('Please enter your account number.'), 'error');
        return;
    }

    // Retrieve the payment method from the POST data
    $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';

    // Ensure the payment method is either "invoice" or another valid value
    if ($payment_method !== 'invoice') {
        // If payment method is not "Invoice", skip account number validation
        return;
    }

    global $wpdb;
    $account_number = sanitize_text_field($_POST['hibernia_acc_no']);
    $table_name = $wpdb->prefix . 'account_numbers';

    // Check if the account number exists
    $account_exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE account_number = %s",
        $account_number
    ));

    if ($account_exists == 0) {
        // Display error message if account number is invalid for Invoice method
        wc_add_notice(__('So sorry – the account number you have entered does not seem to be on direct debit. Therefore, you cannot avail of the invoice option.
Please call 01 866 5727 to speak with our customer service team.'), 'error');
        return;
    }
}


//handlingcsvupload

function wac_handle_csv_upload()
{
    if (isset($_POST['wac_csv_upload_nonce_field']) && wp_verify_nonce($_POST['wac_csv_upload_nonce_field'], 'wac_csv_upload_nonce')) {

        if (!empty($_FILES['wac_csv_file']['tmp_name'])) {
            $csv_file = $_FILES['wac_csv_file']['tmp_name'];

            if (($handle = fopen($csv_file, "r")) !== FALSE) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'account_numbers';
                $success_count = 0;
                $error_count = 0;

                // Loop through the CSV rows
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $account_number = sanitize_text_field($data[0]);

                    // Insert account number into the database
                    $result = $wpdb->insert(
                        $table_name,
                        ['account_number' => $account_number],
                        ['%s']
                    );

                    if ($result) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                }
                fclose($handle);

                // Provide feedback to the user
                $message = 'CSV Upload Complete! ' . $success_count . ' accounts added successfully.';
                if ($error_count > 0) {
                    $message .= ' ' . $error_count . ' accounts failed to add.';
                }

                wp_send_json_success(['message' => $message]);
            } else {
                wp_send_json_error(['message' => 'Error opening the CSV file.']);
            }
        } else {
            wp_send_json_error(['message' => 'No file uploaded.']);
        }
    } else {
        wp_send_json_error(['message' => 'Invalid nonce.']);
    }

    wp_die();
}
add_action('wp_ajax_wac_handle_csv_upload', 'wac_handle_csv_upload');

// Enqueue JavaScript for handling the CSV upload
function wac_enqueue_csv_upload_script()
{
    if (isset($_GET['page']) && $_GET['page'] == 'account_numbers') {
        wp_enqueue_script('wac-csv-upload', plugin_dir_url(__FILE__) . 'assets/js/csv-upload.js', array('jquery'), '1.0', true);
        wp_localize_script('wac-csv-upload', 'wac_csv_ajax_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wac_csv_upload_nonce')
        ));
    }
}
add_action('admin_enqueue_scripts', 'wac_enqueue_csv_upload_script');


// hand;ing searchfunctionality


