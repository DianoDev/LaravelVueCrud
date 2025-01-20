<template>
    <div id="staticBackdrop" class="modal fade" :class="{'modal-sm': (size === 'sm'), 'modal-md': (size === 'md'), 'modal-lg': (size === 'lg'), 'modal-xl': (size === 'xl')}" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content rounded-0 shadow-lg">
                <div class="modal-header d-flex justify-content-between">
                    <h5 class="modal-title fs-5" id="staticBackdropLabel">{{ title }}</h5>
                    <button type="button" class="btn btn-link text-white" @click="close" aria-label="Close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <component v-if="component" v-bind:is="component" v-bind="data" :id="id" :data="data" @close="close" />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {Modal} from 'bootstrap';
import {inject, ref, onMounted} from "vue";

export default {

    setup() {

        const events = inject('events');
        let modal = null;
        const component = ref(null);
        const id = ref(null);
        const data = ref(null);
        const title = ref(null);
        const size = ref('md');

        const init = () => {
            modal = new Modal(document.querySelector('#staticBackdrop'))
            document.getElementById('staticBackdrop').addEventListener('hidden.bs.modal', (evt) => {
                component.value = null;
                id.value = null;
                data.value = null;
                title.value = null;
            })
            events.on('popup', open);
            events.on('popup-close', close);
        }

        const open = (evt) => {
            id.value = evt.id ? evt.id : 'component-popup';
            component.value = evt.component;
            data.value = evt.data;
            title.value = evt.title ? evt.title : 'Sem título';
            size.value = evt.size || 'lg';
            modal.show();
        };

        const close = () => {
            component.value = null;
            id.value = null;
            data.value = null;
            title.value = null;
            modal.hide();
        };

        onMounted(init);

        return {
            modal,
            component,
            id,
            data,
            title,
            size,
            init,
            close
        }
    },
};
</script>

<style scoped>
.modal-body {
    min-height: 200px !important;
}

.modal {
    z-index: 1053;
}
</style>
