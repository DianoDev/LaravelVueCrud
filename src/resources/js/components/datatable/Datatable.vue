<template>
    <div class="dataTables_wrapper dt-bootstrap4 no-footer">
        <table-loading v-if="loading"></table-loading>
        <slot>
            <div v-if="checkboxEnabled" class="text-center">
                <h3 class="text-gray-400 fw-bold">Selected {{selection.selected}} of {{selection.total}} item(s)</h3>
            </div>
        </slot>
        <table class="table table-responsive table-hover" :id="`table-${id}`">
            <table-header v-if="params" :columns="columns" :table="id" :params="params" @sort="sort" @toggle-all="toggleAll" @filter="applyFilter" ref="headerComponent"></table-header>
            <table-content :enable-row-click="enableRowClick" @loaded="onLoaded" v-if="pageData.length > 0" :columns="columns" :data="pageData" :loading="loading" @toggle-item="toggleItem" @click="handleClick"></table-content>
        </table>
        <div class="row" v-if="total > 0 && !disablePagination">
            <div class="col-md-2">
                <select class="form-select form-select-sm" v-model="per_page" @change="changePerPage">
                    <option value="10">10 itens por página</option>
                    <option value="25">25 itens por página</option>
                    <option value="50">50 itens por página</option>
                    <option value="100">100 itens por página</option>
                    <option value="999999999">Exibir tudo</option>
                </select>
            </div>

            <div class="col-md-3">
                <p>
                    Exibindo registros de {{from}} à {{ to }}
                </p>
            </div>

            <div class="col-md-7">
                <table-pagination v-if="!loading" @change-page="changePage" :current_page="current_page" :from="from" :to="to" :total="total" :per_page="per_page" :last_page="last_page"></table-pagination>
            </div>
        </div>
        <div v-if="total === 0" class="text-center">
            <p>
                Sem registros para exibir
            </p>
        </div>
        <div v-if="error" class="text-center">
            <p class="small alert alert-danger">
                <i class="fa fa-exclamation-circle"></i> Erro ao tentar exibir dados.
                <strong>
                    <button @click="loadData" type="button" class="btn btn-danger text-white btn-sm">
                        <i class="fa fa-sync"></i>
                        Recarregar
                    </button>
                </strong>
            </p>
        </div>
    </div>
</template>

<script>
import {defineComponent, inject, onMounted, ref} from "vue";
import TableHeader from './partials/__table-header.vue';
import TableContent from './partials/__table-content.vue';
import TablePagination from './partials/__table-pagination.vue';
import TableLoading from './partials/__table-loading.vue';
import { useTableFilters } from '../table-filters';

