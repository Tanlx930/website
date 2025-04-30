<?php
// Start session
session_start();

// Database connection
$servername = "localhost";
$username = "root"; // Replace with your MySQL username
$password = ""; // Replace with your MySQL password
$dbname = "rwdd"; // Replace with your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Determine the current logged-in user
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
} elseif (isset($_COOKIE['username'])) {
    $username = $_COOKIE['username'];
    $_SESSION['username'] = $username; // Set session for consistency
} else {
    // If not logged in, redirect to login page
    header("Location: login.php");
    exit();
}

// Fetch user ID of the current logged-in user
$stmt = $conn->prepare("SELECT user_id, role, profile_pic FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$user_result = $stmt->get_result();
if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    $db_profile_pic = $user['profile_pic'];
    $user_role = $user['role'];
    $current_user_id = $user['user_id']; // Use this as the logged-in user ID
} else {
    // Redirect to login if user not found
    header("Location: login.php");
    exit();
}

// Handle AJAX requests
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = [];

    if ($_POST['action'] === 'fetch_contacts') {
        // Fetch all users excluding the current user
        $stmt = $conn->prepare("SELECT user_id, username FROM users WHERE user_id != ?");
        $stmt->bind_param("i", $current_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $contacts = $result->fetch_all(MYSQLI_ASSOC);
        $response = $contacts;

    } elseif ($_POST['action'] === 'fetch_messages') {
        // Fetch messages for a specific user
        $participant_id = (int)$_POST['participant_id'];

        // Find or create conversation between current user and selected participant
        $stmt = $conn->prepare("
            SELECT conversation_id FROM conversations 
            WHERE (participant1_id = ? AND participant2_id = ?) 
               OR (participant1_id = ? AND participant2_id = ?)
        ");
        $stmt->bind_param("iiii", $current_user_id, $participant_id, $participant_id, $current_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $conversation = $result->fetch_assoc();

        if (!$conversation) {
            // Create a new conversation if one doesn't exist
            $stmt = $conn->prepare("INSERT INTO conversations (participant1_id, participant2_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $current_user_id, $participant_id);
            $stmt->execute();
            $conversation_id = $stmt->insert_id;
        } else {
            $conversation_id = $conversation['conversation_id'];
        }

        // Fetch messages for the conversation
        $stmt = $conn->prepare("
            SELECT sender_id, content, timestamp 
            FROM messages 
            WHERE conversation_id = ? 
            ORDER BY timestamp ASC
        ");
        $stmt->bind_param("i", $conversation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $messages = $result->fetch_all(MYSQLI_ASSOC);

        $response = [
            'conversation_id' => $conversation_id,
            'messages' => $messages
        ];

    } elseif ($_POST['action'] === 'send_message') {
        // Save a new message to the database
        $conversation_id = (int)$_POST['conversation_id'];
        $content = $_POST['content'];

        if (!empty($content)) {
            $stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, content) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $conversation_id, $current_user_id, $content);
            $stmt->execute();
            $response = ['status' => 'success'];
        } else {
            $response = ['status' => 'error', 'message' => 'Message content cannot be empty.'];
        }
    }

    echo json_encode($response);
    exit;
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat</title>
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

/* Header Styling */
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #4CAF50;
    padding: 15px;
    color: white;
}

header .logo {
    font-size: 20px;
    font-weight: bold;
}

header nav a {
    margin-left: 15px;
    color: white;
    text-decoration: none;
}

header .profile button {
    padding: 5px 10px;
    background-color: white;
    color: #4CAF50;
    border: none;
    cursor: pointer;
}

/* Chat Container */
.chat-container {
    display: flex; /* Two-column layout */
    height: 80vh;
    flex-wrap: wrap; /* Wrap for smaller screens */
}

/* Contacts Sidebar */
.contacts {
    width: 25%; /* Fixed width for sidebar */
    min-width: 200px; /* Prevents it from shrinking too much */
    background-color: #f9f9f9;
    border-right: 1px solid #ddd;
    padding: 15px;
    overflow-y: auto; /* Scrollable sidebar */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.contact-list {
    list-style: none;
    padding: 0;
}

.contact {
    display: flex;
    align-items: center;
    padding: 10px;
    cursor: pointer;
    border-bottom: 1px solid #ddd;
    border-radius: 5px;
    background-color: #fff;
    transition: background-color 0.3s ease;
}

.contact:hover {
    background-color: #e0e0e0;
}

.contact .avatar {
    width: 40px;
    height: 40px;
    background-color: #4CAF50;
    color: white;
    font-size: 18px;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 50%;
    margin-right: 10px;
}

/* Chat Section */
.chat-section {
    flex: 1; /* Takes up the remaining space */
    display: flex;
    flex-direction: column;
    padding: 15px;
    background-color: #fff;
    overflow-y: auto; /* Enable scrolling for messages */
}

.profile-area h3 {
    margin: 0;
    padding: 10px;
    background-color: #f9f9f9;
    border-bottom: 1px solid #ddd;
}

.chat-area {
    flex: 1;
    overflow-y: auto;
    margin-top: 10px;
    padding: 10px;
}

.message {
    padding: 10px;
    margin: 5px 0;
    border-radius: 5px;
    max-width: 60%;
}

.message.sent {
    background-color: #4CAF50;
    color: white;
    align-self: flex-end;
}

.message.received {
    background-color: #ddd;
    align-self: flex-start;
}

/* Message Input */
.message-input {
    display: flex;
    padding: 10px;
    background-color: #f9f9f9;
    border-top: 1px solid #ddd;
}

.message-input input {
    flex: 1;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.message-input button {
    padding: 10px 15px;
    background-color: #4CAF50;
    color: white;
    border: none;
    margin-left: 10px;
    cursor: pointer;
    border-radius: 5px;
    transition: background-color 0.3s ease;
}

.message-input button:hover {
    background-color: #45a049;
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

        </style>
    <script>
        let selectedConversationId = null;
        let selectedParticipantId = null;
        let selectedUsername = "";

        // Fetch contacts (all users except the current user)
        async function fetchContacts() {
            const response = await fetch("", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "action=fetch_contacts",
            });
            const contacts = await response.json();
            const contactsContainer = document.querySelector(".contact-list");
            contactsContainer.innerHTML = "";
            contacts.forEach((contact) => {
                const contactElement = document.createElement("li");
                contactElement.className = "contact";
                contactElement.innerHTML = `
                    <div class="avatar">${contact.username.charAt(0)}</div>
                    <div class="name">${contact.username}</div>
                `;
                contactElement.dataset.participantId = contact.user_id;
                contactElement.dataset.username = contact.username;
                contactElement.onclick = () => fetchMessages(contact.user_id, contact.username);
                contactsContainer.appendChild(contactElement);
            });
        }

        // Fetch chat messages for a specific participant
        async function fetchMessages(participantId, username) {
            selectedParticipantId = participantId;
            selectedUsername = username;

            const response = await fetch("", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `action=fetch_messages&participant_id=${participantId}`,
            });
            const data = await response.json();
            selectedConversationId = data.conversation_id;

            const messagesContainer = document.querySelector(".messages");
            messagesContainer.innerHTML = "";
            data.messages.forEach((message) => {
                const messageElement = document.createElement("div");
                messageElement.className = message.sender_id === participantId ? "message received" : "message sent";
                messageElement.textContent = message.content;
                messagesContainer.appendChild(messageElement);
            });
            document.getElementById("student-name").textContent = `Chat with ${username}`;
        }

        // Send a message
        async function sendMessage() {
            const inputField = document.querySelector(".message-input input");
            const content = inputField.value;

            if (content && selectedConversationId) {
                await fetch("", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `action=send_message&conversation_id=${selectedConversationId}&content=${content}`,
                });
                inputField.value = "";
                fetchMessages(selectedParticipantId, selectedUsername); // Refresh chat
            }
        }

        // Initialize the chat interface
        document.addEventListener("DOMContentLoaded", () => {
            fetchContacts();
            document.querySelector(".message-input button").addEventListener("click", sendMessage);
        });

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
        <a href="quiz.php" class="nav-item">MODIFY QUESTIONS</a>
        <a href="leaderboard.php" class="nav-item">LEADERBOARD</a>
        <a href="chat.php" class="nav-item active">CHAT</a>
    <?php else: ?>
        <a href="quiz.php" class="nav-item">QUIZZES</a>
        <a href="leaderboard.php" class="nav-item">LEADERBOARD</a>
        <a href="chat.php" class="nav-item active">CHAT</a>
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
    <div class="chat-container">
        <!-- Contacts Section -->
        <aside class="contacts">
            <h3>Contacts</h3>
            <ul class="contact-list"></ul>
        </aside>

        <!-- Chat Section -->
        <section class="chat-section">
            <div class="profile-area">
                <h3 id="student-name">Select a user</h3>
            </div>
            <div class="chat-area">
                <div class="messages"></div>
            </div>
            <div class="message-input">
                <input type="text" placeholder="Type a message..." />
                <button>Send</button>
            </div>
        </section>
    </div>
</main>
</body>
</html>
