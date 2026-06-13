<?php
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var string $q */

use common\widgets\ProfileAvatar;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Students';
?>

<?= $this->render('@frontend/views/organization/_page_header', [
    'title' => 'Students',
    'subtitle' => 'Manage applicants who applied to your internship opportunities.',
    'actions' => [
        Html::a('<i class="fas fa-layer-group"></i> ATS', ['/application/index'], ['class' => 'org-btn org-btn-ghost']),
    ],
]) ?>

<form class="org-filter-bar" method="get">
    <div style="flex:1;min-width:200px">
        <label>Search</label>
        <input type="search" name="q" value="<?= Html::encode($q) ?>" placeholder="University, field, student ID…">
    </div>
    <button type="submit" class="org-btn org-btn-primary">Search</button>
</form>

<?php $models = $dataProvider->getModels(); ?>
<?php if (empty($models)): ?>
    <div class="org-empty-state">
        <div><i class="fas fa-user-graduate"></i></div>
        <h3>No students yet</h3>
        <p>Candidates appear here once they apply to your published opportunities.</p>
    </div>
<?php else: ?>
    <table class="org-data-table">
        <thead>
            <tr>
                <th>Candidate</th>
                <th>University</th>
                <th>Field</th>
                <th>GPA</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($models as $student): ?>
            <?php
            $name = $student->user->username ?? 'Student';
            ?>
            <tr>
                <td>
                    <div class="org-candidate-card">
                        <span class="org-avatar-lg"><?= ProfileAvatar::widget(['type' => 'student', 'student' => $student, 'size' => 'md']) ?></span>
                        <div>
                            <strong><?= Html::encode($name) ?></strong><br>
                            <span style="color:var(--org-text-3);font-size:12px"><?= Html::encode($student->student_id) ?></span>
                        </div>
                    </div>
                </td>
                <td><?= Html::encode($student->university) ?></td>
                <td><?= Html::encode($student->field_of_study ?: '—') ?></td>
                <td><?= $student->gpa !== null ? Html::encode(number_format((float) $student->gpa, 2)) : '—' ?></td>
                <td><?= Html::a('View profile', ['view', 'id' => $student->id], ['class' => 'org-btn org-btn-primary org-btn-sm']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="mt-3"><?= LinkPager::widget(['pagination' => $dataProvider->pagination]) ?></div>
<?php endif; ?>
