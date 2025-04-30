<?php
// Start the session
session_start();

// Retrieve username from cookie or session
if (!isset($_COOKIE['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_COOKIE['username'];
$_SESSION['username'] = $username; // Ensure session consistency

// MySQL database connection
$servername = "localhost";
$db_username = "root"; // Replace with your MySQL username
$password = ""; // Replace with your MySQL password
$dbname = "rwdd";

// Create connection
$conn = new mysqli($servername, $db_username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get attempt ID from the URL
$attempt_id = isset($_GET['attempt_id']) ? intval($_GET['attempt_id']) : 0;

// Fetch user details from the database
$stmt = $conn->prepare("SELECT user_id, role, profile_pic FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows === 0) {
    // If user is not found, redirect to login page
    header("Location: login.php");
    exit();
}

$user = $user_result->fetch_assoc();
$db_profile_pic = $user['profile_pic'];
$user_role = $user['role']; // Either 'Teacher' or 'Student'


if ($attempt_id == 0) {
    die("Invalid attempt ID.");
}

// Fetch questions, user responses, and correct answers for this attempt
$questions_sql = "
    SELECT q.question_id, q.question_text, q.question_type, 
           r.response_text, r.is_answer_correct,
           GROUP_CONCAT(a.answer_text SEPARATOR ', ') AS correct_answers
    FROM questions q
    LEFT JOIN responses r ON q.question_id = r.question_id AND r.attempt_id = ?
    LEFT JOIN answers a ON q.question_id = a.question_id AND a.is_correct = 1
    WHERE r.attempt_id = ?
    GROUP BY q.question_id
";
$stmt = $conn->prepare($questions_sql);
$stmt->bind_param("ii", $attempt_id, $attempt_id);
$stmt->execute();
$questions_result = $stmt->get_result();

if ($questions_result->num_rows === 0) {
    die("No questions found for this attempt.");
}

// Fetch all questions and responses
$questions = [];
while ($row = $questions_result->fetch_assoc()) {
    $questions[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Questions Results</title>
    <style>
        body {
  font-family: Arial, sans-serif;
  margin: 0;
  padding: 0;
  background-color: #f4f4f9;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: #4CAF50;
  padding: 15px;
  color: white;
}

.header img {
  height: 50px;
}

.nav a {
  color: white;
  text-decoration: none;
  margin-left: 15px;
}

.questions-container {
  max-width: 800px;
  margin: 30px auto;
  background: white;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.title {
  text-align: center;
  margin-bottom: 20px;
}

.summary {
  background-color: #f9f9f9;
  padding: 15px;
  border: 1px solid #ddd;
  border-radius: 5px;
  margin-bottom: 20px;
}

.question-card {
  margin-bottom: 15px;
  padding: 15px;
  border: 1px solid #ddd;
  border-radius: 5px;
  background-color: #f9f9f9;
}

.question p {
  margin: 5px 0;
}

.status {
  text-align: right;
  margin-top: 10px;
}

.correct {
  color: green;
  font-weight: bold;
}

.incorrect {
  color: red;
  font-weight: bold;
}

.chat-button {
  position: fixed;
  bottom: 20px;
  right: 20px;
  background-color: #4CAF50;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 50%;
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
</head>
<body>

<header class="navbar">
    <a href="homepage.php" class="nav-item active">HOME</a>
    
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

<main class="questions-container">
    <h1 class="title">View Your Responses</h1>
    <?php foreach ($questions as $question): ?>
        <div class="question-card">
            <p><strong>Question:</strong> <?php echo htmlspecialchars($question['question_text']); ?></p>
            <p><strong>Your Response:</strong> <?php echo htmlspecialchars($question['response_text'] ?? 'No response'); ?></p>
            <p><strong>Correct Answer(s):</strong> <?php echo htmlspecialchars($question['correct_answers']); ?></p>
            <p><strong>Status:</strong> 
                <?php if ($question['is_answer_correct'] === null || $question['is_answer_correct'] === ''): ?>
                    Not Answered
                <?php elseif ($question['is_answer_correct'] == 1): ?>
                    <span style="color: green;">Correct</span>
                <?php else: ?>
                    <span style="color: red;">Incorrect</span>
                <?php endif; ?>
            </p>
        </div>
    <?php endforeach; ?>
</main>
<script>
    function toggleProfileMenu() {
    const dropdown = document.querySelector('.profile-dropdown');
    dropdown.classList.toggle('active');
}

</script>
</body>
</html>

<?php
// Close connection
$conn->close();
?>
