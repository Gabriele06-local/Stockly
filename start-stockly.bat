@echo off
REM Stockly - one-click local dev server (no Docker needed)
REM Usage: double-click this file, then open http://127.0.0.1:8000/login

set "PHPDIR=%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe"
set "PATH=%PHPDIR%;%PATH%"

cd /d "%~dp0"

where php >nul 2>nul
if errorlevel 1 (
  echo [ERRORE] PHP non trovato in %PHPDIR%
  echo Installalo con: winget install -e --id PHP.PHP.8.3
  pause
  exit /b 1
)

php --version | findstr "8.3" >nul || echo [ATTENZIONE] Versione PHP diversa da 8.3

if not exist "database\database.sqlite" (
  echo [INFO] Creo database sqlite...
  type nul > "database\database.sqlite"
)

echo [INFO] Pulizia cache...
php artisan optimize:clear >nul 2>&1

echo [INFO] Migrazioni + seed di demo ^(solo se tabelle mancanti^)...
php artisan migrate --seed --force

echo.
echo ============================================================
echo  Stockly online:  http://127.0.0.1:8000/login
echo  admin@stockly.test / password   ^(admin^)
echo  staff@stockly.test / password   ^(staff^)
echo  Per fermare: Ctrl+C in questa finestra
echo ============================================================
echo.
php artisan serve --host=127.0.0.1 --port=8000
