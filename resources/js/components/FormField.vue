<template>
    <div class="m-2">
        <ul class="list-reset flex border-b border-60">
            <li v-for="l in field.locales" class="mr-1" v-bind:class="{ '-mb-px': l === locale}">
                <button type="button" style="outline: 0!important" class="bg-white inline-block py-2 px-4" v-bind:class="tabClass(l)" @click="setLocale(l)">{{ l }}</button>
            </li>
        </ul>
        <div class="border-l border-r border-b border-60" v-for="(fields, l) in field.fields" v-show="l === locale">
            <component
                v-for="(field, index) in fields"
                :key="index"
                :is="'form-' + field.component"
                :errors="formattedErrors"
                :resource-name="resourceName"
                :field="field"
            />
        </div>
    </div>
</template>

<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova'

export default {
    mixins: [FormField, HandlesValidationErrors],

    props: ['resourceName', 'resourceId', 'field'],

    data() {
        return {
            locale: '',
            locales: {}
        };
    },

    mounted() {
        this.locale = this.field.locale;
        this.resetLocalErrors();
    },

    watch: {
        errors(errors) {
            this.resetLocalErrors();
            _.each(errors.errors, (error, key) => {
                const [attribute, locale] = key.split('->');
                this.locales[locale.substring(0,2)] = true;
            });
        }
    },

    methods: {
        resetLocalErrors() {
            this.locales = _.transform(this.field.locales, (result, value, key) => {
                result[value] = false;
            });
        },

        fill(formData) {
            _.each(this.field.fields, fields => _.each(fields, field => field.fill(formData)))
        },

        setLocale(locale) {
            this.locale = locale;
        },

        tabClass(locale) {
            return {
                'border-l border-t border-r rounded-t border-60 text-blue-dark font-semibold' : this.locale === locale,
                'text-blue hover:text-blue-darker font-semibold' : this.locale !== locale,
                'text-danger' : this.locales[locale],
            }
        }
    },

    computed: {
        formattedErrors() {
            if (Object.keys(this.errors.errors).length === 0) {
                return this.errors;
            }
            const errors = _.clone(this.errors);
            errors.errors = _.transform(this.errors.errors, (result, messages, key) => {
                const [attribute, locale] = key.split('->');
                result[key] = _.map(messages, message => message.replace(key, attribute));
            });

            return errors;
        }
    }
}
</script>
