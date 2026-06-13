<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $category
 * @property int|null $faculty_id
 * @property string|null $faculty
 * @property string|null $department
 * @property string|null $aliases
 * @property bool $is_active
 * @property int $created_at
 */
class FieldOfStudy extends ActiveRecord
{
    /** @var self[]|null */
    private static $activeRecordsCache;

    public static function tableName()
    {
        return '{{%field_of_study}}';
    }

    public function rules()
    {
        return [
            [['slug', 'name', 'category', 'created_at'], 'required'],
            [['aliases'], 'string'],
            [['is_active'], 'boolean'],
            [['faculty_id', 'created_at'], 'integer'],
            [['slug'], 'string', 'max' => 120],
            [['name', 'faculty', 'department'], 'string', 'max' => 255],
            [['category'], 'string', 'max' => 80],
            [['slug'], 'unique'],
            [['faculty_id'], 'exist', 'skipOnError' => true, 'targetClass' => AcademicFaculty::class, 'targetAttribute' => ['faculty_id' => 'id']],
        ];
    }

    public function getAcademicFaculty()
    {
        return $this->hasOne(AcademicFaculty::class, ['id' => 'faculty_id']);
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }
        if ($this->faculty_id && !$this->faculty) {
            $faculty = AcademicFaculty::findOne($this->faculty_id);
            if ($faculty) {
                $this->faculty = $faculty->name;
            }
        }
        return true;
    }

    /**
     * Resolve free-text field name to taxonomy record.
     */
    public static function resolve(?string $text): ?self
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        $normalized = strtolower(trim($text));
        if (static::$activeRecordsCache === null) {
            static::$activeRecordsCache = static::find()->where(['is_active' => true])->all();
        }
        $records = static::$activeRecordsCache;

        foreach ($records as $record) {
            if (strtolower($record->name) === $normalized || $record->slug === $normalized) {
                return $record;
            }
        }

        foreach ($records as $record) {
            if (strpos($normalized, strtolower($record->name)) !== false
                || strpos(strtolower($record->name), $normalized) !== false) {
                return $record;
            }
            foreach ($record->getAliasList() as $alias) {
                if ($alias !== '' && (strpos($normalized, $alias) !== false || strpos($alias, $normalized) !== false)) {
                    return $record;
                }
            }
        }

        return null;
    }

    public function getAliasList(): array
    {
        if (empty($this->aliases)) {
            return [];
        }
        return array_filter(array_map(static function ($a) {
            return strtolower(trim($a));
        }, explode(',', $this->aliases)));
    }

    public static function getCategoryOptions(): array
    {
        return static::find()
            ->select('category')
            ->distinct()
            ->orderBy(['category' => SORT_ASC])
            ->column();
    }

    public static function getDropdownOptions(): array
    {
        $options = [];
        foreach (static::find()->where(['is_active' => true])->orderBy(['category' => SORT_ASC, 'name' => SORT_ASC])->all() as $field) {
            $options[$field->id] = $field->name . ' (' . ucfirst($field->category) . ')';
        }
        return $options;
    }

    /**
     * Active fields grouped by faculty (falls back to category label).
     *
     * @return array<string, array<int, array{id:int,name:string,category:string,faculty:string}>>
     */
    public static function getGroupedForSelector(): array
    {
        $groups = [];
        $fields = static::find()
            ->where(['is_active' => true])
            ->with(['academicFaculty'])
            ->orderBy(['faculty' => SORT_ASC, 'category' => SORT_ASC, 'name' => SORT_ASC])
            ->all();

        foreach ($fields as $field) {
            $groupLabel = trim((string) ($field->faculty ?: ''));
            if ($groupLabel === '') {
                $groupLabel = ucfirst((string) $field->category);
            }
            if (!isset($groups[$groupLabel])) {
                $groups[$groupLabel] = [];
            }
            $groups[$groupLabel][] = [
                'id' => (int) $field->id,
                'name' => $field->name,
                'category' => $field->category,
                'faculty' => $groupLabel,
            ];
        }

        return $groups;
    }
}
