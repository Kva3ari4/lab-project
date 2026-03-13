@echo off
REM Запуск встроенного PHP-сервера для ИИС ППР
REM Дважды щёлкните по файлу или выполните: run-server.bat

cd /d "%~dp0"
where php >nul 2>&1
if %errorlevel% equ 0 (
    echo Запуск сервера: http://localhost:8000
    echo Откройте в браузере: http://localhost:8000/proverka.html
    echo Остановка: Ctrl+C
    php -S localhost:8000
    exit /b
)

if exist "C:\xampp\php\php.exe" (
    echo Запуск сервера: http://localhost:8000
    echo Откройте в браузере: http://localhost:8000/proverka.html
    echo Остановка: Ctrl+C
    C:\xampp\php\php.exe -S localhost:8000
    exit /b
)

for /d %%d in ("C:\laragon\bin\php\php*") do (
    if exist "%%d\php.exe" (
        echo Запуск сервера: http://localhost:8000
        echo Откройте в браузере: http://localhost:8000/proverka.html
        echo Остановка: Ctrl+C
        "%%d\php.exe" -S localhost:8000
        exit /b
    )
)

echo PHP не найден в PATH и в стандартных папках.
echo.
echo Варианты:
echo   1. Установите XAMPP (https://www.apachefriends.org/) и запустите этот bat снова.
echo   2. Добавьте PHP в PATH: Панель управления - Система - Доп. параметры - Переменные среды - Path - Добавить путь к папке с php.exe.
echo   3. Запускайте проект через OpenServer/XAMPP: укажите корень сайта на папку проекта.
pause
