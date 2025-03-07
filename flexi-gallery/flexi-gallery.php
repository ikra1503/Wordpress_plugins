
///flexigallery code starts here

function create_custom_gallery_tables()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Categories Table
    $categories_table = $wpdb->prefix . 'custom_gallery_categories';
    $sql1 = "CREATE TABLE IF NOT EXISTS $categories_table (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(255) NOT NULL
    ) $charset_collate;";

    // Subcategories Table
    $subcategories_table = $wpdb->prefix . 'custom_gallery_subcategories';
    $sql2 = "CREATE TABLE IF NOT EXISTS $subcategories_table (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        category_id BIGINT(20) UNSIGNED NOT NULL,
        subcategory_name VARCHAR(255) NOT NULL,
        FOREIGN KEY (category_id) REFERENCES $categories_table(id) ON DELETE CASCADE
    ) $charset_collate;";

    // Images Table
    $images_table = $wpdb->prefix . 'custom_gallery_images';
    $sql3 = "CREATE TABLE IF NOT EXISTS $images_table (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        subcategory_id BIGINT(20) UNSIGNED NOT NULL,
        image_url TEXT NOT NULL,
        is_main TINYINT(1) DEFAULT 0,
        FOREIGN KEY (subcategory_id) REFERENCES $subcategories_table(id) ON DELETE CASCADE
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql1);
    dbDelta($sql2);
    dbDelta($sql3);
}


function custom_gallery_admin_menu()
{
    add_menu_page(
        'Custom Gallery',
        'Custom Gallery',
        'manage_options',
        'custom-gallery',
        'custom_gallery_admin_page',
        'dashicons-format-gallery',
        25
    );
    add_submenu_page(
        'custom-gallery',
        'Create Your Gallery',
        'Create Your Gallery',
        'manage_options',
        'create-your-gallery',
        'create_your_gallery_page'
    );
    add_submenu_page(
        'custom-gallery', // Parent slug (links to main menu)
        'Gallery Table', // Page title
        'Gallery Table', // Menu title
        'manage_options', // Capability
        'custom-gallery-table', // Menu slug
        'custom_gallery_table_page' // Callback function
    );
    add_submenu_page(
        'custom-gallery', // Parent Slug (you can choose any appropriate parent)
        'Edit Image', // Page Title
        'Edit Image', // Menu Title
        'manage_options', // Capability
        'edit_image', // Menu Slug
        'edit_image_page' // Function to display the page content
    );
}
add_action('admin_menu', 'custom_gallery_admin_menu');


function custom_gallery_admin_page()
{
    ?>
    <div class="wrap">
        <h1>Custom Gallery</h1>

        <form id="custom-gallery-form" method="POST" enctype="multipart/form-data">
            <h2>Create a Category</h2>
            <div id="category-container">
                <div class="category-block">
                    <label>Category Name:</label>
                    <input type="text" name="category_name[]" required>

                    <h3>Subcategories</h3>
                    <div class="subcategory-container">
                        <div class="subcategory-block">
                            <label>Subcategory Name:</label>
                            <input type="text" name="subcategory_name[0][]" required>

                            <h4>Images</h4>
                            <div class="image-container">
                                <input type="file" name="subcategory_images[0][0][]" multiple required>
                            </div>

                            <button type="button" class="add-image">+ Add More Images</button>
                        </div>
                    </div>

                    <button type="button" class="add-subcategory">+ Add More Subcategory</button>
                </div>
            </div>

            <button type="button" id="add-category">+ Add New Category</button>
            <button type="submit" name="submit_gallery">Save Gallery</button>
        </form>

        <?php
        if (isset($_POST['submit_gallery'])) {
            save_custom_gallery(); // Call the save function when the form is submitted
            echo '<div class="updated"><p>Gallery saved successfully!</p></div>';
        }
        ?>

    </div>

    <script>
        jQuery(document).ready(function (jQuery) {
            let categoryIndex = 0;

            jQuery('#add-category').click(function () {
                categoryIndex++;
                let newCategory = `<div class="category-block">
                <label>Category Name:</label>
                <input type="text" name="category_name[]" required>
                <h3>Subcategories</h3>
                <div class="subcategory-container">
                    <div class="subcategory-block">
                        <label>Subcategory Name:</label>
                        <input type="text" name="subcategory_name[${categoryIndex}][]" required>
                        <h4>Images</h4>
                        <div class="image-container">
                            <input type="file" name="subcategory_images[${categoryIndex}][0][]" multiple required>
                        </div>
                        <button type="button" class="add-image">+ Add More Images</button>
                    </div>
                </div>
                <button type="button" class="add-subcategory">+ Add More Subcategory</button>
            </div>`;
                jQuery('#category-container').append(newCategory);
            });

            jQuery(document).on('click', '.add-subcategory', function () {
                let categoryIndex = jQuery('#category-container .category-block').index(jQuery(this).closest('.category-block'));
                let subIndex = jQuery(this).siblings('.subcategory-container').children('.subcategory-block').length;

                let newSubcategory = `<div class="subcategory-block">
                <label>Subcategory Name:</label>
                <input type="text" name="subcategory_name[${categoryIndex}][]" required>
                <h4>Images</h4>
                <div class="image-container">
                    <input type="file" name="subcategory_images[${categoryIndex}][${subIndex}][]" multiple required>
                </div>
                <button type="button" class="add-image">+ Add More Images</button>
            </div>`;
                jQuery(this).siblings('.subcategory-container').append(newSubcategory);
            });

            jQuery(document).on('click', '.add-image', function () {
                let categoryIndex = jQuery('#category-container .category-block').index(jQuery(this).closest('.category-block'));
                let subIndex = jQuery(this).closest('.subcategory-block').index();

                let newImage = `<input type="file" name="subcategory_images[${categoryIndex}][${subIndex}][]" multiple required>`;
                jQuery(this).siblings('.image-container').append(newImage);
            });
        });
    </script>

    <?php
}

