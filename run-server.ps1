# Запуск встроенного PHP-сервера для ИИС ППР
# Использование: .\run-server.ps1   или   powershell -ExecutionPolicy Bypass -File run-server.ps1

$phpPaths = @(
    "php",
    "C:\xampp\php\php.exe",
    "C:\OpenServer\modules\php\PHP-8.2\php.exe",
    "C:\OpenServer\modules\php\PHP-8.1\php.exe",
    "C:\laragon\bin\php\php-8.2-Win32-vs16-x64\php.exe",
    "C:\laragon\bin\php\php-8.1-Win32-vs16-x64\php.exe",
    "$env:LOCALAPPDATA\Programs\PHP\php.exe",
    "C:\php\php.exe"
)

$phpExe = $null
foreach ($p in $phpPaths) {
    if ($p -eq "php") {
        try { $null = Get-Command php -ErrorAction Stop; $phpExe = "php"; break } catch {}
    } elseif (Test-Path $p) {
        $phpExe = $p
        break
    }
}

if (-not $phpExe) {
    Write-Host "PHP не найден. Установите PHP одним из способов:" -ForegroundColor Yellow
    Write-Host "  1. XAMPP: https://www.apachefriends.org/ — после установки путь: C:\xampp\php\php.exe"
    Write-Host "  2. Laragon: https://laragon.org/ — PHP входит в состав"
    Write-Host "  3. Официальный Windows-пакет: https://windows.php.net/download/"
    Write-Host ""
    Write-Host "Либо запускайте проект через уже установленный веб-сервер (OpenServer, XAMPP, Denwer),"
    Write-Host "указав корень сайта на папку public этого проекта."
    exit 1
}

Write-Host "Запуск сервера: http://localhost:8000" -ForegroundColor Green
Write-Host "Корень сайта: $PSScriptRoot" -ForegroundColor Gray
Write-Host "Остановка: Ctrl+C" -ForegroundColor Gray
Write-Host ""
Set-Location $PSScriptRoot
& $phpExe -S localhost:8000
