<template>SubmitRest
    <button :class="`btn btn-${type}`" @click="open">
        <slot></slot>
    </button>
</template>

<script>
import {ref, onMounted, inject} from 'vue';

export default {
    setup(props, {emit}) {
        const events = inject('events');

        const open = () => {
            let data = typeof props.data === 'string' ? JSON.parse(props.data) : props.data;
            events.emit('popup', {
                title: props.title,
                component: props.component,
                data: data,
                size: props.size,
                id: props.id
            });
        }

        //onMounted(open);

        return {
            open
        }
    },

    props: {
        type: { type: String, default: 'primary' },
        title: { type: String, default: 'Título' },
        component: { type: String, default: null },
        data: { default: null },
        size: { default: 'lg' },
        id: { default: null },
    }
}
</script>

<style lang="scss" scoped>

</style>
