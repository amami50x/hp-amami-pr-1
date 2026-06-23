param(
    [Parameter(Mandatory = $true, Position = 0)]
    [ValidateSet('staged', 'committed', 'log')]
    [string]$Mode
)

$ErrorActionPreference = 'SilentlyContinue'
[Console]::OutputEncoding = [Text.UTF8Encoding]::new($false)

function Get-GitLines {
    param([object]$Raw)
    @($Raw | Where-Object { $_ -and ($_ -match '\S') })
}

function Show-GitLines {
    param(
        [string[]]$Lines,
        [string]$EmptyMessage
    )
    if (-not $Lines.Count) {
        Write-Host "  $EmptyMessage"
        return
    }
    foreach ($line in $Lines) {
        Write-Host ('  ' + $line.Trim())
    }
    Write-Host ''
    Write-Host ('  合計: ' + $Lines.Count + ' 件')
}

switch ($Mode) {
    'staged' {
        $lines = Get-GitLines (git -c core.quotepath=false diff --cached --name-status)
        Show-GitLines $lines '（対象ファイルなし）'
    }
    'committed' {
        $lines = Get-GitLines (git -c core.quotepath=false show --pretty=format: --name-only HEAD)
        Show-GitLines $lines '（ファイル名を取得できませんでした）'
    }
    'log' {
        $hash = git log -1 --pretty=format:%h
        $msg = git log -1 --pretty=format:%s
        $files = Get-GitLines (git -c core.quotepath=false show --pretty=format: --name-only HEAD)
        $out = @(
            'hp-amami-pr-1 Gitバックアップ 直近一覧',
            '日時: ' + (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'),
            "コミット: $hash $msg",
            '',
            '【バックアップしたファイル】'
        )
        if ($files.Count) {
            $out += $files | ForEach-Object { '  ' + $_.Trim() }
        } else {
            $out += '  （なし）'
        }
        $out += @(
            '',
            ('合計: ' + $files.Count + ' 件'),
            '',
            '※ ターミナルとこのファイルの両方でファイル名を確認できます。'
        )
        $path = Join-Path (Get-Location) 'git-backup-直近一覧.txt'
        $out | Out-File -LiteralPath $path -Encoding utf8
        Write-Host ''
        Write-Host 'ファイル名一覧を保存しました: git-backup-直近一覧.txt'
    }
}
