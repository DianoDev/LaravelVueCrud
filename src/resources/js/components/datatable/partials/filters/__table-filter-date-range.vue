<template>
    <div v-if="ready">
        <div class="row mb-2">
            <div class="col">
                <date-range-picker
                    ref="picker"
                    :locale-data="{ firstDay: 1, format: 'yyyy-mm-dd HH:mm:ss' }"
                    :singleDatePicker=false
                    :showDropdowns=true
                    :autoApply=true
                    :ranges="ranges"
                    v-model="dateRange"
                    @select="handleSelect"
                    :date-range="dateRange"
                >

                    <template v-if="dateRange.startDate" v-slot:input="picker" style="min-width: 350px;">
                        {{ moment(picker.startDate).format('YYYY-MM-DD')  }} - {{ moment(picker.endDate).format('YYYY-MM-DD') }}
                    </template>
                </date-range-picker>
            </div>
        </div>

        <div class="row">
            <div class="col text-center d-grid">
                <button type="button" class="btn btn-sm small btn-primary" @click="apply">
                    Aplicar
                </button>
            </div>
            <div class="col text-center d-grid">
                <button type="button" class="btn btn-sm small btn-link" @click="reset">
                    Limpar
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import DateRangePicker from 'vue3-daterange-picker';
import { useTableFilters } from '../../../table-filters.js';
import {onMounted, ref} from "vue";
import moment from "moment/moment";

const ranges = ref({});
const dateRange = ref({});
const ready = ref(false);

const props = defineProps({
    table: { default: null },
    name: { default: null },
    filter: { type: Object }
});

const emit = defineEmits(['updated']);
const filterStore = useTableFilters();
const { setFilter } = filterStore;

const handleSelect = () => {
    setTimeout(apply, 100);
}

const apply = () => {
    if(!dateRange.value.startDate || !dateRange.value.endDate) {
        reset();
        return;
    }

    let startDate = moment(dateRange.value.startDate);
    let endDate = moment(dateRange.value.endDate);
    setFilter(props.table, props.filter.name ?? props.name, `${startDate.format('YYYY-MM-DD')},${endDate.format('YYYY-MM-DD')}`);
    emit('updated', true);
}

const reset = () => {
    dateRange.value = {
        startDate: null,
        endDate: null
    }
    setFilter(props.table, props.filter.name ?? props.name, null);
    emit('updated', true);
}

onMounted(() => {
    let today = new Date();
    today.setHours(0, 0, 0, 0);

    // last 7 days
    let d7 = new Date();
    d7.setDate(today.getDate() - 7)
    d7.setHours(0, 0, 0, 0);

    // last 15 days
    let d15 = new Date();
    d15.setDate(today.getDate() - 15)
    d15.setHours(0, 0, 0, 0);

    // last 30 days
    let d30 = new Date();
    d30.setDate(today.getDate() - 30)
    d30.setHours(0, 0, 0, 0);

    // last 60 days
    let d60 = new Date();
    d60.setDate(today.getDate() - 60)
    d60.setHours(0, 0, 0, 0);

    // last 90 days
    let d90 = new Date();
    d90.setDate(today.getDate() - 90)
    d90.setHours(0, 0, 0, 0);

    // last 120 days
    let d120 = new Date();
    d120.setDate(today.getDate() - 120)
    d120.setHours(0, 0, 0, 0);

    ranges.value = {
        'Last 7 days': [d7, today],
        'Last 15 days': [d15, today],
        'Last 30 days': [d30, today],
        'Last 60 days': [d60, today],
        'Last 90 days': [d90, today],
        'Last 120 days': [d120, today],
    };

    dateRange.value = {
        startDate: null,
        endDate: null
    }

    ready.value = true;
})
</script>

<style lang="scss">
.form-control {
    padding: 4px;
    font-size: 90%;
}

.vue-daterange-picker {
    width: 100% !important;
}

.daterangepicker .calendar-table th, .daterangepicker .calendar-table td {
    padding: 5px !important;
}
</style>
