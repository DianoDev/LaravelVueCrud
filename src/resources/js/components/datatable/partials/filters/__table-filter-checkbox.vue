<template>
    <div>
        <div class="mb-2 options-container">
            <div class="text-truncate border-bottom py-1" v-for="option of filter.options">
                <label class="fw-normal">
                    <input type="checkbox" class="me-1" name="item[]" :value="option.id" v-model="selectedItems" /> {{option.name}}
                </label>
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
import { useTableFilters } from '../../../../../stores/table-filters';
import {onMounted, ref} from "vue";

const props = defineProps({
    table: { default: null },
    name: { default: null },
    filter: { type: Object }
});
const items = ref([]);
const selectedItems = ref([]);

const emit = defineEmits(['updated']);
const filterStore = useTableFilters();
const { setFilter } = filterStore;

const apply = () => {
    items.value = selectedItems.value;
    setFilter(props.table, props.filter.name ?? props.name, items.value);
    emit('updated', true);
}

const reset = () => {
    items.value = selectedItems.value = [];
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
