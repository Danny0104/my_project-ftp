<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;
?>
<div class="application-form">
    <?php $form = ActiveForm::begin(); ?>
    <?= $form->field($model, 'student_id')->textInput() ?>
    <?= $form->field($model, 'position_id')->textInput() ?>
    <?= $form->field($model, 'status')->dropDownList(['pending' => 'Pending', 'accepted' => 'Accepted', 'rejected' => 'Rejected']) ?>
    <?= $form->field($model, 'feedback')->textarea(['rows' => 6]) ?>
    <?= $form->field($model, 'created_at')->textInput() ?>
    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div> 