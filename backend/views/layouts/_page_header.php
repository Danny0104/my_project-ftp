<?php
/**
 * Reusable admin page header partial.
 *
 * @var string $title
 * @var string|null $subtitle
 * @var array $actions HTML action buttons
 * @var array|null $stats mini stat pills [['label'=>'','value'=>'']]
 */
use yii\helpers\Html;

$subtitle = $subtitle ?? null;
$actions = $actions ?? [];
$stats = $stats ?? [];
?>
<header class="ap-page-header ap-glass">
    <div class="ap-page-header-main">
        <div>
            <h1><?= Html::encode($title) ?></h1>
            <?php if ($subtitle): ?>
                <p><?= Html::encode($subtitle) ?></p>
            <?php endif; ?>
        </div>
        <?php if (!empty($actions)): ?>
            <div class="ap-page-actions">
                <?php foreach ($actions as $action): ?>
                    <?= $action ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php if (!empty($stats)): ?>
        <div class="ap-header-stats">
            <?php foreach ($stats as $stat): ?>
                <div class="ap-header-stat">
                    <span class="ap-header-stat-value"><?= Html::encode($stat['value']) ?></span>
                    <span class="ap-header-stat-label"><?= Html::encode($stat['label']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</header>
