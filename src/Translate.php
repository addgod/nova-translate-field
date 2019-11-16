<?php

namespace Addgod\NovaTranslateField;

use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;
use stdClass;

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

        $this->fields = collect($this->locales)->mapWithKeys(function ($locale) use ($fields) {
            return [
                $locale => collect($fields)->map(function ($field) use ($locale) {
                    $translatedField = clone $field;

                    $translatedField->attribute = $translatedField->attribute . '->' . $locale;

                    return $translatedField;
                }),
            ];
        });
    }

    /**
     * Determine if the field is required.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     *
     * @return bool
     */
    public function isRequired(NovaRequest $request)
    {
        return false;
    }

    /**
     * Resolve the field's value.
     *
     * @param mixed       $resource
     * @param string|null $attribute
     *
     * @return void
     */
    public function resolve($resource, $attribute = null)
    {
        collect($this->fields)->map(function ($fields, $locale) use ($resource, $attribute) {
            $fields->map(function ($field) use ($locale, $resource, $attribute) {
                $field->resolve($resource->translations);
            });
        });
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @param object                                  $model
     *
     * @return mixed
     */
    public function fill(NovaRequest $request, $model)
    {
        collect($this->fields)->map(function ($locale) use ($request, $model) {
            $locale->map(function ($field) use ($request, $model) {
                list ($attribute, $locale) = explode('->', $field->attribute);
                $object = new stdClass();
                $field->fillInto($request, $object, $attribute, $field->attribute);
                $model->setTranslation($attribute, $locale, $object->{$attribute});
            });
        });
    }

    /**
     * Set the locales.
     *
     * @param array  $locales
     * @param string $default
     */
    public static function locales(array $locales, string $default)
    {
        static::$defaultLocales = $locales;
        static::$defaultLocale = $default;
    }

    /**
     * Get the validation rules for this field.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
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
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
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
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
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
