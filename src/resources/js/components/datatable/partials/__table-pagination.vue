<template>
    <div v-if="total > 0">
        <nav aria-label="...">
            <ul class="pagination pagination-sm justify-content-end">
                <li class="page-item" :class="{disabled: current_page === 1}">
                    <a class="page-link" href="javascript:" @click="goto(1)">
                        <i class="fa fa-angles-left"></i>
                    </a>
                </li>

                <li class="page-item" :class="{disabled: current_page === 1}">
                    <a class="page-link" href="javascript:" @click="goto(current_page - 1)">
                        <i class="fa fa-chevron-left"></i>
                    </a>
                </li>

                <li v-for="page in pageList" class="page-item" :class="{active: (page === current_page)}">
                    <a class="page-link" :class="{'text-white': (page === current_page)}" href="javascript:" @click="select(page)">{{page}}</a>
                </li>

                <li class="page-item" :class="{disabled: last_page === current_page}">
                    <a class="page-link" href="javascript:" @click="goto(current_page + 1)">
                        <i class="fa fa-chevron-right"></i>
                    </a>
                </li>

                <li class="page-item" :class="{disabled: last_page === current_page}">
                    <a class="page-link" href="javascript:" @click="goto(last_page)">
                        <i class="fa fa-angles-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</template>

<script>
import { onMounted, inject, ref } from 'vue';
export default {
    watch: {
        current_page: function() { this.setPages() },
        per_page: function() { this.setPages() },
    },

    setup(props, { emit }) {
        const range = ref(10);
        const pageList = ref([]);

        const goto = (page) => {
            emit('change-page', page);
        }

        const select = (page) => {
            emit('change-page', page);
        }

        const setPages = () => {
            if(props.total === null) return;
            pageList.value = [];
            let pages = [];
            for(let i = 1; i <= parseInt(props.last_page); i++) {
                pages.push(i);
            }

            if(props.last_page > range.value) {
                let index = Math.ceil(props.current_page - (range.value / 2));
                if (index < 0) index = 0;
                if ((index + range.value) > props.last_page) index = (props.last_page - range.value);
                pageList.value = pages.slice(index, index + range.value);
            } else {
                pageList.value = pages;
            }
        }

        onMounted(setPages);

        return {
            range,
            pageList,
            setPages,
            goto,
            select
        }
    },

    props: {
        current_page: { default: null },
        from: { default: null },
        to: { default: null },
        total: { default: null },
        per_page: { default: null },
        last_page: { default: null }
    }
};
</script>

<style scoped>

</style>
