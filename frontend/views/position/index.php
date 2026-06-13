<?php

/**
 * Positions index — students use the student hub; guests use the public marketplace.
 *
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var array<string, mixed>|null $searchParams
 * @var string|null $sort
 * @var common\models\Organization[]|null $organizations
 * @var array<int, int>|null $applicantCounts
 * @var int|null $totalActive
 * @var int|null $totalOrgs
 */

use common\models\Position;

$this->title = 'Discover Internships';

$isStudent = !Yii::$app->user->isGuest && Yii::$app->user->identity->role === 'student';

if ($isStudent) {
    echo $this->render('_student_opportunities', ['dataProvider' => $dataProvider]);
    return;
}

echo $this->render('_public_marketplace', [
    'dataProvider' => $dataProvider,
    'searchParams' => $searchParams ?? [
        'title' => '',
        'location' => '',
        'field' => '',
        'organization_id' => 0,
        'duration' => '',
    ],
    'sort' => $sort ?? 'newest',
    'organizations' => $organizations ?? [],
    'applicantCounts' => $applicantCounts ?? [],
    'totalActive' => $totalActive ?? 0,
    'totalOrgs' => $totalOrgs ?? 0,
]);
