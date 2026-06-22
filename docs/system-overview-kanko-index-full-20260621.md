# 奄美群島観光情報 index.html 全体概要（詳細版）

- **作成日:** 2026-06-21
- **対象:** 管理者・関係者（詳細説明用）
- **リポジトリ:** `hp-amami-pr-1`（WordPress テーマ `tokyo-amamikai` とは **別プロジェクト**）
- **本番URL:** https://www.tokyoamamikai.com/hp-amami-pr-1/index.html
- **ページタイトル:** 奄美群島観光情報（12市町村情報）

---

## 1. システムの位置づけ

| 項目 | 内容 |
|------|------|
| 種別 | 静的HTML + JavaScript + PHP（一部） |
| 主ファイル | `index.html`（約 250KB・CSS/JS を大部分内蔵） |
| ローカル | `C:\Users\User\Local Sites\tokyo-amamikai\app\public\hp-amami-pr-1\` |
| 本番パス | `public_html/.../hp-amami-pr-1/`（さくら等） |
| WordPress との関係 | 東京奄美会トップ（`/amami-top/`）の **「奄美群島観光情報」** ボタンからリンク |

**目的:** 奄美群島 **12市町村** の観光・グルメ・特産・イベント等へのリンクを **1ページの表形式** で一覧し、旅行者が目的の公式・観光サイトへ迷わず遷移できるようにする。

**5島構成:** 奄美大島、喜界島、徳之島、沖永良部島、与論島（表内で島見出し行を表示）

---

## 2. 画面構成（上から下）

```
┌─────────────────────────────────────┐
│ 言語切替バー（Google翻訳 / 独自UI）   │
├─────────────────────────────────────┤
│ 固定トップバー                        │
│  左: 公式HPへ  中央: タイトル  右: HP終了 │
├─────────────────────────────────────┤
│ ヒーロー（キャッチ＋CTAボタン）        │
├─────────────────────────────────────┤
│ 今日の奄美（テキスト）                 │
├─────────────────────────────────────┤
│ スライドショー（風景ギャラリー）       │
├─────────────────────────────────────┤
│ ★ 観光情報一覧表（#amami-table）     │  ← 本体
├─────────────────────────────────────┤
│ ご意見・ご要望                        │
├─────────────────────────────────────┤
│ フッター（東京奄美会・広域事務組合 等） │
└─────────────────────────────────────┘
  右下FAB: 「ページ最上部へ」「この画面の説明」
```

### 2-1. 観光情報一覧表（核心）

- **ID:** `#amami-table` / セクション `#amami-table-section`
- **列例:** 市町村名、観光、グルメ、特産、イベント、関連サイト 等（HTML 内に定義）
- **行:** 12市町村 + 島見出し行（`island-title-row`）+ フッター行（東京奄美会・奄美群島広域事務組合）
- **操作:**
  - 横スクロール同期（ヘッダーと本体）
  - 拡大／縮小ボタン（文字サイズ切替）
  - セル内リンクはボタン風表示・同一タブ遷移
  - 複数リンクセルはポップアップで選択

### 2-2. 付帯UI

| 機能 | 説明 |
|------|------|
| 操作マニュアル | 右下「この画面の説明」→ パネル表示（利用者向け） |
| PDFモーダル | 一部リンクを iframe で表示 |
| 島「関連サイト」 | 5島ごとの SNS・関連URL ポップアップ（JSON 管理） |
| リンク切れ表示 | 管理者モードで赤枠・グレー枠（`link-status.json`） |
| 訪問者カウント | `access-counter.php`（日本語/外国語別） |
| セルクリック集計 | 管理者向け（`cell-clicks.json` 等） |

---

## 3. 主要ファイル一覧

### 3-1. 公開ページ

| ファイル | 役割 |
|----------|------|
| `index.html` | **本体**（表・UI・大部分の CSS/JS） |
| `script.js` | 共通スクリプト（defer 読込） |
| `styles.css` | 共通スタイル（サーバーによっては index 内蔵CSSと併用） |
| `slideshow-images.json` | ヒーロー・ギャラリー画像リスト |
| `link-status.json` | リンク切れチェック結果（管理者表示用） |
| `extra-links.json` | 追加リンク定義 |
| `data/island_related_links.json` | 5島「関連サイト」ポップアップデータ |

### 3-2. PHP（サーバー側）

| ファイル | 役割 |
|----------|------|
| `access-counter.php` | 訪問者カウント加算・取得 |
| `access_admin.php` | カウント管理（URL 限定推奨） |
| `island-related-links-api.php` | 関連サイト JSON の公開 API |
| `island-related-links-storage.php` | 関連サイト JSON 保存 |
| `island-links-manage.php` | **関連サイト編集画面**（パスワードなし・URL周知前提） |
| `island-links-admin-guard.php` | IP 制限等 |
| `island-links-admin-config.php` | 管理者 IP 設定（任意・非コミット推奨） |

### 3-3. 管理・運用

| ファイル | 役割 |
|----------|------|
| `kanri.html` | **HP管理メニュー索引**（ブラウザで開く） |
| `docs/admin-manual.md` | 日常運用マニュアル |
| `git-backup-github.bat` | Git バックアップ |
| `link-status-check.py` 等 | リンクチェック（管理者ローカル実行） |

### 3-4. 関連（別系統）

