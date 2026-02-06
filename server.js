const express = require('express');
const sqlite3 = require('sqlite3').verbose();
const cors = require('cors');
const bodyParser = require('body-parser');
const path = require('path');

const app = express();
app.use(cors());
app.use(bodyParser.json());

// Путь к фронтенду
app.use(express.static(path.join(__dirname, 'maribulka-vue/dist')));

// БАЗА ДАННЫХ - Используем абсолютный путь
const dbPath = path.join(__dirname, 'maribulka.db');
console.log('--- DIAGNOSTIC ---');
console.log('Database path:', dbPath);

const db = new sqlite3.Database(dbPath, (err) => {
    if (err) console.error('DB Connection Error:', err.message);
});

db.serialize(() => {
    db.run(`CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        login TEXT UNIQUE,
        password TEXT
    )`);

    // Жёстко обновляем пароль админа при каждом перезапуске сервера для теста
    db.run("INSERT OR REPLACE INTO users (id, login, password) VALUES (1, 'admin', '123')", (err) => {
        if (!err) console.log('Admin user verified: admin / 123');
    });
});

app.post('/api/login', (req, res) => {
    const { login, password } = req.body;
    console.log(`Login attempt: login=${login}, pass=${password}`);

    db.get("SELECT * FROM users WHERE login = ? AND password = ?", [login, password], (err, row) => {
        if (err) return res.status(500).json({ error: err.message });
        if (row) {
            console.log('Login Success!');
            res.json({ success: true });
        } else {
            console.log('Login Failed: User not found or wrong password');
            res.status(401).json({ success: false });
        }
    });
});

// SPA fallback - отдаем index.html для всех неизвестных маршрутов
app.get('*', (req, res) => {
    res.sendFile(path.join(__dirname, 'maribulka-vue/dist', 'index.html'));
});

// Для Passenger на Beget - просто экспортируем app
module.exports = app;

// Только для локальной разработки (закомментировано для продакшена)
/*
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`Server running on port ${PORT}`);
});
*/

