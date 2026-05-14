@echo off
chcp 65001 > nul
setlocal enabledelayedexpansion

:: この .bat と同じフォルダを Git リポジトリのルートとみなす（フォルダを移してもパス修正不要）
:: %~dp0 = このバッチがあるディレクトリ（末尾 \ 付き）

echo.
echo ========================================
echo   GitHub へのバックアップ（hp-amami-pr-1）
echo ========================================
echo.
echo 1. クイックバックアップ（自動メッセージ）
echo 2. カスタムメッセージ入力
echo 3. キャンセル
echo.
set /p choice="選択してください (1-3): "
:: 前後の空白を除く（for /f は先頭の区切り空白も落とすので tokens=* で全体を1トークンに）
for /f "tokens=* delims= " %%A in ("!choice!") do set "choice=%%A"
:: IME の全角数字 １２３ を半角 123 に直す（全角で入力しても有効にする）
set "choice=!choice:１=1!"
set "choice=!choice:２=2!"
set "choice=!choice:３=3!"

if "%choice%"=="1" goto quickbackup
if "%choice%"=="2" goto custombackup
if "%choice%"=="3" goto cancel
goto invalid

:quickbackup
cd /d "%~dp0" || goto error_path
if not exist .git goto not_git_repo
for /f %%D in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HH-mm"') do set "TS=%%D"
git add -A
git diff --cached --quiet
if %errorlevel%==0 goto nothing_to_commit
git commit -m "バックアップ：!TS!"
echo.
echo リモートの変更を取り込みます（pull）...
git pull --no-edit
if errorlevel 1 goto pull_failed
echo push します（現在のブランチ）...
git push origin HEAD
if errorlevel 1 goto push_failed
goto end

:custombackup
echo.
set "message="
set /p message="コミットメッセージを入力してください: "
if not defined message (
    for /f %%D in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd"') do set "message=更新：%%D"
)
cd /d "%~dp0" || goto error_path
if not exist .git goto not_git_repo
git add -A
git diff --cached --quiet
if %errorlevel%==0 goto nothing_to_commit
git commit -m "!message!"
echo.
echo リモートの変更を取り込みます（pull）...
git pull --no-edit
if errorlevel 1 goto pull_failed
echo push します（現在のブランチ）...
git push origin HEAD
if errorlevel 1 goto push_failed
goto end

:error_path
echo [エラー] バッチのあるフォルダへ移動できませんでした。
echo パス: %~dp0
pause
exit /b 1

:not_git_repo
echo [エラー] このフォルダは Git リポジトリではありません。
echo git init とリモート設定を行ってください。
pause
exit /b 1

:nothing_to_commit
echo 変更がありません。コミット・バックアップは不要です。
pause
exit /b 0

:pull_failed
echo [エラー] git pull に失敗しました。
echo 競合や未コミットの状態の可能性があります。ターミナルで git status を確認してください。
pause
exit /b 1

:push_failed
echo [エラー] git push に失敗しました。ネットワーク・認証・リモート設定をご確認ください。
pause
exit /b 1

:end
echo.
echo ========================================
echo   バックアップ処理が完了しました
echo ========================================
echo.
pause
exit /b 0

:cancel
echo キャンセルしました
pause
exit /b 0

:invalid
echo 無効な選択です
pause
exit /b 1
