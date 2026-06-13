<?php
/** @var yii\web\View $this */
/** @var \common\models\SupportConversation $conversation */
/** @var \common\models\User|null $user */

use common\models\SupportConversation;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Support: ' . $conversation->subject;
$this->params['breadcrumbs'][] = ['label' => 'Support Inbox', 'url' => ['index']];
$this->params['breadcrumbs'][] = Html::encode($conversation->subject);
?>

<div class="support-inbox-view">
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h1 class="h4 mb-2"><?= Html::encode($conversation->subject) ?></h1>
            <p class="text-muted mb-0">
                <?= Html::encode(SupportConversation::categoryOptions()[$conversation->category] ?? $conversation->category) ?>
                · <?= $user ? Html::encode($user->username . ' (' . $user->role . ')') : 'Unknown user' ?>
                · <?= Yii::$app->formatter->asDatetime($conversation->created_at) ?>
            </p>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body support-thread">
            <?php foreach ($conversation->messages as $msg): ?>
                <div class="support-msg <?= $msg->sender_role === 'admin' ? 'support-msg--admin' : 'support-msg--user' ?>">
                    <div class="support-msg-meta">
                        <strong><?= Html::encode(ucfirst($msg->sender_role)) ?></strong>
                        <time><?= Yii::$app->formatter->asDatetime($msg->created_at) ?></time>
                    </div>
                    <div class="support-msg-body"><?= nl2br(Html::encode($msg->body)) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php $form = ActiveForm::begin(['action' => ['reply', 'id' => $conversation->id], 'method' => 'post']); ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <label class="form-label fw-semibold">Reply to user</label>
                <textarea name="body" class="form-control mb-3" rows="4" required placeholder="Type your reply…"></textarea>
                <?= Html::submitButton('<i class="fas fa-paper-plane me-1"></i> Send Reply', ['class' => 'btn btn-primary', 'encode' => false]) ?>
            </div>
        </div>
    <?php ActiveForm::end(); ?>
</div>

<style>
.support-thread { display: flex; flex-direction: column; gap: 16px; max-height: 480px; overflow-y: auto; }
.support-msg { padding: 14px 16px; border-radius: 12px; max-width: 85%; }
.support-msg--user { background: #f1f5f9; align-self: flex-start; }
.support-msg--admin { background: #dbeafe; align-self: flex-end; }
.support-msg-meta { font-size: 0.8rem; color: #64748b; margin-bottom: 6px; display: flex; gap: 10px; }
.support-msg-body { font-size: 0.95rem; line-height: 1.55; }
</style>
