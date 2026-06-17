<?php
session_start();
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        $error = 'Введите логин и пароль';
    } else {
        $stmt = $conn->prepare("SELECT id, login, password, role FROM users WHERE login = ?");
        $stmt->bind_param("s", $login);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['login'] = $user['login'];
            $_SESSION['role'] = $user['role'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Неверный логин или пароль';
        }
        $stmt->close();
    }
}

include 'includes/header.php';
?>

<h1>Вход в систему</h1>

<?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST" action="">
    <label>Логин:</label>
    <input type="text" name="login" required>

    <label>Пароль:</label>
    <input type="password" name="password" required>

    <button type="submit">Войти</button>
</form>

<?php include 'includes/footer.php'; ?>