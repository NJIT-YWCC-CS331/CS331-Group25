<?php
session_start();
require 'db.php'; // use your working DB connection

$name = $email = "";
$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and clean input
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Basic validation
    if ($name === '') {
        $errors[] = "Name is required.";
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email is required.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        // 1) Check if email already exists
        $checkSql = "SELECT id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $checkSql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "That email is already registered.";
        } else {
            // 2) Insert new user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $role = "user";

            $insertSql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
            $insertStmt = mysqli_prepare($conn, $insertSql);
            mysqli_stmt_bind_param($insertStmt, "ssss", $name, $email, $hashedPassword, $role);

            if (mysqli_stmt_execute($insertStmt)) {
                $success = "Account created! You can now log in.";
                // clear the form
                $name = "";
                $email = "";
            } else {
                $errors[] = "Something went wrong. Please try again.";
            }

            mysqli_stmt_close($insertStmt);
        }

        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #0f172a;
            color: #e5e7eb;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .card {
            background: #020617;
            padding: 24px 28px;
            border-radius: 12px;
            box-shadow: 0 18px 45px rgba(0,0,0,0.55);
            width: 360px;
        }
        h1 {
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 1.5rem;
        }
        .field {
            margin-bottom: 12px;
        }
        label {
            display: block;
            font-size: 0.9rem;
            margin-bottom: 4px;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 8px 10px;
            border-radius: 6px;
            border: 1px solid #4b5563;
            background: #020617;
            color: #e5e7eb;
        }
        button {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border: none;
            border-radius: 6px;
            background: #3b82f6;
            color: white;
            font-size: 1rem;
            cursor: pointer;
        }
        button:hover {
            background: #2563eb;
        }
        .errors {
            background: #7f1d1d;
            padding: 8px 10px;
            border-radius: 6px;
            margin-bottom: 10px;
            font-size: 0.85rem;
        }
        .success {
            background: #14532d;
            padding: 8px 10px;
            border-radius: 6px;
            margin-bottom: 10px;
            font-size: 0.85rem;
        }
        .small-link {
            margin-top: 8px;
            font-size: 0.85rem;
            text-align: center;
        }
        .small-link a {
            color: #93c5fd;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="card">
    <h1>Create an Account</h1>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $e): ?>
                • <?= htmlspecialchars($e) ?><br>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <div class="field">
            <label for="name">Full Name</label>
            <input type="text" name="name" id="name"
                   value="<?= htmlspecialchars($name) ?>" required>
        </div>

        <div class="field">
            <label for="email">Email address</label>
            <input type="email" name="email" id="email"
                   value="<?= htmlspecialchars($email) ?>" required>
        </div>

        <div class="field">
            <label for="password">Password (min 6 characters)</label>
            <input type="password" name="password" id="password" required>
        </div>

        <div class="field">
            <label for="confirm_password">Confirm password</label>
            <input type="password" name="confirm_password" id="confirm_password" required>
        </div>

        <button type="submit">Sign Up</button>
    </form>

    <div class="small-link">
        Already have an account?
    </div>
</div>
</body>
</html>
