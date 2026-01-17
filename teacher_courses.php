<?php
session_start();
require_once 'db.php';

/* RBAC: μόνο καθηγητής */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

/* Προσθήκη μαθήματος */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_code = trim($_POST['course_code']);
    $course_name = trim($_POST['course_name']);

    if ($course_code === '' || $course_name === '') {
        $error = 'Συμπλήρωσε όλα τα πεδία.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO courses (course_code, course_name, teacher_id)
                VALUES (:code, :name, :teacher)
            ");
            $stmt->execute([
                'code'    => $course_code,
                'name'    => $course_name,
                'teacher' => $_SESSION['user_id']
            ]);
            $success = 'Το μάθημα προστέθηκε επιτυχώς.';
        } catch (PDOException $e) {
            $error = 'Σφάλμα κατά την εισαγωγή μαθήματος.';
        }
    }
}

/* Προβολή μαθημάτων καθηγητή */
$stmt = $pdo->prepare("
    SELECT course_code, course_name
    FROM courses
    WHERE teacher_id = :teacher
");
$stmt->execute(['teacher' => $_SESSION['user_id']]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Καθηγητής – Μαθήματα</title>
    <link rel="stylesheet" href="style.css">

    <!-- CSS ΜΟΝΟ για layout -->
    <style>
        .page-layout {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 30px;
            padding: 30px;
        }

        .side-menu {
            background: #dae4f6ff;
            padding: 20px;
            border-radius: 10px;
        }

        .side-menu a {
            display: block;
            margin-bottom: 15px;
            text-decoration: none;
            font-weight: bold;
            color: #333;
        }

        .main-content {
            display: grid;
            grid-template-rows: auto auto;
            gap: 30px;
        }

        .card {
            background: #dae4f6ff;
            padding: 40px;
            border-radius: 10px;
        }

        .form-group {
            margin-bottom: 15px;
            
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table th, table td {
            padding: 12px;
            border-bottom: 1px solid #ccc;
            text-align: left;
        }

        table th {
            background-color: #34495e;
            color: #fff;
        }

        input {
            width: 100%;
            padding: 8px;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">
        <img src="logo.JPEG" alt="Logo">
    </div>
    <ul class="nav-links">
        <li><a href="logout.php" class="logout">ΑΠΟΣΥΝΔΕΣΗ</a></li>
    </ul>
</nav>

<div class="page-layout">

    <!-- ΑΡΙΣΤΕΡΟ ΜΕΝΟΥ -->
    <aside class="side-menu">
        <h3>Teacher</h3><br>
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="teacher_courses.php">📚 Courses</a>
        <a href="teacher_assignments.php">📝 Assignments</a>
        <a href="teacher_submissions.php">📤 Submissions</a>
        <a href="teacher_grades.php">⭐ Grades</a>
    </aside>

    <!-- ΔΕΞΙ ΠΕΡΙΕΧΟΜΕΝΟ -->
    <section class="main-content">

        <!-- Υπάρχοντα Μαθήματα -->
        <div class="card">
            <h2>Τα μαθήματά μου !</h2>

            <?php if (count($courses) === 0): ?>
                <p>Δεν έχετε δημιουργήσει μαθήματα.</p>
            <?php else: ?>
                <table >
                    <thead>
                        <tr>
                            <th>Κωδικός</th>
                            <th>Όνομα</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($courses as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['course_code']) ?></td>
                            <td><?= htmlspecialchars($c['course_name']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Φόρμα Προσθήκης -->
        <div class="card">
            <h3>➕ Προσθήκη Μαθήματος</h3>

            <?php if ($error): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <?php if ($success): ?>
                <p class="success"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label>Κωδικός Μαθήματος</label>
                    <input type="text" name="course_code" required>
                </div>

                <div class="form-group">
                    <label>Όνομα Μαθήματος</label>
                    <input type="text" name="course_name" required>
                </div>

                <button type="submit" class="btn">ΑΠΟΘΗΚΕΥΣΗ</button>
            </form>
        </div>

    </section>
</div>
</body>
</html>
