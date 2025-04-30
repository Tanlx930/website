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

// Check if the user is logged in and retrieve user details
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
} elseif (isset($_COOKIE['username'])) {
    $username = $_COOKIE['username'];
    $_SESSION['username'] = $username; // Set session for this user
} else {
    // If not logged in, redirect to the login page
    header("Location: login.php");
    exit();
}

// Fetch user details and role
$user_sql = "SELECT user_id, role, profile_pic FROM users WHERE username = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$user_id = $user['user_id'];
$db_profile_pic = $user['profile_pic'];
$user_role = $user['role'];
$is_teacher = ($user['role'] == 'Teacher');

// Fetch quiz results
if ($is_teacher) {
    // Fetch all quizzes and their results for teachers
    $results_sql = "
        SELECT 
            q.quiz_id, 
            q.quiz_name, 
            COUNT(DISTINCT a.attempt_id) AS total_attempts, 
            ROUND(IFNULL(AVG(CASE WHEN r.is_answer_correct = 1 THEN 100 ELSE 0 END), 0), 2) AS avg_score,
            ROUND(IFNULL(AVG(TIMESTAMPDIFF(SECOND, a.start_time, a.end_time)) / 60, 0), 2) AS avg_time_minutes
        FROM quizzes q
        LEFT JOIN attempts a ON q.quiz_id = a.quiz_id
        LEFT JOIN responses r ON a.attempt_id = r.attempt_id
        GROUP BY q.quiz_id;
    ";
} else {
    // Fetch results for the logged-in student
    $results_sql = "
        SELECT 
            q.quiz_id, 
            q.quiz_name, 
            COUNT(DISTINCT a.attempt_id) AS total_attempts, 
            ROUND(IFNULL(AVG(CASE WHEN r.is_answer_correct = 1 THEN 100 ELSE 0 END), 0), 2) AS avg_score,
            ROUND(IFNULL(AVG(TIMESTAMPDIFF(SECOND, a.start_time, a.end_time)) / 60, 0), 2) AS avg_time_minutes
        FROM quizzes q
        LEFT JOIN attempts a ON q.quiz_id = a.quiz_id
        LEFT JOIN responses r ON a.attempt_id = r.attempt_id
        WHERE a.user_id = ?
        GROUP BY q.quiz_id;
    ";
}

$stmt = $conn->prepare($results_sql);
if (!$is_teacher) {
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$results_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results</title>
    <script>
        function toggleProfileMenu() {
            const dropdown = document.querySelector('.profile-dropdown');
            dropdown.classList.toggle('active');
        }

    </script>
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
            padding: 20px;
            color: white;
        }
        .header h1 {
            margin: 0;
        }
        .nav .nav-link {
            margin-left: 20px;
            color: white;
            text-decoration: none;
        }
        .results-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .results-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .results-table th, .results-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        .results-table th {
            background-color: #4CAF50;
            color: white;
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

<main>
    <div class="results-container">
        <h2>Quiz Results</h2>
        <table class="results-table">
            <thead>
                <tr>
                    <th>Quiz Name</th>
                    <th>Total Attempts</th>
                    <th>Average Score (%)</th>
                    <th>Average Time (Minutes)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($results_result->num_rows > 0): ?>
                    <?php while ($result = $results_result->fetch_assoc()): ?>
                        <tr>
                            <td>

                                    <?= htmlspecialchars($result['quiz_name']) ?>
                                </a>
                            </td>
                            <td><?= $result['total_attempts'] ?></td>
                            <td><?= round($result['avg_score'], 2) ?>%</td>
                            <td><?= round($result['avg_time_minutes'], 2) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No results available.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>

<?php
// Close connection
$conn->close();
?>
