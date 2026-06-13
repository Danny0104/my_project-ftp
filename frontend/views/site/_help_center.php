<?php
/** @var yii\web\View $this */
/** @var string $userRole */

if ($userRole === 'organization') {
    echo $this->render('_help_center_organization');
    return;
}

echo $this->render('_help_center_student');
