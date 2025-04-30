<?php
// Start the session
session_start();

// MySQL database connection
$servername = "localhost";
$db_username = "root";
$db_password = "";
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

// Fetch the current user's profile picture
$current_user_username = $_SESSION['username'];
$sql = "SELECT user_id, role, profile_pic FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $current_user_username);
$stmt->execute();
$result = $stmt->get_result();
$profile_pic = "uploads/default.png";

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $db_profile_pic = $user['profile_pic'];
    $user_role = $user['role'];
    $profile_pic = $user['profile_pic'];
}
$stmt->close();

// Fetch all users
$sql = "SELECT * FROM users ORDER BY username ASC";
$result = $conn->query($sql);

$users = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $email = $_POST['email'];
    $age = $_POST['age'];
    $country = $_POST['country'];
    $role = $_POST['role'];
    $gender = $_POST['gender'];

    $stmt = $conn->prepare("INSERT INTO users (username, password, email, age, country, role, gender) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $username, $password, $email, $age, $country, $role, $gender);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_user.php");
    exit();
}

// Handle Reset Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $user_id = $_POST['user_id'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password === $confirm_password) {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        $stmt->execute();
        $stmt->close();

        header("Location: manage_user.php");
        exit();
    } else {
        echo "<script>alert('Passwords do not match!');</script>";
    }
}

// Handle Delete User
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_user.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users</title>
  <style>
    /* General Reset */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: Arial, sans-serif;
  background-color: #f4f4f4;
  color: #333;
  line-height: 1.6;
  font-size: 16px;
}

