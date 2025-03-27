# Notions API & Projects Management

這是一個使用 **PHP + Laravel** 開發的後端專案，串接 **Notion API**，與 Notion 資料庫進行 CRUD 操作。目的是重組工作流程與資訊結構，打造更簡潔的專案管理介面，可供前端系統整合使用。

---

## ✨ 專案特色

- ✅ 使用 Laravel 建立 API Server
- ✅ 串接 Notion API，讀寫指定資料庫
- ✅ 封裝 Notion API 操作邏輯
- ✅ 支援 RESTful API 規格

---

## 🧱 技術棧

| 技術 | 說明 |
|------|------|
| [Laravel 10+](https://laravel.com/) | PHP Web 應用框架 |
| [PHP dotenv](https://github.com/vlucas/phpdotenv) | 環境變數設定 |
| [Notion API](https://developers.notion.com/) | Notion 官方資料庫 API |

---

## ⚙️ 安裝與啟動

```bash
# 安裝相依套件
composer install

# 建立 .env 檔案
cp .env.example .env

# 產生專案金鑰
php artisan key:generate

# 啟動伺服器
npm run dev
```

---

## 🔐 環境變數設定

請於 `.env` 檔案中設定以下內容：

```env
NOTION_API_KEY=your_secret_integration_token
NOTION_DATABASE_ID=your_database_id
```

---

## 📄 License

MIT © 2025

---

## 🙌 開發者

Develop by Ruby Oz
