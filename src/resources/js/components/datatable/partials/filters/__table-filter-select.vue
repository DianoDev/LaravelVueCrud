<template>
    <div>
        <div class="mb-2 options-container">
            <select class="form-select" v-model="selectedItem" @change="apply">
                <option value="">TODOS</option>
                <option v-for="option of filter.options" :value="filter.id ? option[filter.id] : option.id">
                    {{ filter.label ? option[filter.label] :  option.name }}
                </option>
            </select>
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
import { useTableFilters } from '../../../table-filters.js';
import {onMounted, ref} from "vue";

const props = defineProps({
    table: { default: null },
    name: { default: null },
    filter: { type: Object }
});

const selectedItem = ref();
const emit = defineEmits(['updated']);
const filterStore = useTableFilters();
const { setFilter } = filterStore;

const apply = () => {
    setFilter(props.table, props.filter.name ?? props.name, selectedItem.value);
    emit('updated', true);
}

const reset = () => {
    selectedItem.value = null;
    setFilter(props.table, props.filter.name ?? props.name, null);
    emit('updated', true);
}

onMounted(() => {

})
</script>

<style lang="scss" scoped>
.options-container {
    max-height: 200px;
    overflow-y: auto;
    text-align: left;
}
</style>
