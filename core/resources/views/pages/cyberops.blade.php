
            <!-- MENU 2: CYBER OPS -->
            <div 
                 class="max-w-[1600px] mx-auto h-full flex flex-col">
                
                <!-- SERVER LIST VIEW -->
                <div x-show="!activeServer" class="flex-1 flex flex-col">
                    <div class="flex items-center justify-between mb-6 shrink-0">
                        <div>
                            <h2 class="text-2xl font-bold text-white tracking-tight flex items-center">
                                Cyber Ops: Active Fleet
                            </h2>
                            <p class="text-slate-400 text-sm mt-1">Select a server to monitor its live forensic stream</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <template x-if="agents.length === 0">
                            <div class="col-span-full py-12 flex flex-col items-center justify-center text-slate-500 border-2 border-dashed border-slate-800 rounded-xl">
                                <i class="fa-solid fa-satellite-dish text-4xl mb-4 animate-pulse"></i>
                                <p>Waiting for agents to connect...</p>
                            </div>
                        </template>
                        <template x-for="agent in agents" :key="agent.id">
                            <div @click="activeServer = agent" class="bg-slate-900 border border-slate-800 hover:border-cyan-500/50 rounded-xl p-5 cursor-pointer transition-all hover:shadow-[0_0_20px_rgba(34,211,238,0.1)] group relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-24 h-24 bg-cyan-500/5 rounded-bl-full -z-10 group-hover:bg-cyan-500/10 transition-colors"></div>
                                <div class="flex justify-between items-start mb-4">
                                    <div class="p-3 bg-slate-950 rounded-lg border border-slate-800 group-hover:border-cyan-500/30">
                                        <i class="fa-solid fa-server text-cyan-500 text-xl"></i>
                                    </div>
                                    <span class="flex items-center text-[10px] font-bold uppercase tracking-wider text-emerald-400 bg-emerald-400/10 px-2 py-1 rounded border border-emerald-400/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse mr-1.5"></span> Online
                                    </span>
                                </div>
                                <h3 class="text-white font-bold text-lg mb-1 truncate" x-text="agent.hostname"></h3>
                                <p class="text-slate-400 text-sm font-mono mb-4" x-text="agent.ip_address"></p>
                                <div class="text-xs text-slate-500">
                                    Last seen: <span x-text="agent.last_seen ? new Date(agent.last_seen).toLocaleTimeString('id-ID') : 'Aktif'"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- TERMINAL VIEW -->
                <div x-show="activeServer" class="space-y-6">
                    <div class="flex items-center justify-between shrink-0">
                        <div class="flex items-center">
                            <button @click="activeServer = null" class="mr-4 text-slate-400 hover:text-white bg-slate-900 hover:bg-slate-800 p-2.5 rounded-lg border border-slate-800 transition-colors">
                                <i class="fa-solid fa-arrow-left"></i>
                            </button>
                            <div>
                                <h2 class="text-xl md:text-2xl font-bold text-white tracking-tight flex items-center flex-wrap gap-2">
                                    <span class="truncate max-w-[150px] sm:max-w-xs md:max-w-full" x-text="activeServer?.hostname"></span>
                                    <span class="px-2.5 py-0.5 rounded text-[9px] md:text-[10px] uppercase font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30 flex items-center">
                                        <span class="animate-pulse w-1.5 h-1.5 bg-rose-400 rounded-full mr-1.5"></span> Live
                                    </span>
                                </h2>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                        <!-- Dashboard Vitals & Events -->
                        <div class="lg:col-span-3 flex flex-col space-y-6">
                            
                            <!-- Health Widgets -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- CPU -->
                                <div class="bg-slate-950 rounded-xl border border-slate-800 p-5 shadow-lg relative overflow-hidden">
                                    <div class="absolute top-0 right-0 w-16 h-16 bg-cyan-500/5 rounded-bl-full -z-10"></div>
                                    <h3 class="text-xs font-bold text-slate-400 mb-2 uppercase tracking-widest flex items-center">
                                        <i class="fa-solid fa-microchip mr-2 text-cyan-500"></i> CPU Usage
                                    </h3>
                                    <div class="flex items-end space-x-2 mb-2">
                                        <span class="text-3xl font-bold text-white" x-text="metrics.cpu + '%'"></span>
                                        <span class="text-xs text-slate-500 mb-1" x-text="metrics.cpuCount ? (`(${metrics.cpuCount} Cores)`) : ''"></span>
                                    </div>
                                    <div class="w-full bg-slate-900 rounded-full h-1.5 border border-slate-800 overflow-hidden">
                                        <div class="bg-cyan-500 h-1.5 rounded-full transition-all duration-500" :style="`width: ${metrics.cpu}%`"></div>
                                    </div>
                                </div>
                                <!-- RAM -->
                                <div class="bg-slate-950 rounded-xl border border-slate-800 p-5 shadow-lg relative overflow-hidden">
                                    <div class="absolute top-0 right-0 w-16 h-16 bg-purple-500/5 rounded-bl-full -z-10"></div>
                                    <h3 class="text-xs font-bold text-slate-400 mb-2 uppercase tracking-widest flex items-center">
                                        <i class="fa-solid fa-memory mr-2 text-purple-500"></i> Memory
                                    </h3>
                                    <div class="flex items-end space-x-2 mb-2">
                                        <span class="text-3xl font-bold text-white" x-text="metrics.ram + '%'"></span>
                                        <span class="text-xs text-slate-500 mb-1" x-text="metrics.ramUsedGB && metrics.ramTotal ? (`(${metrics.ramUsedGB} / ${metrics.ramTotal} GB)`) : (metrics.ramTotal ? (`/ ${metrics.ramTotal}GB`) : '')"></span>
                                    </div>
                                    <div class="w-full bg-slate-900 rounded-full h-1.5 border border-slate-800 overflow-hidden">
                                        <div class="bg-purple-500 h-1.5 rounded-full transition-all duration-500" :style="`width: ${metrics.ram}%`"></div>
                                    </div>
                                </div>
                                <!-- Network -->
                                <div class="bg-slate-950 rounded-xl border border-slate-800 p-5 shadow-lg relative overflow-hidden">
                                    <div class="absolute top-0 right-0 w-16 h-16 bg-emerald-500/5 rounded-bl-full -z-10"></div>
                                    <h3 class="text-xs font-bold text-slate-400 mb-2 uppercase tracking-widest flex items-center">
                                        <i class="fa-solid fa-network-wired mr-2 text-emerald-500"></i> Network I/O
                                    </h3>
                                    <div class="flex flex-col space-y-1 mt-3">
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-slate-500"><i class="fa-solid fa-arrow-down text-emerald-500 mr-1"></i> RX</span>
                                            <span class="text-white font-mono" x-text="metrics.netIn + ' Mbps'"></span>
                                        </div>
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-slate-500"><i class="fa-solid fa-arrow-up text-blue-500 mr-1"></i> TX</span>
                                            <span class="text-white font-mono" x-text="metrics.netOut + ' Mbps'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Security Events & Connections -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Recent Events -->
                                <div class="bg-slate-950 rounded-xl border border-slate-800 flex flex-col shadow-lg overflow-hidden">
                                    <div class="bg-slate-900/50 border-b border-slate-800 px-5 py-3 shrink-0">
                                        <h3 class="text-xs font-bold text-slate-300 uppercase tracking-widest flex items-center">
                                            <i class="fa-solid fa-shield-halved mr-2 text-rose-500"></i> Recent Security Events
                                        </h3>
                                    </div>
                                    <div class="p-0 overflow-y-auto max-h-[300px] scrollbar-hide">
                                        <div class="divide-y divide-slate-800">
                                            <template x-for="event in securityEvents">
                                                <div class="p-4 hover:bg-slate-900/50 transition-colors flex items-start space-x-3">
                                                    <div class="mt-0.5">
                                                        <i class="fa-solid fa-circle-exclamation text-rose-500" x-show="event.severity === 'high'"></i>
                                                        <i class="fa-solid fa-triangle-exclamation text-yellow-500" x-show="event.severity === 'medium'"></i>
                                                        <i class="fa-solid fa-info-circle text-blue-500" x-show="event.severity === 'low'"></i>
                                                    </div>
                                                    <div class="flex-1">
                                                        <div class="flex justify-between items-center mb-1">
                                                            <span class="text-sm font-bold text-white" x-text="event.type"></span>
                                                            <span class="text-[10px] text-slate-500" x-text="event.time"></span>
                                                        </div>
                                                        <div class="text-xs text-slate-400 font-mono" x-text="`SRC: ${event.src} | USER: ${event.user}`"></div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Active Connections -->
                                <div class="bg-slate-950 rounded-xl border border-slate-800 flex flex-col shadow-lg overflow-hidden">
                                    <div class="bg-slate-900/50 border-b border-slate-800 px-5 py-3 shrink-0">
                                        <h3 class="text-xs font-bold text-slate-300 uppercase tracking-widest flex items-center">
                                            <i class="fa-solid fa-plug mr-2 text-cyan-500"></i> Active Connections
                                        </h3>
                                    </div>
                                    <div class="p-0 overflow-x-auto">
                                        <table class="w-full text-left border-collapse whitespace-nowrap">
                                            <thead>
                                                <tr class="border-b border-slate-800 text-[10px] uppercase text-slate-500 bg-slate-900/30">
                                                    <th class="px-4 py-2 font-medium">Proto</th>
                                                    <th class="px-4 py-2 font-medium">Local Address</th>
                                                    <th class="px-4 py-2 font-medium">State</th>
                                                    <th class="px-4 py-2 font-medium">PID/Program</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-800/50 text-xs">
                                                <template x-for="conn in activeConnections">
                                                    <tr class="border-b border-slate-800/50 hover:bg-slate-800/30 transition-colors">
                                                        <td class="px-4 py-3 font-mono text-slate-400" x-text="conn.proto"></td>
                                                        <td class="px-4 py-3 font-mono text-slate-300" x-text="conn.local_address"></td>
                                                        <td class="px-4 py-3">
                                                            <span class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 text-[9px] whitespace-nowrap" x-text="conn.state"></span>
                                                        </td>
                                                        <td class="px-4 py-3 font-mono text-slate-400" x-text="conn.pid_program"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Threats Widget -->
                        <div class="bg-slate-950 rounded-xl border border-slate-800 flex flex-col shadow-2xl h-full">
                            <div class="bg-slate-900/50 border-b border-slate-800 px-5 py-4 rounded-t-xl shrink-0">
                                <h3 class="text-sm font-bold text-rose-400 flex items-center uppercase tracking-wider">
                                    <i class="fa-solid fa-radar mr-2"></i> Active Threats
                                </h3>
                            </div>
                            
                            <div class="p-4 flex-1 overflow-y-auto space-y-4 scrollbar-hide">
                                <template x-if="activeThreats.length === 0">
                                    <div class="text-center py-10 text-slate-500">
                                        <i class="fa-solid fa-shield-check text-4xl mb-3 text-emerald-500/30"></i>
                                        <p class="text-sm">No active threats detected.</p>
                                    </div>
                                </template>
                                <template x-for="threat in activeThreats" :key="threat.id">
                                    <div class="bg-slate-900 border border-rose-500/30 rounded-lg p-4 relative overflow-hidden group hover:border-rose-500 transition-colors">
                                        <div class="absolute top-0 right-0 w-16 h-16 bg-rose-500/10 rounded-bl-full -z-10 group-hover:bg-rose-500/20 transition-colors"></div>
                                        <div class="flex justify-between items-start mb-3">
                                            <div>
                                                <div class="text-white font-mono text-sm font-bold flex items-center">
                                                    <span x-text="threat.ip"></span>
                                                    <i class="fa-solid fa-copy ml-2 text-slate-500 hover:text-slate-300 cursor-pointer text-xs" @click="navigator.clipboard.writeText(threat.ip); showToast('IP copied to clipboard')"></i>
                                                </div>
                                                <div class="text-[11px] text-slate-400 mt-1 flex items-center">
                                                    <span class="text-slate-300">EXT</span> <i class="fa-solid fa-globe mx-1.5 text-slate-600"></i> Malicious Actor
                                                </div>
                                            </div>
                                            <span class="bg-rose-500/20 text-rose-400 text-[9px] uppercase font-bold px-2 py-1 rounded border border-rose-500/30" x-text="threat.type"></span>
                                        </div>
                                        <div class="text-[11px] text-slate-400 mb-4 bg-slate-950 p-2 rounded border border-slate-800 break-words">
                                            Target: <span class="text-slate-200 font-mono break-all" x-text="threat.target"></span><br>
                                            Severity: <span class="text-rose-500 font-bold" x-text="threat.severityText"></span>
                                        </div>
                                        <button @click="showToast('IP ' + threat.ip + ' has been blocked on ' + activeServer.hostname, 'success')" class="w-full bg-rose-600/90 hover:bg-rose-500 text-white text-[11px] font-bold py-2.5 rounded transition-all shadow-[0_0_15px_rgba(225,29,72,0.3)] hover:shadow-[0_0_20px_rgba(225,29,72,0.6)] active:scale-95 flex items-center justify-center">
                                            <i class="fa-solid fa-shield-halved mr-2"></i> BLOCK IP VIA IPTABLES
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            

