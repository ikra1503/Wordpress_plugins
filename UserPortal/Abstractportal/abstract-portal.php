<?php

function enqueue_custom_styles()
{
    // Get the theme directory URI
    $theme_directory = get_stylesheet_directory_uri();
    $random_version = rand();
    // Enqueue the custom stylesheet
    wp_enqueue_style('custom-style', $theme_directory . '/assets/css/custom-style.css');

    // Enqueue jQuery
    wp_enqueue_script('jquery');

    // Enqueue your custom script
    wp_enqueue_script('custom-script', $theme_directory . '/assets/js/custom-script.js', array('jquery'), $random_version, true);

    // Localize the script for AJAX
    // wp_localize_script('custom-script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    wp_localize_script('custom-script', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('bp-group-action')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_custom_styles');
function enqueue_portal_admin_styles()
{
    wp_enqueue_style('portal-admin-css', get_stylesheet_directory_uri() . '/assets/css/portal.css', array(), '1.0.0', 'all');
}
add_action('admin_enqueue_scripts', 'enqueue_portal_admin_styles');


//user abstract portal code starts gfrom here......
function get_user_membership_levels_and_groups($user_id)
{
    global $wpdb;

    // Ensure user ID is valid
    if (!$user_id || !get_userdata($user_id)) {
        return [
            'error' => 'Invalid user ID.'
        ];
    }

    // Fetch user membership levels
    $levels = pmpro_getMembershipLevelsForUser($user_id);

    if (empty($levels)) {
        return [
            'user_id' => $user_id,
            'membership_levels' => [],
            'groups' => [],
        ];
    }

    $level_ids = array_column($levels, 'id'); // Extract only the IDs

    // Fetch group IDs for these membership levels
    $groups_table = $wpdb->prefix . "pmpro_membership_levels_groups";
    $levels_table = $wpdb->prefix . "pmpro_membership_levels";

    $group_data = $wpdb->get_results(
        "SELECT g.level, g.group, l.name 
        FROM $groups_table g 
        INNER JOIN $levels_table l ON g.level = l.id 
        WHERE g.level IN (" . implode(',', array_map('intval', $level_ids)) . ")"
    );

    // Prepare output
    $groups = [];
    foreach ($group_data as $group) {
        $groups[] = [
            'level_id' => $group->level,
            'group_id' => $group->group,
            'level_name' => $group->name,
        ];
    }


    return [
        'user_id' => $user_id,
        'membership_levels' => $level_ids,
        'groups' => $groups,
    ];
}


function check_user_has_group_4($user_id)
{
    $result = get_user_membership_levels_and_groups($user_id);

    // Check if function returned an error
    if (isset($result['error'])) {
        error_log("Error: " . $result['error']);
        return;
    }

    // Extract user's group IDs
    $user_groups = array_column($result['groups'], 'group_id');
    $total_groups = count($user_groups); // Count total groups the user has

    // Log total groups
    error_log("User ID {$user_id} is part of {$total_groups} group(s): " . implode(', ', $user_groups));

    // Check if group ID 4 exists
    if (in_array(4, $user_groups)) {
        error_log("✅ Success: User ID {$user_id} has Group ID 4.");
        return true;
    } else {
        error_log("❌ User ID {$user_id} does NOT have Group ID 4.");
        return false;
    }
}
// add_action('init', function () {
//     if (is_user_logged_in()) {
//         check_user_has_group_4(get_current_user_id());
//     }
// });
// Function to check if the user has Group ID 4 and show a button
// Function to check if the user has Group ID 4 and show the button

function has_user_made_successful_payment($user_id)
{
    if (!$user_id) {
        // If no user ID is provided, return false
        return false;
    }

    // Query the custom table to get the most recent order for the user
    global $wpdb;

    // Your custom table name (you can modify it if needed)
    $table_name = $wpdb->prefix . 'pmpro_membership_orders';  // IWEe3NY_pmpro_membership_orders

    $sql = "
        SELECT * 
        FROM {$table_name}
        WHERE user_id = %d 
        ORDER BY timestamp DESC 
        LIMIT 1
    ";

    // Get the most recent order
    $order = $wpdb->get_row($wpdb->prepare($sql, $user_id));

    if ($order && $order->status === 'success') {
        // User has made the last successful payment
        return true;
    }

    // No successful payment found
    return false;
}
function show_button_for_group_4_user()
{
    // Get current user ID
    $user_id = get_current_user_id();

    // Check if user is logged in and has made a successful payment
    if (is_user_logged_in() && has_user_made_successful_payment($user_id)) {

        // Call the check_user_has_group_4 function
        $result = get_user_membership_levels_and_groups($user_id);

        // Check if function returned an error
        if (isset($result['error'])) {
            error_log("Error: " . $result['error']);
            return;
        }

        // Extract user's group IDs
        $user_groups = array_column($result['groups'], 'group_id');

        // Check if Group ID 4 is present
        if (in_array(4, $user_groups)) {
            // Display the button before the notification panel
            ?>
                <script type="text/javascript">
                    jQuery(document).ready(function ($) {
                        // Store the site URL in a variable
                        var siteUrl = "<?php echo esc_url(get_site_url()); ?>";
                        console.log(siteUrl);

                        // Create the button element with the URL
                        var button = $('<li id="submit-abstract-item" class="menu-item menu-item-type-custom menu-item-object-custom"><a href="' + siteUrl + '/submit-an-abstract/">Submit an Abstract</a></li>');

                        // Append the button as the first item in the sub-menu
                        $('#menu-item-3607 .sub-menu').prepend(button);
                    });
                </script>
                <style>
                    a#special-button {
                        background: #FB8B25;
                        color: #fff;
                        border: none;
                        border-radius: 0px;
                        font-size: 16px;
                        font-family: oswald !important;
                    }

                    a#special-button:hover {
                        background: #fff;
                        color: #FB8B25;
                    }
                </style>
                <?php
        }
    }
}

// Hook the function to display the button on the front-end, ideally in wp_footer
add_action('wp_footer', 'show_button_for_group_4_user');

function abstract_submission_form()
{
    $user_id = get_current_user_id();
    $membership_data = get_user_membership_levels_and_groups($user_id);
    // error_log(print_r($membership_data, true));

    // Check if the user has valid membership levels
    if (!empty($membership_data['membership_levels']) && is_array($membership_data['membership_levels'])) {
        ob_start();
        ?>
            <?php
            if (isset($_GET['abstract_submitted']) && $_GET['abstract_submitted'] == 'true') {
                ?>
                <div id="submission-popup" class="portal-popup">
                    <div class="popup-content">
                        <span class="close">&times;</span>
                        <strong>Your abstract has been submitted!</strong>
                    </div>
                </div>
                <?php
            }
            ?>
            <div class="membership-note">
                <strong>Important Reminder:</strong><br>
                <ul>
                    <li>The co-authors you select must have the same RC subscription level as yours then only co-Author will get
                        the certificate.</li>
                    <li>Please ensure that you enter the correct membership ID for each co-author.</li>
                    <li>Be cautious when adding co-authors' membership IDs to avoid any discrepancies.</li>
                </ul>
            </div>

            <form id="abstract-form" method="POST" action="">
                <label for="author_name">Author Name (Submitter):</label>
                <input type="text" id="author_name" name="author_name"
                    value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>" readonly>

                <input type="hidden" name="author_email" value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>">
                <input type="hidden" name="author_id" value="<?php echo esc_attr(wp_get_current_user()->ID); ?>">
                <label for="co_authors">Co-Author Name(s):</label>
                <div id="co-authors-wrapper">
                    <div class="co-author-fields">
                        <input type="text" name="co_authors[]" placeholder="Enter co-author name">
                        <input type="text" name="co_authors_membership_id[]" placeholder="Enter co-author membership ID">
                    </div>
                </div>
                <button type="button" id="add-co-author">+ Add Co-Author</button>

                <label for="abstract_title">Abstract Title:</label>
                <input type="text" id="abstract_title" name="abstract_title" required>

                <label for="abstract_description">Abstract Text:</label>
                <textarea id="abstract_description" name="abstract_description" required></textarea>

                <label for="membership_selection">Select Research Committee:</label>
                <select id="membership_selection" name="membership_selection" required>
                    <?php
                    // Populate membership levels from the fetched data
            
                    foreach ($membership_data['groups'] as $group) {
                        // Check if the group_id is 4
                        if ($group['group_id'] == 4) {
                            echo '<option value="' . esc_attr($group['level_id']) . '">' . esc_html($group['level_name']) . '</option>';
                        }
                    }

                    echo '</select>';



                    ?>
                </select>

                <button type="button" id="preview-abstract">Preview</button>

                <!-- Submit Button (Initially Hidden) -->
                <input type="submit" name="submit_abstract" value="Submit Abstract" style="display: none;">

            </form>
            <div id="confirmation-modal" class="confirmation-modal">
                <div class="modal-content">
                    <span class="close-modal">&times;</span>
                    <h2>You have submitted following details please verify !!</h2>
                    <div id="form-preview"></div>
                    <div id="missing-conditions" style="display: none; color: red;">
                        <!-- Missing conditions will be dynamically populated here -->
                    </div>
                    <button id="confirm-details" class="confirm-button">Ready to Submit.</button>
                    <button id="edit-details" class="edit-button">I want to edit</button>
                </div>
            </div>
            <div class="listing-buttons">
                <a href="<?php echo site_url('/abstract-list/'); ?>" class="view-abstracts-button">View Your Abstracts</a>
                <a href="<?php echo site_url('/co-author-abstract-list/'); ?>" class="view-abstracts-button">View your
                    Co-Authorship</a>
            </div>
            <script>
                let isFormModified = false;
                let isSubmitting = false; // New flag to track form submission

                // Function to check if the form has been modified
                function checkFormModification() {
                    const form = document.getElementById('abstract-form');
                    const inputs = form.querySelectorAll('input, textarea, select');

                    inputs.forEach(input => {
                        input.addEventListener('input', () => {
                            isFormModified = true;
                        });
                    });
                }

                // Add beforeunload event listener
                window.addEventListener('beforeunload', (event) => {
                    if (isFormModified && !isSubmitting) {
                        event.preventDefault();
                        event.returnValue = ''; // Required for Chrome
                        return 'You have unsaved changes. Are you sure you want to leave?'; // For older browsers
                    }
                });

                // Reset isFormModified and set isSubmitting on form submission
                document.getElementById('abstract-form').addEventListener('submit', () => {
                    isFormModified = false;
                    isSubmitting = true; // Prevents the beforeunload event from firing during form submission
                });

                // Call the function to check form modification
                checkFormModification();

                // JavaScript to handle the dynamic addition of co-author fields (name + membership ID)
                document.getElementById('add-co-author').addEventListener('click', function () {
                    var wrapper = document.getElementById('co-authors-wrapper');
                    var newCoAuthor = document.createElement('div');
                    newCoAuthor.classList.add('co-author-fields');
                    newCoAuthor.innerHTML = '<input type="text" name="co_authors[]" placeholder="Enter co-author name"> ' +
                        '<input type="text" name="co_authors_membership_id[]" placeholder="Enter co-author membership ID">';
                    wrapper.appendChild(newCoAuthor);
                });
            </script>
            <style>
                /* Form Styling */
                #abstract-form {
                    background-color: #f9f9f9;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                    max-width: 1200px;
                    margin: 0 auto;
                }

                #abstract-form label {
                    display: block;
                    font-size: 16px;
                    font-weight: 600;
                    margin-bottom: 8px;
                    margin-top: 20px;
                }

                #abstract-form input[type="text"],
                #abstract-form input[type="email"],
                #abstract-form input[type="hidden"],
                #abstract-form select,
                #abstract-form textarea {
                    width: 100%;
                    padding: 10px;
                    margin-bottom: 12px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    font-size: 14px;
                    box-sizing: border-box;
                }

                #abstract-form input[type="text"]:focus,
                #abstract-form input[type="email"]:focus,
                #abstract-form select:focus,
                #abstract-form textarea:focus {
                    outline: none;
                    border-color: rgb(227, 100, 20);
                }

                #abstract-form textarea {
                    height: 150px;
                    resize: vertical;
                }

                #add-co-author {
                    background-color: rgb(227, 100, 20);
                    color: white;
                    padding: 8px 16px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 16px;
                    transition: background-color 0.3s ease;
                }

                #add-co-author:hover {
                    background-color: rgb(187, 80, 20);
                }

                /* Buttons Styling */
                .listing-buttons {
                    display: flex;
                    justify-content: center;
                    gap: 15px;
                    margin-top: 20px;
                }

                .view-abstracts-button {
                    background-color: rgb(227, 100, 20);
                    color: white !important;
                    padding: 12px 25px;
                    text-decoration: none;
                    border-radius: 5px;
                    font-size: 16px;
                    font-weight: bold;
                    transition: background-color 0.3s ease, transform 0.2s ease;
                }

                .view-abstracts-button:hover {
                    background-color: #FB8B24 !important;
                    transform: scale(1.05);
                }

                .view-abstracts-button:active {
                    background-color: rgb(147, 60, 20);
                }

                /* Co-Authors Fields Styling */
                .co-author-fields {
                    display: flex;
                    gap: 10px;
                    margin-bottom: 15px;
                }

                .co-author-fields input[type="text"] {
                    width: 48%;
                }

                .co-authors-wrapper {
                    margin-bottom: 20px;
                }
            </style>
            <?php
            return ob_get_clean();
    } else {
        return "<p>You must be logged in and have a valid membership to submit an abstract.</p>";
    }
}
add_shortcode('abstract_submission_form', 'abstract_submission_form');

