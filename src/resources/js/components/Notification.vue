<template>
</template>

<script>
import { ref, onMounted, inject } from 'vue';
import { useToast } from "vue-toastification";
import "vue-toastification/dist/index.css";
export default {

    setup(props, { emit }) {
        const events = inject('events');
        const toast = useToast();

        const getNotificationType = (type) => {
            if(type === 'success') {
                return toast.success;
            } else if(type === 'error') {
                return toast.error;
            } else if (type === 'info') {
                return toast.info;
            } else if (type === 'warning') {
                return toast.warning;
            } else {
                return toast;
            }
        }

        onMounted(() => {
            if(props.show) {
                setTimeout(() => {
                    const notification = getNotificationType(props.type);
                    notification(props.message, {
                        timeout: props.duration || 4000,
                        position: 'top-right'
                    });
                }, 500);

            }

            events.on('notification', (data) => {
                const notification = getNotificationType(data.type);
                notification(data.message, {
                    timeout: data.duration || 4000,
                    position: data.position || 'top-right'
                });
            });
        });
    },

    props: {
        message: { default: null },
        type: { default: null },
        duration: { default: null },
        show: { default: null }
    }
}
</script>
