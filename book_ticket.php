<?php
session_start();
require 'db.php';

// Force login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$errors = [];
$success = "";
$flights = [];

// 1) Get list of flights for dropdown
$flightSql = "
    SELECT
        f.flight_number,
        da.city  AS dep_city,
        aa.city  AS arr_city,
        f.departure_time
    FROM Flight AS f
    JOIN Airport AS da ON f.departure_airport = da.airport_code
    JOIN Airport AS aa ON f.arrival_airport   = aa.airport_code
    ORDER BY f.departure_time
";
$res = mysqli_query($conn, $flightSql);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $flights[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2) Grab form data
    $flight_number   = $_POST['flight_number'] ?? '';
    $passport_number = trim($_POST['passport_number'] ?? '');
    $passenger_name  = trim($_POST['name'] ?? '');
    $dob             = $_POST['date_of_birth'] ?? '';
    $nationality     = trim($_POST['nationality'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $seat_number     = trim($_POST['seat_number'] ?? '');
    $class           = $_POST['class'] ?? '';
    $amount          = $_POST['amount'] ?? '';
    $method          = $_POST['method'] ?? '';

    // 3) Basic validation
    if ($flight_number === '') $errors[] = "Select a flight.";
    if ($passport_number === '') $errors[] = "Passport number is required.";
    if ($passenger_name === '') $errors[] = "Passenger name is required.";
    if ($dob === '') $errors[] = "Date of birth is required.";
    if ($nationality === '') $errors[] = "Nationality is required.";
    if ($seat_number === '') $errors[] = "Seat number is required.";
    if (!in_array($class, ['Economy','Business','First'])) {
        $errors[] = "Choose a valid class.";
    }
    if ($amount === '' || !is_numeric($amount) || $amount <= 0) {
        $errors[] = "Enter a valid payment amount.";
    }
    if (!in_array($method, ['credit card','debit card','wallet'])) {
        $errors[] = "Choose a valid payment method.";
    }

    if (empty($errors)) {
        // 4) Insert passenger
        $pSql = "
            INSERT INTO Passenger (passport_number, name, date_of_birth, nationality, phone, email)
            VALUES (?, ?, ?, ?, ?, ?)
        ";
        $pStmt = mysqli_prepare($conn, $pSql);
        mysqli_stmt_bind_param(
            $pStmt,
            "ssssss",
            $passport_number,
            $passenger_name,
            $dob,
            $nationality,
            $phone,
            $email
        );

        if (!mysqli_stmt_execute($pStmt)) {
            $errors[] = "Could not create passenger record.";
        } else {
            $passenger_id = mysqli_insert_id($conn);

            // 5) Generate a simple unique ticket_number & payment_id
            // (for demo purposes)
            $ticket_number  = time() . rand(100, 999); // BIGINT
            $payment_id     = time() . rand(1000, 9999);

            // 6) Insert ticket
            $today = date('Y-m-d');
            $tSql = "
                INSERT INTO Ticket (ticket_number, booking_date, seat_number, class, flight_number, passenger_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ";
            $tStmt = mysqli_prepare($conn, $tSql);
            mysqli_stmt_bind_param(
                $tStmt,
                "issssi",
                $ticket_number,
                $today,
                $seat_number,
                $class,
                $flight_number,
                $passenger_id
            );

            if (!mysqli_stmt_execute($tStmt)) {
                $errors[] = "Could not create ticket.";
            } else {
                // 7) Insert payment record
                $payDate = $today;
                $paySql = "
                    INSERT INTO Payment_Record (payment_id, payment_date, amount, method, passenger_id)
                    VALUES (?, ?, ?, ?, ?)
                ";
                $payStmt = mysqli_prepare($conn, $paySql);
                mysqli_stmt_bind_param(
                    $payStmt,
                    "isdsi",
                    $payment_id,
                    $payDate,
                    $amount,
                    $method,
                    $passenger_id
                );

                if (!mysqli_stmt_execute($payStmt)) {
                    $errors[] = "Ticket created, but payment record failed.";
                } else {
                    $success = "Ticket booked successfully! Your ticket number is {$ticket_number}.";
                    // Clear form fields
                    $flight_number = $passport_number = $passenger_name = $dob =
                    $nationality = $phone = $email = $seat_number = $class = $amount = $method = "";
                }

                mysqli_stmt_close($payStmt);
            }

            mysqli_stmt_close($tStmt);
        }

        mysqli_stmt_close($pStmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book a Ticket</title>
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
        main {
            flex: 1;
            display: flex;
            justify-content: center;
            padding: 32px 16px;
        }
        .card {
            background: #020617;
            border-radius: 12px;
            padding: 22px 24px;
            box-shadow: 0 20px 45px rgba(0,0,0,0.65);
            max-width: 640px;
            width: 100%;
        }
        h1 {
            margin-top: 0;
            margin-bottom: 12px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 18px;
        }
        label {
            display: block;
            font-size: 0.9rem;
            margin-bottom: 4px;
        }
        input[type="text"],
        input[type="date"],
        input[type="number"],
        select {
            width: 100%;
            padding: 7px 9px;
            border-radius: 6px;
            border: 1px solid #4b5563;
            background: #020617;
            color: #e5e7eb;
        }
        button {
            margin-top: 16px;
            width: 100%;
            padding: 11px;
            border-radius: 6px;
            border: none;
            background: #22c55e;
            color: #022c22;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
        }
        button:hover {
            background: #16a34a;
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
    </style>
</head>
<body>
<header>
    <div class="title">✈ Flight Ticket Booking System</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="search_flights.php">Search Flights</a>
        <a href="book_ticket.php">Book Ticket</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>
    <div class="card">
        <h1>Book a Ticket</h1>

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

        <form method="POST" action="book_ticket.php">
            <div class="grid">
                <div>
                    <label for="flight_number">Flight</label>
                    <select name="flight_number" id="flight_number" required>
                        <option value="">Select a flight</option>
                        <?php foreach ($flights as $f): ?>
                            <?php
                            $code = $f['flight_number'];
                            $label = $code . " – " . $f['dep_city'] . " → " . $f['arr_city'] .
                                     " (" . $f['departure_time'] . ")";
                            $selected = (isset($flight_number) && $flight_number === $code) ? 'selected' : '';
                            ?>
                            <option value="<?= htmlspecialchars($code) ?>" <?= $selected ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="passport_number">Passport Number</label>
                    <input type="text" name="passport_number" id="passport_number"
                           value="<?= htmlspecialchars($passport_number ?? '') ?>" required>
                </div>

                <div>
                    <label for="name">Passenger Name</label>
                    <input type="text" name="name" id="name"
                           value="<?= htmlspecialchars($passenger_name ?? '') ?>" required>
                </div>

                <div>
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" name="date_of_birth" id="date_of_birth"
                           value="<?= htmlspecialchars($dob ?? '') ?>" required>
                </div>

                <div>
                    <label for="nationality">Nationality</label>
                    <input type="text" name="nationality" id="nationality"
                           value="<?= htmlspecialchars($nationality ?? '') ?>" required>
                </div>

                <div>
                    <label for="phone">Phone</label>
                    <input type="text" name="phone" id="phone"
                           value="<?= htmlspecialchars($phone ?? '') ?>">
                </div>

                <div>
                    <label for="email">Email</label>
                    <input type="text" name="email" id="email"
                           value="<?= htmlspecialchars($email ?? '') ?>">
                </div>

                <div>
                    <label for="seat_number">Seat Number</label>
                    <input type="text" name="seat_number" id="seat_number"
                           value="<?= htmlspecialchars($seat_number ?? '') ?>" required>
                </div>

                <div>
                    <label for="class">Class</label>
                    <select name="class" id="class" required>
                        <option value="">Select</option>
                        <option value="Economy" <?= (isset($class) && $class === 'Economy') ? 'selected' : '' ?>>Economy</option>
                        <option value="Business" <?= (isset($class) && $class === 'Business') ? 'selected' : '' ?>>Business</option>
                        <option value="First" <?= (isset($class) && $class === 'First') ? 'selected' : '' ?>>First</option>
                    </select>
                </div>

                <div>
                    <label for="amount">Payment Amount</label>
                    <input type="number" step="0.01" name="amount" id="amount"
                           value="<?= htmlspecialchars($amount ?? '') ?>" required>
                </div>

                <div>
                    <label for="method">Payment Method</label>
                    <select name="method" id="method" required>
                        <option value="">Select</option>
                        <option value="credit card" <?= (isset($method) && $method === 'credit card') ? 'selected' : '' ?>>Credit card</option>
                        <option value="debit card"  <?= (isset($method) && $method === 'debit card')  ? 'selected' : '' ?>>Debit card</option>
                        <option value="wallet"      <?= (isset($method) && $method === 'wallet')      ? 'selected' : '' ?>>Wallet</option>
                    </select>
                </div>
            </div>

            <button type="submit">Confirm Booking</button>
        </form>
    </div>
</main>
</body>
</html>
