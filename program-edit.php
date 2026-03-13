<?php
define('IIS_PPR', true);
require_once __DIR__ . '/bootstrap.php';
\App\Auth::requireLogin();
\App\Auth::requireAnyRole(['manager', 'admin']);

$pdo = \App\Database::getConnection();
$id = (int)($_GET['id'] ?? 0);
$program = null;
if ($id) {
    $st = $pdo->prepare('SELECT * FROM programs WHERE id = ?');
    $st->execute([$id]);
    $program = $st->fetch();
    if (!$program) { header('Location: /programs.php'); exit; }
    $quota = $pdo->prepare('SELECT total_places, occupied_places FROM program_quota WHERE program_id = ? ORDER BY id DESC LIMIT 1');
    $quota->execute([$id]);
    $program['quota'] = $quota->fetch();
} else {
    $program = ['name'=>'','direction'=>'','description'=>'','city'=>'','work_format'=>'','duration_weeks'=>null,'min_course'=>null,'min_gpa'=>null,'min_experience_years'=>null,'is_active'=>1];
    $program['quota'] = ['total_places'=>0,'occupied_places'=>0];
}

$saved = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $direction = trim($_POST['direction'] ?? '');
    if (!$name || !$direction) {
        $error = 'Укажите название и направление.';
    } else {
        if ($id) {
            $st = $pdo->prepare('UPDATE programs SET name=?, direction=?, description=?, city=?, work_format=?, duration_weeks=?, min_course=?, min_gpa=?, min_experience_years=?, is_active=? WHERE id=?');
            $st->execute([
                $name, $direction, trim($_POST['description']??''), trim($_POST['city']??''), trim($_POST['work_format']??''),
                (int)($_POST['duration_weeks']??0) ?: null, (int)($_POST['min_course']??0) ?: null,
                ($v=$_POST['min_gpa']??'')!==''?(float)$v:null, ($v=$_POST['min_experience_years']??'')!==''?(float)$v:null,
                isset($_POST['is_active']) ? 1 : 0, $id
            ]);
        } else {
            $st = $pdo->prepare('INSERT INTO programs (name, direction, description, city, work_format, duration_weeks, min_course, min_gpa, min_experience_years, is_active) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $st->execute([
                $name, $direction, trim($_POST['description']??''), trim($_POST['city']??''), trim($_POST['work_format']??''),
                (int)($_POST['duration_weeks']??0) ?: null, (int)($_POST['min_course']??0) ?: null,
                ($v=$_POST['min_gpa']??'')!==''?(float)$v:null, ($v=$_POST['min_experience_years']??'')!==''?(float)$v:null,
                isset($_POST['is_active']) ? 1 : 0
            ]);
            $id = $pdo->lastInsertId();
        }
        $totalPlaces = (int)($_POST['quota_total'] ?? 0);
        if ($totalPlaces > 0) {
            $existing = $pdo->prepare('SELECT id FROM program_quota WHERE program_id = ? ORDER BY id DESC LIMIT 1');
            $existing->execute([$id]);
            $q = $existing->fetch();
            if ($q) {
                $pdo->prepare('UPDATE program_quota SET total_places = ? WHERE id = ?')->execute([$totalPlaces, $q['id']]);
            } else {
                $pdo->prepare('INSERT INTO program_quota (program_id, total_places) VALUES (?,?)')->execute([$id, $totalPlaces]);
            }
        }
        $saved = true;
        header('Location: /programs.php?saved=1');
        exit;
    }
}

$currentPage = 'programs';
$pageTitle = $id ? 'Редактирование программы' : 'Новая программа';
include __DIR__ . '/header.php';
?>

<div class="page-header">
    <h1><?= $pageTitle ?></h1>
    <p class="subtitle"><a href="/programs.php">← К списку программ</a></p>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card">
    <form method="post">
        <div class="form-group">
            <label>Название</label>
            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($program['name']) ?>">
        </div>
        <div class="form-group">
            <label>Направление</label>
            <input type="text" name="direction" class="form-control" required value="<?= htmlspecialchars($program['direction']) ?>" placeholder="аналитика, разработка, маркетинг...">
        </div>
        <div class="form-group">
            <label>Описание</label>
            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($program['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label>Город</label>
            <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($program['city'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Формат</label>
            <select name="work_format" class="form-control">
                <option value="">—</option>
                <option value="очно" <?= ($program['work_format']??'')==='очно'?'selected':'' ?>>Очно</option>
                <option value="удаленно" <?= ($program['work_format']??'')==='удаленно'?'selected':'' ?>>Удалённо</option>
                <option value="гибрид" <?= ($program['work_format']??'')==='гибрид'?'selected':'' ?>>Гибрид</option>
            </select>
        </div>
        <div class="form-group">
            <label>Длительность (недель)</label>
            <input type="number" name="duration_weeks" class="form-control" min="0" value="<?= (int)($program['duration_weeks']??0) ?>">
        </div>
        <div class="form-group">
            <label>Мин. курс</label>
            <input type="number" name="min_course" class="form-control" min="0" value="<?= (int)($program['min_course']??0) ?>">
        </div>
        <div class="form-group">
            <label>Мин. GPA</label>
            <input type="number" name="min_gpa" class="form-control" step="0.01" value="<?= htmlspecialchars($program['min_gpa']??'') ?>">
        </div>
        <div class="form-group">
            <label>Мин. опыт (лет)</label>
            <input type="number" name="min_experience_years" class="form-control" step="0.5" value="<?= htmlspecialchars($program['min_experience_years']??'') ?>">
        </div>
        <div class="form-group">
            <label>Квота мест</label>
            <input type="number" name="quota_total" class="form-control" min="0" value="<?= (int)($program['quota']['total_places']??0) ?>">
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="is_active" value="1" <?= ($program['is_active']??1) ? 'checked' : '' ?>> Активна</label>
        </div>
        <button type="submit" class="btn btn-primary">Сохранить</button>
        <a href="/programs.php" class="btn btn-secondary">Отмена</a>
    </form>
</div>

<?php include __DIR__ . '/footer.php'; ?>

