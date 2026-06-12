@echo off
chcp 65001 >nul
rem ====================================================================
rem  リンク一覧.txt のバックアップを tbl-url-bk フォルダに作成します。
rem  使い方: このファイルをダブルクリックするだけ。
rem  保存名:  tbl-url-bk\リンク一覧_YYYYMMDD_HHMMSS.txt
rem ====================================================================
cd /d "%~dp0"

if not exist "リンク一覧.txt" (
  echo [エラー] リンク一覧.txt が見つかりません。
  echo このバッチは index.html と同じフォルダに置いてください。
  pause
  exit /b 1
)

if not exist "tbl-url-bk" mkdir "tbl-url-bk"

for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmmss"') do set TS=%%i

copy /Y "リンク一覧.txt" "tbl-url-bk\リンク一覧_%TS%.txt" >nul
if errorlevel 1 (
  echo [エラー] バックアップの作成に失敗しました。
) else (
  echo バックアップを作成しました:
  echo   tbl-url-bk\リンク一覧_%TS%.txt
)
echo.
pause
