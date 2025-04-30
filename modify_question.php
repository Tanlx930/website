<?php
// Start the session
session_start();

// MySQL database connection
$servername = "localhost";
$username = "root"; // replace with your MySQL username
$password = ""; // replace with your MySQL password
$dbname = "rwdd";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the user is logged in
if (!isset($_COOKIE['username'])) {
    header("Location: login.php");
    exit();
}
$username = $_COOKIE['username'];

// Get the username from session
$username = $_SESSION['username'];

// Fetch user details
$user_sql = "SELECT user_id, role, profile_pic FROM users WHERE username = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$user_id = $user['user_id'];
$db_profile_pic = $user['profile_pic'];
$user_role = $user['role'];

// Fetch quiz_id from the URL or default to 1
$quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 1;

// Fetch quiz questions and answers
$questions_sql = "SELECT * FROM questions WHERE quiz_id = ?";
$stmt = $conn->prepare($questions_sql);
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$questions_result = $stmt->get_result();
$questions = [];
while ($row = $questions_result->fetch_assoc()) {
    $question_id = $row['question_id'];
    $answers_sql = "SELECT * FROM answers WHERE question_id = ?";
    $answer_stmt = $conn->prepare($answers_sql);
    $answer_stmt->bind_param("i", $question_id);
    $answer_stmt->execute();
    $answer_result = $answer_stmt->get_result();
    $answers = [];
    while ($answer_row = $answer_result->fetch_assoc()) {
        $answers[] = $answer_row;
    }
    $questions[] = [
        'question_id' => $question_id,
        'question_text' => $row['question_text'],
        'question_type' => $row['question_type'],
        'answers' => $answers
    ];
}

// Handle form submission (save questions and answers)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update existing questions and answers
    if (isset($_POST['action']) && $_POST['action'] == 'update') {
        foreach ($questions as $question) {
            $question_id = $question['question_id'];
            
            // Update question text
            if (isset($_POST['question_' . $question_id])) {
                $new_question_text = $_POST['question_' . $question_id];
                $update_question_sql = "UPDATE questions SET question_text = ? WHERE question_id = ?";
                $stmt = $conn->prepare($update_question_sql);
                $stmt->bind_param("si", $new_question_text, $question_id);
                $stmt->execute();
            }
    
            // Update answers
            foreach ($question['answers'] as $answer) {
                $answer_id = $answer['answer_id'];
                if (isset($_POST['answer_' . $answer_id])) {
                    $answer_value = $_POST['answer_' . $answer_id];
                    $is_correct = isset($_POST['correct_' . $answer_id]) ? 1 : 0;
    
                    $update_answer_sql = "UPDATE answers SET answer_text = ?, is_correct = ? WHERE answer_id = ?";
                    $stmt = $conn->prepare($update_answer_sql);
                    $stmt->bind_param("sii", $answer_value, $is_correct, $answer_id);
                    $stmt->execute();
                }
            }
        }
    
        $_SESSION['message'] = "Questions updated successfully!";
        header("Location: modify_question.php?quiz_id=" . $quiz_id);
        exit();
    }
    

    // Handle deleting a question
    if (isset($_POST['delete_question_id'])) {
        $delete_question_id = $_POST['delete_question_id'];

        // Delete answers for the question first
        $delete_answers_sql = "DELETE FROM answers WHERE question_id = ?";
        $stmt = $conn->prepare($delete_answers_sql);
        $stmt->bind_param("i", $delete_question_id);
        $stmt->execute();

        // Delete the question itself
        $delete_question_sql = "DELETE FROM questions WHERE question_id = ?";
        $stmt = $conn->prepare($delete_question_sql);
        $stmt->bind_param("i", $delete_question_id);
        $stmt->execute();

        // Redirect after deleting the question
        $_SESSION['message'] = "Question deleted successfully!";
        header("Location: modify_question.php?quiz_id=" . $quiz_id);
        exit();
    }
}

