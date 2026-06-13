<?php

use common\models\AcademicFaculty;
use common\models\FieldOfStudy;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var common\models\FieldOfStudy[] $fields */
/** @var common\models\AcademicFaculty[] $faculties */

$this->title = 'Faculties & Fields of Study';
$this->params['breadcrumbs'][] = $this->title;
$this->params['apNavActive'] = 'faculties';

$byCategory = [];
foreach ($fields as $field) {
    if (!$field->is_active) {
        continue;
    }
    $byCategory[$field->category][] = $field;
}

$csrf = Yii::$app->request->csrfParam;
$token = Yii::$app->request->getCsrfToken();
?>

<div class="ap-module">
<?= $this->render('../layouts/_page_header', [
    'title' => 'Academic structure intelligence',
    'subtitle' => 'Faculties, departments, and internship demand by field of study',
    'actions' => [
        Html::button('<i class="fas fa-university"></i> Create Faculty', ['class' => 'ap-btn ap-btn-ghost', 'data-ap-open-modal' => 'facultyForm']),
        Html::button('<i class="fas fa-plus"></i> Create Field', ['class' => 'ap-btn ap-btn-primary', 'data-ap-open-modal' => 'fieldForm']),
        Html::a('<i class="fas fa-scale-balanced"></i> Regulations', ['site/regulations'], ['class' => 'ap-btn ap-btn-ghost']),
    ],
]) ?>

<?= $this->render('../layouts/partials/_kpi_grid', [
    'cards' => [
        ['label' => 'Fields registered', 'value' => count(array_filter($fields, static fn($f) => $f->is_active)), 'icon' => 'fa-sitemap', 'accent' => 'blue'],
        ['label' => 'Categories', 'value' => count($byCategory), 'icon' => 'fa-layer-group', 'accent' => 'purple'],
        ['label' => 'Faculties', 'value' => count($faculties), 'icon' => 'fa-university', 'accent' => 'teal'],
        ['label' => 'Departments', 'value' => count(array_unique(array_filter(array_map(static fn($f) => $f->department, $fields)))), 'icon' => 'fa-building-columns', 'accent' => 'green'],
    ],
]) ?>

