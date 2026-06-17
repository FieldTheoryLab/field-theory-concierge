param(
    [string]$LocalPluginPath = "C:\Users\jamie\Local Sites\ftl-2026\app\public\wp-content\plugins\field-theory-concierge"
)

$ErrorActionPreference = "Stop"

$repoRoot = Split-Path -Parent $PSScriptRoot
$pluginFile = Join-Path $repoRoot "field-theory-concierge.php"

if (!(Test-Path -LiteralPath $pluginFile -PathType Leaf)) {
    throw "Could not find field-theory-concierge.php at $pluginFile"
}

if (!(Test-Path -LiteralPath $LocalPluginPath -PathType Container)) {
    New-Item -ItemType Directory -Path $LocalPluginPath -Force | Out-Null
}

$items = @(
    "assets",
    "includes",
    "templates",
    "field-theory-concierge.php",
    "FieldTheoryBackground.jpg",
    "FieldTheory_2026_BrighterColors.svg",
    "FieldTheory_2026_BrighterColorsIcon.svg",
    "README.md",
    "readme.txt",
    "RELEASE_CHECKLIST.md"
)

foreach ($item in $items) {
    $source = Join-Path $repoRoot $item

    if (!(Test-Path -LiteralPath $source)) {
        Write-Warning "Skipping missing source: $source"
        continue
    }

    if (Test-Path -LiteralPath $source -PathType Container) {
        $destinationRoot = Join-Path $LocalPluginPath $item
        New-Item -ItemType Directory -Path $destinationRoot -Force | Out-Null

        Get-ChildItem -LiteralPath $source -Recurse -File | ForEach-Object {
            $relativePath = $_.FullName.Substring($source.Length + 1)
            $destination = Join-Path $destinationRoot $relativePath
            $destinationDirectory = Split-Path -Parent $destination
            New-Item -ItemType Directory -Path $destinationDirectory -Force | Out-Null
            Copy-Item -LiteralPath $_.FullName -Destination $destination -Force
        }
    } else {
        $destination = Join-Path $LocalPluginPath $item
        Copy-Item -LiteralPath $source -Destination $destination -Force
    }
}

Write-Host "Synced Field Theory Concierge to:"
Write-Host $LocalPluginPath
