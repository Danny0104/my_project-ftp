<?php
/**
 * @var array $cards [['label','value','icon','accent','suffix'=>'','trend'=>'']]
 */
use yii\helpers\Html;

if (empty($cards)) {
    return;
}
?>
<div class="ap-kpi-grid">
    <?php foreach ($cards as $card):
        $accent = Html::encode($card['accent'] ?? 'blue');
        $suffix = $card['suffix'] ?? '';
        $value = (int) ($card['value'] ?? 0);
        ?>
        <article class="ap-kpi-card ap-kpi-card--<?= $accent ?>">
            <div class="ap-kpi-card__head">
                <span class="ap-kpi-card__icon"><i class="fas <?= Html::encode($card['icon'] ?? 'fa-chart-bar') ?>"></i></span>
                <?php if (!empty($card['trend'])): ?>
                    <span class="ap-kpi-card__trend"><?= Html::encode($card['trend']) ?></span>
                <?php endif; ?>
            </div>
            <div class="ap-kpi-card__value">
                <span data-ap-count="<?= $value ?>">0</span><?= $suffix !== '' ? '<span class="ap-kpi-card__suffix">' . Html::encode($suffix) . '</span>' : '' ?>
            </div>
            <div class="ap-kpi-card__label"><?= Html::encode($card['label']) ?></div>
        </article>
    <?php endforeach; ?>
</div>
