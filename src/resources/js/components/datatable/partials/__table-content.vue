<template>
    <tbody>
        <tr v-for="row in data" :key="row.rn" :class="{'clickable': enableRowClick}" @click.stop="handleClick(row)">
            <td v-for="column in tableColumns" :class="getClasses(column)" :nowrap="column.nowrap">
                <div v-if="!column.checkbox">
                    <span v-if="!column.component" v-html="printItem(row, column)"></span>
                    <component v-if="column.component && updated" :is="column.component" :readonly="column.readonly || false" :data="getValue(row, column)" :reference="row"></component>
                </div>
                <div v-if="column.checkbox && updated" class="text-center">
                    <table-checkbox :value="getValue(row, column)" @toggle="toggle" />
                </div>
            </td>
        </tr>
    </tbody>
</template>

<script>
import moment from 'moment';
import {ref, inject, onMounted, watchEffect} from 'vue';
import TableCheckbox from './__table-checkbox.vue';

export default {
    components: {
        TableCheckbox
    },

    setup(props, { emit }) {
        const events = inject('events');
        const tableColumns = ref(props.columns);
        const updated = ref(true);
        const ready = ref(false);

        watchEffect(() => {
            let total = props.data.length;
            updated.value = false;
            setTimeout(() => {
                emit('loaded', true);
                updated.value = true;
            }, 50);
        });

        const init = () => {}

        const getClasses = (column) => {
            let nowrap = column.nowrap ? ' nowrap': '';
            let contentClass = column.contentClass ?? '';
            return (contentClass + nowrap);
        }

        const printItem = (row, column) => {
            if(column.template) {
                const value = getValue(row, column);
                if(!value) return '';
                switch(column.template) {
                    case 'ago':
                        return formatDateAgo(value);
                    case 'date':
                        return formatDate(value, false);
                    case 'datetime':
                        return formatDate(value, true);
                    case 'yesno':
                        return yesno(value);
                    case 'active':
                        return active(value);
                    default:
                        return value;
                }
            } else {
                if (column.formatter && typeof column.formatter === 'function') {
                    return column.formatter(getValue(row, column), row);
                } else {
                    return `${getValue(row, column)}`;
                }
            }
        }

        const getValue = (row, column) => {
            if(column.name.indexOf('.') !== -1) {
                const [ prop, value ] = column.name.split('.');
                if(row[prop] && row[prop][value]) {
                    return row[prop][value];
                } else {
                    return '';
                }

            } else {
                return row[column.name] || '';
            }
        }

        const formatDate = (date, showTime) => {
            if(date.indexOf('/') >= 0) return date;
            if(showTime) {
                return moment(date).format('DD/MM/YYYY hh:mm');
            } else {
                return moment(date).format('DD/MM/YYYY');
            }
        }

        const formatDateAgo = (date) => {
            return moment(date).fromNow();
        }

        const yesno = (value) => {
            if(value === 'S' || value === '1' || parseInt(value) === 1) {
                return '<span class="badge bg-green-500 text-white">SIM</span>';
            } else {
                return '<span class="badge bg-gray-500 text-white">NÃO</span>';
            }
        }

        const active = (value) => {
            if(value === 'S' || value === '1' || parseInt(value) === 1) {
                return '<span class="text-green"><i class="fa fa-check-circle"></i></span>';
            } else {
                return '<span class="text-gray"><i class="fa fa-circle-xmark"></i></span>';
            }
        }

        const toggle = (item) => {
            emit('toggle-item', item);
        }

        const handleClick = (item) => {
            emit('click', item);
        }

        onMounted(init);

        return {
            tableColumns,
            ready,
            updated,
            getValue,
            printItem,
            getClasses,
            toggle,
            handleClick
        }
    },

    props: {
        loading: { type: Boolean, default: false },
        columns: { type: Array, required: true,  default: [] },
        data: {type: Array, required: false, default: [] },
        enableRowClick: { type: Boolean, default: false }
    }
};
</script>

<style>
    .action {
        margin-right: 10px;
    }
    .nowrap {
        text-wrap: nowrap;
    }

    .clickable {
        cursor: pointer !important;
    }

</style>
