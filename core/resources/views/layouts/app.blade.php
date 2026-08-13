<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Simpul DFIR - Master Node</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- html2pdf for local client-side PDF generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" defer></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        slate: {
                            950: '#0a0f18', // extra dark for contrast
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #0f172a; /* slate-900 */
        }
        .terminal-text { 
            font-family: 'JetBrains Mono', monospace; 
        }
        /* Custom scrollbar for terminal */
        .scrollbar-hide::-webkit-scrollbar {
            width: 6px;
        }
        .scrollbar-hide::-webkit-scrollbar-track {
            background: transparent;
        }
        .scrollbar-hide::-webkit-scrollbar-thumb {
            background-color: #334155;
            border-radius: 20px;
        }
        
        /* Glassmorphism utility */
        .glass {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        
        /* Subtle glow utilities */
        .glow-cyan {
            box-shadow: 0 0 15px rgba(34, 211, 238, 0.3);
        }
        .glow-rose {
            box-shadow: 0 0 15px rgba(244, 63, 94, 0.3);
        }
        
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="text-slate-300 h-screen overflow-hidden flex selection:bg-cyan-900 selection:text-cyan-100 bg-slate-950" x-data="appData()">
    
    <!-- LOGIN VIEW MOVED TO NATIVE BLADE -->
    
    <script>
        function appData() {
            const initialTab = window.location.pathname.replace('/', '') || 'fleet';
            return {
                sidebarOpen: false,
                currentTab: initialTab,
                activeServer: null,
                forensicTarget: '',
                isLoggedIn: true, // Native Laravel auth
                showSettingsModal: false,
                settingsTab: 'profile',
                oldPassword: '',
                newPassword: '',
                showDeployModal: false,
                deployTargetIp: '',
                installCommand: '',
                reports: JSON.parse(localStorage.getItem('dfir_reports') || '[]'),
                notifications: [],
                unreadCount: 0,
                isGenerating: false,
                generationProgress: 0,
                lastLogTime: null,
                selectedLogs: ['/var/log/auth.log*', '/var/log/auth.log.1', '/var/log/syslog*'],
                // Dashboard Metrics State
                agents: [],
                metrics: { cpu: 0, ram: 0, disk: 0, netIn: 0, netOut: 0 },
                securityEvents: [],
                activeConnections: [],
                
                // NTP Sync
                sync: false,
                serverTime: '...',
                serverTimeInterval: null,
                initClock() {
                    this.updateClock();
                    if(this.serverTimeInterval) clearInterval(this.serverTimeInterval);
                    this.serverTimeInterval = setInterval(() => this.updateClock(), 1000);
                },
                updateClock() {
                    const now = new Date();
                    this.serverTime = now.toTimeString().split(' ')[0] + ' UTC';
                },
                async syncNTP() {
                    try {
                        this.showToast('Mencoba sinkronisasi waktu dengan Master Node...', 'info');
                        const res = await fetch(`/api/web/system/status`);
                        if(res.ok) {
                            this.sync = true;
                            this.showToast('NTP Sync berhasil!', 'success');
                            setTimeout(() => { this.sync = false }, 5000);
                        } else {
                            this.showToast('Gagal terhubung ke layanan NTP Master Node', 'error');
                        }
                    } catch (e) {
                        this.showToast('Koneksi terputus saat mencoba sync waktu', 'error');
                    }
                },
                formatDate(dateStr) {
                    if (!dateStr) return 'Baru Saja';
                    try {
                        const d = new Date(dateStr);
                        if (isNaN(d.getTime())) return 'Baru Saja';
                        return d.toLocaleString('id-ID', {
                            day: '2-digit', month: 'short', year: 'numeric',
                            hour: '2-digit', minute: '2-digit', second: '2-digit'
                        }) + ' WIB';
                    } catch(e) {
                        return 'Baru Saja';
                    }
                },
                logout() {
                    // Submit a POST request to native Laravel logout
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/logout';
                    
                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    form.appendChild(csrf);
                    
                    document.body.appendChild(form);
                    form.submit();
                },
                async changePassword() {
                    if (!this.oldPassword || !this.newPassword) {
                        this.showToast('Please fill all password fields', 'error');
                        return;
                    }
                    try {
                        const res = await fetch(`/api/web/auth/change-password`, {
                            method: 'POST',
                            headers: { 
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ old_password: this.oldPassword, new_password: this.newPassword })
                        });
                        if (res.ok) {
                            this.showToast('Password changed successfully', 'success');
                            this.showSettingsModal = false;
                            this.oldPassword = '';
                            this.newPassword = '';
                        } else {
                            const data = await res.json();
                            this.showToast(data.detail || 'Failed to change password', 'error');
                        }
                    } catch (err) {
                        this.showToast('Error connecting to server', 'error');
                    }
                },
                get activeThreats() {
                    const threats = {};
                    this.securityEvents.filter(e => e.severity === 'high' && e.src && e.src !== '-' && e.src !== '0.0.0.0').forEach(e => {
                        if (!threats[e.src]) {
                            threats[e.src] = { ip: e.src, type: e.type, count: 0, target: `${e.targetHost}:22` };
                        }
                        threats[e.src].count++;
                    });
                    return Object.values(threats).map(t => ({
                        ...t, id: t.ip, severityText: `CRITICAL (${t.count} attempts)`
                    }));
                },
                async generateDeployToken() {
                    if (!this.deployTargetIp) {
                        this.showToast('Masukkan IP address target server', 'error');
                        return;
                    }
                    try {
                        const res = await fetch(`/api/web/agents/token`, {
                            method: 'POST',
                            headers: { 
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ target_ip: this.deployTargetIp })
                        });
                        if (res.ok) {
                            const data = await res.json();
                            this.installCommand = data.install_command;
                            this.showToast(`Token instalasi berhasil dibuat untuk IP ${this.deployTargetIp}`, 'success');
                        } else {
                            this.showToast('Gagal generate token dari server', 'error');
                        }
                    } catch(e) {
                        this.showToast('Koneksi terputus saat membuat token', 'error');
                    }
                },
                copyInstallCommand() {
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(this.installCommand);
                    } else {
                        // Fallback for non-HTTPS environments
                        const textArea = document.createElement("textarea");
                        textArea.value = this.installCommand;
                        textArea.style.position = "fixed";
                        textArea.style.left = "-999999px";
                        document.body.appendChild(textArea);
                        textArea.focus();
                        textArea.select();
                        try {
                            document.execCommand('copy');
                        } catch (err) {
                            console.error('Copy fallback failed', err);
                        }
                        document.body.removeChild(textArea);
                    }
                    this.showToast('Install command copied to clipboard!', 'success');
                },
                showToast(message, type = 'info') {
                    const id = Date.now();
                    this.toasts.push({ id, message, type });
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(t => t.id !== id);
                    }, 3000);
                },
                fetchAgents() {
                    fetch(`/api/web/agents`)
                        .then(res => res.json())
                        .then(data => {
                            this.agents = data;
                        })
                        .catch(err => console.error('Error fetching agents:', err));
                },
                async deleteAgent(agent) {
                    const targetName = agent.hostname || agent.id || agent.ip_address || 'agen';
                    if(!confirm(`Apakah Anda yakin ingin menghapus ${targetName} dari daftar fleet?`)) return;
                    
                    const deleteKey = agent.id || agent.hostname || agent.ip_address;
                    if (!deleteKey) return;
                    
                    try {
                        const res = await fetch(`/api/web/agents/${encodeURIComponent(deleteKey)}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                        if (res.ok || res.status === 204 || res.status === 404) {
                            this.agents = this.agents.filter(a => a !== agent);
                            this.showToast(`Agen ${targetName} berhasil dihapus dari fleet.`, 'success');
                            setTimeout(() => this.fetchAgents(), 1000); 
                        } else {
                            this.showToast(`Gagal menghapus agen ${targetName}.`, 'error');
                        }
                    } catch(err) {
                        this.showToast(`Koneksi terputus saat menghapus agen ${targetName}.`, 'error');
                    }
                },
                async generateForensicPackage() {
                    if (!this.forensicTarget) {
                        this.showToast('Silakan pilih target server terlebih dahulu.', 'warning');
                        return;
                    }
                    if (this.selectedLogs.length === 0) {
                        this.showToast('Silakan pilih minimal 1 artefak log untuk diekstraksi.', 'warning');
                        return;
                    }
                    
                    this.isGenerating = true;
                    this.generationProgress = 0;
                    this.showToast('Memulai akuisisi forensik pada ' + this.forensicTarget + '...', 'info');
                    
                    const progressInterval = setInterval(() => {
                        if (this.generationProgress < 90) {
                            this.generationProgress += Math.floor(Math.random() * 10) + 5;
                        }
                    }, 500);

                    try {
                        const res = await fetch(`/api/web/agents/${this.forensicTarget}/forensics/generate`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                logs: this.selectedLogs
                            })
                        });
                        
                        if(res.ok) {
                            const newReport = await res.json();
                            this.reports.unshift(newReport);
                            this.showToast('Akuisisi selesai. Hash SHA-256 telah digenerate.', 'success');
                            this.forensicTarget = '';
                            this.selectedLogs = [];
                        } else {
                            throw new Error('Gagal melakukan ekstraksi');
                        }
                    } catch (e) {
                        this.showToast('Terjadi kesalahan saat menghubungi Master Node.', 'error');
                    } finally {
                        clearInterval(progressInterval);
                        this.generationProgress = 100;
                        setTimeout(() => {
                            this.isGenerating = false;
                        }, 500);
                    }
                },
                fallbackDownload(report) {
                    this.showToast('Mengunduh format HTML...', 'info');
                    window.location.href = `/api/v1/utilities/html/${report.id}`;
                },
                async deleteReport(reportId) {
                    if (confirm('Are you sure you want to delete this forensic report?')) {
                        // In a real app we'd DELETE it from backend too
                        this.reports = this.reports.filter(r => r.id !== reportId);
                        localStorage.setItem('dfir_reports', JSON.stringify(this.reports));
                        this.showToast('Report deleted successfully', 'success');
                    }
                },
                startMetricsSimulation() {
                    // Start real-time metrics for dashboard
                    setInterval(async () => {
                        if (!this.activeServer) return;
                        
                        try {
                            const res = await fetch(`/api/web/agents/${this.activeServer.hostname}/metrics`);
                            if (res.ok) {
                                const data = await res.json();
                                this.metrics = {
                                    cpu: Number(data.cpu).toFixed(1),
                                    cpuCount: data.cpu_count || 1,
                                    ram: Number(data.ram).toFixed(1),
                                    ramUsedGB: data.ram_used_gb ? Number(data.ram_used_gb).toFixed(1) : null,
                                    ramTotal: data.ram_total ? Number(data.ram_total).toFixed(1) : null,
                                    disk: Number(data.disk).toFixed(1),
                                    netIn: Number(data.net_in).toFixed(2),
                                    netOut: Number(data.net_out).toFixed(2)
                                };
                            }
                        } catch (err) {
                            console.error('Failed to fetch metrics', err);
                        }
                    }, 2000);
                    
                    
                    // Fetch real logs from backend
                    setInterval(async () => {
                        try {
                            const url = this.activeServer 
                                ? `/api/web/logs/recent?agent_id=${this.activeServer.id}&limit=20`
                                : `/api/web/logs/recent?limit=20`;
                                
                            const res = await fetch(url);
                            if (res.ok) {
                                const data = await res.json();
                                
                                const newSecEvents = data.map(log => {
                                    // simple heuristic to parse type
                                    let type = log.threat_level || 'INFO';
                                    if(log.log_message.toLowerCase().includes('failed password')) type = 'FAILED_LOGIN';
                                    if(log.log_message.toLowerCase().includes('brute force')) type = 'BRUTE_FORCE';
                                    if(log.log_message.toLowerCase().includes('ddos')) type = 'DDOS_DETECTED';
                                    
                                    let sev = 'low';
                                    if (type === 'FAILED_LOGIN' || type === 'BRUTE_FORCE' || type === 'DDOS_DETECTED' || log.threat_level === 'CRITICAL' || log.threat_level === 'HIGH') sev = 'high';
                                    else if (log.threat_level === 'MEDIUM') sev = 'medium';
                                    
                                    const sourceAgent = this.agents.find(a => a.id === log.agent_id);
                                    const agentHostname = sourceAgent ? sourceAgent.hostname : 'System';
                                    
                                    return {
                                        id: log.id,
                                        time: new Date(log.timestamp).toLocaleTimeString(),
                                        timestamp: log.timestamp,
                                        type: type,
                                        src: log.source_ip || '-',
                                        user: '-', // Can be parsed with regex later
                                        severity: sev,
                                        raw: log.log_message,
                                        targetHost: agentHostname
                                    };
                                });
                                
                                this.securityEvents = newSecEvents;
                                
                                if (this.lastLogTime) {
                                    const alerts = newSecEvents.filter(e => new Date(e.timestamp) > this.lastLogTime && e.severity === 'high');
                                    if (alerts.length > 0) {
                                        this.notifications = [...alerts, ...this.notifications].slice(0, 20);
                                        this.unreadCount += alerts.length;
                                    }
                                }
                                
                                if (newSecEvents.length > 0) {
                                    this.lastLogTime = new Date(newSecEvents[0].timestamp);
                                }
                            }
                        } catch(err) {
                            console.error('Failed to fetch logs', err);
                        }
                    }, 3000);
                    // Fetch connections and reports
                    setInterval(async () => {
                        if (!this.activeServer) return;
                        try {
                            const res = await fetch(`/api/web/agents/${this.activeServer.hostname}/connections`);
                            if (res.ok) {
                                this.activeConnections = await res.json();
                            }
                            
                            const resRep = await fetch(`/api/web/agents/${this.activeServer.hostname}/forensics/reports`);
                            if (resRep.ok) {
                                this.reports = await resRep.json();
                            }
                        } catch (err) {}
                    }, 5000);
                },
                init() {
                    this.installCommand = `curl -sSf http://${window.location.hostname}:8000/api/v1/agents/install.sh | bash`;
                    if (this.isLoggedIn) {
                        this.fetchAgents();
                        setInterval(() => this.fetchAgents(), 5000);
                        this.startMetricsSimulation();
                        this.initClock();
                    }
                }
            }
        }
    </script>
    
    <!-- Toast Notifications Container -->
    <div class="fixed bottom-5 right-5 z-50 flex flex-col gap-3">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="true" 
                 x-transition:enter="transition ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-x-10" 
                 x-transition:enter-end="opacity-100 translate-x-0" 
                 x-transition:leave="transition ease-in duration-200" 
                 x-transition:leave-start="opacity-100 translate-x-0" 
                 x-transition:leave-end="opacity-0 translate-x-10" 
                 class="px-4 py-3 rounded-lg shadow-2xl flex items-center space-x-3 w-full sm:w-auto sm:min-w-[300px] max-w-sm border"
                 :class="{
                     'bg-cyan-900/90 border-cyan-500 text-cyan-100': toast.type === 'info',
                     'bg-emerald-900/90 border-emerald-500 text-emerald-100': toast.type === 'success',
                     'bg-rose-900/90 border-rose-500 text-rose-100': toast.type === 'error'
                 }">
                 <i class="fa-solid" :class="{
                     'fa-circle-info': toast.type === 'info',
                     'fa-check-circle': toast.type === 'success',
                     'fa-triangle-exclamation': toast.type === 'error'
                 }"></i>
                 <span class="text-sm font-medium" x-text="toast.message"></span>
            </div>
        </template>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div x-show="sidebarOpen" x-cloak
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-30 md:hidden"></div>

    <!-- Left Sidebar Navigation -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
           class="fixed md:relative inset-y-0 left-0 w-64 bg-slate-950 border-r border-slate-800 flex flex-col shadow-2xl z-40 shrink-0 transform transition-transform duration-300 ease-in-out md:translate-x-0">
        <!-- Logo/Branding -->
        <div class="p-6 border-b border-slate-800 relative overflow-hidden">
            <!-- decorative accent -->
            <div class="absolute -top-4 -left-4 w-12 h-12 bg-cyan-500 rounded-full blur-2xl opacity-20"></div>
            
            <h1 class="text-2xl relative z-10 tracking-tight">
                <span class="font-bold text-white">Simpul</span><span class="font-light text-cyan-400">DFIR</span>
            </h1>
            <p class="text-[10px] text-slate-500 mt-1 tracking-[0.2em] uppercase relative z-10 font-semibold">by Simpul Labs</p>
        </div>
        
        <!-- Nav Links -->
        <nav class="flex-1 px-3 py-6 space-y-1 overflow-y-auto">
            <a href="#" @click.prevent="currentTab = 'fleet'; window.history.pushState({}, '', '/'); sidebarOpen = false" 
                :class="currentTab === 'fleet' ? 'bg-slate-800/50 text-cyan-400 border-cyan-400' : 'text-slate-400 hover:bg-slate-800/30 hover:text-slate-200 border-transparent'"
                class="w-full text-left px-4 py-3 rounded-lg transition-all duration-200 flex items-center group border-l-4">
                <i class="fa-solid fa-server w-7 text-center group-hover:scale-110 transition-transform"></i> 
                <span class="font-medium text-sm">Fleet Management</span>
            </a>
            
            <a href="#" @click.prevent="currentTab = 'cyberops'; window.history.pushState({}, '', '/cyberops'); sidebarOpen = false" 
                :class="currentTab === 'cyberops' ? 'bg-slate-800/50 text-cyan-400 border-cyan-400' : 'text-slate-400 hover:bg-slate-800/30 hover:text-slate-200 border-transparent'"
                class="w-full text-left px-4 py-3 rounded-lg transition-all duration-200 flex items-center group border-l-4">
                <i class="fa-solid fa-shield-halved w-7 text-center group-hover:scale-110 transition-transform"></i> 
                <span class="font-medium text-sm">Cyber Ops</span>
            </a>
            
            <a href="#" @click.prevent="currentTab = 'forensics'; window.history.pushState({}, '', '/forensics'); sidebarOpen = false" 
                :class="currentTab === 'forensics' ? 'bg-slate-800/50 text-cyan-400 border-cyan-400' : 'text-slate-400 hover:bg-slate-800/30 hover:text-slate-200 border-transparent'"
                class="w-full text-left px-4 py-3 rounded-lg transition-all duration-200 flex items-center group border-l-4">
                <i class="fa-solid fa-microscope w-7 text-center group-hover:scale-110 transition-transform"></i> 
                <span class="font-medium text-sm">Digital Forensics</span>
            </a>
        </nav>
        
        <!-- System Status Mini-Widget -->
        <div class="m-4 rounded-xl bg-gradient-to-b from-slate-900 to-slate-950 border border-slate-800/80 overflow-hidden shadow-lg" x-data="{ 
            cpu: 0, ram: 0, disk: 0, 
            ramUsed: 0, ramTotal: 0, diskUsed: 0, diskTotal: 0,
            cpuCount: 0, cpuPhys: 0, hostname: '...', username: '...', osInfo: '...',
            init() { 
                const poll = async () => { 
                    try { 
                        const r = await fetch(`/api/web/system/status`); 
                        const d = await r.json(); 
                        this.cpu = d.cpu; this.ram = d.ram; this.disk = d.disk;
                        this.ramUsed = d.ram_used_gb; this.ramTotal = d.ram_total_gb;
                        this.diskUsed = d.disk_used_gb; this.diskTotal = d.disk_total_gb;
                        this.cpuCount = d.cpu_count; this.cpuPhys = d.cpu_count_phys;
                        this.hostname = d.hostname; this.username = d.username || 'unknown'; this.osInfo = d.os;
                    } catch(e){} 
                };
                poll();
                setInterval(poll, 2000); 
            } 
        }">
            <!-- Header -->
            <div class="px-4 pt-3.5 pb-2.5 border-b border-slate-800/60">
                <div class="flex items-center space-x-2.5">
                    <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center shadow-md">
                        <i class="fa-solid fa-server text-[10px] text-white"></i>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold text-white tracking-wide">Master Node</div>
                        <div class="text-[9px] text-slate-500 font-mono" x-text="username + '@' + hostname"></div>
                    </div>
                </div>
            </div>

            <div class="px-4 py-3 space-y-3">
                <!-- Specs Badge -->
                <div class="flex flex-wrap gap-1.5">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-slate-800/80 text-[8px] font-mono text-cyan-400 border border-slate-700/50">
                        <i class="fa-solid fa-microchip mr-1 text-[7px]"></i><span x-text="cpuPhys + 'C/' + cpuCount + 'T'"></span>
                    </span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-slate-800/80 text-[8px] font-mono text-emerald-400 border border-slate-700/50">
                        <i class="fa-solid fa-memory mr-1 text-[7px]"></i><span x-text="ramTotal + ' GB'"></span>
                    </span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-slate-800/80 text-[8px] font-mono text-amber-400 border border-slate-700/50">
                        <i class="fa-solid fa-hard-drive mr-1 text-[7px]"></i><span x-text="diskTotal + ' GB'"></span>
                    </span>
                </div>

                <!-- CPU -->
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-[10px] text-slate-500 font-medium">CPU</span>
                        <span class="text-[10px] font-bold font-mono" :class="cpu > 80 ? 'text-rose-400' : cpu > 50 ? 'text-amber-400' : 'text-cyan-400'" x-text="(cpu || 0).toFixed(1) + '%'"></span>
                    </div>
                    <div class="w-full bg-slate-800/80 rounded-full h-1.5 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700 ease-out" 
                             :class="cpu > 80 ? 'bg-gradient-to-r from-rose-500 to-red-400' : cpu > 50 ? 'bg-gradient-to-r from-amber-500 to-yellow-400' : 'bg-gradient-to-r from-cyan-500 to-blue-400'"
                             :style="`width: ${cpu}%`"></div>
                    </div>
                </div>

                <!-- RAM -->
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-[10px] text-slate-500 font-medium">RAM</span>
                        <span class="text-[10px] font-mono" :class="ram > 80 ? 'text-rose-400' : 'text-emerald-400'">
                            <span class="font-bold" x-text="(ram || 0).toFixed(1) + '%'"></span>
                            <span class="text-slate-600 text-[8px] ml-0.5" x-text="'(' + ramUsed + '/' + ramTotal + 'G)'"></span>
                        </span>
                    </div>
                    <div class="w-full bg-slate-800/80 rounded-full h-1.5 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700 ease-out"
                             :class="ram > 80 ? 'bg-gradient-to-r from-rose-500 to-red-400' : 'bg-gradient-to-r from-emerald-500 to-teal-400'"
                             :style="`width: ${ram}%`"></div>
                    </div>
                </div>

                <!-- Disk -->
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-[10px] text-slate-500 font-medium">Disk</span>
                        <span class="text-[10px] font-mono" :class="disk > 85 ? 'text-rose-400' : 'text-amber-400'">
                            <span class="font-bold" x-text="(disk || 0).toFixed(1) + '%'"></span>
                            <span class="text-slate-600 text-[8px] ml-0.5" x-text="'(' + diskUsed + '/' + diskTotal + 'G)'"></span>
                        </span>
                    </div>
                    <div class="w-full bg-slate-800/80 rounded-full h-1.5 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700 ease-out"
                             :class="disk > 85 ? 'bg-gradient-to-r from-rose-500 to-red-400' : 'bg-gradient-to-r from-amber-500 to-yellow-400'"
                             :style="`width: ${disk}%`"></div>
                    </div>
                </div>

                <!-- OS Info -->
                <div class="pt-1 border-t border-slate-800/50">
                    <div class="text-[8px] text-slate-600 font-mono truncate" x-text="'OS: ' + osInfo"></div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-slate-900 via-[#0f172a] to-slate-950">
        
        <!-- Top Header -->
        <header class="glass border-b border-slate-800/80 h-16 flex items-center justify-between px-4 md:px-6 z-10 shrink-0">
            <div class="flex items-center space-x-3 md:space-x-4">
                <button @click="sidebarOpen = true" class="md:hidden text-slate-400 hover:text-white p-2 rounded-lg bg-slate-900/50 border border-slate-800 transition-colors">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="flex items-center space-x-2 text-sm bg-slate-950/50 px-2 md:px-3 py-1.5 rounded-md border border-slate-800" x-data="{ time: 'Loading...', sync: false, init() { setInterval(async () => { try { const r = await fetch(`/api/web/system/status`); const d = await r.json(); this.time = d.time; this.sync = d.ntp_sync; } catch(e){} }, 1000); } }">
                    <i class="fa-solid fa-clock text-cyan-500/70 hidden sm:inline"></i>
                    <span class="font-mono text-slate-300 text-[10px] md:text-xs" x-text="time"></span>
                    <div class="h-3 w-px bg-slate-700 mx-1 md:mx-2 hidden sm:block"></div>
                    <span class="flex items-center text-[10px] md:text-xs font-medium" :class="sync ? 'text-emerald-400' : 'text-slate-500'">
                        <i class="fa-solid mr-1 text-[10px]" :class="sync ? 'fa-check-circle' : 'fa-times-circle'"></i> <span class="hidden sm:inline">NTP Sync</span>
                    </span>
                </div>
            </div>
            
            <div class="flex items-center space-x-6">
                <!-- Active Alerts -->
                <div x-data="{ dropdownOpen: false }" class="relative">
                    <button @click="dropdownOpen = !dropdownOpen; if(dropdownOpen) unreadCount = 0" @click.outside="dropdownOpen = false" class="relative text-slate-400 hover:text-white transition-colors cursor-pointer outline-none">
                        <i class="fa-solid fa-bell text-lg"></i>
                        <span x-show="unreadCount > 0" x-cloak class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-rose-500 rounded-full border-2 border-slate-900 animate-pulse"></span>
                    </button>
                    <!-- Dropdown -->
                    <div x-show="dropdownOpen" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-1"
                         class="absolute right-0 sm:right-0 -mr-2 sm:mr-0 mt-3 w-[calc(100vw-2rem)] sm:w-80 max-w-sm bg-slate-900 border border-slate-700 rounded-lg shadow-xl z-50 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-800 bg-slate-950/50 flex justify-between items-center">
                            <h3 class="text-sm font-semibold text-white">Notifications</h3>
                            <button @click="notifications = []; unreadCount = 0" class="text-[10px] text-slate-400 hover:text-cyan-400 transition-colors">Clear All</button>
                        </div>
                        <div class="max-h-64 overflow-y-auto p-2">
                            <template x-if="notifications.length === 0">
                                <div class="px-3 py-6 text-center text-slate-500 text-sm">
                                    <i class="fa-solid fa-check-circle text-3xl mb-3 text-slate-700"></i>
                                    <p>You're all caught up!</p>
                                </div>
                            </template>
                            <template x-for="notif in notifications" :key="notif.id">
                                <div class="px-3 py-2 mb-1 bg-slate-800/50 hover:bg-slate-800 rounded border border-slate-700/50 transition-colors cursor-pointer">
                                    <div class="flex items-start">
                                        <div class="mt-0.5 mr-2">
                                            <i class="fa-solid fa-triangle-exclamation text-rose-500 text-xs"></i>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-slate-200" x-text="notif.type"></p>
                                            <p class="text-[10px] text-slate-400 mt-0.5" x-text="`Source: ${notif.src} \u2192 Target: ${notif.targetHost}`"></p>
                                            <p class="text-[9px] text-slate-500 mt-1" x-text="notif.time"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                
                <!-- Admin Profile -->
                <div x-data="{ open: false }" class="relative border-l border-slate-800/80 pl-6">
                    <div @click="open = !open" @click.outside="open = false" class="flex items-center space-x-3 cursor-pointer group outline-none">
                        <div class="text-right">
                            <p class="text-slate-200 font-semibold text-sm leading-none group-hover:text-white transition-colors">Admin Root</p>
                            <p class="text-cyan-500/70 text-[10px] mt-1 font-mono uppercase tracking-wider">Superuser</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-600 to-blue-800 flex items-center justify-center text-white font-bold text-sm shadow-lg ring-2 ring-slate-800 group-hover:ring-cyan-500/50 transition-all">
                            <i class="fa-solid fa-user-astronaut text-lg"></i>
                        </div>
                    </div>
                    <!-- Dropdown -->
                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                         class="absolute right-0 mt-4 w-64 bg-slate-950/95 backdrop-blur-xl border border-slate-800 rounded-xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.7)] z-50 overflow-hidden">
                        
                        <div class="px-5 py-4 bg-gradient-to-br from-slate-900 to-slate-950 border-b border-slate-800">
                            <p class="text-white font-bold text-sm">Administrator</p>
                            <p class="text-slate-400 text-xs mt-0.5">admin@simpul-dfir.local</p>
                        </div>

                        <div class="p-2 space-y-1">
                            <a href="#" @click.prevent="open = false; settingsTab = 'profile'; showSettingsModal = true" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800/80 transition-all group">
                                <div class="w-8 h-8 rounded-md bg-slate-800 flex items-center justify-center mr-3 group-hover:bg-cyan-500/20 group-hover:text-cyan-400 transition-colors">
                                    <i class="fa-solid fa-user-shield"></i>
                                </div>
                                <span>My Profile</span>
                            </a>
                            <a href="#" @click.prevent="open = false; settingsTab = 'security'; showSettingsModal = true" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800/80 transition-all group">
                                <div class="w-8 h-8 rounded-md bg-slate-800 flex items-center justify-center mr-3 group-hover:bg-blue-500/20 group-hover:text-blue-400 transition-colors">
                                    <i class="fa-solid fa-sliders"></i>
                                </div>
                                <span>Settings & Security</span>
                            </a>
                        </div>
                        
                        <div class="border-t border-slate-800/80 bg-slate-900/30 p-2">
                            <a href="#" @click.prevent="open = false; logout()" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 transition-all group">
                                <div class="w-8 h-8 rounded-md flex items-center justify-center mr-3 group-hover:bg-rose-500/20 transition-colors">
                                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                </div>
                                <span>Sign out</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Dynamic Views -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 lg:p-8">
            @yield("content")

            <!-- Deploy Agent Modal -->
            <div x-show="showDeployModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6" @open-deploy.window="showDeployModal = true">
                <div x-show="showDeployModal" x-transition.opacity class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="showDeployModal = false"></div>
                
                <div x-show="showDeployModal" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative bg-slate-900 rounded-xl border border-slate-700 shadow-2xl w-full max-w-2xl overflow-hidden max-h-full flex flex-col">
                    
                    <div class="px-6 py-4 border-b border-slate-800 bg-slate-950/50 flex justify-between items-center shrink-0">
                        <h3 class="text-lg font-semibold text-white flex items-center">
                            <i class="fa-solid fa-server text-cyan-500 mr-3"></i> Add New Agent
                        </h3>
                        <button @click="showDeployModal = false" class="text-slate-400 hover:text-white transition-colors">
                            <i class="fa-solid fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <div class="p-4 sm:p-6 overflow-y-auto">
                        <p class="text-slate-300 mb-4 text-sm">Enter the IP address of the target server to generate a secure, one-touch deployment command bound to that machine.</p>
                        
                        <div class="mb-5 flex flex-col sm:flex-row gap-3">
                            <input type="text" x-model="deployTargetIp" placeholder="e.g. 192.168.128.112" class="flex-1 bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-lg px-4 py-2.5 text-sm text-white outline-none font-mono w-full">
                            <button @click="generateDeployToken()" class="bg-cyan-600 hover:bg-cyan-500 text-white font-semibold px-4 py-2.5 rounded-lg text-sm transition-colors flex items-center justify-center shrink-0 w-full sm:w-auto">
                                <i class="fa-solid fa-key mr-2"></i> Generate Key
                            </button>
                        </div>

                        <div class="bg-slate-950 border border-slate-800 rounded-lg p-4 relative group">
                            <code class="text-cyan-400 font-mono text-xs sm:text-sm break-all" x-text="installCommand"></code>
                            <button @click="copyInstallCommand()" class="absolute top-1/2 -translate-y-1/2 right-4 bg-slate-800 hover:bg-slate-700 text-slate-300 p-2 rounded transition-colors opacity-0 group-hover:opacity-100" title="Copy Command">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>
                        
                        <div class="mt-6 flex flex-col sm:flex-row sm:items-start bg-blue-900/20 border border-blue-500/20 p-4 rounded-lg">
                            <i class="fa-solid fa-shield-check text-blue-400 mt-0.5 mb-2 sm:mb-0 sm:mr-3 text-lg hidden sm:block"></i>
                            <div class="text-xs text-slate-300">
                                <p class="flex items-center"><i class="fa-solid fa-shield-check text-blue-400 mr-2 sm:hidden"></i><strong>IP-Bound One-Touch Provisioning</strong></p>
                                <p class="mt-1 text-slate-400">This script includes an encrypted secret token. Running this 1-liner on the target machine automatically downloads the agent, binds it to the specified IP, and starts the systemd service.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 border-t border-slate-800 bg-slate-950/50 flex justify-end shrink-0">
                        <button @click="showDeployModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-sm font-medium transition-colors w-full sm:w-auto">Close</button>
                    </div>
                </div>
            </div>

            <!-- Settings & Profile Modal -->
            <div x-show="showSettingsModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6">
                <div x-show="showSettingsModal" x-transition.opacity class="fixed inset-0 bg-slate-950/80 backdrop-blur-md" @click="showSettingsModal = false"></div>
                
                <div x-show="showSettingsModal" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative bg-slate-900 rounded-2xl border border-slate-700 shadow-[0_0_50px_-12px_rgba(0,0,0,0.8)] w-full max-w-3xl overflow-hidden flex flex-col md:flex-row min-h-[400px]">
                    
                    <!-- Sidebar Tabs -->
                    <div class="w-full md:w-64 bg-slate-950/80 border-b md:border-b-0 md:border-r border-slate-800 shrink-0 flex flex-col">
                        <div class="p-6 border-b border-slate-800/80">
                            <h3 class="text-lg font-semibold text-white tracking-wide">Configuration</h3>
                            <p class="text-[10px] text-slate-500 mt-1 font-mono uppercase tracking-widest">System & Account</p>
                        </div>
                        <div class="p-3 space-y-1 overflow-x-auto md:overflow-x-visible flex md:block">
                            <button @click="settingsTab = 'profile'" :class="settingsTab === 'profile' ? 'bg-cyan-500/10 text-cyan-400 border-cyan-500/50' : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-200 border-transparent'" class="w-full text-left px-4 py-3 rounded-xl transition-all duration-200 flex items-center border border-transparent whitespace-nowrap">
                                <i class="fa-solid fa-user-shield w-6 text-center"></i>
                                <span class="font-medium text-sm ml-2">My Profile</span>
                            </button>
                            <button @click="settingsTab = 'security'" :class="settingsTab === 'security' ? 'bg-rose-500/10 text-rose-400 border-rose-500/50' : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-200 border-transparent'" class="w-full text-left px-4 py-3 rounded-xl transition-all duration-200 flex items-center border border-transparent whitespace-nowrap">
                                <i class="fa-solid fa-key w-6 text-center"></i>
                                <span class="font-medium text-sm ml-2">Security</span>
                            </button>
                            <button @click="settingsTab = 'system'" :class="settingsTab === 'system' ? 'bg-blue-500/10 text-blue-400 border-blue-500/50' : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-200 border-transparent'" class="w-full text-left px-4 py-3 rounded-xl transition-all duration-200 flex items-center border border-transparent whitespace-nowrap">
                                <i class="fa-solid fa-sliders w-6 text-center"></i>
                                <span class="font-medium text-sm ml-2">System Settings</span>
                            </button>
                        </div>
                    </div>

                    <!-- Main Content Area -->
                    <div class="flex-1 flex flex-col relative bg-gradient-to-br from-slate-900 to-slate-950">
                        <!-- Close Button -->
                        <button @click="showSettingsModal = false" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full bg-slate-800/50 hover:bg-slate-700 text-slate-400 hover:text-white transition-colors z-10">
                            <i class="fa-solid fa-times"></i>
                        </button>
                        
                        <!-- Profile Tab -->
                        <div x-show="settingsTab === 'profile'" class="p-6 md:p-8 flex-1 overflow-y-auto">
                            <div class="flex items-center space-x-6 mb-8">
                                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-cyan-600 to-blue-800 flex items-center justify-center text-white text-3xl shadow-[0_0_20px_rgba(8,145,178,0.3)] ring-4 ring-slate-800">
                                    <i class="fa-solid fa-user-astronaut"></i>
                                </div>
                                <div>
                                    <h2 class="text-2xl font-bold text-white">Admin Root</h2>
                                    <div class="inline-flex items-center mt-2 px-2.5 py-1 rounded-full bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 text-[10px] font-mono tracking-wider">
                                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 mr-2 animate-pulse"></span> SUPERUSER
                                    </div>
                                </div>
                            </div>
                            
                            <div class="space-y-5">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-2">Display Name</label>
                                        <input type="text" value="Admin Root" class="w-full bg-slate-950/50 border border-slate-700 rounded-xl p-3 text-sm text-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-2">Email Address</label>
                                        <input type="email" value="admin@simpul-dfir.local" class="w-full bg-slate-950/50 border border-slate-700 rounded-xl p-3 text-sm text-slate-400 cursor-not-allowed outline-none" disabled>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-2">Theme Preference</label>
                                    <div class="flex space-x-3">
                                        <button class="px-4 py-2.5 rounded-lg border-2 border-cyan-500 bg-slate-800 text-cyan-400 text-sm font-medium flex items-center"><i class="fa-solid fa-moon mr-2"></i> Dark Mode</button>
                                        <button class="px-4 py-2.5 rounded-lg border-2 border-transparent bg-slate-800/50 text-slate-400 hover:text-slate-200 text-sm font-medium flex items-center opacity-50 cursor-not-allowed" title="Coming soon"><i class="fa-solid fa-sun mr-2"></i> Light Mode</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Security Tab -->
                        <div x-show="settingsTab === 'security'" style="display: none;" class="p-6 md:p-8 flex-1 overflow-y-auto">
                            <h2 class="text-xl font-bold text-white mb-2">Security Settings</h2>
                            <p class="text-sm text-slate-400 mb-8">Update your password and manage authentication methods.</p>
                            
                            <div class="bg-slate-950/50 border border-slate-800 rounded-2xl p-5 mb-6">
                                <h4 class="text-sm font-semibold text-white mb-4 flex items-center"><i class="fa-solid fa-lock text-slate-500 mr-2"></i> Change Password</h4>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-2">Current Password</label>
                                        <input type="password" x-model="oldPassword" placeholder="••••••••" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-sm text-white focus:border-rose-500 outline-none transition-all">
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-2">New Password</label>
                                            <input type="password" x-model="newPassword" placeholder="••••••••" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-sm text-white focus:border-rose-500 outline-none transition-all">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-2">Confirm Password</label>
                                            <input type="password" placeholder="••••••••" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-sm text-white focus:border-rose-500 outline-none transition-all">
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 flex justify-end">
                                    <button @click="changePassword()" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-sm font-semibold transition-all shadow-[0_0_15px_rgba(225,29,72,0.2)]">Update Password</button>
                                </div>
                            </div>
                        </div>

                        <!-- System Settings Tab -->
                        <div x-show="settingsTab === 'system'" style="display: none;" class="p-6 md:p-8 flex-1 overflow-y-auto">
                            <h2 class="text-xl font-bold text-white mb-2">System Configuration</h2>
                            <p class="text-sm text-slate-400 mb-8">Global parameters for the SimpulDFIR Master Node.</p>
                            
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-4 bg-slate-950/50 border border-slate-800 rounded-2xl">
                                    <div>
                                        <h4 class="text-sm font-semibold text-white">Strict IP Whitelisting</h4>
                                        <p class="text-[11px] text-slate-500 mt-1">Restrict agent connections to known subnets only.</p>
                                    </div>
                                    <button class="w-12 h-6 rounded-full bg-cyan-600 relative transition-colors shadow-inner">
                                        <span class="absolute right-1 top-1 w-4 h-4 bg-white rounded-full shadow transition-transform"></span>
                                    </button>
                                </div>
                                
                                <div class="flex items-center justify-between p-4 bg-slate-950/50 border border-slate-800 rounded-2xl">
                                    <div>
                                        <h4 class="text-sm font-semibold text-white">Auto-prune Stale Agents</h4>
                                        <p class="text-[11px] text-slate-500 mt-1">Remove agents from dashboard if offline for > 72 hours.</p>
                                    </div>
                                    <button class="w-12 h-6 rounded-full bg-slate-700 relative transition-colors shadow-inner">
                                        <span class="absolute left-1 top-1 w-4 h-4 bg-slate-400 rounded-full shadow transition-transform"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

</body>
</html>
