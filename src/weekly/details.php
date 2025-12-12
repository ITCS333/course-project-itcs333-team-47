<?php
require_once __DIR__ . '/../config/Database.php';

$database = new Database();
$db = $database->getConnection();

$weekId = $_GET['id'] ?? null;

if (!$weekId) {
    die('Week ID is required');
}

$stmt = $db->prepare("SELECT id, title, start_date, description, links, created_at FROM weeks WHERE id = ?");
$stmt->execute([$weekId]);
$week = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$week) {
    die('Week not found');
}

$week['links'] = json_decode($week['links'], true) ?? [];

$stmt = $db->prepare("SELECT id, week_id, author, text, created_at FROM comments_week WHERE week_id = ? ORDER BY created_at ASC");
$stmt->execute([$weekId]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new-comment'])) {
    $author = htmlspecialchars(trim($_POST['author'] ?? 'Student'));
    $text = htmlspecialchars(trim($_POST['new-comment']));
    
    if (!empty($text)) {
        $stmt = $db->prepare("INSERT INTO comments_week (week_id, author, text) VALUES (?, ?, ?)");
        $stmt->execute([$weekId, $author, $text]);
        header("Location: details.php?id=$weekId");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($week['title']) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1 id="week-title"><?= htmlspecialchars($week['title']) ?></h1>
    </header>
    
    <main>
        <article>
            <p class="start-date" id="week-start-date">Starts on: <?= htmlspecialchars($week['start_date']) ?></p>
            
            <h2>Description & Notes</h2>
            <p id="week-description"><?= htmlspecialchars($week['description']) ?></p>
            
            <h2>Exercises & Resources</h2>
            <ul id="week-links-list">
                <?php foreach ($week['links'] as $link): ?>
                <li><a href="<?= htmlspecialchars($link) ?>" target="_blank"><?= htmlspecialchars($link) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </article>
        
        <section id="discussion-forum">
            <h2>Discussion</h2>
            
            <div class="comments-container" id="comment-list">
                <?php foreach ($comments as $comment): ?>
                <article class="comment">
                    <p><?= htmlspecialchars($comment['text']) ?></p>
                    <footer>Posted by: <?= htmlspecialchars($comment['author']) ?> on <?= htmlspecialchars($comment['created_at']) ?></footer>
                </article>
                <?php endforeach; ?>
            </div>
            
            <form action="details.php?id=<?= $weekId ?>" method="POST" id="comment-form">
                <fieldset>
                    <legend>Ask a Question</legend>
                    
                    <label for="author">Your Name:</label>
                    <input type="text" id="author" name="author" placeholder="Enter your name" value="Student">
                    
                    <label for="new-comment">Your Comment:</label>
                    <textarea id="new-comment" name="new-comment" rows="5" placeholder="Type your question or comment here..." required></textarea>
                    
                    <button type="submit">Post Comment</button>
                </fieldset>
            </form>
        </section>
    </main>
</body>
</html>
