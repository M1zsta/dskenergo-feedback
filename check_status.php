<?php
require_once 'config/database.php';

$status = '';
$error = '';
$requests = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_name = trim($_POST['client_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($client_name) || empty($phone)) {
        $error = 'Введите ФИО и телефон';
    } else {
        $stmt = $conn->prepare("SELECT * FROM requests WHERE client_name = ? AND phone = ? ORDER BY created_at DESC");
        $stmt->bind_param("ss", $client_name, $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        $requests = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (empty($requests)) {
            $error = 'Заявки не найдены';
        }
    }
}

include 'includes/header.php';
?>

<h1>Проверка статуса заявки</h1>

<?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST" action="">
    <label>ФИО:</label>
    <input type="text" name="client_name" required>

    <label>Телефон:</label>
    <input type="tel" name="phone" required>

    <button type="submit">Проверить</button>
</form>

<?php if (!empty($requests)): ?>
    <h2>Ваши заявки:</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Тема</th>
                <th>Статус</th>
                <th>Дата</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['topic']) ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<p><a href="index.php">← Назад к форме обращения</a></p>

<?php include 'includes/footer.php'; ?>