function create_abstract_submission_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "abstract_submissions";

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        author_id BIGINT(20) UNSIGNED NOT NULL,  -- Using BIGINT for user IDs   
        author_name VARCHAR(255) NOT NULL,
        co_authors TEXT NULL,
         co_authors_ids TEXT NULL,
        abstract_title VARCHAR(255) NOT NULL,
        abstract_description TEXT NOT NULL,
                rc_edit_status VARCHAR(50) NULL,  -- New column added after abstract_description
         abstract_title_initial VARCHAR(255) NULL,  -- New column for initial title
        abstract_description_initial TEXT NULL,  -- New column for initial description
        membership_selection VARCHAR(255) NOT NULL,
        reviewer_status VARCHAR(50) NOT NULL DEFAULT 'Pending for review',
        secretary_status VARCHAR(50) NOT NULL DEFAULT 'Pending for review',
        downloadable_abstract VARCHAR(255) NULL,
           certificate_unique_id VARCHAR(100) NULL UNIQUE,
        certificate_issued VARCHAR(255) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Run the function on theme activation
add_action('init', 'create_abstract_submission_table');
function create_certificates_table()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'co_author_certificates'; // Define table name with prefix

    $charset_collate = $wpdb->get_charset_collate();

    // SQL query to create the table
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        membership_id VARCHAR(255) NOT NULL,
        abstract_id VARCHAR(255) NOT NULL,
        status VARCHAR(100) NOT NULL DEFAULT 'pending',
        certificate_link VARCHAR(255) NULL,
        certificate_id VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY membership_id (membership_id) -- Ensure no duplicate membership IDs
    ) $charset_collate;";

    // Execute the query
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('init', 'create_certificates_table');

function get_user_id_by_membership_brought_id($membership_brought_id)
{
    global $wpdb;

    // Query the table to get the user_id based on membership_brought_id
    $query = $wpdb->prepare("
        SELECT user_id 
        FROM {$wpdb->prefix}membership_user
        WHERE membership_brought_id = %s
    ", $membership_brought_id);

    // Log the SQL query
    error_log("🔍 SQL Query: " . $query);

    // Execute the query
    $user_id = $wpdb->get_var($query);

    // Log the result
    error_log("🔍 Result for Membership ID {$membership_brought_id}: " . ($user_id ?: 'Not Found'));

    // Return the user_id if found, or null if not
    return $user_id ? $user_id : null;
}

function handle_abstract_submission()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_abstract'])) {
        // Log the start of submission
        error_log('Submission started');

        // Sanitize and validate input data
        $author_name = sanitize_text_field($_POST['author_name']);
        $author_email = sanitize_email($_POST['author_email']);
        $author_id = sanitize_text_field($_POST['author_id']);
        $co_authors = array_map('sanitize_text_field', $_POST['co_authors']);
        $co_author_ids = array_map('sanitize_text_field', $_POST['co_authors_membership_id']);
        $abstract_title = sanitize_text_field($_POST['abstract_title']);
        $abstract_description = sanitize_textarea_field($_POST['abstract_description']);
        $membership_selection = sanitize_text_field($_POST['membership_selection']);
        $abstract_title_initial = $abstract_title; // Initial version before any changes
        $abstract_description_initial = $abstract_description; // Initial version before any changes

        // Prepare data for logging
        $submitted_data = array(
            'author_name' => $author_name,
            'co_authors' => $co_authors,
            'co_authors_ids' => $co_author_ids,
            'abstract_title' => $abstract_title,
            'abstract_description' => $abstract_description,
            'membership_selection' => $membership_selection,
        );

        // Log the submitted data
        error_log("Submitted data start from below");
        error_log(print_r($submitted_data, true));

        // Check for file uploads (abstract file and certificate file)
        $abstract_file_url = '';
        $certificate_file_url = '';

        // Handle abstract file upload
        if (isset($_FILES['abstract_file']) && !empty($_FILES['abstract_file']['name'])) {
            $uploaded_abstract = wp_handle_upload($_FILES['abstract_file'], ['test_form' => false]);
            if (!isset($uploaded_abstract['error'])) {
                $abstract_file_url = $uploaded_abstract['url']; // Get the URL of the abstract file
            }
        }

        // Handle certificate file upload
        if (isset($_FILES['certificate_file']) && !empty($_FILES['certificate_file']['name'])) {
            $uploaded_certificate = wp_handle_upload($_FILES['certificate_file'], ['test_form' => false]);
            if (!isset($uploaded_certificate['error'])) {
                $certificate_file_url = $uploaded_certificate['url']; // Get the URL of the certificate file
            }
        }

        // Insert data into the database
        global $wpdb;
        $table_name = $wpdb->prefix . "abstract_submissions"; // Your custom table name

        // Prepare the data to be inserted
        $data = array(
            'author_id' => $author_id,
            'author_name' => $author_name,
            'co_authors' => implode(", ", $co_authors),
            'co_authors_ids' => implode(", ", $co_author_ids),
            'abstract_title' => $abstract_title,
            'abstract_description' => $abstract_description,
            'abstract_title_initial' => $abstract_title_initial, // Insert the initial abstract title
            'abstract_description_initial' => $abstract_description_initial,
            'membership_selection' => $membership_selection,
            'downloadable_abstract' => esc_url($abstract_file_url), // URL of the abstract file
            'certificate_issued' => esc_url($certificate_file_url), // URL of the certificate file
            'reviewer_status' => 'pending for review', // Default reviewer status
            'secretary_status' => 'pending for review', // Default secretary status
        );

        // Insert the data into the database
        $wpdb->insert($table_name, $data);
        $abstract_id = $wpdb->insert_id;
        $co_author_ids = array_map('sanitize_text_field', $_POST['co_authors_membership_id']);

        // Log the sanitized co-author IDs
        // error_log("🔍 Co-Author IDs: " . print_r($co_author_ids, true));

        $co_authors_group_check_results = array(); // Variable to store results

        foreach ($co_author_ids as $co_author_id) {
            // Get the user ID by membership_brought_id
            $co_author_uid = get_user_id_by_membership_brought_id($co_author_id);

            if ($co_author_uid) {
                // Check if the co-author belongs to group ID 4
                $is_in_group_4 = check_user_has_group_4($co_author_uid);

                // Determine status
                $status = $is_in_group_4 ? 'Valid' : 'Invalid';

                // Store the result for logging
                $co_authors_group_check_results[$co_author_uid] = $status === 'valid' ? 'In Group 4' : 'Not in Group 4';

                // Insert into co_author_certificates table
                $wpdb->insert(
                    "{$wpdb->prefix}co_author_certificates",
                    [
                        'user_id' => $co_author_uid,
                        'membership_id' => $co_author_id,
                        'abstract_id' => $abstract_id,
                        'submitter_id' => $author_id,
                        'status' => $status,
                    ],
                    ['%d', '%s', '%s', '%s', '%s']
                );

                if ($wpdb->last_error) {
                    // error_log("❌ DB Insert Error for User ID $co_author_uid: " . $wpdb->last_error);
                } else {
                    // error_log("✅ Inserted into co_author_certificates: User ID $co_author_uid, Membership ID $co_author_id, Status $status");
                }
            } else {
                // error_log("❌ No user found for Co-author ID: {$co_author_id}");
            }
        }

        // Log final results
        error_log("✅ Co-Authors Group Check Results: " . print_r($co_authors_group_check_results, true));
        // Log the data insert
        error_log("Abstract submission inserted into the database.");
        $rc_convincer_mail = get_user_email_by_admin_id($membership_selection);
        if ($rc_convincer_mail) {
            $subject = 'Abstract Received from ' . $author_name;
            $message = "Hello,\n\nYou have received an abstract from $author_name. Please check your dashboard.\n\nThank you and best regards,\nInsoso Abstract System";

            // Set email headers
            $headers = array('Content-Type: text/plain; charset=UTF-8', 'From: Insoso Abstract System <no-reply@insoso.com>');

            // Send the email
            wp_mail($rc_convincer_mail, $subject, $message, $headers);
        }
        if ($author_email) {
            $subject = 'Abstract submiited';
            $message = "Hello,\n\nYour abstract has been captured and sent for approval. You will be notified via email on any update.\n\nBest regards,\nInsoso Abstract System";

            // Set email headers
            $headers = array('Content-Type: text/plain; charset=UTF-8', 'From: Insoso Abstract System <no-reply@insoso.com>');

            // Send the email
            wp_mail($author_email, $subject, $message, $headers);
        }

        wp_redirect(home_url('/submit-an-abstract/?abstract_submitted=true')); // Redirect with the 'abstract_submitted' parameter
        exit; // Make sure to call exit after wp_redirect to stop further execution
    }
}
add_action('init', 'handle_abstract_submission');
function get_user_email_by_admin_id($admin_id)
{
    // Sanitize the input
    $admin_id = sanitize_text_field($admin_id);

    // Query users with the specified admin_id
    $users = get_users(array(
        'meta_key' => 'admin_id', // ACF field name
        'meta_value' => $admin_id,  // Value to match
        'number' => 1,          // Limit to 1 user (assuming admin_id is unique)
    ));

    // Check if a user was found
    if (!empty($users)) {
        // Return the first user's email
        return $users[0]->user_email;
    }

    // Return false if no user was found
    return false;
}
//abstratc list page 
function display_user_abstract_details()
{
    // Get the current logged-in user ID
    $current_user_id = get_current_user_id();

    if ($current_user_id == 0) {
        return 'You need to be logged in to view your abstract details.';
    }

    global $wpdb;

    // Query to fetch all abstracts where author_id matches the current user ID
    $table_name = $wpdb->prefix . 'abstract_submissions';
    $abstracts = $wpdb->get_results($wpdb->prepare(
        "SELECT id, author_name, co_authors, abstract_title, abstract_description,rc_edit_status, reviewer_status, secretary_status, certificate_issued 
         FROM $table_name WHERE author_id = %d",
        $current_user_id
    ));

    // If abstracts exist for the user
    if ($abstracts) {
        // Start the table structure
        $author_name = $abstracts[0]->author_name;

        // Start the table structure
        $output = "<h3>Abstract Details of {$author_name}</h3>";

        $output .= '<table border="1" cellpadding="10" cellspacing="0" style="width: 100%; margin-bottom: 20px;">';
        $output .= '<thead>
                        <tr>
                              <th>Abstract Title</th>
                            <th>Co-Authors</th>                          
                            <th>Abstract Text</th>
                            <th>RC Approval?</th>
                            <th>Certificate Issued by Secratery?</th>
                            <th>Certificate</th>
                            <th> Download Abstract </th>
                            <th> Initial Version </th>
                            
                            </tr>
                    </thead>';
        $output .= '<tbody>';

        // Loop through each abstract and output the details in a table row
        // Loop through each abstract and output the details in a table row
        foreach ($abstracts as $abstract) {
            $output .= '<tr>';
            // $output .= '<td>' . esc_html($abstract->author_name) . '</td>';
            $output .= '<td>' . esc_html($abstract->abstract_title) . '</td>';
            $output .= '<td>' . esc_html($abstract->co_authors) . '</td>';
            // $output .= '<td>' . esc_html($abstract->abstract_title) . '</td>';
            $output .= '<td>' . esc_html($abstract->abstract_description) . '</td>';
            $reviewer_status_text = ($abstract->reviewer_status === 'Edited') ? 'Edited & Pending' : esc_html($abstract->reviewer_status);
            $output .= '<td>' . $reviewer_status_text . '</td>';
            $output .= '<td>' . esc_html($abstract->secretary_status) . '</td>';

            // Check if certificate is issued
            if (!empty($abstract->certificate_issued)) {
                $upload_dir = wp_upload_dir();
                $certificate_url = esc_url($upload_dir['baseurl'] . '/certificates/abstract_' . $abstract->id . '_certificate.pdf');
                $output .= '<td><a href="' . esc_url($certificate_url) . '" download class="button download-certificate">Download Certificate</a></td>';
            } else {
                $output .= '<td>No certificate issued yet</td>';
            }
            // Add the 'Download Abstract' button (Word file)
            $output .= '<td><a href="' . esc_url(add_query_arg(['download_abstract' => $abstract->id], get_permalink())) . '" class="button download-certificate">Download Abstract</a></td>';
            if ($abstract->rc_edit_status === 'true') {
                $compare_url = esc_url(add_query_arg(['abstract_id' => $abstract->id], site_url('/compare-abstracts/')));
                $output .= '<td><a href="' . $compare_url . '" class="button compare-abstracts">Edited <span>Compare Versions</span></a></td>';
                // $output .= '<td><a href="' . $compare_url . '" class="button compare-abstracts">Compare Abstracts</a></td>';
            } else {
                $output .= '<td>' .
                    ($abstract->reviewer_status == 'Approved' ? 'AS IS' :
                        ($abstract->reviewer_status == 'pending for review' ? 'Pending' : 'No Action has been taken'))
                    . '</td>';

            }

            $output .= '</tr>';
        }

        $output .= '</tbody>';
        $output .= '</table>';

    } else {
        $output = 'No abstracts found for the current user.';
    }

    return $output;
}


