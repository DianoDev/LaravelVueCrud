<template>
    <thead v-if="ready" :id="`head_${table}`">
    <tr class="table-header">
        <th v-for="header in columns" :key="header.name" :class="header.headerClass"
            :style="{width: header.width || 'auto'}" nowrap>
            <div v-if="!header.checkbox" class="d-block">
                {{ header.title }}
                <span @click="sort(header)" class="ms-1"
                      :class="{'sortable': !!header.sort, 'text-red-500': (header.sort === sortColumn), 'text-gray-500': (header.sort !== sortColumn)}"
                      v-if="!!header.sort">
                        <i class="fa"
                           :class="{'fa-sort-asc': (header.sort === sortColumn && sortAsc), 'fa-sort-desc': (header.sort === sortColumn && !sortAsc), 'fa-sort': (header.sort !== sortColumn)}"></i>
                </span>
                <div class="ms-1 d-inline" v-if="header.filter">
                    <span @click="toggleFilter(header.name)" role="button" :class="{'text-red-500': hasFilter(header), 'text-gray-500': !hasFilter(header)}">
                        <i class="fa fa-filter"></i>
                    </span>
                    <div class="filter-container p-3 border" :id="`filter_column_${sanitize(header.name)}`">
                        <i class="fa fa-times-circle text-danger close-btn" @click="hideAllFilters()"></i>
                        <component :table="table" :is="`table-filter-${header.filter.type}`" :name="header.name" :filter="header.filter" @updated="handleUpdateFilter"/>
                    </div>
                </div>
            </div>
            <div v-if="header.checkbox" class="text-center">
                <table-checkbox @toggle="toggle" value="all"/>
            </div>
        </th>
    </tr>
    </thead>
</template>

<script>

import {ref, onMounted} from 'vue';
import { useTableFilters } from '../../table-filters';
import TableCheckbox from "./__table-checkbox.vue";
import TableFilterText from './filters/__table-filter-text.vue'
import TableFilterCPF from './filters/__table-filter-cpf.vue'
import TableFilterCheckbox from './filters/__table-filter-checkbox.vue'
import TableFilterSelect from './filters/__table-filter-select.vue'
import TableFilterDateRange from './filters/__table-filter-date-range.vue'
import TableFilterDate from './filters/__table-filter-date.vue'
import {storeToRefs} from "pinia";

export default {
    components: {
        TableCheckbox,
        TableFilterText,
        TableFilterCPF,
        TableFilterCheckbox,
        TableFilterSelect,
        TableFilterDateRange,
        TableFilterDate,
    },

    setup(props, {emit}) {
        const filter = useTableFilters();
        const { enabledFilters } = storeToRefs(filter);
        const { sanitize } = filter;
        const sortColumn = ref(null);
        const sortAsc = ref(null);
        const ready = ref(false);

        const init = () => {
            if (props.params) {
                let direction = props.params.sort_direction || 'asc';
                sortColumn.value = props.params.sort || null;
                sortAsc.value = direction === 'asc';
            }

            ready.value = true;
        }

        const sort = (header) => {
            if (!header.sort) return null;
            sortAsc.value = (sortColumn.value === header.sort) ? !sortAsc.value : true;
            sortColumn.value = header.sort;
            emit('sort', {
                column: sortColumn.value,
                direction: sortAsc.value ? 'asc' : 'desc'
            });
        }

        const toggle = (item) => {
            document.querySelector('body').click();
            emit('toggle-all', item);
        }

        const dataLoaded = (params) => {
            let direction = params.sort_direction || 'asc';
            sortColumn.value = params.sort || null;
            sortAsc.value = direction === 'asc';
        }

        const toggleFilter = (name) => {
            hideAllFilters(name);
            const el = document.querySelector(`#head_${props.table} #filter_column_${sanitize(name)}`);
            if(el) {
                el.style.display = el.style.display === 'block' ? 'none' : 'block';
                if(el.style.display === 'block') {
                    const input = document.querySelector(`#head_${props.table} #filter_column_${sanitize(name)} .auto-focus`);
                    if(input) input.focus();
                }
            }
        }

        const hideAllFilters = (except) => {
            const els = document.querySelectorAll('.filter-container');
            els.forEach(el => {
                if(!except || el.id !== `filter_column_${sanitize(except)}`) el.style.display = 'none';
            })
        }

        const handleUpdateFilter = () => {
            emit('filter', true);
            hideAllFilters();
        }

        const hasFilter = (header) => {
            const filterItem = `${props.table}_${header.filter.name ?? header.name}`;
            return (enabledFilters.value.indexOf(filterItem) >= 0);
        }

        onMounted(init);

        return {
            ready,
            sortColumn,
            sortAsc,
            toggle,
            sort,
            dataLoaded,
            toggleFilter,
            hideAllFilters,
            sanitize,
            handleUpdateFilter,
            hasFilter
        }
    },

    props: {
        table: { default: null },
        params: {default: null},
        columns: {type: Array, required: true},
        filters: {type: Array, default: []}
    }
};
</script>

<style scoped>

.sortable {
    cursor: pointer;
}

.table-action-active {
    opacity: 1;
}

.table-action-inactive {
    opacity: .2;
}

.filter-container {
    display: none;
    position: absolute;
    width: 250px;
    background-color: #ffff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    z-index: 4;

    .close-btn {
        font-size: 140%;
        position: absolute;
        top: -5px;
        right: -5px;
        cursor: pointer;
    }
}
</style>
