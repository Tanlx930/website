<?php
// Start the session
session_start();

// MySQL database connection
$servername = "localhost";
$username = "root"; // Replace with your MySQL username
$password = ""; // Replace with your MySQL password
$dbname = "rwdd";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Get the username from session
$username = $_SESSION['username'];

// Get the quiz_id from the URL
if (!isset($_GET['quiz_id'])) {
    die("Invalid request: quiz_id is missing.");
}

$quiz_id = intval($_GET['quiz_id']); // Ensure quiz_id is an integer

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

// Debug logging
error_log("Fetching or creating attempt for user_id: $user_id, quiz_id: $quiz_id");

// Function to get or create a new attempt for students
function getOrCreateAttempt($conn, $user_id, $quiz_id) {
    // Check if an active attempt exists for this user and quiz
    $stmt = $conn->prepare("SELECT attempt_id FROM attempts WHERE user_id = ? AND quiz_id = ? AND end_time IS NULL");
    $stmt->bind_param("ii", $user_id, $quiz_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Return the existing attempt_id
        $row = $result->fetch_assoc();
        return $row['attempt_id'];
    } else {
        // Create a new attempt if no active attempt exists
        $stmt = $conn->prepare("INSERT INTO attempts (user_id, quiz_id, start_time) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $user_id, $quiz_id);
        $stmt->execute();
        return $stmt->insert_id;
    }
}

// Call getOrCreateAttempt ONLY ONCE at the beginning
$attempt_id = getOrCreateAttempt($conn, $user_id, $quiz_id);

// Fetch questions for the quiz
$questions_sql = "
    SELECT q.question_id, q.question_text, q.question_type
    FROM questions q
    WHERE q.quiz_id = ?
    ORDER BY q.question_id
";
$stmt = $conn->prepare($questions_sql);
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$questions_result = $stmt->get_result();

if ($questions_result->num_rows === 0) {
    echo "<p>No questions found for this quiz.</p>";
    echo '<a href="quiz.php" class="back-link">Return to Quizzes</a>';
    exit();
}

$questions = $questions_result->fetch_all(MYSQLI_ASSOC);

// Fetch all answers for the questions
$answers = [];
$answers_sql = "
    SELECT question_id, answer_text, is_correct
    FROM answers
    WHERE question_id IN (" . implode(',', array_column($questions, 'question_id')) . ")
";
$answers_result = $conn->query($answers_sql);
while ($row = $answers_result->fetch_assoc()) {
    $answers[$row['question_id']][] = [
        'text' => $row['answer_text'],
        'is_correct' => $row['is_correct']
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($questions as $question) {
        $question_id = $question['question_id'];
        $response = isset($_POST['response_' . $question_id]) 
            ? (is_array($_POST['response_' . $question_id]) 
                ? $_POST['response_' . $question_id] 
                : [$_POST['response_' . $question_id]]) 
            : null;

        // Default to incorrect
        $is_correct = 0;

        if ($response !== null && isset($answers[$question_id])) {
            $correct_answers = array_filter($answers[$question_id], fn($ans) => $ans['is_correct'] == 1);
            $correct_texts = array_column($correct_answers, 'text');

            if ($question['question_type'] === 'checkbox') {
                // For checkboxes, the response must match exactly the correct answers
                $is_correct = !array_diff($response, $correct_texts) && !array_diff($correct_texts, $response) ? 1 : 0;
            } elseif ($question['question_type'] === 'mcq' || $question['question_type'] === 'text') {
                // For MCQ or text inputs, check if the response matches a correct answer
                $is_correct = in_array(implode(', ', $response), $correct_texts) ? 1 : 0;
            }
        }

        // Save the response in the database
        $response_text = is_array($response) ? implode(', ', $response) : $response;
        $stmt = $conn->prepare("
            INSERT INTO responses (attempt_id, question_id, response_text, is_answer_correct)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE response_text = VALUES(response_text), is_answer_correct = VALUES(is_answer_correct)
        ");
        $stmt->bind_param("iisi", $attempt_id, $question_id, $response_text, $is_correct);
        $stmt->execute();
    }

    // Update the end time of the attempt
    $update_attempt_sql = "UPDATE attempts SET end_time = NOW() WHERE attempt_id = ?";
    $stmt = $conn->prepare($update_attempt_sql);
    $stmt->bind_param("i", $attempt_id);
    $stmt->execute();

    // Redirect to a thank-you page or quiz summary
    header("Location: result.php?attempt_id=$attempt_id");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attempt Quiz - MathStorm</title>
  <style>
      body {
          font-family: Arial, sans-serif;
          margin: 0;
          padding: 0;
          background-color: #f4f4f9;
      }

      .header {
          background-color: #4CAF50;
          padding: 20px;
          color: white;
          display: flex;
          justify-content: space-between;
          align-items: center;
      }

      .header h1 {
          margin: 0;
      }

      .header nav a {
          margin-left: 20px;
          color: white;
          text-decoration: none;
      }

      .quiz-container {
          max-width: 800px;
          margin: 50px auto;
          padding: 20px;
          background: white;
          border-radius: 10px;
          box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      }

      .title {
          text-align: center;
          margin-bottom: 20px;
      }

      .question-card {
          margin-bottom: 15px;
          padding: 15px;
          border: 1px solid #ddd;
          border-radius: 5px;
          background-color: #f9f9f9;
      }

      .question-card p {
          margin: 5px 0;
      }

      .submit-btn {
          display: inline-block;
          margin: 20px 0;
          padding: 10px 15px;
          background-color: #4CAF50;
          color: white;
          text-decoration: none;
          border-radius: 5px;
          border: none;
          cursor: pointer;
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

    /* Responsive Design */
    @media (max-width: 768px) {


        .cta-buttons {
          flex-direction: column;
          gap: 10px;
        }

        .Paragraph p {
          font-size: 16px;
          width: 90%;
        }

        .Explain h2 {
          font-size: 24px;
        }

        .Explain p {
          font-size: 16px;
        }
      }

  </style>
    <script>
        function toggleProfileMenu() {
            const dropdown = document.querySelector('.profile-dropdown');
            dropdown.classList.toggle('active');
        }

    </script>
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

<main class="quiz-container">
    <h1 class="title">Attempt Quiz</h1>
    <form method="POST">
        <?php foreach ($questions as $question): ?>
        <div class="question-card">
            <p><strong>Question:</strong> <?php echo htmlspecialchars($question['question_text']); ?></p>
            <?php if ($question['question_type'] === 'mcq' || $question['question_type'] === 'checkbox'): ?>
                <?php if (isset($answers[$question['question_id']])): ?>
                    <?php foreach ($answers[$question['question_id']] as $option): ?>
                        <label>
                            <input type="<?php echo $question['question_type'] === 'mcq' ? 'radio' : 'checkbox'; ?>"
                                   name="response_<?php echo $question['question_id']; ?>[]" 
                                   value="<?php echo htmlspecialchars($option['text']); ?>">
                            <?php echo htmlspecialchars($option['text']); ?>
                        </label><br>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No options available for this question.</p>
                <?php endif; ?>
            <?php else: ?>
                <input type="text" name="response_<?php echo $question['question_id']; ?>" placeholder="Your Answer">
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <button type="submit" class="submit-btn">Submit Quiz</button>
    </form>
</main>
</body>
</html>

<?php
// Close connection
$conn->close();
?>