// Register the shortcode [user_abstract_details]
add_shortcode('user_abstract_details', 'display_user_abstract_details');
function compare_abstract_shortcode()
{
    global $wpdb;

    // Get abstract ID from the URL
    $abstract_id = isset($_GET['abstract_id']) ? intval($_GET['abstract_id']) : 0;

    if ($abstract_id === 0) {
        return '<p>Invalid abstract ID.</p>';
    }

    // Fetch abstract details from the database
    $table_name = $wpdb->prefix . 'abstract_submissions';
    $abstract = $wpdb->get_row(
        $wpdb->prepare("SELECT abstract_title, abstract_description, abstract_title_initial, abstract_description_initial FROM $table_name WHERE id = %d", $abstract_id)
    );

    if (!$abstract) {
        return '<p>Abstract not found.</p>';
    }

    ob_start();
    ?>

        <h2 style="text-align: center;">Compare Your Abstract</h2>


        <div class="abstract-comparison">

            <div class="abstract-column">
                <h3>Your Version</h3>
                <strong>Abstract Title:</strong>
                <p><?php echo esc_html($abstract->abstract_title_initial); ?></p>
                <strong>Abstract text:</strong>
                <p><?php echo nl2br(esc_html($abstract->abstract_description_initial)); ?></p>
            </div>
            <div class="abstract-column">
                <h3>Edited By RC</h3>
                <strong>Abstract Title:</strong>
                <p><?php echo esc_html($abstract->abstract_title); ?></p>
                <strong>Abstract Text:</strong>
                <p><?php echo nl2br(esc_html($abstract->abstract_description)); ?></p>
            </div>


        </div>

        <div style="text-align: center; margin-top: 20px;">
            <a href="<?php echo esc_url(site_url('/abstract-list/')); ?>" class="button btn-back"
                style="padding: 10px 20px; background-color: #0073aa; color: #fff; text-decoration: none; border-radius: 5px;">
                ⬅ Back to Abstract List
            </a>
        </div>
        <?php
        return ob_get_clean();
}

// Register the shortcode [compare_abstract]
add_shortcode('compare_abstract', 'compare_abstract_shortcode');


function handle_abstract_download()
{
    if (isset($_GET['download_abstract'])) {
        $abstract_id = intval($_GET['download_abstract']);

        // Load the abstract details from the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'abstract_submissions';
        $abstract = $wpdb->get_row($wpdb->prepare(
            "SELECT author_name, co_authors, abstract_title, abstract_description 
             FROM $table_name WHERE id = %d",
            $abstract_id
        ));

        if ($abstract) {
            // Include PHPWord library
            require_once ABSPATH . 'wp-content/vendor/autoload.php';

            // Create a new PHPWord object
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $section = $phpWord->addSection();

            // Ensure the document allows modifications
            $phpWord->getSettings()->setUpdateFields(true);

            // Title Styling
            $titleStyle = ['bold' => true, 'size' => 20, 'color' => '0000FF']; // Blue color, large text
            $section->addText("Abstract Details", $titleStyle);
            $section->addTextBreak(2); // Add some space

            // Styling for headings and content
            $headingStyle = ['bold' => true, 'size' => 14, 'color' => '333333']; // Dark gray headings
            $contentStyle = ['size' => 12, 'color' => '000000']; // Black normal text

            // Add abstract details
            $section->addText("Abstract Title:", $headingStyle);
            $section->addText($abstract->abstract_title, $contentStyle);
            $section->addTextBreak(1); // Add spacing

            $section->addText("Author Name:", $headingStyle);
            $section->addText($abstract->author_name, $contentStyle);
            $section->addTextBreak(1);

            $section->addText("Co-Authors:", $headingStyle);
            $section->addText($abstract->co_authors, $contentStyle);
            $section->addTextBreak(1);

            $section->addText("Abstract Text:", $headingStyle);
            $section->addText($abstract->abstract_description, ['size' => 12, 'color' => '000000', 'italic' => true]);
            $section->addTextBreak(2); // Extra spacing at the end

            // Define the file path
            $file_path = sys_get_temp_dir() . '/abstract_' . $abstract_id . '.docx';

            // Save the document properly using the Writer
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($file_path);

            // Clear output buffer to avoid corruption
            ob_clean();

            // Send headers for download
            header('Content-Description: File Transfer');
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename="abstract_' . $abstract_id . '.docx"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            unlink($file_path); // Delete the file after download

            exit;
        }
    }
}

// Hook to handle the download request
add_action('template_redirect', 'handle_abstract_download');
// Hook to add a custom menu page in the admin panel
function add_approver_page()
{
    // Check if the user has the 'approver' role
    if (current_user_can('approver')) {
        // Add a menu item in the admin panel
        add_menu_page(
            'Received Abstracts',          // Page Title
            'Received Abstracts',          // Menu Title
            'manage_options',              // Capability required (you can adjust this if needed)
            'received_abstracts',          // Menu slug
            'display_received_abstracts',  // Callback function to display the page content
            'dashicons-clipboard',         // Icon for the menu item
            6                              // Position in the menu
        );
    }
}
add_action('admin_menu', 'add_approver_page');

// Callback function to display the page content


