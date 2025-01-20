import {defineStore} from "pinia";
import {ref} from "vue";

export const useTableFilters = defineStore('filters', () => {
    const filterData = {};
    const enabledFilters = ref([]);

    const setFilter = (grid, column, value) => {
        if(!filterData[sanitize(grid)]) filterData[sanitize(grid)] = {};
        filterData[sanitize(grid)][sanitize(column)] = value;

        const filterItem = `${grid}_${column}`;
        if(!value) {
            enabledFilters.value = enabledFilters.value.filter(item => item !== filterItem);
        } else {
            if(enabledFilters.value.indexOf(filterItem) === -1) {
                enabledFilters.value.push(filterItem);
            }
        }
    }

    const getFilters = (grid) => {
        return filterData[sanitize(grid)] ?? null;
    }

    const getFilter = (grid, column) => {
        return filterData[sanitize(grid)][sanitize(column)] ?? null;
    }

    const sanitize = (name) => {
        return name.replace(/[.-]/g, '_');
    }

    return {
        enabledFilters,
        setFilter,
        getFilter,
        getFilters,
        sanitize,
    }
});
