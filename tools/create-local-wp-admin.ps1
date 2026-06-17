param(
    [string]$LocalWpPath = "C:\Users\jamie\Local Sites\ftl-2026\app\public",
    [string]$SiteUrl = "http://ftl-2026.local/",
    [string]$Username = "jamie-local",
    [string]$Email = "jamie-local@example.test",
    [string]$Password = "",
    [string]$Token = ""
)

$ErrorActionPreference = "Stop"

function New-LocalSecret([int]$Length = 16) {
    $chars = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789"
    $bytes = New-Object byte[] $Length
    [System.Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($bytes)
    $secret = ""
    foreach ($byte in $bytes) {
        $secret += $chars[$byte % $chars.Length]
    }
    return $secret
}

function Escape-PhpSingleQuoted([string]$Value) {
    return $Value.Replace("\", "\\").Replace("'", "\'")
}

if (!(Test-Path -LiteralPath (Join-Path $LocalWpPath "wp-load.php") -PathType Leaf)) {
    throw "Could not find wp-load.php in $LocalWpPath"
}

if ($Password -eq "") {
    $Password = "FtLocal-" + (New-LocalSecret 14) + "!"
}

if ($Token -eq "") {
    $Token = New-LocalSecret 32
}

$muPluginsPath = Join-Path $LocalWpPath "wp-content\mu-plugins"
New-Item -ItemType Directory -Path $muPluginsPath -Force | Out-Null

$bootstrapPath = Join-Path $muPluginsPath "codex-local-admin-bootstrap.php"

$phpUsername = Escape-PhpSingleQuoted $Username
$phpEmail = Escape-PhpSingleQuoted $Email
$phpPassword = Escape-PhpSingleQuoted $Password
$phpToken = Escape-PhpSingleQuoted $Token

$bootstrap = @"
<?php
/**
 * Plugin Name: Codex Local Admin Bootstrap
 * Description: Temporary local-only admin bootstrap. Deletes itself after a successful tokenized run.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    `$expected_token = '$phpToken';
    if (!isset(`$_GET['codex_local_admin']) || !hash_equals(`$expected_token, (string) `$_GET['codex_local_admin'])) {
        return;
    }

    `$username = '$phpUsername';
    `$email = '$phpEmail';
    `$password = '$phpPassword';
    `$user = get_user_by('login', `$username);

    if (`$user) {
        `$user_id = `$user->ID;
        wp_set_password(`$password, `$user_id);
    } else {
        `$user_id = wp_create_user(`$username, `$password, `$email);
    }

    header('Content-Type: text/plain; charset=utf-8');

    if (is_wp_error(`$user_id)) {
        status_header(500);
        echo 'Could not create local admin: ' . `$user_id->get_error_message();
        exit;
    }

    `$wp_user = new WP_User(`$user_id);
    `$wp_user->set_role('administrator');

    `$removed = @unlink(__FILE__);

    echo "Local admin ready\n";
    echo "Username: " . `$username . "\n";
    echo "Bootstrap removed: " . (`$removed ? 'yes' : 'no') . "\n";
    exit;
});
"@

Set-Content -LiteralPath $bootstrapPath -Value $bootstrap -Encoding UTF8

$separator = if ($SiteUrl.Contains("?")) { "&" } else { "?" }
$triggerUrl = $SiteUrl.TrimEnd("/") + "/" + $separator + "codex_local_admin=$Token"
$response = Invoke-WebRequest -Uri $triggerUrl -UseBasicParsing -TimeoutSec 30

Write-Host $response.Content.Trim()
Write-Host ""
Write-Host "Login URL: $($SiteUrl.TrimEnd('/'))/wp-admin/"
Write-Host "Username: $Username"
Write-Host "Password: $Password"
Write-Host "Temporary bootstrap exists: $(Test-Path -LiteralPath $bootstrapPath)"
