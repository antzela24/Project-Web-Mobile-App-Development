<?php
session_start();

// Προστασία σελίδας: μόνο συνδεδεμένοι χρήστες
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION["role"];
$username = htmlspecialchars($_SESSION["username"]);

// Σύνδεση στη βάση
require_once 'db.php';

// Student statistics
$student_stats = [];
if ($role === "student") {
    // Πλήθος μαθημάτων
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM courses c
        JOIN enrollments e ON c.id = e.course_id
        WHERE e.student_id = :uid
    ");
    $stmt->execute(['uid' => $_SESSION['user_id']]);
    $student_stats['courses'] = $stmt->fetchColumn();

    // Πλήθος εργασιών
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM assignments a
        JOIN enrollments e ON a.course_id = e.course_id
        WHERE e.student_id = :uid
    ");
    $stmt->execute(['uid' => $_SESSION['user_id']]);
    $student_stats['assignments'] = $stmt->fetchColumn();

    // Πλήθος υποβολών
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM submissions s
        WHERE s.student_id = :uid
    ");
    $stmt->execute(['uid' => $_SESSION['user_id']]);
    $student_stats['submissions'] = $stmt->fetchColumn();

    // Μέσος όρος βαθμολογίας
    $stmt = $pdo->prepare("
        SELECT AVG(grade) 
        FROM submissions 
        WHERE student_id = :uid
    ");
    $stmt->execute(['uid' => $_SESSION['user_id']]);
    $student_stats['avg_grade'] = number_format($stmt->fetchColumn(), 2);
}

// Teacher statistics
$teacher_stats = [];
if ($role === "teacher") {
    // Πλήθος μαθημάτων
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM courses 
        WHERE teacher_id = :uid
    ");
    $stmt->execute(['uid' => $_SESSION['user_id']]);
    $teacher_stats['courses'] = $stmt->fetchColumn();

    // Πλήθος εργασιών (μέσω courses)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM assignments a
        JOIN courses c ON a.course_id = c.id
        WHERE c.teacher_id = :uid
    ");
    $stmt->execute(['uid' => $_SESSION['user_id']]);
    $teacher_stats['assignments'] = $stmt->fetchColumn();

    // Πλήθος υποβολών φοιτητών (μέσω assignments -> courses)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM submissions s
        JOIN assignments a ON s.assignment_id = a.id
        JOIN courses c ON a.course_id = c.id
        WHERE c.teacher_id = :uid
    ");
    $stmt->execute(['uid' => $_SESSION['user_id']]);
    $teacher_stats['submissions'] = $stmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            line-height: 1.6;
            background-color: #E8E5FF;
            font-family: Arial, sans-serif;

        }
        h2 { text-align: center; margin-top: 20px; }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 30px;
        }
        .card {
            background: #dae4f6ff;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 1px 1px 8px rgba(0,0,0,0.1);
        }
        .card h3 { margin-bottom: 15px; }
        .card p { font-size: 1.5em; margin-bottom: 10px; }
        .card a { text-decoration: none; color: #333; font-weight: bold; }
        .card a:hover { color: #0000ff; }
        
    </style>
</head>
<body>

<!-- Navigation Bar -->
<div class="navbar">
    <div class="logo">
        <img src="logo.JPEG" alt="Logo">
    </div>
    <ul class="nav-links">
        <?php if ($role === "teacher"): ?>
            <li><a href="teacher_courses.php">COURSES</a></li>
            <li><a href="teacher_assignments.php">ASSIGNMENTS</a></li>
            <li><a href="teacher_submissions.php">SUBMISSIONS</a></li>
            <li><a href="teacher_grades.php"> GRADES</a></li>
        <?php elseif ($role === "student"): ?>
            <li><a href="student_courses.php">COURSES</a></li>
            <li><a href="student_enrollments.php">ENROLLMENTS</a></li>
            <li><a href="student_assignments.php">ASSIGNMENTS</a></li>
            <li><a href="student_submissions.php">SUBMISSIONS</a></li>
            <li><a href="student_grades.php">GRADES</a></li>
        <?php endif; ?>
        <li><a href="logout.php">ΑΠΟΣΥΝΔΕΣΗ</a></li>
    </ul>
</div>

<!-- Καλωσόρισμα -->
<h2>
    Καλώς ήρθες <?= $role === "teacher" ? "καθηγητή" : "φοιτητή" ?> <?= htmlspecialchars($_SESSION['username']) ?>!
</h2>


<!-- Dashboard cards -->
<div class="dashboard-grid">
<?php if ($role === "student"): ?>
    <div class="card">
        <h3>📚 Μαθήματα</h3>
        <p><?= $student_stats['courses'] ?></p>
    </div>
    <div class="card">
        <h3>📝 Εργασίες</h3>
        <p><?= $student_stats['assignments'] ?></p>
        </div>
    <div class="card">
        <h3>📤 Υποβολές</h3>
        <p><?= $student_stats['submissions'] ?></p>
    </div>
    <div class="card">
        <h3>⭐ Μέσος Όρος Βαθμολογίας</h3>
        <p><?= $student_stats['avg_grade'] ?: 'N/A' ?></p>
    </div>
<?php elseif ($role === "teacher"): ?>
    <div class="card">
        <h3>📚 Μαθήματα</h3>
        <p><?= $teacher_stats['courses'] ?></p>
    </div>
    <div class="card">
        <h3>📝 Εργασίες</h3>
        <p><?= $teacher_stats['assignments'] ?></p>
    </div>
    <div class="card">
        <h3>📤 Υποβολές Φοιτητών</h3>
        <p><?= $teacher_stats['submissions'] ?></p>
    </div>
<?php endif; ?>
</div>

</body>
</html>