// ✅ Save Function (Runs on Form Submission)
function save_custom_gallery()
{
    global $wpdb;

    if (!empty($_POST['category_name'])) {
        foreach ($_POST['category_name'] as $cat_index => $category_name) {
            $wpdb->insert($wpdb->prefix . 'custom_gallery_categories', ['category_name' => sanitize_text_field($category_name)]);
            $category_id = $wpdb->insert_id;

            if (!empty($_POST['subcategory_name'][$cat_index])) {
                foreach ($_POST['subcategory_name'][$cat_index] as $sub_index => $subcategory_name) {
                    $wpdb->insert($wpdb->prefix . 'custom_gallery_subcategories', [
                        'category_id' => $category_id,
                        'subcategory_name' => sanitize_text_field($subcategory_name)
                    ]);
                    $subcategory_id = $wpdb->insert_id;

                    if (!empty($_FILES['subcategory_images']['name'][$cat_index][$sub_index])) {
                        foreach ($_FILES['subcategory_images']['name'][$cat_index][$sub_index] as $img_index => $image_name) {
                            $file_tmp = $_FILES['subcategory_images']['tmp_name'][$cat_index][$sub_index][$img_index];
                            $file_path = wp_upload_dir()['path'] . '/' . $image_name;

                            if (move_uploaded_file($file_tmp, $file_path)) {
                                $wpdb->insert($wpdb->prefix . 'custom_gallery_images', [
                                    'subcategory_id' => $subcategory_id,
                                    'image_url' => wp_upload_dir()['url'] . '/' . $image_name,
                                    'is_main' => 0
                                ]);
                            }
                        }
                    }
                }
            }
        }
    }
}

function gallery_admin_css()
{
    echo '<style>
        #custom-gallery-form {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: Arial, sans-serif;
        }

        #custom-gallery-form h2 {
            text-align: center;
            color: #333;
        }

        .category-block {
            background: #fff;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 6px;
        }

        .category-block label,
        .subcategory-block label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #444;
        }

        .category-block input[type="text"],
        .subcategory-block input[type="text"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .image-container input[type="file"] {
            margin-top: 5px;
        }

        button {
            display: inline-block;
            padding: 8px 12px;
            margin-top: 10px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            border-radius: 4px;
        }

        .add-subcategory,
        .add-image {
            background: #007bff;
            color: #fff;
        }

        #add-category {
            background: #28a745;
            color: #fff;
        }

        button:hover {
            opacity: 0.8;
        }

        #custom-gallery-form button[type="submit"] {
            background: #ff6600;
            color: white;
            width: 100%;
            padding: 10px;
            font-size: 16px;
        }

        table {
        border-collapse: collapse;
        width: 100%;
        margin-top: 20px;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    th {
        background-color: #f4f4f4;
    }
    img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 5px;
    }
    </style>';
}
add_action('admin_head', 'gallery_admin_css');


