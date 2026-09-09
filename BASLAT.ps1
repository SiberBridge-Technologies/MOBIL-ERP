param([switch]$Open)
$ErrorActionPreference = 'Stop'
$projectRoot = $PSScriptRoot
$localRoot = Join-Path $projectRoot '.local'
function PortListening([int]$port) {
    $client = New-Object Net.Sockets.TcpClient
    try { $client.Connect('127.0.0.1', $port); return $true } catch { return $false } finally { $client.Dispose() }
}
if (!(PortListening 3308)) {
    Start-Process -FilePath 'C:\xampp\mysql\bin\mysqld.exe' -ArgumentList "--defaults-file=`"$localRoot\mysql\my.ini`"",'--bind-address=127.0.0.1','--console' -WindowStyle Hidden -RedirectStandardOutput "$localRoot\mysql-start.log" -RedirectStandardError "$localRoot\mysql-start-error.log"
}
if (!(PortListening 8088)) {
    Start-Process -FilePath 'C:\xampp\php\php.exe' -ArgumentList '-S','127.0.0.1:8088','-t',"`"$projectRoot\backend`"","`"$projectRoot\backend\router.php`"" -WindowStyle Hidden -RedirectStandardOutput "$localRoot\backend-start.log" -RedirectStandardError "$localRoot\backend-start-error.log"
}
if (!(PortListening 8081)) {
    Start-Process -FilePath 'C:\Program Files\nodejs\node.exe' -ArgumentList "`"$projectRoot\scripts\serve-web.cjs`"" -WindowStyle Hidden -RedirectStandardOutput "$localRoot\web.log" -RedirectStandardError "$localRoot\web-error.log"
}
Write-Host 'Panel: http://localhost:8088/admin/'
Write-Host 'Mobil onizleme: http://localhost:8081'
Write-Host 'Yerel hesaplar: .local\mobilerp2_local.json'
if ($Open) { Start-Process 'http://localhost:8088/admin/'; Start-Process 'http://localhost:8081' }
