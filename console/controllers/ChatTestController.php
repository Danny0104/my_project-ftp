<?php

namespace console\controllers;

use common\models\Application;
use common\models\Organization;
use common\models\User;
use common\services\ChatService;
use yii\console\Controller;

class ChatTestController extends Controller
{
    public function actionListApplications()
    {
        $rows = Application::find()->with(['position'])->orderBy(['id' => SORT_DESC])->limit(10)->all();
        foreach ($rows as $a) {
            $this->stdout("#{$a->id} user={$a->user_id} pos=" . ($a->position->title ?? '?') . ' org=' . ($a->position->organization_id ?? '?') . "\n");
        }
        foreach (Organization::find()->all() as $o) {
            $u = User::findOne($o->user_id);
            $this->stdout("org#{$o->id} user#{$o->user_id} " . ($u->username ?? '') . "\n");
        }
        return 0;
    }

    public function actionEnsureApplication($applicationId = 1)
    {
        $app = Application::find()->with(['position'])->where(['id' => (int) $applicationId])->one();
        if (!$app) {
            $this->stderr("Application not found\n");
            return 1;
        }
        $orgId = (int) ($app->position->organization_id ?? 0);
        $org = Organization::findOne($orgId);
        if (!$org) {
            $this->stderr("Organization not found for app\n");
            return 1;
        }
        $this->stdout("App #{$app->id} org user #{$org->user_id} student user #{$app->user_id}\n");

        $chat = new ChatService();
        try {
            $conv = $chat->ensureForApplication((int) $app->id, (int) $org->user_id);
            $thread = $chat->getMessages((int) $conv->id, (int) $org->user_id);
            $this->stdout("OK conversation #{$conv->id} messages=" . count($thread['messages']) . "\n");
        } catch (\Throwable $e) {
            $this->stderr('FAIL: ' . $e->getMessage() . "\n");
            return 1;
        }
        return 0;
    }
}
