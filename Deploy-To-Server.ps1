# Script Otomasi Deploy ke Server VPS (Proxmox)
# IP: 192.168.128.111

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  SimpulDFIR - Auto Deploy ke Server MobaXterm  " -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Memulai koneksi SSH ke 192.168.128.111..." -ForegroundColor Yellow
Write-Host "Jika diminta, silakan masukkan password server Anda (r00twidaD)." -ForegroundColor Green
Write-Host ""

# Menjalankan urutan perintah instalasi penuh secara remote
ssh root@192.168.128.111 "cd ~/SimpulDFIR && echo 'Menerima kode terbaru dari GitHub...' && git pull origin main && echo 'Membangun ulang Docker...' && docker compose build --no-cache && echo 'Menyalakan ulang aplikasi...' && docker compose up -d && echo 'Selesai!'"

Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host " Deploy Selesai! Container seharusnya sudah Up. " -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""
Pause
