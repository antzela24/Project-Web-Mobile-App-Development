<?php
session_start();
require_once 'db.php';

// RBAC: μόνο καθηγητής
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

$teacher_id = $_SESSION['user_id'];

// Προσθήκη νέας εργασίας με αρχείο
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);

    if ($course_id === '' || $title === '' || $description === '') {
        $error = 'Συμπλήρωσε όλα τα πεδία.';
    } elseif (!isset($_FILES['assignment_file']) || $_FILES['assignment_file']['error'] != 0) {
        $error = 'Πρέπει να επιλέξετε ένα αρχείο.';
    } else {
        $uploadDir = 'uploads/assignments/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $filename = basename($_FILES['assignment_file']['name']);
        $targetFile = $uploadDir . time() . "_" . $filename;

        if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $targetFile)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO assignments (course_id, title, description, file_path)
                    VALUES (:course, :title, :desc, :file)
                ");
                $stmt->execute([
                    'course' => $course_id,
                    'title'  => $title,
                    'desc'   => $description,
                    'file'   => $targetFile
                ]);
                $success = 'Η εργασία προστέθηκε επιτυχώς με αρχείο.';
            } catch (PDOException $e) {
                $error = 'Σφάλμα κατά την εισαγωγή εργασίας.';
            }
        } else {
            $error = 'Σφάλμα κατά την αποθήκευση του αρχείου.';
        }
    }
}

// Μαθήματα καθηγητή
$stmt = $pdo->prepare("SELECT id AS course_id, course_name, course_code FROM courses WHERE teacher_id = :teacher");
$stmt->execute(['teacher' => $teacher_id]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Επιλεγμένο μάθημα
$selected_course = $_GET['course_id'] ?? null;

// Assignments
$assignments = [];
if ($selected_course) {
    $stmt2 = $pdo->prepare("SELECT id, title, description, file_path FROM assignments WHERE course_id = ?");
    $stmt2->execute([$selected_course]);
    $assignments = $stmt2->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Καθηγητής – Εργασίες</title>
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

        .form-group {
            margin-bottom: 15px;
        }

        input, select, textarea {
            width: 100%;
            padding: 8px;
        }

        .btn {
            padding: 10px 20px;
            background-color: #2c3e50;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #34495e;
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

        <!-- Πίνακας Assignments -->
        <div class="card">
            <h2>Assignments</h2>

            <?php if (count($assignments) === 0): ?>
                <p>Δεν υπάρχουν assignments για αυτό το μάθημα.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Τίτλος</th>
                            <th>Περιγραφή</th>
                            <th>Αρχείο</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($assignments as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['title']) ?></td>
                            <td><?= htmlspecialchars($a['description']) ?></td>
                            <td>
                                <?php if (!empty($a['file_path'])): ?>
                                    <a href="<?= htmlspecialchars($a['file_path']) ?>" target="_blank">📎 Download</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Φόρμα Προσθήκης -->
        <div class="card">
            <h3>➕ Προσθήκη Νέας Εργασίας</h3>

            <?php if ($error): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <?php if ($success): ?>
                <p class="success"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="course_id" value="<?= $selected_course ?>">

                <div class="form-group">
                    <label>Τίτλος</label>
                    <input type="text" name="title" required>
                </div>
                
                <div class="form-group">
                    <label>Περιγραφή</label>
                    <textarea name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label>Επιλέξτε αρχείο</label>
                    <input type="file" name="assignment_file" required>
                </div>
                
                <button type="submit" class="btn">ΑΠΟΘΗΚΕΥΣΗ</button>
            </form>
        </div>

        <?php endif; ?>

    </section>
</div>

</body>
</html>
