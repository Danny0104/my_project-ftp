<?php
/** @var array $insights */
/** @var string|null $title */
use yii\helpers\Html;

if (empty($insights)) {
    return;
}
$title = $title ?? 'Smart insights';
?>
<section class="ap-insights ap-glass">
    <header class="ap-insights__head">
        <h2><i class="fas fa-wand-magic-sparkles"></i> <?= Html::encode($title) ?></h2>
        <span class="ap-chip">Auto-generated</span>
    </header>
    <div class="ap-insights__grid">
        <?php foreach ($insights as $insight): ?>
            <article class="ap-insight ap-insight--<?= Html::encode($insight['type'] ?? 'neutral') ?>">
                <span class="ap-insight__icon"><i class="fas <?= Html::encode($insight['icon'] ?? 'fa-circle-info') ?>"></i></span>
                <p><?= Html::encode($insight['text']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
