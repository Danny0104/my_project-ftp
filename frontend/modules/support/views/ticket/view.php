<?php

/** @var yii\web\View $this */
/** @var common\models\SupportTicket $ticket */
/** @var common\models\SupportMessage[] $messages */

use common\models\SupportTicket;
use yii\bootstrap5\Html;
use yii\helpers\Url;

$this->title = $ticket->code;

$statusLabels = [
    SupportTicket::STATUS_OPEN => 'Open',
    SupportTicket::STATUS_IN_PROGRESS => 'In Progress',
    SupportTicket::STATUS_RESOLVED => 'Resolved',
    SupportTicket::STATUS_CLOSED => 'Closed',
];
?>

<div class="support-hub support-hub--thread" data-support-thread data-ticket-code="<?= Html::encode($ticket->code) ?>">
    <aside class="support-hub__sidebar">
        <div class="support-hub__brand">
            <div>
                <div class="support-hub__title">Support</div>
                <div class="support-hub__subtitle">Ticket <?= Html::encode($ticket->code) ?></div>
            </div>
            <?= Html::a('<i class="fas fa-chevron-left"></i>', ['index'], ['class' => 'support-icon-btn', 'title' => 'Back']) ?>
        </div>

        <div class="support-hub__list">
            <a class="support-ticket is-active" href="#">
                <div class="support-ticket__head">
                    <strong><?= Html::encode($ticket->code) ?></strong>
                    <span class="support-status support-status--<?= Html::encode($ticket->status) ?>"><?= Html::encode($statusLabels[$ticket->status] ?? $ticket->status) ?></span>
                </div>
                <div class="support-ticket__subject"><?= Html::encode($ticket->subject) ?></div>
                <div class="support-ticket__meta">
                    <span class="support-priority support-priority--<?= Html::encode($ticket->priority) ?>"><?= Html::encode($ticket->priority) ?></span>
                    <span class="support-time"><?= $ticket->last_message_at ? Yii::$app->formatter->asRelativeTime($ticket->last_message_at) : '—' ?></span>
                </div>
            </a>
        </div>
    </aside>

    <section class="support-hub__main">
        <header class="support-thread__topbar">
            <div class="support-thread__title">
                <div class="support-thread__code"><?= Html::encode($ticket->code) ?></div>
                <div class="support-thread__subject"><?= Html::encode($ticket->subject) ?></div>
            </div>
            <div class="support-thread__badges">
                <span class="support-status support-status--<?= Html::encode($ticket->status) ?>"><?= Html::encode($statusLabels[$ticket->status] ?? $ticket->status) ?></span>
                <span class="support-priority support-priority--<?= Html::encode($ticket->priority) ?>"><?= Html::encode($ticket->priority) ?></span>
            </div>
        </header>

        <div class="support-thread__body" id="supportThreadBody">
            <?php foreach ($messages as $m): ?>
                <div class="support-msg support-msg--<?= $m->sender_role === 'student' || $m->sender_role === 'organization' ? 'out' : 'in' ?>" data-msg-id="<?= (int) $m->id ?>">
                    <div class="support-msg__meta">
                        <span class="support-msg__from"><?= Html::encode($m->sender_role) ?></span>
                        <span class="support-msg__time"><?= Yii::$app->formatter->asRelativeTime($m->created_at) ?></span>
                    </div>
                    <div class="support-msg__bubble">
                        <?= nl2br(Html::encode($m->body)) ?>
                        <?php if (!empty($m->attachments)): ?>
                            <div class="support-attachments">
                                <?php foreach ($m->attachments as $a): ?>
                                    <a class="support-attachment" href="<?= Html::encode(Url::to('@web/' . ltrim($a->path, '/'))) ?>" target="_blank" rel="noopener">
                                        <i class="fas fa-paperclip me-1"></i><?= Html::encode($a->name) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <footer class="support-thread__composer">
            <?php if ($ticket->status === SupportTicket::STATUS_CLOSED): ?>
                <div class="alert alert-secondary mb-0">This ticket is closed. You can view the history but cannot reply.</div>
            <?php else: ?>
                <form id="supportComposer" class="support-composer" method="post" enctype="multipart/form-data" action="<?= Url::to(['/support/api/send']) ?>">
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
                    <?= Html::hiddenInput('code', $ticket->code) ?>
                    <textarea class="form-control support-input" name="body" rows="2" placeholder="Write a message to support…" required></textarea>
                    <div class="support-composer__row">
                        <input type="file" name="attachment" class="form-control support-file" accept="image/*,application/pdf">
                        <button type="submit" class="btn btn-primary support-btn"><i class="fas fa-paper-plane me-1"></i>Send</button>
                    </div>
                </form>
            <?php endif; ?>
        </footer>
    </section>

    <aside class="support-hub__details">
        <div class="support-details">
            <div class="support-details__title">Details</div>
            <div class="support-details__item">
                <span>Status</span>
                <strong><?= Html::encode($statusLabels[$ticket->status] ?? $ticket->status) ?></strong>
            </div>
            <div class="support-details__item">
                <span>Priority</span>
                <strong><?= Html::encode($ticket->priority) ?></strong>
            </div>
            <div class="support-details__item">
                <span>Created</span>
                <strong><?= Yii::$app->formatter->asDatetime($ticket->created_at) ?></strong>
            </div>
            <div class="support-details__item">
                <span>Last update</span>
                <strong><?= $ticket->last_message_at ? Yii::$app->formatter->asDatetime($ticket->last_message_at) : '—' ?></strong>
            </div>
        </div>
    </aside>
</div>

