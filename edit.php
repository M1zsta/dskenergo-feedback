<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die('Неверный ID');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = trim($_POST['status'] ?? '');
    $valid_statuses = ['новая', 'в работе', 'выполнена'];

    if (!in_array($status, $valid_statuses)) {
        $error = 'Неверный статус';
    } else {
        $stmt = $conn->prepare("UPDATE requests SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        $success = 'Статус обновлён';
        $stmt->close();
    }
}

$stmt = $conn->prepare("SELECT * FROM requests WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();

if (!$request) {
    die('Заявка не найдена');
}

include 'includes/header.php';
?>

<h1>Изменить статус заявки №<?= htmlspecialchars($id) ?></h1>

<?php if ($success): ?>
    <p class="success"><?= htmlspecialchars($success) ?></p>
<?php endif; ?>

<?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<p><strong>Клиент:</strong> <?= htmlspecialchars($request['client_name']) ?></p>
<p><strong>Телефон:</strong> <?= htmlspecialchars($request['phone']) ?></p>
<p><strong>Тема:</strong> <?= htmlspecialchars($request['topic']) ?></p>
<p><strong>Сообщение:</strong> <?= htmlspecialchars($request['request_text']) ?></p>

<form method="POST">
    <label>Статус:</label>
    <select name="status">
        <option value="новая" <?= $request['status'] === 'новая' ? 'selected' : '' ?>>новая</option>
        <option value="в работе" <?= $request['status'] === 'в работе' ? 'selected' : '' ?>>в работе</option>
        <option value="выполнена" <?= $request['status'] === 'выполнена' ? 'selected' : '' ?>>выполнена</option>
    </select>
    <button type="submit">Сохранить</button>
</form>

<p><a href="dashboard.php">← Назад к списку</a></p>

<?php include 'includes/footer.php'; ?>