function custom_gallery_table_page()
{
    global $wpdb;
    $categories_table = $wpdb->prefix . 'custom_gallery_categories';
    $subcategories_table = $wpdb->prefix . 'custom_gallery_subcategories';
    $images_table = $wpdb->prefix . 'custom_gallery_images';

    // Fetch all categories
    $categories = $wpdb->get_results("SELECT * FROM $categories_table");

    echo '<div class="wrap">';
    echo '<h1>Gallery Table</h1>';
    echo '<table class="widefat fixed" style="width:100%; border-collapse: collapse;">';
    echo '<thead>
            <tr>
                <th style="border: 1px solid #ddd; padding: 8px;">Category</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Subcategory</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Images</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Actions</th>
            </tr>
          </thead>';
    echo '<tbody>';

    if (!empty($categories)) {
        foreach ($categories as $category) {
            // Fetch subcategories for this category
            $subcategories = $wpdb->get_results($wpdb->prepare("SELECT * FROM $subcategories_table WHERE category_id = %d", $category->id));

            if (!empty($subcategories)) {
                foreach ($subcategories as $subcategory) {
                    // Fetch images for this subcategory
                    $images = $wpdb->get_results($wpdb->prepare("SELECT * FROM $images_table WHERE subcategory_id = %d", $subcategory->id));

                    echo '<tr>';
                    echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($category->category_name) . '</td>';
                    echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($subcategory->subcategory_name) . '</td>';
                    echo '<td style="border: 1px solid #ddd; padding: 8px;">';

                    if (!empty($images)) {
                        foreach ($images as $image) {
                            echo '<img src="' . esc_url($image->image_url) . '" alt="Gallery Image" style="width: 50px; height: 50px; margin-right: 5px;">';
                        }
                    } else {
                        echo 'No Images';
                    }

                    echo '</td>';
                    echo '<td style="border: 1px solid #ddd; padding: 8px;">';
                    // Edit Button for Image (pass category_id and subcategory_id in the URL)
                    echo '<a href="' . esc_url(admin_url('admin.php?page=edit_image&category_id=' . $category->id . '&subcategory_id=' . $subcategory->id)) . '" class="button button-secondary">Edit Image</a>';
                    echo '</td>';

                    echo '</tr>';
                }
            } else {
                echo '<tr>';
                echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($category->category_name) . '</td>';
                echo '<td colspan="3" style="border: 1px solid #ddd; padding: 8px; text-align:center;">No Subcategories</td>';
                echo '</tr>';
            }
        }
    } else {
        echo '<tr><td colspan="4" style="text-align: center; border: 1px solid #ddd; padding: 8px;">No Categories Found</td></tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}


function create_gallery_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_galleries'; // Uses WordPress prefix dynamically
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        gallery_name VARCHAR(255) NOT NULL,
        category_ids TEXT NOT NULL,
        want_slider TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql); // Runs the query safely
}
add_action('init', 'create_gallery_table');

