# =============================================================================
# download-amami-pdfs.ps1
# 貼り付けたテキスト（HTML / 表示テキスト / Markdown）またはページURLから
# PDF または画像（jpg 等）の URL を抽出し、デスクトップの「amami_pdf」へ一括ダウンロードします。
# -Media で種類を指定（既定は PDF のみ）。
# 作業は「ステップ1→確認→次へ」の順で進める: 同フォルダの PDF一括取得-手順.md（先頭のステップ1〜5）。
# -Interactive は PDF「ファイルごと」の止め方で、ステップ制御とは別（手順書 補足 B）。
#
# 【前提】
#   - Windows の PowerShell（ターミナルで「powershell」と出る環境）
#   - 初回だけ実行ポリシーで止まる場合:
#       Set-ExecutionPolicy -Scope CurrentUser RemoteSigned
#   - 保存先: %USERPROFILE%\Desktop\amami_pdf（なければ自動作成）
#
# 【入力の取り方（重要）】
#   - 画面上の一部だけをドラッグコピーすると、その範囲のリンクしか入らず件数が少なくなります。
#     （例: 講演会の資料だけコピーすると議事録ブロックの PDF は含まれません）
#   - 画面上の文字だけをコピーすると、リンク先URLが含まれないこともあります。
#   - いちばん確実: ページURLをそのまま渡す（-Uri）。HTML全体からリンクを列挙します。
#   - 画像も: -Media Both または -Media Images（jpg/png/gif/webp/bmp/svg）
#   - 拡張子なしでも、パス上のファイル名が pdf で始まるURL（例: .../pdf_1）をPDF候補として列挙
#     （大きいHTMLでは相対「拡張子付き」抽出に時間が掛かる古い正規表現のため「抽出で止まる」ことが
#     あっても、本スクリプトの現版は回避しています。href に「pdf_1.」のように句点が入っても
#     Trim して正しい base の pdf_1 として扱います。）
#   - 手動で取る場合: F12 → Elements、または「ページのソースを表示」で全文を保存。
#
# 【実行例】
#   スクリプトがあるフォルダへ移動してから実行してください。
#     cd "C:\Users\User\OneDrive\NEW-HP\hp-amami-pr-1"
#
#   A) ページURLを指定（推奨・一覧ページの PDF を取りこぼしにくい）
#        .\download-amami-pdfs.ps1 -Uri 'https://www.tokyoamamikai.com/menu-meisai-hp/?menu_no=05-01-01'
#        PDF+画像: .\download-amami-pdfs.ps1 -Uri '...' -Media Both -ListOnly
#        件数だけ確認: 末尾に -ListOnly
#
#   A') 複数ページURLをテキストで一括（1行1URL、# でコメント可）→ 詳細は PDF一括取得-手順.md
#        .\download-amami-pdfs.ps1 -UriList '.\pdf-page-urls.txt' -ListOnly
#
#   PDF ファイルごとに止める（上級・任意）: -Interactive（手順のステップとは別。PDF一括取得-手順.md 補足 B）
#        .\download-amami-pdfs.ps1 -Uri '...' -Interactive
#
#   B) クリップボードの内容から（コピー後に実行）
#        .\download-amami-pdfs.ps1 -FromClipboard
#
#   C) ファイルから（UTF-8 で保存した page.html など）
#        .\download-amami-pdfs.ps1 -Path .\page.html
#
#   D) スクリプト内の here-string（'【ここに…】' のブロック）に全文を貼り付けたうえで
#        .\download-amami-pdfs.ps1
#
# 【-BaseUrl（相対パス）】
#   HTML に「/sites/.../xxx.pdf」のようにルート相対で書かれている場合、
#   サイトのトップURLと結合して絶対URLにします。
#   既定: https://www.tokyo-amamikai.com/（東京奄美会サイト向け）
#   別サイトのHTMLを扱う例:
#        .\download-amami-pdfs.ps1 -Path .\other.html -BaseUrl 'https://example.jp/'
#   相対パスは無視し、既に書かれている http(s)://...pdf だけ使う:
#        .\download-amami-pdfs.ps1 -FromClipboard -BaseUrl ''
#
# 【WordPress への流れ（参考）】
#   1) 管理画面 → メディア → 新規追加 → amami_pdf 内の PDF を選択してアップロード
#   2) 投稿を編集 → ブロックで「ファイル」を追加するか、本文に「メディアを挿入」でPDFを選ぶ
#   3) リンクのみにする場合は、テキストを選択してリンク先にメディアライブラリのPDF URLを指定
#   ※アップロード上限（php.ini の upload_max_filesize 等）を超えるPDFは分割・圧縮または上限変更が必要
# =============================================================================

