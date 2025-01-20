<template>
    <div :id="`${id}`" class="modal fade" tabindex="-1" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog" v-bind:class="{'modal-dialog-centered': center}">
            <div class="modal-content shadow rounded-0">
                <div class="modal-header d-flex justify-content-between">
                    <h5 class="modal-title">{{confirmation.title}}</h5>
                    <button type="button" class="btn btn-link text-white" @click="cancel" aria-label="Close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <p v-html="confirmation.message"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" id="confirmation-cancel-button" class="btn btn-secondary me-2" data-dismiss="modal" @click="cancel">Cancel</button>
                    <button type="button" :class="'text-white btn btn-success'" @click="confirm">Continue</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, inject, onMounted } from 'vue';
import {Modal} from "bootstrap";

export default {
    setup(props, { emit }) {
        const show = ref(false);
        const events = inject('events');
        const center = ref(true);
        const confirmation = ref({});
        let confirmationModal = null;

        const confirm = () => {
            if(typeof confirmation.value.event === 'function') {
                confirmation.value.event();
            } else {
                events.emit(confirmation.value.event, confirmation.value.data);
            }
            if(show.value) closeConfirmation();
            emit('close');
        }

        const closeConfirmation = () => {
            confirmationModal.hide();
            confirmation.value = {
                title: props.title,
                message: props.message,
                data: props.data,
                event: props.event,
            };
            show.value = false;
        }

        const cancel = () => {
            if(show.value) closeConfirmation();
            emit('close');
        }

        onMounted(() => {
            confirmation.value = {
                title: props.title,
                message: props.message,
                data: props.data,
                event: props.event,
            };

            if(props.top) {
                center.value = false;
            }

            events.on('confirmation', (data) => {
                if (!confirmationModal) {
                    confirmationModal = new Modal(document.getElementById(`${props.id}`), {
                        backdrop: 'static'
                    });
                }
                confirmation.value = data;
                confirmationModal.show();
                show.value = true;
            });
        });

        return {
            center,
            confirmation,
            cancel,
            confirm,
        }
    },

    props: {
        id: { default: 'global-confirmation-popup'},
        data: { default: null },
        message: { default: 'Você tem certeza?' },
        title: { default: 'Confirmação' },
        event: { default: null },
        top: { default: false },
    }
}
</script>

<style scoped>
.modal {
    z-index: 999999;
}
</style>
