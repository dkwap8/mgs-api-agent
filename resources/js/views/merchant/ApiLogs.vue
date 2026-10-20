<template><RecordTable title="API Logs" description="Request metadata for the selected business. Credentials and request bodies are excluded." endpoint="/api/v1/merchant/api-logs" search-key="endpoint" search-placeholder="Endpoint" status-code :columns="columns" /></template>
<script setup>
import RecordTable from '../../components/RecordTable.vue';
const columns=[{key:'created_at',label:'Time'},{key:'method',label:'Method'},{key:'endpoint',label:'Endpoint'},{key:'status_code',label:'HTTP status'},{key:'ip_address',label:'IP address'},{key:'duration',label:'Duration (ms)'}];
</script>
