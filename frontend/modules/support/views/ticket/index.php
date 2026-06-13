<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var string $status */
/** @var string $q */
/** @var int $totalUnread */

use common\models\SupportTicket;
use yii\bootstrap5\Html;
use yii\helpers\Url;

$this->title = 'Support';

$statuses = [
    '' => 'All',
    SupportTicket::STATUS_OPEN => 'Open',
    SupportTicket::STATUS_IN_PROGRESS => 'In Progress',
    SupportTicket::STATUS_RESOLVED => 'Resolved',
    SupportTicket::STATUS_CLOSED => 'Closed',
];
?>

<div class="support-hub" data-support-hub>
    <div class="support-hub__sidebar">
        <div class="support-hub__brand">
            <div>
                <div class="support-hub__title">Support</div>
                <div class="support-hub__subtitle">Tickets & conversations</div>
            </div>
            <?php if ($totalUnread > 0): ?>
                <span class="support-pill" data-support-unread><?= (int) $totalUnread ?></span>
            <?php endif; ?>
        </div>

        <div class="support-hub__actions">
            <?= Html::a('<i class="fas fa-plus"></i> New ticket', ['create'], ['class' => 'btn btn-primary w-100 support-btn']) ?>
        </div>

        <form class="support-hub__filters" method="get" action="<?= Url::to(['index']) ?>">
            <label class="support-label">Status</label>
            <select class="form-select support-select" name="status">
                <?php foreach ($statuses as $key => $label): ?>
                    <option value="<?= Html::encode($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= Html::encode($label) ?></option>
                <?php endforeach; ?>
            </select>

            <label class="support-label mt-3">Search</label>
            <input class="form-control support-input" type="search" name="q" value="<?= Html::encode($q) ?>" placeholder="Code or subject">
        </form>

        <div class="support-hub__list">
            <?php foreach ($dataProvider->getModels() as $ticket): ?>
                <?php /** @var SupportTicket $ticket */ ?>
                <a class="support-ticket" href="<?= Url::to(['view', 'code' => $ticket->code]) ?>">
                    <div class="support-ticket__head">
                        <strong><?= Html::encode($ticket->code) ?></strong>
                        <span class="support-status support-status--<?= Html::encode($ticket->status) ?>"><?= Html::encode(strtoupper(str_replace('_', ' ', $ticket->status))) ?></span>
                    </div>
                    <div class="support-ticket__subject"><?= Html::encode($ticket->subject) ?></div>
                    <div class="support-ticket__meta">
                        <span class="support-priority support-priority--<?= Html::encode($ticket->priority) ?>"><?= Html::encode($ticket->priority) ?></span>
                        <span class="support-time"><?= $ticket->last_message_at ? Yii::$app->formatter->asRelativeTime($ticket->last_message_at) : '—' ?></span>
                    </div>
                </a>
            <?php endforeach; ?>

            <?php if (!$dataProvider->getCount()): ?>
                <div class="support-empty">
                    <div class="support-empty__icon"><i class="fas fa-life-ring"></i></div>
                    <strong>No tickets found</strong>
                    <div class="text-muted">Create a ticket to contact support.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="support-hub__main">
        <div class="support-hub__blank">
            <div class="support-blank__card">
                <div class="support-blank__icon"><i class="fas fa-comments"></i></div>
                <h2>Select a ticket</h2>
                <p>Choose a conversation from the left, or create a new ticket.</p>
                <?= Html::a('Create a ticket', ['create'], ['class' => 'btn btn-outline-primary support-btn']) ?>
            </div>
        </div>
    </div>
</div>

