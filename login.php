<?php
session_start();
require_once 'db.php';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $stmt = $pdo->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id=r.id WHERE email=:email");
    $stmt->execute(['email'=>$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role_name'];

        header("Location: dashboard.php");
        exit;

    } else {
        $errors[] = "Λάθος email ή password.";
    }
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stratford University - Σύνδεση</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { line-height: 1.6; background-color: #E8E5FF; font-family: Arial, sans-serif; }
        .navbar { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            background-color:#000047;
            padding: 1rem 2rem; 
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 20px;
            color: #fff;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .logo img { height: 60px; width: auto; }
        .navbar .nav-links { list-style: none; display: flex; }
        .navbar .nav-links li { margin-left: 1.5rem; }
        .navbar .nav-links a { color: #fff; text-decoration: none; font-weight: 500; transition: color 0.3s; }
        .navbar .nav-links a:hover { color: #185457; }

        .form-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height:100vh;
            padding-top: 100px;
        }

        .form-container { 
            background-color: #fff; 
            padding: 2rem; 
            border-radius: 20px; 
            box-shadow: 0 0 10px rgba(0,0,0,0.1); 
            width: 100%; 
            max-width: 550px; 
        }
        h2 { 
            text-align: center; 
            margin-bottom: 1rem; 
            color: #004080; 
        }
        form { display: flex; flex-direction: column; }
        input { 
            padding: 0.8rem; 
            margin-bottom: 1rem; 
            border-radius: 5px; 
            border: 1px solid #ccc; 
            font-size: 1rem; 
        }
        button { 
            padding: 0.8rem; 
            background-color: #004080; 
            color: #fff; 
            border: none; 
            border-radius: 5px; 
            font-size: 1rem; 
            cursor: pointer; 
            transition: background-color 0.3s; 
        }
        button:hover { background-color: #00264d; }
        .link { text-align: center; margin-top: 1rem; }
        .link a { color: #004080; text-decoration: none; }

        @media(max-width: 480px) {
            .navbar { flex-direction: column; padding: 1rem; }
            .navbar .nav-links { flex-direction: column; margin-top: 10px; }
            .navbar .nav-links li { margin: 0.5rem 0; }
        }
    </style>
</head>
<body>
 
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="logo">
            <img src="logo.JPEG" alt="Logo">
        </div>
        <ul class="nav-links">
            <li><a href="home.html">HOME</a></li>
            <li><a href="register.php">ΕΓΓΡΑΦΗ</a></li>
            <li><a href="login.php">ΣΥΝΔΕΣΗ</a></li>
            <li><a href="campus.html">CAMPUS </a></li>
        </ul>
    </nav>

    <!-- Εμφάνιση λαθών -->
    <div class="form-wrapper">
        <div class="form-container">
            <h2>Σύνδεση Χρήστη</h2>
            <?php if (!empty($errors)): ?>
                <div style="color:red; margin-bottom:15px;">
                    <?php foreach ($errors as $e) echo $e . "<br>"; ?>
                </div>
            <?php endif; ?>

            <form id="loginForm" method="POST" action="login.php" onsubmit="return validateLogin()">
                <input type="email" id="email" name="email" placeholder="Email" required>
                <input type="password" id="password" name="password" placeholder="Password" required>
                <input type="text" id="Role_id" name="Role_id" placeholder="Role_id" required>
                <button type="submit">Σύνδεση</button>
            </form>
            <div class="link">Δεν έχετε λογαριασμό; <a href="register.php">Εγγραφή</a></div>
        </div>
    </div>

    <script>
        function validateLogin() {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            if (!email || !password) {
                alert('Παρακαλώ συμπληρώστε όλα τα πεδία.');
                return false;
            }

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                alert('Μη έγκυρη μορφή email.');
                return false;
            }

            return true;
        }
    </script>
</body>
</html>