| フォルダ | 内容 |
|----------|------|
| `kanto-info/` | 関東情報・shops Excel 連携（表とは別系統の場合あり） |
| `hp-machi-inf-upd/` | 町情報フォーム・JSON |
| `towninfo/` | 市町村個別 HTML |
| `hp-music/` | BGM 関連 |

---

## 4. index.html 内部構造（管理者向け）

`index.html` 先頭付近に **◆01〜◆27** の機能番号コメントあり。主な塊:

| 番号 | 機能 |
|------|------|
| ◆04 | 右下フローティングパネル（最上部へ・画面説明） |
| ◆07 | 公式HPへ戻る / HP終了 |
| ◆09 | 訪問者カウント（`access-counter.php`） |
| ◆10 | 操作マニュアル開閉 |
| ◆11 | ヒーローセクション |
| ◆12/17/18 | スライドショー（JSON + 停止/再開） |
| ◆13 | **観光情報テーブル本体** |
| ◆14 | 表の拡大縮小・横スクロール・セルクリック |
| ◆19 | 表リンクのボタン化 |
| ◆20 | リンク切れ可視化（`link-status.json`） |
| ◆23 | 島関連サイトポップアップ（API + JSON） |
| ◆25 | BGM 連動（BroadcastChannel） |

**編集の原則:** 大きな変更前に Git バックアップ。画像リストは `slideshow-images.json` を編集（HTML 直書き不要）。

---

## 5. 5島「関連サイト」管理

1. ブラウザで `island-links-manage.php` を開く（本番 URL は `kanri.html` 参照）
2. 島ごとに見出し・表示名・URL を編集 → 保存
3. `data/island_related_links.json` が更新される
4. `index.html` のポップアップが API 経由で反映（失敗時はページ内フォールバック）

**セキュリティ注意:**

- PHP 側に **パスワードロックなし**
- URL を一般公開しない / **IP 制限** / **Basic 認証**（`island-links-manage.htaccess.example`）を検討
- `island-links-admin-config.php` はリポジトリにコミットしない

---

## 6. リンク切れチェック（管理者）

| 状態 | 表示 |
|------|------|
| 正常 | 通常表示 |
| URL 空 | グレー太枠（`cell-no-url`） |
| リンク切れ | 赤太枠（`cell-broken-url`） |

- 判定データ: `link-status.json`
- 管理者ログイン等で `amami-admin` クラスが付与された閲覧時に強調表示
- チェック脚本: `link-status-check.py` 等（ローカル実行 → JSON 更新 → FTP）

---

## 7. 訪問者カウント

- エンドポイント: `access-counter.php`（index 内 `COUNTER_ENDPOINT`）
- 日本語 / 外国語（翻訳利用時）を分けて集計
- 一般ユーザーには空集計を返す設計の箇所あり（管理者のみ詳細）

---

## 8. WordPress（tokyo-amamikai）との関係

| 項目 | 観光 index.html | WordPress MENU |
|------|-----------------|----------------|
| プロジェクト | `hp-amami-pr-1` | テーマ `tokyo-amamikai` |
| 入口 | トップの観光ボタン | トップの MENU ボタン |
| 編集 | HTML/JSON/PHP | WordPress 投稿編集 |
| ドキュメント | 本資料・`docs/admin-manual.md` | `tokyo-amamikai/docs/system-overview-amami-full-20260621.md` |

**混同しないこと:** FileZilla・Git・バックアップは **プロジェクトごと** に行う。

---

## 9. 日常更新手順（概要）

### HTML・表のリンク変更

1. `index.html` を編集（または生成脚本から再生成）
2. ローカルブラウザで表示確認（Ctrl+F5）
3. `git-backup-github.bat` でコミット
4. FTP で本番 `hp-amami-pr-1/` にアップロード

### スライドショー画像だけ変更

1. `slideshow-images.json` の `hero` / `gallery` を編集
2. 画像ファイルを `hp-image/` 等の正しいパスに配置
3. 本番反映

### 関連サイトポップアップだけ変更

1. `island-links-manage.php` で編集・保存
2. 本番 `index.html` で該当島のポップアップを確認

---

## 10. 本番反映・確認

| 確認項目 | 方法 |
|----------|------|
| 表全体 | 12市町村のリンクが開けるか |
| スマホ | 横スクロール・タップ反応 |
| 公式HPへ戻る | 左上ボタン → tokyoamamikai.com |
| 言語切替 | 翻訳バー動作 |
| 関連サイト | 5島ポップアップ |

---

## 11. 想定Q&A

**Q. WordPress の投稿編集と混同している**  
A. 観光情報は `hp-amami-pr-1/index.html`。MENU明細は WordPress 別系統。

**Q. index.html が巨大で編集しにくい**  
A. 画像は JSON、関連サイトは PHP 管理画面、リンクチェックは Python 脚本と役割分担。

**Q. 表のセルが赤い**  
A. `link-status.json` 上のリンク切れ。URL 修正またはチェック再実行。

**Q. 管理画面はどこ？**  
A. `kanri.html`（索引）、関連サイトは `island-links-manage.php`。WordPress 管理画面ではない。

---

## 12. 関連資料

| ファイル | 用途 |
|----------|------|
| `system-overview-kanko-index-brief-20260621.md` | 3分説明版 |
| `docs/admin-manual.md` | 運用手順詳細 |
| `kanri.html` | 管理索引 |
| `no-up-操作ガイド(index.html）.txt` | 操作メモ（ルート） |
| `tokyo-amamikai/docs/system-overview-amami-full-20260621.md` | WordPress 側概要 |
