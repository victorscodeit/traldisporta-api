@echo off
start firefox.exe "http://localhost:8080/oms/morosos/report.php"
timeout /t 300 /nobreak >nul
taskkill /im firefox.exe /f