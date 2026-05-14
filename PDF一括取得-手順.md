# PDF 取得 → WordPress（ステップ別ワークフロー）

**このページの読み方:** 下の **ステップ 1** から順に進めてください。  
各ステップの **「このステップの確認」** が問題なければ、**次のステップ**へ進みます。  
（スクリプト名: `download-amami-pdfs.ps1`）

---

## ターミナル用：ワンステップずつ（PDF を PC に取り込むだけ）

**進め方:** 下から **1つだけ** コマンドをコピーして PowerShell に貼り、Enter → **確認チェック** → 問題なければ **次の1つ**。**2行以上を1回に貼らない**でください（`cd` と別コマンドは必ず分ける）。

### T-1　作業フォルダへ移動

**今回入力するのは次の1行だけです。**

```powershell
cd "C:\Users\User\OneDrive\NEW-HP\hp-amami-pr-1"
```

**確認:** プロンプトの左に `hp-amami-pr-1` のようなフォルダ名が含まれている。エラーが出ていない。

→ OK なら **T-2** へ。

---

### T-2　スクリプト実行の許可（初めてのとき・止まるときだけ）

**※ 以前に済ませて `Get-ExecutionPolicy` が `RemoteSigned` なら、このステップは飛ばして T-3 へ。**

**入力するのは次の1行だけです。**

```powershell
Set-ExecutionPolicy -Scope CurrentUser RemoteSigned
```

確認ダイアログが出たら **`Y`** と入力して Enter。

**確認:** エラーが出ていない（何も表示されずに次の行になってもよい）。

→ OK なら **T-3** へ。

---

### T-3　許可の確認（任意）

**入力するのは次の1行だけです。**

```powershell
Get-ExecutionPolicy -Scope CurrentUser
```

**確認:** `RemoteSigned` と表示される。

→ OK なら **T-4** へ。

---

### T-4　件数だけ確認（まだダウンロードしない）

1. ブラウザで対象ページを開き、アドレスバーの **URL をコピー**する。  
2. **次の1行**を貼り、**`"..."` の中だけ**をその URL に置き換えて Enter。

```powershell
.\download-amami-pdfs.ps1 -Uri "ここにページのURLを貼る" -ListOnly
```

**確認:** `見つかったPDF:` または `見つかったPDF・画像:` などと **件数** が出る。その下に URL の一覧が出る。

- 画像も数えたいときは **同じ URL** で次の1行（`-Media Both` を追加）:

```powershell
.\download-amami-pdfs.ps1 -Uri "同じURL" -Media Both -ListOnly
```

**拡張子を1つずつ指定する必要はありません。** `-Media Both`（または `-Media Images`）1回で、HTML 上のリンクから次をまとめて拾います。  
**画像:** `.jpg` `.jpeg` `.png` `.gif` `.webp` `.avif` `.bmp` `.svg` `.ico`　**PDF:** `.pdf`  
（`.jpg` と `.jpeg` はどちらも対象です。）

件数が少なすぎるときは **別ページの URL** で、**T-4 をやり直す**。

→ 件数に納得したら **T-5** へ。

---

### T-5　PC に保存する（ダウンロード）

**T-4 と同じ URL** を使い、**`-ListOnly` を付けない**次の1行だけ実行する。

```powershell
.\download-amami-pdfs.ps1 -Uri "T-4と同じURLを貼る"
```

（T-4 で `-Media Both` を使ったなら、こちらも同様に付ける。）

```powershell
.\download-amami-pdfs.ps1 -Uri "同じURL" -Media Both
```

**確認:** `保存:` とパスが並ぶ。エクスプローラーで **デスクトップ → `amami_pdf`** にファイルがある。

→ ここまでで **ターミナル側の取り込みは完了**。WordPress へは下の **ステップ 4** 以降へ。

---

## ステップ 1　準備する

### やること

1. **PowerShell** を開く。
2. 作業フォルダへ移動する（パスはご自身の PC に合わせる）。

```powershell
cd "C:\Users\User\OneDrive\NEW-HP\hp-amami-pr-1"
```

3. 次のどちらかが初めてのときだけ実行する（スクリプトが実行できない場合）。

```powershell
Set-ExecutionPolicy -Scope CurrentUser RemoteSigned
```

### このステップの確認

- [ ] PowerShell の画面で、上の `cd` の後、エラーなくプロンプトが返っている。

**確認できたら → ステップ 2 へ。**

---

## ステップ 2　「何本の PDF があるか」だけ調べる（まだ保存しない）

### やること

1. **ブラウザ**で、PDF を載せている**そのページ**を開く。
2. アドレスバーの **URL をまるごとコピー**する。  
   例: `https://www.tokyoamamikai.com/menu-meisai-hp/?menu_no=05-01-01`
3. PowerShell に次を貼り付け、**引用符の中だけ**を自分の URL に差し替えて Enter する。

```powershell
.\download-amami-pdfs.ps1 -Uri "ここにコピーしたURLを貼る" -ListOnly
```

### このステップの確認

- [ ] 画面に **「見つかったPDF: ○○ 件」** のように件数が出ている。
- [ ] その下に **PDF のアドレス（http や https で始まる行）** が並んでいる。
- [ ] 件数が **想定より少ない** → 別のページ（別の `menu_no` など）に PDF が分かれていないか、ブラウザで当たりを付けてから、**別の URL でステップ 2 をもう一度**行う。

