@echo off
chcp 65001 > nul
setlocal

rem ============================================================
rem  リンク切れチェック（パソコン上で実行）
rem  ※ このファイルは no-up- なのでサーバーへは送りません。
rem  同じフォルダーの index.html / link-status.json を使います。
rem ============================================================

cd /d "%~dp0"

if not exist "index.html" (
  echo [エラー] このバットは hp-amami-pr-1 フォルダー内に置いてください。
  echo   今の場所: %~dp0
  pause
  exit /b 1
)

where py >nul 2>nul
if %errorlevel%==0 (
  py "no-up-link-status-check.py"
  goto done
)

where python >nul 2>nul
if %errorlevel%==0 (
  python "no-up-link-status-check.py"
  goto done
)

echo [エラー] Python が見つかりません。
pause
exit /b 1

:done
echo.
echo ------------------------------------------------------------
echo  完了。次にアップロードするのは このフォルダーの:
echo    link-status.json
echo  だけです（一覧を見るときは admin-link-check.html も）。
echo  ※ no-up- で始まるファイルはアップロードしません。
echo ------------------------------------------------------------
echo.
pause
