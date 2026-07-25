@echo off
setlocal EnableExtensions
chcp 65001 >nul

REM ============================================================
REM SchoolPass - Inicio local completo para Windows + XAMPP
REM ============================================================

set "PROJECT_DIR=C:\xampp\htdocs\schoolpass"
set "PHP_EXE=C:\xampp\php\php.exe"
set "HOST=127.0.0.1"
set "PORT=8000"

REM Modos internos utilizados por este mismo BAT.
if /I "%~1"=="server" goto RUN_SERVER
if /I "%~1"=="queue" goto RUN_QUEUE
if /I "%~1"=="scheduler" goto RUN_SCHEDULER

title SchoolPass - Inicio completo

echo.
echo ============================================================
echo              INICIANDO SCHOOLPASS
echo ============================================================
echo.

REM ------------------------------------------------------------
REM Validaciones
REM ------------------------------------------------------------

if not exist "%PHP_EXE%" (
    echo [ERROR] No se encontro PHP en:
    echo         %PHP_EXE%
    echo.
    echo Edita PHP_EXE dentro de este archivo.
    pause
    exit /b 1
)

if not exist "%PROJECT_DIR%\artisan" (
    echo [ERROR] No se encontro artisan en:
    echo         %PROJECT_DIR%
    echo.
    echo Edita PROJECT_DIR dentro de este archivo.
    pause
    exit /b 1
)

if not exist "%PROJECT_DIR%\.env" (
    echo [ERROR] No existe el archivo:
    echo         %PROJECT_DIR%\.env
    pause
    exit /b 1
)

cd /d "%PROJECT_DIR%"

echo [1/4] Limpiando caches de Laravel...
"%PHP_EXE%" artisan optimize:clear
if errorlevel 1 (
    echo.
    echo [ERROR] Laravel no pudo limpiar las caches.
    pause
    exit /b 1
)

echo.
echo [2/4] Solicitando reinicio de workers anteriores...
"%PHP_EXE%" artisan queue:restart

echo.
echo [3/4] Iniciando procesos independientes...

start "SchoolPass - Servidor" cmd /k call "%~f0" server
timeout /t 2 /nobreak >nul

start "SchoolPass - Queue Worker" cmd /k call "%~f0" queue
timeout /t 1 /nobreak >nul

start "SchoolPass - Scheduler" cmd /k call "%~f0" scheduler

echo.
echo [4/4] Abriendo SchoolPass en el navegador...
start "" "http://%HOST%:%PORT%"

echo.
echo ============================================================
echo SchoolPass fue iniciado correctamente.
echo.
echo URL:
echo http://%HOST%:%PORT%
echo.
echo Procesos activos:
echo - Servidor Laravel
echo - Cola default y notifications
echo - Scheduler de Laravel
echo.
echo Para detenerlos ejecuta:
echo detener_schoolpass.bat
echo ============================================================
echo.

timeout /t 4 /nobreak >nul
exit /b 0


REM ============================================================
REM SERVIDOR LARAVEL
REM ============================================================
:RUN_SERVER
title SchoolPass - Servidor
cd /d "%PROJECT_DIR%"

echo.
echo ============================================================
echo SCHOOLPASS - SERVIDOR
echo http://%HOST%:%PORT%
echo ============================================================
echo.

:SERVER_LOOP
"%PHP_EXE%" artisan serve --host=%HOST% --port=%PORT%

echo.
echo [AVISO] El servidor se detuvo.
echo Se intentara reiniciar en 5 segundos.
echo Presiona Ctrl+C para cancelar definitivamente.
timeout /t 5 /nobreak >nul
goto SERVER_LOOP


REM ============================================================
REM WORKER DE COLAS
REM ============================================================
:RUN_QUEUE
title SchoolPass - Queue Worker
cd /d "%PROJECT_DIR%"

echo.
echo ============================================================
echo SCHOOLPASS - QUEUE WORKER
echo Colas: default,notifications
echo Conexion: database
echo ============================================================
echo.

:QUEUE_LOOP
"%PHP_EXE%" artisan queue:work database ^
    --queue=default,notifications ^
    --sleep=1 ^
    --tries=3 ^
    --timeout=180 ^
    --backoff=5 ^
    --max-time=3600

echo.
echo [AVISO] El worker termino o fue reiniciado.
echo Se iniciara nuevamente en 5 segundos.
echo Presiona Ctrl+C para cancelar definitivamente.
timeout /t 5 /nobreak >nul
goto QUEUE_LOOP


REM ============================================================
REM SCHEDULER DE LARAVEL
REM ============================================================
:RUN_SCHEDULER
title SchoolPass - Scheduler
cd /d "%PROJECT_DIR%"

echo.
echo ============================================================
echo SCHOOLPASS - SCHEDULER
echo Ejecutando tareas programadas de Laravel
echo ============================================================
echo.

:SCHEDULER_LOOP
"%PHP_EXE%" artisan schedule:work

echo.
echo [AVISO] El scheduler se detuvo.
echo Se intentara reiniciar en 5 segundos.
echo Presiona Ctrl+C para cancelar definitivamente.
timeout /t 5 /nobreak >nul
goto SCHEDULER_LOOP
