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

// Fetch leaderboard data
$leaderboard_sql = "
    SELECT 
        u.username, 
        COUNT(r.response_id) AS correct_answers
    FROM 
        users u
    LEFT JOIN 
        attempts a ON u.user_id = a.user_id
    LEFT JOIN 
        responses r ON a.attempt_id = r.attempt_id
    WHERE 
        r.is_answer_correct = 1
    GROUP BY 
        u.user_id
    ORDER BY 
        correct_answers DESC
    LIMIT 10;
";

$leaderboard_result = $conn->query($leaderboard_sql);
$leaderboard = [];
if ($leaderboard_result->num_rows > 0) {
    while ($row = $leaderboard_result->fetch_assoc()) {
        $leaderboard[] = $row;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - MathStorm</title>
    <script>
        function toggleProfileMenu() {
            const dropdown = document.querySelector('.profile-dropdown');
            dropdown.classList.toggle('active');
        }

    </script>
       <style>
        /* General Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
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
        /* Leaderboard Container */
        .leaderboard-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .leaderboard-container h2 {
            margin-bottom: 20px;
            font-size: 28px;
            color: #333;
        }

        /* Table Styling */
        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
            margin: 20px 0;
        }

        .leaderboard-table th, .leaderboard-table td {
            border: 1px solid #ddd;
            padding: 10px;
            font-size: 16px;
        }

        .leaderboard-table th {
            background-color: #4CAF50;
            color: white;
        }

        .leaderboard-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .leaderboard-table tr:hover {
            background-color: #f1f1f1;
        }
    </style>
    <link rel="stylesheet" href="leaderboard.css">
</head>
<body>

<header class="navbar">
    <a href="homepage.php" class="nav-item">HOME</a>
    
    <?php if ($user_role == 'Teacher'): ?>
        <a href="quiz.php" class="nav-item">MODIFY QUESTIONS</a>
        <a href="leaderboard.php" class="nav-item active">LEADERBOARD</a>
        <a href="chat.php" class="nav-item">CHAT</a>
    <?php else: ?>
        <a href="quiz.php" class="nav-item">QUIZZES</a>
        <a href="leaderboard.php" class="nav-item active">LEADERBOARD</a>
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

<main class="leaderboard-container">
    <h2>Leaderboard</h2>
    <table class="leaderboard-table">
        <thead>
            <tr>
                <th>Rank</th>
                <th>Username</th>
                <th>Correct Answers</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($leaderboard)): ?>
                <?php foreach ($leaderboard as $index => $entry): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($entry['username']) ?></td>
                        <td><?= $entry['correct_answers'] ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">No data available</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</main>

</body>
</html>
