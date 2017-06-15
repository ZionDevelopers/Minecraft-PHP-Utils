@echo off
TITLE Minecraft Worlds Backupper
pushd "%CD%"
CD /D "%~dp0"
php Minecraft.Worlds.Backupper.php
pause