function display_received_abstracts()
{
    // Get the current user's admin_id ACF field
    $current_user_id = get_current_user_id();
    $admin_id = get_field('admin_id', 'user_' . $current_user_id); // ACF field for admin_id

    if (!$admin_id) {
        echo '<p>You do not have an admin ID assigned.</p>';
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'abstract_submissions'; // Adjust if needed

    // Query abstracts where membership_selection matches the admin_id
    $sql = $wpdb->prepare(
        "SELECT id, abstract_title, author_name, co_authors, co_authors_ids, reviewer_status, secretary_status 
         FROM $table_name 
         WHERE membership_selection = %s",
        $admin_id
    );

    $results = $wpdb->get_results($sql);

    if (!empty($results)) {
        echo '<div class="wrap"><h1>Received Abstracts</h1>';
        echo '<table class="wp-list-table widefat striped posts">';
        // Table headers: note that we now have separate columns for each co-author and its certificate status.
        echo '<thead>
                <tr>
                    <th>Sr no.</th>
                    <th>Author</th>
                    <th>Title</th>
                    
                    <th>Co-Author</th>
                    <th>Co-Author Eligibility</th>
                    <th>RC Approval?</th>
                    <th>Certificate Issued by Secratery?</th>
                    <th>Action</th>
                    <th>Update</th>
                </tr>
              </thead>';
        echo '<tbody>';
        $sr_no = 1;
        foreach ($results as $result) {
            $abstract_id = $result->id;
            $abstract_title = $result->abstract_title;
            $author_name = $result->author_name;
            $co_authors = $result->co_authors;
            $co_authors_ids = $result->co_authors_ids;
            $reviewer_status = $result->reviewer_status;
            $secretary_status = $result->secretary_status;

            // Convert comma-separated strings into arrays (trim any extra spaces)
            $co_authors_array = array_map('trim', explode(',', $co_authors));
            $co_authors_ids_array = array_map('trim', explode(',', $co_authors_ids));
            $num_coauthors = count($co_authors_array);
            $pending_class = ($reviewer_status == 'Pending for review') ? 'pending-review' : '';
            $approved_class = ($reviewer_status == 'Approved') ? 'approved-review' : '';
            $rejected_class = ($reviewer_status == 'Rejected') ? 'rejected-review' : '';
            $edited_class = ($reviewer_status == 'Edited') ? 'edited-review' : '';
            $review_class = match ($reviewer_status) {
                'Approved' => 'approved-review',
                'Pending for review' => 'pending-review',
                'Edited' => 'edited-review',
                'Rejected' => 'rejected-review',
                default => '',
            };
            $secratery_class = match ($secretary_status) {
                'Approved' => 'secretary-approved',
                'Pending for review' => 'secretary-pending',
                'Edited' => 'secretary-edited',
                'Rejected' => 'secretary-rejected',
                default => '',
            };

            // If there is at least one co-author, display them in separate rows.
            if ($num_coauthors > 0) {
                // For the first co-author, output a row with all abstract details.
                // Use rowspan for abstract details if there are multiple co-authors.
                echo '<tr>';
                echo '<td rowspan="' . $num_coauthors . '">' . $sr_no++ . '</td>';
                echo '<td rowspan="' . $num_coauthors . '">' . esc_html($author_name) . '</td>';
                echo '<td rowspan="' . $num_coauthors . '">' . esc_html($abstract_title) . '</td>';


                // Get certificate status for the first co-author.
                $first_co_author = $co_authors_array[0];
                $first_co_author_id = $co_authors_ids_array[0];
                $first_status = get_co_author_certificate_status($abstract_id, $first_co_author_id);
                $class = ($first_status == 'Valid') ? 'valid-member' : 'invalid-member';
                $first_status_text = match ($first_status) {
                    'Valid' => 'Eligible for certificate',
                    'Invalid' => 'Ineligible for certificate',
                    'No record found' => 'Invalid Member',
                    default => 'Unknown Status'
                };

                echo '<td class="' . esc_attr($class) . '">' . esc_html($first_co_author) . '</td>';
                echo '<td class="' . esc_attr($class) . '">' . esc_html($first_status_text) . '</td>';

                // The reviewer, secretary, action, and update cells also get a rowspan.
                echo '<td  class="' . esc_attr($review_class) . '"rowspan="' . $num_coauthors . '">' . esc_html($reviewer_status) . '</td>';
                echo '<td  class="' . esc_attr($secratery_class) . '"rowspan="' . $num_coauthors . '">' . esc_html($secretary_status) . '</td>';
                echo '<td rowspan="' . $num_coauthors . '">
                        <a href="' . esc_url(admin_url('admin.php?page=view_abstract&id=' . $abstract_id)) . '" class="button download-certificate">Download Doc File</a>
                      </td>';
                echo '<td rowspan="' . $num_coauthors . '">
                      <div style="display: flex; flex-direction: column; gap: 10px;">
        <a href="' . esc_url(admin_url('admin-post.php?action=update_abstract_status&status=pending&id=' . $abstract_id . '&redirect_to=view_abstract')) . '" class="button ' . esc_attr($pending_class) . '">Pending Review</a>
        
        <a href="' . esc_url(admin_url('admin-post.php?action=update_abstract_status&status=approve&id=' . $abstract_id . '&redirect_to=view_abstract')) . '" class="button ' . esc_attr($approved_class) . '">Approve</a>
        
        <a href="' . esc_url(admin_url('admin-post.php?action=update_abstract_status&status=reject&id=' . $abstract_id . '&redirect_to=view_abstract')) . '" class="button ' . esc_attr($rejected_class) . '">Reject</a>
        
        <a href="' . esc_url(admin_url('admin-post.php?action=update_abstract_status&status=edit&id=' . $abstract_id . '&redirect_to=view_abstract')) . '" class="button ' . esc_attr($edited_class) . '">Edit</a>
    </div>
                      </td>';
                echo '</tr>';

                // If there are additional co-authors, output a new row for each.
                if ($num_coauthors > 1) {
                    for ($i = 1; $i < $num_coauthors; $i++) {
                        $co_author = $co_authors_array[$i];
                        $co_author_id = $co_authors_ids_array[$i];
                        $status = get_co_author_certificate_status($abstract_id, $co_author_id);
                        $class = ($status == 'Valid') ? 'valid-member' : 'invalid-member';
                        $status_text = match ($status) {
                            'Valid' => 'Eligible for certificate',
                            'Invalid' => 'Ineligible for certificate',
                            'No record found' => 'Invalid Member',
                            default => 'Unknown Status'
                        };

                        echo '<tr>';
                        echo '<td class="' . esc_attr($class) . '">' . esc_html($co_author) . '</td>';
                        echo '<td class="' . esc_attr($class) . '">' . esc_html($status_text) . '</td>';
                        echo '</tr>';
                    }
                }
            } else {
                // If there are no co-authors, output a single row with blank co-author cells.
                echo '<tr>';
                echo '<td>' . $sr_no++ . '</td>';
                echo '<td>' . esc_html($author_name) . '</td>';
                echo '<td>' . esc_html($abstract_title) . '</td>';

                echo '<td colspan="2">No co-authors</td>';
                echo '<td class="' . esc_attr($review_class) . '">' . esc_html($reviewer_status) . '</td>';
                echo '<td class="' . esc_attr($secratery_class) . '">' . esc_html($secretary_status) . '</td>';
                echo '<td>
                        <a href="' . esc_url(admin_url('admin.php?page=view_abstract&id=' . $abstract_id)) . '" class="button download-certificate">Download Doc File</a>
                      </td>';
                echo '<td>
                      <div style="display: flex; flex-direction: column; gap: 10px;">
                          <a href="' . esc_url(admin_url('admin-post.php?action=update_abstract_status&status=pending&id=' . $abstract_id . '&redirect_to=view_abstract')) . '" class="button ' . esc_attr($pending_class) . '">Pending Review</a>
                          
                          <a href="' . esc_url(admin_url('admin-post.php?action=update_abstract_status&status=approve&id=' . $abstract_id . '&redirect_to=view_abstract')) . '" class="button ' . esc_attr($approved_class) . '">Approve</a>
                          
                          <a href="' . esc_url(admin_url('admin-post.php?action=update_abstract_status&status=reject&id=' . $abstract_id . '&redirect_to=view_abstract')) . '" class="button ' . esc_attr($rejected_class) . '">Reject</a>
                          
                          <a href="' . esc_url(admin_url('admin-post.php?action=update_abstract_status&status=edit&id=' . $abstract_id . '&redirect_to=view_abstract')) . '" class="button ' . esc_attr($edited_class) . '">Edit</a>
                      </div>
                  </td>';
                echo '</tr>';
            }
            // Add a separator row after each abstract's rows.
            echo '<tr><td colspan="9" style="padding: 10px 0;"><hr></td></tr>';
        }

        echo '</tbody></table></div>';
    } else {
        echo '<p>No abstracts found for your membership selection.</p>';
    }
}

/**
 * Helper function to get the certificate status for a co-author.
 *
 * @param int    $abstract_id   The abstract ID.
 * @param string $co_author_id  The co-author membership ID.
 *
 * @return string The certificate status or a not-found message.
 */
function get_co_author_certificate_status($abstract_id, $co_author_id)
{
    global $wpdb;

    // Adjust query: use %d for abstract_id (if integer) and %s for co_author_id (if string)
    $sql = $wpdb->prepare(
        "SELECT status FROM IWEe3NY_co_author_certificates WHERE abstract_id = %d AND membership_id = %s",
        $abstract_id,
        $co_author_id
    );

    $status = $wpdb->get_var($sql);

    if ($status !== null) {
        return $status;
    } else {
        error_log("No certificate record found for co_author_id {$co_author_id} with abstract_id {$abstract_id}");
        return "No record found";
    }
}


// Add Admin Page for Viewing Abstracts
function register_view_abstract_page()
{
    add_submenu_page(
        'tools.php',            // Parent menu (you can change this to another menu slug)
        'View Abstract',        // Page title
        'View Abstract',        // Menu title
        'manage_options',       // Capability (you can change this as needed)
        'view_abstract',        // Menu slug
        'view_abstract_page'    // Callback function to display the page content
    );
}
add_action('admin_menu', 'register_view_abstract_page');
// Callback function to display the abstract details
require_once ABSPATH . 'wp-content/vendor/autoload.php';

function view_abstract_page()
{
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        // Get the abstract ID from the URL
        $abstract_id = intval($_GET['id']);

        // Get abstract details from the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'abstract_submissions'; // Adjust the table name if necessary

        // Query to get the abstract details by ID
        $sql = $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $abstract_id);
        $abstract = $wpdb->get_row($sql);

        if ($abstract) {
            // Check if edit mode is enabled
            $is_editable = isset($_GET['rc_edit']) && $_GET['rc_edit'] == 'true';

            // Handle form submission if it's in edit mode
            if (isset($_POST['update_abst'])) {
                // Sanitize and update the abstract content
                $updated_abstract_title = sanitize_text_field($_POST['abstract_title']);
                // $updated_author_name = sanitize_text_field($_POST['author_name']);
                // $updated_co_authors = sanitize_text_field($_POST['co_authors']);
                $updated_abstract_description = sanitize_textarea_field($_POST['abstract_description']);

                // Update the abstract in the database
                $wpdb->update(
                    $table_name,
                    [
                        'abstract_title' => $updated_abstract_title,
                        // 'author_name' => $updated_author_name,
                        // 'co_authors' => $updated_co_authors,
                        'abstract_description' => $updated_abstract_description,
                        'rc_edit_status' => 'true',
                    ],
                    ['id' => $abstract_id],
                    ['%s', '%s', '%s'],
                    ['%d']
                );

                // Redirect to avoid resubmission
                wp_redirect(add_query_arg('id', $abstract_id, $_SERVER['REQUEST_URI']));
                exit;
            }

            // If DOC file is requested
            if (isset($_GET['download']) && $_GET['download'] == 'doc') {
                // Load PHPWord using Composer's autoload
                require_once ABSPATH . 'wp-content/vendor/autoload.php';

                // Create a new PHPWord object
                $phpWord = new \PhpOffice\PhpWord\PhpWord();
                $section = $phpWord->addSection();

                // Ensure the document allows modifications
                $phpWord->getSettings()->setUpdateFields(true);

                // Title Styling
                $titleStyle = ['bold' => true, 'size' => 20, 'color' => '0000FF']; // Blue color, large text
                $section->addText("Abstract Details", $titleStyle);
                $section->addTextBreak(2); // Add some space

                // Styling for headings and content
                $headingStyle = ['bold' => true, 'size' => 14, 'color' => '333333']; // Dark gray headings
                $contentStyle = ['size' => 12, 'color' => '000000']; // Black normal text

                // Add abstract details
                $section->addText("Abstract Title:", $headingStyle);
                $section->addText($abstract->abstract_title, $contentStyle);
                $section->addTextBreak(1); // Add spacing

                $section->addText("Author Name:", $headingStyle);
                $section->addText($abstract->author_name, $contentStyle);
                $section->addTextBreak(1);

                $section->addText("Co-Authors:", $headingStyle);
                $section->addText($abstract->co_authors, $contentStyle);
                $section->addTextBreak(1);

                $section->addText("Abstract Text:", $headingStyle);
                $section->addText($abstract->abstract_description, ['size' => 12, 'color' => '000000', 'italic' => true]);
                $section->addTextBreak(2); // Extra spacing at the end

                // Define the file path
                $file_path = sys_get_temp_dir() . '/abstract_' . $abstract_id . '.docx';

                // Save the document properly using the Writer
                $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
                $objWriter->save($file_path);

                // Clear output buffer to avoid corruption
                ob_clean();

                // Send headers for download
                header('Content-Description: File Transfer');
                header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                header('Content-Disposition: attachment; filename="abstract_' . $abstract_id . '.docx"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file_path));
                readfile($file_path);
                unlink($file_path); // Delete the file after download

                exit;
            }

            ?>

                <div class="wrap">
                    <h1>View Abstract: <?php echo esc_html($abstract->abstract_title); ?></h1>

                    <form method="POST">
                        <p><strong>Author Name:</strong>
                            <input type="text" name="author_name" value="<?php echo esc_html($abstract->author_name); ?>" <?php echo $is_editable ? '' : 'readonly'; ?>
                                style="border: none; background: none; font-size: 16px; font-weight: normal;">
                        </p>

                        <p><strong>Co-Authors:</strong>
                            <input type="text" name="co_authors" value="<?php echo esc_html($abstract->co_authors); ?>" <?php echo $is_editable ? '' : 'readonly'; ?>
                                style="border: none; background: none; font-size: 16px; font-weight: normal;">
                        </p>

                        <p><strong>Abstract Title:</strong>
                            <input type="text" name="abstract_title" value="<?php echo esc_html($abstract->abstract_title); ?>"
                                <?php echo $is_editable ? '' : 'readonly'; ?>
                                style="border: none; background: none; font-size: 16px; font-weight: normal;">
                        </p>

                        <p><strong>Abstract Text:</strong></p>
                        <textarea name="abstract_description" <?php echo $is_editable ? '' : 'readonly'; ?>
                            style="width: 100%; height: 200px; font-size: 16px; font-weight: normal;"><?php echo esc_html($abstract->abstract_description); ?></textarea>
                        <div class="action-btns">
                            <?php

                            if (current_user_can('secretary')) {
                                // If the current user has the "secretary" role.
                                $back_url = esc_url(site_url('/wp-admin/admin.php?page=view_available_abstracts'));
                            } else {
                                // For all other users.
                                $back_url = esc_url(site_url('/wp-admin/admin.php?page=received_abstracts'));
                            }
                            ?>

                            <p>
                                <a href="<?php echo $back_url; ?>" class="button btn-back">Back</a>
                            </p>

                            <?php if ($is_editable): ?>
                                <p>
                                    <input type="submit" class="button update-asbt" name="update_abst" value="Update">
                                </p>
                            <?php else: ?>
                                <p>
                                    <a href="<?php echo esc_url(add_query_arg('download', 'doc', $_SERVER['REQUEST_URI'])); ?>"
                                        class="button download-abst">Download as DOC</a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </form>

                </div>
                <?php
        } else {
            echo '<p>No abstract found for this ID.</p>';
        }
    } else {
        echo '<p>No abstract ID provided.</p>';
    }
}


