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
goto end

:show_staged_files
echo.
echo ========================================
echo   バックアップ対象ファイル
echo ========================================
set "LIST=%TEMP%\hp-amami-git-backup-staged.txt"
git -c core.quotepath=false diff --cached --name-status > "%LIST%" 2>nul
set "file_count=0"
for /f "usebackq delims=" %%F in ("%LIST%") do (
    echo   %%F
    set /a file_count+=1
)
if exist "%LIST%" del "%LIST%" >nul 2>&1
if !file_count!==0 (
    echo   （対象ファイルなし）
) else (
    echo.
    echo   合計: !file_count! 件
)
echo ========================================
exit /b 0

:show_committed_files
echo.
echo ========================================
echo   今回バックアップしたファイル
echo ========================================
set "LIST=%TEMP%\hp-amami-git-backup-done.txt"
git -c core.quotepath=false show --pretty=format: --name-only HEAD > "%LIST%" 2>nul
set "done_count=0"
for /f "usebackq delims=" %%F in ("%LIST%") do (
    if not "%%F"=="" (
        echo   %%F
        set /a done_count+=1
    )
)
if exist "%LIST%" del "%LIST%" >nul 2>&1
if !done_count!==0 (
    echo   （ファイル名を取得できませんでした）
) else (
    echo.
    echo   合計: !done_count! 件
)
echo ========================================
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
