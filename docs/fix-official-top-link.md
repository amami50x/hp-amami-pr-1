# 公式TOPの「奄美群島観光情報」リンク修正

## 原因

`hp-amami-pr-1` リポジトリは `index.html` に統一済みですが、**東京奄美会公式サイト（WordPress）のテーマ**に `amami.html` が直書きされたままです。

本番HTML（2026-05-15 確認）の該当箇所:

```html
<a href="https://violetfoal2.sakura.ne.jp/hp-amami-pr-1/amami.html?return=https%3A%2F%2Fwww.tokyoamamikai.com%2Famami-top%2F"
   target="_blank" rel="noopener noreferrer" …>
  奄美群島観光情報
</a>
```

## 修正手順（WordPress 管理画面）

1. `https://www.tokyoamamikai.com/wp-admin/` にログイン
2. **外観 → テーマファイルエディター**（または FTP で `public_html/tokyo/wp-content/themes/…`）
3. 次の文字列で検索: `hp-amami-pr-1/amami.html`
4. 次のURLに**すべて**置換:

```
https://violetfoal2.sakura.ne.jp/hp-amami-pr-1/index.html?return=https%3A%2F%2Fwww.tokyoamamikai.com%2Famami-top%2F
```

（`return` の戻り先は現状の `amami-top` のままで問題ありません）

5. 保存後、公式TOPを開き、「奄美群島観光情報」をクリック → アドレスバーが **`index.html`** になることを確認

## さくら側（案内HPフォルダ）

- 最新 `index.html` をアップロード
- 本リポジトリの `amami.html`（`index.html` へ即時転送）をアップロード  
  → 公式側の修正が遅れても、旧URLは `index.html` に飛びます

## テンプレートファイル（任意）

子テーマ等に `template-amami-top.php` を使っている場合は、リポジトリ直下の同名ファイル（既に `index.html` 指定）をテーマフォルダへコピーしてください。
