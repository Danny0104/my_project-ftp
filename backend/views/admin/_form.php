<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;

?>
<div class="admin-form">
    <?php
    $form = ActiveForm::begin();
    ?>
    <?= $form->field($model, 'username')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'status')->dropDownList([
        \common\models\Admin::STATUS_ACTIVE => 'Active',
        \common\models\Admin::STATUS_REJECTED => 'Rejected',
    ]) ?>
    <?= $form->field($model, 'password')->passwordInput()->hint($model->isNewRecord ? '' : 'Leave blank to keep current password.') ?>
    <?php if ($model->hasErrors()): ?>
        <div class="alert alert-danger">
            <?php foreach ($model->getErrors() as $errors): ?>
                <?php foreach ($errors as $error): ?>
                    <?= Html::encode($error) ?><br>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div> 