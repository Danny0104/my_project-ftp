<?php
/** @var string $title */
/** @var string|null $subtitle */
/** @var array $actions HTML strings */
/** @var string|null $titleAvatar */
?>
<div class="org-page-header">
    <div class="d-flex align-items-start gap-3">
        <?php if (!empty($titleAvatar)): ?>
            <div class="org-page-header-avatar"><?= $titleAvatar ?></div>
        <?php endif; ?>
        <div>
        <h1><?= htmlspecialchars($title) ?></h1>
        <?php if (!empty($subtitle)): ?>
            <p><?= htmlspecialchars($subtitle) ?></p>
        <?php endif; ?>
        </div>
    </div>
    <?php if (!empty($actions)): ?>
        <div class="org-page-actions">
            <?php foreach ($actions as $actionHtml): ?>
                <?= $actionHtml ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
