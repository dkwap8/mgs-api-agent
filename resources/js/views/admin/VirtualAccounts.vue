<template><RecordTable title="Virtual Accounts" description="Search platform accounts by name or number, provider and status." endpoint="/api/v1/admin/virtual-accounts" provider-filter :statuses="['active','inactive','suspended','closed','expired']" :columns="columns" /></template>
<script setup>
import RecordTable from '../../components/RecordTable.vue';
const columns=[{key:'account_number',label:'Account number'},{key:'account_name',label:'Account name'},{key:'bank_name',label:'Bank'},{key:'provider.name',label:'Provider'},{key:'business.name',label:'Business'},{key:'customer.name',label:'Customer'},{key:'status',label:'Status'}];
</script>
