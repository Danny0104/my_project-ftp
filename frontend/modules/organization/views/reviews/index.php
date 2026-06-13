<?php
/** @var \common\models\OrgReview[] $reviews */
/** @var float $avgRating */
/** @var array $byCategory */

use common\models\OrgReview;
use yii\helpers\Html;
use yii\helpers\Url;

$csrf = Yii::$app->request->csrfParam;
$token = Yii::$app->request->getCsrfToken();
?>

<?= $this->render('@frontend/views/organization/_page_header', [
    'title' => 'Reviews & Feedback',
    'subtitle' => 'Performance reviews, satisfaction surveys, and moderation.',
    'actions' => [
        Html::button('<i class="fas fa-plus"></i> Add review', ['class' => 'org-btn org-btn-primary', 'data-org-open-modal' => 'reviewForm']),
    ],
]) ?>

<div class="org-kpi-grid">
    <div class="org-kpi-card"><div class="kpi-label">Average rating</div><div class="kpi-value"><?= Html::encode($avgRating) ?></div><div class="kpi-trend">/ 5</div></div>
    <div class="org-kpi-card"><div class="kpi-label">Total reviews</div><div class="kpi-value"><?= count($reviews) ?></div></div>
</div>

<?php if (!empty($byCategory)): ?>
    <div class="org-chart-card mb-4">
        <h3>By category</h3>
        <canvas id="orgChartReviews" height="100"
                data-labels='<?= Html::encode(json_encode(array_keys($byCategory))) ?>'
                data-values='<?= Html::encode(json_encode(array_values($byCategory))) ?>'></canvas>
    </div>
<?php endif; ?>

<table class="org-data-table">
    <thead><tr><th>Title</th><th>Rating</th><th>Category</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($reviews as $review): ?>
        <tr>
            <td><?= Html::encode($review->title) ?></td>
            <td><?= str_repeat('★', (int) $review->rating) ?></td>
            <td><?= Html::encode(OrgReview::categoryOptions()[$review->category] ?? $review->category) ?></td>
            <td><?= Html::encode($review->status) ?></td>
            <td>
                <?php if ($review->status === OrgReview::STATUS_PUBLISHED): ?>
                    <button type="button" class="org-btn org-btn-ghost org-btn-sm" data-org-moderate="<?= (int) $review->id ?>">Moderate</button>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="org-modal-backdrop" id="reviewFormModal" data-org-modal="reviewForm">
    <div class="org-modal">
        <h2>New review</h2>
        <form class="org-form-grid" data-org-ajax-form="<?= Url::to(['save']) ?>">
            <input type="hidden" name="<?= $csrf ?>" value="<?= $token ?>">
            <div><label>Title</label><input name="OrgReview[title]" required></div>
            <div><label>Rating (1-5)</label><input type="number" name="OrgReview[rating]" min="1" max="5" value="4" required></div>
            <div><label>Category</label>
                <select name="OrgReview[category]">
                    <?php foreach (OrgReview::categoryOptions() as $k => $v): ?>
                        <option value="<?= Html::encode($k) ?>"><?= Html::encode($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Feedback</label><textarea name="OrgReview[feedback]" rows="4"></textarea></div>
            <button type="submit" class="org-btn org-btn-primary">Publish</button>
        </form>
    </div>
</div>

<?php
$modUrl = Url::to(['moderate']);
$this->registerJs(<<<JS
document.querySelectorAll('[data-org-moderate]').forEach(function(btn){
  btn.addEventListener('click', function(){
    var fd = new FormData();
    fd.append('id', btn.getAttribute('data-org-moderate'));
    fd.append('status', 'moderated');
    fd.append('{$csrf}', '{$token}');
    fetch('{$modUrl}', {method:'POST', body:fd}).then(r=>r.json()).then(function(res){ if(res.success) location.reload(); });
  });
});
JS
);
?>
