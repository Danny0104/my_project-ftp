<?php

/** @var yii\web\View $this */
/** @var common\models\Position $model */
/** @var common\models\Application|null $application */
/** @var common\models\Student|null $student */
/** @var common\services\EligibilityResult|null $eligibility */
/** @var int|null $profileCompletion */
/** @var array{matched: int, total: int, percent: int} $skillsOverlap */
/** @var int $applicationCount */
/** @var array<int, string> $allowedFieldNames */
/** @var common\models\Position[] $similarPositions */
/** @var int $orgActiveCount */
/** @var int|null $orgHireRate */

use frontend\assets\PositionDetailAsset;
use yii\helpers\Html;

$this->title = $model->title;

$identity = Yii::$app->user->identity;
if (Yii::$app->user->isGuest || !$identity) {
    $this->params['breadcrumbs'][] = ['label' => 'Home', 'url' => ['/site/index']];
    $this->params['breadcrumbs'][] = ['label' => 'Opportunities', 'url' => ['/position/index']];
    $this->params['breadcrumbs'][] = ['label' => $model->title];
}

PositionDetailAsset::register($this);

echo $this->render('_detail_content', [
    'model' => $model,
    'application' => $application,
    'student' => $student,
    'eligibility' => $eligibility,
    'profileCompletion' => $profileCompletion,
    'skillsOverlap' => $skillsOverlap,
    'applicationCount' => $applicationCount,
    'allowedFieldNames' => $allowedFieldNames,
    'similarPositions' => $similarPositions,
    'orgActiveCount' => $orgActiveCount,
    'orgHireRate' => $orgHireRate,
]);
