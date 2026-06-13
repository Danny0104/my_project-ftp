<?php

use common\models\Position;
use common\widgets\ProfileAvatar;
use yii\helpers\Html;
use yii\helpers\Url;

require_once __DIR__ . '/_public_helpers.php';

/** @var Position $position */
/** @var int $applicantCount */

$org = $position->organization;
$orgName = $org->name ?? 'Organization';
$service = pmPublicService();
$deadline = pmDeadlineMeta($position);
$badges = $service->publicBadges($position, $org);
$skills = pmSkillsList($position->skills_required);
$visibleSkills = array_slice($skills, 0, 3);
$hiddenSkillCount = max(0, count($skills) - count($visibleSkills));
$isAccepting = $service->isAcceptingApplications($position);
$isGuest = Yii::$app->user->isGuest;
$isStudent = !$isGuest && Yii::$app->user->identity && Yii::$app->user->identity->role === 'student';

$loginUrl = Url::to(['/site/login', 'returnUrl' => Url::to(['position/view', 'id' => $position->id])]);
$fieldLabel = $position->category ?: $position->field_of_study;
?>

<article class="pm-card pm-reveal<?= $deadline['is_closed'] ? ' pm-card--closed' : '' ?>" data-position-id="<?= (int) $position->id ?>">
    <header class="pm-card__head">
        <div class="pm-card__logo" aria-hidden="true"><?= ProfileAvatar::widget(['type' => 'organization', 'organization' => $org, 'size' => 'sm', 'fillSlot' => true]) ?></div>
        <div class="pm-card__intro">
            <h2 class="pm-card__title">
                <?= Html::a(Html::encode($position->title), ['position/view', 'id' => $position->id]) ?>
            </h2>
            <p class="pm-card__org"><?= Html::encode($orgName) ?></p>
        </div>
    </header>

    <div class="pm-card__badges" aria-label="Internship tags">
        <?php foreach ($badges as $badgeKey): ?>
            <?php
            $badgeClass = 'pm-badge--' . str_replace('_', '-', $badgeKey);
            if ($badgeKey === 'closing_soon') {
                $badgeClass = 'pm-badge--urgent';
            }
            ?>
            <span class="pm-badge <?= Html::encode($badgeClass) ?>">
                <?php if ($badgeKey === 'open'): ?>
                    <i class="fas fa-door-open" aria-hidden="true"></i>
                <?php elseif ($badgeKey === 'closed'): ?>
                    <i class="fas fa-lock" aria-hidden="true"></i>
                <?php elseif ($badgeKey === 'verified'): ?>
                    <i class="fas fa-shield-halved" aria-hidden="true"></i>
                <?php elseif ($badgeKey === 'closing_soon'): ?>
                    <i class="fas fa-hourglass-half" aria-hidden="true"></i>
                <?php elseif ($badgeKey === 'paid'): ?>
                    <i class="fas fa-coins" aria-hidden="true"></i>
                <?php endif; ?>
                <?= Html::encode(pmPublicBadgeLabel($badgeKey, $deadline)) ?>
            </span>
        <?php endforeach; ?>
    </div>

    <ul class="pm-card__meta pm-card__meta--grid">
        <?php if ($position->location): ?>
            <li><i class="fas fa-location-dot" aria-hidden="true"></i><span><?= Html::encode($position->location) ?></span></li>
        <?php endif; ?>
        <?php if ($position->duration): ?>
            <li><i class="fas fa-clock" aria-hidden="true"></i><span><?= Html::encode($position->duration) ?></span></li>
        <?php endif; ?>
        <li class="pm-card__meta-deadline<?= $deadline['is_closed'] ? ' is-closed' : ($deadline['is_urgent'] ? ' is-urgent' : '') ?>">
            <i class="fas fa-calendar-day" aria-hidden="true"></i>
            <span><?= Html::encode($deadline['label']) ?> · <?= Html::encode(pmFormatDeadline($position)) ?></span>
        </li>
        <?php if ($fieldLabel): ?>
            <li><i class="fas fa-graduation-cap" aria-hidden="true"></i><span><?= Html::encode($fieldLabel) ?></span></li>
        <?php endif; ?>
    </ul>

    <?php if ($visibleSkills): ?>
        <div class="pm-card__skills" aria-label="Required skills">
            <?php foreach ($visibleSkills as $skill): ?>
                <span class="pm-skill"><?= Html::encode($skill) ?></span>
            <?php endforeach; ?>
            <?php if ($hiddenSkillCount > 0): ?>
                <span class="pm-skill pm-skill--more">+<?= $hiddenSkillCount ?> more</span>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="pm-card__skills pm-card__skills--empty" aria-hidden="true"></div>
    <?php endif; ?>

    <footer class="pm-card__foot">
        <?= Html::a('<i class="fas fa-eye" aria-hidden="true"></i> View Details', ['position/view', 'id' => $position->id], [
            'class' => 'pm-btn pm-btn--ghost',
        ]) ?>
        <?php if (!$isAccepting): ?>
            <span class="pm-btn pm-btn--disabled" aria-disabled="true" title="Applications are no longer accepted">
                <i class="fas fa-lock" aria-hidden="true"></i> Closed
            </span>
        <?php elseif ($isGuest): ?>
            <?= Html::a('<i class="fas fa-paper-plane" aria-hidden="true"></i> Apply Now', $loginUrl, [
                'class' => 'pm-btn pm-btn--primary',
            ]) ?>
        <?php elseif ($isStudent): ?>
            <?= Html::a('<i class="fas fa-paper-plane" aria-hidden="true"></i> Apply Now', ['position/view', 'id' => $position->id, '#' => 'apply'], [
                'class' => 'pm-btn pm-btn--primary',
            ]) ?>
        <?php else: ?>
            <?= Html::a('<i class="fas fa-paper-plane" aria-hidden="true"></i> View to Apply', ['position/view', 'id' => $position->id], [
                'class' => 'pm-btn pm-btn--primary',
            ]) ?>
        <?php endif; ?>
    </footer>
</article>