// Handle adding a new question
if (isset($_POST['new-question-text'])) {
    $new_question_text = $_POST['new-question-text'];
    $new_question_type = $_POST['new-question-type']; // New question type
    $answers = isset($_POST['answers']) ? $_POST['answers'] : []; // Answers array
    $correct_answer = isset($_POST['correct-answer']) ? intval($_POST['correct-answer']) : 0; // Correct answer index

    // Insert the new question into the database, include quiz_id
    $question_sql = "INSERT INTO questions (quiz_id, question_text, question_type) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($question_sql);
    $stmt->bind_param("iss", $quiz_id, $new_question_text, $new_question_type);
    $stmt->execute();
    $new_question_id = $stmt->insert_id; // Get the ID of the newly inserted question

    // Insert the answers for the new question
    foreach ($answers as $index => $answer) {
        $is_correct = ($correct_answer === ($index + 1)) ? 1 : 0; // Mark the correct answer
        $answer_sql = "INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)";
        $answer_stmt = $conn->prepare($answer_sql);
        $answer_stmt->bind_param("isi", $new_question_id, $answer, $is_correct);
        $answer_stmt->execute();
    }

    // Redirect or show success message
    $_SESSION['message'] = "New question added successfully!";
    header("Location: modify_question.php?quiz_id=" . $quiz_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modify Quiz - MathStorm</title>
    <style>
        /* General Reset */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: Arial, sans-serif;
  background-color: #f4f7f6;
  color: #343a40;
  line-height: 1.6;
}

/* Navbar */
.navbar {
    display: flex;
    align-items: center;
    padding: 10px 20px;
    background-color: #4CAF50;
    border-bottom: 1px solid #ddd;
    gap: 20px;
}

.nav-item {
    color: white;
    text-decoration: none;
    font-weight: bold;
}

.nav-item.active,
.nav-item:hover {
    color: #007BFF;
}

/* Profile Dropdown */
.profile-icon {
    margin-left: auto;
    display: flex;
    align-items: center;
}

.profile-pic {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
}

.dropdown-menu {
    display: none;
    position: absolute;
    top: 50px;
    right: 0;
    background-color: white;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    width: 150px;
    text-align: left;
}

.dropdown-menu a {
    display: block;
    padding: 10px;
    text-decoration: none;
    color: #333;
    font-size: 14px;
}

.dropdown-menu a:hover {
    background-color: #f4f4f4;
    color: #4CAF50;
}

.profile-dropdown.active .dropdown-menu {
    display: block;
}
/* Main Content Styling */
main {
  max-width: 1200px;
  margin: 20px auto;
  padding: 20px;
  background-color: #ffffff;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.quiz-container h2 {
  font-size: 1.8em;
  margin-bottom: 20px;
  text-align: center;
  color: #343a40;
}

.question-container {
  margin-bottom: 20px;
  padding: 10px;
  border-bottom: 1px solid #ddd;
}

.question-container h3 {
  font-size: 1.4em;
  color: #495057;
}

.question-container input[type="text"],
.question-container select {
  width: 100%;
  padding: 8px;
  margin: 5px 0;
  border: 1px solid #ccc;
  border-radius: 5px;
}

/* Answers Section */
.answers-container {
  margin-top: 10px;
}

.answer-container {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
}

.answer-container input[type="text"] {
  flex: 1;
  margin-right: 10px;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 5px;
}

.answer-container input[type="checkbox"] {
  margin-left: 10px;
}

.delete-btn {
  padding: 8px 15px;
  background-color: #f44336;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 1rem;
  margin-top: 10px;
}

.delete-btn:hover {
  background-color: #e32f2f;
}

/* Add Question Button */
#new-question-btn {
  display: block;
  margin: 20px auto;
  padding: 12px 20px;
  background-color: #28a745;
  color: white;
  border: none;
  border-radius: 5px;
  font-size: 1.1em;
  text-align: center;
  cursor: pointer;
}

#new-question-btn:hover {
  background-color: #218838;
}

/* Back Button */
.back-btn,
.submit-btn {
  padding: 10px 20px;
  background-color: #4CAF50;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 16px;
  margin-top: 20px;
}

.back-btn:hover,
.submit-btn:hover {
  background-color: #45a049;
}

/* Modal Styling */
.modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  overflow: auto;
  z-index: 1000;
}

.modal-content {
  margin: 5% auto;
  padding: 20px;
  background-color: white;
  border-radius: 5px;
  width: 90%;
  max-width: 600px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.modal-content h2 {
  font-size: 1.5em;
  color: #343a40;
}

.modal-content input[type="text"],
.modal-content select {
  width: 100%;
  padding: 8px;
  margin: 10px 0;
  border: 1px solid #ccc;
  border-radius: 5px;
}

.modal-content button {
  padding: 10px 20px;
  background-color: #28a745;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 1rem;
  margin-top: 10px;
}

.modal-content button:hover {
  background-color: #218838;
}

.close-btn {
  float: right;
  font-size: 1.5em;
  color: #aaa;
  cursor: pointer;
}

.close-btn:hover {
  color: black;
}

/* Responsive Design */
@media (max-width: 768px) {

  .question-container h3,
  .quiz-container h2 {
    text-align: center;
  }

  .answer-container {
    flex-direction: column;
    align-items: flex-start;
  }

  .answer-container input[type="checkbox"] {
    margin-left: 0;
    margin-top: 5px;
  }

  #new-question-btn {
    width: 100%;
    padding: 12px;
  }

  .delete-btn,
  .back-btn {
    width: 100%;
    padding: 12px;
  }
}

@media (max-width: 480px) {
  .quiz-container h2 {
    font-size: 1.5em;
  }

  .question-container h3 {
    font-size: 1.2em;
  }

  .modal-content {
    padding: 15px;
  }
}

    </style>
