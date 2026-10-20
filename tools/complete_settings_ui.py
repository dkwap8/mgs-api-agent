from pathlib import Path
root=Path(__file__).resolve().parents[1]
path=root/'resources/js/views/merchant/Settings.vue'
source=path.read_text(encoding='utf-8')
start=source.index('                    <div class="flex items-center justify-between rounded-xl border border-slate-200 p-4 dark:border-slate-700">')
end=source.index('                    <div>\n                        <h4 class="mb-2 font-medium text-slate-950 dark:text-white">Change Password', start)
source=source[:start]+'                    <UserSecuritySettings />\n\n'+source[end:]
source=source.replace("import axios from 'axios';", "import axios from 'axios';\nimport UserSecuritySettings from '../../components/UserSecuritySettings.vue';")
source=source.replace("    name: 'Settings',", "    name: 'Settings',\n    components: { UserSecuritySettings },")
start=source.index('        const toggle2FA = ')
end=source.index('        onMounted(fetchProfile);',start)
source=source[:start]+source[end:]
source=source.replace('            toggle2FA,\n','')
path.write_text(source,encoding='utf-8')
