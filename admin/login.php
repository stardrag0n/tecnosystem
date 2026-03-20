<?php
session_start();
require 'db.php';

// Se sei già loggato, vai alla dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Credenziali non valide!";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Tecno System</title>
    <style>
        body { font-family: sans-serif; background: #050505; color: white; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: #111; padding: 40px; border: 1px solid #333; border-radius: 8px; width: 100%; max-width: 320px; text-align: center; }
        input { width: 100%; padding: 10px; margin: 10px 0; background: #222; border: 1px solid #444; color: white; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #1a55e3; color: white; border: none; cursor: pointer; font-weight: bold; margin-top: 10px; }
        button:hover { background: #1340b0; }
        .error { color: #ff4444; margin-bottom: 10px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Area Riservata</h2>
        <?php if($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">ACCEDI</button>
        </form>
    </div>
</body>
</html>