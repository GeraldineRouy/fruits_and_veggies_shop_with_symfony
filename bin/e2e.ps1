# Script E2E : démarre le serveur Symfony, lance les tests Playwright, puis arrête le serveur

$ServerPort = 8000
$ServerProcess = $null

try {
    Write-Host "Démarrage du serveur Symfony sur le port $ServerPort..." -ForegroundColor Cyan

    # Vérifier si symfony CLI est disponible
    $symfonyCli = Get-Command symfony -ErrorAction SilentlyContinue

    if ($symfonyCli) {
        # Démarrer le serveur en arrière-plan
        $ServerProcess = Start-Process -FilePath "symfony" -ArgumentList "server:start --port=$ServerPort --no-tls --daemon" -NoNewWindow -PassThru
        Start-Sleep -Seconds 3
    } else {
        Write-Host "symfony CLI non trouvé, utilisation de php -S..." -ForegroundColor Yellow
        $ServerProcess = Start-Process -FilePath "php" -ArgumentList "-S 127.0.0.1:$ServerPort -t public" -NoNewWindow -PassThru
        Start-Sleep -Seconds 2
    }

    # Vérifier que le serveur répond
    try {
        $response = Invoke-WebRequest -Uri "http://127.0.0.1:$ServerPort" -TimeoutSec 5 -UseBasicParsing
        Write-Host "Serveur prêt (HTTP $($response.StatusCode))." -ForegroundColor Green
    } catch {
        Write-Host "Erreur : le serveur ne répond pas." -ForegroundColor Red
        exit 1
    }

    # Lancer les tests Playwright
    Write-Host "Lancement des tests Playwright..." -ForegroundColor Cyan
    npx playwright test
    $exitCode = $LASTEXITCODE

    if ($exitCode -eq 0) {
        Write-Host "Tous les tests E2E ont réussi !" -ForegroundColor Green
    } else {
        Write-Host "Certains tests E2E ont échoué." -ForegroundColor Red
    }
} finally {
    # Arrêter le serveur
    Write-Host "Arrêt du serveur..." -ForegroundColor Cyan

    $symfonyCli = Get-Command symfony -ErrorAction SilentlyContinue
    if ($symfonyCli) {
        Start-Process -FilePath "symfony" -ArgumentList "server:stop" -NoNewWindow -Wait
    } elseif ($ServerProcess -and !$ServerProcess.HasExited) {
        $ServerProcess.Kill()
    }
}

exit $exitCode
