<?php
/** @var common\models\Position $position */
/** @var bool $compact */

use common\models\Position;
use yii\helpers\Html;
use yii\helpers\Url;

$compact = !empty($compact);
$status = Position::normalizeStatus((string) $position->status);
$toggle = Position::getStatusToggleMeta($status);
$isActive = $status === Position::STATUS_ACTIVE;
?>

<div class="org-opp-actions<?= $compact ? ' org-opp-actions--inline' : '' ?>" data-position-card="<?= (int) $position->id ?>">
    <button type="button"
            class="org-btn org-btn-ghost org-btn-sm"
            data-open-position-modal
            data-url="<?= Url::to(['position/edit', 'id' => $position->id]) ?>"
            title="Edit internship details">
        <i class="fas fa-pen"></i><?= $compact ? '' : ' Edit' ?>
    </button>

    <button type="button"
            class="org-btn org-btn-ghost org-btn-sm"
            data-delete-position="<?= (int) $position->id ?>"
            title="Delete internship">
        <i class="fas fa-trash"></i><?= $compact ? '' : ' Delete' ?>
    </button>

    <a href="<?= Url::to(['position/view', 'id' => $position->id]) ?>"
       class="org-btn org-btn-ghost org-btn-sm"
       title="View analytics">
        <i class="fas fa-chart-line"></i><?= $compact ? '' : ' Analytics' ?>
    </a>

    <div class="org-status-control<?= $isActive ? ' org-status-control--menu' : '' ?>">
        <button type="button"
                class="org-btn org-btn-sm org-btn-status-toggle <?= $toggle['primary'] ? 'org-btn-primary' : 'org-btn-ghost' ?>"
                data-toggle-position-status="<?= (int) $position->id ?>"
                data-current-status="<?= Html::encode($status) ?>"
                data-next-status="<?= Html::encode($toggle['next']) ?>"
                data-confirm="<?= Html::encode($toggle['confirm'] ?? '') ?>"
                data-label="<?= Html::encode($toggle['label']) ?>"
                data-icon="<?= Html::encode($toggle['icon']) ?>"
                <?= $isActive ? 'data-status-menu-trigger' : '' ?>
                title="<?= $isActive ? 'Manage internship status' : 'Change internship status' ?>">
            <i class="fas <?= Html::encode($toggle['icon']) ?>"></i>
            <?php if (!$compact): ?><span data-position-status-label><?= Html::encode($toggle['label']) ?></span><?php else: ?><span data-position-status-label class="visually-hidden"><?= Html::encode($toggle['label']) ?></span><?php endif; ?>
            <?php if ($isActive): ?><i class="fas fa-chevron-down org-status-caret"></i><?php endif; ?>
        </button>

        <?php if ($isActive): ?>
            <div class="org-status-menu" data-position-status-menu="<?= (int) $position->id ?>" hidden>
                <button type="button"
                        data-toggle-position-status="<?= (int) $position->id ?>"
                        data-current-status="<?= Html::encode($status) ?>"
                        data-next-status="<?= Html::encode(Position::STATUS_PAUSED) ?>"
                        data-label="Pause"
                        data-icon="fa-pause">
                    <i class="fas fa-pause"></i> Pause
                </button>
                <button type="button"
                        data-close-position="<?= (int) $position->id ?>"
                        data-current-status="<?= Html::encode($status) ?>"
                        data-next-status="<?= Html::encode(Position::STATUS_CLOSED) ?>">
                    <i class="fas fa-stop"></i> <?= $compact ? 'Close' : 'Close internship' ?>
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>
