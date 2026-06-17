<?php
require_once 'config/database.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_name = trim($_POST['client_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $topic = trim($_POST['topic'] ?? '');
    $request_text = trim($_POST['request_text'] ?? '');

    if (empty($client_name) || empty($phone) || empty($topic) || empty($request_text)) {
        $error = 'Заполните все обязательные поля';
    } else {
        $stmt = $conn->prepare("INSERT INTO requests (client_name, phone, topic, request_text) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $client_name, $phone, $topic, $request_text);
        $stmt->execute();
        $success = 'Заявка успешно отправлена';
        $stmt->close();
    }
}

include 'includes/header.php';
?>

<h1>Форма обращения</h1>

<?php if ($success): ?>
    <p class="success"><?= htmlspecialchars($success) ?></p>
<?php endif; ?>

<?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST" action="">
    <label>ФИО:</label>
    <input type="text" name="client_name" required>

    <label>Телефон:</label>
    <input type="tel" name="phone" required>

    <label>Тема:</label>
    <input type="text" name="topic" required>

    <label>Сообщение:</label>
    <textarea name="request_text" required></textarea>

    <button type="submit">Отправить</button>
</form>

<p><a href="check_status.php">Проверить статус заявки</a></p>

<?php include 'includes/footer.php'; ?>