# Builds the theme zip with ZIP-spec forward slashes in the entry paths.
# Windows PowerShell 5.1's Compress-Archive writes backslashes, which Linux PHP
# reads as one flat file named "synergi\style.css" instead of a folder -- that is
# the "theme is missing the style.css stylesheet" install error.
param(
    [string]$Root = (Split-Path -Parent $PSScriptRoot),
    [string]$ZipName = "synergi-theme.zip"
)

$src = Join-Path $Root "synergi"
$dst = Join-Path $Root $ZipName
$sep = [string][char]92   # backslash, kept out of the literal so it reads as data

if (Test-Path -LiteralPath $dst) { Remove-Item -LiteralPath $dst -Force }

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$zip = [System.IO.Compression.ZipFile]::Open($dst, 'Create')
foreach ($f in Get-ChildItem -LiteralPath $src -Recurse -File) {
    $rel = "synergi/" + $f.FullName.Substring($src.Length + 1).Replace($sep, "/")
    $entry = $zip.CreateEntry($rel, [System.IO.Compression.CompressionLevel]::Optimal)
    $out = $entry.Open()
    $bytes = [System.IO.File]::ReadAllBytes($f.FullName)
    $out.Write($bytes, 0, $bytes.Length)
    $out.Close()
}
$zip.Dispose()

$z = [System.IO.Compression.ZipFile]::OpenRead($dst)
$bad = @($z.Entries | Where-Object { $_.FullName.Contains($sep) }).Count
$roots = ($z.Entries | ForEach-Object { $_.FullName.Split("/")[0] } | Sort-Object -Unique) -join ","
$hasStyle = @($z.Entries | Where-Object { $_.FullName -eq "synergi/style.css" }).Count
Write-Output ("entries: {0}  backslash entries: {1}  roots: {2}  synergi/style.css: {3}  size: {4:N0} KB" -f $z.Entries.Count, $bad, $roots, $hasStyle, ((Get-Item $dst).Length / 1KB))
$z.Dispose()