function create_your_gallery_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_galleries';

    // Fetch existing galleries
    $galleries = $wpdb->get_results("SELECT * FROM $table_name");
    if (isset($_POST['delete_gallery']) && isset($_POST['gallery_id'])) {
        $gallery_id = intval($_POST['gallery_id']);
        $wpdb->delete($table_name, array('id' => $gallery_id), array('%d'));

        // Redirect to the same page to reload and show the updated galleries
        echo '<script type="text/javascript">
            window.location.reload();
          </script>';
        exit;
    }

    ?>
    <div class="wrap">
        <h1>Create Your Gallery</h1>

        <button id="open-gallery-form" class="button button-primary">Create Your Gallery</button>

        <div id="gallery-form-container" style="display: none;">
            <h2>Add New Gallery</h2>
            <form id="gallery-form" method="POST">
                <label>Gallery Name:</label>
                <input type="text" name="gallery_name" required><br><br>

                <label>Select Main Categories:</label><br>
                <?php
                $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}custom_gallery_categories");
                foreach ($categories as $category) {
                    echo '<input type="checkbox" name="category_ids[]" value="' . esc_attr($category->id) . '"> ' . esc_html($category->category_name) . '<br>';
                }
                ?>
                <br>

                <label>Want Slider?</label>
                <input type="radio" name="want_slider" value="1"> Yes
                <input type="radio" name="want_slider" value="0" checked> No
                <br><br>

                <button type="submit" name="save_gallery" class="button button-primary">Save Gallery</button>
            </form>
        </div>

        <h2>All Galleries</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Gallery Name</th>
                    <th>Categories</th>
                    <th>Slider</th>
                    <th>Shortcode</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($galleries as $gallery): ?>
                    <tr>
                        <td><?php echo $gallery->id; ?></td>
                        <td><?php echo esc_html($gallery->gallery_name); ?></td>
                        <td><?php echo esc_html($gallery->category_ids); ?></td>
                        <td><?php echo $gallery->want_slider ? 'Yes' : 'No'; ?></td>
                        <td>[flexi_gallery id="<?php echo $gallery->id; ?>"]</td>
                        <td>
                            <!-- Delete Button -->
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="gallery_id" value="<?php echo $gallery->id; ?>">
                                <button type="submit" name="delete_gallery" class="button button-secondary"
                                    onclick="return confirm('Are you sure you want to delete this gallery?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        document.getElementById("open-gallery-form").addEventListener("click", function () {
            document.getElementById("gallery-form-container").style.display = "block";
        });
    </script>
    <?php
}
function save_gallery_data()
{
    if (isset($_POST['save_gallery'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_galleries';

        $gallery_name = sanitize_text_field($_POST['gallery_name']);
        $category_ids = isset($_POST['category_ids']) ? implode(',', array_map('intval', $_POST['category_ids'])) : '';
        $want_slider = isset($_POST['want_slider']) ? intval($_POST['want_slider']) : 0;

        $wpdb->insert($table_name, [
            'gallery_name' => $gallery_name,
            'category_ids' => $category_ids,
            'want_slider' => $want_slider,
        ]);

        wp_redirect(admin_url('admin.php?page=create-your-gallery'));
        exit;
    }
}
add_action('admin_init', 'save_gallery_data');
function enqueue_media_uploader_script()
{
    wp_enqueue_media();  // Enqueue WordPress media library
}
add_action('admin_enqueue_scripts', 'enqueue_media_uploader_script');
function edit_image_page()
{
    global $wpdb;

    // Retrieve the category_id and subcategory_id from the URL
    $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
    $subcategory_id = isset($_GET['subcategory_id']) ? intval($_GET['subcategory_id']) : 0;

    // Check if both category_id and subcategory_id are valid
    if (!$category_id || !$subcategory_id) {
        echo '<div class="error"><p>Invalid category or subcategory ID.</p></div>';
        return;
    }

    // Handle Image Update
    if (isset($_GET['action']) && $_GET['action'] === 'update' && isset($_GET['id'])) {
        $image_id = intval($_GET['id']);

        // Retrieve the new image URL from the form submission
        if (isset($_POST['image_url']) && !empty($_POST['image_url'])) {
            // Sanitize and update the image URL
            $new_image_url = esc_url($_POST['image_url']);

            // Update the image URL in the database
            $wpdb->update(
                $wpdb->prefix . 'custom_gallery_images', // Table name
                array('image_url' => $new_image_url), // New data
                array('id' => $image_id), // Condition
                array('%s'), // Data format
                array('%d') // Format for the ID
            );

            // Set a flag to show success notice
            add_action('admin_notices', function () {
                echo '<div class="notice notice-success is-dismissible"><p>Image updated successfully.</p></div>';
            });

            // Redirect to the gallery table page after update
            wp_redirect(admin_url('admin.php?page=custom-gallery-table'));
            exit; // Ensure the script stops here
        }
    }

    // Handle Image Deletion
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $image_id = intval($_GET['id']);

        // Delete the image from the database
        $wpdb->delete(
            $wpdb->prefix . 'custom_gallery_images', // Table name
            array('id' => $image_id), // Condition
            array('%d') // Format for the ID
        );

        // Set a flag to show success notice
        add_action('admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible"><p>Image deleted successfully.</p></div>';
        });

        // Redirect to the gallery table page after deletion
        wp_redirect(admin_url('admin.php?page=custom-gallery-table'));
        exit; // Ensure nothing else is processed
    }

    // Handle New Image Upload
    if (isset($_POST['new_image_url']) && !empty($_POST['new_image_url'])) {
        // Sanitize and insert the new image URL into the database
        $new_image_url = esc_url($_POST['new_image_url']);
        // Assuming category_id is not required in your table, remove it from the query:
        $wpdb->insert(
            $wpdb->prefix . 'custom_gallery_images', // Table name
            array(
                'image_url' => $new_image_url, // Only include the columns that exist in the table
                'subcategory_id' => $subcategory_id // Assuming subcategory_id exists
            ),
            array(
                '%s', // Format for image_url
                '%d'  // Format for subcategory_id
            )
        );


        // Set a flag to show success notice
        add_action('admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible"><p>New image added successfully.</p></div>';
        });

        // Redirect after adding the image
        wp_safe_redirect(admin_url('admin.php?page=edit_image&category_id=' . $category_id . '&subcategory_id=' . $subcategory_id));
        exit;
    }

    // Get images related to the category_id and subcategory_id
    $images_table = $wpdb->prefix . 'custom_gallery_images';
    $images = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $images_table WHERE subcategory_id = %d",
            $subcategory_id
        )
    );

    echo '<div class="wrap">';
    echo '<h1>Edit Images for Category ID: ' . esc_html($category_id) . ' and Subcategory ID: ' . esc_html($subcategory_id) . '</h1>';

    // Add New Image Form
    echo '<h2>Add New Image</h2>';
    echo '<form method="POST" action="">';
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th scope="row"><label for="new_image_url">Select Image</label></th>';
    echo '<td>';
    echo '<input type="text" id="new_image_url" name="new_image_url" class="regular-text" readonly /> ';
    echo '<input type="button" class="button button-primary" value="Select Image" id="new_image_button" />'; // Button to trigger media uploader
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '<p><input type="submit" name="submit" class="button button-primary" value="Add Image"></p>';
    echo '</form>';

    // Display existing images
    if (!empty($images)) {
        echo '<table class="widefat fixed" style="width:100%; border-collapse: collapse;">';
        echo '<thead>
                <tr>
                    <th style="border: 1px solid #ddd; padding: 8px;">Image</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Actions</th>
                </tr>
              </thead>';
        echo '<tbody>';

        foreach ($images as $image) {
            echo '<tr>';
            echo '<td style="border: 1px solid #ddd; padding: 8px;">';
            if (filter_var($image->image_url, FILTER_VALIDATE_URL)) {
                echo '<img src="' . esc_url($image->image_url) . '" alt="Image" style="max-width: 100px; max-height: 100px;"/>';
            } else {
                echo 'Invalid image URL';
            }
            echo '</td>';
            echo '<td style="border: 1px solid #ddd; padding: 8px;">';
            echo '<a href="' . esc_url(admin_url('admin.php?page=edit_image&id=' . $image->id . '&action=update&category_id=' . $category_id . '&subcategory_id=' . $subcategory_id)) . '" class="button button-secondary">Update Image</a> ';
            echo '<a href="' . esc_url(admin_url('admin.php?page=edit_image&action=delete&id=' . $image->id . '&category_id=' . $category_id . '&subcategory_id=' . $subcategory_id)) . '" class="button button-danger" onclick="return confirm(\'Are you sure you want to delete this image?\')">Delete Image</a>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No images found for the selected category and subcategory.</p>';
    }

    echo '</div>';

    // Handle Update Image Form
    if (isset($_GET['action']) && $_GET['action'] === 'update' && isset($_GET['id'])) {
        $image_id = intval($_GET['id']);
        $image_data = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}custom_gallery_images WHERE id = %d", $image_id)
        );

        if ($image_data) {
            echo '<div class="wrap">';
            echo '<h2>Update Image</h2>';
            echo '<form method="POST" action="">';
            echo '<table class="form-table">';
            echo '<tr>';
            echo '<th scope="row"><label for="image_url">Select New Image</label></th>';
            echo '<td>';
            echo '<input type="text" id="image_url" name="image_url" value="' . esc_attr($image_data->image_url) . '" class="regular-text" readonly /> ';
            echo '<input type="button" class="button button-primary" value="Select Image" id="image_button" />'; // Button to trigger media uploader
            echo '</td>';
            echo '</tr>';
            echo '</table>';
            echo '<p><input type="submit" name="submit" class="button button-primary" value="Update Image"></p>';
            echo '</form>';
            echo '</div>';
        }
    }

    // Include JavaScript for Media Uploader
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function (jQuery) {
            var mediaUploader;
            jQuery('#image_button').click(function (e) {
                e.preventDefault();
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                mediaUploader = wp.media.frames.file_frame = wp.media({
                    title: 'Choose an Image',
                    button: {
                        text: 'Select Image'
                    },
                    multiple: false
                });
                mediaUploader.on('select', function () {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    jQuery('#image_url').val(attachment.url);
                });
                mediaUploader.open();
            });

            var mediaUploaderNew;
            jQuery('#new_image_button').click(function (e) {
                e.preventDefault();
                if (mediaUploaderNew) {
                    mediaUploaderNew.open();
                    return;
                }
                mediaUploaderNew = wp.media.frames.file_frame = wp.media({
                    title: 'Choose an Image',
                    button: {
                        text: 'Select Image'
                    },
                    multiple: false
                });
                mediaUploaderNew.on('select', function () {
                    var attachment = mediaUploaderNew.state().get('selection').first().toJSON();
                    jQuery('#new_image_url').val(attachment.url);
                });
                mediaUploaderNew.open();
            });
        });
    </script>
    <?php
}


