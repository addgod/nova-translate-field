<?php

namespace Addgod\NovaTranslateField;

use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;

class Translate extends Field
{

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'nova-translate-field';

    /** @var string[] */
    public static $defaultLocales = [];

    /** @var string */
    public static $defaultLocale;

    /** @var string[] */
    protected $locales = [];

    /** @var \Laravel\Nova\Fields\Field[] */
    protected $originalFields;

    /** @var \Laravel\Nova\Fields\Field[] */
    protected $fields;

    /**
     * The field's assigned panel.
     *
     * @var string
     */
    public $panel;

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
            throw new \Exception('Default locales not set.');
        }

        $this->locales = static::$defaultLocales;

        $this->originalFields = $fields;

        $this->fields = collect($this->locales)->mapWithKeys(function ($locale) use ($fields) {
            return [
                $locale => collect($fields)->map(function ($field) use ($locale) {
                    $translatedField = clone $field;

                    $originalAttribute = $translatedField->attribute;

                    $translatedField->attribute = 'translations';

                    $translatedField
                        ->resolveUsing(function ($value, $model) use ($translatedField, $locale, $originalAttribute) {
                            $translatedField->attribute = 'translations_' . $originalAttribute . '_' . $locale;
                            $translatedField->panel = $this->panel;

                            return $model->translations[$originalAttribute][$locale] ?? '';
                        });

                    $translatedField->fillUsing(function ($request, $model, $attribute, $requestAttribute) {
                        $requestAttributeParts = explode('_', $requestAttribute);
                        $locale = array_last($requestAttributeParts);
                        array_shift($requestAttributeParts);
                        array_pop($requestAttributeParts);
                        $key = implode('_', $requestAttributeParts);
                        $model->setTranslation($key, $locale, $request->get($requestAttribute));
                    });

                    return $translatedField;
                }),
            ];
        });
    }

    public function resolve($resource, $attribute = null)
    {
        return collect($this->fields)->map(function ($locale) use ($resource, $attribute) {
            return $locale->map(function ($field) use ($resource, $attribute) {
                return $field->resolve($resource, $attribute);
            });
        });
    }

    public function fill(NovaRequest $request, $model)
    {
        return collect($this->fields)->map(function ($locale) use ($request, $model) {
            return $locale->map(function ($field) use ($request, $model) {
                return $field->fill($request, $model);
            });
        });
    }

    public static function locales(array $locales, string $default)
    {
        static::$defaultLocales = $locales;
        static::$defaultLocale = $default;
    }

    /**
     * Get the validation rules for this field.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest $request
     *
     * @return array
     */
    public function getRules(NovaRequest $request)
    {
        return collect($this->fields)->flatten()->mapWithKeys(function ($field) use ($request) {
            return $field->getRules($request);
        })->toArray();
    }

    /**
     * Get the creation rules for this field.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest $request
     *
     * @return array
     */
    public function getCreationRules(NovaRequest $request)
    {
        return collect($this->fields)->flatten()->mapWithKeys(function ($field) use ($request) {
            return $field->getCreationRules($request);
        })->toArray();
    }

    /**
     * Get the update rules for this field.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest $request
     *
     * @return array
     */
    public function getUpdateRules(NovaRequest $request)
    {
        return collect($this->fields)->flatten()->mapWithKeys(function ($field) use ($request) {
            return $field->getUpdateRules($request);
        })->toArray();
    }

    /**
     * Get additional meta information to merge with the field payload.
     *
     * @return array
     */
    public function meta()
    {
        return array_merge([
            'fields'  => $this->fields,
            'locales' => $this->locales,
            'locale'  => static::$defaultLocale,
        ], $this->meta);
    }
}
