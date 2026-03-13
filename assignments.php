<?php
define('IIS_PPR', true);
require_once __DIR__ . '/bootstrap.php';
\App\Auth::requireLogin();
\App\Auth::requireAnyRole(['manager', 'admin', 'hr']);

$pdo = \App\Database::getConnection();
$programs = $pdo->query('SELECT id, name FROM programs WHERE is_active = 1 ORDER BY name')->fetchAll();
$selectedProgramId = (int)($_GET['program_id'] ?? 0);

$approveSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && \App\Auth::canApproveAssignment()) {
    $assignId = (int)($_POST['assign_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if ($assignId && in_array($status, ['approved', 'rejected'], true)) {
        $pdo->prepare('UPDATE assignments SET status = ?, decided_by = ?, decided_at = NOW() WHERE id = ?')->execute([$status, \App\Auth::userId(), $assignId]);
        if ($status === 'approved') {
            $a = $pdo->prepare('SELECT program_id FROM assignments WHERE id = ?');
            $a->execute([$assignId]);
            $a = $a->fetch();
            if ($a) {
                $q = $pdo->prepare('SELECT id FROM program_quota WHERE program_id = ? ORDER BY id DESC LIMIT 1');
                $q->execute([$a['program_id']]);
                $qid = $q->fetchColumn();
                if ($qid) {
                    $pdo->prepare('UPDATE program_quota SET occupied_places = occupied_places + 1 WHERE id = ?')->execute([$qid]);
                }
            }
        }
        $approveSuccess = 'Решение сохранено.';
    }
}

$assignments = [];
if ($selectedProgramId) {
    $assignments = $pdo->prepare('
        SELECT a.*, u.full_name, c.university, c.specialty
        FROM assignments a
        JOIN candidates c ON c.id = a.candidate_id
        JOIN users u ON u.id = c.user_id
        WHERE a.program_id = ?
        ORDER BY a.created_at DESC
    ');
    $assignments->execute([$selectedProgramId]);
    $assignments = $assignments->fetchAll();
}

$currentPage = 'assignments';
$pageTitle = 'Распределение';
include __DIR__ . '/header.php';
?>

<div class="page-header">
    <h1>Распределение</h1>
    <p class="subtitle">Утверждение распределения по программам (F7, F8)</p>
</div>

<?php if ($approveSuccess): ?><div class="alert alert-success"><?= htmlspecialchars($approveSuccess) ?></div><?php endif; ?>

<div class="card">
    <h2 class="card-title">По программе</h2>
    <form method="get" style="margin-bottom:16px;">
        <select name="program_id" class="form-control" style="width:auto; display:inline-block;" onchange="this.form.submit()">
            <option value="">— Выберите программу —</option>
            <?php foreach ($programs as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $selectedProgramId === (int)$p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php if ($selectedProgramId && empty($assignments)): ?>
        <p>Нет распределений по этой программе.</p>
    <?php elseif (!empty($assignments)): ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Кандидат</th>
                        <th>Вуз / Специальность</th>
                        <th>Статус</th>
                        <th>Дата решения</th>
                        <?php if (\App\Auth::canApproveAssignment()): ?><th class="actions">Действия</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['full_name']) ?></td>
                            <td><?= htmlspecialchars($a['university'] ?? '') ?> / <?= htmlspecialchars($a['specialty'] ?? '') ?></td>
                            <td><span class="badge badge-<?= $a['status'] === 'approved' ? 'assigned' : ($a['status'] === 'rejected' ? 'rejected' : 'in_review') ?>"><?= htmlspecialchars($a['status']) ?></span></td>
                            <td><?= $a['decided_at'] ? date('d.m.Y H:i', strtotime($a['decided_at'])) : '—' ?></td>
                            <?php if (\App\Auth::canApproveAssignment() && $a['status'] === 'proposed'): ?>
                                <td class="actions">
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="assign_id" value="<?= $a['id'] ?>">
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="btn btn-sm btn-success">Утвердить</button>
                                    </form>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="assign_id" value="<?= $a['id'] ?>">
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="btn btn-sm btn-danger">Отклонить</button>
                                    </form>
                                </td>
                            <?php elseif (\App\Auth::canApproveAssignment()): ?>
                                <td>—</td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($selectedProgramId): ?>
<div class="card">
    <h2 class="card-title">Предложить к распределению</h2>
    <p>Укажите ID кандидата (из <a href="/scoring.php?program_id=<?= $selectedProgramId ?>">рейтинга</a>).</p>
    <form method="post" action="/assignment-add.php" style="max-width:400px;">
        <input type="hidden" name="program_id" value="<?= $selectedProgramId ?>">
        <div class="form-group">
            <label>Кандидат (ID)</label>
            <input type="number" name="candidate_id" class="form-control" placeholder="ID кандидата" required>
        </div>
        <button type="submit" class="btn btn-primary">Добавить в распределение</button>
    </form>
</div>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
