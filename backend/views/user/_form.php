<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;
?>
<div class="user-form">
    <?php $form = ActiveForm::begin(); ?>
    <?= $form->field($model, 'username')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'password_hash')->passwordInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'role')->dropDownList(['student' => 'Student', 'organization' => 'Organization', 'admin' => 'Admin']) ?>
    <?= $form->field($model, 'status')->dropDownList([
        \common\models\User::STATUS_PENDING => 'Pending',
        \common\models\User::STATUS_ACTIVE => 'Active',
        \common\models\User::STATUS_INACTIVE => 'Inactive',
        \common\models\User::STATUS_DELETED => 'Deleted',
    ]) ?>
    <?= $form->field($model, 'created_at')->textInput() ?>
    <?= $form->field($model, 'updated_at')->textInput() ?>
    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div> 