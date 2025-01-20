<template>
    <label class="container">
        <input class="table-checkbox" type="checkbox" :id="`table-checkbox-${value}`" :checked="checked" @change="toggle">
        <div class="checkmark"></div>
    </label>
</template>

<script>
import {ref, onMounted, inject} from 'vue';

export default {
    setup(props, {emit}) {
        const events = inject('events');
        const checked = ref(false);

        const onToggleAll = (check) => {
            checked.value = check
        }

        const toggle = (evt) => {
            document.querySelector('body').click();
            emit('toggle', {
                checked: evt.target.checked,
                value: props.value
            });
        }

        const onCheckboxReset = () => {
            onToggleAll(false);
            emit('toggle', {
                checked: false,
                value: props.value
            });
        }

        events.on('table-toggle-all', onToggleAll);
        events.on('table-checkbox-reset', onCheckboxReset);

        return {
            checked,
            toggle
        }
    },

    props: {
        value: { type: String, default: null }
    }
}
</script>

<style lang="scss" scoped>
.container input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}

.container {
    display: block;
    position: relative;
    cursor: pointer;
    font-size: 1.5rem;
    user-select: none;
}

/* Create a custom checkbox */
.checkmark {
    --clr: #507497;
    position: relative;
    top: 0;
    left: 0;
    height: 1em;
    width: 1em;
    border: solid 1px #888;
    background-color: #ffffff;
    border-radius: .3rem;
    transition: 100ms;
}

/* When the checkbox is checked, add a blue background */
.container input:checked ~ .checkmark {
    background-color: var(--clr);
    border: 0;
    border-radius: .3rem;
    animation: pulse 100ms ease-in-out;
}

/* Create the checkmark/indicator (hidden when not checked) */
.checkmark:after {
    content: "";
    position: absolute;
    display: none;
}

/* Show the checkmark when checked */
.container input:checked ~ .checkmark:after {
    display: block;
}

/* Style the checkmark/indicator */
.container .checkmark:after {
    left: 0.40em;
    top: 0.20em;
    width: 0.25em;
    height: 0.5em;
    border: solid #ffffff;
    border-width: 0 0.15em 0.15em 0;
    transform: rotate(45deg);
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 #0B6E4F90;
        rotate: 20deg;
    }

    50% {
        rotate: -20deg;
    }

    75% {
        box-shadow: 0 0 0 10px #0B6E4F60;
    }

    100% {
        box-shadow: 0 0 0 13px #0B6E4F30;
        rotate: 0;
    }
}
</style>
