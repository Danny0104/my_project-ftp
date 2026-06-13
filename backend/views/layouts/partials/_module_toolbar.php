<?php
/**
 * @var string|null $searchPlaceholder
 * @var string|null $searchId
 * @var string|null $searchTarget DOM id of container with [data-search] items
 * @var array $filters HTML strings for filter controls
 * @var string|null $viewToggleId optional
 */
use yii\helpers\Html;

$searchPlaceholder = $searchPlaceholder ?? 'Search…';
$searchId = $searchId ?? 'apModuleSearch';
$searchTarget = $searchTarget ?? '';
$filters = $filters ?? [];
?>
<div class="ap-module-toolbar ap-glass">
    <div class="ap-module-toolbar__search">
        <i class="fas fa-search" aria-hidden="true"></i>
        <input type="search" id="<?= Html::encode($searchId) ?>" class="ap-module-search" placeholder="<?= Html::encode($searchPlaceholder) ?>" autocomplete="off"<?= $searchTarget !== '' ? ' data-target="' . Html::encode($searchTarget) . '"' : '' ?>>
    </div>
    <?php if (!empty($filters)): ?>
        <div class="ap-module-toolbar__filters">
            <?php foreach ($filters as $filterHtml): ?>
                <?= $filterHtml ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($viewToggleId)): ?>
        <div class="ap-view-toggle" data-ap-view-toggle="<?= Html::encode($viewToggleId) ?>">
            <button type="button" class="is-active" data-view="grid" title="Grid view"><i class="fas fa-grid-2"></i></button>
            <button type="button" data-view="list" title="List view"><i class="fas fa-list"></i></button>
        </div>
    <?php endif; ?>
</div>