export default defineComponent({
    name: "datatable",

    components: {
        TableHeader,
        TableContent,
        TablePagination,
        TableLoading,
    },

    setup(props, { emit }) {
        const selection = ref({ id: props.id, all: false, items: [], total: 0, selected: 0 });
        const filterStore = useTableFilters();
        const { getFilters } = filterStore;
        const events = inject('events');
        const confirmation = ref({});
        const params = ref({});
        const current_page = ref(1);
        const per_page = ref(10);
        const loading = ref(false);
        const error = ref(false);
        const pageData = ref([]);
        const ready = ref(false);
        const from = ref(null);
        const to = ref(null);
        const total = ref(0);
        const last_page = ref(0);
        const headerComponent = ref();

        const init = () => {
            loadDefaultParams();
            params.value.current_page = current_page.value = 1;
            events.on('table-filter', onTableFilter);

            if(props.watchReloadEvent) {
                events.on('table-reload', () => {loadData(true)});
            }

            events.on('table-delete-item', (data) => {
                emit('delete', data);
            });
            loadData();
        }

        const onLoaded = () => {
            emit('loaded', true);
            let items = document.querySelectorAll(`#table-${props.id} [data-event]`);
            items.forEach(item => {
                item.addEventListener('click', () => {
                    const event = item.dataset.event;
                    const data = JSON.parse(item.dataset.json);
                    emit(event, data);
                });
            });

            let actionItems = document.querySelectorAll(`#table-${props.id} [data-action]`);
            actionItems.forEach(item => {
                item.addEventListener('click', () => {
                    const action = item.dataset.action;
                    switch(action) {
                        case 'delete':
                            events.emit('confirmation', {
                                title: item.dataset.title || 'Confirmação',
                                data: JSON.parse(item.dataset.json),
                                message: item.dataset.message || 'Você deseja realmente excluir este registro?',
                                event: 'table-delete-item'
                            })
                            break;
                        case 'popup':
                            const component = item.dataset.component;
                            if(component) {
                                const data = JSON.parse(item.dataset.json ?? null);
                                const size = item.dataset.size ?? null;
                                const title = item.dataset.title ?? null;
                                events.emit('popup', {
                                    component,
                                    data,
                                    size,
                                    title
                                });
                            }
                            break;
                        default: break;
                    }
                });
            });
            if(props.checkboxEnabled) applySelection();
        }

        const loadDefaultParams = () => {
            if(props.id) {
                let savedParams = localStorage.getItem(`table-${props.id}-params`);
                if(savedParams) {
                    let newParams = {};
                    for(const [key, value] of Object.entries(JSON.parse(savedParams))) {
                        if(key.lastIndexOf('keyword') === -1)
                            newParams[key] = value;
                    }
                    params.value = newParams;
                }
            }
        }

        const onTableFilter = (data) => {
            let newParams = {};
            for(const [key, value] of Object.entries(data)) {
                newParams[key] = value;
            }
            params.value = newParams;
            loadData();
        }

        const changePerPage = () => {
            current_page.value = 1;
            params.value.per_page = per_page.value;
            params.value.current_page = current_page.value = 1;
            loadData();
        }

        const sort = (data) => {
            params.value.sort = data.column;
            params.value.sort_direction = data.direction;
            emit('sort', data);
            loadData();
        }

        const applyFilter = () => {
            params.value.current_page = current_page.value = 1;
            loadData();
        }

        const loadData = async (quiet = false) => {
            if(!quiet) loading.value = true;
            error.value = false;
            try {
                const baseUrl = props.source.indexOf('?') > 0 ? `${props.source}&` : `${props.source}?`;
                const url = `${baseUrl}${getJsonParameters()}`
                const response = await axios.get(url);
                pageData.value = response.data.data;
                parseHeaderData(response.data.data, response.data.filter_options ?? null)
                params.value.current_page = current_page.value = response.data.current_page;
                from.value = response.data.from;
                to.value = response.data.to;
                total.value = response.data.total;
                params.value.per_page = per_page.value = response.data.per_page;
                last_page.value = response.data.last_page;
                ready.value = true;
                selection.value.total = total.value;

                if(props.id) {
                    localStorage.setItem(`table-${props.id}-params`, JSON.stringify(params.value));
                }
                headerComponent.value.dataLoaded(params.value);
            } catch(err) {
                error.value = true;
                emit('data-load-error', true);
            } finally {
                loading.value = false;
            }
        }

        const parseHeaderData = (data, options = null) => {
            if(!options) return;

            props.columns.map(col => {
                if(!!options[col.name]) {
                    col.filter = options[col.name];
                }
            })
        }

        const changePage = (page) => {
            params.value.current_page = current_page.value = page;
            emit('page-change', page);
            loadData();
        }

        const getTableFilters = () => {
            let filters = getFilters(props.id);
            return Object.assign(params.value, filters);
        }

        const getJsonParameters = () => {
            let str = [];
            for (let p in params.value) {
                if (params.value.hasOwnProperty(p) && params.value[p] !== null) {
                    str.push(encodeURIComponent(p) + "=" + encodeURIComponent(params.value[p]));
                }
            }

            let filters = getFilters(props.id);
            for (let p in filters) {
                if (filters.hasOwnProperty(p) && filters[p] !== null) {
                    if(typeof filters[p] === 'object') {
                        filters[p].map(filterItem => {

                            str.push(encodeURIComponent(p) + "=" + encodeURIComponent(filterItem));
                        })
                    } else {
                        str.push(encodeURIComponent(p) + "=" + encodeURIComponent(filters[p]));
                    }
                }
            }
            return str.join("&");
        }

        const toggleAll = (item) => {
            selection.value.all = item.checked;
            selection.value.items = [];
            if(item.checked) {
                selection.value.selected = selection.value.total - selection.value.items.length;
            } else {
                selection.value.selected = 0;
            }
            events.emit('table-toggle-all', item.checked);
            events.emit('table-item-select', {items: selection.value, filters: getTableFilters()});
            applySelection();
        }

        const toggleItem = (item) => {
            if(selection.value.all) {
                if(item.checked) {
                    selection.value.items = selection.value.items.filter(i => i !== item.value);
                } else {
                    selection.value.items.push(item.value);
                }
                selection.value.selected = selection.value.total - selection.value.items.length;
            } else {
                if(item.checked) {
                    selection.value.items.push(item.value);
                } else {
                    selection.value.items = selection.value.items.filter(i => i !== item.value);
                }
                selection.value.selected = selection.value.items.length;
            }
            events.emit('table-item-select', {items: selection.value, filters: getTableFilters()});
        }

        const applySelection = () => {
            setTimeout(() => {
                let checkAll = document.getElementById("table-checkbox-all");
                if(checkAll) checkAll.checked = selection.value.all;
                let items = document.querySelectorAll(".table-checkbox");
                items.forEach((item) => {
                    if(item.value !== 'all') {
                        if (selection.value.all)
                            item.checked = !selection.value.items.includes(item.id.replace('table-checkbox-', ''));
                        else
                            item.checked = selection.value.items.includes(item.id.replace('table-checkbox-', ''));
                    }
                });
            }, 50);
        }

        const handleClick = (item) => {
            emit('row-click', item);
        }

        onMounted(init);

        return {
            confirmation,
            params,
            current_page,
            per_page,
            pageData,
            loading,
            error,
            ready,
            from,
            to,
            total,
            last_page,
            headerComponent,
            selection,
            loadData,
            applyFilter,
            onLoaded,
            changePage,
            changePerPage,
            sort,
            toggleItem,
            toggleAll,
            handleClick,
        }
    },

    props: {
        id: { type: String, required: false },
        columns: { type: Array, required: true},
        source: { type: String, required: false, default: null },
        data: {type: Array, required: false, default: null},
        itemsPerPage: {type: Number, default: 10},
        checkboxEnabled: {type: Boolean, required: false, default: false},
        checkboxLabel: {type: String, required: false, default: "ID"},
        disablePagination:{type: Boolean, default: false},
        enableRowClick: {type: Boolean, default: false },
        watchReloadEvent: { type: Boolean, default: true }
    },
});
</script>

<style scoped lang="scss">
.table {
    font-size: 0.85rem !important;
}
</style>
