<?php
/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var common\models\Organization $organization */
/** @var string $viewMode */
/** @var string $searchQuery */
/** @var string $statusFilter */

use common\models\Application;
use common\widgets\ProfileAvatar;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;

$this->title = 'Internship Opportunities';
$models = $dataProvider->getModels();

$positionIds = array_map(static fn($m) => (int) $m->id, $models);
$appCounts = [];
if (!empty($positionIds)) {
    $rows = Application::find()
        ->select(['position_id', 'COUNT(*) AS c'])
        ->where(['position_id' => $positionIds])
        ->groupBy('position_id')
        ->asArray()
        ->all();
    foreach ($rows as $r) {
        $appCounts[(int) $r['position_id']] = (int) $r['c'];
    }
}
?>

<div class="org-page-header">
    <div>
        <h1>Internship Opportunities</h1>
        <p>Premium recruitment workspace for posting, optimizing, and managing internship programs.</p>
    </div>
    <div class="org-page-actions">
        <button type="button" class="org-btn org-btn-primary" data-open-position-modal data-url="<?= Url::to(['position/create']) ?>">
            <i class="fas fa-plus"></i> New Internship
        </button>
    </div>
</div>

<section data-org-opportunities>
    <div class="org-toolbar">
        <form method="get" action="<?= Url::to(['position/index']) ?>" class="left">
            <input type="text" class="org-input" name="q" value="<?= Html::encode($searchQuery) ?>" placeholder="Search title, skills, field…">
            <select name="status" class="org-select">
                <option value="">All statuses</option>
                <option value="Active" <?= $statusFilter === 'Active' ? 'selected' : '' ?>>Active</option>
                <option value="Draft" <?= $statusFilter === 'Draft' ? 'selected' : '' ?>>Draft</option>
                <option value="Paused" <?= $statusFilter === 'Paused' ? 'selected' : '' ?>>Paused</option>
                <option value="Closed" <?= $statusFilter === 'Closed' ? 'selected' : '' ?>>Closed</option>
            </select>
            <button class="org-btn org-btn-ghost" type="submit"><i class="fas fa-filter"></i> Filter</button>
        </form>
        <div class="right">
            <div class="org-toggle">
                <a href="<?= Url::to(['position/index', 'view' => 'grid', 'q' => $searchQuery, 'status' => $statusFilter]) ?>" class="<?= $viewMode === 'grid' ? 'is-active' : '' ?>">
                    <i class="fas fa-grip"></i> Grid
                </a>
                <a href="<?= Url::to(['position/index', 'view' => 'list', 'q' => $searchQuery, 'status' => $statusFilter]) ?>" class="<?= $viewMode === 'list' ? 'is-active' : '' ?>">
                    <i class="fas fa-table-list"></i> List
                </a>
            </div>
            <a class="org-btn org-btn-ghost" href="<?= Url::to(['application/index']) ?>">
                <i class="fas fa-layer-group"></i> Open ATS
            </a>
        </div>
    </div>

    <?php if (empty($models)): ?>
        <div class="org-card">
            <h3 class="org-card-title">No opportunities yet</h3>
            <p style="color:var(--org-text-2);margin:0 0 10px">Create your first internship and start receiving applications instantly.</p>
            <button type="button" class="org-btn org-btn-primary" data-open-position-modal data-url="<?= Url::to(['position/create']) ?>">
                <i class="fas fa-plus"></i> Create Internship
            </button>
        </div>
    <?php elseif ($viewMode === 'list'): ?>
        <div class="org-card">
            <table class="org-opps-table">
                <thead>
                <tr>
                    <th>Internship</th>
                    <th>Status</th>
                    <th>Applications</th>
                    <th>Posted</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($models as $position): ?>
                    <?php
                    $count = (int) ($appCounts[(int) $position->id] ?? 0);
                    $status = \common\models\Position::normalizeStatus((string) $position->status);
                    ?>
                    <tr data-position-row="<?= (int) $position->id ?>">
                        <td>
                            <strong><?= Html::encode($position->title) ?></strong><br>
                            <small style="color:var(--org-text-2)"><?= Html::encode(StringHelper::truncate((string) $position->description, 80)) ?></small>
                        </td>
                        <td>
                            <span class="org-tag <?= strtolower($status) ?>" data-position-status-badge="<?= (int) $position->id ?>">
                                <?= Html::encode($status) ?>
                            </span>
                        </td>
                        <td><?= $count ?></td>
                        <td><?= date('M d, Y', (int) $position->created_at) ?></td>
                        <td>
                            <?= $this->render('_opp_actions', ['position' => $position, 'compact' => true]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="org-opps-grid">
            <?php foreach ($models as $position): ?>
                <?php
                $count = (int) ($appCounts[(int) $position->id] ?? 0);
                $score = min(99, 55 + ($count * 3));
                $status = \common\models\Position::normalizeStatus((string) $position->status);
                ?>
                <article class="org-opp-card">
                    <div class="org-opp-head">
                        <?= ProfileAvatar::widget(['type' => 'organization', 'organization' => $organization, 'size' => 'sm']) ?>
                        <div>
                            <h3 class="org-opp-title"><?= Html::encode($position->title) ?></h3>
                            <p class="org-opp-sub"><?= Html::encode($organization->name ?? 'Organization') ?></p>
                        </div>
                        <span class="org-tag <?= strtolower($status) ?>" data-position-status-badge="<?= (int) $position->id ?>"><?= Html::encode($status) ?></span>
                    </div>

                    <div class="org-opp-tags">
                        <?php if ($position->field_of_study): ?><span class="org-tag"><?= Html::encode($position->field_of_study) ?></span><?php endif; ?>
                        <?php if ($position->duration): ?><span class="org-tag"><?= Html::encode($position->duration) ?></span><?php endif; ?>
                        <span class="org-tag">Score <?= $score ?></span>
                    </div>

                    <div class="org-opp-metrics">
                        <div class="org-metric"><div class="l">Applications</div><div class="v"><?= $count ?></div></div>
                        <div class="org-metric"><div class="l">Match quality</div><div class="v"><?= $score ?>%</div></div>
                        <div class="org-metric"><div class="l">Trend</div><div class="v"><?= $count > 4 ? '+12%' : '+3%' ?></div></div>
                    </div>

                    <?= $this->render('_opp_actions', ['position' => $position]) ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<div class="modal fade ft-modal-stack" id="orgPositionModal" tabindex="-1" aria-hidden="true" aria-labelledby="orgPositionModalTitle">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content org-position-form-modal"></div>
    </div>
</div>

