@echo off
title DEORIS System Startup
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "scripts\start-deoris-portal.ps1"
