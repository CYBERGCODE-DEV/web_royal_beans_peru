[CmdletBinding()]
param(
    [ValidateSet('staging', 'production')]
    [string] $Environment = 'staging',
    [switch] $BuildOnly
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$outPath = Join-Path $projectRoot 'out'
$distPath = Join-Path $projectRoot ("dist-$Environment")
$zipPath = Join-Path $projectRoot ("dist-$Environment.zip")
$siteUrl = if ($Environment -eq 'production') { 'https://royalbeansperu.com' } else { 'https://darkseagreen-squirrel-657050.hostingersite.com' }
$indexable = if ($Environment -eq 'production') { 'true' } else { 'false' }

Set-Location $projectRoot
$env:NEXT_PUBLIC_DEPLOYMENT_ENV = $Environment
$env:NEXT_PUBLIC_SITE_URL = $siteUrl
$env:NEXT_PUBLIC_INDEXABLE = $indexable

$generatedPaths = @((Join-Path $projectRoot '.next'), $outPath)
if (-not $BuildOnly) { $generatedPaths += @($distPath, $zipPath) }
foreach ($generatedPath in $generatedPaths) {
    if (Test-Path -LiteralPath $generatedPath) {
        Remove-Item -LiteralPath $generatedPath -Recurse -Force
    }
}

& npm.cmd run build
if ($LASTEXITCODE -ne 0) {
    throw "Next.js build failed with exit code $LASTEXITCODE."
}

$textExtensions = @('.html', '.txt', '.xml', '.json', '.js', '.css', '.php')
$textFiles = Get-ChildItem -LiteralPath $outPath -Recurse -File -Force | Where-Object { $_.Extension.ToLowerInvariant() -in $textExtensions -or $_.Name -eq '.htaccess' }
if ($Environment -eq 'production') {
    $stagingReferences = $textFiles | Select-String -SimpleMatch 'darkseagreen-squirrel-657050.hostingersite.com' -List
    if ($stagingReferences) {
        throw "Production build still references staging: $($stagingReferences.Path -join ', ')"
    }
    $localhostReferences = $textFiles | Where-Object { $_.Extension.ToLowerInvariant() -in @('.html', '.txt', '.xml') } | Select-String -SimpleMatch 'http://localhost:3000' -List
    if ($localhostReferences) {
        throw "Production build still references localhost: $($localhostReferences.Path -join ', ')"
    }
    if (Select-String -LiteralPath (Join-Path $outPath 'index.html') -SimpleMatch 'noindex' -Quiet) {
        throw 'Production HTML must not contain noindex metadata.'
    }
    foreach ($requiredReference in @(
        @{ Path = 'robots.txt'; Value = 'Sitemap: https://royalbeansperu.com/sitemap.xml' },
        @{ Path = 'sitemap.xml'; Value = 'https://royalbeansperu.com/' },
        @{ Path = 'index.html'; Value = 'https://royalbeansperu.com' }
    )) {
        $target = Join-Path $outPath $requiredReference.Path
        if (-not (Select-String -LiteralPath $target -SimpleMatch $requiredReference.Value -Quiet)) {
            throw "Production SEO validation failed for $($requiredReference.Path): $($requiredReference.Value)"
        }
    }
} else {
    if (-not (Select-String -LiteralPath (Join-Path $outPath 'robots.txt') -SimpleMatch 'Disallow: /' -Quiet)) {
        throw 'Staging robots.txt must disallow crawling.'
    }
    if (-not (Select-String -LiteralPath (Join-Path $outPath 'index.html') -SimpleMatch 'noindex' -Quiet)) {
        throw 'Staging HTML must contain noindex metadata.'
    }
}

if ($BuildOnly) {
    Write-Output "ENVIRONMENT=$Environment"
    Write-Output "SITE_URL=$siteUrl"
    Write-Output "INDEXABLE=$indexable"
    Write-Output "OUT=$outPath"
    exit 0
}

$excludedFiles = @(
    'admin/install.php',
    'cms-config/database.php',
    'cms-config/database.local.php',
    'cms-config/database.local.example.php',
    'cms-config/products.seed.json',
    'cms-config/schema.sql'
)
$excludedNames = @('.DS_Store', 'Thumbs.db', 'desktop.ini')
$excludedExtensions = @('.map', '.log', '.tmp', '.temp', '.bak', '.old', '.orig', '.sql', '.swp', '.swo', '.tsbuildinfo')
$excludedDirectories = @('__MACOSX', '.next', 'node_modules', 'cache', 'tmp', 'temp')

function Get-DeployRelativePath([string] $basePath, [string] $filePath) {
    return $filePath.Substring($basePath.Length).TrimStart([char[]] "\/").Replace('\', '/')
}

$publishedFiles = 0
Get-ChildItem -LiteralPath $outPath -Recurse -File -Force | ForEach-Object {
    $relativePath = Get-DeployRelativePath $outPath $_.FullName
    $segments = $relativePath.Split('/')
    $publicPath = '/' + $relativePath
    $excluded = $relativePath -in $excludedFiles `
        -or $_.Name -in $excludedNames `
        -or $_.Extension.ToLowerInvariant() -in $excludedExtensions `
        -or @($segments | Where-Object { $_ -in $excludedDirectories }).Count -gt 0

    if (-not $excluded) {
        $destination = Join-Path $distPath $relativePath
        $destinationDirectory = Split-Path -Parent $destination
        New-Item -ItemType Directory -Path $destinationDirectory -Force | Out-Null
        Copy-Item -LiteralPath $_.FullName -Destination $destination -Force
        $publishedFiles++
    }
}

$requiredFiles = @(
    '.htaccess',
    'index.html',
    '404.html',
    'admin/index.php',
    'admin/_bootstrap.php',
    'api/products.php',
    'media-redirect.php',
    'product.php',
    'product-seo.php',
    'router.php',
    'robots.txt',
    'sitemap.php',
    'sitemap.xml'
)
foreach ($requiredFile in $requiredFiles) {
    if (-not (Test-Path -LiteralPath (Join-Path $distPath $requiredFile) -PathType Leaf)) {
        throw "Required deployment file is missing: $requiredFile"
    }
}

$forbiddenFiles = Get-ChildItem -LiteralPath $distPath -Recurse -File -Force | Where-Object {
    $relativePath = Get-DeployRelativePath $distPath $_.FullName
    $segments = $relativePath.Split('/')
    $relativePath -in $excludedFiles `
        -or $_.Name -in $excludedNames `
        -or $_.Extension.ToLowerInvariant() -in $excludedExtensions `
        -or @($segments | Where-Object { $_ -in $excludedDirectories }).Count -gt 0
}
if ($forbiddenFiles) {
    throw "Forbidden deployment files found: $($forbiddenFiles.FullName -join ', ')"
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$zipWriter = [IO.Compression.ZipFile]::Open($zipPath, [IO.Compression.ZipArchiveMode]::Create)
try {
    Get-ChildItem -LiteralPath $distPath -Recurse -File -Force | ForEach-Object {
        $entryName = Get-DeployRelativePath $distPath $_.FullName
        [IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zipWriter, $_.FullName, $entryName, [IO.Compression.CompressionLevel]::Optimal) | Out-Null
    }
} finally {
    $zipWriter.Dispose()
}

$zip = [IO.Compression.ZipFile]::OpenRead($zipPath)
try {
    if ($zip.Entries.Count -ne $publishedFiles) {
        throw "ZIP entry count ($($zip.Entries.Count)) does not match dist file count ($publishedFiles)."
    }
    if ($zip.GetEntry('cms-config/database.php') -or $zip.GetEntry('cms-config/database.local.php') -or $zip.GetEntry('admin/install.php')) {
        throw 'The ZIP contains a forbidden sensitive file.'
    }
} finally {
    $zip.Dispose()
}

$size = (Get-ChildItem -LiteralPath $distPath -Recurse -File | Measure-Object -Property Length -Sum).Sum
$sha256 = [Security.Cryptography.SHA256]::Create()
$zipStream = [IO.File]::OpenRead($zipPath)
try {
    $hash = -join ($sha256.ComputeHash($zipStream) | ForEach-Object { $_.ToString('X2') })
} finally {
    $zipStream.Dispose()
    $sha256.Dispose()
}
Write-Output "DIST=$distPath"
Write-Output "ZIP=$zipPath"
Write-Output "ENVIRONMENT=$Environment"
Write-Output "SITE_URL=$siteUrl"
Write-Output "INDEXABLE=$indexable"
Write-Output "FILES=$publishedFiles"
Write-Output "SIZE_BYTES=$size"
Write-Output "SHA256=$hash"
