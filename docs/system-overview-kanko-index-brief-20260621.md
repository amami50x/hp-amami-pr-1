# 奄美群島観光情報 index.html 管理者説明用（2026-06-21版・3分）

作成日: 2026-06-21  
詳細版: `system-overview-kanko-index-full-20260621.md`

---

## 1. 要点（30秒）

- **奄美群島12市町村** の観光・グルメ等へのリンクを **1ページの表** で案内するサイト。
- 本番: https://www.tokyoamamikai.com/hp-amami-pr-1/index.html
- 東京奄美会トップの **「奄美群島観光情報」** からリンク。
- WordPress（MENU明細）とは **別プロジェクト**（`hp-amami-pr-1`）。
- 本体は **`index.html`**（約250KB）。画像リストは JSON、関連サイトは PHP 管理画面。

---

## 2. 利用者が見るもの

| エリア | 内容 |
|--------|------|
| 上部 | 言語切替、公式HPへ戻る、HP終了 |
| 中部 | ヒーロー、スライドショー |
| **中心** | **12市町村の観光情報一覧表** |
| 下部 | ご意見・ご要望、フッター |
| 右下 | 最上部へ / この画面の説明 |

---

## 3. 管理者が触るファイル

| 作業 | 主なファイル |
|------|--------------|
| 表のリンク・文言 | `index.html` |
| スライドショー画像 | `slideshow-images.json` |
| 5島「関連サイト」 | `island-links-manage.php` → `data/island_related_links.json` |
| リンク切れ表示 | `link-status.json`（チェック脚本実行後） |
| 訪問カウント | `access-counter.php` |
| 索引 | `kanri.html` |

---

## 4. 読み上げ用テンプレート

「観光情報ページは、奄美12市町村へのリンクを一覧表で示す HTML サイトです。  
WordPress の MENU とは別フォルダ hp-amami-pr-1 で管理します。  
表の変更は index.html、島の関連サイトは専用 PHP 管理画面、  
画像は slideshow-images.json で更新します。  
本番反映後はスマホでも表のスクロールとリンクを確認します。」

---

## 5. WordPress との違い（説明用）

| | 観光 index.html | WordPress MENU |
|--|----------------|----------------|
| 何を見せるか | 12市町村の外部リンク集 | 東京奄美会の内部MENU・履歴 |
| 編集 | HTML / JSON / PHP | 投稿編集画面 |
| フォルダ | hp-amami-pr-1 | wp-content/themes/tokyo-amamikai |

---

## 6. 参照

- 詳細: `system-overview-kanko-index-full-20260621.md`
- 運用: `admin-manual.md`
- 索引: `../kanri.html`