function display_custom_gallery($atts)
{
    global $wpdb;
    $atts = shortcode_atts(['id' => ''], $atts, 'custom_gallery');

    // Fetch the gallery data
    $gallery = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}custom_galleries WHERE id = %d", $atts['id']));

    if (!$gallery) {
        return '<p>Gallery not found.</p>';
    }

    // Get category IDs from the gallery
    $category_ids = explode(',', $gallery->category_ids);

    // Fetch categories from the custom_gallery_categories table
    $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}custom_gallery_categories WHERE id IN (" . implode(',', array_map('intval', $category_ids)) . ")");
    if (empty($categories)) {
        return '<p>No categories found.</p>';
    }

    // Fetch subcategories based on category_id
    $subcategories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}custom_gallery_subcategories WHERE category_id IN (" . implode(',', array_map('intval', $category_ids)) . ")");
    if (empty($subcategories)) {
        return '<p>No subcategories found.</p>';
    }

    // Fetch images based on subcategory_id
    $subcategory_ids = wp_list_pluck($subcategories, 'id');
    $images = [];
    if (!empty($subcategory_ids)) {
        $images = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}custom_gallery_images WHERE subcategory_id IN (" . implode(',', array_map('intval', $subcategory_ids)) . ")");
    }

    ob_start();
    ?>

    <div class="gallery-container">
        <div class="sidebar">
            <h3>Categories</h3>
            <?php foreach ($categories as $category): ?>
                <div class="category-item">
                    <strong><?php echo esc_html($category->category_name); ?></strong>
                    <div class="subcategory-tabs">
                        <?php foreach ($subcategories as $index => $subcategory): ?>
                            <?php if ($subcategory->category_id == $category->id): ?>
                                <button class="subcategory-item <?php echo ($index === 0) ? 'active' : ''; ?>"
                                    onclick="showSubcategoryImages(<?php echo esc_js($subcategory->id); ?>)">
                                    <?php echo esc_html($subcategory->subcategory_name); ?>
                                </button>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="gallery" id="gallery">
            <?php foreach ($images as $image): ?>
                <div class="gallery-item" data-subcategory-id="<?php echo esc_attr($image->subcategory_id); ?>"
                    onclick="openLightbox('<?php echo esc_url($image->image_url); ?>')">
                    <img src="<?php echo esc_url($image->image_url); ?>" alt="Gallery Image">
                    <div class="overlay">
                        <span
                            class="overlay-text"><?php echo esc_html(pathinfo(basename($image->image_url), PATHINFO_FILENAME)); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Lightbox Modal -->
    <div class="lightbox" id="lightbox">
        <span class="close-lightbox" onclick="closeLightbox()">&times;</span>
        <img id="lightbox-img" src="" alt="Expanded Image">
    </div>

    <script>
        function openLightbox(imageSrc) {
            document.getElementById('lightbox-img').src = imageSrc;
            document.getElementById('lightbox').classList.add('active');
        }

        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('active');
        }

        function showSubcategoryImages(subcategoryId) {
            var galleryItems = document.querySelectorAll('.gallery-item');
            galleryItems.forEach(function (item) {
                if (item.getAttribute('data-subcategory-id') === String(subcategoryId)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });

            // Highlight the active tab
            var tabs = document.querySelectorAll('.subcategory-item');
            tabs.forEach(function (tab) {
                tab.classList.remove('active');
            });
            document.querySelector('.subcategory-item[data-subcategory-id="' + subcategoryId + '"]').classList.add('active');
        }

        // Default to showing the first subcategory
        document.addEventListener('DOMContentLoaded', function () {
            var firstSubcategory = document.querySelector('.subcategory-item');
            if (firstSubcategory) {
                var firstSubcategoryId = firstSubcategory.getAttribute('onclick').match(/\d+/)[0];
                showSubcategoryImages(firstSubcategoryId);
            }
        });
    </script>

    <style>
        /* Gallery and Sidebar Layout */
        .gallery-container {
            display: flex;
            justify-content: space-between;
            margin: 50px auto;
            max-width: 1200px;
            align-items: stretch;
            /* Ensures both sidebar and gallery stretch to equal height */
        }

        /* Sidebar Styles */
        .sidebar {
            width: 25%;
            background-color: #333;
            color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 20px;
            height: 100%;
            /* Ensures the sidebar takes the full height of its parent container */
            display: flex;
            flex-direction: column;
        }

        /* Sidebar content */
        .sidebar h3 {
            text-align: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #fff;
        }

        .category-item {
            margin-bottom: 20px;
        }

        .category-item strong {
            display: block;
            font-size: 1.2rem;
            color: #f5a623;
            margin-bottom: 10px;
        }

        .subcategory-tabs {
            display: flex;
            flex-direction: column;
        }

        .subcategory-item {
            background-color: #444;
            color: #fff;
            padding: 10px;
            margin: 5px 0;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-align: left;
            font-size: 1rem;
        }

        .subcategory-item:hover {
            background-color: #f5a623;
        }

        /* Gallery Styles */
        .gallery {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            width: 70%;
            height: 100%;
            /* Ensures the gallery stretches to match the height of the sidebar */
        }

        /* Gallery Items */
        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 12px;
            cursor: pointer;
        }

        .gallery-item img {
            width: 100%;
            aspect-ratio: 1/1;
            object-fit: cover;
            transition: transform 0.4s ease, filter 0.4s ease, box-shadow 0.4s ease;
            border-radius: 12px;
        }

        .gallery-item:hover img {
            transform: scale(1.08);
            filter: brightness(75%);
            box-shadow: 0px 10px 20px rgba(255, 255, 255, 0.3);
        }

        /* Overlay Effect */
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.4s ease;
            border-radius: 12px;
        }

        .gallery-item:hover .overlay {
            opacity: 1;
        }

        .overlay-text {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #fff;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.8);
        }

        /* Lightbox Effect */
        .lightbox {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.4s ease, visibility 0.4s ease;
        }

        .lightbox img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 12px;
            transition: transform 0.4s ease;
        }

        .lightbox.active {
            opacity: 1;
            visibility: visible;
        }

        .close-lightbox {
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 30px;
            cursor: pointer;
            color: #fff;
            transition: transform 0.3s ease;
        }

        .close-lightbox:hover {
            transform: scale(1.2);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .gallery {
                grid-template-columns: repeat(2, 1fr);
            }

            .sidebar {
                width: 100%;
                margin-bottom: 30px;
            }

            .gallery-container {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .gallery {
                grid-template-columns: repeat(1, 1fr);
            }

            .sidebar {
                width: 100%;
                position: relative;
            }

            .subcategory-item {
                font-size: 0.9rem;
            }
        }
    </style>
    <?php
    return ob_get_clean();
}

add_shortcode('custom_gallery', 'display_custom_gallery');
