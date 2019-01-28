Nova.booting((Vue, router, store) => {
    Vue.component('index-nova-translate-field', require('./components/IndexField'))
    Vue.component('detail-nova-translate-field', require('./components/DetailField'))
    Vue.component('form-nova-translate-field', require('./components/FormField'))
})
