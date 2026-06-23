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
for /f "tokens=* delims= " %%A in ("!choice!") do set "choice=%%A"
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
set "commit_msg=バックアップ：!TS!"
goto do_backup

:custombackup
echo.
set "message="
set /p message="コミットメッセージを入力してください: "
if not defined message (
    for /f %%D in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd"') do set "message=更新：%%D"
)
cd /d "%~dp0" || goto error_path
if not exist .git goto not_git_repo
set "commit_msg=!message!"
goto do_backup

:do_backup
git add -A
git diff --cached --quiet
if %errorlevel%==0 goto nothing_to_commit
call :show_staged_files
git commit -m "!commit_msg!"
if errorlevel 1 goto commit_failed
call :show_committed_files
echo.
echo リモートの変更を取り込みます（pull）...
git -c http.postBuffer=52428800 pull --no-edit
if errorlevel 1 goto pull_failed
echo push します（現在のブランチ）...
git -c http.postBuffer=52428800 push origin HEAD
if errorlevel 1 goto push_failed
call :write_backup_log
goto end

:show_staged_files
echo.
echo ========================================
echo   バックアップ対象ファイル
echo ========================================
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0git-backup-list.ps1" staged
echo ========================================
exit /b 0

:show_committed_files
echo.
echo ========================================
echo   今回バックアップしたファイル
echo ========================================
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0git-backup-list.ps1" committed
echo ========================================
exit /b 0

:write_backup_log
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0git-backup-list.ps1" log
exit /b 0

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
if exist "git-backup-直近一覧.txt" (
    echo.
    echo 直近のバックアップ一覧: git-backup-直近一覧.txt
)
pause
exit /b 0

:commit_failed
echo [エラー] git commit に失敗しました。
pause
exit /b 1

:pull_failed
echo [エラー] git pull に失敗しました。
echo 競合・認証・PCの Git 設定（http.postBuffer が大きすぎると Out of memory）の可能性があります。
echo ターミナルで git status を確認してください。
pause
exit /b 1

:push_failed
echo [エラー] git push に失敗しました。ネットワーク・認証・リモート設定をご確認ください。
echo Out of memory のときは PC 全体の Git 設定 http.postBuffer を見直してください（例: 52428800）。
echo ローカルにはコミット済みです。git-backup-直近一覧.txt でファイル名を確認できます。
call :write_backup_log
pause
exit /b 1

:end
echo.
echo ========================================
echo   バックアップ処理が完了しました
echo ========================================
echo   ファイル名一覧: git-backup-直近一覧.txt
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
