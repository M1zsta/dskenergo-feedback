<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';

$sql = "SELECT * FROM requests WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (client_name LIKE ? OR topic LIKE ?)";
    $search_like = "%$search%";
    $params[] = $search_like;
    $params[] = $search_like;
    $types .= "ss";
}

if (!empty($status_filter)) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

include 'includes/header.php';
?>

<div class="user-info">
    Вы вошли как: <?= htmlspecialchars($_SESSION['login']) ?>
    <a href="logout.php">Выйти</a>
</div>

<h1>Список обращений</h1>

<form method="GET" action="" style="margin-bottom: 20px;">
    <input type="text" name="search" placeholder="Поиск по ФИО или теме" value="<?= htmlspecialchars($search) ?>">

    <select name="status">
        <option value="">Все статусы</option>
        <option value="новая" <?= $status_filter === 'новая' ? 'selected' : '' ?>>новая</option>
        <option value="в работе" <?= $status_filter === 'в работе' ? 'selected' : '' ?>>в работе</option>
        <option value="выполнена" <?= $status_filter === 'выполнена' ? 'selected' : '' ?>>выполнена</option>
    </select>

    <button type="submit">Найти</button>
    <a href="dashboard.php">Сбросить</a>
</form>

<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Клиент</th>
            <th>Телефон</th>
            <th>Тема</th>
            <th>Сообщение</th>
            <th>Статус</th>
            <th>Дата</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['client_name']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars($row['topic']) ?></td>
                <td><?= htmlspecialchars(substr($row['request_text'], 0, 50)) ?>...</td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td><?= htmlspecialchars($row['created_at']) ?></td>
                <td>
                    <a href="edit.php?id=<?= $row['id'] ?>" class="btn-edit">Изменить статус</a>
                    <a href="delete.php?id=<?= $row['id'] ?>" class="btn-delete" onclick="return confirm('Удалить заявку?')">Удалить</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php include 'includes/footer.php'; ?>