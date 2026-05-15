# 奄美サイト群 管理者マニュアル（hp-amami-pr-1）

- **対象**: HTML / CSS / JavaScript / PHP / Git（GitHub）/ 補助スクリプトまで担当する管理者
- **リポジトリ**: `hp-amami-pr-1`（他テーマ・別リポジトリの作業と混同しないこと）
- **運用の起点**: ルートの `kanri.html`（HP管理メニュー・サーバ情報・関連リンク管理の概要）
- **最終更新日**: 2026-05-15

---

## 0. マニュアル運用ルール

1. **秘密情報**（FTP パスワード、API キー、管理用の合言葉など）は本ファイルに書かない。`kanri.html` にホスト名等が載っている場合でも、**パスワードは別紙またはパスワードマネージャ**で管理する。
2. 手順は必ず **前提 → 手順 → 成功の確認** の順で読む。
3. 本番 URL やサーバパスは環境により異なる。不明点は契約書・サーバ会社の管理画面と照合する。
4. 設定ファイル `island-links-admin-config.php` は**公開リポジトリにコミットしない**ことを推奨（サンプルのみコミット）。

---

## 1. リポジトリ内の役割一覧（ファイルマップ）

| 種別 | 主なパス・入口 | 備考 |
|------|----------------|------|
| トップ・島ページ | `amami.html`、各市町用 `*.html`、`hp-machi-inf-upd/amami.html` など | 本番で使うファイルを変更したら Git にコミットし、FTP で反映 |
| 管理メニュー（索引） | `kanri.html` | ローカルで開いて作業手順・URL を確認 |
| 共通スタイル・スクリプト | ルート `styles.css`、`script.js`（ほかフォルダに同名がある場合は**本番が参照している方**を正とする） | 変更後はキャッシュを考慮して再読み込みで確認 |
| 5島「関連サイト」管理 UI | `island-links-manage.php` | **PHP によるパスワードロックなし**。URL の周知範囲とサーバ側制限で守る |
| 関連リンク 公開 API | `island-related-links-api.php` | `amami.html` 側が取得に使用。失敗時はページ内フォールバック |
| 関連リンク 保存処理 | `island-related-links-storage.php` | JSON 読み書き |
| IP 制限など | `island-links-admin-guard.php` | 設定は `island-links-admin-config.php` |
| 設定（任意・ローカル/本番のみ） | `island-links-admin-config.php`（`island-links-admin-config.sample.php` からコピー） | `allowed_admin_ips` で接続元を絞れる。無い場合も管理画面は動作 |
| 管理 UI（別配置用） | `admin/island-links-admin.php`、`island-links-admin.php` | 運用でどちらを使うか決め、手順を統一する |
| 保存データ | `data/island_related_links.json` | Web サーバの書き込み権限が必要 |
| Basic 認証の例 | `island-links-manage.htaccess.example` | 必要に応じて本番用 `.htaccess` に反映 |
| アクセスカウンタ | `access-counter.php`、`access_admin.php`、`akusesu-cnt/counter.php` | 設置パスに合わせて HTML から参照 |
| 町情報フォーム系 | `hp-machi-inf-upd/*.php`（`submit.php`、`save-json.php` 等） | 本番 URL と連携方法を別途確認 |
| PHP テンプレート | `template-amami-top.php`、`template-amai-top.php` | ファイル名の typo 注意（`amai` / `amami`） |
| Git バックアップ | `git-backup-github.bat`、`git-backup-コピー用.txt` | リポジトリルートで実行 |
| PDF 取得など | `download-amami-pdfs.ps1` | PowerShell 環境が必要 |
| アップロード補助 | `upload_amami.py`、ルート `run_all.bat`、`server.py` 等 | 実際に運用で使うものだけ手順化する |
| 関東情報ツール連携 | `kanto-info/` 以下の `.py` / `.bat` | `kanri.html` の shops 系手順と対応。本リポジトリ外データに依存する場合あり |

---

## 2. 日常更新（HTML・画像・音声・PDF）

### 前提

- エディタは UTF-8（BOM なし推奨）。バックアップ（Git）を取ってから大きな編集をする。

### 手順（例）

1. 変更対象の HTML を開き、テキスト・リンク・画像パスを修正する。
2. ローカルでブラウザを開き、主要ブラウザで表示確認する。
3. `git-backup-github.bat` でコミット・プッシュ（後述）。
4. FTP 等で本番にアップロード（後述）。

### 成功の確認

- ローカルでレイアウト崩れ・リンク切れがないこと。
- 本番 URL で同様に確認できること。

---

## 3. CSS / JavaScript

