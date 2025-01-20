<template>
    <div class="loading-container">
        <div class="loading-spiner d-flex justify-content-center align-items-center">
            <div class="flex text-blue-500"><i class="fa fa-sync-alt fa-3x fa-spin"></i></div>
        </div>
        <div class="loading-background"></div>
    </div>
</template>

<script>
export default {
    name: 'table-loading',
};
</script>

<style scoped>
.loading-container {
    backdrop-filter: blur(2px) !important;
    z-index: 2;
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    height: 100%;
    width: 100%;
}

.loading-container .loading-spiner {
    z-index: 4;
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    height: 100%;
    width: 100%;
}

.loading-container .loading-background {
    z-index: 3;
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    height: 100%;
    width: 100%;
    background-color: #000;
    opacity: .1;
}
</style>
