<template>
    <div id="error-container" v-if="errors.length > 0 && showErrors" class="alert alert-danger rounded-0" role="alert">
        <p v-if="showCaption"><strong>Erro ao validar seu formulário:</strong></p>
        <ul v-if="showErrors" style="margin-bottom: 0;">
            <li v-for="error in errors" v-html="error"></li>
        </ul>
    </div>
</template>

<script>
import { ref, onMounted, inject } from 'vue';
export default {

    setup(props, { emit }) {
        const events = inject('events');
        const errors = ref([]);

        onMounted(() => {
            events.on('form-error', (data) => {
                document.querySelectorAll('.form-error').forEach((el) => {
                    el?.classList?.remove('form-error');
                });
                errors.value = [];
                for (let prop in data) {
                    //document.querySelector(`[name=${prop}], [data-name=${prop}]`)?.classList?.add('form-error');
                    let input = document.querySelector(`[name=${prop}], [data-name=${prop}]`);
                    if(input) {
                        const errorClass = input.getAttribute('data-error-class');
                        if(errorClass) {
                            let cls = `.${errorClass}`;
                            input.parentNode.querySelector(cls)?.classList?.add('form-error');
                        } else {
                            input.classList.add('form-error');
                        }
                    }
                    if (Object.prototype.hasOwnProperty.call(data, prop)) {
                        errors.value.push(data[prop][0]);
                    }
                }
                setTimeout(() => {
                    const container = document.querySelector('#error-container');
                    if(container) {
                        container.scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                }, 100);
            });
        });

        return {
            errors,
        }
    },

    props: {
        showCaption: { type: Boolean, default: true },
        showErrors: { type: Boolean, default: true },
    }
}
</script>
