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

if not exist "database\database.sqlite" (
  echo [INFO] Creo database sqlite...
  type nul > "database\database.sqlite"
)

echo [INFO] Pulizia cache...
php artisan optimize:clear >nul 2>&1

echo [INFO] Migrazioni...
php artisan migrate --force
if errorlevel 1 (
  echo [ERRORE] Migrazione fallita. Leggi il messaggio qui sopra.
  pause
  exit /b 1
)

echo [INFO] Controllo dati demo...
php artisan tinker --execute="exit(App\Models\User::query()->exists() ? 0 : 1);" >nul 2>&1
if errorlevel 1 (
  echo [INFO] Database vuoto: carico dati demo ^(ci vuole ~20 secondi^)...
  php artisan db:seed --force
) else (
  echo [INFO] Dati presenti, skip seed.
)

echo.
echo ============================================================
echo  Stockly online:  http://127.0.0.1:8000/login
echo  admin@stockly.test / password   ^(admin^)
echo  staff@stockly.test / password   ^(staff^)
echo  Se la pagina e' bianca: Ctrl+F5, oppure chiudi Docker
echo  Desktop ^(occupa la porta 8000^) e riavvia questo file.
echo  Per fermare: Ctrl+C in questa finestra
echo ============================================================
echo.
php artisan serve --host=127.0.0.1 --port=8000
if errorlevel 1 (
  echo.
  echo [ERRORE] Porta 8000 occupata o server crashato.
  echo Chiudi Docker Desktop o altri server e riprova.
  pause
)
