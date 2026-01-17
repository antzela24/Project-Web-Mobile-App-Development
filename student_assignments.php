<?php
session_start();
require_once 'db.php';

// RBAC: μόνο φοιτητής
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];

// Λήψη μαθημάτων του φοιτητή
$stmt = $pdo->prepare("
    SELECT c.id AS course_id, c.course_name
    FROM courses c
    JOIN enrollments e ON c.id = e.course_id
    WHERE e.student_id = :student
");
$stmt->execute(['student' => $student_id]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Λήψη εργασιών για τα μαθήματα του φοιτητή
$stmt2 = $pdo->prepare("
    SELECT a.id AS assignment_id, a.title, a.description, c.course_name
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    JOIN enrollments e ON c.id = e.course_id
    WHERE e.student_id = :student
");
$stmt2->execute(['student' => $student_id]);
$assignments = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Υποβολή εργασίας
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assignment_id']) && isset($_FILES['submission_file'])) {
    $assignment_id = $_POST['assignment_id'];

    $file = $_FILES['submission_file'];
    if ($file['error'] === 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $filename = basename($file['name']);
        $target = $upload_dir . time() . "_" . $filename;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            $stmt3 = $pdo->prepare("
                INSERT INTO submissions (assignment_id, student_id, file_path)
                VALUES (:assignment, :student, :file)
            ");
            $stmt3->execute([
                'assignment' => $assignment_id,
                'student' => $student_id,
                'file' => $target
            ]);
            $success = 'Η εργασία υποβλήθηκε επιτυχώς.';
        } else {
            $error = 'Σφάλμα κατά την αποθήκευση του αρχείου.';
        }
    } else {
        $error = 'Παρουσιάστηκε σφάλμα στο ανέβασμα του αρχείου.';
    }
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Φοιτητής – Εργασίες</title>
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

        <!-- Πίνακας εργασιών -->
        <div class="card">
            <h2>Οι εργασίες μου !</h2>

            <?php if (count($assignments) === 0): ?>
                <p>Δεν υπάρχουν διαθέσιμες εργασίες.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Μάθημα</th>
                            <th>Τίτλος</th>
                            <th>Περιγραφή</th>
                            <th>Υποβολή</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($assignments as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['course_name']) ?></td>
                            <td><?= htmlspecialchars($a['title']) ?></td>
                            <td><?= htmlspecialchars($a['description']) ?></td>
                            <td>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="assignment_id" value="<?= $a['assignment_id'] ?>">
                                    <input type="file" name="submission_file" required>
                                    <button type="submit" class="btn">Υποβολή</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if ($success): ?>
                <p class="success"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>
            <?php if ($error): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

        </div>

    </section>
</div>

</body>
</html>

