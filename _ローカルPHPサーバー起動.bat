@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion
rem ============================================================
rem  ローカルPHPサーバー起動（Local/LocalWP の PHP を利用）
rem  ・このフォルダ(hp-amami-pr-1)を http://localhost:8123/ で配信します
rem  ・PHP（クリック集計・分析レポート）がローカルで動きます
rem
rem  使い方:
rem    1) このバッチをダブルクリック
rem    2) ブラウザで下記を開く
rem         本体ページ : http://localhost:8123/index.html
rem         分析レポート: http://localhost:8123/cell-click-report.php?pw=amami-admin-2026
rem    3) 終了するときは、この黒い画面で Ctrl + C を押す
rem ============================================================

set "PHPEXE="
rem Local の PHP（新しいバージョン優先）を自動検出
for /f "delims=" %%P in ('dir /b /s /o-n "%APPDATA%\Local\lightning-services\php-*\bin\win32\php.exe" 2^>nul') do (
  if not defined PHPEXE set "PHPEXE=%%P"
)

if not defined PHPEXE (
  echo [エラー] PHP が見つかりませんでした。
  echo  Local（LocalWP）がインストールされているか確認してください。
  pause
  exit /b 1
)

echo 使用するPHP : "!PHPEXE!"
echo 配信フォルダ: "%~dp0"
echo.
echo  本体ページ  : http://localhost:8123/index.html
echo  分析レポート: http://localhost:8123/cell-click-report.php?pw=amami-admin-2026
echo.
echo  終了するには Ctrl + C を押してください。
echo ============================================================
cd /d "%~dp0"
"!PHPEXE!" -S 127.0.0.1:8123