param(
    [string]$Uri,
    [string]$UriList,
    [switch]$FromClipboard,
    [string]$Path,
    # 本サイトは https 前提（旧 http リンクは正規化で https に寄せる）
    [string]$BaseUrl = 'https://www.tokyoamamikai.com/',
    # 一覧だけ表示してダウンロードしない（件数確認用）
    [switch]$ListOnly,
    # 各 PDF の前で確認（Enter=ダウンロード / n=スキップ / q=終了）
    [switch]$Interactive,
    # Pdf=PDFのみ（既定） / Images=jpg,jpeg,png,gif,webp,avif,bmp,svg,ico / Both=PDF+上記
    [ValidateSet('Pdf', 'Images', 'Both')]
    [string]$Media = 'Pdf',
    # ページ取得・各ファイルのダウンロードをこの秒数で打ち切る（応答が遅いと「終わらない」ように見えるのを防ぐ）
    [int]$TimeoutSec = 120
)

$ErrorActionPreference = 'Stop'
# 大きいファイルの取得で接続が切れやすい環境向け（可能なら TLS 1.2 を明示）
try {
    [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
}
catch { }
$DestDir = Join-Path $env:USERPROFILE 'Desktop\amami_pdf'
# 一覧・ダウンロード用の同一URL判定（不可視文字・パス % エンコードの揺れを吸収）
function Get-CanonicalMediaUrlKey {
    param([Parameter(Mandatory)][string]$Url)
    $clean = ($Url.Trim() -replace '[\u200B-\u200D\uFEFF]', '')
    $clean = ($clean -replace '#.*$', '')
    $clean = $clean -replace '(?i)(https://[^/:]+):443(?=/)', '$1'
    $clean = $clean -replace '(?i)(http://[^/:]+):80(?=/)', '$1'
    try {
        $clean = $clean.Normalize([System.Text.NormalizationForm]::FormKC)
    }
    catch { }
    try {
        $u = [Uri]$clean
        $h = $u.Host.TrimEnd('.').ToLowerInvariant()
        $p = [Uri]::UnescapeDataString($u.AbsolutePath).Replace([char]0x5C, '/').TrimEnd('/')
        $q = $u.Query
        # 同一サイト内で「見た目同じURLが2本」になるのはホスト表記・内部解決の揺れが多いため、パスで一本化
        if ($h -match 'tokyoamamikai\.com$') {
            return ('tokyoamamikai|' + $p.ToLowerInvariant() + $q.ToLowerInvariant())
        }
        return ($u.Scheme.ToLowerInvariant() + '://' + $h + $p + $q)
    }
    catch {
        return $clean.ToLowerInvariant()
    }
}
function Get-StoredMediaUrlForOutput {
    param([Parameter(Mandatory)][string]$Raw)
    $t = $Raw.Trim()
    try {
        $u = [Uri]$t
        if ($u.Host -match 'tokyoamamikai\.com$') {
            return ('https://www.tokyoamamikai.com' + $u.AbsolutePath + $u.Query)
        }
        return $u.AbsoluteUri.Trim()
    }
    catch {
        return $t
    }
}
# 抽出URLの揺れ（http/https・/default/files/ と /sites/default/files/）を整理（同一ファイルの重複・誤404を減らす）
function Get-NormalizedMediaUrlList {
    param(
        [string[]]$Urls
    )
    if ($null -eq $Urls -or $Urls.Count -eq 0) {
        return @()
    }
    $set = [System.Collections.Generic.HashSet[string]]::new([StringComparer]::OrdinalIgnoreCase)
    foreach ($line in $Urls) {
        if ([string]::IsNullOrWhiteSpace($line)) { continue }
        $cand = $line.Trim()
        try { $a = [Uri]$cand } catch { [void]$set.Add($cand); continue }
        if ($a.Host -match 'tokyoamamikai\.com$') {
            if ($a.Scheme -eq 'http') {
                $cand = $cand -ireplace '^http://', 'https://'
                $a = [Uri]$cand
            }
            if ($a.Host -ieq 'tokyoamamikai.com') {
                $cand = $cand -ireplace '://tokyoamamikai\.com', '://www.tokyoamamikai.com'
                $a = [Uri]$cand
            }
            if ($a.AbsolutePath -like '/default/files/*' -and $a.AbsolutePath -notlike '/sites/*') {
                $cand = 'https://' + $a.Host + '/sites' + $a.PathAndQuery
                $a = [Uri]$cand
            }
        }
        [void]$set.Add($a.AbsoluteUri.Trim())
    }
    $final = [System.Collections.Generic.Dictionary[string, string]]::new([StringComparer]::Ordinal)
    foreach ($u in $set) {
        $key = Get-CanonicalMediaUrlKey -Url $u
        if (-not $final.ContainsKey($key)) {
            $final[$key] = Get-StoredMediaUrlForOutput -Raw $u
        }
    }
    $sorted = @($final.Values) | Sort-Object
    # ソート後に文字列が完全一致で隣接する重複を除去（端末表示が同一なのに辞書キーが分岐した残り対策）
    $out = [System.Collections.Generic.List[string]]::new()
    $prevLine = [char]0
    foreach ($line in $sorted) {
        if ($line -ieq $prevLine) { continue }
        $prevLine = $line
        [void]$out.Add($line)
    }
    @($out)
}
if (-not (Test-Path -LiteralPath $DestDir)) {
    New-Item -ItemType Directory -Path $DestDir | Out-Null
}

# 接続が途中で切れる場合の再試行（Invoke-WebRequest をそのまま連打しない）
function Invoke-WebDownloadWithRetries {
    param(
        [Parameter(Mandatory)][string]$Uri,
        [Parameter(Mandatory)][string]$OutFile,
        [Parameter(Mandatory)][int]$TimeoutSec,
        [int]$Retries = 6
    )
    $waitMs = 800
    for ($ri = 1; $ri -le $Retries; $ri++) {
        try {
            Invoke-WebRequest -Uri $Uri -OutFile $OutFile -UseBasicParsing -TimeoutSec $TimeoutSec
            return
        }
        catch {
            if ($ri -ge $Retries) {
                throw
            }
            $m = $_.Exception.Message
            if ($m -match '閉じ|中止|closed|reset|broken|unexpected|timeout|timed out|408|429|503|504') {
                Start-Sleep -Milliseconds $waitMs
                $waitMs = [Math]::Min([int]($waitMs * 1.75), 5000)
                continue
            }
            throw
        }
    }
}

# 先頭バイトで実体を推定（URLに拡張子がない・.bin フォールバックになった場合の救済）
function Get-ExtensionFromFileSignature {
    param([byte[]]$Bytes)
    if ($null -eq $Bytes -or $Bytes.Length -lt 4) { return $null }
    # PDF
    if ($Bytes[0] -eq 0x25 -and $Bytes[1] -eq 0x50 -and $Bytes[2] -eq 0x44 -and $Bytes[3] -eq 0x46) { return '.pdf' }
    # JPEG
    if ($Bytes[0] -eq 0xFF -and $Bytes[1] -eq 0xD8 -and $Bytes[2] -eq 0xFF) { return '.jpg' }
    # PNG
    if ($Bytes.Length -ge 8 -and $Bytes[0] -eq 0x89 -and $Bytes[1] -eq 0x50 -and $Bytes[2] -eq 0x4E -and $Bytes[3] -eq 0x47) { return '.png' }
    # GIF
    if ($Bytes[0] -eq 0x47 -and $Bytes[1] -eq 0x49 -and $Bytes[2] -eq 0x46 -and $Bytes[3] -eq 0x38) { return '.gif' }
    # WEBP (RIFF....WEBP)
    if ($Bytes.Length -ge 12 -and $Bytes[0] -eq 0x52 -and $Bytes[1] -eq 0x49 -and $Bytes[2] -eq 0x46 -and $Bytes[3] -eq 0x46 -and $Bytes[8] -eq 0x57 -and $Bytes[9] -eq 0x45 -and $Bytes[10] -eq 0x42 -and $Bytes[11] -eq 0x50) { return '.webp' }
    return $null
}

function Get-MediaExtensionRegex {
    param([string]$Media)
    # 拡張子が「pdf」で始まるもの（.pdf, .pdf 大文字, .pdfx 等）を含める
    $pdfExt = 'pdf[0-9A-Za-z]*'
    switch ($Media) {
        'Pdf' { return $pdfExt }
        'Images' { return 'jpe?g|png|gif|webp|avif|bmp|svg|ico' }
        'Both' { return ($pdfExt + '|jpe?g|png|gif|webp|avif|bmp|svg|ico') }
        default { return $pdfExt }
    }
}

function Add-PdfLinksToSet {
    param(
        [Parameter(Mandatory)]
        [string]$Text,
        [Parameter(Mandatory)]
        [object]$UrlSet,
        [string]$BaseUrl,
        [Parameter(Mandatory)]
        [string]$Media
    )
    if ($UrlSet -isnot [System.Collections.Generic.HashSet[string]]) {
        throw 'UrlSet は [System.Collections.Generic.HashSet[string]] である必要があります。'
    }
    if ([string]::IsNullOrWhiteSpace($Text)) {
        return
    }
    $extAlt = Get-MediaExtensionRegex -Media $Media
    $absPattern = 'https?://[^\s<>()''"]+?\.(?:' + $extAlt + ')(?:\?[^\s<>()''"]*)?'
    $absMatches = [regex]::Matches($Text, $absPattern, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
    foreach ($m in $absMatches) {
        [void]$UrlSet.Add($m.Value.TrimEnd([char[]]'.);,'))
    }
    # 拡張子なし https://.../.../pdf_1（-BaseUrl 内の $relPattern が大きいHTMLで極端に遅い場合に備え、先に拾う）
    if ($Media -ne 'Images') {
        $pdfNameAbs = 'https?://[^\s<>"()]+/pdf[0-9A-Za-z_.%\-]+(?:\?[^?\s<>"()]*)?'
        $mPdfName = [regex]::Matches($Text, $pdfNameAbs, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
        foreach ($m in $mPdfName) {
            $u = $m.Value.TrimEnd([char[]]'.);,')
            [void]$UrlSet.Add($u)
        }
    }
    if (-not [string]::IsNullOrWhiteSpace($BaseUrl)) {
        try {
            $baseUri = [Uri]$BaseUrl
        }
        catch {
            Write-Warning "無効な -BaseUrl です: $BaseUrl"
            return
        }
        $relPattern = '(?<![:/])/(?:[^/\s<>()''"]+/){0,60}[^/\s<>()''"]+\.(?:' + $extAlt + ')(?:\?[^\s<>()''"]*)?'
        $relMatches = [regex]::Matches($Text, $relPattern, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
        foreach ($m in $relMatches) {
            $rel = $m.Value.TrimEnd([char[]]'.);,')
            try {
                $absolute = ([Uri]::new($baseUri, $rel)).AbsoluteUri
                [void]$UrlSet.Add($absolute)
            }
            catch {
                Write-Warning "相対URLの解決に失敗: $rel"
            }
        }
        # 拡張子なし・パス上の「ファイル名」が pdf で始まる（先頭 / 付き: .../user7/pdf_1）
        if ($Media -ne 'Images') {
            $pdfNameRel = '(?<![:/])/(?:[^/\s<>#''"　]+/)*pdf[0-9A-Za-z_.%\-]+'
            $mPdfRel = [regex]::Matches($Text, $pdfNameRel, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
            foreach ($m in $mPdfRel) {
                $rel2 = $m.Value.TrimEnd([char[]]'.);,')
                try {
                    $absolute2 = ([Uri]::new($baseUri, $rel2)).AbsoluteUri
                    [void]$UrlSet.Add($absolute2)
                }
                catch {
                    Write-Warning "相対URLの解決に失敗: $rel2"
                }
            }
            # Drupal: 本文の「sites/.../pdf_1」や「sites/.../ms06.pdf」が <a> なし（先頭 / なし）の行
            $mSitesPlain = [regex]::Matches( $Text, '(?i)(sites/[^"''\s<>#]+/pdf[0-9A-Za-z_.%\-]+)', [System.Text.RegularExpressions.RegexOptions]::Singleline )
            foreach ($m in $mSitesPlain) {
                $g = $m.Groups[1].Value.Trim()
                try { [void]$UrlSet.Add(([Uri]::new($baseUri, $g)).AbsoluteUri) }
                catch { Write-Warning "相対(sites/.../pdf*)の解決に失敗: $g" }
            }
            $mSitesPdfExt = [regex]::Matches( $Text, '(?i)(sites/[^"''\s<>#]+?\.pdf[0-9A-Za-z]*)', [System.Text.RegularExpressions.RegexOptions]::Singleline )
            foreach ($m in $mSitesPdfExt) {
                $g = $m.Groups[1].Value.Trim()
                try { [void]$UrlSet.Add(([Uri]::new($baseUri, $g)).AbsoluteUri) }
                catch { Write-Warning "相対(sites/...拡張子pdf*)の解決に失敗: $g" }
            }
            if ($false -and $mSites0.Count -eq 0) {
                $sitesNoLead2 = '(?i)()(sites/[^\s<>#''"　\]\)]*?/pdf[0-9A-Za-z_.%\-]+)'
                $mSites0 = [regex]::Matches($Text, $sitesNoLead2, [System.Text.RegularExpressions.RegexOptions]::Singleline)
            } else { }
            if ($mSites0.Count -eq 0) {
                $sitesNoLead3 = '(?i)(sites/[^\s<>#''"　\]\)]+/pdf[0-9A-Za-z_.%\-]+)'
                $mSites0 = [regex]::Matches($Text, $sitesNoLead3, [System.Text.RegularExpressions.RegexOptions]::Singleline)
            }
            foreach ($m in $mSites0) {
                $g = if ($m.Groups.Count -gt 1) { $m.Groups[1].Value } else { $m.Value }
                if ([string]::IsNullOrWhiteSpace($g)) { $g = $m.Groups[2].Value }
                if ($null -eq $g) { $g = $m.Value }
                $g = $g.Trim()
                if ($g -notlike 'sites/*') { continue }
                try {
                    $absoluteS = ([Uri]::new($baseUri, $g)).AbsoluteUri
                    [void]$UrlSet.Add($absoluteS)
                }
                catch {
                    Write-Warning "相対(sites)の解決に失敗: $g"
                }
            }
            # sites/.../xxx.pdf（拡張子付き、プレーンテキスト。例: ms06.pdf）
            $mSitesPdf = [regex]::Matches(
                $Text,
                '(?i) sites(/[^\s<>#''"　\]\)]+?\.pdf)',
                [System.Text.RegularExpressions.RegexOptions]::Singleline
            )
            if ($mSitesPdf.Count -eq 0) {
                $mSitesPdf = [regex]::Matches(
                    $Text,
                    '(?i)(sites/[^\s<>#''"　\]\)]+?\.pdf)',
                    [System.Text.RegularExpressions.RegexOptions]::Singleline
                )
            }
            foreach ($m in $mSitesPdf) {
                $gp = if ($m.Groups.Count -gt 1) { $m.Groups[1].Value } else { $m.Value }
                if ([string]::IsNullOrWhiteSpace($gp)) { $gp = $m.Groups[2].Value }
                if ($null -eq $gp) { $gp = $m.Value }
                $gp = $gp.Replace('`', '' ).Trim()  # 誤入り対策
                if ($gp -notlike 'sites/*') { continue }
                try {
                    [void]$UrlSet.Add(([Uri]::new($baseUri, $gp)).AbsoluteUri)
                }
                catch {
                    Write-Warning "相対(sites .pdf)の解決に失敗: $gp"
                }
            }
        }
    }
    if ($Media -ne 'Images') {
        $pdfNameAbs = 'https?://[^\s<>"()]+/pdf[0-9A-Za-z_.%\-]+(?:\?[^?\s<>"()]*)?'
        $mPdfName = [regex]::Matches($Text, $pdfNameAbs, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
        foreach ($m in $mPdfName) {
            $u = $m.Value.TrimEnd([char[]]'.);,')
            [void]$UrlSet.Add($u)
        }
    }
}

$pdfUrlSet = [System.Collections.Generic.HashSet[string]]::new([StringComparer]::OrdinalIgnoreCase)

if (-not [string]::IsNullOrWhiteSpace($UriList)) {
    if (-not (Test-Path -LiteralPath $UriList)) {
        Write-Warning "URLリストが見つかりません: $UriList"
        exit 1
    }
    $lineNo = 0
    foreach ($rawLine in Get-Content -LiteralPath $UriList -Encoding UTF8) {
        $lineNo++
        $line = $rawLine.Trim()
        if ([string]::IsNullOrWhiteSpace($line)) {
            continue
        }
        if ($line.StartsWith('#')) {
            continue
        }
        Write-Host "[$lineNo] ページ取得: $line"
        try {
            $html = (Invoke-WebRequest -Uri $line -UseBasicParsing -TimeoutSec $TimeoutSec).Content
            Add-PdfLinksToSet -Text $html -UrlSet $pdfUrlSet -BaseUrl $BaseUrl -Media $Media
        }
        catch {
            Write-Warning "ページ取得失敗 (行 $lineNo): $line -> $($_.Exception.Message)"
        }
    }
}
elseif (-not [string]::IsNullOrWhiteSpace($Uri)) {
    Write-Host ('ページ取得中: {0}（タイムアウト {1} 秒）' -f $Uri, $TimeoutSec)
    try {
        $PastedText = (Invoke-WebRequest -Uri $Uri -UseBasicParsing -TimeoutSec $TimeoutSec).Content
    }
    catch {
        Write-Warning "ページの取得に失敗しました: $($_.Exception.Message)"
        exit 1
    }
    if ([string]::IsNullOrWhiteSpace($PastedText)) {
        Write-Warning '取得したHTMLが空です。'
        exit 1
    }
    Write-Host '取得完了。PDF・画像のURLを抽出しています...'
    Add-PdfLinksToSet -Text $PastedText -UrlSet $pdfUrlSet -BaseUrl $BaseUrl -Media $Media
}
elseif ($FromClipboard) {
    $PastedText = Get-Clipboard -Raw
    if ([string]::IsNullOrWhiteSpace($PastedText)) {
        Write-Warning 'クリップボードが空です。'
        exit 1
    }
    Add-PdfLinksToSet -Text $PastedText -UrlSet $pdfUrlSet -BaseUrl $BaseUrl -Media $Media
}
elseif ($Path) {
    if (-not (Test-Path -LiteralPath $Path)) {
        Write-Warning "ファイルが見つかりません: $Path"
        exit 1
    }
    $PastedText = Get-Content -LiteralPath $Path -Raw -Encoding UTF8
    Add-PdfLinksToSet -Text $PastedText -UrlSet $pdfUrlSet -BaseUrl $BaseUrl -Media $Media
}
else {
    # ▼▼▼ ここにコピーしたテキストを貼り付け ▼▼▼
    $PastedText = @'
【ここにコピーしたテキストを貼り付け】
'@
    if ($PastedText -match '【ここにコピーしたテキストを貼り付け】') {
        Write-Warning 'まだ here-string に実際の内容を貼っていません。-Uri / -UriList / -FromClipboard / -Path のいずれか、またはスクリプト内を編集してください。'
        exit 1
    }
    Add-PdfLinksToSet -Text $PastedText -UrlSet $pdfUrlSet -BaseUrl $BaseUrl -Media $Media
}

$urls = Get-NormalizedMediaUrlList -Urls @($pdfUrlSet)

if ($urls.Count -eq 0) {
    Write-Warning "該当するファイルのURLが見つかりませんでした（-Media $Media）。HTMLソースを貼るか、-BaseUrl を指定して相対パスを解決してください。"
    exit 1
}

$mediaLabel = switch ($Media) {
    'Pdf' { 'PDF' }
    'Images' { '画像' }
    'Both' { 'PDF・画像' }
}
Write-Host "見つかった$mediaLabel : $($urls.Count) 件 -> $DestDir"

if ($ListOnly) {
    $urls | ForEach-Object { Write-Host $_ }
    exit 0
}

$idx = 0
foreach ($rawUrl in $urls) {
    $idx++
    if ($Interactive) {
        Write-Host ''
        Write-Host "----- [$idx / $($urls.Count)] -----"
        Write-Host $rawUrl
        $ans = Read-Host 'Enter=ダウンロード / n=この1件スキップ / q=ここで終了'
        $key = if ($null -eq $ans) { '' } else { $ans.Trim() }
        if ($key.Length -gt 0) {
            $c = $key.Substring(0, 1)
            if ($c -eq 'q' -or $c -eq 'Q') {
                Write-Host '中断しました（未処理のファイルはダウンロードしていません）。'
                break
            }
            if ($c -eq 'n' -or $c -eq 'N') {
                Write-Host 'スキップしました。'
                continue
            }
        }
    }
    $tmpFile = $null
    try {
        $tmpFile = Join-Path $env:TEMP ("amami-dl-" + [Guid]::NewGuid().ToString('N') + '.part')
        if (-not $Interactive) {
            Write-Host ("[{0}/{1}] {2}" -f $idx, $urls.Count, $rawUrl)
        }
        $tryUris = [System.Collections.Generic.List[string]]::new()
        [void]$tryUris.Add($rawUrl)
        if ($rawUrl -imatch 'pdf_1$' -and $rawUrl -inotmatch 'pdf_1\.pdf$') { [void]$tryUris.Add( $rawUrl + '.pdf' ) }
        $effective = $rawUrl
        $lastEx = $null
        $ti = 0
        $got = $false
        foreach ($tu in $tryUris) {
            if ($ti -gt 0 -and (Test-Path -LiteralPath $tmpFile)) {
                Remove-Item -LiteralPath $tmpFile -Force -ErrorAction SilentlyContinue
            }
            $ti++
            try {
                Invoke-WebDownloadWithRetries -Uri $tu -OutFile $tmpFile -TimeoutSec $TimeoutSec
                $effective = $tu
                if ($tu -cne $rawUrl) { Write-Host "  （拡張子 .pdf 付きのURLで取得: $tu）" }
                $got = $true
                break
            }
            catch { $lastEx = $_ }
        }
        if (-not $got) { throw $lastEx }
        $uri = [Uri]$effective
        $name = [Uri]::UnescapeDataString([System.IO.Path]::GetFileName($uri.LocalPath))
        $extPat = switch ($Media) {
            'Pdf' { '\.pdf$' }
            'Images' { '\.(jpe?g|png|gif|webp|avif|bmp|svg|ico)$' }
            'Both' { '\.(pdf|jpe?g|png|gif|webp|avif|bmp|svg|ico)$' }
        }
        if ([string]::IsNullOrWhiteSpace($name) -or $name -notmatch $extPat) {
            $fallbackExt = switch ($Media) {
                'Pdf' { '.pdf' }
                'Images' { '.jpg' }
                'Both' { '.bin' }   # 仮。保存後にシグネチャで .pdf / .jpg 等へ
            }
            $name = "download_$idx$fallbackExt"
        }
        $bytes = [System.IO.File]::ReadAllBytes($tmpFile)
        $sniff = Get-ExtensionFromFileSignature -Bytes $bytes
        $finalName = $name
        if ($name -match '\.bin$' -and $null -ne $sniff) {
            $finalName = ('download_{0}{1}' -f $idx, $sniff)
        }
        elseif ($null -ne $sniff) {
            $base = [System.IO.Path]::GetFileNameWithoutExtension($name)
            $extFromPath = [System.IO.Path]::GetExtension($name)
            if ($extFromPath -eq '' -or $extFromPath -eq '.bin') {
                $finalName = $base + $sniff
            }
        }
        $outPath = Join-Path $DestDir $finalName
        if (Test-Path -LiteralPath $outPath) {
            $stem = [System.IO.Path]::GetFileNameWithoutExtension($finalName)
            $ext = [System.IO.Path]::GetExtension($finalName)
            if ([string]::IsNullOrWhiteSpace($ext)) { $ext = if ($null -ne $sniff) { $sniff } else { '.bin' } }
            $outPath = Join-Path $DestDir ("{0}_{1}{2}" -f $stem, (Get-Date -Format 'yyyyMMddHHmmss'), $ext)
        }
        Move-Item -LiteralPath $tmpFile -Destination $outPath -Force
        if ($finalName -match '\.bin$' -or ($name -match '\.bin$' -and $outPath -notmatch '\.bin$')) {
            Write-Host "  （拡張子はファイル内容から判定）"
        }
        Write-Host "保存: $outPath"
    }
    catch {
        if ($null -ne $tmpFile -and (Test-Path -LiteralPath $tmpFile)) {
            Remove-Item -LiteralPath $tmpFile -Force -ErrorAction SilentlyContinue
        }
        $hint = ''
        if ($rawUrl -imatch 'pdf_[0-9]+$' -and $_.Exception.Message -match '404') {
            $hint = ' （拡張子なしリンクはサーバーに実体がないことがあります。WordPressの本文で正しいメディアURLに差し替えてください。）'
        }
        Write-Warning "失敗: $rawUrl -> $($_.Exception.Message)$hint"
    }
    if (-not $Interactive) {
        Start-Sleep -Milliseconds 600
    }
}

Write-Host '完了しました。'