function handle_abstract_status_update()
{
    // Check if the necessary parameters are set
    if (isset($_GET['id'], $_GET['status'])) {
        global $wpdb;

        $abstract_id = intval($_GET['id']);
        $status = sanitize_text_field($_GET['status']);  // The status passed from the button click

        // Log the ID and status for debugging


        $author_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT author_id FROM {$wpdb->prefix}abstract_submissions WHERE id = %d",
                $abstract_id
            )
        );
        $abstract_title = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT abstract_title FROM {$wpdb->prefix}abstract_submissions WHERE id = %d",
                $abstract_id
            )
        );

        // Log the author ID and abstract title


        $user_info = get_userdata($author_id);

        // Log the user info for debugging
        if ($user_info) {
            // error_log("User Email: " . $user_info->user_email);
        } else {
            // error_log("No user found with ID: " . $author_id);
        }

        // Check if user exists and email is available
        if ($user_info) {
            $user_email = $user_info->user_email;

            // Map the incoming status to the actual status to store in the database
            switch ($status) {
                case 'pending':
                    $status = 'Pending for review';
                    break;
                case 'edit':
                    $status = 'Edited';
                    break;
                case 'approve':
                    $status = 'Approved';
                    break;
                case 'reject':
                    $status = 'Rejected';
                    break;
                default:
                    $status = 'Unknown';  // In case of an invalid status
                    break;
            }

            // Prepare the email subject and message
            $subject = 'Abstract Status Update';
            $message = "
                Hello,
        
                Hope this email finds you well.
        
                There is a status update on your abstract titled: '$abstract_title'. 
                The new status is: '$status'.
        
                Best regards,
                Insoso Abstract System
            ";

            // Set the email headers
            $headers = array('Content-Type: text/html; charset=UTF-8');

            // Log the email data before sending


            // Send the email
            $mail_sent = wp_mail($user_email, $subject, nl2br($message), $headers);

            // Check if the email was sent successfully
            if ($mail_sent) {
                error_log("Email sent successfully to: " . $user_email);
            } else {
                error_log("Failed to send email to: " . $user_email);
            }
        }

        // Update the reviewer_status column based on the status
        $table_name = $wpdb->prefix . 'abstract_submissions';  // Table name
        $wpdb->update(
            $table_name,
            ['reviewer_status' => $status],  // Set reviewer_status to the mapped status
            ['id' => $abstract_id],  // Find the abstract by ID
            ['%s'],  // Format for reviewer_status
            ['%d']  // Format for id
        );

        // Redirect to the received_abstracts page after updating
        if ($status == 'Edited') {
            // Redirect to the view_abstract page if the status is 'edit'
            wp_redirect(admin_url('admin.php?page=view_abstract&id=' . $abstract_id . '&rc_edit=true'));
        } else {
            // Redirect to the received_abstracts page for other status changes
            wp_redirect(admin_url('admin.php?page=received_abstracts'));
        }
        exit;
    }
}

add_action('admin_post_update_abstract_status', 'handle_abstract_status_update');


// secratery role approval 
function create_secretary_role()
{
    // Check if the role already exists to prevent duplication
    if (!get_role('secretary')) {
        // Add the Secretary role with admin capabilities
        add_role('secretary', 'Secretary', get_role('administrator')->capabilities);
    }
}
add_action('init', 'create_secretary_role');


function add_secretary_abstracts_page()
{
    if (current_user_can('secretary') || current_user_can('administrator')) {
        add_menu_page(
            'View Available Abstracts',  // Page Title
            'Abstracts',                 // Menu Title
            'secretary',                 // Capability (Only Secretaries & Admins)
            'view_available_abstracts',  // Menu Slug
            'view_available_abstracts_callback', // Callback function
            'dashicons-media-document',  // Icon
            25                           // Position
        );
    }
}
add_action('admin_menu', 'add_secretary_abstracts_page');


