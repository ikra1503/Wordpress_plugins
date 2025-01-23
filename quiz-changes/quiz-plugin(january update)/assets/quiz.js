jQuery(document).ready(function ($) {
    // Initialize variables
    let questions = [];
    let currentQuestionIndex = 0;
    let userAnswers = {};
    let userData = {};

    // Show loader function
    function showLoader() {
        $('#quiz-popup').append('<div class="loader">Loading...</div>');
    }

    // Hide loader function
    function hideLoader() {
        $('.loader').remove();
    }

    if (sessionStorage.getItem('quizSubmitted') === 'true') {
        $('#start-quiz').hide();
    }




    // Show registration form first
    function showRegistrationForm() {
        $('#quiz-popup').html(`
          
            <h3>User Registration</h3>
            <form id="registration-form">
              <input type="hidden" name="quiz_nonce" value="${quiz_ajax.quiz_nonce}">
                <label for="fullname">Full Name:</label>
                <input type="text" name="username" id="username" required><br><br>
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required><br><br>
                <label for="contact">Contact Number:</label>
                <input type="text" name="contact" id="contact" required><br><br>
                <label for="customer_id">Unique ID:</label>
                <input type="text" name="customer_id" id="customer_id" required><br><br>
                <input type="hidden" id="woo-cust-id">
                <button type="submit" id="submit-registration">Submit</button>
            </form>
        `);
    }

    // Show quiz after registration
    function showQuiz() {
        var selectedCategory = $('#quiz-category-select').val();
        console.log('Selected Category:', selectedCategory);
        showLoader();
        $.ajax({
            url: quiz_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'fetch_quiz_questions',  // Action to trigger the PHP function
                category: selectedCategory // Pass the selected category to the server
            },
            success: function (response) {
                console.log(response);
                questions = response;
                currentQuestionIndex = 0;
                displayQuestion();
                hideLoader();
                startTimer();
            },
            error: function () {
                alert('Error loading quiz questions.');
                hideLoader();
            }
        });
    }

    // Submit registration form via AJAX
    $('#quiz-popup').on('submit', '#registration-form', function (e) {
        e.preventDefault();
        $('#timer').hide();
        // Collect form data
        userData.username = $('#username').val();
        userData.email = $('#email').val();
        userData.contact = $('#contact').val();
        userData.customer_id = $('#customer_id').val();
        showLoader();
        $.ajax({
            url: quiz_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'register_user_for_quiz',
                user_data: userData,
                quiz_nonce: quiz_ajax.quiz_nonce
            },
            success: function (response) {
                if (response.success) {
                    showQuiz();
                    $('#quiz-popup').attr('data-woo-id', response.data.user_id);
                } else {
                    alert(response.data.message);
                }
                hideLoader();
            },
            error: function () {
                alert(response.data.message);
                hideLoader();
            }
        });
    });

    // Display current question
    function displayQuestion() {
        let question = questions[currentQuestionIndex];
        $('#quiz-popup').html(`<h3>${question.question}</h3>`).addClass('custom-quiz-qstn');
        // Check if there is a featured image
        if (question.featured_image) {
            $('#quiz-popup').append(`<img src="${question.featured_image}" alt="${question.question}" class="quiz-question-image" >`);
        }
        question.answers.forEach((answer, index) => {
            $('#quiz-popup').append(`
                <label>
                    <input type="radio" class="quiz-ans" name="answer" value="answer_${index + 1}">
                    ${answer}
                </label><br>
            `);
        });

        if (currentQuestionIndex === questions.length - 1) {
            $('#quiz-popup').append('<button id="submit-quiz">Submit Quiz</button>');
        } else {
            $('#quiz-popup').append('<button id="next-question">Next Question</button>');
        }
        // console.log('Correct answer for this question:', question.correct_answer);
        // console.log("i am going to log hr");

        $('.quiz-ans').on('change', function () {
            // console.log("I am changed");

            // Disable all radio buttons after one is selected
            $('.quiz-ans').prop('disabled', true);

            // Log the selected value
            let selectedValue = $(this).val();
            // console.log('Selected answer:', selectedValue);

            // Get the label of the selected radio button
            let label = $(this).closest('label');
            const correctAudio = new Audio('https://quiz.webtech-evolution.com/wp-content/uploads/2025/01/the-correct-answer-33-183620.mp3');
            const incorrectAudio = new Audio('https://quiz.webtech-evolution.com/wp-content/uploads/2025/01/buzzer-or-wrong-answer-20582.mp3');


            // Add the appropriate class based on the answer
            if (selectedValue === question.correct_answer) {
                label.addClass('correct-answer');
                // console.log('Correct answer selected!');
                correctAudio.play();
                
            } else {
                label.addClass('incorrect-answer');
                // console.log('Incorrect answer selected.');
                incorrectAudio.play();
            }
            $('.quiz-ans').each(function () {
                let ansLabel = $(this).closest('label');
                if ($(this).val() === question.correct_answer) {
                    ansLabel.addClass('actual-correct-answer');
                }
            });

            // Optional: Store the selected value if needed
            question.selectedAnswer = selectedValue;
        });


    }

    // Next Question Button Click
    $('#quiz-popup').on('click', '#next-question', function () {
        let selectedAnswer = $('input[name="answer"]:checked').val();
        if (selectedAnswer) {
            userAnswers[questions[currentQuestionIndex].ID] = selectedAnswer;
            currentQuestionIndex++;
            displayQuestion();
        } else {
            alert('Please select an answer');
        }
    });

    // quiz ertication code
    $('#quiz-popup').on('click', '#submit-quiz', function () {
        showLoader();
        var selectedCategory = $('#quiz-category-select').val();
        let selectedAnswer = $('input[name="answer"]:checked').val();
        userAnswers[questions[currentQuestionIndex].ID] = selectedAnswer;
        let customer_id = userData.customer_id;
        let username = userData.username;
        let usermailsend = userData.email;
        let usercontact = userData.contact;
        let wooId = $('#quiz-popup').attr('data-woo-id');
        console.log(wooId);
        console.log(username);
        // Debugging logs
        console.log('Selected Answer:', selectedAnswer);
        console.log('User Answers:', userAnswers);
        console.log('Customer ID:', customer_id);

        $.ajax({
            url: quiz_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'submit_quiz',
                answers: userAnswers,
                user_id: customer_id,
                selected_category: selectedCategory,
                quiz_nonce: quiz_ajax.quiz_nonce // Include the nonce
            },
            success: function (response) {
                console.log(response); // For debugging
                $('#timer').hide();
                if (response.success && response.data) {
                    let result = response.data;
                    let attempted = result.attempted || 0;
                    let correct = result.correct || 0;
                    let score = result.score || 0;
                    $('#timer').hide();
                    $('#quiz-popup').html(`
                    <h3>Quiz Result</h3>
                    <p>You attempted ${attempted} questions.</p>
                    <p>Correct answers: ${correct}</p>
                    <p>Score: ${score}%</p>
                `);
                    $.ajax({
                        url: quiz_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'generate_coupon_code',
                            score: score,
                            username: username,
                            usercontact: usercontact,
                            quiz_nonce: quiz_ajax.quiz_nonce // Include the nonce
                        },
                        success: function (couponResponse) {
                            // Hide the timer
                            $('#timer').hide();

                            if (couponResponse.success) {
                                // Extract coupon details
                                let couponCode = couponResponse.data.coupon_code;
                                let discount = couponResponse.data.discount;

                                // Append the coupon code message
                                $('#quiz-popup').append(`
                                    <p>
                                        <span class="cong-msg">Congratulations!</span>
                                        <span class="emoji">🎉</span>
                                        Your coupon code is: <strong>${couponCode}</strong>
                                    </p>
                                `);

                                // Append the discount message only if the discount is available
                                if (discount) {
                                    $('#quiz-popup').append(`
                                        <p>You got a <strong>${discount}%</strong> discount!</p>
                                    `);
                                }
                            } else {
                                // Append a message when no coupon is available
                                $('#quiz-popup').append(`
                                    <p>😞 Hard luck!!  Better luck next time! </p>
                                `);
                            }
                        },
                        error: function (xhr, status, error) {
                            console.log('Error fetching coupon code:', error);
                        }
                    });
                    $.ajax({
                        url: quiz_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'generate_certificate',
                            score: score,
                            user_name: username,
                            usermailsend: usermailsend,
                            category: selectedCategory,
                            quiz_nonce: quiz_ajax.quiz_nonce // Include the nonce
                        },
                        success: function (certificateResponse) {
                            if (certificateResponse.success) {
                                let certificateUrl = certificateResponse.data.certificate_url;
                                $('#quiz-popup').append(`
                                    <p>Your certificate is ready!</p>
                                    <a href="${certificateUrl}" target="_blank" download>
                                        <button class="download-certificate">Download Certificate</button>
                                    </a>
                                    <button id="close-popup">Close</button>
                                `);
                            } else {
                                $('#quiz-popup').append(`
                                    <p>Your havent scored enough to get a certificate!!!</p>
                                    <button id="close-popup">Close</button>
                                `);
                            }


                            $(this).hide();
                            sessionStorage.setItem('quizSubmitted', 'true');
                        },
                        error: function (xhr, status, error) {
                            console.log('Error generating certificate:', error);
                        }
                    });
                    $.ajax({
                        url: quiz_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'get_quiz_coin',
                            score: score,
                            wooid: wooId,
                            quiz_nonce: quiz_ajax.quiz_nonce
                        },
                        success: function (response) {
                            console.log('Response:', response); // Check the response
                            if (response.success) {
                                let coins = response.data.coins; // Get the coin value from the response
                                console.log('Coins:', coins); // Log the coin value for debugging

                                // Dynamically append the coin display in the popup container
                                $('#quiz-popup').append(`
                                    <p>Congratulations! You have earned ${coins} coins!</p>
                                    
                                `);
                            } else {
                                console.log('Error:', response.data.message); // Error message if no coins are available
                            }
                        },

                        error: function (xhr, status, error) {
                            console.log('AJAX Error:', error); // Check if the AJAX request fails
                        }
                    });

                } else {
                    $('#quiz-popup').html('<p>Error: Unable to fetch quiz results.</p>');
                }
                hideLoader();
            },
            error: function (xhr, status, error) {
                console.log('AJAX error:', error);
                console.log('Response:', xhr.responseText);
                alert('Error submitting quiz.');
                hideLoader();
            }
        });
    });




    $(document).on('click', '#close-popup', function () {
        $('#quiz-popup').fadeOut();  // Close the popup by fading out
        $('body').removeClass('overlay');  // Remove the overlay class if you have one
    });

    // Open registration form when quiz starts
    $('#start-quiz').on('click', function () {
        $('.quiz-cat-wrap').hide();
        $('#quiz-popup').fadeIn();
        showRegistrationForm();
    });


    //quiz timer
    let quizTimer = 5000000000000000000000000000000000000000;
    let timerInterval;

    // Show the remaining time
    function updateTimer() {
        let minutes = Math.floor(quizTimer / 60);
        let seconds = quizTimer % 60;

        // Check if the timer already exists, if so, just update the text
        if ($('#timer').length === 0) {
            $('#quiz-popup').append(`<p id="timer">Time Remaining: ${minutes}:${seconds < 10 ? '0' + seconds : seconds}</p>`);
        } else {
            $('#timer').text(`Time Remaining: ${minutes}:${seconds < 10 ? '0' + seconds : seconds}`);
        }
    }


    // Start the quiz timer
    function startTimer() {
        quizTimer = 4764647647; // Reset timer to 3 minutes
        updateTimer(); // Display the initial time
        timerInterval = setInterval(function () {
            quizTimer--;
            updateTimer(); // Update the time every second
            if (quizTimer <= 0) {
                clearInterval(timerInterval);
                submitQuizAutomatically(); // Submit the quiz once the timer runs out
            }
        }, 1000); // Update every second
    }

    // Automatically submit the quiz when the timer is up
    function submitQuizAutomatically() {
        let selectedAnswer = $('input[name="answer"]:checked').val();
        if (selectedAnswer) {
            userAnswers[questions[currentQuestionIndex].ID] = selectedAnswer;
        }
        let customer_id = userData.customer_id;
        let username = userData.username;
        let usercontact = userData.contact;
        let wooId = $('#quiz-popup').attr('data-woo-id');
        console.log(wooId);
        console.log(username);
        $('#timer').hide();
        $.ajax({
            url: quiz_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'submit_quiz',
                answers: userAnswers,
                user_id: customer_id,
                quiz_nonce: quiz_ajax.quiz_nonce // Include the nonce
            },
            success: function (response) {
                $('#timer').hide();
                console.log(response); // For debugging
                if (response.success && response.data) {
                    let result = response.data;
                    let attempted = result.attempted || 0;
                    let correct = result.correct || 0;
                    let score = result.score || 0;

                    $('#quiz-popup').html(`
                    <h3>Quiz Result</h3>
                    <p>You attempted ${attempted} questions.</p>
                    <p>Correct answers: ${correct}</p>
                    <p>Score: ${score}%</p>
                `);
                    $('#timer').hide();
                    $.ajax({
                        url: quiz_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'generate_coupon_code',
                            score: score,
                            username: username,
                            usercontact: usercontact,
                            quiz_nonce: quiz_ajax.quiz_nonce // Include the nonce
                        },
                        success: function (couponResponse) {
                            // Hide the timer
                            $('#timer').hide();

                            if (couponResponse.success) {
                                // Extract coupon details
                                let couponCode = couponResponse.data.coupon_code;
                                let discount = couponResponse.data.discount;

                                // Append the coupon code message
                                $('#quiz-popup').append(`
                                    <p>
                                        <span class="cong-msg">Congratulations!</span>
                                        <span class="emoji">🎉</span>
                                        Your coupon code is: <strong>${couponCode}</strong>
                                    </p>
                                `);

                                // Append the discount message only if the discount is available
                                if (discount) {
                                    $('#quiz-popup').append(`
                                        <p>You got a <strong>${discount}%</strong> discount!</p>
                                    `);
                                }
                            } else {
                                // Append a message when no coupon is available
                                $('#quiz-popup').append(`
                                    <p>😞 Hard luck!!  Better luck next time! </p>
                                `);
                            }
                        },
                        error: function (xhr, status, error) {
                            console.log('Error fetching coupon code:', error);
                        }
                    });

                    $.ajax({
                        url: quiz_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'generate_certificate',
                            score: score,
                            user_name: username,
                            quiz_nonce: quiz_ajax.quiz_nonce // Include the nonce
                        },
                        success: function (certificateResponse) {
                            if (certificateResponse.success) {
                                let certificateUrl = certificateResponse.data.certificate_url;
                                $('#timer').hide();
                                $('#quiz-popup').append(`
                                <p>Your certificate is ready!</p>
                                <a href="${certificateUrl}" target="_blank" download>
                                    <button class="download-certificate">Download Certificate</button>
                                </a>
                                <button id="close-popup">Close</button>
                            `);
                            } else {
                                $('#timer').hide();
                                $('#quiz-popup').append(`
                                <p>Your haven't scored enough to get a certificate!!!</p>
                                <button id="close-popup">Close</button>
                            `);
                            }
                            sessionStorage.setItem('quizSubmitted', 'true');
                        },
                        error: function (xhr, status, error) {
                            console.log('Error generating certificate:', error);
                        }
                    });
                    $.ajax({
                        url: quiz_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'get_quiz_coin',
                            score: score,
                            wooid: wooId,
                            quiz_nonce: quiz_ajax.quiz_nonce
                        },
                        success: function (response) {
                            console.log('Response:', response); // Check the response
                            if (response.success) {
                                let coins = response.data.coins; // Get the coin value from the response
                                console.log('Coins:', coins); // Log the coin value for debugging

                                // Dynamically append the coin display in the popup container
                                $('#quiz-popup').append(`
                                    <p>Congratulations! You have earned ${coins} coins!</p>
                                    <button id="close-popup">Close</button>
                                `);
                            } else {
                                console.log('Error:', response.data.message); // Error message if no coins are available
                            }
                        },

                        error: function (xhr, status, error) {
                            console.log('AJAX Error:', error); // Check if the AJAX request fails
                        }
                    });
                } else {
                    $('#quiz-popup').html('<p>Error: Unable to fetch quiz results.</p>');
                }
                hideLoader();
            },
            error: function (xhr, status, error) {
                console.log('AJAX error:', error);
                console.log('Response:', xhr.responseText);
                alert('Error submitting quiz.');
                hideLoader();
            }
        });
    }

});

