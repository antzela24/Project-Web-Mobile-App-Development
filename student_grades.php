<?php
session_start();
require_once 'db.php';

// RBAC: μόνο φοιτητής
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            c.course_code, 
            c.course_name, 
            a.title AS assignment_title,
            g.grade,
            g.feedback
        FROM submissions s
        JOIN assignments a ON s.assignment_id = a.id
        JOIN courses c ON a.course_id = c.id
        LEFT JOIN grades g ON g.submission_id = s.id
        WHERE s.student_id = :student
        ORDER BY c.course_name, a.title
    ");
    $stmt->execute(['student' => $student_id]);
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Σφάλμα στη βάση: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Φοιτητής – Βαθμοί</title>
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
            grid-template-rows: auto;
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
        <div class="card">
            <h2>Βαθμοί μαθημάτων !</h2>

            <?php if (count($grades) === 0): ?>
                <p>Δεν υπάρχουν βαθμοί για τα μαθήματα σας.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Κωδικός Μαθήματος</th>
                            <th>Όνομα Μαθήματος</th>
                            <th>Εργασία</th>
                            <th>Βαθμός</th>
                            <th>Σχόλια</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grades as $g): ?>
                            <tr>
                                <td><?= htmlspecialchars($g['course_code']) ?></td>
                                <td><?= htmlspecialchars($g['course_name']) ?></td>
                                <td><?= htmlspecialchars($g['assignment_title']) ?></td>
                                <td><?= htmlspecialchars($g['grade'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($g['feedback'] ?? '-') ?></td>
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
