# ============================================================
# VÉRIFICATION POST-INSTALLATION SUPABASE (PowerShell)
# ============================================================
# Script pour vérifier que tout est bien configuré

Write-Host "🔍 Vérification de l'intégration Supabase..." -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# Vérifier les fichiers critiques
Write-Host "📋 Vérification des fichiers critiques..." -ForegroundColor Yellow
$files = @("supabase.js", "api-client.js", "index.html")

foreach ($file in $files) {
    if (Test-Path $file) {
        Write-Host "✅ $file" -ForegroundColor Green
    } else {
        Write-Host "❌ $file (MANQUANT!)" -ForegroundColor Red
        exit 1
    }
}

Write-Host ""
Write-Host "📋 Vérification des outils..." -ForegroundColor Yellow
$tools = @("test-supabase.html", "check-supabase-connection.js", "sync-helper.js")

foreach ($tool in $tools) {
    if (Test-Path $tool) {
        Write-Host "✅ $tool" -ForegroundColor Green
    } else {
        Write-Host "⚠️  $tool (optionnel, mais recommandé)" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "📚 Vérification de la documentation..." -ForegroundColor Yellow
$docs = @("QUICK_START.md", "SUPABASE_SETUP.md", "INDEX.md", "START_HERE.txt")

foreach ($doc in $docs) {
    if (Test-Path $doc) {
        Write-Host "✅ $doc" -ForegroundColor Green
    } else {
        Write-Host "⚠️  $doc (optionnel)" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "✅ VÉRIFICATION COMPLÈTE!" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""
Write-Host "🚀 Prochaines étapes:" -ForegroundColor Cyan
Write-Host "1. Ouvrez http://localhost/Projet/Site/test-supabase.html" -ForegroundColor White
Write-Host "2. Cliquez sur 'Charger les Manhwas'" -ForegroundColor White
Write-Host "3. Vérifiez que vos données s'affichent" -ForegroundColor White
Write-Host ""
Write-Host "📖 Consultez QUICK_START.md ou START_HERE.txt pour commencer" -ForegroundColor Cyan
Write-Host ""
