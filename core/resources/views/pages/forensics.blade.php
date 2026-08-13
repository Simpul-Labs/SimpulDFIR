
            <!-- MENU 3: DIGITAL FORENSICS -->
            <div 
                 class="space-y-6 max-w-7xl mx-auto">
                 
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-white tracking-tight">Digital Forensics</h2>
                        <p class="text-slate-400 text-sm mt-1">Audit, artifact extraction, and compliance reporting</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <div class="lg:col-span-1 space-y-6">
                        <!-- Extraction Form -->
                        <div class="bg-slate-950 rounded-xl border border-slate-800 p-6 shadow-xl relative overflow-hidden">
                            <!-- accent line -->
                            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-cyan-600 to-blue-500"></div>
                            
                            <h3 class="text-xs font-bold text-slate-400 mb-5 uppercase tracking-widest flex items-center">
                                <i class="fa-solid fa-crosshairs mr-2 text-cyan-500"></i> Extraction Target
                            </h3>
                            
                            <div class="mb-6">
                                <label class="block text-xs font-medium text-slate-400 mb-2">Select Target Server</label>
                                <div class="relative">
                                    <select x-model="forensicTarget" :disabled="isGenerating" class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-lg focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 block p-3 appearance-none transition-all outline-none font-medium disabled:opacity-50">
                                        <option value="" disabled selected>Select an agent...</option>
                                        <template x-if="agents.length === 0">
                                            <option value="" disabled>No agents available</option>
                                        </template>
                                        <template x-for="agent in agents" :key="agent.id">
                                            <option :value="agent.id" x-text="`${agent.hostname} (${agent.ip_address})`"></option>
                                        </template>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                                        <i class="fa-solid fa-chevron-down text-xs"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-8 bg-slate-900 p-4 rounded-lg border border-slate-800">
                                <label class="block text-xs font-medium text-slate-400 mb-4 uppercase tracking-wider">Artifacts to Collect</label>
                                <div class="space-y-3">
                                    <label class="flex items-start space-x-3 cursor-pointer group">
                                        <input type="checkbox" value="/var/log/auth.log*" x-model="selectedLogs" class="w-4 h-4 mt-0.5 bg-slate-950 border-slate-700 rounded text-cyan-500 focus:ring-cyan-500/50 focus:ring-offset-slate-900 form-checkbox transition-colors">
                                        <div>
                                            <div class="text-sm text-slate-300 font-mono group-hover:text-white transition-colors">/var/log/auth.log*</div>
                                            <div class="text-[10px] text-slate-500">Authentication & SSH logs</div>
                                        </div>
                                    </label>
                                    <label class="flex items-start space-x-3 cursor-pointer group">
                                        <input type="checkbox" value="/var/log/auth.log.1" x-model="selectedLogs" class="w-4 h-4 mt-0.5 bg-slate-950 border-slate-700 rounded text-cyan-500 focus:ring-cyan-500/50 focus:ring-offset-slate-900 form-checkbox transition-colors">
                                        <div>
                                            <div class="text-sm text-slate-300 font-mono group-hover:text-white transition-colors">/var/log/auth.log.1</div>
                                            <div class="text-[10px] text-slate-500">Rotated authentication log</div>
                                        </div>
                                    </label>
                                    <label class="flex items-start space-x-3 cursor-pointer group">
                                        <input type="checkbox" value="/var/log/syslog*" x-model="selectedLogs" class="w-4 h-4 mt-0.5 bg-slate-950 border-slate-700 rounded text-cyan-500 focus:ring-cyan-500/50 focus:ring-offset-slate-900 form-checkbox transition-colors">
                                        <div>
                                            <div class="text-sm text-slate-300 font-mono group-hover:text-white transition-colors">/var/log/syslog*</div>
                                            <div class="text-[10px] text-slate-500">System daemon logs</div>
                                        </div>
                                    </label>
                                    <label class="flex items-start space-x-3 cursor-pointer group">
                                        <input type="checkbox" value="/var/log/nginx/*" x-model="selectedLogs" class="w-4 h-4 mt-0.5 bg-slate-950 border-slate-700 rounded text-cyan-500 focus:ring-cyan-500/50 focus:ring-offset-slate-900 form-checkbox transition-colors">
                                        <div>
                                            <div class="text-sm text-slate-300 font-mono group-hover:text-white transition-colors">/var/log/nginx/*</div>
                                            <div class="text-[10px] text-slate-500">Web server access/error logs</div>
                                        </div>
                                    </label>
                                    <label class="flex items-start space-x-3 cursor-pointer group">
                                        <input type="checkbox" value="System State Snapshot" x-model="selectedLogs" class="w-4 h-4 mt-0.5 bg-slate-950 border-slate-700 rounded text-cyan-500 focus:ring-cyan-500/50 focus:ring-offset-slate-900 form-checkbox transition-colors">
                                        <div>
                                            <div class="text-sm text-slate-300 font-mono group-hover:text-white transition-colors">System State Snapshot</div>
                                            <div class="text-[10px] text-slate-500">netstat, ps aux, lsof, w</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mt-8 pt-6 border-t border-slate-800">
                                <button @click="generateForensicPackage()" x-show="!isGenerating" class="w-full bg-cyan-600 hover:bg-cyan-500 text-white font-bold py-3 rounded-lg transition-all shadow-[0_0_20px_rgba(8,145,178,0.3)] hover:shadow-[0_0_25px_rgba(8,145,178,0.5)] active:scale-[0.98] flex items-center justify-center border border-cyan-400/20">
                                    <i class="fa-solid fa-microchip mr-2"></i> Generate Forensic Package
                                </button>
                                
                                <div x-show="isGenerating" class="w-full space-y-2">
                                    <div class="flex justify-between text-xs font-bold text-cyan-400">
                                        <span>Extracting Artifacts...</span>
                                        <span x-text="generationProgress + '%'"></span>
                                    </div>
                                    <div class="w-full bg-slate-900 rounded-full h-2.5 border border-slate-700 overflow-hidden">
                                        <div class="bg-gradient-to-r from-cyan-600 to-blue-500 h-2.5 rounded-full transition-all duration-300 ease-out" :style="`width: ${generationProgress}%`"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="lg:col-span-2">
                        <!-- Reports Table -->
                        <div class="bg-slate-950 rounded-xl border border-slate-800 overflow-hidden h-full flex flex-col shadow-xl">
                            <div class="bg-slate-900/80 border-b border-slate-800 px-6 py-5 shrink-0">
                                <h3 class="text-sm font-semibold text-white flex items-center tracking-wide">
                                    <i class="fa-solid fa-folder-open mr-2 text-cyan-500"></i> Recent Forensic Reports
                                </h3>
                            </div>
                            <div class="overflow-x-hidden flex-1">
                                <table class="w-full text-left text-sm table-fixed">
                                    <thead class="bg-slate-950 text-slate-400 border-b border-slate-800/80 text-xs uppercase tracking-wider">
                                        <tr>
                                            <th class="px-6 py-4 font-semibold w-[15%]">Case ID</th>
                                            <th class="px-6 py-4 font-semibold w-[20%]">Target Server</th>
                                            <th class="px-6 py-4 font-semibold w-[20%]">Date Generated</th>
                                            <th class="px-6 py-4 font-semibold w-[20%]">SHA-256 Hash</th>
                                            <th class="px-6 py-4 font-semibold w-[25%] text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800/50">
                                        <tr x-show="reports.length === 0">
                                            <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                                <i class="fa-solid fa-file-shield text-4xl mb-3 text-slate-700"></i>
                                                <p class="text-sm">Belum ada laporan forensik yang dibuat.</p>
                                            </td>
                                        </tr>
                                        <template x-for="report in reports" :key="report.id">
                                            <tr class="hover:bg-slate-900/50 transition-colors group">
                                                <td class="px-6 py-4 font-mono font-medium text-white truncate" x-text="report.id" :title="report.id"></td>
                                                <td class="px-6 py-4 truncate">
                                                    <div class="flex flex-col">
                                                        <span class="text-slate-300 font-medium text-sm truncate" x-text="report.agent" :title="report.agent"></span>
                                                        <span class="text-xs text-slate-500 font-mono" x-text="report.agent_ip"></span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-slate-400 text-sm truncate" x-text="formatDate(report.date || report.created_at)"></td>
                                                <td class="px-6 py-4 text-slate-400 font-mono text-[11px] truncate" :title="report.hash" x-text="report.hash"></td>
                                                <td class="px-6 py-4 text-right">
                                                    <div class="flex items-center justify-end space-x-2">
                                                        <button @click="fallbackDownload(report)" class="bg-cyan-500/10 text-cyan-400 border border-cyan-500/30 hover:bg-cyan-500/20 px-3 py-1.5 rounded text-xs font-semibold transition-colors inline-flex items-center" title="Download Laporan HTML">
                                                            <i class="fa-solid fa-file-code mr-1"></i> Download HTML
                                                        </button>
                                                        <button @click="deleteReport(report.id)" class="text-slate-400 hover:text-rose-400 transition-colors p-1.5 hover:bg-rose-500/10 rounded-lg" title="Delete Report">
                                                            <i class="fa-solid fa-trash-can"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="bg-slate-900 border-t border-slate-800 px-6 py-4 text-xs text-slate-500 flex justify-between items-center shrink-0">
                                <span>Showing <span x-text="reports.length"></span> of <span x-text="reports.length"></span> reports</span>
                                <div class="flex space-x-2">
                                    <button class="px-3 py-1 bg-slate-800 hover:bg-slate-700 rounded text-slate-300 transition-colors">Previous</button>
                                    <button class="px-3 py-1 bg-slate-800 hover:bg-slate-700 rounded text-slate-300 transition-colors">Next</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            

