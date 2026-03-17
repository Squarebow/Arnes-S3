# trim-vendor.ps1
# Odstrani vse AWS SDK servisne mape, ki niso potrebne za S3 funkcionalnost.
# Zaženi iz korenske mape vtičnika: C:\_DEV\Apps\arnes-s3\Arnes-S3
# Uporaba: .\trim-vendor.ps1

$srcPath = "vendor\aws\aws-sdk-php\src"

# Mape, ki jih OHRANIMO (enako kot v v1.0.8)
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
    "data"
)

# Pridobi vse mape v src/
$allFolders = Get-ChildItem -Path $srcPath -Directory

$deleted = 0
$kept = 0

foreach ($folder in $allFolders) {
    if ($keep -contains $folder.Name) {
        Write-Host "  OHRANI  $($folder.Name)" -ForegroundColor Green
        $kept++
    } else {
        Write-Host "  IZBRIŠI $($folder.Name)" -ForegroundColor Red
        Remove-Item -Recurse -Force $folder.FullName
        $deleted++
    }
}

Write-Host ""
Write-Host "Končano: $kept map ohranjenih, $deleted map izbrisanih." -ForegroundColor Cyan

# Prikaži novo velikost vendor mape
$sizeMB = (Get-ChildItem -Recurse "vendor" | Measure-Object -Property Length -Sum).Sum / 1MB
Write-Host ("Skupna velikost vendor/: {0:N2} MB" -f $sizeMB) -ForegroundColor Cyan