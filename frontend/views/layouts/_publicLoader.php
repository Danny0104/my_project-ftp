<?php

/** @var \yii\web\View $this */

$loaderMessage = 'Loading Your Experience...';
$route = Yii::$app->controller->route ?? '';
if (str_starts_with($route, 'position/')) {
    $loaderMessage = 'Preparing Opportunities...';
}
?>
<div id="pltPageLoader" class="plt-loader" aria-live="polite" aria-busy="true" aria-label="Loading">
    <div class="plt-loader__backdrop" aria-hidden="true"></div>
    <div class="plt-loader__panel">
        <div class="plt-loader__stage">
            <div class="plt-loader__glow" aria-hidden="true"></div>
            <div class="plt-loader__ring" aria-hidden="true"></div>
            <div class="plt-loader__orbit" aria-hidden="true">
                <?php for ($i = 0; $i < 12; $i++): ?>
                    <span class="plt-loader__particle" style="--plt-i: <?= $i ?>"></span>
                <?php endfor; ?>
            </div>
            <div class="plt-loader__logo" aria-hidden="true">
                <i class="fas fa-graduation-cap"></i>
            </div>
        </div>
        <p class="plt-loader__title" id="pltLoaderTitle"><?= htmlspecialchars($loaderMessage, ENT_QUOTES, 'UTF-8') ?></p>
        <p class="plt-loader__subtitle">Connecting students with opportunities...</p>
        <p class="plt-loader__dots" id="pltLoaderDots" aria-hidden="true">
            <span class="plt-loader__dots-text">Loading</span><span class="plt-loader__dots-anim"></span>
        </p>
    </div>
</div>
<div id="pltRouteProgress" class="plt-route-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-hidden="true">
    <div class="plt-route-progress__bar" id="pltRouteProgressBar"></div>
</div>