<div class="ap-panel ap-glass mb-4">
    <h3 style="margin:0 0 16px;font-size:1rem;font-weight:600"><i class="fas fa-university me-2"></i>Faculties</h3>
    <div class="ap-table-wrap">
        <table class="ap-table">
            <thead>
                <tr><th>Faculty</th><th>Fields</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($faculties as $faculty):
                    $fieldCount = (int) FieldOfStudy::find()->where(['faculty_id' => $faculty->id, 'is_active' => true])->count();
                    ?>
                    <tr>
                        <td><strong><?= Html::encode($faculty->name) ?></strong></td>
                        <td><?= $fieldCount ?></td>
                        <td><span class="ap-tag"><?= $faculty->is_active ? 'Active' : 'Inactive' ?></span></td>
                        <td>
                            <?= Html::button('Edit', [
                                'class' => 'ap-btn ap-btn-ghost ap-btn-sm',
                                'data-ap-open-modal' => 'facultyForm',
                                'data-prefill-id' => $faculty->id,
                                'data-prefill-name' => $faculty->name,
                                'data-prefill-description' => $faculty->description,
                                'data-prefill-is_active' => $faculty->is_active ? '1' : '0',
                            ]) ?>
                            <?= Html::button('Delete', [
                                'class' => 'ap-btn ap-btn-ghost ap-btn-sm',
                                'data-ap-delete' => Url::to(['site/delete-faculty']),
                                'data-id' => $faculty->id,
                                'data-confirm' => 'Delete this faculty?',
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($byCategory as $category => $items): ?>
    <div class="ap-panel ap-glass mb-4">
        <h3 style="margin:0 0 16px;font-size:1rem;font-weight:600;text-transform:capitalize">
            <i class="fas fa-sitemap me-2"></i><?= Html::encode($category) ?>
            <span class="ap-tag ms-2"><?= count($items) ?> fields</span>
        </h3>
        <div class="ap-table-wrap">
            <table class="ap-table">
                <thead>
                    <tr><th>Field</th><th>Faculty</th><th>Department</th><th>Slug</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $field): ?>
                        <tr>
                            <td><strong><?= Html::encode($field->name) ?></strong></td>
                            <td><?= Html::encode($field->faculty ?? '—') ?></td>
                            <td><?= Html::encode($field->department ?? '—') ?></td>
                            <td><code><?= Html::encode($field->slug) ?></code></td>
                            <td>
                                <?= Html::button('Edit', [
                                    'class' => 'ap-btn ap-btn-ghost ap-btn-sm',
                                    'data-ap-open-modal' => 'fieldForm',
                                    'data-prefill-id' => $field->id,
                                    'data-prefill-name' => $field->name,
                                    'data-prefill-slug' => $field->slug,
                                    'data-prefill-category' => $field->category,
                                    'data-prefill-faculty_id' => $field->faculty_id,
                                    'data-prefill-department' => $field->department,
                                    'data-prefill-is_active' => $field->is_active ? '1' : '0',
                                ]) ?>
                                <?= Html::button('Deactivate', [
                                    'class' => 'ap-btn ap-btn-ghost ap-btn-sm',
                                    'data-ap-delete' => Url::to(['site/delete-field']),
                                    'data-id' => $field->id,
                                    'data-confirm' => 'Deactivate this field?',
                                ]) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>
</div>

<div class="ap-modal-backdrop" data-ap-modal="facultyForm">
    <div class="ap-modal ap-glass">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 style="margin:0;font-size:1.1rem">Faculty</h2>
            <button type="button" class="ap-btn ap-btn-ghost ap-btn-sm" data-ap-close-modal>&times;</button>
        </div>
        <form data-ap-ajax-form="<?= Url::to(['site/save-faculty']) ?>">
            <input type="hidden" name="<?= $csrf ?>" value="<?= $token ?>">
            <input type="hidden" name="AcademicFaculty[id]" value="" data-ap-prefill-target="id">
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input class="form-control" name="AcademicFaculty[name]" required data-ap-prefill-target="name">
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="AcademicFaculty[description]" rows="2" data-ap-prefill-target="description"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="AcademicFaculty[is_active]" data-ap-prefill-target="is_active">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <button type="submit" class="ap-btn ap-btn-primary">Save faculty</button>
        </form>
    </div>
</div>

<div class="ap-modal-backdrop" data-ap-modal="fieldForm">
    <div class="ap-modal ap-glass">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 style="margin:0;font-size:1.1rem">Field of study</h2>
            <button type="button" class="ap-btn ap-btn-ghost ap-btn-sm" data-ap-close-modal>&times;</button>
        </div>
        <form data-ap-ajax-form="<?= Url::to(['site/save-field']) ?>">
            <input type="hidden" name="<?= $csrf ?>" value="<?= $token ?>">
            <input type="hidden" name="FieldOfStudy[id]" value="" data-ap-prefill-target="id">
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input class="form-control" name="FieldOfStudy[name]" required data-ap-prefill-target="name">
            </div>
            <div class="mb-3">
                <label class="form-label">Slug</label>
                <input class="form-control" name="FieldOfStudy[slug]" data-ap-prefill-target="slug">
            </div>
            <div class="mb-3">
                <label class="form-label">Category</label>
                <input class="form-control" name="FieldOfStudy[category]" required data-ap-prefill-target="category">
            </div>
            <div class="mb-3">
                <label class="form-label">Faculty</label>
                <select class="form-select" name="FieldOfStudy[faculty_id]" data-ap-prefill-target="faculty_id">
                    <option value="">—</option>
                    <?php foreach (AcademicFaculty::getDropdownOptions() as $id => $name): ?>
                        <option value="<?= (int) $id ?>"><?= Html::encode($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Department</label>
                <input class="form-control" name="FieldOfStudy[department]" data-ap-prefill-target="department">
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="FieldOfStudy[is_active]" data-ap-prefill-target="is_active">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <button type="submit" class="ap-btn ap-btn-primary">Save field</button>
        </form>
    </div>
</div>
