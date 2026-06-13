<?php

define('YII_DEBUG', true);
define('YII_ENV', 'dev');

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';
require __DIR__ . '/../../frontend/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/main.php',
    require __DIR__ . '/../../common/config/main-local.php',
    require __DIR__ . '/../../frontend/config/main.php',
    require __DIR__ . '/../../frontend/config/main-local.php'
);

$app = new yii\console\Application($config);

$userId = (int) ($argv[1] ?? 16);

function bench(string $label, callable $fn): void
{
    $start = microtime(true);
    $fn();
    $ms = round((microtime(true) - $start) * 1000, 1);
    echo str_pad($label, 42) . $ms . " ms\n";
}

bench('Student::findOrCreateForUserId', function () use ($userId) {
    \common\models\Student::findOrCreateForUserId($userId);
});

bench('Applications all', function () use ($userId) {
    \common\models\Application::find()->where(['user_id' => $userId])->with(['position', 'position.organization'])->all();
});

bench('Position Active count', function () {
    \common\models\Position::find()->where(['status' => 'Active'])->count();
});

bench('ChatService unread', function () use ($userId) {
    (new \common\services\ChatService())->countUnreadForUser($userId);
});

bench('Support unread loop', function () use ($userId) {
    $user = \common\models\User::findOne($userId);
    if (!$user) {
        return;
    }
    $ticketIds = \common\models\SupportTicket::findVisibleToUser($userId, (string) $user->role)->select(['id'])->column();
    $supportUnreadCount = 0;
    if (!empty($ticketIds)) {
        $reads = \common\models\SupportTicketRead::find()
            ->where(['user_id' => $userId, 'ticket_id' => $ticketIds])
            ->indexBy('ticket_id')
            ->all();
        foreach ($ticketIds as $tid) {
            $lastRead = isset($reads[$tid]) ? (int) $reads[$tid]->last_read_message_id : 0;
            $supportUnreadCount += (int) \common\models\SupportMessage::find()
                ->where(['ticket_id' => (int) $tid, 'is_internal_note' => 0])
                ->andWhere(['>', 'id', $lastRead])
                ->count();
        }
    }
});

bench('authManager init', function () {
    Yii::$app->authManager->getRoles();
});
