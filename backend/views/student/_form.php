<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;
?>
<div class="student-form">
    <?php $form = ActiveForm::begin(); ?>
    <?= $form->field($model, 'user_id')->textInput() ?>
    <?= $form->field($model, 'student_id')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'university')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'cv')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'personal_statement')->textarea(['rows' => 6]) ?>
    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div> 