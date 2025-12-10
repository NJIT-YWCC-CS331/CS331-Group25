<?php
session_start();
require 'db.php';

$email = "";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email.";
    }

    if ($password === '') {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        $sql  = "SELECT id, name, email, password, role FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password'])) {
            // login success → store in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            header("Location: index.php"); // send them to home/profile page
            exit;
        } else {
            $errors[] = "Incorrect email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Log In</title>
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
    <h1>Log In</h1>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $e): ?>
                • <?= htmlspecialchars($e) ?><br>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="field">
            <label for="email">Email address</label>
            <input type="email" name="email" id="email"
                   value="<?= htmlspecialchars($email) ?>" required>
        </div>

        <div class="field">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>
        </div>

        <button type="submit">Log In</button>
    </form>

    <div class="small-link">
        Don’t have an account? <a href="register.php">Create one</a>
    </div>
</div>
</body>
</html>
