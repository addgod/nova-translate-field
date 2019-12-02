<?php

namespace Addgod\NovaTranslateField;

use Eminiarts\Tabs\Tabs;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\MergeValue;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Controllers\ResourceIndexController;
use Laravel\Nova\Http\Controllers\ResourceShowController;
use Laravel\Nova\Metable;

class Translate extends MergeValue
{
    use Metable;

    /** @var string[] */
    protected static $defaultLocales = [];

    /** @var string[] */
    protected $locales = [];

    /** @var \Laravel\Nova\Fields\Field[] */
    protected $originalFields;

    /**
     * The field's assigned panel.
     *
     * @var string
     */
    public $panel;

    /**
     * The panel's component.
     *
     * @var string
     */
    public $component = 'nova-translate-field';

    public static function make(array $fields): self
    {
        return new static($fields);
    }

    /**
     * Translate constructor.
     *
     * @param array $fields
     *
     * @throws \Exception
     */
    public function __construct(array $fields = [])
    {
        if (!count(static::$defaultLocales)) {
            throw new Exception('No default locale set.');
        }

        $this->locales = static::$defaultLocales;

        $this->originalFields = $fields;

        $this->createTranslatableFields();
    }

    public static function defaultLocales(array $locales)
    {
        static::$defaultLocales = $locales;
    }

    public function locales(array $locales)
    {
        $this->locales = $locales;

        $this->createTranslatableFields();

        return $this;
    }

    protected function createTranslatableFields()
    {
        if ($this->onIndexOrDetailPage()) {
            $this->data = $this->originalFields;

            return;
        }

        $this->data = [];

        $this->data[] = new Tabs('Languages', collect($this->locales)
            ->mapWithKeys(function (string $locale) {
                return [
                    Str::upper($locale) => collect($this->originalFields)->map(function (Field $field) use ($locale) {
                        return $this->createTranslatedField($field, $locale);
                    }),
                ];
            })
            ->toArray()
        );

    }

    protected function createTranslatedField(Field $originalField, string $locale): Field
    {
        $translatedField = clone $originalField;

        $originalAttribute = $translatedField->attribute;

        $translatedField->attribute = 'translations';

        $translatedField
            ->resolveUsing(function ($value, Model $model) use ($translatedField, $locale, $originalAttribute) {
                $translatedField->attribute = $originalAttribute . '->' . $locale;
                $translatedField->panel = $this->panel;

                return $model->translations[$originalAttribute][$locale] ?? '';
            });

        $translatedField->fillUsing(function (
            $request,
            $model,
            $attribute,
            $requestAttribute
        ) use ($locale, $originalAttribute) {
            $model->setTranslation($originalAttribute, $locale, $request->get($requestAttribute));
        });

        return $translatedField;
    }

    protected function onIndexOrDetailPage(): bool
    {
        if (!request()->route()) {
            return false;
        }
        $currentController = Str::before(request()->route()->getAction()['controller'], '@');

        return $currentController === ResourceIndexController::class ||
            $currentController === ResourceShowController::class;
    }
}
