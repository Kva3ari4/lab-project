<?php
define('IIS_PPR', true);
require_once __DIR__ . '/bootstrap.php';
\App\Auth::requireLogin();
\App\Auth::requireAnyRole(['student', 'hr', 'admin']);

$pdo = \App\Database::getConnection();
$isOwnProfile = \App\Auth::isStudent() && !isset($_GET['id']);
$candidateId = $isOwnProfile ? null : (int)($_GET['id'] ?? 0);

if ($isOwnProfile) {
    $st = $pdo->prepare('SELECT * FROM candidates WHERE user_id = ?');
    $st->execute([\App\Auth::userId()]);
    $candidate = $st->fetch();
    if (!$candidate) $candidate = ['id' => null, 'user_id' => \App\Auth::userId()];
} elseif ($candidateId && \App\Auth::canManageCandidates()) {
    $st = $pdo->prepare('SELECT * FROM candidates WHERE id = ?');
    $st->execute([$candidateId]);
    $candidate = $st->fetch();
    if (!$candidate) { header('Location: /candidates.php'); exit; }
} else {
    \App\Auth::forbidden();
}

$skillsList = $pdo->query('SELECT id, name FROM skills ORDER BY name')->fetchAll();
$languagesList = $pdo->query('SELECT id, name, code FROM languages ORDER BY name')->fetchAll();

