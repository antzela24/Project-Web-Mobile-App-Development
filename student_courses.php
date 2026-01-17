<?php
session_start();
require_once 'db.php';

// RBAC: μόνο φοιτητής
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];

// Μαθήματα στα οποία είναι εγγεγραμμένος ο φοιτητής
$stmt = $pdo->prepare("
    SELECT c.id AS course_id, c.course_code, c.course_name
    FROM courses c
    JOIN enrollments e ON c.id = e.course_id
    WHERE e.student_id = :student
");
$stmt->execute(['student' => $student_id]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Μαθήματα στα οποία ΔΕΝ είναι εγγεγραμμένος ο φοιτητής
$stmt2 = $pdo->prepare("
    SELECT c.id, c.course_code, c.course_name
    FROM courses c
    WHERE c.id NOT IN (
        SELECT course_id FROM enrollments WHERE student_id = :student
    )
");
$stmt2->execute(['student' => $student_id]);
$available_courses = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Επεξεργασία POST για εγγραφή
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_to_enroll'])) {
    $course_id = $_POST['course_to_enroll'];

    $stmt3 = $pdo->prepare("
        INSERT INTO enrollments (student_id, course_id)
        VALUES (:student, :course)
    ");
    $stmt3->execute(['student' => $student_id, 'course' => $course_id]);

    header("Location: student_courses.php"); // refresh
    exit;
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Φοιτητής – Μαθήματα</title>
    <link rel="stylesheet" href="style.css">
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

        .side-menu a.active {
            font-weight: bold;
            text-decoration: underline;
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

        select, input, button {
            padding: 8px;
            margin-top: 8px;
            width: 100%;
            border-radius: 5px;
            border: 1px solid #ccc;
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

    <!-- Sidebar -->
    <aside class="side-menu">
        <h3>Student</h3><br>
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="student_courses.php">📚 Courses </a>
        <a href="student_enrollments.php">📚 Enrollments </a>
        <a href="student_assignments.php">📝 Assignment</a>
        <a href="student_submissions.php">📤 Submissions</a>
        <a href="student_grades.php">⭐ Grades</a>
    </aside>

    <!-- Main Content -->
    <section class="main-content">

        <!-- Πίνακας μαθημάτων -->
        <div class="card">
            <h2>Τα μαθήματά μου !</h2>

            <?php if (count($courses) === 0): ?>
                <p>Δεν έχετε εγγραφεί σε κανένα μάθημα ακόμα.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Κωδικός Μαθήματος</th>
                            <th>Όνομα Μαθήματος</th>
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

        

    </section>
</div>

</body>
</html>



