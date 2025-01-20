<template>
    <div class="btn-group">
        <button :disabled="disabled || totalEventsTriggered < events.length" type="button" :class="`btn ${btnClass}`" @click="submit('form-submitted')">
            <i v-if="icon" class="fa" :class="{'fa-check': (totalEventsTriggered === events.length), 'fa-sync-alt fa-spin': (totalEventsTriggered < events.length)}"></i> {{ totalEventsTriggered < events.length ? 'Carregando...' : btnLabel }}
        </button>
        <button v-if="options.length > 0" type="button" class="btn btn-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="visually-hidden">Exibir/ocultar opções</span>
        </button>
        <ul class="dropdown-menu" v-if="options.length > 0">
            <li v-for="option in options">
                <a class="dropdown-item" href="#" @click="submitAction(option)">
                    <i :class="`fa ${option.icon}`"></i>
                    {{option.label}}
                </a>
            </li>
        </ul>
    </div>
</template>

<script>
import { ref, onMounted, inject } from 'vue';

export default {
    setup(props, { emit }) {
        const $events = inject('events');
        const error = ref(false);
        const btnLabel = ref('Save');
        const events = ref([]);
        const totalEventsTriggered = ref(0);

        const loading = (toggle) => {
            $events.emit('loading', toggle);
        }

        const submitAction = async (option) => {
            await submit(option.event);
        }

        const submit = async (formEvent = 'form-submitted') => {
            loading(true);
            error.value = false;
            try {
                $events.emit('form-error', []);
                let form = document.querySelector(props.form);
                document.querySelectorAll('.form-error').forEach((el) => {
                    el.classList.remove('form-error');
                });
                let method = form.dataset.method.toLowerCase();
                let url = form.action;

                if(!url) {
                    url = window.location.href;
                }
                let data = null;
                if(props.formData) {
                    data = props.formData;
                } else {
                    data = new FormData(form);
                    if(method === 'put') {
                        data.set('_method', 'PUT');
                        method = 'post';
                    }
                }

                const result = await axios.request({url, method, data: data});
                processResult(result);
                $events.emit(formEvent, true);
            } catch(err) {
                error.value = true;
                loading(false);
                emit('error');
                $events.emit('form-submitted', false);
                if (err.response && err.response.status === 500) {
                    let arr = [];
                    arr.push(err.response.data.message);
                    let error = {
                        message: arr
                    };
                    $events.emit('form-error', error);
                } else {
                    if(err?.response?.data) {
                        $events.emit('form-error', err.response.data.errors);
                    } else {
                        console.log(err);
                    }
                }
            }
        }

        const processResult = (result) => {
            if(result.data.error) {
                loading(false);
                let arr = [];
                arr.push(result.data.message);
                let error = {
                    message: arr
                };
                $events.emit('form-error', error);
                return true;
            }

            if(props.event) {
                $events.emit(props.event, result.data);
            }

            if(result.data.redirect) {
                window.location = result.data.redirect;
                return true;
            } else {
                if(result.data.notification) {
                    $events.emit('notification', result.data.notification);
                }
                loading(false);
            }
        }

        onMounted(() => {
            $events.on('submit-rest', submit);

            if(props.label) {
                btnLabel.value = props.label
            }

            // must wait for events?
            if(props.wait) {
                const waitEvents = props.wait.split(",");
                waitEvents.map(item => {
                    events.value.push(item.trimStart().trimEnd().toLowerCase());
                });

                events.value.map(event => {
                    $events.on(event, () => {
                        totalEventsTriggered.value++;
                    })
                })
            }
        });

        return {
            btnLabel,
            events,
            totalEventsTriggered,
            submitAction,
            submit,
        }
    },

    props: {
        options: { default: [] },
        form: { default: '#frm' },
        event: { default: null },
        label: { default: 'Save' },
        disabled: { default: false },
        lg: { default: false },
        wait: { default: null },
        formData: { default: null },
        icon: { type: Boolean, default: true },
        btnClass: { default:  'btn-primary text-white' }
    }
}
</script>
