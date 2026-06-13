<?php
/** @var \common\models\OrgInterview[] $interviews */
/** @var \common\models\OrgInterview[] $upcoming */
/** @var \common\models\OrgInterview[] $past */
/** @var string $viewMode */

use common\models\OrgInterview;
use common\widgets\ProfileAvatar;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'My Interviews';
$monthStart = strtotime(date('Y-m-01'));
$daysInMonth = (int) date('t', $monthStart);
$monthLabel = date('F Y', $monthStart);
?>

<div class="ftp-page-header mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <h1 class="ftp-page-title">My Interviews</h1>
        <p class="ftp-page-sub text-muted mb-0">Upcoming and completed interview sessions with organizations.</p>
    </div>
    <div class="btn-group">
        <?= Html::a('List', ['index', 'view' => 'list'], ['class' => 'btn btn-sm ' . ($viewMode === 'list' ? 'btn-primary' : 'btn-outline-primary')]) ?>
        <?= Html::a('Calendar', ['index', 'view' => 'calendar'], ['class' => 'btn btn-sm ' . ($viewMode === 'calendar' ? 'btn-primary' : 'btn-outline-primary')]) ?>
    </div>
</div>

<?php if (empty($interviews)): ?>
    <div class="ftp-empty-state card border-0 shadow-sm p-5 text-center">
        <i class="fas fa-video fa-2x text-muted mb-3"></i>
        <h3>No interviews yet</h3>
        <p class="text-muted mb-3">When an organization schedules an interview, it will appear here.</p>
        <?= Html::a('View applications', ['application/index'], ['class' => 'btn btn-primary']) ?>
    </div>
<?php elseif ($viewMode === 'calendar'): ?>
    <div class="card border-0 shadow-sm p-3">
        <h2 class="h5 mb-3"><?= Html::encode($monthLabel) ?></h2>
        <div class="row row-cols-7 g-2 text-center small fw-semibold text-muted mb-2">
            <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dow): ?>
                <div class="col"><?= $dow ?></div>
            <?php endforeach; ?>
        </div>
        <div class="row row-cols-7 g-2">
            <?php
            $firstDow = (int) date('w', $monthStart);
            for ($blank = 0; $blank < $firstDow; $blank++) {
                echo '<div class="col"><div class="border rounded-3 p-2 bg-light" style="min-height:88px"></div></div>';
            }
            for ($day = 1; $day <= $daysInMonth; $day++):
                $dayTs = strtotime(date('Y-m-', $monthStart) . str_pad((string) $day, 2, '0', STR_PAD_LEFT));
                $dayKey = date('Y-m-d', $dayTs);
                $dayEvents = array_filter($interviews, static function (OrgInterview $iv) use ($dayKey) {
                    return date('Y-m-d', (int) $iv->scheduled_at) === $dayKey;
                });
                ?>
                <div class="col">
                    <div class="border rounded-3 p-2 h-100" style="min-height:88px">
                        <div class="fw-bold small mb-1"><?= $day ?></div>
                        <?php foreach ($dayEvents as $iv): ?>
                            <div class="badge bg-primary text-wrap mb-1" style="white-space:normal;font-size:10px">
                                <?= Html::encode(date('g:i A', $iv->scheduled_at) . ' · ' . $iv->title) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>
<?php else: ?>
    <?php if (!empty($upcoming)): ?>
        <section class="mb-4">
            <h2 class="h5 mb-3">Upcoming</h2>
            <div class="row g-3">
                <?php foreach ($upcoming as $iv): ?>
                    <div class="col-md-6">
                        <article class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start gap-2 mb-2">
                                    <?= ProfileAvatar::widget(['type' => 'organization', 'organization' => $iv->organization ?? null, 'size' => 'sm']) ?>
                                    <div class="flex-grow-1 min-w-0">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <h3 class="h6 mb-0"><?= Html::encode($iv->title) ?></h3>
                                    <span class="badge bg-primary"><?= Html::encode(OrgInterview::statusOptions()[$iv->status] ?? $iv->status) ?></span>
                                </div>
                                <?php if ($iv->position): ?>
                                    <p class="text-muted small mb-2"><?= Html::encode($iv->position->title) ?></p>
                                <?php endif; ?>
                                <p class="mb-2"><i class="fas fa-calendar me-2 text-primary"></i><?= Yii::$app->formatter->asDatetime($iv->scheduled_at) ?></p>
                                <?php if ($iv->interviewer_name): ?>
                                    <p class="mb-2 small"><i class="fas fa-user me-2"></i><?= Html::encode($iv->interviewer_name) ?></p>
                                <?php endif; ?>
                                <?php if ($iv->meeting_link): ?>
                                    <p class="mb-0"><?= Html::a('<i class="fas fa-link me-1"></i> Join meeting', $iv->meeting_link, ['class' => 'btn btn-sm btn-outline-primary', 'target' => '_blank', 'rel' => 'noopener']) ?></p>
                                <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($past)): ?>
        <section>
            <h2 class="h5 mb-3">Past & completed</h2>
            <div class="table-responsive card border-0 shadow-sm">
                <table class="table table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Interview</th>
                        <th>When</th>
                        <th>Status</th>
                        <th>Score</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($past as $iv): ?>
                        <tr>
                            <td>
                                <strong><?= Html::encode($iv->title) ?></strong>
                                <?php if ($iv->position): ?>
                                    <br><small class="text-muted"><?= Html::encode($iv->position->title) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= Yii::$app->formatter->asDatetime($iv->scheduled_at) ?></td>
                            <td><span class="badge bg-secondary"><?= Html::encode(OrgInterview::statusOptions()[$iv->status] ?? $iv->status) ?></span></td>
                            <td><?= $iv->evaluation_score !== null ? (int) $iv->evaluation_score . '%' : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
<?php endif; ?>
