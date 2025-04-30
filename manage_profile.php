<?php
// Start the session
session_start();

// Check if the user is logged in either via session or cookie
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username']; // Session-based login
} elseif (isset($_COOKIE['username'])) {
    $username = $_COOKIE['username']; // Cookie-based login
    $_SESSION['username'] = $username; // Set session to cookie value
} else {
    header("Location: login.php"); // Redirect to login if no session or cookie is set
    exit();
}

// MySQL database connection
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "rwdd";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update profile if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $updated_email = $_POST['email'];
    $updated_age = $_POST['age'];
    $updated_country = $_POST['country'];
    $updated_gender = $_POST['gender'];
    $updated_password = $_POST['password'];

    $sql = "UPDATE users SET email = ?, age = ?, country = ?, gender = ?";
    if (!empty($updated_password)) {
        $hashed_password = password_hash($updated_password, PASSWORD_BCRYPT);
        $sql .= ", password = ?";
    }
    $sql .= " WHERE username = ?";
    
    $stmt = $conn->prepare($sql);

    if (!empty($updated_password)) {
        $stmt->bind_param("sissss", $updated_email, $updated_age, $updated_country, $updated_gender, $hashed_password, $username);
    } else {
        $stmt->bind_param("sisss", $updated_email, $updated_age, $updated_country, $updated_gender, $username);
    }

    if ($stmt->execute()) {
        echo "<script>alert('Profile updated successfully!');</script>";
    } else {
        echo "<script>alert('Error updating profile.');</script>";
    }

    $stmt->close();
}

// Fetch the current user details including role
$sql = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $db_username = $user['username'];
    $db_email = $user['email'];
    $db_age = $user['age'];
    $db_country = $user['country'];
    $db_gender = $user['gender'];
    $db_profile_pic = $user['profile_pic'];
    $user_role = $user['role'];
} else {
    echo "User not found.";
    exit();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile</title>
  <style>
    /* Global Styling */
body {
  font-family: Arial, sans-serif;
  margin: 0;
  padding: 0;
  background-color: #a4b5eb93;
  color: #333;
  font-size: 1rem;
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

/* Profile Page Styling */
.profile-container {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  align-items: flex-start;
  padding: 2rem;
  margin-top: 2rem;
  background: #fff;
  border-radius: 1rem;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
  max-width: 90%;
  margin: 2rem auto;
  gap: 2rem;
}

.left-side {
  flex: 1;
  max-width: 300px;
  text-align: center;
}

.profile-pic img {
  width: 100%;
  max-width: 200px;
  height: auto;
  border-radius: 50%;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.right-side {
  flex: 2;
  max-width: 500px;
  width: 100%;
}

h1 {
  font-size: clamp(1.5rem, 2vw, 2rem);
  text-align: center;
  color: #333;
  margin-bottom: 1rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: bold;
}

input[type="text"],
input[type="email"],
input[type="number"],
input[type="password"],
select {
  width: 100%;
  padding: 0.8rem;
  border: 1px solid #ccc;
  border-radius: 0.5rem;
  font-size: 1rem;
  background-color: #f9f9f9;
  box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: border 0.3s;
}

input:focus,
select:focus {
  border-color: #007BFF;
  outline: none;
}

button {
  padding: 0.8rem;
  background-color: #007BFF;
  color: white;
  border: none;
  border-radius: 0.5rem;
  cursor: pointer;
  font-size: 1rem;
  width: 100%;
}

button:hover {
  background-color: #0056b3;
}

.back-button {
  background-color: #f44336;
  margin-top: 1rem;
}

.back-button:hover {
  background-color: #d32f2f;
}



/* Responsive Design */
@media (max-width: 768px) {
  .profile-container {
    flex-direction: column;
    align-items: center;
  }

  .left-side,
  .right-side {
    max-width: 100%;
  }

  h1 {
    font-size: 1.5rem;
  }
}

@media (max-width: 480px) {

  .nav-item {
    margin: 0.5rem 0;
  }

  .profile-pic img {
    max-width: 150px;
  }

  h1 {
    font-size: 1.2rem;
  }
}

  </style>
  <link rel="stylesheet" href="manage_profile.css">
  <script>
    function toggleEdit() {
      const form = document.querySelector('#profileForm');
      const editButton = document.getElementById('editButton');
      const saveButton = document.getElementById('saveButton');
      
      form.classList.toggle('edit-mode');
      const fields = form.querySelectorAll('input, select');
      const isEditable = form.classList.contains('edit-mode');
      
      fields.forEach(field => field.disabled = !isEditable);

      editButton.style.display = isEditable ? 'none' : 'block';
      saveButton.style.display = isEditable ? 'block' : 'none';

      if (!isEditable) {
        fields.forEach(field => field.disabled = false);
      }
    }

    function toggleProfileMenu() {
      const dropdown = document.querySelector('.profile-dropdown');
      dropdown.classList.toggle('active');
    }

    document.addEventListener('click', function (event) {
      const dropdown = document.querySelector('.profile-dropdown');
      if (!dropdown.contains(event.target)) {
        dropdown.classList.remove('active');
      }
    });
  </script>
</head>
<body>
<header class="navbar">
    <a href="homepage.php" class="nav-item">HOME</a>
    
    <?php if ($user_role == 'Teacher'): ?>
        <a href="quiz.php" class="nav-item">MODIFY QUESTIONS</a>
        <a href="leaderboard.php" class="nav-item">LEADERBOARD</a>
        <a href="chat.php" class="nav-item">CHAT</a>
    <?php else: ?>
        <a href="quiz.php" class="nav-item">QUIZZES</a>
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

  <div class="profile-container">
    <div class="left-side">
      <div class="profile-icon">
        <img src="<?= htmlspecialchars($db_profile_pic) ?>">
      </div>
    </div>
    <div class="right-side">
      <h1><?= htmlspecialchars($db_username) ?>'s Profile</h1>
      <form id="profileForm" method="POST" enctype="multipart/form-data">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" name="username" value="<?= htmlspecialchars($db_username) ?>" disabled>
        </div>
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($db_email) ?>" disabled>
        </div>
        <div class="form-group">
          <label for="age">Age</label>
          <input type="number" name="age" value="<?= htmlspecialchars($db_age) ?>" disabled>
        </div>
        <div class="form-group">
          <label for="country">Country</label>
          <input type="text" name="country" value="<?= htmlspecialchars($db_country) ?>" disabled>
        </div>
        <div class="form-group">
          <label for="gender">Gender</label>
          <select name="gender" disabled>
            <option value="Male" <?= ($db_gender == 'Male') ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= ($db_gender == 'Female') ? 'selected' : '' ?>>Female</option>
          </select>
        </div>
        <div class="form-group">
          <label for="password">New Password</label>
          <input type="password" name="password" placeholder="Enter new password" disabled>
        </div>
        <div class="form-group">
          <button type="button" id="editButton" onclick="toggleEdit()">Edit</button>
          <button type="submit" id="saveButton" style="display: none;">Save</button>
        </div>
      </form>
      <button class="back-button" onclick="window.history.back()">Back</button>
    </div>
  </div>
</body>
</html>
