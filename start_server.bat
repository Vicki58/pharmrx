@echo off
title PharmRx Development Server (XAMPP PHP)
echo ========================================================
echo       PharmRx Pharmacy Management System
echo       Starting local PHP server on port 8000 (XAMPP)...
echo ========================================================
echo.
start http://localhost:8000/login.php
if exist "C:\xampp\php\php.exe" (
    "C:\xampp\php\php.exe" -S localhost:8000
) else (
    php -S localhost:8000
)
pause
