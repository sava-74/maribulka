#!/bin/bash
# ==========================================
# 🚀 Скрипт автодеплоя на Beget
# ==========================================

set -e  # Останавливаться при ошибках

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Конфигурация
SSH_KEY="$HOME/.ssh/beget_maribulka"
SSH_HOST="sava7424@sava7424.beget.tech"
REMOTE_PATH="/home/s/sava7424/maribulka.rf/maribulka-vue/dist"
LOCAL_BUILD_PATH="maribulka-vue/dist"
LOCAL_API_PATH="api"

echo -e "${BLUE}===========================================${NC}"
echo -e "${BLUE}🚀 Начинаем деплой на Beget${NC}"
echo -e "${BLUE}===========================================${NC}"
echo ""

# 1. Проверка Git статуса
echo -e "${YELLOW}📋 Проверка Git статуса...${NC}"
if [[ -n $(git status -s) ]]; then
    echo -e "${RED}⚠️  Есть несохраненные изменения!${NC}"
    git status -s
    read -p "Продолжить деплой? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${RED}❌ Деплой отменен${NC}"
        exit 1
    fi
fi
echo -e "${GREEN}✅ Git статус проверен${NC}"
echo ""

# 2. Сборка Vue проекта
echo -e "${YELLOW}🏗️  Сборка Vue проекта...${NC}"
cd maribulka-vue
npm run build
cd ..
echo -e "${GREEN}✅ Сборка завершена${NC}"
echo ""

# 3. Создание бэкапа на сервере
echo -e "${YELLOW}💾 Создание бэкапа на сервере...${NC}"
BACKUP_NAME="dist_backup_$(date +%Y%m%d_%H%M%S)"
ssh -i "$SSH_KEY" "$SSH_HOST" "cd /home/s/sava7424/maribulka.rf/maribulka-vue && cp -r dist $BACKUP_NAME && echo 'Бэкап создан: $BACKUP_NAME'"
echo -e "${GREEN}✅ Бэкап создан${NC}"
echo ""

# 4. Загрузка dist на сервер
echo -e "${YELLOW}📤 Загрузка фронтенда (dist)...${NC}"
rsync -avz --delete \
    --exclude 'api' \
    -e "ssh -i $SSH_KEY" \
    "$LOCAL_BUILD_PATH/" \
    "$SSH_HOST:$REMOTE_PATH/"
echo -e "${GREEN}✅ Фронтенд загружен${NC}"
echo ""

# 5. Загрузка API на сервер
echo -e "${YELLOW}📤 Загрузка API (PHP)...${NC}"
if [ -d "$LOCAL_API_PATH" ]; then
    rsync -avz \
        -e "ssh -i $SSH_KEY" \
        "$LOCAL_API_PATH/" \
        "$SSH_HOST:$REMOTE_PATH/api/"
    echo -e "${GREEN}✅ API загружен${NC}"
else
    echo -e "${YELLOW}⚠️  Папка api не найдена, пропускаем${NC}"
fi
echo ""

# 6. Настройка media (Создание "железного" симлинка с абсолютным путём)
echo -e "${YELLOW}🔗 Настройка media (Absolute path symlink)...${NC}"
ssh -i "$SSH_KEY" "$SSH_HOST" "rm -f /home/s/sava7424/maribulka.rf/public_html/media && ln -s /home/s/sava7424/maribulka.rf/media /home/s/sava7424/maribulka.rf/public_html/media && echo 'Симлинк с абсолютным путем создан'"
echo -e "${GREEN}✅ Медиа настроено${NC}"
echo ""

# 7. Проверка деплоя
echo -e "${YELLOW}🔍 Проверка деплоя...${NC}"
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "http://xn--80aac1alfd7a3a5g.xn--p1ai/")
if [ "$RESPONSE" == "200" ]; then
    echo -e "${GREEN}✅ Сайт доступен (HTTP $RESPONSE)${NC}"
else
    echo -e "${RED}❌ Сайт недоступен (HTTP $RESPONSE)${NC}"
fi
echo ""

# 7. Финальная информация
echo -e "${BLUE}===========================================${NC}"
echo -e "${GREEN}✨ Деплой успешно завершен!${NC}"
echo -e "${BLUE}===========================================${NC}"
echo ""
echo -e "🌐 Сайт: ${BLUE}http://марибулька.рф${NC}"
echo -e "📁 Бэкап: ${YELLOW}$BACKUP_NAME${NC}"
echo -e "⏰ Время: ${YELLOW}$(date '+%Y-%m-%d %H:%M:%S')${NC}"
echo ""
echo -e "${GREEN}🎉 Готово!${NC}"
