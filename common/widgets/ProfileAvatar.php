<?php

namespace common\widgets;

use common\models\Organization;
use common\models\Student;
use common\services\ProfileImageService;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * Renders organization logo or student profile photo with professional fallback.
 */
class ProfileAvatar extends Widget
{
    /** @var 'organization'|'student' */
    public string $type = 'student';

    public ?Organization $organization = null;
    public ?Student $student = null;
    public ?int $organizationId = null;
    public ?int $studentId = null;

    /** xs|sm|md|lg|xl */
    public string $size = 'md';

    public ?string $name = null;
    public string $cssClass = '';
    public bool $lazy = true;
    public ?string $alt = null;

    /** When true, avatar fills its parent slot (no fixed inline dimensions). */
    public bool $fillSlot = false;

    public function run(): string
    {
        $service = new ProfileImageService();
        $px = ProfileImageService::SIZES[$this->size] ?? ProfileImageService::SIZES['md'];

        if ($this->type === 'organization') {
            $org = $this->organization ?? ($this->organizationId ? Organization::findOne($this->organizationId) : null);
            $url = $service->organizationLogoUrl($org, $this->size);
            $initials = $service->organizationInitials($org);
            $placeholderIcon = 'fa-building';
            $placeholderClass = 'ft-avatar--org';
            $alt = $this->alt ?? ($org->name ?? 'Organization');
        } else {
            $student = $this->student ?? ($this->studentId ? Student::findOne($this->studentId) : null);
            $url = $service->studentPhotoUrl($student, $this->size);
            $initials = $service->studentInitials($student);
            $placeholderIcon = 'fa-user';
            $placeholderClass = 'ft-avatar--student';
            $alt = $this->alt ?? ($student && $student->user ? ($student->user->username ?? 'Student') : 'Student');
        }

        if ($this->name !== null && $this->name !== '') {
            $initials = (new ProfileImageService())->initialsFromName($this->name, $initials);
        }

        $classes = trim(
            'ft-avatar ft-avatar--' . Html::encode($this->size) . ' '
            . $placeholderClass . ' '
            . ($this->fillSlot ? 'ft-avatar--fill ' : '')
            . $this->cssClass
        );
        $style = $this->fillSlot ? null : ('width:' . $px . 'px;height:' . $px . 'px;');
        $tagOptions = static function (array $extra) use ($style): array {
            if ($style !== null) {
                $extra['style'] = $style;
            }

            return $extra;
        };

        $fallback = Html::tag('span',
            Html::tag('i', '', ['class' => 'fas ' . $placeholderIcon, 'aria-hidden' => 'true'])
            . Html::tag('span', Html::encode($initials), ['class' => 'ft-avatar__initials', 'aria-hidden' => 'true']),
            ['class' => 'ft-avatar__fallback', 'aria-hidden' => 'true']
        );

        if ($url) {
            $imgClass = 'ft-avatar__img';
            if ($this->type === 'organization') {
                $imgClass .= ' ft-avatar__img--contain';
            }

            $imgAttrs = [
                'class' => $imgClass,
                'src' => $url,
                'alt' => $alt,
                'width' => $px,
                'height' => $px,
                'loading' => $this->lazy ? 'lazy' : 'eager',
                'decoding' => 'async',
                'onerror' => 'var root=this.closest(".ft-avatar");if(!root||root.classList.contains("ft-avatar--broken"))return;'
                    . 'this.remove();root.classList.remove("ft-avatar--has-image");'
                    . 'root.classList.add("ft-avatar--broken","ft-avatar--placeholder");'
                    . 'root.insertAdjacentHTML("beforeend",' . json_encode($fallback, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ');',
            ];

            return Html::tag('span', Html::img($url, $imgAttrs), $tagOptions([
                'class' => $classes . ' ft-avatar--has-image',
                'role' => 'img',
                'aria-label' => $alt,
            ]));
        }

        return Html::tag('span', $fallback, $tagOptions([
            'class' => $classes . ' ft-avatar--placeholder',
            'role' => 'img',
            'aria-label' => $alt,
            'title' => $alt,
        ]));
    }
}
