#!/bin/bash
# ============================================================
# VÉRIFICATION POST-INSTALLATION SUPABASE
# ============================================================
# Script pour vérifier que tout est bien configuré

echo "🔍 Vérification de l'intégration Supabase..."
echo "==========================================="
echo ""

# Vérifier les fichiers critiques
echo "📋 Vérification des fichiers critiques..."
FILES=("supabase.js" "api-client.js" "index.html")

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file"
    else
        echo "❌ $file (MANQUANT!)"
        exit 1
    fi
done

echo ""
echo "📋 Vérification des outils..."
TOOLS=("test-supabase.html" "check-supabase-connection.js" "sync-helper.js")

for tool in "${TOOLS[@]}"; do
    if [ -f "$tool" ]; then
        echo "✅ $tool"
    else
        echo "⚠️  $tool (optionnel, mais recommandé)"
    fi
done

echo ""
echo "📚 Vérification de la documentation..."
DOCS=("QUICK_START.md" "SUPABASE_SETUP.md" "INDEX.md")

for doc in "${DOCS[@]}"; do
    if [ -f "$doc" ]; then
        echo "✅ $doc"
    else
        echo "⚠️  $doc (optionnel)"
    fi
done

echo ""
echo "✅ VÉRIFICATION COMPLÈTE!"
echo "==========================================="
echo ""
echo "🚀 Prochaines étapes:"
echo "1. Ouvrez http://localhost/Projet/Site/test-supabase.html"
echo "2. Cliquez sur 'Charger les Manhwas'"
echo "3. Vérifiez que vos données s'affichent"
echo ""
echo "📖 Consultez QUICK_START.md pour commencer"
echo ""