</head>
<body>
<header class="navbar">
    <a href="homepage.php" class="nav-item">HOME</a>
    
    <?php if ($user_role == 'Teacher'): ?>
        <a href="quiz.php" class="nav-item active">MODIFY QUESTIONS</a>
        <a href="leaderboard.php" class="nav-item">LEADERBOARD</a>
        <a href="chat.php" class="nav-item">CHAT</a>
    <?php else: ?>
        <a href="quiz.php" class="nav-item active">QUIZZES</a>
        <a href="leaderboard.php" class="nav-item">LEADERBOARD</a>
        <a href="chat.php" class="nav-item">CHAT</a>
    <?php endif; ?>

    <div class="profile-icon">
      <div class="profile-dropdown">
        <img src="<?= htmlspecialchars($db_profile_pic) ?>" alt="Profile Picture" class="profile-pic" onclick="toggleProfileMenu()">
        <div class="dropdown-menu">
            <?php if ($user_role == 'Teacher'): ?>
                <a href="manage_user.php">Manage Users</a>
                <a href="manage_profile.php">Manage Profile</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="manage_profile.php">Manage Profile</a>
                <a href="logout.php">Logout</a>
            <?php endif; ?>
        </div>
      </div>
    </div>
</header>
<main class="quiz-main">
    <div class="quiz-container">
        <h2>Modify Quiz: <?= htmlspecialchars($quiz_id) ?></h2>
        <form id="modify-quiz-form" method="POST" action="modify_question.php?quiz_id=<?= $quiz_id ?>">
    <?php foreach ($questions as $index => $question): ?>
        <div class="question-container">
            <h3><?php echo $index + 1; ?>. 
                <input type="text" name="question_<?= $question['question_id'] ?>" 
                       value="<?= htmlspecialchars($question['question_text']); ?>" 
                       class="question-text" />
            </h3>

            <!-- Dropdown for question type -->
            <label for="question_type_<?= $question['question_id'] ?>">Question Type:</label>
            <select name="question_type_<?= $question['question_id'] ?>" 
                    id="question_type_<?= $question['question_id'] ?>" 
                    required>
                <option value="mcq" <?= $question['question_type'] == 'mcq' ? 'selected' : ''; ?>>Multiple Choice</option>
                <option value="checkbox" <?= $question['question_type'] == 'checkbox' ? 'selected' : ''; ?>>Checkbox</option>
                <option value="fill_in_the_blank" <?= $question['question_type'] == 'fill_in_the_blank' ? 'selected' : ''; ?>>Fill in the Blank</option>
                <option value="short_answer" <?= $question['question_type'] == 'short_answer' ? 'selected' : ''; ?>>Short Answer</option>
            </select>

            <!-- Answer options -->
            <div class="answers-container" id="answers-container-<?= $question['question_id'] ?>">
                <?php if (!empty($question['answers'])): ?>
                    <?php foreach ($question['answers'] as $answer): ?>
                        <div class="answer-container">
                            <label for="answer_<?= $answer['answer_id'] ?>">Answer:</label>
                            <input type="text" 
                                   name="answer_<?= $answer['answer_id'] ?>" 
                                   id="answer_<?= $answer['answer_id'] ?>" 
                                   value="<?= htmlspecialchars($answer['answer_text']); ?>" 
                                   required />
                            <label for="correct_<?= $answer['answer_id'] ?>">
                                <input type="checkbox" 
                                       name="correct_<?= $answer['answer_id'] ?>" 
                                       id="correct_<?= $answer['answer_id'] ?>" 
                                       <?= $answer['is_correct'] ? 'checked' : ''; ?> />
                                Correct Answer
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No answers available for this question.</p>
                <?php endif; ?>
            </div>

            <!-- Delete Question Button -->
            <button type="submit" name="delete_question_id" value="<?= $question['question_id'] ?>" class="delete-btn">Delete Question</button>
        </div>
    <?php endforeach; ?>

    <!-- Submit button -->
    <button type="submit" name="action" value="update" class="submit-btn">Save All Changes</button>
</form>



    <!-- Add New Question Button -->
    <button id="new-question-btn">Add New Question</button>
    <!-- New Question Modal -->
    <div id="new-question-modal" class="modal">
          <div class="modal-content">
              <span class="close-btn">&times;</span>
              <h2>Add New Question</h2>
              <form method="POST" action="modify_question.php?quiz_id=<?= $quiz_id ?>">
                  <label for="new-question-text">Question Text:</label>
                  <input type="text" id="new-question-text" name="new-question-text" required>

                  <label for="new-question-type">Question Type:</label>
                  <select id="new-question-type" name="new-question-type" required onchange="updateNewAnswers()">
                      <option value="mcq">Multiple Choice</option>
                      <option value="checkbox">Checkbox</option>
                      <option value="fill_in_the_blank">Fill in the Blank</option>
                      <option value="short_answer">Short Answer</option>
                  </select>

                  <div id="new-answer-container">
                      <!-- Dynamic answer inputs will be added here -->
                  </div>

                  <button type="submit" class="submit-btn">Add Question</button>
              </form>
          </div>
      </div>
    </div>
    <!-- Back Button -->
    <div>
        <button class="back-btn" onclick="goBack()">Back</button>
    </div>

