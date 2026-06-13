<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use common\models\Notification;
?>
<div class="notification-form">
    <?php $form = ActiveForm::begin(); ?>
    
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'user_id')->textInput() ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    
    <?= $form->field($model, 'message')->textarea(['rows' => 6]) ?>
    
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'sender_type')->dropDownList([
                Notification::SENDER_TYPE_ADMIN => 'Admin',
                Notification::SENDER_TYPE_ORGANIZATION => 'Organization',
                Notification::SENDER_TYPE_SYSTEM => 'System'
            ], ['prompt' => 'Select sender type']) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'sender_id')->textInput() ?>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'action_url')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'action_text')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'is_read')->dropDownList([0 => 'No', 1 => 'Yes']) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'created_at')->textInput(['readonly' => true]) ?>
        </div>
    </div>
    
    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div> 