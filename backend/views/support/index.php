<?php
/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var string $q */
/** @var array<int, \common\models\User> $chatUsers */
/** @var int $unreadCount */

use common\models\SupportConversation;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Support Inbox';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="support-inbox-index">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted mb-0">User help requests and live chat conversations</p>
        </div>
        <?php if ($unreadCount > 0): ?>
            <span class="badge bg-danger"><?= (int) $unreadCount ?> unread</span>
        <?php endif; ?>
    </div>

    <form method="get" class="row g-2 mb-4">
        <div class="col-md-6">
            <input type="search" name="q" value="<?= Html::encode($q) ?>" class="form-control" placeholder="Search by subject or category…">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Search</button>
        </div>
    </form>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Support Requests</div>
                <div class="card-body p-0">
                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'tableOptions' => ['class' => 'table table-hover mb-0'],
                        'layout' => "{items}\n<div class='p-3'>{pager}</div>",
                        'columns' => [
                            [
                                'attribute' => 'subject',
                                'format' => 'raw',
                                'value' => static function (SupportConversation $model) {
                                    return Html::a(Html::encode($model->subject), ['view', 'id' => $model->id]);
                                },
                            ],
                            [
                                'attribute' => 'category',
                                'value' => static function (SupportConversation $model) {
                                    return SupportConversation::categoryOptions()[$model->category] ?? $model->category;
                                },
                            ],
                            [
                                'label' => 'User',
                                'value' => static function (SupportConversation $model) {
                                    return $model->user ? $model->user->username : '—';
                                },
                            ],
                            [
                                'attribute' => 'last_message_at',
                                'format' => ['datetime', 'short'],
                            ],
                        ],
                    ]) ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Recent Live Chats</div>
                <ul class="list-group list-group-flush">
                    <?php if (empty($chatUsers)): ?>
                        <li class="list-group-item text-muted">No live chat sessions yet.</li>
                    <?php else: ?>
                        <?php foreach ($chatUsers as $chatUser): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?= Html::encode($chatUser->username) ?> <small class="text-muted">(<?= Html::encode($chatUser->role) ?>)</small></span>
                                <?= Html::a('Open', ['chat', 'user_id' => $chatUser->id], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
