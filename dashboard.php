<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';
$per_page = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Валидация сортировки
$allowed_sort = ['id', 'client_name', 'status', 'created_at'];
$allowed_order = ['ASC', 'DESC'];
if (!in_array($sort, $allowed_sort)) $sort = 'created_at';
if (!in_array(strtoupper($order), $allowed_order)) $order = 'DESC';
$order = strtoupper($order);

// Подсчёт общего количества
$count_sql = "SELECT COUNT(*) as total FROM requests WHERE 1=1";
$count_params = [];
$count_types = "";

if (!empty($search)) {
    $count_sql .= " AND (client_name LIKE ? OR topic LIKE ?)";
    $search_like = "%$search%";
    $count_params[] = $search_like;
    $count_params[] = $search_like;
    $count_types .= "ss";
}

if (!empty($status_filter)) {
    $count_sql .= " AND status = ?";
    $count_params[] = $status_filter;
    $count_types .= "s";
}

$count_stmt = $conn->prepare($count_sql);
if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

// Основной запрос с сортировкой
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

$sql .= " ORDER BY $sort $order LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Функция для ссылки сортировки
function sort_link($column, $label, $current_sort, $current_order, $search, $status_filter)
{
    $new_order = ($current_sort === $column && $current_order === 'DESC') ? 'ASC' : 'DESC';
    $arrow = '';
    if ($current_sort === $column) {
        $arrow = $current_order === 'DESC' ? ' ▼' : ' ▲';
    }
    return "<a href=\"?sort=$column&order=$new_order&search=" . urlencode($search) . "&status=" . urlencode($status_filter) . "\">$label$arrow</a>";
}

include 'includes/header.php';
?>

<div class="user-info">
    Вы вошли как: <?= htmlspecialchars($_SESSION['login']) ?>
    <a href="logout.php">Выйти</a>
</div>

<h1>Список обращений</h1>

<form method="GET" action="" style="margin-bottom: 20px;">
    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
    <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">
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
            <th><?= sort_link('id', 'ID', $sort, $order, $search, $status_filter) ?></th>
            <th>Клиент</th>
            <th>Телефон</th>
            <th>Тема</th>
            <th>Сообщение</th>
            <th><?= sort_link('status', 'Статус', $sort, $order, $search, $status_filter) ?></th>
            <th><?= sort_link('created_at', 'Дата', $sort, $order, $search, $status_filter) ?></th>
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

<?php if ($total_pages > 1): ?>
    <div style="margin-top: 20px;">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&sort=<?= $sort ?>&order=<?= $order ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>">← Назад</a>
        <?php endif; ?>

        <span>Страница <?= $page ?> из <?= $total_pages ?></span>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page + 1 ?>&sort=<?= $sort ?>&order=<?= $order ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>">Вперёд →</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>     