1. 対象ページが読み込んでいる `styles.css` / `script.js` を特定する（HTML 内の `<link>` / `<script>`）。
2. 変更後、ブラウザのスーパーリロード（キャッシュ無視）で確認する。
3. Git にコミットし、本番へ反映する。

---

## 4. 5島「関連サイト」リンク管理（PHP）

### 概要

- `amami.html` のポップアップ用データは JSON で保持され、**`island-links-manage.php`** から編集する。
- 閲覧用に **`island-related-links-api.php`** が使われる。

### 初回・本番配置

1. `island-links-manage.php`、`island-links-admin-guard.php`、`island-related-links-storage.php`、`island-related-links-api.php` を、**`amami.html` と同じディレクトリ階層**に揃える（`island-links-manage.php` 先頭コメント参照）。
2. `data/` ディレクトリを置き、`island_related_links.json` がサーバから書き込める権限にする。
3. 任意: `island-links-admin-config.sample.php` を `island-links-admin-config.php` にコピーし、`allowed_admin_ips` に管理者の固定 IP を列挙する（空なら制限なし）。

### 運用時の注意

- 管理画面は **パスワード認証をしない** 設計のため、**URL を一般公開しない**、**IP 制限または Basic 認証**（`island-links-manage.htaccess.example` 参照）を検討する。
- 保存時は CSRF トークンが使われる。エラーが出たらページを再読み込みしてから再保存する。

### 成功の確認

- 管理画面で保存後、本番の `amami.html` を開き、該当島の「関連サイト」ポップアップに反映されていること。
- `data/island_related_links.json` の更新日時が変わっていること（サーバ上で確認）。

---

## 5. アクセスカウンタ・その他 PHP

- `access-counter.php` 等をページから参照している場合、**本番パス**と **include パス**が一致しているか確認する。
- `access_admin.php` は集計・管理用として使う場合、**URL を限定**し、不要なら本番から外す。

---

## 6. Git / GitHub（バックアップ）

### 手順

1. リポジトリのルート（`git-backup-github.bat` があるフォルダ）を Cursor やエクスプローラで開く。
2. コマンドプロンプトまたは PowerShell で、次のいずれかを実行する。  
   - `cmd /c git-backup-github.bat`  
   - またはカレントがルートなら `.\git-backup-github.bat`
3. メニュー **1**（自動メッセージ）または **2**（任意メッセージ）を選ぶ。半角・全角どちらの数字でも可（`git-backup-コピー用.txt` 参照）。

### 内部の流れ（概要）

- `git add -A` → 変更がなければ終了 → あれば `git commit` → `git pull` → `git push`

### トラブル

- **pull で競合**: 担当者と調整し、マージまたは一方に合わせる。自信がない場合は作業を止め、バックアップコピーを別フォルダに取ってから対応する。
- **別リポジトリ**: `tokyo-amamikai` 等とは**別フォルダで**バッチを実行すること（`git-backup-コピー用.txt` の注意）。

### 成功の確認

- バッチ完了メッセージが表示され、GitHub 上の該当リポジトリに最新コミットが見えること。

---

## 7. 本番への反映（FTP 等）

1. **接続情報・アップロード先**は `kanri.html` の「サーバ情報」を参照（パスワードは別管理）。
2. 変更したファイルだけを上げるか、運用ルールに従い全件同期する。
3. PHP や `data/` を変更した場合は、**パーミッション**と **PHP エラー**（500 など）に注意する。

### 成功の確認

- 本番 URL でページが表示され、管理画面の保存内容が反映されていること。

---

## 8. トラブルシューティング（早見）

| 現象 | 確認すること |
|------|----------------|
| 関連サイトが更新されない | API URL、JSON のパス、`data/` の権限、ブラウザキャッシュ |
| 管理画面が開けない | IP 制限（`allowed_admin_ips`）、URL の typo、本番にファイル未アップロード |
| PHP 500 エラー | サーバエラーログ、ファイルの配置ミス、PHP バージョン |
| Git push が失敗 | 認証情報、ネットワーク、`pull` 未実施による非ファストフォワード |

---

## 9. 引き継ぎチェックリスト

- [ ] GitHub のリポジトリへのアクセス権
- [ ] FTP（またはデプロイ手段）の認証情報（パスワードは別保管）
- [ ] 本番 URL・管理画面 URL の一覧（社内限定）
- [ ] `kanri.html` と本マニュアルの場所の周知
- [ ] ドメイン・サーバ契約の更新担当

---

## 10. 改訂履歴

| 日付 | 内容 |
|------|------|
| 2026-05-15 | 初版作成 |
