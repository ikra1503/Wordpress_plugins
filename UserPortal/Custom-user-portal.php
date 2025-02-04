
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

                <label for="abstract_description">Abstract Description:</label>
                <textarea id="abstract_description" name="abstract_description" required></textarea>

                <label for="membership_selection">Select Membership:</label>
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

                <input type="submit" name="submit_abstract" value="Submit Abstract">
            </form>
            <a href="<?php echo site_url('/abstract-list/'); ?>" class="view-abstracts-button">View Your Abstracts</a>
            <a href="<?php echo site_url('/co-author-abstract-list/'); ?>" class="view-abstracts-button">View your Co-Authorship</a>

            <script>
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
                .view-abstracts-button {
                    background: #FB8B25;
                    color: #fff;
                    border: none;
                    border-radius: 0px;
                    font-size: 16px;
                    font-family: oswald !important;
                }

                .view-abstracts-button:hover {
                    background: #fff;
                    color: #FB8B25;
                }

                #co-authors-wrapper {
                    margin-bottom: 10px;
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                }

                .co-author-fields {
                    display: flex;
                    gap: 10px;
                }

                .co-author-fields input {
                    width: 48%;
                    /* Makes each input field 50% width */
                    box-sizing: border-box;
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

        wp_redirect(home_url()); // You can change this URL to a thank you page or confirmation page
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
        "SELECT id, author_name, co_authors, abstract_title, abstract_description, reviewer_status, secretary_status, certificate_issued 
         FROM $table_name WHERE author_id = %d",
        $current_user_id
    ));

    // If abstracts exist for the user
    if ($abstracts) {
        // Start the table structure
        $output = '<h3>Your Abstract Details</h3>';
        $output .= '<table border="1" cellpadding="10" cellspacing="0" style="width: 100%; margin-bottom: 20px;">';
        $output .= '<thead>
                        <tr>
                            <th>Author Name</th>
                            <th>Co-Authors</th>
                            <th>Abstract Title</th>
                            <th>Abstract Description</th>
                            <th>Reviewer Status</th>
                            <th>Secretary Status</th>
                            <th>Certificate</th>
                        </tr>
                    </thead>';
        $output .= '<tbody>';

        // Loop through each abstract and output the details in a table row
        // Loop through each abstract and output the details in a table row
        foreach ($abstracts as $abstract) {
            $output .= '<tr>';
            $output .= '<td>' . esc_html($abstract->author_name) . '</td>';
            $output .= '<td>' . esc_html($abstract->co_authors) . '</td>';
            $output .= '<td>' . esc_html($abstract->abstract_title) . '</td>';
            $output .= '<td>' . esc_html($abstract->abstract_description) . '</td>';
            $output .= '<td>' . esc_html($abstract->reviewer_status) . '</td>';
            $output .= '<td>' . esc_html($abstract->secretary_status) . '</td>';

            // Check if certificate is issued
            if (!empty($abstract->certificate_issued)) {
                $upload_dir = wp_upload_dir();
                $certificate_url = esc_url($upload_dir['baseurl'] . '/certificates/abstract_' . $abstract->id . '_certificate.pdf');
                $output .= '<td><a href="' . esc_url($certificate_url) . '" download class="button">Download Certificate</a></td>';
            } else {
                $output .= '<td>No certificate issued yet</td>';
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
                    <th>Abstract ID</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Co-Author</th>
                    <th>Co-Author Certificate Status</th>
                    <th>Reviewer Status</th>
                    <th>Secretary Status</th>
                    <th>Action</th>
                    <th>Update</th>
                </tr>
              </thead>';
        echo '<tbody>';

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

            // If there is at least one co-author, display them in separate rows.
            if ($num_coauthors > 0) {
                // For the first co-author, output a row with all abstract details.
                // Use rowspan for abstract details if there are multiple co-authors.
                echo '<tr>';
                echo '<td rowspan="' . $num_coauthors . '">' . esc_html($abstract_id) . '</td>';
                echo '<td rowspan="' . $num_coauthors . '">' . esc_html($abstract_title) . '</td>';
                echo '<td rowspan="' . $num_coauthors . '">' . esc_html($author_name) . '</td>';

                // Get certificate status for the first co-author.
                $first_co_author = $co_authors_array[0];
                $first_co_author_id = $co_authors_ids_array[0];
                $first_status = get_co_author_certificate_status($abstract_id, $first_co_author_id);

                echo '<td>' . esc_html($first_co_author) . '</td>';
                echo '<td>' . esc_html($first_status) . '</td>';

                // The reviewer, secretary, action, and update cells also get a rowspan.
                echo '<td rowspan="' . $num_coauthors . '">' . esc_html($reviewer_status) . '</td>';
                echo '<td rowspan="' . $num_coauthors . '">' . esc_html($secretary_status) . '</td>';
                echo '<td rowspan="' . $num_coauthors . '">
                        <a href="' . esc_url(admin_url('admin.php?page=view_abstract&id=' . $abstract_id)) . '" class="button">Download Doc File</a>
                      </td>';
                echo '<td rowspan="' . $num_coauthors . '">
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <a href="' . esc_url(admin_url('admin-post.php?action=update_abstract_status&status=pending&id=' . $abstract_id . '&redirect_to=view_abstract')) . '" class="button">Pending Review</a>
                            <a href="' . esc_url(admin_url('admin-post.php?action=update_abstract_status&status=approve&id=' . $abstract_id . '&redirect_to=view_abstract')) . '" class="button">Approve</a>
                            <a href="' . esc_url(admin_url('admin-post.php?action=update_abstract_status&status=reject&id=' . $abstract_id . '&redirect_to=view_abstract')) . '" class="button">Reject</a>
                            <a href="' . esc_url(admin_url('admin-post.php?action=update_abstract_status&status=edit&id=' . $abstract_id . '&redirect_to=view_abstract')) . '" class="button">Edit</a>
                        </div>
                      </td>';
                echo '</tr>';

                // If there are additional co-authors, output a new row for each.
                if ($num_coauthors > 1) {
                    for ($i = 1; $i < $num_coauthors; $i++) {
                        $co_author = $co_authors_array[$i];
                        $co_author_id = $co_authors_ids_array[$i];
                        $status = get_co_author_certificate_status($abstract_id, $co_author_id);

                        echo '<tr>';
                        echo '<td>' . esc_html($co_author) . '</td>';
                        echo '<td>' . esc_html($status) . '</td>';
                        echo '</tr>';
                    }
                }
            } else {
                // If there are no co-authors, output a single row with blank co-author cells.
                echo '<tr>';
                echo '<td>' . esc_html($abstract_id) . '</td>';
                echo '<td>' . esc_html($abstract_title) . '</td>';
                echo '<td>' . esc_html($author_name) . '</td>';
                echo '<td colspan="2">No co-authors</td>';
                echo '<td>' . esc_html($reviewer_status) . '</td>';
                echo '<td>' . esc_html($secretary_status) . '</td>';
                echo '<td>
                        <a href="' . esc_url(admin_url('admin.php?page=view_abstract&id=' . $abstract_id)) . '" class="button">Download Doc File</a>
                      </td>';
                echo '<td>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <a href="' . esc_url(admin_url('admin-post.php?action=update_abstract_status&status=pending&id=' . $abstract_id . '&redirect_to=view_abstract')) . '" class="button">Pending Review</a>
                            <a href="' . esc_url(admin_url('admin-post.php?action=update_abstract_status&status=approve&id=' . $abstract_id . '&redirect_to=view_abstract')) . '" class="button">Approve</a>
                            <a href="' . esc_url(admin_url('admin-post.php?action=update_abstract_status&status=reject&id=' . $abstract_id . '&redirect_to=view_abstract')) . '" class="button">Reject</a>
                            <a href="' . esc_url(admin_url('admin-post.php?action=update_abstract_status&status=edit&id=' . $abstract_id . '&redirect_to=view_abstract')) . '" class="button">Edit</a>
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

                $section->addText("Abstract Description:", $headingStyle);
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

                    <p><strong>Author Name:</strong>
                        <input type="text" value="<?php echo esc_html($abstract->author_name); ?>" readonly
                            style="border: none; background: none; font-size: 16px; font-weight: normal;">
                    </p>

                    <p><strong>Co-Authors:</strong>
                        <input type="text" value="<?php echo esc_html($abstract->co_authors); ?>" readonly
                            style="border: none; background: none; font-size: 16px; font-weight: normal;">
                    </p>

                    <p><strong>Abstract Title:</strong>
                        <input type="text" value="<?php echo esc_html($abstract->abstract_title); ?>" readonly
                            style="border: none; background: none; font-size: 16px; font-weight: normal;">
                    </p>

                    <p><strong>Abstract Description:</strong></p>
                    <textarea readonly
                        style="width: 100%; height: 200px; font-size: 16px; font-weight: normal;"><?php echo esc_html($abstract->abstract_description); ?></textarea>

                    <p>
                        <a href="<?php echo esc_url(add_query_arg('download', 'doc', $_SERVER['REQUEST_URI'])); ?>"
                            class="button">Download as DOC</a>
                    </p>
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

        $user_info = get_userdata($author_id);

        // Check if user exists and email is available


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
        if ($user_info) {
            $user_email = $user_info->user_email;

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

            // Send the email
            wp_mail($user_email, $subject, nl2br($message), $headers);
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
        wp_redirect(admin_url('admin.php?page=received_abstracts'));
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

    // Query abstracts where reviewer_status is 'Approved'
    $abstracts = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, author_id, abstract_title, author_name, co_authors, co_authors_ids, reviewer_status, certificate_issued 
             FROM $table_name 
             WHERE reviewer_status = %s",
            'Approved'
        )
    );

    echo '<div class="wrap">';
    echo '<h1>Available Abstracts</h1>';

    if (!$abstracts) {
        echo '<p>No abstracts available.</p>';
    } else {
        echo '<table class="wp-list-table widefat  striped">';
        echo '<thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Co-Author</th>
                    <th>Co-Author Certificate Status</th>
                    <th>Issue Certificate (Co-Author)</th>
                    <th>Download Co-Author Certificate</th>
                    <th>Reviewer Status</th>
                    <th>Action</th>
                    <th>Download Certificate</th>
                    <th>Issue Certificate (AUTHOR)</th>
                </tr>
              </thead>';
        echo '<tbody>';

        foreach ($abstracts as $abstract) {
            $abstract_id = $abstract->id;
            $abstract_title = $abstract->abstract_title;
            $author_name = $abstract->author_name;
            $reviewer_status = $abstract->reviewer_status;
            $certificate_issued = $abstract->certificate_issued;

            // Process co_authors and co_authors_ids into arrays (if available)
            $co_authors_array = !empty($abstract->co_authors) ? array_map('trim', explode(',', $abstract->co_authors)) : array();
            $co_authors_ids_array = !empty($abstract->co_authors_ids) ? array_map('trim', explode(',', $abstract->co_authors_ids)) : array();
            $num_coauthors = count($co_authors_array);

            if ($num_coauthors === 0) {
                // No co-authors; display a single row with blank co-author cells.
                echo '<tr>';
                echo '<td>' . esc_html($abstract_title) . '</td>';
                echo '<td>' . esc_html($author_name) . '</td>';
                echo '<td colspan="2">No co-authors</td>';
                echo '<td>' . esc_html($reviewer_status) . '</td>';
                // Action: view abstract
                echo '<td><a href="' . esc_url(admin_url('admin.php?page=view_abstract&id=' . $abstract_id)) . '" class="button">View</a></td>';
                // Download certificate cell:
                echo '<td>';
                if ($certificate_issued) {
                    $upload_dir = wp_upload_dir();
                    $certificate_url = esc_url($upload_dir['baseurl'] . '/certificates/abstract_' . $abstract_id . '_certificate.pdf');
                    echo '<a href="' . $certificate_url . '" download class="button">Download Certificate</a>';
                } else {
                    echo 'No certificate issued';
                }
                echo '</td>';
                // Issue Certificate cell:
                echo '<td><a href="' . esc_url(admin_url('admin.php?page=view_available_abstracts&id=' . $abstract_id . '&issue_certificate=true')) . '" class="button">Issue Certificate</a></td>';
                echo '</tr>';
            } else {
                // There are co-authors. For the first co-author, output the main abstract details with rowspan.
                $first_co_author = $co_authors_array[0];
                $first_co_author_id = isset($co_authors_ids_array[0]) ? $co_authors_ids_array[0] : '';
                $first_cert_status = get_co_author_certificate_status($abstract_id, $first_co_author_id);
                $certificate_link = get_certificate_link($abstract_id, $first_co_author_id); // Function to get certificate link

                echo '<tr>';
                echo '<td rowspan="' . $num_coauthors . '">' . esc_html($abstract_title) . '</td>';
                echo '<td rowspan="' . $num_coauthors . '">' . esc_html($author_name) . '</td>';
                echo '<td>' . esc_html($first_co_author) . '</td>';
                echo '<td>' . esc_html($first_cert_status) . '</td>';

                // Issue Certificate button for co-author if cert_status is not 'valid'
                if ($first_cert_status == 'Valid') {
                    echo '<td><a href="' . esc_url(admin_url('admin.php?page=view_available_abstracts&issue_co_author_certificate=true&abstract_id=' . $abstract_id . '&co_author_id=' . $first_co_author_id)) . '" class="button">Issue Co-Author Certificate</a></td>';
                } else {
                    echo '<td>This user doesnt match criteria</td>';
                }

                // Download Co-Author Certificate or "No certificate" message
                echo '<td>';
                if ($certificate_link) {
                    echo '<a href="' . esc_url($certificate_link) . '" download class="button">Download Certificate</a>';
                } else {
                    echo 'No certificate issued';
                }
                echo '</td>';

                echo '<td rowspan="' . $num_coauthors . '">' . esc_html($reviewer_status) . '</td>';
                echo '<td rowspan="' . $num_coauthors . '">
                        <a href="' . esc_url(admin_url('admin.php?page=view_abstract&id=' . $abstract_id)) . '" class="button">View</a>
                      </td>';
                echo '<td rowspan="' . $num_coauthors . '">';
                if ($certificate_issued) {
                    $upload_dir = wp_upload_dir();
                    $certificate_url = esc_url($upload_dir['baseurl'] . '/certificates/abstract_' . $abstract_id . '_certificate.pdf');
                    echo '<a href="' . $certificate_url . '" download class="button">Download Certificate</a>';
                } else {
                    echo 'No certificate issued';
                }
                echo '</td>';
                echo '<td rowspan="' . $num_coauthors . '">
                        <a href="' . esc_url(admin_url('admin.php?page=view_available_abstracts&id=' . $abstract_id . '&issue_certificate=true')) . '" class="button">Issue Certificate</a>
                      </td>';
                echo '</tr>';

                // For additional co-authors, output a row for each.
                if ($num_coauthors > 1) {
                    for ($i = 1; $i < $num_coauthors; $i++) {
                        $co_author = $co_authors_array[$i];
                        $co_author_id = isset($co_authors_ids_array[$i]) ? $co_authors_ids_array[$i] : '';
                        $cert_status = get_co_author_certificate_status($abstract_id, $co_author_id);
                        $certificate_link = get_certificate_link($abstract_id, $co_author_id); // Function to get certificate link

                        echo '<tr>';
                        echo '<td>' . esc_html($co_author) . '</td>';
                        echo '<td>' . esc_html($cert_status) . '</td>';

                        // Issue Certificate button for co-authors if cert_status is not 'valid'
                        if ($cert_status == 'valid') {
                            echo '<td><a href="' . esc_url(admin_url('admin.php?page=view_available_abstracts&issue_co_author_certificate=true&abstract_id=' . $abstract_id . '&co_author_id=' . $co_author_id)) . '" class="button">Issue Co-Author Certificate</a></td>';
                        } else {
                            echo '<td>This user doesnt match criteria</td>';
                        }

                        // Download Co-Author Certificate or "No certificate" message
                        echo '<td>';
                        if ($certificate_link) {
                            echo '<a href="' . esc_url($certificate_link) . '" download class="button">Download Certificate</a>';
                        } else {
                            echo 'No certificate issued';
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

        // Fetch the abstract details from the database
        $table_name = $wpdb->prefix . 'abstract_submissions';
        $abstract = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $abstract_id");
        $author_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT author_id FROM $table_name WHERE id = %d",
                $abstract_id
            )
        );
        //update mail


        if ($abstract) {
            // Load FPDF library from the specified path
            require_once ABSPATH . 'wp-content/lib/fpdf/fpdf.php'; // Adjusted to your specific path

            // Create a new FPDF instance
            $pdf = new FPDF();
            $pdf->AddPage();

            // Set title and author
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, 'Certificate of Abstract Submission', 0, 1, 'C');
            $pdf->Ln(10); // Line break

            // Add the abstract details
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, 'This is to certify that the following abstract has been submitted successfully.', 0, 1);
            $pdf->Ln(5);
            $pdf->Cell(0, 10, 'Abstract Title: ' . $abstract->abstract_title, 0, 1);
            $pdf->Cell(0, 10, 'Author: ' . $abstract->author_name, 0, 1);
            $pdf->Cell(0, 10, 'Co-Authors: ' . $abstract->co_authors, 0, 1);
            // $pdf->Cell(0, 10, 'Reviewer Status: ' . $abstract->reviewer_status, 0, 1);
            $pdf->Ln(10);

            // Output the PDF to a file
            $upload_dir = wp_upload_dir();
            $pdf_file_path = $upload_dir['basedir'] . '/certificates/abstract_' . $abstract_id . '_certificate.pdf';

            // Ensure the directory exists
            if (!file_exists($upload_dir['basedir'] . '/certificates')) {
                mkdir($upload_dir['basedir'] . '/certificates', 0755, true);
            }

            $pdf->Output('F', $pdf_file_path); // Save PDF to file
            $certificate_unique_id = 'ABST' . rand(10000, 99999);
            while ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE certificate_unique_id = %s", $certificate_unique_id)) > 0) {
                $certificate_unique_id = 'ABST' . rand(10000, 99999);
            }
            // Update the database with the PDF file path
            $wpdb->update(
                $table_name,
                array(
                    'certificate_issued' => $pdf_file_path, // Store the file path in the database
                    'secretary_status' => 'Approved', // Set the status to Approved
                    'certificate_unique_id' => $certificate_unique_id, // Store the unique certificate ID
                ),
                array('id' => $abstract_id),
                array('%s', '%s', '%s'),
                array('%d')
            );
            if ($author_id) {
                // Fetch user information using the author_id
                $user_info = get_userdata($author_id);

                if ($user_info) {
                    // Get the user email
                    $user_email = $user_info->user_email;

                    // Prepare the email subject and message
                    $subject = 'Abstract Approved';
                    $message = "
                        Hello,
            
                        Hope this email finds you well.
            
                        Congratulations!!! Your abstract has been approved. 
                        Keep it up!
                        We wish you all the very best for your future.
                        Your approval certificate is attached below.
            
                        Best regards,
                        Insoso Abstract System
                    ";

                    // Set the email headers
                    $headers = array('Content-Type: text/html; charset=UTF-8');

                    // Attach the PDF file
                    $attachments = array($pdf_file_path); // Path to the generated PDF

                    // Send the email with the attachment
                    wp_mail($user_email, $subject, nl2br($message), $headers, $attachments);
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
    $pdf->MultiCell(0, 10, 'Abstract Description: ' . esc_html($abstract->abstract_description), 0, 1);
    $pdf->Cell(100, 10, 'Reviewer Status: ' . esc_html($abstract->reviewer_status), 0, 1);
    $pdf->Cell(100, 10, 'Secretary Status: ' . esc_html($abstract->secretary_status), 0, 1);

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
function membership_certificate_shortcode() {
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
            "SELECT id, author_name, co_authors_ids, abstract_title, abstract_description, reviewer_status, secretary_status
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
    <table class="membership-certificates-table" border="1" cellpadding="10" cellspacing="0" style="width: 100%; margin-top: 20px; border-collapse: collapse;">
        <thead>
            <tr>
                <th>Abstract Title</th>
                <th>Author Name</th>
                <th>Co-Authors</th>
                <th>Abstract Description</th>
                <th>Reviewer Status</th>
                <th>Secretary Status</th>
                <th>Certificate Link</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($results as $row) : ?>
            <tr>
                <td><?php echo esc_html($row->abstract_title); ?></td>
                <td><?php echo esc_html($row->author_name); ?></td>
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
                        $row->id, $user_id
                    )
                );

                // Display certificate link if available, otherwise display a message
                if ($certificate_link) {
                    echo '<td><a href="' . esc_url($certificate_link) . '" target="_blank">Download Certificate</a></td>';
                } else {
                    echo '<td>No certificate available</td>';
                }
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

add_shortcode('membership_certificate', 'membership_certificate_shortcode');
