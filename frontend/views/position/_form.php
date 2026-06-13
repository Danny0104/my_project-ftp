<?php
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use common\models\FieldOfStudy;

/* @var $model common\models\Position */
/* @var $isCreate boolean */

$groupedFields = FieldOfStudy::getGroupedForSelector();
$selectedIds = array_values(array_map('intval', (array) $model->allowedFieldIds));
$primaryFieldOptions = FieldOfStudy::find()
    ->select('name')
    ->where(['is_active' => true])
    ->orderBy(['name' => SORT_ASC])
    ->column();
$primaryFieldList = array_combine($primaryFieldOptions, $primaryFieldOptions);
?>

<div class="modal-header">
    <h5 class="modal-title"><?= $isCreate ? 'Add New Internship' : 'Edit Internship' ?></h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<?php $form = ActiveForm::begin([
    'id' => $isCreate ? 'addPositionForm' : 'editPositionForm',
    'options' => ['class' => 'position-form'],
    'enableAjaxValidation' => false,
    'action' => $isCreate ? Url::to(['position/create']) : Url::to(['position/edit', 'id' => $model->id]),
]); ?>

<div class="modal-body org-position-form-body">
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'title')->textInput(['maxlength' => true, 'required' => true]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'duration')->dropDownList([
                '1 month' => '1 month',
                '2 months' => '2 months',
                '3 months' => '3 months',
                '4 months' => '4 months',
                '6 months' => '6 months',
            ], ['prompt' => 'Select duration']) ?>
        </div>
    </div>

    <?= $form->field($model, 'description')->textarea(['rows' => 4, 'required' => true]) ?>

    <div class="form-group field-position-allowedfieldids required">
        <label class="form-label" for="orgFieldSearch">Allowed Fields of Study <span class="text-danger">*</span></label>
        <p class="help-block text-muted small mb-2">Only students in these academic fields may apply. Search, group by faculty, and select all relevant specializations.</p>

        <div class="org-field-select"
             data-org-field-select
             data-groups="<?= Html::encode(Json::encode($groupedFields)) ?>"
             data-selected="<?= Html::encode(Json::encode($selectedIds)) ?>">
            <div class="org-field-select__toolbar">
                <input type="search"
                       id="orgFieldSearch"
                       class="org-field-select__search"
                       placeholder="Search fields…"
                       autocomplete="off"
                       aria-label="Search academic fields">
                <div class="org-field-select__actions">
                    <button type="button" class="org-field-select__btn" data-field-select-all>Select all</button>
                    <button type="button" class="org-field-select__btn" data-field-clear-all>Clear all</button>
                </div>
                <span class="org-field-select__count"><?= count($selectedIds) ?> selected</span>
            </div>

            <div class="org-field-select__tags" aria-live="polite"></div>

            <div class="org-field-select__panel" role="listbox" aria-label="Academic fields"></div>

            <div class="org-field-select__error">Please select at least one academic field.</div>
            <div class="org-field-select__inputs"></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'field_of_study')->dropDownList(
                $primaryFieldList,
                ['prompt' => 'Primary field (display label)']
            )->hint('Shown on listings; allowed fields above enforce eligibility.') ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'category')->dropDownList([
                'technology' => 'Technology',
                'healthcare' => 'Healthcare',
                'engineering' => 'Engineering',
                'business' => 'Business',
                'law' => 'Law',
                'education' => 'Education',
                'agriculture' => 'Agriculture',
            ], ['prompt' => 'Internship category']) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <?= $form->field($model, 'academic_level_required')->dropDownList([
                '' => 'Any level',
                'undergraduate' => 'Undergraduate',
                'graduate' => 'Graduate',
                'postgraduate' => 'Postgraduate',
                'diploma' => 'Diploma',
            ]) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'min_gpa')->textInput(['type' => 'number', 'step' => '0.01', 'min' => 0, 'max' => 4, 'placeholder' => 'e.g. 2.5']) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'application_deadline')->input('date', [
                'value' => $model->application_deadline ? date('Y-m-d', $model->application_deadline) : '',
            ]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'skills_required')->textInput([
                'placeholder' => 'e.g., Python, Communication, Excel',
                'maxlength' => true,
            ]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'location')->textInput(['maxlength' => true]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'status')->dropDownList(\common\models\Position::getStatusOptions()) ?>
        </div>
    </div>

    <?= $form->field($model, 'criteria')->textarea(['rows' => 3, 'placeholder' => 'Additional requirements or criteria...']) ?>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    <?= Html::submitButton($isCreate ? 'Save Position' : 'Save Changes', [
        'class' => 'btn btn-primary',
        'id' => $isCreate ? 'saveNewPosition' : 'saveEditPosition',
    ]) ?>
</div>

<?php ActiveForm::end(); ?>