**確認できたら → ステップ 3 へ。**  
（ここで満足いくまで URL を変えて `-ListOnly` を繰り返して構いません。）

---

## ステップ 3　PC に PDF を保存する

### やること

1. ステップ 2 で使った **同じ URL** で、`-ListOnly` を**付けず**に実行する。

```powershell
.\download-amami-pdfs.ps1 -Uri "ステップ2と同じURLを貼る"
```

2. 処理が終わるまで待つ。

### このステップの確認

- [ ] PowerShell に **「保存:」** と **フォルダのパス** が出ている（またはエラーが無い）。
- [ ] エクスプローラーで **デスクトップ → `amami_pdf`** を開き、**PDF ファイルが増えている**。

保存先の既定は次の場所です（ユーザー名は環境により異なります）。

`C:\Users\（あなたのユーザー名）\Desktop\amami_pdf`

**確認できたら → ステップ 4 へ。**

---

## ステップ 4　WordPress のメディアにアップロードする

### やること

1. WordPress の **管理画面**にログインする。
2. **メディア** → **新規追加** を開く。
3. **デスクトップの `amami_pdf`** から、今回保存した PDF を選んでアップロードする。

### このステップの確認

- [ ] メディアライブラリに、今アップロードした PDF が表示される。
- [ ] エラーが出た場合 → ファイルサイズ制限のメッセージなら、ホストの **アップロード上限**の説明を確認する（スクリプトの問題ではありません）。

**確認できたら → ステップ 5 へ。**

---

## ステップ 5　投稿に PDF を反映する

### やること

1. 管理画面で **投稿**（または固定ページ）を **編集** する。
2. ブロックエディタで **「ファイル」** を追加するか、**「メディアを挿入」** で、ステップ 4 でアップロードした PDF を選ぶ。  
   （リンクだけ貼る場合は、文字を選んでリンク先にメディアの URL を指定。）

### このステップの確認

- [ ] プレビューまたは公開サイトで、**意図した位置に PDF（またはリンク）が出る**。

**ここまでで一連の作業は完了です。**

---

## 補足 A　ページ URL が複数あるとき（ワンステップの中身が増えるだけ）

「1 ステップ＝1 ページ」ではなく、**複数ページをまとめてからステップ 2 に進む**場合の流れです。

1. メモ帳などで **UTF-8** のテキストファイルを作る（例: `pdf-page-urls.txt`）。
2. **1 行に 1 つ**、ブラウザからコピーしたページ URL を書く。`#` で始まる行はメモ用として無視されます。
3. **ステップ 2 に相当:**  
   `.\download-amami-pdfs.ps1 -UriList ".\pdf-page-urls.txt" -ListOnly`
4. 件数と一覧を確認してから、**ステップ 3 に相当:**  
   `.\download-amami-pdfs.ps1 -UriList ".\pdf-page-urls.txt"`  
   （`-ListOnly` なし）

その後は **ステップ 4・5** は同じです。

---

## 補足 B　`-Interactive` について（作業ステップとは別物です）

スクリプトの **`-Interactive`** は、「**ステップ 2〜5** のような大きな流れ」とは別で、

**「1 つ目の PDF を落とす前に止まる → キー入力 → 2 つ目の前に止まる → …」**

という **ファイル単位の細かい止め方** です。  
普段は **ステップ 2 → 3** のように、`-ListOnly` で確認してから一括保存する方法で十分なことが多いです。

使う場合の例（上級・必要なときだけ）:

```powershell
.\download-amami-pdfs.ps1 -Uri "URL" -Interactive
```

- **Enter** … その PDF をダウンロード  
- **n** + Enter … その 1 ファイルだけスキップ  
- **q** + Enter … 途中で終了  

---

## 補足 C　取りこぼしを減らすコツ

| 状況 | ポイント |
|------|----------|
| 画面上の一部だけコピーしてスクリプトに貼る | PDF の URL が本文に含まれず、**件数が少なくなる**ことがあります。 |
| **`-Uri` でページ URL を渡す** | そのページの **HTML 全体**から PDF を探すので **取りこぼしにくい**です。 |
| ページに「未収録」とある | 公開 PDF が無いので **取得できません**。 |

---

## 補足 D　別サイト・相対パスだけの HTML

- HTML ファイルを保存して使う場合:

```powershell
.\download-amami-pdfs.ps1 -Path ".\page.html" -BaseUrl "https://そのサイトのトップ/"
```

- 相対パスは使わず、文中の絶対 URL だけ拾う:

```powershell
.\download-amami-pdfs.ps1 -Path ".\page.html" -BaseUrl ""
```

---

## コマンド早見表

| 目的 | コマンド例 |
|------|------------|
| ステップ 2（件数・一覧だけ） | `.\download-amami-pdfs.ps1 -Uri "URL" -ListOnly` |
| ステップ 3（保存） | `.\download-amami-pdfs.ps1 -Uri "URL"` |
| 複数ページの件数確認 | `.\download-amami-pdfs.ps1 -UriList ".\pdf-page-urls.txt" -ListOnly` |
| 複数ページの保存 | `.\download-amami-pdfs.ps1 -UriList ".\pdf-page-urls.txt"` |
| PDF ごとに止める（補足 B） | 上記に `-Interactive` を付ける |
| 保存した HTML から | `.\download-amami-pdfs.ps1 -Path ".\page.html"` |

`download-amami-pdfs.ps1` の先頭にも短い説明があります。
