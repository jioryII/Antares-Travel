#!/bin/bash

# ==============================
# DETECCIÓN DEL SISTEMA
# ==============================
if [[ "$OSTYPE" == "linux-android"* ]] || [[ -n "$TERMUX_VERSION" ]] || [[ -d "$PREFIX" ]]; then
    SYSTEM="termux"
    echo -e "\033[1;36m🤖 Sistema detectado: Termux (Android)\033[0m"
elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
    SYSTEM="ubuntu"
    echo -e "\033[1;36m🐧 Sistema detectado: Ubuntu/Linux\033[0m"
else
    SYSTEM="unknown"
    echo -e "\033[1;33m⚠️ Sistema no identificado, usando configuración genérica\033[0m"
fi

# ==============================
# CONFIGURACIÓN DE RUTAS
# ==============================
if [ "$SYSTEM" == "termux" ]; then
    PROJECT_PATH="$HOME/vXcode/Antares-Travel"
    BASH_RC="$PREFIX/etc/bash.bashrc"
else
    PROJECT_PATH="$(pwd)"
    BASH_RC="$HOME/.bashrc"
fi

echo -e "\033[1;32m📂 Directorio del proyecto: $PROJECT_PATH\033[0m"

# Otorgar permisos de ejecución
chmod +x "$PROJECT_PATH"/* 2>/dev/null

# ==============================
# VALIDACIÓN E INSTALACIÓN DE DEPENDENCIAS
# ==============================

echo -e "\033[1;33m🔍 Verificando dependencias...\033[0m"

# Verificar PHP
if ! command -v php >/dev/null 2>&1; then
    echo -e "\033[1;31m❌ PHP no está instalado\033[0m"
    if [ "$SYSTEM" == "termux" ]; then
        echo -e "\033[1;33m📦 Instalando PHP en Termux...\033[0m"
        pkg install php -y
    elif [ "$SYSTEM" == "ubuntu" ]; then
        echo -e "\033[1;33m📦 Instalando PHP en Ubuntu...\033[0m"
        sudo apt update && sudo apt install php php-cli php-mysql php-mbstring php-xml php-curl -y
    fi
else
    echo -e "\033[1;32m✅ PHP encontrado: $(php -v | head -n1)\033[0m"
fi

# Verificar MySQL/MariaDB
if ! command -v mysql >/dev/null 2>&1 && ! command -v mariadb >/dev/null 2>&1; then
    echo -e "\033[1;31m❌ MySQL/MariaDB no está instalado\033[0m"
    if [ "$SYSTEM" == "termux" ]; then
        echo -e "\033[1;33m📦 Instalando MariaDB en Termux...\033[0m"
        pkg install mariadb -y
    elif [ "$SYSTEM" == "ubuntu" ]; then
        echo -e "\033[1;33m💡 En Ubuntu, instala MySQL con: sudo apt install mysql-server\033[0m"
    fi
else
    echo -e "\033[1;32m✅ Base de datos encontrada\033[0m"
fi

# ==============================
# CONFIGURACIÓN ESPECÍFICA DE TERMUX
# ==============================
if [ "$SYSTEM" == "termux" ]; then
    echo -e "\033[1;35m🎨 Configurando interfaz de Termux...\033[0m"
    
    CONFIG_BASH=$(cat <<'EOF'
# =============================#
#    🌟 ANTARES TRAVEL 🌟      #
# =============================#

# Configuración del historial de comandos
shopt -s histappend
shopt -s histverify
export HISTCONTROL=ignoreboth

# Personalización del prompt con colores
PROMPT_DIRTRIM=2
PS1='\e[1;34m \e[1;32m\w\e[1;36m ➜ \e[1;37m'

# Interfaz visual al iniciar la terminal
clear
echo -e "\033[1;36m==========================================\033[0m"
echo -e "\033[1;33m    🚀 BIENVENIDO A ANTARES TRAVEL 🚀    \033[0m"
echo -e "\033[1;36m==========================================\033[0m"

echo -e "\033[1;34m █████╗ ███╗   ██╗████████╗ █████╗ ██████╗ ███████╗███████╗\033[0m"
echo -e "\033[1;34m██╔══██╗████╗  ██║╚══██╔══╝██╔══██╗██╔══██╗██╔════╝██╔════╝\033[0m"
echo -e "\033[1;34m███████║██╔██╗ ██║   ██║   ███████║██████╔╝█████╗  ███████╗\033[0m"
echo -e "\033[1;34m██╔══██║██║╚██╗██║   ██║   ██╔══██║██╔══██╗██╔══╝  ╚════██║\033[0m"
echo -e "\033[1;34m██║  ██║██║ ╚████║   ██║   ██║  ██║██║  ██║███████╗███████║\033[0m"
echo -e "\033[1;34m╚═╝  ╚═╝╚═╝  ╚═══╝   ╚═╝   ╚═╝  ╚═╝╚═╝  ╚═╝╚══════╝╚══════╝\033[0m"

echo -e "\n\033[1;32m🌟 Proyecto: \033[0mAntares Travel"
echo -e "\033[1;32m👤 Autor: \033[0mAndi"
echo -e "\033[1;32m🔌 Puerto usado: \033[0m8000"
echo -e "\033[1;32m📝 Descripción: \033[0mSistema de administración de tours y reservas"
echo -e "\033[1;32m🟢 Estado: \033[0mServidor corriendo"

# Verificar configuración de BD
DB_CONFIG="$HOME/vXcode/Antares-Travel/src/config/conexion.php"
if [ -f "$DB_CONFIG" ]; then
    echo -e "\033[1;34m✔ Configuración de base de datos encontrada.\033[0m"
else
    echo -e "\033[1;31m⚠ Archivo de configuración de BD no encontrado.\033[0m"
fi

# Iniciar servidor automáticamente
cd $HOME/vXcode/Antares-Travel || exit
php -S 0.0.0.0:8000 -t . &

# Abrir en navegador (Android)
am start -n com.android.chrome/com.google.android.apps.chrome.Main -d http://localhost:8000/index.php &
EOF
)

    # Sobrescribir bash.bashrc solo en Termux
    echo "$CONFIG_BASH" > "$BASH_RC"
    echo -e "\033[1;32m✅ bash.bashrc actualizado en Termux\033[0m"
fi

# ==============================
# FUNCIÓN PARA ABRIR NAVEGADOR
# ==============================
open_browser() {
    if [ "$SYSTEM" == "termux" ]; then
        am start -n com.android.chrome/com.google.android.apps.chrome.Main -d http://localhost:8000/index.php &
    elif [ "$SYSTEM" == "ubuntu" ]; then
        if command -v xdg-open >/dev/null 2>&1; then
            xdg-open http://localhost:8000/index.php &
        elif command -v firefox >/dev/null 2>&1; then
            firefox http://localhost:8000/index.php &
        elif command -v google-chrome >/dev/null 2>&1; then
            google-chrome http://localhost:8000/index.php &
        else
            echo -e "\033[1;33m🌐 Abre manualmente: http://localhost:8000\033[0m"
        fi
    fi
}

# ==============================
# FUNCIÓN PARA INICIAR SERVIDOR
# ==============================
start_server() {
    echo -e "\033[1;32m🚀 Iniciando servidor Antares Travel...\033[0m"
    cd "$PROJECT_PATH" || exit
    echo -e "\033[1;36m📂 Directorio: $(pwd)\033[0m"
    echo -e "\033[1;36m🌐 URL: http://localhost:8000\033[0m"
    
    # Iniciar servidor en background
    php -S 0.0.0.0:8000 -t . > server.log 2>&1 &
    SERVER_PID=$!
    
    echo -e "\033[1;32m✅ Servidor iniciado (PID: $SERVER_PID)\033[0m"
    
    # Esperar un momento para que el servidor esté listo
    sleep 2
    
    # Abrir navegador automáticamente
    echo -e "\033[1;33m🌐 Abriendo navegador...\033[0m"
    open_browser
    
    return $SERVER_PID
}

# ==============================
# MENÚ INTERACTIVO
# ==============================

show_ubuntu_banner() {
    clear
    echo -e "\033[1;36m╔══════════════════════════════════════════════════════════════╗\033[0m"
    echo -e "\033[1;36m║\033[1;33m                    🌟 ANTARES TRAVEL 🌟                      \033[1;36m║\033[0m"
    echo -e "\033[1;36m╠══════════════════════════════════════════════════════════════╣\033[0m"
    echo -e "\033[1;36m║\033[1;34m     █████╗ ███╗   ██╗████████╗ █████╗ ██████╗ ███████╗     \033[1;36m║\033[0m"
    echo -e "\033[1;36m║\033[1;34m    ██╔══██╗████╗  ██║╚══██╔══╝██╔══██╗██╔══██╗██╔════╝     \033[1;36m║\033[0m"
    echo -e "\033[1;36m║\033[1;34m    ███████║██╔██╗ ██║   ██║   ███████║██████╔╝█████╗       \033[1;36m║\033[0m"
    echo -e "\033[1;36m║\033[1;34m    ██╔══██║██║╚██╗██║   ██║   ██╔══██║██╔══██╗██╔══╝       \033[1;36m║\033[0m"
    echo -e "\033[1;36m║\033[1;34m    ██║  ██║██║ ╚████║   ██║   ██║  ██║██║  ██║███████╗     \033[1;36m║\033[0m"
    echo -e "\033[1;36m║\033[1;34m    ╚═╝  ╚═╝╚═╝  ╚═══╝   ╚═╝   ╚═╝  ╚═╝╚═╝  ╚═╝╚══════╝     \033[1;36m║\033[0m"
    echo -e "\033[1;36m╠══════════════════════════════════════════════════════════════╣\033[0m"
    echo -e "\033[1;36m║\033[1;32m  🚀 Sistema de Gestión de Tours y Reservas                  \033[1;36m║\033[0m"
    echo -e "\033[1;36m║\033[1;37m  💻 Plataforma: Ubuntu/Linux                                \033[1;36m║\033[0m"
    echo -e "\033[1;36m║\033[1;35m  🌐 Puerto: 8000                                            \033[1;36m║\033[0m"
    echo -e "\033[1;36m║\033[1;33m  👨‍💻 Desarrollador: Andi                                      \033[1;36m║\033[0m"
    echo -e "\033[1;36m╚══════════════════════════════════════════════════════════════╝\033[0m"
    echo ""
}

show_termux_banner() {
    clear
    echo -e "\033[1;36m==========================================\033[0m"
    echo -e "\033[1;33m    🌍 ANTARES TRAVEL - SERVIDOR PHP 🚀   \033[0m"
    echo -e "\033[1;36m==========================================\033[0m"
    echo -e "\033[1;35m Sistema: $SYSTEM\033[0m"
    echo -e "\033[1;36m==========================================\033[0m"
}

show_ubuntu_menu() {
    echo -e "\033[1;36m┌─────────────────────────────────────────────────────────────┐\033[0m"
    echo -e "\033[1;36m│\033[1;33m                      🎮 PANEL DE CONTROL                      \033[1;36m│\033[0m"
    echo -e "\033[1;36m├─────────────────────────────────────────────────────────────┤\033[0m"
    echo -e "\033[1;36m│\033[1;32m  [1] 🚀 Iniciar Servidor + Navegador Automático            \033[1;36m│\033[0m"
    echo -e "\033[1;36m│\033[1;31m  [2] 🛑 Detener Servidor                                    \033[1;36m│\033[0m"
    echo -e "\033[1;36m│\033[1;33m  [3] 🌐 Abrir en Navegador                                  \033[1;36m│\033[0m"
    echo -e "\033[1;36m│\033[1;35m  [4] 📊 Estado del Servidor                                 \033[1;36m│\033[0m"
    echo -e "\033[1;36m│\033[1;34m  [5] ⚙️  Verificar Configuración                            \033[1;36m│\033[0m"
    echo -e "\033[1;36m│\033[1;37m  [6] 📋 Logs del Servidor                                   \033[1;36m│\033[0m"
    echo -e "\033[1;36m│\033[1;31m  [7] ❌ Salir                                               \033[1;36m│\033[0m"
    echo -e "\033[1;36m└─────────────────────────────────────────────────────────────┘\033[0m"
    echo ""
}

show_termux_menu() {
    echo -e "\033[1;34m 1) \033[1;32m🚀 Iniciar servidor y abrir navegador\033[0m"
    echo -e "\033[1;34m 2) \033[1;31m🛑 Detener servidor\033[0m"
    echo -e "\033[1;34m 3) \033[1;33m🌐 Abrir en navegador\033[0m"
    echo -e "\033[1;34m 4) \033[1;36m📊 Ver estado del servidor\033[0m"
    echo -e "\033[1;34m 5) \033[1;35m🗄️ Verificar configuración\033[0m"
    echo -e "\033[1;34m 6) \033[1;37m📋 Ver logs del servidor\033[0m"
    echo -e "\033[1;34m 7) \033[1;31m❌ Salir\033[0m"
    echo -e "\033[1;36m==========================================\033[0m"
}

show_status_bar() {
    # Verificar estado del servidor
    if pgrep -f "php -S 0.0.0.0:8000" > /dev/null; then
        SERVER_STATUS="\033[1;32m🟢 ACTIVO\033[0m"
    else
        SERVER_STATUS="\033[1;31m🔴 INACTIVO\033[0m"
    fi
    
    # Verificar PHP
    if command -v php >/dev/null 2>&1; then
        PHP_STATUS="\033[1;32m✅ PHP OK\033[0m"
    else
        PHP_STATUS="\033[1;31m❌ PHP NO\033[0m"
    fi
    
    # Verificar BD
    if command -v mysql >/dev/null 2>&1 || command -v mariadb >/dev/null 2>&1; then
        DB_STATUS="\033[1;32m✅ BD OK\033[0m"
    else
        DB_STATUS="\033[1;31m❌ BD NO\033[0m"
    fi
    
    if [ "$SYSTEM" == "ubuntu" ]; then
        echo -e "\033[1;36m┌─────────────────────────────────────────────────────────────┐\033[0m"
        echo -e "\033[1;36m│\033[1;37m  Estado: $SERVER_STATUS │ $PHP_STATUS │ $DB_STATUS │ \033[1;33m🌐 localhost:8000\033[1;36m  │\033[0m"
        echo -e "\033[1;36m└─────────────────────────────────────────────────────────────┘\033[0m"
        echo ""
    fi
}

while true; do
    if [ "$SYSTEM" == "ubuntu" ]; then
        show_ubuntu_banner
        show_status_bar
        show_ubuntu_menu
        echo -e "\033[1;36m╭─────────────────────────────────────────────────────────────╮\033[0m"
        echo -ne "\033[1;36m│\033[1;37m  Seleccione una opción \033[1;33m[1-7]\033[1;37m: \033[0m"
        read opcion
        echo -e "\033[1;36m╰─────────────────────────────────────────────────────────────╯\033[0m"
    else
        show_termux_banner
        show_termux_menu
        read -p $'\033[1;37mSeleccione una opción (1-7): \033[0m' opcion
    fi

    case $opcion in
        1)
            if [ "$SYSTEM" == "ubuntu" ]; then
                echo -e "\n\033[1;36m╔══════════════════════════════════════════════════════════════╗\033[0m"
                echo -e "\033[1;36m║\033[1;32m                    🚀 INICIANDO SERVIDOR                     \033[1;36m║\033[0m"
                echo -e "\033[1;36m╚══════════════════════════════════════════════════════════════╝\033[0m"
            fi
            start_server
            if [ "$SYSTEM" == "ubuntu" ]; then
                echo -e "\n\033[1;32m┌─────────────────────────────────────────────────────────────┐\033[0m"
                echo -e "\033[1;32m│  ✅ Servidor iniciado correctamente                           │\033[0m"
                echo -e "\033[1;32m│  🌐 Navegador abierto automáticamente                         │\033[0m"
                echo -e "\033[1;32m│  📍 URL: http://localhost:8000                                │\033[0m"
                echo -e "\033[1;32m└─────────────────────────────────────────────────────────────┘\033[0m"
                read -p $'\n\033[1;37m👆 Presiona Enter para continuar...\033[0m'
            else
                sleep 3
            fi
            ;;
        2)
            if [ "$SYSTEM" == "ubuntu" ]; then
                echo -e "\n\033[1;36m╔══════════════════════════════════════════════════════════════╗\033[0m"
                echo -e "\033[1;36m║\033[1;31m                    🛑 DETENIENDO SERVIDOR                    \033[1;36m║\033[0m"
                echo -e "\033[1;36m╚══════════════════════════════════════════════════════════════╝\033[0m"
            fi
            echo -e "\033[1;33m⏳ Finalizando procesos del servidor...\033[0m"
            pkill -f "php -S 0.0.0.0:8000"
            if [ "$SYSTEM" == "ubuntu" ]; then
                echo -e "\n\033[1;32m┌─────────────────────────────────────────────────────────────┐\033[0m"
                echo -e "\033[1;32m│  ✅ Servidor detenido exitosamente                            │\033[0m"
                echo -e "\033[1;32m│  🔒 Puerto 8000 liberado                                      │\033[0m"
                echo -e "\033[1;32m└─────────────────────────────────────────────────────────────┘\033[0m"
                read -p $'\n\033[1;37m👆 Presiona Enter para continuar...\033[0m'
            else
                echo -e "\033[1;32m✔ Servidor detenido.\033[0m"
                sleep 2
            fi
            ;;
        3)
            if [ "$SYSTEM" == "ubuntu" ]; then
                echo -e "\n\033[1;36m╔══════════════════════════════════════════════════════════════╗\033[0m"
                echo -e "\033[1;36m║\033[1;33m                  🌐 ABRIENDO NAVEGADOR                      \033[1;36m║\033[0m"
                echo -e "\033[1;36m╚══════════════════════════════════════════════════════════════╝\033[0m"
                echo -e "\033[1;33m🔍 Detectando navegadores disponibles...\033[0m"
            fi
            open_browser
            if [ "$SYSTEM" == "ubuntu" ]; then
                echo -e "\n\033[1;32m┌─────────────────────────────────────────────────────────────┐\033[0m"
                echo -e "\033[1;32m│  🌐 Navegador abierto                                         │\033[0m"
                echo -e "\033[1;32m│  📍 URL: http://localhost:8000                                │\033[0m"
                echo -e "\033[1;32m└─────────────────────────────────────────────────────────────┘\033[0m"
                read -p $'\n\033[1;37m👆 Presiona Enter para continuar...\033[0m'
            else
                sleep 2
            fi
            ;;
        4)
            if [ "$SYSTEM" == "ubuntu" ]; then
                echo -e "\n\033[1;36m╔══════════════════════════════════════════════════════════════╗\033[0m"
                echo -e "\033[1;36m║\033[1;35m                   📊 ESTADO DEL SERVIDOR                     \033[1;36m║\033[0m"
                echo -e "\033[1;36m╚══════════════════════════════════════════════════════════════╝\033[0m"
                echo ""
            fi
            
            if pgrep -f "php -S 0.0.0.0:8000" > /dev/null; then
                SERVER_PID=$(pgrep -f "php -S 0.0.0.0:8000")
                if [ "$SYSTEM" == "ubuntu" ]; then
                    echo -e "\033[1;32m┌─────────────────────────────────────────────────────────────┐\033[0m"
                    echo -e "\033[1;32m│  🟢 SERVIDOR ACTIVO                                           │\033[0m"
                    echo -e "\033[1;32m│  📍 Puerto: 8000                                              │\033[0m"
                    echo -e "\033[1;32m│  🆔 PID: $SERVER_PID                                              │\033[0m"
                    echo -e "\033[1;32m│  🌐 URL: http://localhost:8000                                │\033[0m"
                    echo -e "\033[1;32m│  📂 Directorio: $(pwd | cut -c1-40)...                        │\033[0m"
                    echo -e "\033[1;32m└─────────────────────────────────────────────────────────────┘\033[0m"
                else
                    echo -e "\033[1;32m🟢 Servidor ACTIVO (Puerto 8000)\033[0m"
                    echo -e "\033[1;36m🌐 URL: http://localhost:8000\033[0m"
                fi
            else
                if [ "$SYSTEM" == "ubuntu" ]; then
                    echo -e "\033[1;31m┌─────────────────────────────────────────────────────────────┐\033[0m"
                    echo -e "\033[1;31m│  🔴 SERVIDOR INACTIVO                                         │\033[0m"
                    echo -e "\033[1;31m│  📍 Puerto 8000 disponible                                    │\033[0m"
                    echo -e "\033[1;31m│  💡 Use la opción [1] para iniciar                            │\033[0m"
                    echo -e "\033[1;31m└─────────────────────────────────────────────────────────────┘\033[0m"
                else
                    echo -e "\033[1;31m🔴 Servidor INACTIVO\033[0m"
                fi
            fi
            
            if [ "$SYSTEM" == "ubuntu" ]; then
                read -p $'\n\033[1;37m👆 Presiona Enter para continuar...\033[0m'
            else
                sleep 3
            fi
            ;;
        5)
            if [ "$SYSTEM" == "ubuntu" ]; then
                echo -e "\n\033[1;36m╔══════════════════════════════════════════════════════════════╗\033[0m"
                echo -e "\033[1;36m║\033[1;34m                 ⚙️  VERIFICACIÓN DEL SISTEMA                 \033[1;36m║\033[0m"
                echo -e "\033[1;36m╚══════════════════════════════════════════════════════════════╝\033[0m"
                echo ""
            else
                echo -e "\033[1;35m🗄️ Verificando configuración...\033[0m"
            fi
            
            # Verificar PHP
            if command -v php >/dev/null 2>&1; then
                PHP_VERSION=$(php -v | head -n1 | cut -d' ' -f2)
                if [ "$SYSTEM" == "ubuntu" ]; then
                    echo -e "\033[1;32m✅ PHP: Instalado (Versión: $PHP_VERSION)\033[0m"
                else
                    echo -e "\033[1;32m✔ PHP: $(php -v | head -n1 | cut -d' ' -f2)\033[0m"
                fi
            else
                if [ "$SYSTEM" == "ubuntu" ]; then
                    echo -e "\033[1;31m❌ PHP: No instalado\033[0m"
                else
                    echo -e "\033[1;31m❌ PHP no encontrado\033[0m"
                fi
            fi
            
            # Verificar BD
            if command -v mysql >/dev/null 2>&1 || command -v mariadb >/dev/null 2>&1; then
                if [ "$SYSTEM" == "ubuntu" ]; then
                    echo -e "\033[1;32m✅ Base de Datos: Disponible\033[0m"
                else
                    echo -e "\033[1;32m✔ Base de datos disponible\033[0m"
                fi
            else
                if [ "$SYSTEM" == "ubuntu" ]; then
                    echo -e "\033[1;31m❌ Base de Datos: No encontrada\033[0m"
                else
                    echo -e "\033[1;31m❌ Base de datos no encontrada\033[0m"
                fi
            fi
            
            # Verificar archivos de configuración
            if [ -f "$PROJECT_PATH/src/config/conexion.php" ]; then
                if [ "$SYSTEM" == "ubuntu" ]; then
                    echo -e "\033[1;32m✅ Configuración BD: Archivo encontrado\033[0m"
                else
                    echo -e "\033[1;32m✔ Archivo de conexión BD encontrado\033[0m"
                fi
            else
                if [ "$SYSTEM" == "ubuntu" ]; then
                    echo -e "\033[1;31m❌ Configuración BD: Archivo no encontrado\033[0m"
                else
                    echo -e "\033[1;31m❌ Archivo de conexión BD no encontrado\033[0m"
                fi
            fi
            
            if [ -d "$PROJECT_PATH/db" ]; then
                SQL_COUNT=$(ls "$PROJECT_PATH/db"/*.sql 2>/dev/null | wc -l)
                if [ "$SYSTEM" == "ubuntu" ]; then
                    echo -e "\033[1;32m✅ Esquemas BD: $SQL_COUNT archivos SQL encontrados\033[0m"
                else
                    echo -e "\033[1;32m✔ Directorio de BD encontrado ($SQL_COUNT archivos SQL)\033[0m"
                fi
            else
                if [ "$SYSTEM" == "ubuntu" ]; then
                    echo -e "\033[1;31m❌ Esquemas BD: Directorio no encontrado\033[0m"
                else
                    echo -e "\033[1;31m❌ Directorio de BD no encontrado\033[0m"
                fi
            fi
            
            if [ "$SYSTEM" == "ubuntu" ]; then
                echo ""
                echo -e "\033[1;37m📂 Directorio del proyecto: $(pwd)\033[0m"
                read -p $'\n\033[1;37m👆 Presiona Enter para continuar...\033[0m'
            else
                sleep 4
            fi
            ;;
        6)
            if [ "$SYSTEM" == "ubuntu" ]; then
                echo -e "\n\033[1;36m╔══════════════════════════════════════════════════════════════╗\033[0m"
                echo -e "\033[1;36m║\033[1;37m                    📋 LOGS DEL SERVIDOR                      \033[1;36m║\033[0m"
                echo -e "\033[1;36m╚══════════════════════════════════════════════════════════════╝\033[0m"
                echo ""
            else
                echo -e "\033[1;37m📋 Logs del servidor...\033[0m"
            fi
            
            if [ -f "$PROJECT_PATH/server.log" ]; then
                if [ "$SYSTEM" == "ubuntu" ]; then
                    echo -e "\033[1;33m┌─────────────────── ÚLTIMAS 15 LÍNEAS ──────────────────────┐\033[0m"
                    tail -15 "$PROJECT_PATH/server.log" | while IFS= read -r line; do
                        echo -e "\033[1;37m│ $line\033[0m"
                    done
                    echo -e "\033[1;33m└─────────────────────────────────────────────────────────────┘\033[0m"
                else
                    echo -e "\033[1;36m--- Últimas 15 líneas del log ---\033[0m"
                    tail -15 "$PROJECT_PATH/server.log"
                fi
            else
                if [ "$SYSTEM" == "ubuntu" ]; then
                    echo -e "\033[1;33m┌─────────────────────────────────────────────────────────────┐\033[0m"
                    echo -e "\033[1;33m│  ⚠️  No se encontraron logs del servidor                      │\033[0m"
                    echo -e "\033[1;33m│  💡 Los logs se crean cuando se inicia el servidor            │\033[0m"
                    echo -e "\033[1;33m└─────────────────────────────────────────────────────────────┘\033[0m"
                else
                    echo -e "\033[1;33m⚠ No se encontraron logs del servidor.\033[0m"
                fi
            fi
            read -p $'\033[1;37m👆 Presiona Enter para continuar...\033[0m'
            ;;
        7)
            if [ "$SYSTEM" == "ubuntu" ]; then
                echo -e "\n\033[1;36m╔══════════════════════════════════════════════════════════════╗\033[0m"
                echo -e "\033[1;36m║\033[1;31m                      👋 FINALIZANDO                          \033[1;36m║\033[0m"
                echo -e "\033[1;36m╚══════════════════════════════════════════════════════════════╝\033[0m"
            else
                echo -e "\033[1;31m👋 Cerrando Antares Travel Manager...\033[0m"
                echo -e "\033[1;36m🚀 ¡Gracias por usar Antares Travel!\033[0m"
            fi
            
            # Preguntar si detener el servidor al salir
            if pgrep -f "php -S 0.0.0.0:8000" > /dev/null; then
                if [ "$SYSTEM" == "ubuntu" ]; then
                    echo -e "\n\033[1;33m⚠️  El servidor está actualmente en ejecución\033[0m"
                    echo -ne "\033[1;37m¿Deseas detenerlo antes de salir? \033[1;32m[S/n]\033[0m: "
                else
                    read -p $'\033[1;33m¿Detener el servidor antes de salir? (s/n): \033[0m' stop_server
                fi
                read stop_server
                if [[ "$stop_server" == "s" || "$stop_server" == "S" || "$stop_server" == "" ]]; then
                    pkill -f "php -S 0.0.0.0:8000"
                    if [ "$SYSTEM" == "ubuntu" ]; then
                        echo -e "\033[1;32m✅ Servidor detenido correctamente\033[0m"
                    else
                        echo -e "\033[1;32m✔ Servidor detenido.\033[0m"
                    fi
                fi
            fi
            
            if [ "$SYSTEM" == "ubuntu" ]; then
                echo -e "\n\033[1;32m╔══════════════════════════════════════════════════════════════╗\033[0m"
                echo -e "\033[1;32m║                  🚀 ¡GRACIAS POR USAR                         ║\033[0m"
                echo -e "\033[1;32m║                    ANTARES TRAVEL!                           ║\033[0m"
                echo -e "\033[1;32m║                                                              ║\033[0m"
                echo -e "\033[1;32m║              💻 Desarrollado con ❤️  en Ubuntu                ║\033[0m"
                echo -e "\033[1;32m╚══════════════════════════════════════════════════════════════╝\033[0m"
                echo ""
            fi
            
            exit 0
            ;;
        *)
            if [ "$SYSTEM" == "ubuntu" ]; then
                echo -e "\n\033[1;31m╔══════════════════════════════════════════════════════════════╗\033[0m"
                echo -e "\033[1;31m║                    ❌ OPCIÓN INVÁLIDA                         ║\033[0m"
                echo -e "\033[1;31m╚══════════════════════════════════════════════════════════════╝\033[0m"
                echo -e "\033[1;33m💡 Por favor, selecciona un número del 1 al 7\033[0m"
                read -p $'\033[1;37m👆 Presiona Enter para continuar...\033[0m'
            else
                echo -e "\033[1;31m❌ Opción inválida. Selecciona un número del 1 al 7.\033[0m"
                sleep 2
            fi
            ;;
    esac
done
