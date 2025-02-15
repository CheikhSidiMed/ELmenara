@echo off
:: Définition des variables
setlocal
set TIMESTAMP=%DATE:~6,4%-%DATE:~3,2%-%DATE:~0,2%
set BACKUP_DIR=C:\xampp\mysql\backup
set MYSQLDUMP_PATH=C:\xampp\mysql\bin\mysqldump.exe
set DB_NAME=employeeleavedb
set DB_USER=root
set DB_PASSWORD=
set BACKUP_FILE=%BACKUP_DIR%\%DB_NAME%_%TIMESTAMP%.sql

:: Créer le dossier de sauvegarde s'il n'existe pas
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

:: Exécuter la sauvegarde
"%MYSQLDUMP_PATH%" -u%DB_USER% -p%DB_PASSWORD% %DB_NAME% > "%BACKUP_FILE%"

:: Vérifier si la sauvegarde a réussi
if %ERRORLEVEL% equ 0 (
    echo Sauvegarde réussie : %BACKUP_FILE%
) else (
    echo Erreur lors de la sauvegarde
)

:: Supprimer les sauvegardes de plus de 7 jours (optionnel)
forfiles /p "%BACKUP_DIR%" /s /m *.sql /d -7 /c "cmd /c del @file"

exit
