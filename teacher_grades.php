<?php
session_start();
require_once 'db.php';

// RBAC: μόνο καθηγητής
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];

// Μαθήματα καθηγητή
$stmt = $pdo->prepare("SELECT id AS course_id, course_name, course_code FROM courses WHERE teacher_id = :teacher");
$stmt->execute(['teacher' => $teacher_id]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Επιλεγμένο μάθημα
$selected_course = $_GET['course_id'] ?? null;

// Ανέβασμα βαθμολογίας
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submission_id'], $_POST['grade'])) {
    $submission_id = $_POST['submission_id'];
    $grade = $_POST['grade'];

    $stmtUpdate = $pdo->prepare("UPDATE submissions SET grade = :grade WHERE id = :id");
    $stmtUpdate->execute([
        'grade' => $grade,
        'id' => $submission_id
    ]);
}

// Υποβολές για επιλεγμένο μάθημα
$submissions = [];
if ($selected_course) {
    $stmt2 = $pdo->prepare("
        SELECT s.id, s.student_id, u.username, s.assignment_id, a.title AS assignment_title, s.file_path, s.submitted_at, s.grade
        FROM submissions s
        JOIN users u ON s.student_id = u.id
        JOIN assignments a ON s.assignment_id = a.id
        WHERE a.course_id = ?
        ORDER BY s.submitted_at DESC
    ");
    $stmt2->execute([$selected_course]);
    $submissions = $stmt2->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Καθηγητής – Βαθμολόγηση</title>
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

        input.grade-input {
            width: 60px;
            padding: 5px;
        }

        button.btn-grade {
            padding: 5px 10px;
            cursor: pointer;
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
        <h3>Teacher</h3><br>
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="teacher_courses.php">📚 Courses</a>
        <a href="teacher_assignments.php">📝 Assignments</a>
        <a href="teacher_submissions.php">📤 Submissions</a>
        <a href="teacher_grades.php">⭐ Grades</a>
    </aside>

    <!-- Main Content -->
    <section class="main-content">

        <!-- Επιλογή Μαθήματος -->
        <div class="card">
            <form method="GET">
                <label>Επιλέξτε Μάθημα</label>
                <select name="course_id" onchange="this.form.submit()" required>
                    <option value="">-- Επιλέξτε --</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['course_id'] ?>" <?= ($selected_course == $c['course_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['course_name']) ?> (<?= htmlspecialchars($c['course_code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if ($selected_course): ?>

        <!-- Πίνακας Βαθμολογίας -->
        <div class="card">
            <h2>Βαθμολόγηση Φοιτητών</h2>

            <?php if (count($submissions) === 0): ?>
                <p>Δεν υπάρχουν υποβολές για αυτό το μάθημα.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Φοιτητής</th>
                            <th>Assignment</th>
                            <th>Αρχείο</th>
                            <th>Ημερομηνία</th>
                            <th>Βαθμός</th>
                            <th>Ενέργεια</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($submissions as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['username']) ?></td>
                            <td><?= htmlspecialchars($s['assignment_title']) ?></td>
                            <td>
                                <?php if (!empty($s['file_path'])): ?>
                                    <a href="<?= htmlspecialchars($s['file_path']) ?>" target="_blank">📎 Download</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($s['submitted_at']) ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="submission_id" value="<?= $s['id'] ?>">
                                    <input type="number" name="grade" class="grade-input" min="0" max="100" value="<?= htmlspecialchars($s['grade']) ?>">
                            </td>
                            <td>
                                    <button type="submit" class="btn-grade">Αποθήκευση</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <?php endif; ?>

    </section>
</div>

</body>
</html>

