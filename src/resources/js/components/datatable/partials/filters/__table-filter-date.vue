<template>
    <div>
        <div class="row">
            <div class="col mb-3">
                <input-date class="full-width" v-model="keyword" />
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
import { useTableFilters } from '../../../table-filters.js';
import {onMounted, ref} from "vue";
const emit = defineEmits(['updated']);

const props = defineProps({
    table: { default: null },
    name: { default: null },
    filter: { default: null }
});

const keyword = ref(null);
const filterStore = useTableFilters();
const { setFilter } = filterStore;

const apply = () => {
    setFilter(props.table, props.filter.name ?? props.name, keyword.value);
    emit('updated', true);
}

const reset = () => {
    keyword.value = null;
    setFilter(props.table, props.filter.name ?? props.name, null);
    emit('updated', true);
}

const handleKeypress = (evt) => {
    if(evt.keyCode === 13) apply();
}

onMounted(() => {

})

</script>

<style lang="scss" scoped>
.full-width {
    width: 100%;
    padding: 4px;
    font-size: 90%;
}
</style>
