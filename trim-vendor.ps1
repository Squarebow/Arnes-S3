# trim-vendor.ps1
# Odstrani vse AWS SDK servisne mape, ki niso potrebne za S3 funkcionalnost.
# Čisti TAKO src/ PHP razrede KOT src/data/ API definicije.
# Zaženi iz korenske mape vtičnika: C:\_DEV\Apps\arnes-s3\Arnes-S3
# Uporaba: .\trim-vendor.ps1

$srcPath  = "vendor\aws\aws-sdk-php\src"
$dataPath = "vendor\aws\aws-sdk-php\src\data"

# ──────────────────────────────────────────────
# 1. Mape v src/, ki jih OHRANIMO
# ──────────────────────────────────────────────
$keep = @(
    "Api",
    "ApiGateway",
    "ApiGatewayManagementApi",
    "ApiGatewayV2",
    "Arn",
    "Auth",
    "ClientSideMonitoring",
    "Configuration",
    "Credentials",
    "Crypto",
    "DefaultsMode",
    "Endpoint",
    "EndpointDiscovery",
    "EndpointV2",
    "Exception",
    "Handler",
    "Identity",
    "Multipart",
    "Retry",
    "S3",
    "S3Control",
    "S3Outposts",
    "S3Tables",
    "S3Vectors",
    "Script",
    "Signature",
    "Sts",
    "Token",
    "data"          # mapa data se obdela posebej spodaj
)

# ──────────────────────────────────────────────
# 2. Mape v src/data/, ki jih OHRANIMO
#    (samo S3 in STS servisni podatki)
#    Datoteke v korenu data/ ostanejo samodejno.
# ──────────────────────────────────────────────
$keepData = @(
    "s3",
    "s3control",
    "s3outposts",
    "s3tables",
    "s3vectors",
    "sts"
)

# ──────────────────────────────────────────────
# KORAK 1: Počisti src/ mape
# ──────────────────────────────────────────────
Write-Host ""
Write-Host "=== KORAK 1: Čiščenje src/ map ===" -ForegroundColor Yellow

$allSrcFolders = Get-ChildItem -Path $srcPath -Directory
$deletedSrc = 0
$keptSrc = 0

foreach ($folder in $allSrcFolders) {
    if ($keep -contains $folder.Name) {
        Write-Host "  OHRANI  src/$($folder.Name)" -ForegroundColor Green
        $keptSrc++
    } else {
        Write-Host "  IZBRIŠI src/$($folder.Name)" -ForegroundColor Red
        Remove-Item -Recurse -Force $folder.FullName
        $deletedSrc++
    }
}

# ──────────────────────────────────────────────
# KORAK 2: Počisti src/data/ podmape
# ──────────────────────────────────────────────
Write-Host ""
Write-Host "=== KORAK 2: Čiščenje src/data/ map ===" -ForegroundColor Yellow

$allDataFolders = Get-ChildItem -Path $dataPath -Directory
$deletedData = 0
$keptData = 0

foreach ($folder in $allDataFolders) {
    if ($keepData -contains $folder.Name) {
        Write-Host "  OHRANI  data/$($folder.Name)" -ForegroundColor Green
        $keptData++
    } else {
        Write-Host "  IZBRIŠI data/$($folder.Name)" -ForegroundColor Red
        Remove-Item -Recurse -Force $folder.FullName
        $deletedData++
    }
}

# ──────────────────────────────────────────────
# REZULTAT
# ──────────────────────────────────────────────
Write-Host ""
Write-Host "=== REZULTAT ===" -ForegroundColor Yellow
Write-Host "src/:  $keptSrc map ohranjenih, $deletedSrc map izbrisanih." -ForegroundColor Cyan
Write-Host "data/: $keptData map ohranjenih, $deletedData map izbrisanih." -ForegroundColor Cyan

$sizeMB = (Get-ChildItem -Recurse "vendor" | Measure-Object -Property Length -Sum).Sum / 1MB
Write-Host ("Skupna velikost vendor/: {0:N2} MB" -f $sizeMB) -ForegroundColor Cyan
