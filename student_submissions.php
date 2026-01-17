<?php
session_start();
require_once 'db.php';

// RBAC: μόνο φοιτητής
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];

$error = '';
$success = '';

/* Διαχείριση ανεβάσματος αρχείου */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['submission_file'])) {
    $assignment_id = $_POST['course_id'];
    $file = $_FILES['submission_file'];

    if ($file['error'] === 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $filename = time() . '_' . basename($file['name']);
        $filepath = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO submissions (assignment_id, student_id, file_path)
                    VALUES (:assignment, :student, :file)
                ");
                $stmt->execute([
                    'assignment' => $assignment_id,
                    'student' => $student_id,
                    'file' => $filepath
                ]);
                $success = 'Το αρχείο υποβλήθηκε επιτυχώς.';
            } catch (PDOException $e) {
                $error = 'Σφάλμα κατά την υποβολή.';
            }
        } else {
            $error = 'Αποτυχία μεταφόρτωσης αρχείου.';
        }
    } else {
        $error = 'Παρουσιάστηκε σφάλμα κατά την αποστολή αρχείου.';
    }
}

/* Επιλογή όλων των assignments για τον φοιτητή */
$stmt = $pdo->prepare("
    SELECT a.id AS assignment_id, a.title, c.course_code, c.course_name
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    ORDER BY c.course_name, a.title
");
$stmt->execute();
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Υποβολές του φοιτητή */
$stmt2 = $pdo->prepare("
    SELECT s.id, s.assignment_id, s.file_path, a.title, c.course_code
    FROM submissions s
    JOIN assignments a ON s.assignment_id = a.id
    JOIN courses c ON a.course_id = c.id
    WHERE s.student_id = :student
    ORDER BY c.course_name, a.title
");
$stmt2->execute(['student' => $student_id]);
$submissions = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Φοιτητής – Υποβολές</title>
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
        .form-group {
            margin-bottom: 15px;
        }
        input[type="file"], select {
            width: 100%;
            padding: 8px;
        }
        .btn {
            padding: 10px 20px;
            background: #34495e;
            color: #fff;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }
        .error { color: red; }
        .success { color: green; }
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

        <!-- Υποβολές -->
        <div class="card">
            <h2>Οι υποβολές μου !</h2>

            <?php if (count($submissions) === 0): ?>
                <p>Δεν έχετε υποβάλει ακόμα εργασίες.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Κωδικός Μαθήματος</th>
                            <th>Εργασία</th>
                            <th>Αρχείο</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $s): ?>
                            <tr>
                                <td><?= htmlspecialchars($s['course_code']) ?></td>
                                <td><?= htmlspecialchars($s['title']) ?></td>
                                <td><a href="<?= htmlspecialchars($s['file_path']) ?>" target="_blank">Άνοιγμα</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Φόρμα Νέας Υποβολής -->
        <div class="card">
            <h3>➕ Υποβολή Νέας Εργασίας</h3>

            <?php if ($error): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <?php if ($success): ?>
                <p class="success"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
              <div class="form-group">
                <label>Επιλογή Μαθήματος</label>
                <select name="course_id" required>
                <option value="">-- Επιλέξτε Μάθημα --</option>
                <?php
          // Επιλέγουμε τα μαθήματα που είναι εγγεγραμμένος ο φοιτητής
                 $stmt_courses = $pdo->prepare("
                     SELECT c.id, c.course_code, c.course_name
                     FROM courses c
                     JOIN enrollments e ON c.id = e.course_id
                     WHERE e.student_id = :student
                    ");
                 $stmt_courses->execute(['student' => $student_id]);
                 $courses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);

                  foreach ($courses as $course):
             ?>
            <option value="<?= $course['id'] ?>">
                <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?>
            </option>
        <?php endforeach; ?>
</select>
</div>
                <div class="form-group">
                    <label>Αρχείο (PDF/DOC)</label>
                    <input type="file" name="submission_file" required>
                </div>

                <button type="submit" class="btn">ΥΠΟΒΟΛΗ</button>
            </form>
        </div>

    </section>
</div>

</body>
</html>
