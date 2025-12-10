<?php
require_once __DIR__ . '/../config/Database.php';

$database = new Database();
$db = $database->getConnection();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            $title = htmlspecialchars(trim($_POST['week-title']));
            $startDate = $_POST['week-start-date'];
            $description = htmlspecialchars(trim($_POST['week-description']));
            $linksText = $_POST['week-links'] ?? '';
            
            $links = array_filter(array_map('trim', explode("\n", $linksText)));
            $linksJson = json_encode($links);
            
            $stmt = $db->prepare("INSERT INTO weeks (title, start_date, description, links) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$title, $startDate, $description, $linksJson])) {
                $message = 'Week added successfully!';
            } else {
                $message = 'Error adding week.';
            }
        } elseif ($action === 'delete' && isset($_POST['week-id'])) {
            $weekId = $_POST['week-id'];
            
            $stmt = $db->prepare("DELETE FROM comments_week WHERE week_id = ?");
            $stmt->execute([$weekId]);
            
            $stmt = $db->prepare("DELETE FROM weeks WHERE id = ?");
            if ($stmt->execute([$weekId])) {
                $message = 'Week deleted successfully!';
            } else {
                $message = 'Error deleting week.';
            }
        }
    }
}

$stmt = $db->prepare("SELECT id, title, start_date, description, links, created_at FROM weeks ORDER BY start_date ASC");
$stmt->execute();
$weeks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Weekly Breakdown</title>
    <link rel="stylesheet" href="style.css">
    <script src="admin.js" defer></script>
</head>
<body>
    <header>
        <h1>Manage Weekly Breakdown</h1>
    </header>
    
    <main>
        <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <section>
            <h2>Add a New Week</h2>
            
            <form id="week-form" action="admin.php" method="POST">
                <input type="hidden" name="action" value="add">
                <fieldset>
                    <legend>Weekly Details</legend>
                    
                    <label for="week-title">Week Title:</label>
                    <input type="text" id="week-title" name="week-title" placeholder="Week 1: Introduction to HTML" required>
                    
                    <label for="week-start-date">Start Date:</label>
                    <input type="date" id="week-start-date" name="week-start-date" required>
                    
                    <label for="week-description">Description & Notes:</label>
                    <textarea id="week-description" name="week-description" rows="5" placeholder="Enter week description and any important notes..."></textarea>
                    
                    <label for="week-links">Exercise/Resource Links (one per line):</label>
                    <textarea id="week-links" name="week-links" rows="3" placeholder="https://example.com/exercise1&#10;https://example.com/resource1"></textarea>
                    
                    <button type="submit" id="add-week">Add Week</button>
                </fieldset>
            </form>
        </section>
        
        <section>
            <h2>Current Weekly Breakdown</h2>
            
            <table id="weeks-table">
                <thead>
                    <tr>
                        <th>Week Title</th>
                        <th>Start Date</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                
                <tbody id="weeks-tbody">
                    <?php foreach ($weeks as $week): ?>
                    <tr>
                        <td><?= htmlspecialchars($week['title']) ?></td>
                        <td><?= htmlspecialchars($week['start_date']) ?></td>
                        <td><?= htmlspecialchars(substr($week['description'], 0, 100)) . (strlen($week['description']) > 100 ? '...' : '') ?></td>
                        <td class="action-buttons">
                            <a href="details.php?id=<?= $week['id'] ?>" class="view-btn">View</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this week?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="week-id" value="<?= $week['id'] ?>">
                                <button type="submit" class="delete-btn">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