function view_available_abstracts_callback()
{
    if (!current_user_can('secretary')) {
        wp_die(__('You do not have permission to access this page.'));
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'abstract_submissions'; // Adjust your table name
    $years = $wpdb->get_col("SELECT DISTINCT YEAR(created_at) as year FROM $table_name ORDER BY year DESC");
    $selected_year = isset($_GET['filter_year']) ? intval($_GET['filter_year']) : '';
    // Query abstracts where reviewer_status is 'Approved'
    $query = "SELECT id, author_id, abstract_title, author_name, co_authors, co_authors_ids, reviewer_status, certificate_issued, YEAR(created_at) as year
             FROM $table_name 
             WHERE reviewer_status = %s";

    $params = ['Approved'];

    if ($selected_year) {
        $query .= " AND YEAR(created_at) = %d";
        $params[] = $selected_year;
    }

    $abstracts = $wpdb->get_results($wpdb->prepare($query, ...$params));
    echo '<div class="secretery-heading">View Available Abstracts</div>';

    echo '<form method="GET" action="" class="abstracts-filter-form">';
    echo '<input type="hidden" name="page" value="view_available_abstracts">';
    echo '<label for="filter_year" class="filter-year-label">Filter by Year:</label>';
    echo '<select name="filter_year" id="filter_year" class="filter-year-dropdown">';
    echo '<option value="">Select Year</option>';
    foreach ($years as $year) {
        $selected = ($selected_year == $year) ? 'selected' : '';
        echo "<option value='$year' $selected>$year</option>";
    }
    echo '</select>';
    echo '<input type="submit" value="Filter" class="button button-primary filter-button">';
    echo '</form>';
    echo '<br>';


    if (!$abstracts) {
        echo '<p>No abstracts available.</p>';
    } else {
        echo '<table class="wp-list-table widefat  striped">';
        echo '<thead>
                <tr>
                 <th>Sr no.</th>
                    <th>Author</th>
                    <th>Title</th>
                    <th>Co-Author</th>
                    <th>Co-Author Certificate Eligibility</th>
                    <th>Issue Certificate (Co-Author)</th>
                    <th>Download Co-Author Certificate</th>
                    <th>Reviewer Status</th>
                    <th>Action</th>
                    <th>Download Certificate</th>
                    <th>Issue Certificate (AUTHOR)</th>
                    <th> Created Year </th>
                </tr>
              </thead>';
        echo '<tbody>';
        $sr_no = 1;
        foreach ($abstracts as $abstract) {
            $abstract_id = $abstract->id;
            $author_name = $abstract->author_name;
            $abstract_title = $abstract->abstract_title;

            $reviewer_status = $abstract->reviewer_status;
            $review_class = ($reviewer_status == 'Approved') ? 'approved-review-final' : 'pending-review';

            $certificate_issued = $abstract->certificate_issued;
            // $certificate_uid = $abstract->certificate_unique_id;
            // Process co_authors and co_authors_ids into arrays (if available)
            $co_authors_array = !empty($abstract->co_authors) ? array_map('trim', explode(',', $abstract->co_authors)) : array();
            $co_authors_ids_array = !empty($abstract->co_authors_ids) ? array_map('trim', explode(',', $abstract->co_authors_ids)) : array();
            $num_coauthors = count($co_authors_array);

            if ($num_coauthors === 0) {
                // No co-authors; display a single row with blank co-author cells.
                echo '<tr>';
                echo '<td>' . $sr_no++ . '</td>';
                echo '<td>' . esc_html($author_name) . '</td>';
                echo '<td>' . esc_html($abstract_title) . '</td>';

                echo '<td colspan="2">No co-authors</td>';
                echo '<td class="' . esc_attr($review_class) . '">' . esc_html($reviewer_status) . '</td>';
                // Action: view abstract
                echo '<td><a href="' . esc_url(admin_url('admin.php?page=view_abstract&id=' . $abstract_id)) . '" class="button view-abst">View</a></td>';
                // Download certificate cell:
                echo '<td>';
                if ($certificate_issued) {
                    $upload_dir = wp_upload_dir();
                    $certificate_url = esc_url($upload_dir['baseurl'] . '/certificates/abstract_' . $abstract_id . '_certificate.pdf');
                    echo '<a href="' . $certificate_url . '" download class="button download-certificate">Download Certificate</a>';
                } else {
                    echo '<span class="no-certificate">No certificate issued</span>';

                }
                if ($certificate_issued) {
                    echo '<span class="button certificate-issued">Certificate Issued</span>';
                } else {
                    echo '<a href="' . esc_url(admin_url('admin.php?page=view_available_abstracts&id=' . $abstract_id . '&issue_certificate=true')) . '" class="button">Issue Certificate</a>';
                }
                echo '</td>';
                echo '</tr>';
            } else {
                // There are co-authors. For the first co-author, output the main abstract details with rowspan.
                $first_co_author = $co_authors_array[0];
                $first_co_author_id = isset($co_authors_ids_array[0]) ? $co_authors_ids_array[0] : '';
                $first_cert_status = get_co_author_certificate_status($abstract_id, $first_co_author_id);
                $first_cert_text = match ($first_cert_status) {
                    'Valid' => 'Eligible for certificate',
                    'Invalid' => 'Ineligible for certificate',
                    'No record found' => 'Invalid Member',
                    default => 'Unknown Status'
                };

                $certificate_link = get_certificate_link($abstract_id, $first_co_author_id); // Function to get certificate link
                $class = ($first_cert_status == 'Valid') ? 'valid-member' : '';
                $icon = ($first_cert_status == 'Valid') ? '✔' : '❌';
                echo '<tr>';
                echo '<td rowspan="' . $num_coauthors . '">' . $sr_no++ . '</td>';
                echo '<td rowspan="' . $num_coauthors . '">' . esc_html($author_name) . '</td>';
                echo '<td rowspan="' . $num_coauthors . '">' . esc_html($abstract_title) . '</td>';

                echo '<td class="' . esc_attr($class) . '">' . esc_html($first_co_author) . '</td>';
                echo '<td class="' . esc_attr($class) . '">' . esc_html($first_cert_text) . ' ' . $icon . '</td>';

                // Issue Certificate button for co-author if cert_status is not 'valid'
                if ($first_cert_status == 'Valid') {
                    if ($certificate_link) {
                        echo '<td><span class="button certificate-issued">Certificate Issued</span></td>';
                    } else {
                        echo '<td><a href="' . esc_url(admin_url('admin.php?page=view_available_abstracts&issue_co_author_certificate=true&abstract_id=' . $abstract_id . '&co_author_id=' . $first_co_author_id)) . '" class="button valid-member">Issue Co-Author Certificate</a></td>';
                    }
                } else {
                    echo '<td class="invalid-member">This user doesnt match criteria</td>';
                }

                // Download Co-Author Certificate or "No certificate" message
                echo '<td>';
                if ($certificate_link) {
                    echo '<a href="' . esc_url($certificate_link) . '" download class="button download-certificate">Download Certificate</a>';
                } elseif ($first_cert_status == 'Valid') {
                    echo '<span class="yet-to-issue">Certificate yet to be issued.</span>';
                } else {
                    echo '<span class="no-certificate">No certificate issued</span>';
                }
                echo '</td>';

                echo '<td class="' . esc_attr($review_class) . '" rowspan="' . intval($num_coauthors) . '">' . esc_html($reviewer_status) . '</td>';
                echo '<td rowspan="' . $num_coauthors . '">
                        <a href="' . esc_url(admin_url('admin.php?page=view_abstract&id=' . $abstract_id)) . '" class="button view-abst">View</a>
                      </td>';
                echo '<td rowspan="' . $num_coauthors . '">';
                if ($certificate_issued) {
                    $upload_dir = wp_upload_dir();
                    $certificate_url = esc_url($upload_dir['baseurl'] . '/certificates/abstract_' . $abstract_id . '_certificate.pdf');
                    echo '<a href="' . $certificate_url . '" download class="button download-certificate">Download Certificate</a>';
                } else {
                    echo '<span class="yet-to-issue">Certificate yet to be issued.</span>';

                }
                echo '</td>';
                if ($certificate_issued) {
                    echo '<td rowspan="' . $num_coauthors . '">
                   <span class="button certificate-issued">Certificate Issued</span>  </td>';
                } else {
                    echo '<td rowspan="' . $num_coauthors . '">
                        <a href="' . esc_url(admin_url('admin.php?page=view_available_abstracts&id=' . $abstract_id . '&issue_certificate=true')) . '" class="button">Issue Certificate</a>
                      </td>';
                }
                $created_year = $abstract->year;
                echo '<td>' . esc_html($created_year) . '</td>';
                echo '</tr>';

                // For additional co-authors, output a row for each.
                if ($num_coauthors > 1) {
                    for ($i = 1; $i < $num_coauthors; $i++) {
                        $co_author = $co_authors_array[$i];
                        $co_author_id = isset($co_authors_ids_array[$i]) ? $co_authors_ids_array[$i] : '';
                        $cert_status = get_co_author_certificate_status($abstract_id, $co_author_id);
                        error_log($cert_status . ' cert_status');
                        $status_text = match ($cert_status) {
                            'Valid' => 'Eligible for certificate',
                            'Invalid' => 'Ineligible for certificate',
                            'No record found' => 'Invalid Member',
                            default => 'Unknown Status'
                        };

                        $certificate_link = get_certificate_link($abstract_id, $co_author_id); // Function to get certificate link
                        $class = ($cert_status == 'Valid') ? 'valid-member' : 'invalid-member';
                        $icon = ($cert_status == 'Valid') ? '✔' : '❌';
                        echo '<tr>';
                        echo '<td class="' . esc_attr($class) . '">' . esc_html($co_author) . '</td>';
                        echo '<td class="' . esc_attr($class) . '">' . esc_html($status_text) . ' ' . $icon . '</td>';


                        // Issue Certificate button for co-authors if cert_status is not 'valid'
                        if ($cert_status == 'valid') {
                            echo '<td><a href="' . esc_url(admin_url('admin.php?page=view_available_abstracts&issue_co_author_certificate=true&abstract_id=' . $abstract_id . '&co_author_id=' . $co_author_id)) . '" class="button">Issue Co-Author Certificate</a></td>';
                        } else {
                            echo '<td  class="invalid-member">This user doesnt match criteria</td>';
                        }

                        // Download Co-Author Certificate or "No certificate" message
                        echo '<td>';
                        if ($certificate_link) {
                            echo '<a href="' . esc_url($certificate_link) . '" download class="button download-certificate">Download Certificate</a>';
                        } elseif ($cert_status == 'Valid') {
                            echo '<span class="yet-to-issue">Certificate yet to be issued.</span>';
                        } else {
                            echo '<span class="no-certificate">No certificate issued</span>';

                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                }
            }
            // Add a separator row after each abstract group.
            echo '<tr><td colspan="8" style="padding: 10px 0;"><hr></td></tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }

    echo '</div>';
}

// Helper function to get the certificate link for a co-author
function get_certificate_link($abstract_id, $co_author_id)
{
    global $wpdb;
    $certificate_table = $wpdb->prefix . 'co_author_certificates';
    $certificate = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT certificate_link 
             FROM $certificate_table 
             WHERE abstract_id = %d AND membership_id = %s",
            $abstract_id,
            $co_author_id
        )
    );

    return $certificate ? $certificate->certificate_link : null;
}


function handle_certificate_issued()
{
    // Check if the issue_certificate query parameter is set and equals 'true'
    if (isset($_GET['issue_certificate']) && $_GET['issue_certificate'] == 'true') {
        global $wpdb;

        // Get the abstract ID from the query parameter
        $abstract_id = intval($_GET['id']);

        // Define table name
        $table_name = $wpdb->prefix . 'abstract_submissions';

        // Fetch the abstract details from the database
        $abstract = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $abstract_id));

        // Fetch author ID separately
        $author_id = $wpdb->get_var($wpdb->prepare("SELECT author_id FROM $table_name WHERE id = %d", $abstract_id));

        if ($abstract) {
            // Load FPDF library
            require_once ABSPATH . 'wp-content/lib/fpdf/fpdf.php';

            // Generate a unique certificate ID
            $certificate_unique_id = 'ABST' . rand(1000000, 9999999);
            while ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE certificate_unique_id = %s", $certificate_unique_id)) > 0) {
                $certificate_unique_id = 'ABST' . rand(1000000, 9999999);
            }


            // Prepare upload directory for certificates
            $upload_dir = wp_upload_dir();
            $certificates_dir = $upload_dir['basedir'] . '/certificates';
            if (!file_exists($certificates_dir)) {
                mkdir($certificates_dir, 0755, true);
            }
            $pdf_file_path = $certificates_dir . '/abstract_' . $abstract_id . '_certificate.pdf';

            // Update the database with the generated certificate ID and PDF path
            $wpdb->update(
                $table_name,
                array(
                    'certificate_issued' => $pdf_file_path, // Store file path
                    'secretary_status' => 'Approved', // Set status
                    'certificate_unique_id' => $certificate_unique_id, // Store unique certificate ID
                ),
                array('id' => $abstract_id),
                array('%s', '%s', '%s'),
                array('%d')
            );

            // 🔄 Fetch the updated abstract data
            $abstract = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $abstract_id));

            // ✅ Generate the final PDF with the certificate ID
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 10, 'Certificate ID: ' . $abstract->certificate_unique_id, 0, 1, 'C');
            $pdf->Ln(10);
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, 'Certificate of Abstract Submission', 0, 1, 'C');
            $pdf->Ln(10);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, 'Abstract Title: ' . $abstract->abstract_title, 0, 1);
            $pdf->Cell(0, 10, 'Author: ' . $abstract->author_name, 0, 1);
            $pdf->Cell(0, 10, 'Co-Authors: ' . $abstract->co_authors, 0, 1);
            $pdf->Ln(10);
            $pdf->SetFont('Arial', '', 12);
            $pdf->MultiCell(0, 10, 'Abstract Text: ' . $abstract->abstract_description, 0, 1);
            $pdf->Ln(10);
            $pdf->Output('F', $pdf_file_path);

            // ✅ Send email with the certificate as an attachment
            if ($author_id) {
                $user_info = get_userdata($author_id);
                if ($user_info) {
                    $user_email = $user_info->user_email;
                    $subject = 'Abstract Approved';
                    $message = "
                        Hello,<br><br>
                        Hope this email finds you well.<br><br>
                        Congratulations! Your abstract has been approved.<br>
                        Keep it up! We wish you all the very best for your future.<br>
                        Your approval certificate is attached below.<br><br>
                        Best regards,<br>
                        Insoso Abstract System
                    ";
                    $headers = array('Content-Type: text/html; charset=UTF-8');
                    $attachments = array($pdf_file_path);
                    wp_mail($user_email, $subject, $message, $headers, $attachments);
                }
            }
        } else {
            echo '<p>Abstract not found.</p>';
        }
    }
}

// Hook the function to the 'init' action to process the certificate generation
add_action('init', 'handle_certificate_issued');

add_action('init', 'handle_issue_co_author_certificate');
function handle_issue_co_author_certificate()
{
    global $wpdb;  // Use the global $wpdb object for database access

    // Check if the 'issue_co_author_certificate' parameter is set and both 'abstract_id' and 'co_author_id' are present
    if (
        isset($_GET['issue_co_author_certificate']) && $_GET['issue_co_author_certificate'] === 'true'
        && isset($_GET['abstract_id']) && isset($_GET['co_author_id'])
    ) {

        // Sanitize and retrieve the parameters
        $abstract_id = intval($_GET['abstract_id']);  // Ensure abstract_id is an integer
        $co_author_id = sanitize_text_field($_GET['co_author_id']);

        // Fetch the abstract details from the database
        $table_name = $wpdb->prefix . 'abstract_submissions';  // Ensure the table name is correct

        // Query to get the abstract details by abstract_id
        $abstract = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT author_name, co_authors, abstract_title, abstract_description, reviewer_status, secretary_status 
                 FROM $table_name WHERE id = %d",
                $abstract_id
            )
        );

        // Check if abstract data is found
        if ($abstract) {

            // Query to check if the certificate already exists for this abstract and co-author ID
            $certificate_table = $wpdb->prefix . 'co_author_certificates'; // Ensure the table name is correct
            $query = $wpdb->prepare(
                "SELECT id, certificate_link, certificate_id, user_id
                 FROM $certificate_table 
                 WHERE abstract_id = %s AND membership_id = %s",
                (string) $abstract_id,  // Ensure it's a string since abstract_id is varchar(255)
                $co_author_id
            );

            // Execute the query
            $existing_certificate = $wpdb->get_row($query);

            if ($existing_certificate) {
                // Certificate already exists, update or send email if necessary
                if ($existing_certificate->certificate_link) {
                    // Certificate already exists, no need to regenerate
                    echo '<div class="notice notice-success is-dismissible">';
                    echo '<p>Certificate already issued. <a href="' . esc_url($existing_certificate->certificate_link) . '" target="_blank">Download the certificate</a></p>';
                    echo '</div>';
                } else {
                    // Generate the new certificate and send email
                    generate_and_send_certificate($abstract, $co_author_id, $certificate_table, $abstract_id, $existing_certificate->id);
                }
            } else {
                // If no certificate exists, generate a new one and send email
                generate_and_send_certificate($abstract, $co_author_id, $certificate_table, $abstract_id);
            }
        } else {
            // Error: Abstract not found
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>No abstract found with ID ' . esc_html($abstract_id) . '.</p>';
            echo '</div>';
        }
    }
}

