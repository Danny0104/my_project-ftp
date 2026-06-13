<?php
use yii\helpers\Html;
?>

<div class="notification-item <?= $notification->is_read == 0 ? 'unread' : 'read' ?> <?= $notification->sender_type ?>-notification">
    <div class="notification-icon">
        <?php if ($notification->sender_type == 'admin'): ?>
            <i class="fas fa-user-shield text-info"></i>
        <?php elseif ($notification->sender_type == 'organization'): ?>
            <i class="fas fa-building text-success"></i>
        <?php else: ?>
            <i class="fas fa-cog text-secondary"></i>
        <?php endif; ?>
    </div>
    <div class="notification-content">
        <div class="notification-header">
            <div class="notification-title">
                <strong><?= Html::encode($notification->title) ?></strong>
                <span class="notification-badge <?= $notification->sender_type ?>-badge">
                    <?= ucfirst($notification->sender_type) ?>
                </span>
            </div>
            <div class="notification-time">
                <?= Yii::$app->formatter->asRelativeTime($notification->created_at) ?>
            </div>
        </div>
        <div class="notification-message">
            <?= Html::encode($notification->message) ?>
        </div>
        <div class="notification-actions">
            <?php if ($notification->action_url): ?>
                <a href="<?= $notification->action_url ?>" class="btn btn-sm btn-outline-primary me-2">
                    <?= $notification->action_text ?: 'View Details' ?>
                </a>
            <?php endif; ?>
            
            <?php if ($notification->is_read == 0): ?>
                <button class="btn btn-sm btn-outline-secondary mark-read-btn" data-id="<?= $notification->id ?>">
                    Mark as Read
                </button>
            <?php else: ?>
                <button class="btn btn-sm btn-outline-secondary mark-unread-btn" data-id="<?= $notification->id ?>">
                    Mark as Unread
                </button>
            <?php endif; ?>
            
            <button class="btn btn-sm btn-outline-danger delete-notification-btn" data-id="<?= $notification->id ?>">
                Delete
            </button>
        </div>
    </div>
</div> 