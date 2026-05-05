<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first.");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Welcome</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard-container {
            max-width: 600px;
            margin: 50px auto;
            text-align: center;
        }
        .logout-btn {
            background: #dc2626;
            margin-top: 20px;
        }
        .logout-btn:hover {
            background: #b91c1c;
        }
        .welcome-card {
            background: white;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .user-info {
            background: #f1f5f9;
            padding: 15px;
            border-radius: 12px;
            margin: 20px 0;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="welcome-card">
            <h1>🎉 Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
            <p>You have successfully logged in.</p>
            
            <div class="user-info">
                <p><strong>📧 Email:</strong> <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                <p><strong>🆔 User ID:</strong> <?php echo $_SESSION['user_id']; ?></p>
            </div>
            
            <a href="logout.php" class="submit-btn logout-btn" style="text-decoration: none; display: inline-block;">Logout</a>
        </div>
    </div>
</body>
</html>