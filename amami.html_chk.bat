@echo off
cd /d "%~dp0"
python check_html_links.py
start amami_checked.html
pause
