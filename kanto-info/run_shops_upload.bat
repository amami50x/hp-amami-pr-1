@echo off
cd /d "C:\Users\User\OneDrive\デスクトップ\new-hp\kanto-info"

:: Excel並べ替え
python sort_excel.py >nul 2>&1

:: HTML生成
python shop-html.py >nul 2>&1

:: さくらインターネットへアップロード
python upload_html.py >nul 2>&1

echo ✅ 処理が完了しました。
pause