/* Header Styling */
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  background-color: #fff;
  padding: 10px 20px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.logo {
  font-size: 1.5rem;
  font-weight: bold;
  color: #4CAF50;
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
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.users-section {
  margin-top: 30px;
}

h2 {
  font-size: 1.8rem;
  margin-bottom: 10px;
}

.tag {
  font-size: 0.9rem;
  color: #777;
  margin-bottom: 20px;
}

.user-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.user-row {

  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  padding: 15px;
  background-color: white;
  border-radius: 5px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.user-info {
  display: flex;
  gap: 15px;
  align-items: center;
}

.user-icon {
  font-size: 1.5rem;
  background-color: #4CAF50;
  color: white;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.user-details {
  display: flex;
  flex-direction: column;
}

.username {
  font-weight: bold;
  font-size: 1rem;
}

.email,
.gender,
.role {
  font-size: 0.9rem;
  color: #777;
}

.user-actions {
  display: flex;
  gap: 10px;
}

.user-actions button {
  padding: 8px 15px;
  background-color: #4CAF50;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 0.9rem;
}

.user-actions button:hover {
  background-color: #45a049;
}

.delete-btn {
  background-color: #f44336;
}

.delete-btn:hover {
  background-color: #e32f2f;
}

/* Add Button */
.add-btn {
  position: fixed;
  bottom: 20px;
  right: 20px;
  padding: 15px;
  background-color: #4CAF50;
  color: white;
  border: none;
  border-radius: 50%;
  font-size: 1.5rem;
  cursor: pointer;
}

.add-btn:hover {
  background-color: #45a049;
}

/* Modal Styling */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: none;
  justify-content: center;
  align-items: center;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1000;
}

.modal-content {
  background-color: white;
  padding: 20px;
  border-radius: 8px;
  width: 400px;
  max-width: 90%;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.modal-content form label {
  display: block;
  margin: 10px 0 5px;
}

.modal-content form input,
.modal-content form select,
.modal-content form button {
  width: 100%;
  padding: 10px;
  margin-bottom: 15px;
  border: 1px solid #ccc;
  border-radius: 5px;
}

.modal-content form button {
  background-color: #4CAF50;
  color: white;
  border: none;
  cursor: pointer;
}

.modal-content form button:hover {
  background-color: #45a049;
}

.modal-content form button[type="button"] {
  background-color: #f44336;
}

.modal-content form button[type="button"]:hover {
  background-color: #d32f2f;
}

/* Profile Picture in Navbar */
.profile-pic {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  cursor: pointer;
  object-fit: cover;
}

/* Responsive Design */
@media (max-width: 768px) {
  .header {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }

  .user-row {
    flex-direction: column;
    gap: 10px;
  }

  .user-actions {
    flex-wrap: wrap;
    gap: 5px;
  }

  .add-btn {
    bottom: 10px;
    right: 10px;
    font-size: 1.2rem;
  }
}

@media (max-width: 480px) {
  h2 {
    font-size: 1.5rem;
  }

  .username {
    font-size: 1rem;
  }

  .email,
  .gender,
  .role {
    font-size: 0.8rem;
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

  <main>
    <section class="users-section">
      <h2>Users</h2>
      <div class="user-list">
        <?php foreach ($users as $user): ?>
          <div class="user-row">
            <div class="user-info">
              <div class="user-icon">👤</div>
              <div class="user-details">
                <span class="username"><?= htmlspecialchars($user['username']) ?></span>
                <span class="email"><?= htmlspecialchars($user['email']) ?></span>
                <span class="gender"><?= htmlspecialchars($user['gender']) ?></span>
                <span class="role"><?= htmlspecialchars($user['role']) ?></span>
              </div>
            </div>
            <div class="user-actions">
              <button class="chat-btn" onclick="startChat(<?= $user['user_id'] ?>)">💬 Chat</button>
              <button class="reset-btn" onclick="resetPassword(<?= $user['user_id'] ?>)">🔑 Reset Password</button>
              <button class="delete-btn" onclick="confirmDelete(<?= $user['user_id'] ?>)">❌ Delete</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <button class="add-btn" onclick="openAddUserForm()">+</button>

    <!-- Add User Modal -->
    <div id="add-user-modal" class="modal" style="display: none;">
      <div class="modal-content">
        <h2>Add New User</h2>
        <form action="manage_user.php" method="POST">
          <label for="username">Username:</label>
          <input type="text" id="username" name="username" required>

          <label for="password">Password:</label>
          <input type="password" id="password" name="password" required>

          <label for="email">Email:</label>
          <input type="email" id="email" name="email" required>

          <label for="age">Age:</label>
          <input type="number" id="age" name="age" min="1" max="100" required>

          <label for="country">Country:</label>
          <input type="text" id="country" name="country" required>

          <label for="role">Role:</label>
          <select id="role" name="role" required>
            <option value="Student">Student</option>
            <option value="Teacher">Teacher</option>
          </select>

          <label for="gender">Gender:</label>
          <select id="gender" name="gender" required>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
          </select>

          <button type="submit" name="add_user">Add User</button>
          <button type="button" onclick="closeAddUserForm()">Cancel</button>
        </form>
      </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="reset-password-modal" class="modal" style="display: none;">
      <div class="modal-content">
        <h2>Reset Password</h2>
        <form action="manage_user.php" method="POST">
          <input type="hidden" id="reset-user-id" name="user_id">
          <label for="new_password">New Password:</label>
          <input type="password" id="new_password" name="new_password" required>
          <label for="confirm_password">Confirm Password:</label>
          <input type="password" id="confirm_password" name="confirm_password" required>
          <button type="submit" name="reset_password">Reset Password</button>
          <button type="button" onclick="closeResetPasswordModal()">Cancel</button>
        </form>
      </div>
    </div>
  </main>

  <script>
    function toggleProfileMenu() {
        const dropdown = document.querySelector('.profile-dropdown');
        dropdown.classList.toggle('active');
    }

    function startChat(userId) {
        alert('Chat with user ID: ' + userId);
    }

    function confirmDelete(userId) {
        if (confirm('Are you sure you want to delete this user?')) {
            window.location.href = 'manage_user.php?delete_id=' + userId;
        }
    }

    function openAddUserForm() {
        const addUserModal = document.getElementById('add-user-modal');
        addUserModal.style.display = 'block';
    }

    function closeAddUserForm() {
        const addUserModal = document.getElementById('add-user-modal');
        addUserModal.style.display = 'none';
    }

    function resetPassword(userId) {
        const resetPasswordModal = document.getElementById('reset-password-modal');
        document.getElementById('reset-user-id').value = userId;
        resetPasswordModal.style.display = 'block';
    }

    function closeResetPasswordModal() {
        const resetPasswordModal = document.getElementById('reset-password-modal');
        resetPasswordModal.style.display = 'none';
    }

    function toggleProfileMenu() {
        const dropdown = document.querySelector('.profile-dropdown');
        dropdown.classList.toggle('active');
    }

  </script>
</body>
</html>
