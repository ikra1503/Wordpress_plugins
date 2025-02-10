jQuery(document).ready(function ($) {
    // Activate the first tab by default
    $(".tab-link").first().addClass("active");
    $(".tab-content").first().addClass("active").show();

    // Click event for tab links
    $(".tab-link").on("click", function () {
        var year = $(this).data("year");

        // Remove active class from all tabs and hide all tab contents
        $(".tab-link").removeClass("active");
        $(".tab-content").removeClass("active").hide();

        // Add active class to the clicked tab and show the corresponding content
        $(this).addClass("active");
        $("#year-" + year).addClass("active").show();
    });
});


function showPopup(memberId) {
    const popup = document.getElementById(memberId);
    popup.style.display = 'block'; // Show the popup
}

// Function to close the popup
function closePopup(event) {
    if (event.target.classList.contains('popup') || event.target.classList.contains('close')) {
        const popups = document.querySelectorAll('.popup');
        popups.forEach(popup => {
            popup.style.display = 'none'; // Hide all popups
        });
    }
}


// function openPopup(id) {
//     document.getElementById(id).style.display = 'flex';
// }

// function closePopup(id) {
//     document.getElementById(id).style.display = 'none';
// }

// Function to open the popup
function openPopup(popupId) {
    document.getElementById(popupId).style.display = 'flex'; // Show the popup
}

// Function to close the popup
function closePopup(popupId) {
    document.getElementById(popupId).style.display = 'none'; // Hide the popup
}

const cards = document.querySelectorAll('.managing-committee-card');
cards.forEach((card, index) => {
    if (index % 2 !== 0) {
        card.classList.add('right-side'); // Add the class to every second card
    }
});


function addLoader() {
    // Create a simple spinner div
    const spinner = document.createElement('div');
    spinner.classList.add('spinner');

    // Add it to the body
    document.body.appendChild(spinner);
}

function removeLoader() {
    // Find the spinner by class name and remove it
    const spinner = document.querySelector('.spinner');
    if (spinner) {
        spinner.remove();
    }
}


// jQuery(document).ready(function ($) {
//     // Handle the click event for the Load More button
//     $('#load_more_members').on('click', function () {
//         var button = $(this);
//         var paged = button.data('paged');  // Get the current paged value

//         $.ajax({
//             url: ajaxurl,  // WordPress AJAX URL
//             method: 'POST',
//             data: {
//                 action: 'load_more_members',
//                 paged: paged,
//             },
//             beforeSend: function () {
//                 addLoader();
//                 button.text('Loading...'); // Change button text while loading
//             },
//             success: function (response) {
//                 if (response) {
//                     removeLoader();
//                     $('.member-section .member-cards-container').append(response);

//                     // Append new member cards
//                     button.data('paged', paged + 1); // Increment the paged value for the next request
//                 } else {
//                     button.hide(); // Change button text if no more members
//                 }
//             },
//             complete: function () {
//                 removeLoader();
//                 button.text('Load More'); // Reset the button text after request
//             }
//         });
//     });
// });


jQuery(document).ready(function ($) {
    // Handle the click event for the Load More button
    $('#load_more_members').on('click', function () {
        var button = $(this);
        var paged = button.data('paged');  // Get the current paged value

        $.ajax({
            url: ajaxurl,  // WordPress AJAX URL
            method: 'POST',
            data: {
                action: 'load_more_members',
                paged: paged,
            },
            beforeSend: function () {
                addLoader();
                button.text('Loading...'); // Change button text while loading
            },
            success: function (response) {
                if (response) {
                    removeLoader();
                    $('.managing-committee-member-cards-container').append(response);

                    // Increment paged value for the next request
                    button.data('paged', paged + 1);

                    // If there are no more posts to load, hide the button
                    if (response.trim() === '') {
                        button.hide();
                    }
                } else {
                    button.hide(); // Hide the button if no more members
                }
            },
            complete: function () {
                removeLoader();
                button.text('Load More'); // Reset the button text after request
            }
        });
    });
});


