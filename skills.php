<?php
define('IIS_PPR', true);
require_once __DIR__ . '/bootstrap.php';
\App\Auth::requireLogin();
\App\Auth::requireRole('admin');

$pdo = \App\Database::getConnection();
$list = $pdo->query('SELECT * FROM skills ORDER BY category, name')->fetchAll();

$saved = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    if (!$name) {
        $error = 'Укажите название.';
    } else {
        $sid = (int)($_POST['skill_id'] ?? 0);
        if ($sid) {
            $pdo->prepare('UPDATE skills SET name = ?, category = ? WHERE id = ?')->execute([$name, $category, $sid]);
        } else {
            $pdo->prepare('INSERT INTO skills (name, category) VALUES (?,?)')->execute([$name, $category]);
        }
        $saved = true;
        header('Location: /skills.php?saved=1');
        exit;
    }
}

$currentPage = 'skills';
$pageTitle = 'Справочник навыков';
include __DIR__ . '/header.php';
?>

<div class="page-header">
    <h1>Справочник навыков</h1>
    <p class="subtitle">Компетенции для анкет и программ</p>
</div>

<?php if (!empty($_GET['saved'])): ?><div class="alert alert-success">Сохранено.</div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card">
    <h2 class="card-title">Добавить навык</h2>
    <form method="post" style="max-width:400px;">
        <div class="form-group">
            <label>Название</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Категория</label>
            <input type="text" name="category" class="form-control" placeholder="Программирование, Аналитика...">
        </div>
        <button type="submit" class="btn btn-primary">Добавить</button>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>Название</th><th>Категория</th></tr>
            </thead>
            <tbody>
                <?php foreach ($list as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['name']) ?></td>
                        <td><?= htmlspecialchars($s['category'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>

