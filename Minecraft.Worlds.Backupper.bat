@echo off
pushd "%CD%"
CD /D "%~dp0"
php Minecraft.Worlds.Backupper.php
pause