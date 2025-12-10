<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$name  = $_SESSION['name']  ?? 'User';
$email = $_SESSION['email'] ?? '';
$role  = $_SESSION['role']  ?? 'user';
$isAdmin = ($role === 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard – Flight Ticket Booking System</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #020617;
            color: #e5e7eb;
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
        }
        header {
            padding: 12px 24px;
            background: #0f172a;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        }
        header .title {
            font-size: 1.1rem;
        }
        header nav a {
            margin-left: 16px;
            color: #bfdbfe;
            text-decoration: none;
            font-size: 0.9rem;
        }
        header nav a:hover {
            text-decoration: underline;
        }
        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 32px 16px;
        }
        .card {
            background: #020617;
            padding: 28px 32px;
            border-radius: 16px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.7);
            max-width: 560px;
            width: 100%;
            text-align: center;
        }
        h1 {
            margin: 0 0 6px;
            font-size: 1.7rem;
        }
        .meta {
            font-size: 0.9rem;
            margin-bottom: 18px;
            color: #9ca3af;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.75rem;
            background: #1d4ed8;
            color: #e5e7eb;
            margin-left: 6px;
        }
        .badge.admin {
            background: #f97316;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-top: 18px;
        }
        .btn {
            display: inline-block;
            padding: 9px 18px;
            border-radius: 999px;
            border: none;
            text-decoration: none;
            font-size: 0.95rem;
            cursor: pointer;
        }
        .btn-primary {
            background: #3b82f6;
            color: #e5e7eb;
        }
        .btn-primary:hover {
            background: #2563eb;
        }
        .btn-secondary {
            background: #22c55e;
            color: #022c22;
            font-weight: 600;
        }
        .btn-secondary:hover {
            background: #16a34a;
        }
        .btn-outline {
            background: transparent;
            border: 1px solid #4b5563;
            color: #e5e7eb;
        }
        .btn-outline:hover {
            background: #111827;
        }
        .section-title {
            margin-top: 20px;
            font-size: 0.95rem;
            font-weight: 600;
        }
        ul {
            list-style: disc;
            text-align: left;
            margin: 8px auto 0;
            padding-left: 20px;
            max-width: 360px;
            font-size: 0.9rem;
            color: #9ca3af;
        }
        .logout {
            margin-top: 20px;
        }
        .logout a {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 999px;
            background: #dc2626;
            color: #fdecec;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .logout a:hover {
            background: #b91c1c;
        }
    </style>
</head>
<body>
<header>
    <div class="title">✈ Flight Ticket Booking System</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="search_flights.php">Search Flights</a>
        <a href="book_ticket.php">Book Ticket</a>
        <?php if ($isAdmin): ?>
            <a href="admin_users.php">Admin – Users</a>
            <a href="admin_tickets.php">Admin – Tickets</a>
        <?php endif; ?>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>
    <div class="card">
        <h1>Welcome, <?= htmlspecialchars($name) ?> 👋</h1>
        <div class="meta">
            Logged in as <strong><?= htmlspecialchars($email) ?></strong><br>
            Role:
            <span class="badge <?= $isAdmin ? 'admin' : '' ?>">
                <?= htmlspecialchars($role) ?>
            </span>
        </div>

        <div class="section-title">Quick actions</div>
        <div class="actions">
            <a href="search_flights.php" class="btn btn-primary">🔍 Search Flights</a>
            <a href="book_ticket.php" class="btn btn-secondary">🎫 Book a Ticket</a>
        </div>

        <?php if ($isAdmin): ?>
            <div class="section-title">Admin tools</div>
            <div class="actions">
                <a href="admin_users.php" class="btn btn-outline">👥 Manage Users</a>
                <a href="admin_tickets.php" class="btn btn-outline">📊 View All Bookings</a>
            </div>
        <?php endif; ?>

        <div class="logout">
            <a href="logout.php">Log Out</a>
        </div>
    </div>
</main>
</body>
</html>
