<?php
require_once __DIR__ . '/../config/Database.php';

$database = new Database();
$db = $database->getConnection();

$search = $_GET['search'] ?? null;
$sort = $_GET['sort'] ?? 'start_date';
$order = $_GET['order'] ?? 'asc';

$query = "SELECT id, title, start_date, description, links, created_at FROM weeks";
$params = [];

if ($search) {
    $query .= " WHERE title LIKE ? OR description LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$allowedSortFields = ['title', 'start_date', 'created_at'];
if (!in_array($sort, $allowedSortFields)) {
    $sort = 'start_date';
}

if (!in_array($order, ['asc', 'desc'])) {
    $order = 'asc';
}

$query .= " ORDER BY $sort $order";

$stmt = $db->prepare($query);
$stmt->execute($params);
$weeks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Course Breakdown</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Weekly Course Breakdown</h1>
    </header>
    
    <main>
        <section id="week-list-section">
            <?php foreach ($weeks as $week): ?>
            <article class="week-card">
                <h2><?= htmlspecialchars($week['title']) ?></h2>
                <p class="start-date">Starts on: <?= htmlspecialchars($week['start_date']) ?></p>
                <p><?= htmlspecialchars($week['description']) ?></p>
                <a href="details.php?id=<?= $week['id'] ?>" class="details-link">View Details & Discussion</a>
            </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