function generate_and_send_certificate($abstract, $co_author_id, $certificate_table, $abstract_id, $certificate_id = null)
{
    global $wpdb;

    // Generate a unique certificate ID
    $unique_certificate_id = uniqid('CERT_');

    // Include the FPDF library
    require_once ABSPATH . 'wp-content/lib/fpdf/fpdf.php';  // Ensure to use the correct path to fpdf.php

    // Create a new PDF document
    $pdf = new FPDF();
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('Arial', 'B', 16);

    // Title of the Certificate
    $pdf->Cell(200, 10, 'Co-Authorship Certificate', 0, 1, 'C');

    // Add a line break
    $pdf->Ln(10);

    // Set the font for content
    $pdf->SetFont('Arial', '', 12);

    // Add abstract and co-author details to the PDF
    $pdf->Cell(100, 10, 'Certificate ID: ' . esc_html($unique_certificate_id), 0, 1);
    $pdf->Cell(100, 10, 'Co-Author ID: ' . esc_html($co_author_id), 0, 1);
    $pdf->Cell(100, 10, 'Author Name: ' . esc_html($abstract->author_name), 0, 1);
    $pdf->Cell(100, 10, 'Co-Authors: ' . esc_html($abstract->co_authors), 0, 1);
    $pdf->Cell(100, 10, 'Abstract Title: ' . esc_html($abstract->abstract_title), 0, 1);
    $pdf->MultiCell(0, 10, 'Abstract Text: ' . esc_html($abstract->abstract_description), 0, 1);
    $pdf->Cell(100, 10, 'RC Approval: ' . esc_html($abstract->reviewer_status), 0, 1);
    $pdf->Cell(100, 10, 'Certificate Issued by Secratery?: ' . esc_html($abstract->secretary_status), 0, 1);

    // Add a message indicating certificate issuance
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 12);
    $pdf->Cell(200, 10, 'This certificate confirms the co-authorship of the above abstract.', 0, 1, 'C');

    // Save the PDF to the server
    $upload_dir = wp_upload_dir();  // Get the WordPress uploads directory
    $pdf_file_path = $upload_dir['path'] . '/Co-Authorship_Certificate_' . $co_author_id . '.pdf';

    // Output the PDF to a file
    $pdf->Output('F', $pdf_file_path);  // Save the file to the server

    // Prepare the download link
    $pdf_file_url = $upload_dir['url'] . '/Co-Authorship_Certificate_' . $co_author_id . '.pdf';

    // Update or insert certificate details in the database
    // After successfully updating or inserting the certificate details in the database
    // After successfully updating or inserting the certificate details in the database
    if ($certificate_id) {
        $update_result = $wpdb->update(
            $certificate_table,
            array(
                'certificate_link' => $pdf_file_url,
                'certificate_id' => $unique_certificate_id,
            ),
            array(
                'id' => $certificate_id
            )
        );

        // Check if update was successful and log the result
        if ($update_result !== false) {
            $user_id_query = $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}co_author_certificates WHERE membership_id = %s AND abstract_id = %d",
                $co_author_id,
                $abstract_id
            );

            // Execute the query and get the result
            $user_id = $wpdb->get_var($user_id_query);

            if ($user_id) {
                $user_email = get_userdata($user_id)->user_email;

                if ($user_email) {
                    // Log the success message
                    error_log('Certificate updated successfully - Certificate Link: ' . $pdf_file_url . ', User ID: ' . $user_id . ', Abstract ID: ' . $abstract_id);

                    // Prepare the email
                    $subject = 'Co-Authorship Certificate - ' . $membership_id;

                    // Plain text email content
                    $message = "Dear Co-Author,\n\n" .
                        "We are pleased to inform you that your co-authorship certificate is now available.\n\n" .
                        "Please find your certificate attached below:\n\n" .
                        "Certificate Link: " . $pdf_file_url . "\n\n" .
                        "You can download and print your certificate for your records.\n\n" .
                        "Best regards,\nThe Team";

                    // Define the headers (no HTML content here)
                    $headers = array('Content-Type: text/html; charset=UTF-8');

                    // Define the attachments (ensure the file exists)
                    $attachments = array($pdf_file_url);

                    // Send the email with the PDF attached
                    $mail_sent = wp_mail($user_email, $subject, $message, $headers, $attachments);

                    if ($mail_sent) {
                        error_log('Email sent successfully to ' . $user_email . ' with the certificate link.');
                    } else {
                        error_log('Failed to send email to ' . $user_email);
                    }

                } else {
                    // Handle case where no user email was found
                    error_log('No email found for User ID: ' . $user_id);
                }
            } else {
                // Handle case where no user_id was found
                error_log('No user found for Membership ID: ' . $membership_id . ' and Abstract ID: ' . $abstract_id);
            }

        } else {
            error_log('Failed to update certificate - User ID: ' . $co_author_id . ', Abstract ID: ' . $abstract_id);
        }

    } else {
        $insert_result = $wpdb->insert(
            $certificate_table,
            array(
                'abstract_id' => $abstract_id,
                'membership_id' => $co_author_id,
                'certificate_link' => $pdf_file_url,
                'certificate_id' => $unique_certificate_id,
                'created_at' => current_time('mysql'),
            )
        );
        $certificate_id = $wpdb->insert_id;

        // Check if insert was successful and log the result
        if ($insert_result !== false) {
            error_log('Certificate inserted successfully - Certificate Link: ' . $pdf_file_url . ', User ID: ' . $co_author_id . ', Abstract ID: ' . $abstract_id);
        } else {
            error_log('Failed to insert certificate - User ID: ' . $co_author_id . ', Abstract ID: ' . $abstract_id);
        }
    }

    // Output the success message
    echo '<div class="notice notice-success is-dismissible">';
    echo '<p>Certificate issued successfully! <a href="' . esc_url($pdf_file_url) . '" target="_blank">Download the certificate</a></p>';
    echo '</div>';

}


//// memebrship id code\\
function create_membership_user_table()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'membership_user'; // Prefix added dynamically

    // Create the table if it doesn't exist
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            membership_brought_id VARCHAR(255) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

add_action('init', 'create_membership_user_table');

//user id creation
function set_membership_brought_id_on_registration($user_id)
{
    global $wpdb;

    error_log("hook got triggered");

    // Ensure the user ID is valid
    if (!$user_id) {
        error_log('Invalid user ID: ' . $user_id);
        return;
    }

    // Log user ID for debugging
    error_log('User ID: ' . $user_id);

    // Define the custom prefix
    $prefix = 'ISS';

    // Generate the membership_brought_id
    $membership_brought_id = $prefix . $user_id;

    // Get the table name dynamically
    $membership_table = $wpdb->prefix . 'membership_user';

    // Insert the membership data into the new table
    $result = $wpdb->insert(
        $membership_table,
        array(
            'user_id' => $user_id,
            'membership_brought_id' => $membership_brought_id
        ),
        array('%d', '%s') // Format for user_id and membership_brought_id
    );

    // Check if the insert was successful
    if ($result === false) {
        error_log('Failed to insert membership_brought_id for user ID: ' . $user_id);
    } else {
        error_log('Successfully inserted membership_brought_id for user ID: ' . $user_id . ' with value: ' . $membership_brought_id);
    }
}

add_action('pmpro_after_checkout', 'set_membership_brought_id_on_registration', 10, 1);


//co-author listing page
function membership_certificate_shortcode()
{
    // Get current user ID
    $user_id = get_current_user_id();

    if (!$user_id) {
        return 'You need to be logged in to view your certificates.';
    }

    // Access the 'membership_user' table to get 'membership_brought_id'
    global $wpdb;
    $membership_brought_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT membership_brought_id FROM {$wpdb->prefix}membership_user WHERE user_id = %d",
            $user_id
        )
    );

    if (!$membership_brought_id) {
        return 'No membership found for this user.';
    }

    // Access the 'abstract_submissions' table to find matching entries
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, author_name, co_authors, co_authors_ids, abstract_title, abstract_description, reviewer_status, secretary_status
            FROM {$wpdb->prefix}abstract_submissions WHERE FIND_IN_SET(%s, co_authors_ids)",
            $membership_brought_id
        )
    );

    if (empty($results)) {
        return 'You are not part of any abstract yet.';
    }

    // Start output buffering for the table HTML
    ob_start();
    ?>
        <h2>Abstracts Where You Are a Co-Author</h2>
        <table class="membership-certificates-table" border="1" cellpadding="10" cellspacing="0"
            style="width: 100%; margin-top: 20px; border-collapse: collapse;">
            <thead>
                <tr>
                    <th>Abstract Title</th>
                    <th>Author Name</th>
                    <th>Co-Authors Name</th>
                    <th>Co-Authors</th>
                    <th>Abstract Text</th>
                    <th>RC Approval?</th>
                    <th>Certificate Issued by Secratery?</th>
                    <th>Certificate Link</th>
                    <th>Download Abstract</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td><?php echo esc_html($row->abstract_title); ?></td>
                        <td><?php echo esc_html($row->author_name); ?></td>
                        <td><?php echo esc_html($row->co_authors); ?></td>
                        <td><?php echo esc_html($row->co_authors_ids); ?></td>
                        <td><?php echo esc_html($row->abstract_description); ?></td>
                        <td><?php echo esc_html($row->reviewer_status); ?></td>
                        <td><?php echo esc_html($row->secretary_status); ?></td>

                        <?php
                        // Now get the certificate link for the abstract
                        $certificate_link = $wpdb->get_var(
                            $wpdb->prepare(
                                "SELECT certificate_link FROM {$wpdb->prefix}co_author_certificates 
                        WHERE abstract_id = %d AND user_id = %d",
                                $row->id,
                                $user_id
                            )
                        );

                        // Display certificate link if available, otherwise display a message
                        if ($certificate_link) {
                            echo '<td><a href="' . esc_url($certificate_link) . '" target="_blank"class="button download-certificate">Download Certificate</a></td>';
                        } else {
                            echo '<td>No certificate available</td>';
                        }
                        // Add the 'Download Abstract' button (Word file)
                        $abstract_download_url = esc_url(add_query_arg(['download_abstract' => $row->id], get_permalink()));
                        echo '<td><a href="' . $abstract_download_url . '"class="button download-certificate">Download Abstract</a></td>';

                        ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        // End output buffering and return the content
        $output = ob_get_clean();

        return $output;
}
function download_abstract_file()
{
    if (isset($_GET['download_abstract'])) {
        $abstract_id = intval($_GET['download_abstract']);

        // Load the abstract details from the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'abstract_submissions';
        $abstract = $wpdb->get_row($wpdb->prepare(
            "SELECT author_name, co_authors_ids, abstract_title, abstract_description 
             FROM $table_name WHERE id = %d",
            $abstract_id
        ));

        if ($abstract) {
            // Include PHPWord library
            require_once ABSPATH . 'wp-content/vendor/autoload.php';

            // Create a new PHPWord object
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $section = $phpWord->addSection();

            // Ensure the document allows modifications
            $phpWord->getSettings()->setUpdateFields(true);

            // Title Styling
            $titleStyle = ['bold' => true, 'size' => 20, 'color' => '0000FF']; // Blue color, large text
            $section->addText("Abstract Details", $titleStyle);
            $section->addTextBreak(2); // Add some space

            // Styling for headings and content
            $headingStyle = ['bold' => true, 'size' => 14, 'color' => '333333']; // Dark gray headings
            $contentStyle = ['size' => 12, 'color' => '000000']; // Black normal text

            // Add abstract details
            $section->addText("Abstract Title:", $headingStyle);
            $section->addText($abstract->abstract_title, $contentStyle);
            $section->addTextBreak(1); // Add spacing

            $section->addText("Author Name:", $headingStyle);
            $section->addText($abstract->author_name, $contentStyle);
            $section->addTextBreak(1);

            $section->addText("Co-Authors:", $headingStyle);
            $section->addText($abstract->co_authors, $contentStyle);
            $section->addTextBreak(1);

            $section->addText("Abstract Text:", $headingStyle);
            $section->addText($abstract->abstract_description, ['size' => 12, 'color' => '000000', 'italic' => true]);
            $section->addTextBreak(2); // Extra spacing at the end

            // Define the file path
            $file_path = sys_get_temp_dir() . '/abstract_' . $abstract_id . '.docx';

            // Save the document properly using the Writer
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($file_path);

            // Clear output buffer to avoid corruption
            ob_clean();

            // Send headers for download
            header('Content-Description: File Transfer');
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename="abstract_' . $abstract_id . '.docx"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            unlink($file_path); // Delete the file after download

            exit;
        }
    }
}