$saved = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($isOwnProfile || \App\Auth::canManageCandidates())) {
    $data = [
        'university' => trim($_POST['university'] ?? ''),
        'specialty' => trim($_POST['specialty'] ?? ''),
        'course' => (int)($_POST['course'] ?? 0) ?: null,
        'graduation_year' => (int)($_POST['graduation_year'] ?? 0) ?: null,
        'gpa' => ($v = $_POST['gpa'] ?? '') !== '' ? (float)$v : null,
        'experience_years' => (float)($_POST['experience_years'] ?? 0),
        'projects_count' => (int)($_POST['projects_count'] ?? 0),
        'motivation_text' => trim($_POST['motivation_text'] ?? ''),
        'experience_text' => trim($_POST['experience_text'] ?? ''),
        'interests_text' => trim($_POST['interests_text'] ?? ''),
        'city' => trim($_POST['city'] ?? ''),
        'work_format' => trim($_POST['work_format'] ?? ''),
        'available_from' => ($v = trim($_POST['available_from'] ?? '')) ? $v : null,
        'available_to' => ($v = trim($_POST['available_to'] ?? '')) ? $v : null,
    ];
    if ($candidate['id']) {
        $st = $pdo->prepare('UPDATE candidates SET university=?, specialty=?, course=?, graduation_year=?, gpa=?, experience_years=?, projects_count=?, motivation_text=?, experience_text=?, interests_text=?, city=?, work_format=?, available_from=?, available_to=? WHERE id=?');
        $st->execute([...array_values($data), $candidate['id']]);
    } else {
        $st = $pdo->prepare('INSERT INTO candidates (user_id, university, specialty, course, graduation_year, gpa, experience_years, projects_count, motivation_text, experience_text, interests_text, city, work_format, available_from, available_to) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $st->execute([$candidate['user_id'], ...array_values($data)]);
        $candidate['id'] = $pdo->lastInsertId();
    }
    $pdo->prepare('DELETE FROM candidate_skills WHERE candidate_id = ?')->execute([$candidate['id']]);
    foreach ($_POST['skill_id'] ?? [] as $sid) {
        $sid = (int)$sid;
        if ($sid) $pdo->prepare('INSERT INTO candidate_skills (candidate_id, skill_id) VALUES (?,?)')->execute([$candidate['id'], $sid]);
    }
    $pdo->prepare('DELETE FROM candidate_languages WHERE candidate_id = ?')->execute([$candidate['id']]);
    foreach ($_POST['language_id'] ?? [] as $lid) {
        $lid = (int)$lid;
        if ($lid) $pdo->prepare('INSERT INTO candidate_languages (candidate_id, language_id, level) VALUES (?,?,?)')->execute([$candidate['id'], $lid, $_POST['lang_level'][$lid] ?? 'B2']);
    }
    $saved = true;
    $st = $pdo->prepare('SELECT * FROM candidates WHERE id = ?');
    $st->execute([$candidate['id']]);
    $candidate = $st->fetch();
}

$candidateSkills = [];
$candidateLangs = [];
if (!empty($candidate['id'])) {
    $candidateSkills = $pdo->prepare('SELECT skill_id FROM candidate_skills WHERE candidate_id = ?');
    $candidateSkills->execute([$candidate['id']]);
    $candidateSkills = $candidateSkills->fetchAll(PDO::FETCH_COLUMN);
    $candidateLangs = $pdo->prepare('SELECT language_id, level FROM candidate_languages WHERE candidate_id = ?');
    $candidateLangs->execute([$candidate['id']]);
    $candidateLangs = $candidateLangs->fetchAll(PDO::FETCH_KEY_PAIR);
}

$currentPage = 'profile';
$pageTitle = $isOwnProfile ? 'Моя анкета' : 'Анкета кандидата';
include __DIR__ . '/header.php';
?>

<div class="page-header">
    <h1><?= $pageTitle ?></h1>
    <?php if (!$isOwnProfile): ?>
        <p class="subtitle"><a href="/candidates.php">← К списку кандидатов</a></p>
    <?php endif; ?>
</div>

<?php if ($saved): ?><div class="alert alert-success">Данные сохранены.</div><?php endif; ?>
<?php if (!empty($_GET['submitted'])): ?><div class="alert alert-success">Анкета отправлена на рассмотрение.</div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card">
    <form method="post">
        <div class="form-group">
            <label>Вуз</label>
            <input type="text" name="university" class="form-control" value="<?= htmlspecialchars($candidate['university'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Специальность</label>
            <input type="text" name="specialty" class="form-control" value="<?= htmlspecialchars($candidate['specialty'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Курс</label>
            <input type="number" name="course" class="form-control" min="1" max="6" value="<?= (int)($candidate['course'] ?? 0) ?>">
        </div>
        <div class="form-group">
            <label>Год выпуска</label>
            <input type="number" name="graduation_year" class="form-control" min="2020" max="2030" value="<?= (int)($candidate['graduation_year'] ?? 0) ?>">
        </div>
        <div class="form-group">
            <label>Средний балл (GPA)</label>
            <input type="number" name="gpa" class="form-control" step="0.01" min="0" max="4" value="<?= htmlspecialchars($candidate['gpa'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Опыт (лет)</label>
            <input type="number" name="experience_years" class="form-control" step="0.5" min="0" value="<?= htmlspecialchars($candidate['experience_years'] ?? '0') ?>">
        </div>
        <div class="form-group">
            <label>Количество проектов/стажировок</label>
            <input type="number" name="projects_count" class="form-control" min="0" value="<?= (int)($candidate['projects_count'] ?? 0) ?>">
        </div>
        <div class="form-group">
            <label>Город</label>
            <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($candidate['city'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Формат работы</label>
            <select name="work_format" class="form-control">
                <option value="">—</option>
                <option value="очно" <?= ($candidate['work_format'] ?? '') === 'очно' ? 'selected' : '' ?>>Очно</option>
                <option value="удаленно" <?= ($candidate['work_format'] ?? '') === 'удаленно' ? 'selected' : '' ?>>Удалённо</option>
                <option value="гибрид" <?= ($candidate['work_format'] ?? '') === 'гибрид' ? 'selected' : '' ?>>Гибрид</option>
            </select>
        </div>
        <div class="form-group">
            <label>Доступен с</label>
            <input type="date" name="available_from" class="form-control" value="<?= htmlspecialchars($candidate['available_from'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Доступен по</label>
            <input type="date" name="available_to" class="form-control" value="<?= htmlspecialchars($candidate['available_to'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Навыки</label>
            <?php foreach ($skillsList as $s): ?>
                <label style="display:inline-block; margin-right:12px;">
                    <input type="checkbox" name="skill_id[]" value="<?= $s['id'] ?>" <?= in_array($s['id'], $candidateSkills) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($s['name']) ?>
                </label>
            <?php endforeach; ?>
        </div>
        <div class="form-group">
            <label>Языки (уровень)</label>
            <?php foreach ($languagesList as $l): ?>
                <label style="display:block; margin-bottom:6px;">
                    <input type="checkbox" name="language_id[]" value="<?= $l['id'] ?>" <?= isset($candidateLangs[$l['id']]) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($l['name']) ?>
                    <select name="lang_level[<?= $l['id'] ?>]" class="form-control" style="display:inline-block; width:80px; margin-left:8px;">
                        <?php foreach (['A1','A2','B1','B2','C1','C2'] as $lev): ?>
                            <option value="<?= $lev ?>" <?= ($candidateLangs[$l['id']] ?? 'B2') === $lev ? 'selected' : '' ?>><?= $lev ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endforeach; ?>
        </div>
        <div class="form-group">
            <label>Мотивация</label>
            <textarea name="motivation_text" class="form-control" rows="4"><?= htmlspecialchars($candidate['motivation_text'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label>Опыт (описание)</label>
            <textarea name="experience_text" class="form-control" rows="4"><?= htmlspecialchars($candidate['experience_text'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label>Профессиональные интересы</label>
            <textarea name="interests_text" class="form-control" rows="3"><?= htmlspecialchars($candidate['interests_text'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Сохранить</button>
        <?php if ($isOwnProfile && !empty($candidate['id'])): ?>
            <?php if (($candidate['status'] ?? '') === 'draft'): ?>
                <a href="/candidate-submit.php" class="btn btn-success">Отправить на рассмотрение</a>
            <?php endif; ?>
        <?php endif; ?>
    </form>
</div>

<?php include __DIR__ . '/footer.php'; ?>
