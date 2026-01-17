<?php
session_start();
require_once 'db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role_name = trim($_POST['role'] ?? '');
    $special_code = trim($_POST['special_code'] ?? '');

    if (!$username || !$email || !$password || !$role_name || !$special_code) {
        $errors[] = "Όλα τα πεδία είναι υποχρεωτικά.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Το password πρέπει να έχει τουλάχιστον 8 χαρακτήρες.";
    }

    // Role
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE role_name = :role");
        $stmt->execute(['role' => $role_name]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$role) {
            $errors[] = "Μη έγκυρος ρόλος.";
        } else {
            $role_id = $role['id'];
        }
    }

    // Special code
    if (empty($errors)) {
        if (
            ($role_name === 'student' && $special_code !== 'STUD2025') ||
            ($role_name === 'teacher' && $special_code !== 'PROF2025')
        ) {
            $errors[] = "Λάθος ειδικός κωδικός εγγραφής!";
        }
    }

    // Existing user
    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "SELECT id FROM users WHERE username = :u OR email = :e"
        );
        $stmt->execute(['u' => $username, 'e' => $email]);
        if ($stmt->fetch()) {
            $errors[] = "Το username ή email χρησιμοποιείται ήδη.";
        }
    }

    // Insert
if (empty($errors)) {
    $stmt = $pdo->prepare(
        "INSERT INTO users (username,email,password,role_id)
         VALUES (:u,:e,:p,:r)"
    );
    $stmt->execute([
        'u' => $username,
        'e' => $email,
        'p' => password_hash($password, PASSWORD_DEFAULT),
        'r' => $role_id
    ]);

    // Auto login μετά την εγγραφή 
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role_name;

    header("Location: dashboard.php");
    exit;
    }
}

?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stratford University - Εγγραφή</title>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { line-height: 1.6; background-color: #E8E5FF; }
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color:#000047;
            padding: 1rem 2rem;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }
        .logo img { height: 60px; }
        .nav-links { list-style: none; display: flex; }
        .nav-links li { margin-left: 1.5rem; }
        .nav-links a { color: #fff; text-decoration: none; }

        .form-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height:100vh;
            padding-top: 120px;
        }

        .form-container {
            background-color: #fff;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 550px;
            width: 100%;
        }

        h2 { text-align: center; margin-bottom: 1rem; color: #004080; }
        form { display: flex; flex-direction: column; }
        input, select {
            padding: 0.8rem;
            margin-bottom: 1rem;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            padding: 0.8rem;
            background-color: #004080;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover { background-color: #00264d; }
        .link { text-align: center; margin-top: 1rem; }
        .link a { color: #004080; text-decoration: none; }
    </style>
</head>

<body>

<nav class="navbar">
    <div class="logo">
        <img src="logo.JPEG" alt="Logo">
    </div>
    <ul class="nav-links">
        <li><a href="home.html">HOME</a></li>
        <li><a href="register.php">ΕΓΓΡΑΦΗ</a></li>
        <li><a href="login.php">ΣΥΝΔΕΣΗ</a></li>
        <li><a href="campus.html">CAMPUS</a></li>
    </ul>
</nav>

<div class="form-wrapper">
    <div class="form-container">
        <h2>Εγγραφή Χρήστη</h2>

        <?php if (!empty($errors)): ?>
            <div style="color:red; margin-bottom:15px;">
                <?php foreach ($errors as $e) echo htmlspecialchars($e)."<br>"; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>

            <select name="role" required>
                <option value="">Επιλέξτε ρόλο</option>
                <option value="student">Φοιτητής</option>
                <option value="teacher">Καθηγητής</option>
            </select>

            <input type="text" name="special_code" placeholder="Special Code" required>

            <button type="submit">Εγγραφή</button>
        </form>

        <div class="link">
            Έχετε ήδη λογαριασμό; <a href="login.php">Σύνδεση</a>
        </div>
    </div>
</div>

</body>
</html>
