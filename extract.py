import sys
with open('core/resources/views/dashboard.blade.php', 'r', encoding='utf-8') as f:
    lines = f.readlines()

# fix the left menu buttons first in the loaded lines
for i, line in enumerate(lines):
    if '@click="currentTab = \'fleet\'"' in line:
        lines[i] = '            <a href="{{ route(\'dashboard\') }}" \n                class="w-full text-left px-4 py-3 rounded-lg transition-all duration-200 flex items-center group border-l-4 {{ request()->routeIs(\'dashboard\') ? \'bg-slate-800/50 text-cyan-400 border-cyan-400\' : \'text-slate-400 hover:bg-slate-800/30 hover:text-slate-200 border-transparent\' }}">\n                <i class="fa-solid fa-server w-7 text-center group-hover:scale-110 transition-transform"></i> \n                <span class="font-medium text-sm">Fleet Management</span>\n            </a>\n'
        lines[i+1] = ''
        lines[i+2] = ''
        lines[i+3] = ''
        lines[i+4] = ''
    elif '@click="currentTab = \'ops\'"' in line:
        lines[i] = '            <a href="{{ route(\'cyberops\') }}" \n                class="w-full text-left px-4 py-3 rounded-lg transition-all duration-200 flex items-center group border-l-4 {{ request()->routeIs(\'cyberops\') ? \'bg-slate-800/50 text-cyan-400 border-cyan-400\' : \'text-slate-400 hover:bg-slate-800/30 hover:text-slate-200 border-transparent\' }}">\n                <i class="fa-solid fa-shield-halved w-7 text-center group-hover:scale-110 transition-transform"></i> \n                <span class="font-medium text-sm">Cyber Ops</span>\n            </a>\n'
        lines[i+1] = ''
        lines[i+2] = ''
        lines[i+3] = ''
        lines[i+4] = ''
    elif '@click="currentTab = \'forensics\'"' in line:
        lines[i] = '            <a href="{{ route(\'forensics\') }}" \n                class="w-full text-left px-4 py-3 rounded-lg transition-all duration-200 flex items-center group border-l-4 {{ request()->routeIs(\'forensics\') ? \'bg-slate-800/50 text-cyan-400 border-cyan-400\' : \'text-slate-400 hover:bg-slate-800/30 hover:text-slate-200 border-transparent\' }}">\n                <i class="fa-solid fa-microscope w-7 text-center group-hover:scale-110 transition-transform"></i> \n                <span class="font-medium text-sm">Digital Forensics</span>\n            </a>\n'
        lines[i+1] = ''
        lines[i+2] = ''
        lines[i+3] = ''
        lines[i+4] = ''

# extract components
start_idx = -1
end_idx = -1
for i, line in enumerate(lines):
    if '<main ' in line:
        start_idx = i
    if '<!-- Deploy Agent Modal -->' in line:
        end_idx = i
        break

if start_idx != -1 and end_idx != -1:
    layout_lines = lines[:start_idx + 1] + ['            @yield("content")\n\n'] + lines[end_idx:]
    with open('core/resources/views/layouts/app.blade.php', 'w', encoding='utf-8') as f:
        f.writelines(layout_lines)
    print('Generated layout app.blade.php')

    # extract fleet
    fleet_lines = ['@extends("layouts.app")\n', '@section("content")\n']
    fleet_start = -1
    fleet_end = -1
    for i, line in enumerate(lines):
        if '<!-- MENU 1: FLEET MANAGEMENT -->' in line:
            fleet_start = i
        if '<!-- MENU 2: CYBER OPS -->' in line:
            fleet_end = i
            break
    
    if fleet_start != -1 and fleet_end != -1:
        fleet_content = lines[fleet_start:fleet_end]
        # remove x-show from fleet
        for j, fl in enumerate(fleet_content):
            if 'x-show="currentTab === \'fleet\'"' in fl:
                fleet_content[j] = fl.replace('x-show="currentTab === \'fleet\'" x-cloak', '')
        
        with open('core/resources/views/pages/fleet.blade.php', 'w', encoding='utf-8') as f:
            f.writelines(fleet_lines + fleet_content + ['@endsection\n'])
            
    # extract ops
    ops_lines = ['@extends("layouts.app")\n', '@section("content")\n']
    ops_start = -1
    ops_end = -1
    for i, line in enumerate(lines):
        if '<!-- MENU 2: CYBER OPS -->' in line:
            ops_start = i
        if '<!-- MENU 3: DIGITAL FORENSICS -->' in line:
            ops_end = i
            break
            
    if ops_start != -1 and ops_end != -1:
        ops_content = lines[ops_start:ops_end]
        for j, fl in enumerate(ops_content):
            if 'x-show="currentTab === \'ops\'"' in fl:
                ops_content[j] = fl.replace('x-show="currentTab === \'ops\'" x-cloak', '')
        with open('core/resources/views/pages/cyberops.blade.php', 'w', encoding='utf-8') as f:
            f.writelines(ops_lines + ops_content + ['@endsection\n'])
            
    # extract forensics
    forensics_lines = ['@extends("layouts.app")\n', '@section("content")\n']
    for_start = -1
    for_end = end_idx
    for i, line in enumerate(lines):
        if '<!-- MENU 3: DIGITAL FORENSICS -->' in line:
            for_start = i
            break
            
    if for_start != -1 and for_end != -1:
        for_content = lines[for_start:for_end]
        for j, fl in enumerate(for_content):
            if 'x-show="currentTab === \'forensics\'"' in fl:
                for_content[j] = fl.replace('x-show="currentTab === \'forensics\'" x-cloak', '')
        with open('core/resources/views/pages/forensics.blade.php', 'w', encoding='utf-8') as f:
            f.writelines(forensics_lines + for_content + ['@endsection\n'])

    print("Success")
