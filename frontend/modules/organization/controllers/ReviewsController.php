<?php

namespace frontend\modules\organization\controllers;

use common\models\OrgReview;
use Yii;
use yii\web\NotFoundHttpException;

class ReviewsController extends BaseController
{
    protected function navKey(): string
    {
        return 'reviews';
    }

    public function actionIndex()
    {
        $reviews = OrgReview::find()
            ->where(['organization_id' => $this->orgId()])
            ->with(['student.user'])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $avgRating = 0;
        if ($reviews) {
            $avgRating = round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1);
        }

        $this->view->title = 'Reviews & Feedback';

        return $this->render('index', [
            'reviews' => $reviews,
            'avgRating' => $avgRating,
            'byCategory' => $this->groupByCategory($reviews),
        ]);
    }

    public function actionSave()
    {
        $id = (int) Yii::$app->request->post('id');
        $model = $id ? $this->findModel($id) : new OrgReview();
        $model->organization_id = $this->orgId();
        $model->reviewer_user_id = Yii::$app->user->id;
        $model->load(Yii::$app->request->post());

        if (!$model->save()) {
            return $this->jsonError('Validation failed.', ['errors' => $model->errors]);
        }

        $this->audit($id ? 'review.updated' : 'review.created', ['id' => $model->id]);
        return $this->jsonSuccess(['id' => $model->id]);
    }

    public function actionModerate()
    {
        $model = $this->findModel((int) Yii::$app->request->post('id'));
        $model->status = (string) Yii::$app->request->post('status', OrgReview::STATUS_MODERATED);
        $model->save(false);
        $this->audit('review.moderated', ['id' => $model->id]);
        return $this->jsonSuccess();
    }

    public function actionDelete()
    {
        $model = $this->findModel((int) Yii::$app->request->post('id'));
        $model->delete();
        $this->audit('review.deleted', ['id' => $model->id]);
        return $this->jsonSuccess();
    }

    private function findModel(int $id): OrgReview
    {
        $model = OrgReview::findOne(['id' => $id, 'organization_id' => $this->orgId()]);
        if (!$model) {
            throw new NotFoundHttpException('Review not found.');
        }
        return $model;
    }

    private function groupByCategory(array $reviews): array
    {
        $out = [];
        foreach ($reviews as $r) {
            $out[$r->category] = ($out[$r->category] ?? 0) + 1;
        }
        return $out;
    }
}
