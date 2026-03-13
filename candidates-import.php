<?php
define('IIS_PPR', true);
require_once __DIR__ . '/bootstrap.php';
\App\Auth::requireLogin();
\App\Auth::requireAnyRole(['hr', 'admin']);

$pdo = \App\Database::getConnection();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['file']['tmp_name'])) {
    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Ошибка загрузки файла.';
    } else {
        $csv = array_map('str_getcsv', file($file['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        $header = array_shift($csv);
        $header = array_map('trim', $header);
        $imported = 0;
        foreach ($csv as $row) {
            $row = array_pad($row, count($header), '');
            $data = array_combine($header, $row);
            $email = trim($data['email'] ?? '');
            if (!$email) continue;
            $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $st->execute([$email]);
            $userId = $st->fetchColumn();
            if (!$userId) {
                $pdo->prepare('INSERT INTO users (email, password_hash, full_name) VALUES (?, ?, ?)')->execute([
                    $email,
                    password_hash('import' . bin2hex(random_bytes(4)), PASSWORD_DEFAULT),
                    trim($data['full_name'] ?? $email)
                ]);
                $userId = $pdo->lastInsertId();
                $roleStudent = $pdo->query("SELECT id FROM roles WHERE code = 'student'")->fetchColumn();
                $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?,?)')->execute([$userId, $roleStudent]);
            }
            $st = $pdo->prepare('SELECT id FROM candidates WHERE user_id = ?');
            $st->execute([$userId]);
            if ($st->fetch()) continue;
            $pdo->prepare('INSERT INTO candidates (user_id, university, specialty, course, graduation_year, gpa, experience_years, projects_count, motivation_text, experience_text, city, work_format, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([
                $userId,
                trim($data['university'] ?? ''),
                trim($data['specialty'] ?? ''),
                (int)($data['course'] ?? 0) ?: null,
                (int)($data['graduation_year'] ?? 0) ?: null,
                ($v = $data['gpa'] ?? '') !== '' ? (float)$v : null,
                (float)($data['experience_years'] ?? 0),
                (int)($data['projects_count'] ?? 0),
                trim($data['motivation_text'] ?? ''),
                trim($data['experience_text'] ?? ''),
                trim($data['city'] ?? ''),
                trim($data['work_format'] ?? ''),
                'draft'
            ]);
            $imported++;
        }
        $message = "Импортировано анкет: $imported.";
    }
}

$currentPage = 'import';
$pageTitle = 'Импорт анкет';
include __DIR__ . '/header.php';
?>

<div class="page-header">
    <h1>Импорт анкет</h1>
    <p class="subtitle">Загрузка пакета анкет (CSV). Колонки: email, full_name, university, specialty, course, graduation_year, gpa, experience_years, projects_count, motivation_text, experience_text, city, work_format</p>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card">
    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label>CSV-файл</label>
            <input type="file" name="file" accept=".csv,.txt" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Импортировать</button>
    </form>
</div>

<?php include __DIR__ . '/footer.php'; ?>

