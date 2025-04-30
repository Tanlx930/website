<?php
session_start();

// MySQL database connection
$servername = "localhost";
$db_username = "root"; 
$db_password = ""; 
$db_name = "rwdd";

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission for password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['username']) && isset($_POST['email'])) {
        // Step 1: User verification (username + email)
        $user = $_POST['username'];
        $email = $_POST['email'];

        // Check if username and email match
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND email = ?");
        $stmt->bind_param("ss", $user, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // If the username and email match, allow password reset

            // Handle password reset
            if (isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];

                // Check if passwords match
                if ($new_password === $confirm_password) {
                    // Hash the new password
                    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

                    // Update password in the database
                    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
                    $update_stmt->bind_param("ss", $hashed_password, $user);

                    if ($update_stmt->execute()) {
                        // Redirect to login after successful password reset
                        echo "<script>alert('Password reset successful. You can now log in.'); window.location.href = 'login.php';</script>";
                        exit();
                    } else {
                        echo "<script>alert('Error updating password. Please try again.');</script>";
                    }

                    $update_stmt->close();
                } else {
                    echo "<script>alert('Passwords do not match.');</script>";
                }
            }
            $stmt->close();
        } else {
            echo "<script>alert('Username or Email is incorrect. Please try again.');</script>";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>MathStorm - Reset Password</title>
  <style>
      /* Basic styling for the container and form */
    body {
      font-family: Arial, sans-serif;
      background-color: #a4b5eb93; 
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .container {
      background-color: white;
      width: 400px;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      text-align: center;
    }

    h1 {
      background-color: rgb(179, 73, 232);
      color: white;
      padding: 10px;
      border-radius: 5px;
      margin-bottom: 20px;
    }

    label {
      display: block;
      text-align: left;
      margin: 10px 0 5px;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"] {
      width: calc(100% - 20px);
      padding: 10px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 16px;
    }

    button {
      width: 100%;
      padding: 10px;
      background-color: #6dbc70a5;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
      margin-bottom: 10px;
    }

    button:hover {
      background-color: #f44336
    }

    .back-button {
      background-color: #f44336;
    }

    .back-button:hover {
      background-color: #d32f2f;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .navbar {
          flex-direction: column;
          gap: 10px;
        }

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
  <div class="container">
    <h1>Reset Password</h1>
    <form method="post" action="reset_password.php">
      <!-- Username and Email Verification -->
      <div class="Name">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required />
      </div>

      <div class="Email">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required />
      </div>

      <!-- Password Reset -->
      <div class="password-reset">
        <label for="new_password">Enter New Password:</label>
        <input type="password" id="new_password" name="new_password" required />

        <label for="confirm_password">Confirm New Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required />
      </div>

      <!-- Submit Button -->
      <div class="Submit">
        <button type="submit">Submit</button>
      </div>

      <!-- Back Button -->
      <div class="Submit">
        <button type="button" class="back-button" onclick="window.location.href='login.php';">Back</button>
      </div>
    </form>
  </div>
</body>
</html>