jQuery(document).ready(function ($) {
    // Check if the current page is the one with ID 2084
    if ($('body').hasClass('page-id-2084')) {
        $('.join-group, .leave-group').on('click', function (e) {
            e.preventDefault();
            console.log("i am clicked");
            const button = $(this);
            const groupId = button.data('group-id');
            const actionType = button.data('action'); // 'join' or 'leave'

            // Add loading class and spinner
            button.addClass('loading-button');
            button.append('<span class="loading-spinner"></span>'); // Add spinner

            $.ajax({
                url: ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'handle_group_join_leave',
                    group_id: groupId,
                    action_type: actionType,
                    security: ajax_object.nonce
                },
                success: function (response) {
                    if (response.success) {
                        // Update button text and action type based on the result
                        if (actionType === 'join') {
                            button.text('Leave Group').data('action', 'leave').removeClass('join-group').addClass('leave-group');
                        } else {
                            button.text('Join Group').data('action', 'join').removeClass('leave-group').addClass('join-group');
                        }
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function () {
                    alert('An error occurred. Please try again.');
                },
                complete: function () {
                    // Remove loading indicator after AJAX completes
                    button.removeClass('loading-button');
                    button.find('.loading-spinner').remove(); // Remove spinner
                }
            });
        });
    }
});



// FAQ JS
function toggleFAQ(id) {
    var faqItem = document.getElementById(id);
    var answer = faqItem.querySelector('.accordion-data');
    var indicator = faqItem.querySelector('.indicator');

    // Toggle visibility of the clicked answer
    if (answer.style.display === 'none' || answer.style.display === '') {
        answer.style.display = 'block';
        indicator.textContent = '▲';  // Change indicator to up caret when open
    } else {
        answer.style.display = 'none';
        indicator.textContent = '▼';  // Change indicator to down caret when closed
    }
}

// Add event listener to carat to also trigger opening/closing
document.querySelectorAll('.indicator').forEach(function (indicator) {
    indicator.addEventListener('click', function (event) {
        var faqItem = indicator.closest('.accordion-row');
        var answer = faqItem.querySelector('.accordion-data');

        // Prevent the event from bubbling to the parent
        event.stopPropagation();

        // Toggle visibility of the clicked answer
        if (answer.style.display === 'none' || answer.style.display === '') {
            answer.style.display = 'block';
            indicator.textContent = '▲';  // Change indicator to up caret when open
        } else {
            answer.style.display = 'none';
            indicator.textContent = '▼';  // Change indicator to down caret when closed
        }
    });
});

// //portal releeted js
// jQuery(document).ready(function() {
//     // Handle Preview Button Click
//     jQuery("#preview-abstract").on("click", function() {
//         var formData = {
//             author_name: jQuery("#author_name").val(),
//             author_email: jQuery("input[name='author_email']").val(),
//             author_id: jQuery("input[name='author_id']").val(),
//             co_authors: [],
//             co_authors_membership_id: [],
//             abstract_title: jQuery("#abstract_title").val(),
//             abstract_description: jQuery("#abstract_description").val(),
//             membership_selection: jQuery("#membership_selection").val()
//         };

//         jQuery(".co-author-fields").each(function() {
//             formData.co_authors.push(jQuery(this).find("input[name='co_authors[]']").val());
//             formData.co_authors_membership_id.push(jQuery(this).find("input[name='co_authors_membership_id[]']").val());
//         });

//         // Populate the modal with form data
//         var previewContent = "<strong>Author Name:</strong> " + formData.author_name + "<br>";
//         previewContent += "<strong>Co-Authors:</strong> " + formData.co_authors.join(', ') + "<br>";
//         previewContent += "<strong>Abstract Title:</strong> " + formData.abstract_title + "<br>";
//         previewContent += "<strong>Abstract Description:</strong> " + formData.abstract_description + "<br>";
//         previewContent += "<strong>Membership Selection:</strong> " + formData.membership_selection + "<br>";

//         jQuery("#form-preview").html(previewContent);

//         // Show the confirmation modal
//         jQuery("#confirmation-modal").show();

//         // Hide the submit button initially
//         jQuery("input[type='submit']").hide();
//     });

//     // Close the modal if the close button is clicked
//     jQuery(".close-modal").on("click", function() {
//         jQuery("#confirmation-modal").hide();
//     });

//     // Confirm button clicked
//     jQuery("#confirm-details").on("click", function() {
//         // Show the submit button and hide the modal
//         jQuery("input[type='submit']").show();
//         jQuery("#confirmation-modal").hide();
//         jQuery('#preview-abstract').hide();
//     });

//     // Edit button clicked
//     jQuery("#edit-details").on("click", function() {
//         // Just hide the modal and let the user edit
//         jQuery("#confirmation-modal").hide();
//     });
// });
// Show the popup when the page loads if the parameter is set
window.onload = function () {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('abstract_submitted') === 'true') {
        document.getElementById('submission-popup').classList.add('show');
    }

    // Ensure the close button exists before adding the event listener
    const closeButton = document.querySelector('.close');
    if (closeButton) {
        closeButton.addEventListener('click', function () {
            document.getElementById('submission-popup').classList.remove('show');
            removeUrlParamsAndRedirect();
        });
    }

    // Optionally, close the popup when clicking outside of it
    const popup = document.getElementById('submission-popup');
    if (popup) {
        popup.addEventListener('click', function (event) {
            if (event.target === this) {
                popup.classList.remove('show');
                removeUrlParamsAndRedirect();
            }
        });
    }
};

