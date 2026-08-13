
            <!-- MENU 1: FLEET MANAGEMENT -->
            <div 
                 class="space-y-6 max-w-7xl mx-auto">
                 
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-white tracking-tight">Fleet Management</h2>
                        <p class="text-slate-400 text-sm mt-1">Monitor and control deployed Simpul agents</p>
                    </div>
                    <button @click="$dispatch('open-deploy')" class="bg-cyan-600 hover:bg-cyan-500 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-all shadow-[0_0_15px_rgba(8,145,178,0.2)] hover:shadow-[0_0_20px_rgba(8,145,178,0.4)] flex items-center justify-center border border-cyan-400/20 active:scale-95 w-full sm:w-auto">
                        <i class="fa-solid fa-plus mr-2"></i> Deploy Agent
                    </button>
                </div>

                <div class="bg-slate-950/80 backdrop-blur-sm rounded-xl border border-slate-800 overflow-hidden shadow-2xl">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-slate-900/80 text-slate-400 border-b border-slate-800/80">
                                <tr>
                                    <th class="px-6 py-4 font-semibold tracking-wide">Hostname</th>
                                    <th class="px-6 py-4 font-semibold tracking-wide">IP Address</th>
                                    <th class="px-6 py-4 font-semibold tracking-wide">Status</th>
                                    <th class="px-6 py-4 font-semibold tracking-wide">Last Seen</th>
                                    <th class="px-6 py-4 font-semibold tracking-wide text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/50">
                                <template x-if="agents.length === 0">
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                            <i class="fa-solid fa-server text-4xl mb-3 text-slate-700"></i>
                                            <p class="text-sm">Tidak ada agen yang terhubung saat ini.</p>
                                        </td>
                                    </tr>
                                </template>
                                <template x-for="agent in agents" :key="agent.id">
                                    <tr class="hover:bg-slate-800/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <i class="fa-solid fa-server text-cyan-500 mr-3"></i>
                                                <span class="font-medium text-slate-200" x-text="agent.hostname || agent.id || 'Dokumentasi'"></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="font-mono text-slate-400" x-text="agent.ip_address || '192.168.128.25'"></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-1.5 animate-pulse"></span> Active
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-slate-400 text-sm" x-text="agent.last_seen ? new Date(agent.last_seen).toLocaleString('id-ID') : 'Aktif'"></td>
                                        <td class="px-6 py-4 text-right">
                                            <button @click="activeServer = agent; currentTab = 'ops'" class="text-slate-400 hover:text-cyan-400 transition-colors p-2 hover:bg-slate-800 rounded-lg mr-1" title="Monitor & Configure Agent">
                                                <i class="fa-solid fa-sliders"></i>
                                            </button>
                                            <button @click="deleteAgent(agent)" class="text-slate-400 hover:text-rose-400 transition-colors p-2 hover:bg-rose-500/10 rounded-lg" title="Delete">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            

