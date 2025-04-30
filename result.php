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

// Fetch the start_time and end_time from the attempts table
$attempt_sql = "SELECT start_time, end_time FROM attempts WHERE attempt_id = ?";
$stmt = $conn->prepare($attempt_sql);
$stmt->bind_param("i", $attempt_id);
$stmt->execute();
$attempt_result = $stmt->get_result();

if ($attempt_result->num_rows === 0) {
    die("No such attempt found.");
}

$attempt = $attempt_result->fetch_assoc();
$start_time = strtotime($attempt['start_time']);
$end_time = strtotime($attempt['end_time']);

if ($end_time === false || $start_time === false) {
    die("Invalid start or end time in the database.");
}

// Calculate completion time in seconds
$total_time = $end_time - $start_time;

// Format completion time in H:i:s format
$completion_time = gmdate("H:i:s", $total_time);

// Fetch correct answers and total questions for this attempt
$responses_sql = "
    SELECT COUNT(*) AS total_questions,
           COUNT(CASE WHEN is_answer_correct = 1 THEN 1 END) AS correct_answers
    FROM responses
    WHERE attempt_id = ?
";
$stmt = $conn->prepare($responses_sql);
$stmt->bind_param("i", $attempt_id);
$stmt->execute();
$responses_result = $stmt->get_result();

// Initialize counters
$total_questions = 0;
$correct_answers = 0;

if ($response_row = $responses_result->fetch_assoc()) {
    $total_questions = intval($response_row['total_questions']);
    $correct_answers = intval($response_row['correct_answers']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - MathStorm</title>
    <style>
        /* Basic reset and styling */
        * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        }

        body {
        font-family: Arial, sans-serif;
        background-color: #f7f7f7;
        }

        /* Header styling */
        .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #3f51b5;
        color: white;
        padding: 15px 30px;
        }

        .logo {
        width: 50px;
        height: auto;
        }

        .nav {
        display: flex;
        gap: 30px;
        }

        .nav-link {
        text-decoration: none;
        color: white;
        font-weight: bold;
        transition: color 0.3s;
        }

        .nav-link:hover {
        color: #ffeb3b;
        }

        .nav-link.active {
        color: #ffeb3b;
        }

        /* Main result container */
        .result-container {
        display: flex;
        justify-content: center;
        padding: 40px;
        }

        .result-card {
        background-color: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border-radius: 10px;
        width: 500px;
        padding: 30px;
        text-align: center;
        }

        .result-title {
        font-size: 28px;
        color: #3f51b5;
        margin-bottom: 20px;
        }

        .result-stats {
        margin-bottom: 30px;
        }

        .stat {
        font-size: 18px;
        margin: 10px 0;
        color: #333;
        }

        .stat .value {
        font-size: 24px;
        font-weight: bold;
        color: #2196f3;
        }

        .stat .label {
        font-size: 14px;
        color: #888;
        }

        /* Button Styling */
        .actions {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        }

        .action-btn {
        padding: 12px 20px;
        border-radius: 5px;
        color: white;
        text-align: center;
        text-decoration: none;
        width: 48%;
        font-size: 16px;
        }

        .view-questions {
        background-color: #4caf50;
        }

        .retake-quiz {
        background-color: #2196f3;
        }

        .action-btn:hover {
        opacity: 0.9;
        }

        /* Chat button styling */
        .chat-button {
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 12px 18px;
        background-color: #ffeb3b;
        color: #333;
        border: none;
        border-radius: 30px;
        cursor: pointer;
        font-size: 18px;
        }

        .chat-button:hover {
        background-color: #ffca28;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
        .header {
            flex-direction: column;
            align-items: flex-start;
        }

        .nav {
            flex-direction: column;
            gap: 15px;
        }

        .result-container {
            padding: 20px;
        }

        .result-card {
            width: 100%;
            padding: 20px;
        }

        .result-title {
            font-size: 24px;
        }

        .stat .value {
            font-size: 20px;
        }

        .action-btn {
            width: 100%;
            font-size: 14px;
        }
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

<main class="result-container">
    <section class="result-card">
        <h1 class="result-title">Your Quiz Results</h1>
        <div class="result-stats">
            <div class="stat">
                <span class="value"><?php echo htmlspecialchars($completion_time); ?></span>
                <span class="label">Completion Time</span>
            </div>
            <div class="stat">
                <span class="value"><?php echo $correct_answers; ?></span>
                <span class="label">Correct Answers</span>
            </div>
            <div class="stat">
                <span class="value"><?php echo $total_questions; ?></span>
                <span class="label">Total Questions</span>
            </div>
        </div>

        <div class="actions">
            <a href="view_result_question.php?attempt_id=<?php echo $attempt_id; ?>" class="action-btn view-questions">View Questions</a>
            <a href="quiz.php" class="action-btn retake-quiz">Retake Quiz</a>
        </div>
    </section>
</main>
</body>
</html>

<?php
// Close connection
$conn->close();
?>