// Function to remove URL parameters and redirect
function removeUrlParamsAndRedirect() {
    const url = window.location.href.split('?')[0]; // Get the base URL without parameters
    window.location.href = url; // Redirect to the same URL without query parameters
}


jQuery(document).ready(function () {
    jQuery("#confirmation-modal").hide();

    // Handle Preview Button Click
    jQuery("#preview-abstract").on("click", function () {
        var formData = {
            author_name: jQuery("#author_name").val(),
            author_email: jQuery("input[name='author_email']").val(),
            author_id: jQuery("input[name='author_id']").val(),
            co_authors: [],
            co_authors_membership_id: [],
            abstract_title: jQuery("#abstract_title").val(),
            abstract_description: jQuery("#abstract_description").val(),
            membership_selection: jQuery("#membership_selection").val(),
            membershipSelectionText: jQuery("#membership_selection option:selected").text(),
            action: "submit_abstract_data", // AJAX action name
            nonce: ajax_object.nonce // Pass nonce for security
        };

        jQuery(".co-author-fields").each(function () {
            formData.co_authors.push(jQuery(this).find("input[name='co_authors[]']").val());
            formData.co_authors_membership_id.push(jQuery(this).find("input[name='co_authors_membership_id[]']").val());
        });

        // Send AJAX request on Preview Button Click
        jQuery.ajax({
            type: "POST",
            url: ajax_object.ajax_url,
            data: formData,
            success: function (response) {
                if (response.success) {
                    var satisfiedCount = response.data.co_authors_satisfied_count;
                    var totalCertificates = satisfiedCount + 1;

                    var previewContent = "<strong>Total Certificates will be Generated:</strong> " + totalCertificates + "<br><br>";
                    previewContent += "1 main author and " + satisfiedCount + " certificate(s) for co-author(s)<br><br>";

                    // Append form data
                    previewContent += "<strong>Author Name:</strong> " + formData.author_name + "<br>";
                    previewContent += "<strong>Co-Authors:</strong> " + (formData.co_authors ? formData.co_authors.join(', ') : "None") + "<br>";
                    previewContent += "<strong>Abstract Title:</strong> " + formData.abstract_title + "<br>";
                    previewContent += "<strong>Abstract Text:</strong> " + formData.abstract_description + "<br>";
                    previewContent += "<strong>Research Committee to be reviewed:</strong> " + formData.membershipSelectionText + "<br>";



                    jQuery("#form-preview").html(previewContent);
                    jQuery("#confirmation-modal").show();
                    jQuery("input[type='submit']").hide();


                    // Handle Co-Author Status
                    var coAuthorsStatus = response.data.co_authors_status;
                    var tableContent = `
                    <table border="1">
                        <thead>
                            <tr>
                            <th>Member Name</th>
                                <th>ISS Membership ID</th>                                
                                <th>RC Subscription</th>
                                <th>Last Payment</th>
                                <th>Same RC as You Have Selected</th>
                                <th>Certificate Eligibility</th>
                            </tr>
                        </thead>
                        <tbody>`;

                    coAuthorsStatus.forEach(function (statusMessage, index) {
                        // Extract co-author membership ID
                        var membershipIdMatch = statusMessage.match(/Membership ID: (\S+)/);
                        var membershipId = membershipIdMatch ? membershipIdMatch[1].replace(/[()]/g, '') : "Unknown";  // Remove brackets


                        // Initialize default status
                        var rcSubscription = '❌';
                        var lastPayment = '❌';
                        var sameRC = '❌';
                        var certificateEligibility = '❌';

                        // Handle "is in Group 4" and "has made last payment" conditions
                        if (statusMessage.includes("is in Group 4")) {
                            rcSubscription = '✔️';
                        }
                        if (statusMessage.includes("has made last payment")) {
                            lastPayment = '✔️';
                        }

                        // Handle "same membership as current user" condition
                        if (statusMessage.includes("has same membership selection as current user")) {
                            sameRC = '✔️';
                        }

                        // Check for missing conditions
                        if (statusMessage.includes("Missing Conditions")) {
                            if (statusMessage.includes("Co-author is not part of Group 4")) {
                                rcSubscription = '❌';
                              
                                
                            }
                            if (statusMessage.includes("Co-author does not have the same membership as the current user")) {
                                sameRC = '❌';
                                lastPayment = '❌'; 
                            }
                            if (!statusMessage.includes("has made last payment") && lastPayment !== '❌') {
                                lastPayment = '✔️';
                            }
                        }

                        // Check for Invalid Membership ID
                        if (statusMessage.includes("Invalid Membership ID")) {
                            rcSubscription = '❌';
                            lastPayment = '❌';
                            sameRC = '❌';
                            certificateEligibility = '❌';
                        }

                        // Determine Certificate Eligibility based on conditions
                        if (rcSubscription === '✔️' && lastPayment === '✔️' && sameRC === '✔️') {
                            certificateEligibility = '✔️';
                        }
                        var coAuthorName = formData.co_authors[index] || "Unknown";
                        // Build the table row for the current co-author
                        tableContent += `
                                <tr>
                                  <td>${coAuthorName}</td>
                                    <td>${membershipId}</td>
                                    
                                    <td>${rcSubscription}</td>
                                    <td>${lastPayment}</td>
                                    <td>${sameRC}</td>
                                    <td>${certificateEligibility}</td>
                                </tr>`;
                    });

                    tableContent += `</tbody></table>`;
                    jQuery("#missing-conditions").html(tableContent).show();

                } else {
                    alert("Error: " + response.message);
                }
            },

            error: function () {
                alert("An error occurred while processing your request.");
            }
        });
    });

    // Close the modal
    jQuery(".close-modal").on("click", function () {
        jQuery("#confirmation-modal").hide();
    });

    // Confirm button clicked
    jQuery("#confirm-details").on("click", function () {
        jQuery("input[type='submit']").show();
        jQuery("#confirmation-modal").hide();
        jQuery('#preview-abstract').hide();
    });

    // Edit button clicked
    jQuery("#edit-details").on("click", function () {
        jQuery("#confirmation-modal").hide();
    });
});
