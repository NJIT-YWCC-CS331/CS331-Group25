<?php
session_start();
require 'db.php';

// Force login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$departure_airport = $_GET['departure_airport'] ?? '';
$arrival_airport   = $_GET['arrival_airport'] ?? '';
$depart_date       = $_GET['depart_date'] ?? '';

$airports = [];
$flights  = [];

// 1) Get list of airports for dropdowns
$airportSql = "SELECT airport_code, city, country FROM Airport ORDER BY city, country";
$airportRes = mysqli_query($conn, $airportSql);
if ($airportRes) {
    while ($row = mysqli_fetch_assoc($airportRes)) {
        $airports[] = $row;
    }
}

// 2) If user submitted search, build query
if ($departure_airport !== '' || $arrival_airport !== '' || $depart_date !== '') {
    $conditions = [];

    if ($departure_airport !== '') {
        $safe = mysqli_real_escape_string($conn, $departure_airport);
        $conditions[] = "f.departure_airport = '{$safe}'";
    }

    if ($arrival_airport !== '') {
        $safe = mysqli_real_escape_string($conn, $arrival_airport);
        $conditions[] = "f.arrival_airport = '{$safe}'";
    }

    if ($depart_date !== '') {
        $safe = mysqli_real_escape_string($conn, $depart_date);
        $conditions[] = "DATE(f.departure_time) = '{$safe}'";
    }

    $where = '';
    if (!empty($conditions)) {
        $where = 'WHERE ' . implode(' AND ', $conditions);
    }

    $sql = "
        SELECT
            f.flight_number,
            f.departure_time,
            f.arrival_time,
            f.duration_minutes,
            da.city  AS dep_city,
            da.country AS dep_country,
            aa.city  AS arr_city,
            aa.country AS arr_country,
            a.name   AS airline_name
        FROM Flight AS f
        JOIN Airport AS da
            ON f.departure_airport = da.airport_code
        JOIN Airport AS aa
            ON f.arrival_airport = aa.airport_code
        JOIN Airline_Company AS a
            ON f.airline_id = a.airline_id
        {$where}
        ORDER BY f.departure_time
    ";

    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $flights[] = $row;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Flights</title>
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
        .layout {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 28px;
            max-width: 1100px;
            width: 100%;
        }
        .card {
            background: #020617;
            border-radius: 12px;
            padding: 20px 22px;
            box-shadow: 0 20px 45px rgba(0,0,0,0.65);
        }
        h1 {
            margin-top: 0;
            font-size: 1.4rem;
            margin-bottom: 12px;
        }
        label {
            display: block;
            font-size: 0.9rem;
            margin-bottom: 4px;
        }
        select, input[type="date"] {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 12px;
            border-radius: 6px;
            border: 1px solid #4b5563;
            background: #020617;
            color: #e5e7eb;
        }
        button {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: none;
            background: #3b82f6;
            color: white;
            font-size: 1rem;
            cursor: pointer;
        }
        button:hover {
            background: #2563eb;
        }
        .results h2 {
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 1.2rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        th, td {
            padding: 8px 10px;
            border-bottom: 1px solid #1f2937;
        }
        th {
            text-align: left;
            background: #0f172a;
        }
        tr:nth-child(even) td {
            background: #020617;
        }
        .pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 0.75rem;
            background: #1d4ed8;
            color: #e5e7eb;
        }
        .muted {
            color: #9ca3af;
            font-size: 0.85rem;
        }
        .no-results {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #9ca3af;
        }
    </style>
</head>
<body>
<header>
    <div class="title">✈ Flight Ticket Booking System</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="search_flights.php">Search Flights</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>
    <div class="layout">
        <div class="card">
            <h1>Search Flights</h1>
            <form method="GET" action="search_flights.php">
                <label for="departure_airport">From (Departure Airport)</label>
                <select name="departure_airport" id="departure_airport">
                    <option value="">Any</option>
                    <?php foreach ($airports as $a): ?>
                        <?php
                        $code = $a['airport_code'];
                        $label = $a['city'] . " (" . $a['country'] . ") - " . $code;
                        $selected = ($departure_airport === $code) ? 'selected' : '';
                        ?>
                        <option value="<?= htmlspecialchars($code) ?>" <?= $selected ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="arrival_airport">To (Arrival Airport)</label>
                <select name="arrival_airport" id="arrival_airport">
                    <option value="">Any</option>
                    <?php foreach ($airports as $a): ?>
                        <?php
                        $code = $a['airport_code'];
                        $label = $a['city'] . " (" . $a['country'] . ") - " . $code;
                        $selected = ($arrival_airport === $code) ? 'selected' : '';
                        ?>
                        <option value="<?= htmlspecialchars($code) ?>" <?= $selected ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="depart_date">Departure Date</label>
                <input type="date" name="depart_date" id="depart_date"
                       value="<?= htmlspecialchars($depart_date) ?>">

                <button type="submit">Search</button>
            </form>
        </div>

        <div class="card results">
            <h2>Results</h2>

            <?php if ($departure_airport === '' && $arrival_airport === '' && $depart_date === ''): ?>
                <p class="no-results">
                    Use the form on the left to search flights by route and date.
                </p>
            <?php elseif (empty($flights)): ?>
                <p class="no-results">
                    No flights found for the selected criteria.
                </p>
            <?php else: ?>
                <table>
                    <thead>
                    <tr>
                        <th>Flight</th>
                        <th>Route</th>
                        <th>Departure</th>
                        <th>Arrival</th>
                        <th>Duration</th>
                        <th>Airline</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($flights as $f): ?>
                        <tr>
                            <td>
                                <span class="pill"><?= htmlspecialchars($f['flight_number']) ?></span>
                            </td>
                            <td>
                                <?= htmlspecialchars($f['dep_city']) ?> → <?= htmlspecialchars($f['arr_city']) ?><br>
                                <span class="muted">
                                    <?= htmlspecialchars($f['dep_country']) ?> → <?= htmlspecialchars($f['arr_country']) ?>
                                </span>
                            </td>
                            <td>
                                <?= htmlspecialchars($f['departure_time']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($f['arrival_time']) ?>
                            </td>
                            <td>
                                <?= (int)$f['duration_minutes'] ?> min
                            </td>
                            <td>
                                <?= htmlspecialchars($f['airline_name']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>
