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

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare SQL statement to select the user from the database
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // If a user is found
    if ($result->num_rows > 0) {
        // Fetch the user's data
        $user = $result->fetch_assoc();
        
        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Successful login
            $_SESSION['username'] = $username;
            
            // Set a cookie to remember the user for 1 week (604800 seconds)
            setcookie('username', $username, time() + 604800, "/"); // Cookie for 1 week

            // Redirect to profile page
            header("Location: quiz.php");
            exit();
        } else {
            // Invalid password
            $_SESSION['error'] = "Invalid password.";
            header("Location: login.php"); // Redirect back to login page
            exit();
        }
    } else {
        // User not found
        $_SESSION['error'] = "No user found with that username.";
        header("Location: login.php"); // Redirect back to login page
        exit();
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bookworm Paradise - Login</title>
  <style>
    
    /* Basic Reset */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html {
  font-size: 16px; /* Base font size for scaling */
}

body {
  font-family: Arial, sans-serif;
  background-color: #a4b5eb93;
  line-height: 1.5;
}

/* Header Styling */
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: #fff;
  padding: 1rem 2rem; /* Relative padding */
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  width: 100%;
}

.header img.logo {
  width: 10%; /* Responsive logo size */
  max-width: 60px; /* Prevent excessive scaling */
  height: auto;
}

.nav {
  display: flex;
  gap: 1rem; /* Responsive spacing */
}

.nav-link {
  text-decoration: none;
  color: black;
  font-weight: bold;
  font-size: clamp(1rem, 1vw, 1.2rem); /* Scales between 1rem and 1.2rem */
}

.nav-link.active {
  text-decoration: underline;
}

/* Main Container */
.container {
  display: flex;
  justify-content: space-around;
  align-items: center;
  flex-wrap: wrap; /* Wrap elements on smaller screens */
  padding: 2rem;
  width: 100%;
  max-width: 1200px; /* Prevent excessive width on larger screens */
  margin: 0 auto;
}

.left-content img {
  width: 100%;
  max-width: 300px; /* Prevent excessive scaling */
  height: auto;
}

.right-content {
  background-color: white;
  padding: 2rem;
  border-radius: 0.5rem;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 400px; /* Constrain width for readability */
}

h1 {
  font-size: clamp(1.5rem, 2vw, 2rem); /* Scales dynamically */
  background-color: rgb(179, 73, 232);
  color: white;
  text-align: center;
  padding: 1rem;
  border-radius: 0.5rem;
  margin-bottom: 1.5rem;
}

label {
  font-size: 1rem;
  margin-bottom: 0.5rem;
  display: block;
}

.error {
      color: red;
      font-size: 0.9rem;
      margin-bottom: 1rem;
      text-align: center;
    }

input[type="text"],
input[type="password"] {
  width: 100%;
  padding: 0.8rem;
  margin-bottom: 1rem;
  border: 1px solid #ccc;
  border-radius: 0.5rem;
  font-size: 1rem;
}

button,
.button-link {
  display: block;
  width: 100%;
  padding: 1rem;
  font-size: 1rem;
  text-align: center;
  border: none;
  border-radius: 0.5rem;
  cursor: pointer;
  margin-bottom: 1rem;
}

button {
  background-color: #4CAF50;
  color: white;
}

button:hover {
  background-color: #45a049;
}

.back-button {
  background-color: #f44336;
  color: white;
}

.back-button:hover {
  background-color: #d32f2f;
}

.button-link {
  background-color: #4CAF50;
  color: white;
  text-decoration: none;
}

.button-link:hover {
  background-color: #45a049;
}

/* Chat Button */
.chat-button {
  position: fixed;
  bottom: 5%;
  right: 5%;
  padding: 1rem;
  font-size: 1rem;
  background-color: #4CAF50;
  color: white;
  border: none;
  border-radius: 2rem;
  cursor: pointer;
}

.chat-button:hover {
  background-color: #45a049;
}

/* Responsive Media Queries */
@media (max-width: 768px) {
  .container {
    flex-direction: column;
    gap: 1.5rem; /* Increase spacing for readability */
  }

  .right-content {
    max-width: 90%; /* Allow full width for smaller screens */
  }

  h1 {
    font-size: 1.8rem; /* Adjust heading size */
  }
}

  </style>
</head>
<body>
  <header class="header">
    <img src="logo.png" alt="logo" class="logo">
    <nav class="nav">
      <a href="homepage.php" class="nav-link active">HOME</a>
      <a href="login.php" class="nav-link">QUIZZES</a>
      <a href="login.php" class="nav-link">RESULTS</a>
    </nav>

  </header>
  
  <div class="container">
    <div class="left-content">
      <img src="logo.png" alt="Logo" class="logo-image">
    </div>
    
    <div class="right-content">
      <div calss="container"></div>
        <h1>Welcome to MathStorm</h1>
        <?php
      if (isset($_SESSION['error'])) {
          echo '<p class="error">' . $_SESSION['error'] . '</p>';
          unset($_SESSION['error']); // Clear the error after displaying
      }
      ?>
        <form method="post" action="login.php">
          <div class="Name">
            <label for="username">Name:</label>
            <input type="text" id="username" name="username" required />
          </div>
    
          <div class="user_password">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required />
          </div>
    
          <div class="Submit">
            <button type="submit">Login</button>
          </div>
    
          <div class="Submit">
            <a href="reset_password.php" class="button-link">Forget Password</a>
          </div>
    
          <div class="Submit">
            <button type="button" class="back-button" onclick="window.location.href='homepage.php';">Back</button>
          </div>
        </div>
        </form>
      </div>
  

</body>
</html>
