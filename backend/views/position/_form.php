<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;
?>
<div class="position-form">
    <?php $form = ActiveForm::begin(); ?>
    <?= $form->field($model, 'organization_id')->textInput() ?>
    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>
    <?= $form->field($model, 'criteria')->textarea(['rows' => 6]) ?>
    <?= $form->field($model, 'location')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'status')->dropDownList(['open' => 'Open', 'closed' => 'Closed']) ?>
    <?= $form->field($model, 'created_at')->textInput() ?>
    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div> 