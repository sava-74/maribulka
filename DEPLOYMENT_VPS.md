# Деплой на Beget VPS

## 1. Подключение по SSH
```bash
ssh username@maribulka.rf
```

## 2. Установка Node.js
```bash
# Устанавливаем nvm
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
source ~/.bashrc

# Устанавливаем Node.js
nvm install 18
nvm use 18
```

## 3. Клонирование проекта
```bash
git clone <your-repo-url> /var/www/maribulka
cd /var/www/maribulka
```

## 4. Установка зависимостей
```bash
# Backend
npm install

# Frontend
cd maribulka-vue
npm install
npm run build
cd ..
```

## 5. Настройка PM2 (автозапуск)
```bash
npm install -g pm2
pm2 start server.js --name maribulka
pm2 save
pm2 startup
```

## 6. Настройка Nginx (проксирование)
Создайте файл `/etc/nginx/sites-available/maribulka.rf`:

```nginx
server {
    listen 80;
    server_name maribulka.rf www.maribulka.rf;

    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/maribulka.rf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 7. SSL сертификат (опционально)
```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d maribulka.rf -d www.maribulka.rf
```
