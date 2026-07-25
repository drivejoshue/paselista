@echo off
setlocal EnableExtensions
chcp 65001 >nul

title SchoolPass - Detener procesos

echo.
echo ============================================================
echo             DETENIENDO SCHOOLPASS
echo ============================================================
echo.

taskkill /FI "WINDOWTITLE eq SchoolPass - Servidor*" /T /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq SchoolPass - Queue Worker*" /T /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq SchoolPass - Scheduler*" /T /F >nul 2>&1

echo Procesos detenidos:
echo - Servidor Laravel
echo - Queue Worker
echo - Scheduler
echo.
timeout /t 3 /nobreak >nul
exit /b 0
