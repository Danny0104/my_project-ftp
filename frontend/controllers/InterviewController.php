<?php

namespace frontend\controllers;

use common\models\OrgInterview;
use common\models\Student;
use common\traits\RoleDashboardLayoutTrait;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;

/**
 * Student-facing interview schedule view.
 */
class InterviewController extends Controller
{
    use RoleDashboardLayoutTrait;

    public $layout = 'student';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            $user = Yii::$app->user->identity;
                            return $user && $user->role === 'student';
                        },
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $student = Student::findOne(['user_id' => (int) Yii::$app->user->id]);
        if (!$student) {
            throw new ForbiddenHttpException('Student profile required.');
        }

        $viewMode = Yii::$app->request->get('view', 'list');
        if (!in_array($viewMode, ['list', 'calendar'], true)) {
            $viewMode = 'list';
        }

        $this->view->params['ftpNavActive'] = 'interviews';
        $this->view->title = 'My Interviews';

        $interviews = OrgInterview::find()
            ->where(['student_id' => (int) $student->id])
            ->andWhere(['not', ['status' => OrgInterview::STATUS_CANCELLED]])
            ->with(['position', 'application', 'organization'])
            ->orderBy(['scheduled_at' => SORT_ASC])
            ->all();

        $upcoming = array_filter($interviews, static function (OrgInterview $i) {
            return $i->status === OrgInterview::STATUS_SCHEDULED && $i->scheduled_at >= time();
        });

        $past = array_filter($interviews, static function (OrgInterview $i) {
            return $i->status !== OrgInterview::STATUS_SCHEDULED || $i->scheduled_at < time();
        });

        return $this->render('index', [
            'interviews' => $interviews,
            'upcoming' => $upcoming,
            'past' => $past,
            'viewMode' => $viewMode,
        ]);
    }
}
