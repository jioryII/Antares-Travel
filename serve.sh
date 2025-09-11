#!/bin/bash
# Script para levantar un servidor PHP en el directorio actual

# Puerto por defecto (puedes cambiarlo)
PORT=8000

# Directorio actual
DIR=$(pwd)

echo "Levantando servidor PHP en $DIR en el puerto $PORT..."
echo "Presiona Ctrl+C para detenerlo."

# Levantar el servidor PHP
php -S 0.0.0.0:$PORT -t "$DIR"
