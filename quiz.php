<?php
// MySQL database connection
$servername = "localhost";
$db_username = "root"; // Replace with your MySQL username
$db_password = ""; // Replace with your MySQL password
$dbname = "rwdd";

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve username from cookie
if (!isset($_COOKIE['username'])) {
    header("Location: login.php");
    exit();
}
$username = $_COOKIE['username'];

// Fetch user details from the database
$stmt = $conn->prepare("SELECT user_id, role, profile_pic FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows === 0) {
    header("Location: login.php");
    exit();
}

$user = $user_result->fetch_assoc();
$user_id = $user['user_id'];
$db_profile_pic = $user['profile_pic'];
$user_role = $user['role']; // Either 'Teacher' or 'Student'

// Handle new quiz creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_quiz'])) {
    $quiz_name = $_POST['quiz_name'];

    if (!empty($quiz_name)) {
        $stmt = $conn->prepare("INSERT INTO quizzes (quiz_name, created_at) VALUES (?, NOW())");
        $stmt->bind_param("s", $quiz_name);
        $stmt->execute();
        $stmt->close();
        header("Location: quiz.php");
        exit();
    } else {
        $error_message = "Quiz name cannot be empty.";
    }
}

// Handle quiz deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_quiz'])) {
    $quiz_id = intval($_POST['quiz_id']);

    // Delete quiz and related data
    $stmt = $conn->prepare("DELETE FROM quizzes WHERE quiz_id = ?");
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $stmt->close();

    header("Location: quiz.php");
    exit();
}

// Handle quiz start for students
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['quiz_id']) && $user_role === 'Student') {
    $quiz_id = intval($_GET['quiz_id']); // Ensure quiz_id is an integer

    // Function to create a new attempt
    function createNewAttempt($conn, $user_id, $quiz_id) {
        $stmt = $conn->prepare("INSERT INTO attempts (user_id, quiz_id, start_time) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $user_id, $quiz_id);
        $stmt->execute();
        return $stmt->insert_id;
    }

    // Create a new attempt and redirect to view_question.php
    $attempt_id = createNewAttempt($conn, $user_id, $quiz_id);
    header("Location: view_question.php?quiz_id=$quiz_id&attempt_id=$attempt_id");
    exit();
}

// Fetch quizzes
$quizzes_sql = "SELECT * FROM quizzes";
$quizzes_result = $conn->query($quizzes_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Page - MathStorm</title>
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

        .quiz-main {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .quiz-main h2, .quiz-main h3 {
            text-align: center;
        }

        .quiz-list {
            margin-top: 20px;
        }

        .quiz-item {
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .quiz-link {
            display: inline-block;
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .quiz-link:hover {
            background-color: #45a049;
        }

        .delete-btn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
        }

        .delete-btn:hover {
            background-color: #d32f2f;
        }

        .add-quiz-form {
            display: none;
            margin: 20px 0;
            padding: 15px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .add-quiz-form input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .add-quiz-form button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .add-quiz-form button:hover {
            background-color: #45a049;
        }

        .error-message {
            color: red;
            text-align: center;
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
        function toggleAddQuizForm() {
            const form = document.getElementById("add-quiz-form");
            form.style.display = form.style.display === "none" ? "block" : "none";
        }

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


<main class="quiz-main">
    <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>

    <?php if ($quizzes_result->num_rows > 0): ?>
        <h3><?php echo $user_role === 'Teacher' ? 'Manage Quizzes' : 'Available Quizzes'; ?>:</h3>
        <div class="quiz-list">
            <?php while ($quiz = $quizzes_result->fetch_assoc()): ?>
                <div class="quiz-item">
                    <span><?php echo htmlspecialchars($quiz['quiz_name']); ?></span>
                    <?php if ($user_role === 'Teacher'): ?>
                        <!-- Teacher: Link to modify and delete quizzes -->
                        <a href="modify_question.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" class="quiz-link">Edit</a>
                        <form method="POST" action="quiz.php" style="display: inline;">
                            <input type="hidden" name="quiz_id" value="<?php echo $quiz['quiz_id']; ?>">
                            <button type="submit" name="delete_quiz" class="delete-btn">Delete</button>
                        </form>
                    <?php else: ?>
                        <!-- Student: Link to start the quiz -->
                        <a href="quiz.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" class="quiz-link">
                            Start Quiz
                        </a>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p>No quizzes available at the moment.</p>
    <?php endif; ?>

    <?php if ($user_role === 'Teacher'): ?>
        <button onclick="toggleAddQuizForm()"class="quiz-link">Add New Quiz</button>
        <div id="add-quiz-form" class="add-quiz-form">
            <form method="POST" action="quiz.php">
                <input type="text" name="quiz_name" placeholder="Enter quiz name" required>
                <button type="submit" name="add_quiz">Create Quiz</button>
            </form>
        </div>
        <?php if (isset($error_message)): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>
    <?php endif; ?>
</main>
</body>
</html>

<?php
$conn->close();
?>
