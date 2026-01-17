<?php
session_start();
require_once 'db.php';

// RBAC: μόνο φοιτητής
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];

$success = '';
$error = '';

// Λήψη όλων των μαθημάτων για επιλογή εγγραφής
$stmt_courses = $pdo->query("SELECT id, course_code, course_name FROM courses");
$allCourses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);

// Λήψη μαθημάτων που είναι ήδη εγγεγραμμένος ο φοιτητής
$stmt_enrolled = $pdo->prepare("
    SELECT c.course_code, c.course_name
    FROM courses c
    JOIN enrollments e ON c.id = e.course_id
    WHERE e.student_id = :student
");
$stmt_enrolled->execute(['student' => $student_id]);
$enrolledCourses = $stmt_enrolled->fetchAll(PDO::FETCH_ASSOC);

// Εγγραφή σε νέο μάθημα
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    $course_id = $_POST['course_id'];

    // Έλεγχος αν ήδη εγγεγραμμένος
    $stmt_check = $pdo->prepare("
        SELECT * FROM enrollments
        WHERE student_id = :student AND course_id = :course
    ");
    $stmt_check->execute(['student' => $student_id, 'course' => $course_id]);
    if ($stmt_check->rowCount() > 0) {
        $error = 'Έχετε ήδη εγγραφεί σε αυτό το μάθημα.';
    } else {
        $stmt_insert = $pdo->prepare("
            INSERT INTO enrollments (student_id, course_id)
            VALUES (:student, :course)
        ");
        $stmt_insert->execute(['student' => $student_id, 'course' => $course_id]);
        $success = 'Η εγγραφή ολοκληρώθηκε επιτυχώς.';
        // Ανανεώνουμε τα εγγεγραμμένα μαθήματα
        $stmt_enrolled->execute(['student' => $student_id]);
        $enrolledCourses = $stmt_enrolled->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Φοιτητής – Εγγραφές</title>
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
        .btn {
            background-color: #34495e;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #2c3e50;
        }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
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

        <!-- Εγγεγραμμένα Μαθήματα -->
        <div class="card">
            <h2>Μαθήματα που είμαι εγγεγραμμένος !</h2>

            <?php if (count($enrolledCourses) === 0): ?>
                <p>Δεν είστε εγγεγραμμένος σε κανένα μάθημα.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Κωδικός</th>
                            <th>Όνομα Μαθήματος</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($enrolledCourses as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['course_code']) ?></td>
                            <td><?= htmlspecialchars($c['course_name']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Φόρμα Εγγραφής -->
        <div class="card">
            <h3>➕ Εγγραφή σε Νέο Μάθημα</h3>

            <?php if ($error): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <?php if ($success): ?>
                <p class="success"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Επιλογή Μαθήματος</label>
                    <select name="course_id" required>
                        <option value="">-- Επιλέξτε μάθημα --</option>
                        <?php foreach ($allCourses as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn">Εγγραφή</button>
            </form>
        </div>

    </section>
</div>
</body>
</html>
