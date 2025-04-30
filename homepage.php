<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MathStorm</title>
    <style>
  /* Basic reset and styling */
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  body {
    font-family: Arial, sans-serif;
    background-color: #f4f6fc;
  }

  /* Navbar Styling */
  header {
    background-color: #4CAF50;
    padding: 15px 20px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }

  .navbar {
    display: flex;
    justify-content: center;
    gap: 30px;
  }

  .nav-item {
    text-decoration: none;
    color: black;
    font-weight: bold;
  }

  .nav-item:hover {
    text-decoration: underline;
  }

  .nav-item.active {
    color: rgb(255, 255, 255);
    text-decoration: underline;
  }

  /* Welcome Section Styling */
  h1 {
    text-align: center;
    color: #3f51b5;
    font-size: 24px; /* Reduced size for consistency */
    margin-top: 20px;
  }

  /* Profile Icon */
  .profile-icon {
    display: flex;
    justify-content: center;
    margin-top: 20px;
  }

  .profile-icon .logo {
    width: 100px; /* Adjusted for smaller view */
    height: auto;
  }

  /* Description Paragraph */
  .Paragraph {
    text-align: center;
    margin: 20px auto;
    width: 90%; /* Flexible width */
    font-size: 16px; /* Adjusted size */
    line-height: 1.5;
  }

  /* Logo Container */
  .logo-container {
    display: flex;
    justify-content: center;
    margin-top: 20px;
  }

  .logo-container .logo {
    width: 150px; /* Adjusted for smaller view */
    height: auto;
  }

  /* Explanation Section */
  .Explain {
    text-align: center;
    margin: 30px 20px;
  }

  .Explain h2 {
    font-size: 24px; /* Unified font size */
    color: #4caf50;
  }

  .Explain p {
    font-size: 16px;
    line-height: 1.5;
    margin-top: 10px;
  }

  /* Call-to-Action Buttons */
  .cta-buttons {
    display: flex;
    justify-content: center;
    gap: 10px; /* Smaller gap */
    margin-top: 20px;
    flex-wrap: wrap; /* Wrap buttons if needed */
  }

  .cta-buttons .Login {
    padding: 10px 20px;
    background-color: #4caf50;
    color: white;
    font-size: 16px; /* Unified font size */
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }

  .cta-buttons .Login:hover {
    background-color: #45a049;
  }

  .cta-buttons .Login:active {
    background-color: #3e8e41;
  }

  /* Footer Styling */
  footer {
    background-color: #fff;
    padding: 20px;
    text-align: center;
    box-shadow: 0 -4px 8px rgba(0, 0, 0, 0.1);
    margin-top: 20px;
  }

  footer hr {
    margin-bottom: 20px;
  }

  .Boarder {
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .Boarder p {
    font-size: 14px; /* Unified font size */
    color: #3f51b5;
    margin-bottom: 10px;
  }

  .Boarder .fa {
    font-size: 18px; /* Adjusted size */
    margin: 0 5px;
    color: #4caf50;
  }

  .Boarder .fa:hover {
    color: #45a049;
  }

  /* Contact Info */
  .contact-info {
    margin-top: 10px;
  }

  .contact-info a {
    color: #4caf50;
    text-decoration: none;
  }

  .contact-info a:hover {
    text-decoration: underline;
  }
</style>

    <!-- Font Awesome for icons -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"
    />
  </head>
  <body>
    <!-- Navbar -->
    <header>
      <div class="navbar">
        <a href="manage_profile.php" class="nav-item active">Profile</a>
        <a href="quiz.php" class="nav-item">Quiz</a>
        <a href="leaderboard.php" class="nav-item">Leaderboard</a>
      </div>
    </header>

    <!-- Welcome Section -->
    <header>
      <h1>Welcome to MathStorm</h1>
    </header>
    <div class="profile-icon">
      <img src="logo.png" alt="Logo" class="logo" />
    </div>
    <!-- Description Paragraph -->
    <div class="Paragraph">
      <p>
        “MathStorm” is an interactive web application designed to enhance mathematics skills among primary and secondary schools students. The platform provides a dynamic and engaging environment where students can take quizzes, receive instant feedback, and track their progress. Instructors can create and manage quizzes, while administrators oversee the platform’s overall performance and user management.
      </p>
    </div>

    <!-- Logo Section -->
    <div class="logo-container">
      <img src="logo.png" alt="MathStorm Logo" class="logo" />
    </div>

    <!-- Explanation Section -->
    <div class="Explain">
      <h2>What do we offer?</h2>
      <p>
        We offer a wide range of Mathematics Quiz that will help to improve Mathematics skills.
      </p>
    </div>

    <!-- Call-to-Action Buttons -->
    <div class="cta-buttons">
      <button class="Login" onclick="window.location.href='login.php';">
        Login
      </button>
      <button class="Login" onclick="window.location.href='leaderboard.php';">
        ScoreBoard
      </button>
    </div>

    <!-- Footer -->
    <footer>
      <hr />
      <div class="Boarder">
        <p style="color: blueviolet">
          &copy; MathStorm |
          <a href="https://www.facebook.com/" class="fa fa-facebook"></a>
          <a href="https://www.instagram.com/" class="fa fa-instagram"></a>
        </p>
        <div class="contact-info">
          <p>
            Tel &#9742;
            <a href="tel:+60125887969">+60125887969</a>
          </p>
        </div>
      </div>
    </footer>
  </body>
</html>
