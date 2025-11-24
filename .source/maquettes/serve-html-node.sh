#!/bin/bash

# Script pour servir le dossier .source/maquette/html avec Node.js (http-server)

PORT=${1:-8000}
DIRECTORY="./html"

# Vérifier si le dossier existe
if [ ! -d "$DIRECTORY" ]; then
    echo "❌ Le dossier $DIRECTORY n'existe pas."
    echo "📁 Création du dossier..."
    mkdir -p "$DIRECTORY"
    echo "✅ Dossier créé. Vous pouvez maintenant y ajouter vos fichiers HTML."
fi

# Vérifier si Node.js est disponible
if command -v npx &> /dev/null; then
    echo "🚀 Démarrage du serveur HTTP avec Node.js sur le port $PORT"
    echo "📂 Dossier servi: $DIRECTORY"
    echo "🌐 Accès local: http://localhost:$PORT"
    echo "🌍 Accès réseau: http://$(hostname -I | awk '{print $1}'):$PORT"
    echo ""
    echo "Appuyez sur Ctrl+C pour arrêter le serveur"
    echo ""
    npx --yes http-server "$DIRECTORY" -p "$PORT" -o
else
    echo "❌ Node.js/npx n'est pas installé sur votre système."
    echo "💡 Utilisez plutôt: ./serve-html.sh"
    exit 1
fi