// Hook to handle the download request
add_action('template_redirect', 'download_abstract_file');
add_shortcode('membership_certificate', 'membership_certificate_shortcode');


function custom_redirect_after_logout()
{
    wp_redirect(home_url('/login-register/')); // Redirect to /login-register/
    exit();
}
add_action('wp_logout', 'custom_redirect_after_logout');


//Preview flow changes here 
add_action('wp_ajax_submit_abstract_data', 'submit_abstract_data_handler');
add_action('wp_ajax_nopriv_submit_abstract_data', 'submit_abstract_data_handler'); // Allow non-logged-in users

function submit_abstract_data_handler()
{
    // Verify required fields
    if (!isset($_POST['author_name']) || !isset($_POST['abstract_title']) || !isset($_POST['abstract_description'])) {
        error_log('Missing required fields: ' . print_r($_POST, true));
        wp_send_json_error(['message' => 'Missing required fields!']);
    }

    // Sanitize input data
    global $wpdb;
    $author_name = sanitize_text_field($_POST['author_name']);
    $author_email = sanitize_email($_POST['author_email']);
    $author_id = sanitize_text_field($_POST['author_id']);
    $co_authors_membership_id = array_map('sanitize_text_field', $_POST['co_authors_membership_id'] ?? []);
    $co_authors_names = array_map('sanitize_text_field', $_POST['co_authors_names'] ?? []); // Capture co-author names
    $abstract_title = sanitize_text_field($_POST['abstract_title']);
    $abstract_description = sanitize_textarea_field($_POST['abstract_description']);
    $membership_selection = sanitize_text_field($_POST['membership_selection']);

    // Log the received data
    error_log('Abstract Submission Data: ' . print_r([
        'author_name' => $author_name,
        'author_email' => $author_email,
        'author_id' => $author_id,
        'co_authors_membership_id' => $co_authors_membership_id,
        'co_authors_names' => $co_authors_names, // Log co-author names
        'abstract_title' => $abstract_title,
        'abstract_description' => $abstract_description,
        'membership_selection' => $membership_selection
    ], true));

    $co_authors_status = [];
    $co_authors_satisfied_count = 0;

    foreach ($co_authors_membership_id as $index => $membership_brought_id) {
        $co_author_name = $co_authors_names[$index] ?? "Unknown"; // Assign co-author name
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}membership_user WHERE membership_brought_id = %s",
            $membership_brought_id
        ));

        if ($user_id) {
            error_log("User ID for membership_brought_id {$membership_brought_id}: {$user_id}");

            // Check conditions
            $coauthor_paid = has_user_made_successful_payment($user_id);
            $coauthor_in_group_4 = check_user_has_group_4($user_id);
            $coauthor_memberships = $wpdb->get_col($wpdb->prepare(
                "SELECT membership_id FROM {$wpdb->prefix}pmpro_memberships_users 
                WHERE user_id = %d AND status = 'active'",
                $user_id
            ));

            $has_same_membership = in_array($membership_selection, $coauthor_memberships);

            $co_author_status = [
                'name' => $co_author_name,
                'membership_id' => $membership_brought_id,
                'group_4' => $coauthor_in_group_4 ? 'is in Group 4' : 'is not in Group 4',
                'payment_status' => $coauthor_paid ? 'has made last payment' : 'has not made last payment',
                'membership_status' => $has_same_membership ? 'has same membership selection as current user' : 'does not have same membership selection as current user'
            ];

            error_log('Co-author Status: ' . print_r($co_author_status, true));

            if ($coauthor_paid && $coauthor_in_group_4 && $has_same_membership) {
                $co_authors_status[] = "{$co_author_status['name']} (Membership ID: {$co_author_status['membership_id']}) - {$co_author_status['group_4']}, {$co_author_status['payment_status']}, {$co_author_status['membership_status']}";
                $co_authors_satisfied_count++;
            } else {
                $missing_messages = [];
                if (!$coauthor_paid) {
                    $missing_messages[] = "Co-author has not made a successful payment.";
                }
                if (!$coauthor_in_group_4) {
                    $missing_messages[] = "Co-author is not part of Group 4.";
                }
                if (!$has_same_membership) {
                    $missing_messages[] = "Co-author does not have the same membership as the current user.";
                }

                $co_authors_status[] = "{$co_author_status['name']} (Membership ID: {$co_author_status['membership_id']}) - Missing Conditions: " . implode(', ', $missing_messages);
            }
        } else {
            error_log("No user found for membership_brought_id {$membership_brought_id}");
            $co_authors_status[] = "{$co_author_name} (Membership ID: {$membership_brought_id}) - Invalid Membership ID.";
        }
    }

    if (empty($co_authors_status)) {
        wp_send_json_error(['message' => 'No co-authors found or conditions not met.']);
    }

    error_log('Co-authors Status: ' . print_r($co_authors_status, true));

    // Return success with co-authors' names
    wp_send_json_success([
        'message' => 'Abstract data logged successfully!',
        'co_authors_status' => $co_authors_status,
        'co_authors_satisfied_count' => $co_authors_satisfied_count,
        'co_authors_names' => $co_authors_names // Include co-authors' names
    ]);
}

//
// certificate searching page
// Ensure 'secretary' role has 'view_certificates' capability
function add_secretary_role_capability()
{
    $role = get_role('secretary');
    if (!$role) {
        add_role('secretary', 'Secretary', ['read' => true]);
        $role = get_role('secretary');
    }
    if ($role) {
        $role->add_cap('view_certificates');
    }
}
add_action('init', 'add_secretary_role_capability');

// Register admin menu for certificate search
function certificate_search_admin_menu()
{
    $user = wp_get_current_user();

    // Ensure the user has the 'secretary' role
    if (in_array('secretary', (array) $user->roles)) {
        add_menu_page(
            'Author Certificate Search',
            'Author Certificates',
            'view_certificates',
            'author-certificate-search',
            'author_certificate_listing_page',
            'dashicons-search',
            25
        );

        add_submenu_page(
            'author-certificate-search',
            'Co-Author Certificate Search',
            'Co-Author Certificates',
            'view_certificates',
            'co-author-certificate-search',
            'co_author_certificate_listing_page'
        );
    }
}
add_action('admin_menu', 'certificate_search_admin_menu');

// Author Certificate Listing Page
function author_certificate_listing_page()
{
    if (!current_user_can('view_certificates')) {
        wp_die(__('You do not have permission to access this page.'));
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'abstract_submissions';
    $search_value = isset($_GET['certificate_unique_id']) ? sanitize_text_field($_GET['certificate_unique_id']) : '';

    ?>
        <div class="wrap">
            <h1>Author Certificates</h1>
            <form method="get" action="">
                <input type="hidden" name="page" value="author-certificate-search" />
                <input type="text" name="certificate_unique_id" placeholder="Enter Certificate Unique ID"
                    value="<?php echo esc_attr($search_value); ?>" />
                <input type="submit" value="Search" />
            </form>

            <?php
            $query = "SELECT id, author_name, abstract_title, certificate_issued, certificate_unique_id, created_at FROM $table_name";
            if ($search_value) {
                $query .= $wpdb->prepare(" WHERE certificate_unique_id LIKE %s", '%' . $wpdb->esc_like($search_value) . '%');
            }

            $results = $wpdb->get_results($query);

            if ($results) {
                echo '<table class="widefat fixed striped">';
                echo '<thead><tr><th>Serial No</th><th>Abstract ID</th><th>Abstract Title</th><th>Certificate Issued</th><th>Certificate Unique ID</th><th>Created At</th></tr></thead>';
                echo '<tbody>';
                $serial_no = 1;
                foreach ($results as $row) {
                    echo '<tr>';
                    echo '<td>' . esc_html($serial_no++) . '</td>';
                    echo '<td>' . esc_html($row->id) . '</td>';
                    echo '<td>' . esc_html($row->abstract_title) . '</td>';
                    $upload_dir = wp_upload_dir();
                    $certificate_url = esc_url($upload_dir['baseurl'] . '/certificates/abstract_' . $row->id . '_certificate.pdf');
                    echo '<td><a href="' . esc_url($certificate_url) . '" download class="button download-certificate">Download Certificate</a></td>';
                    echo '<td>' . esc_html($row->certificate_unique_id) . '</td>';
                    echo '<td>' . esc_html($row->created_at) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<p>No author certificates found.</p>';
            }
            if ($search_value) {
                echo '<br><a href="?page=author-certificate-search" class="button button-primary">Back to Listing</a>';
            }
            ?>

        </div>
        <?php
}

// Co-Author Certificate Listing Page
function co_author_certificate_listing_page()
{
    if (!current_user_can('view_certificates')) {
        wp_die(__('You do not have permission to access this page.'));
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'co_author_certificates';
    $search_value = isset($_GET['certificate_id']) ? sanitize_text_field($_GET['certificate_id']) : '';

    ?>
        <div class="wrap">
            <h1>Co-Author Certificates</h1>
            <form method="get" action="">
                <input type="hidden" name="page" value="co-author-certificate-search" />
                <input type="text" name="certificate_id" placeholder="Enter Certificate ID"
                    value="<?php echo esc_attr($search_value); ?>" />
                <input type="submit" value="Search" />
            </form>

            <?php
            $query = "SELECT membership_id, abstract_id, certificate_link, certificate_id, created_at, status FROM $table_name WHERE status = 'Valid'";
            if ($search_value) {
                $query .= $wpdb->prepare(" AND certificate_id LIKE %s", '%' . $wpdb->esc_like($search_value) . '%');
            }

            $results = $wpdb->get_results($query);

            if ($results) {
                echo '<table class="widefat fixed striped">';
                echo '<thead><tr><th>Serial No</th><th>Membership ID</th><th>Abstract ID</th><th>Certificate Link</th><th>Certificate ID</th><th>Created At</th></tr></thead>';
                echo '<tbody>';
                $serial_no = 1;
                foreach ($results as $row) {
                    echo '<tr>';
                    echo '<td>' . esc_html($serial_no++) . '</td>';
                    echo '<td>' . esc_html($row->membership_id) . '</td>';
                    echo '<td>' . esc_html($row->abstract_id) . '</td>';
                    echo '<td><a href="' . esc_url($row->certificate_link) . '" download class="download-certificate">Download Certificate</a></td>';
                    echo '<td>' . esc_html($row->certificate_id) . '</td>';
                    echo '<td>' . esc_html($row->created_at) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<p>No valid co-author certificates found.</p>';
            }
            if ($search_value) {
                echo '<br><a href="?page=co-author-certificate-search" class="button button-primary">Back to Listing</a>';
            }
            ?>


        </div>
        <?php
}
