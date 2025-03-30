@echo off
setlocal enabledelayedexpansion

echo ===================================================
echo    Sephp Monitoring System with LocalTunnel
echo ===================================================
echo.

REM Set path to PHP executable (adjust as needed)
set PHP_PATH=php

REM Load environment variables from .env
echo Loading environment variables...
for /f "tokens=*" %%a in (.env) do (
    set line=%%a
    if not "!line:~0,1!"=="#" if not "!line!"=="" (
        for /f "tokens=1,2 delims==" %%b in ("!line!") do (
            set %%b=%%c
        )
    )
)

REM Check if Node.js is installed
where node >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo Error: Node.js is not installed. Please install Node.js first.
    echo You can download it from https://nodejs.org/
    pause
    exit /b 1
)

REM No need to check for localtunnel in PATH, we'll run it using npm directly

REM Check database connection
echo Checking database connection...
%PHP_PATH% -r "try { new PDO('mysql:host=localhost;dbname=sephp_monitoring', 'root', ''); echo 'Database connection successful.\n'; } catch(PDOException $e) { echo 'Database connection failed: '.$e->getMessage().'\n'; exit(1); }"
if %ERRORLEVEL% NEQ 0 (
    echo Please make sure MySQL is running and the database exists.
    pause
    exit /b 1
)

REM Start PHP WebSocket server in the background
start "PHP WebSocket Server" %PHP_PATH% server.php
echo PHP WebSocket server started...

REM Set subdomain from .env or use default
if defined LOCALTUNNEL_SUBDOMAIN (
    set SUBDOMAIN=%LOCALTUNNEL_SUBDOMAIN%
) else (
    set SUBDOMAIN=sephp-monitoring
)
echo Using subdomain: %SUBDOMAIN%

REM Start localtunnel and capture its output using npm directly
echo.
echo Starting localtunnel for webhook endpoint...
echo This may take a few moments...

REM Run npx localtunnel instead of lt command
start "Localtunnel" /B cmd /c "npx localtunnel --port 80 --subdomain %SUBDOMAIN% > localtunnel_output.log 2>&1"

REM Wait for localtunnel to initialize
timeout /t 10 /nobreak > nul

REM Check if localtunnel started successfully
findstr /C:"your url is:" localtunnel_output.log > lt_url.txt
if %ERRORLEVEL% NEQ 0 (
    echo Waiting for localtunnel to initialize...
    timeout /t 10 /nobreak > nul
    findstr /C:"your url is:" localtunnel_output.log > lt_url.txt
)

REM Extract the localtunnel URL
for /F "tokens=4 delims= " %%G in (lt_url.txt) do set LT_URL=%%G

if "%LT_URL%"=="" (
    echo Could not extract localtunnel URL. Checking log file for errors...
    type localtunnel_output.log
    echo.
    echo Possible issues:
    echo 1. The subdomain "%SUBDOMAIN%" may already be in use
    echo 2. LocalTunnel service might be temporarily unavailable
    echo 3. There might be network connectivity issues
    echo.
    echo Please try using a different subdomain by setting LOCALTUNNEL_SUBDOMAIN in .env file
    goto cleanup
)

echo.
echo ===================================================================
echo Your localtunnel URL is: %LT_URL%
echo.
echo Use this URL as your GitHub webhook: %LT_URL%/Sephp/public/webhook.php
echo ===================================================================
echo.

REM Update GitHub webhook if credentials are available
if defined GITHUB_TOKEN (
    if defined GITHUB_USERNAME (
        if defined GITHUB_REPO (
            echo GitHub credentials found. Updating webhook URL...
            %PHP_PATH% update_github_webhook.php %LT_URL%
        ) else (
            echo GITHUB_REPO not set in .env file. Skipping webhook URL update.
        )
    ) else (
        echo GITHUB_USERNAME not set in .env file. Skipping webhook URL update.
    )
) else (
    echo GITHUB_TOKEN not set in .env file. Skipping webhook URL update.
    echo.
    echo To enable automatic webhook updates, edit your .env file and add:
    echo - GITHUB_TOKEN: Your personal access token
    echo - GITHUB_USERNAME: Your GitHub username
    echo - GITHUB_REPO: The repository name
    echo - GITHUB_WEBHOOK_ID: Your webhook ID (optional)
)

:cleanup
REM Clean up temporary files
if exist lt_url.txt del lt_url.txt

echo.
echo System is running! The localtunnel URL will remain active as long as this window is open.
echo Press Ctrl+C to stop the servers when done.
echo.

REM Keep the script running
pause