</main>

<script>
    function goBack() {
        window.history.back(); // Navigate to the previous page in browser history
    }

    // Initialize answers for existing questions based on their types
    <?php foreach ($questions as $question): ?>
        //updateAnswers(<?= $question['question_id'] ?>);
    <?php endforeach; ?>

    // Function to update answers based on the selected question type
    function updateAnswers(question_id) {
    const questionType = document.querySelector(`#question_type_${question_id}`).value;
    const answersContainer = document.querySelector(`#answers-container-${question_id}`);
    
    // Do not overwrite if answers are already present
    if (answersContainer.children.length > 0) {
        console.log(`Answers already exist for question ${question_id}. Skipping update.`);
        return;
    }

    // If no answers exist, populate based on the question type
    answersContainer.innerHTML = ''; // Clear the container

    if (questionType === 'mcq' || questionType === 'checkbox') {
        for (let i = 0; i < 4; i++) {
            answersContainer.innerHTML += `
                <div class="answer-container">
                    <label for="answer-${question_id}-${i}">Answer ${i + 1}:</label>
                    <input type="text" name="answer_${question_id}[]" id="answer-${question_id}-${i}" required>
                    <label for="correct_${question_id}_${i}">
                        <input type="checkbox" name="correct_${question_id}[]" id="correct_${question_id}_${i}">
                        Correct Answer
                    </label>
                </div>
            `;
        }
    } else if (questionType === 'fill_in_the_blank' || questionType === 'short_answer') {
        answersContainer.innerHTML = `
            <div class="answer-container">
                <label for="answer-${question_id}">Answer:</label>
                <input type="text" name="answer_${question_id}[]" id="answer-${question_id}" required>
            </div>
        `;
    }
}


    // Function to update answers based on the selected question type for the new question modal
    function updateNewAnswers() {
    const questionType = document.getElementById('new-question-type').value;
    const answersContainer = document.getElementById('new-answer-container');
    answersContainer.innerHTML = ''; // Clear any existing answer fields

    if (questionType === 'mcq' || questionType === 'checkbox') {
        for (let i = 0; i < 4; i++) {
            const answerDiv = document.createElement('div');
            answerDiv.classList.add('answer-container');
            answerDiv.innerHTML = `
                <label for="answer-${i}">Answer ${i + 1}:</label>
                <input type="text" name="answers[]" id="answer-${i}" required>
                <label for="correct_${i}">
                    <input type="checkbox" name="correct-answer" value="${i + 1}" id="correct_${i}">
                    Correct Answer
                </label>
            `;
            answersContainer.appendChild(answerDiv);
        }
    } else if (questionType === 'fill_in_the_blank' || questionType === 'short_answer') {
        const answerDiv = document.createElement('div');
        answerDiv.classList.add('answer-container');
        answerDiv.innerHTML = `
            <label for="answer-0">Answer:</label>
            <input type="text" name="answers[]" id="answer-0" required>
        `;
        answersContainer.appendChild(answerDiv);
    }
}


    // Initialize the answer fields when the modal is first opened (for default selection)
    document.getElementById('new-question-type').addEventListener('change', updateNewAnswers);

    // Automatically populate answers on page load
    updateNewAnswers();

        // Open the modal when the "Add New Question" button is clicked
    document.getElementById('new-question-btn').addEventListener('click', function () {
        const modal = document.getElementById('new-question-modal');
        if (modal) {
            modal.style.display = 'block'; // Display the modal
        } else {
            console.error("Modal element not found. Please check the modal ID.");
        }
    });

    // Close the modal when the close button is clicked
    document.querySelector('.close-btn').addEventListener('click', function () {
        const modal = document.getElementById('new-question-modal');
        if (modal) {
            modal.style.display = 'none'; // Hide the modal
        } else {
            console.error("Modal element not found. Please check the modal ID.");
        }
    });

    // Close the modal when clicking outside of the modal content
    window.addEventListener('click', function (event) {
        const modal = document.getElementById('new-question-modal');
        if (event.target === modal) {
            modal.style.display = 'none'; // Hide the modal when clicking outside
        }
    });

    function toggleProfileMenu() {
        const dropdown = document.querySelector('.profile-dropdown');
        dropdown.classList.toggle('active');
    }

</script>

</body>
</html>
