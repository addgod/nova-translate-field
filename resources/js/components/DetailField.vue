<template>
    <div>
        <component
            v-for="(field, index) in fields"
            :key="index"
            :is="'detail-' + field.component"
            :errors="errors"
            :resource-name="field.resourceName"
            :field="field"
        />
    </div>
</template>

<script>
export default {
    props: ['resource', 'resourceName', 'resourceId', 'field'],

    computed: {
        fields() {
            return this.field.fields[this.field.locale]
        }
    }
}
</script>
