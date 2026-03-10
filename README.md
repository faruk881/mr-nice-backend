# 🚚 Parcel Delivery Platform – Backend (Laravel API)

![Laravel](https://img.shields.io/badge/Laravel-API-red)
![PHP](https://img.shields.io/badge/PHP-8.4-blue)
![Docker](https://img.shields.io/badge/Docker-Containerized-blue)
![MySQL](https://img.shields.io/badge/MySQL-8.0-orange)

This repository contains the **Laravel API backend** for the **Parcel Delivery Platform**, a system designed to connect **customers, couriers, and administrators** for efficient urban parcel delivery.

The platform manages parcel requests, courier assignments, delivery tracking, and real-time order status updates.

---

# 📌 Project Overview

The system provides a scalable backend for managing parcel delivery operations with multiple stakeholders.

### Key Features

- 📦 Parcel delivery request management  
- 🚴 Courier application and assignment system  
- 📍 Real-time delivery status updates  
- 🔍 Parcel tracking functionality  
- 👤 Role-based system (Customer, Courier, Admin)  
- 🔐 Secure RESTful API  

---

# 🌍 Supported Locations

The platform is initially designed to operate in the following Swiss cities:

- Sion  
- Sierre  
- Martigny  
- Monthey  

---

# 🛠 Tech Stack

| Technology | Description |
|------------|-------------|
| Laravel | Backend framework |
| PHP 8.4 | Programming language |
| Nginx | Web server |
| MySQL 8 | Database |
| Docker | Containerization |
| Docker Compose | Multi-container orchestration |
| phpMyAdmin | Database management UI |

---

# 📂 Project Structure

```
project-root
│
├── app
├── bootstrap
├── config
├── database
├── docker
│   ├── nginx
│   │   └── default.conf
│   └── php
│       └── Dockerfile
│
├── routes
├── storage
├── docker-compose.yml
└── README.md
```

---

# ⚙️ Requirements

Make sure the following tools are installed:

- Docker
- Docker Compose
- Git

---

# 🚀 Installation

## 1️⃣ Clone the repository

```bash
git clone https://github.com/your-username/parcel-delivery-backend.git
cd parcel-delivery-backend
```

---

## 2️⃣ Copy environment file

```bash
cp .env.example .env
```

Update the database configuration inside `.env`:

```
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=mr_nice_backend_db
DB_USERNAME=mr_nice_user
DB_PASSWORD=mypassword
```

---

## 3️⃣ Build and start containers

```bash
docker compose up -d --build
```

This will start the following services:

| Service | Container | Port |
|-------|-------|------|
| Laravel App | PHP-FPM | internal |
| Nginx | Web Server | 8001 |
| MySQL | Database | 3308 |
| phpMyAdmin | DB Manager | 8081 |

---

## 4️⃣ Install Laravel dependencies

```bash
docker compose exec app composer install
```

---

## 5️⃣ Generate application key

```bash
docker compose exec app php artisan key:generate
```

---

## 6️⃣ Run database migrations

```bash
docker compose exec app php artisan migrate
```

---

# 🌐 Access the Application

| Service | URL |
|------|------|
| Laravel API | http://localhost:8001 |
| phpMyAdmin | http://localhost:8081 |
| MySQL | 127.0.0.1:3308 |

---

# 🐳 Docker Services

### PHP-FPM (`app`)
Runs the Laravel application.

### Nginx (`web`)
Handles HTTP requests and serves the Laravel `public` directory.

### MySQL (`db`)
Stores application data.

### phpMyAdmin (`phpmyadmin`)
Provides a web interface for database management.

---

# 🔧 Useful Commands

### Start containers

```bash
docker compose up -d
```

### Stop containers

```bash
docker compose down
```

### Rebuild containers

```bash
docker compose up -d --build
```

### Run Artisan commands

```bash
docker compose exec app php artisan <command>
```

Example:

```bash
docker compose exec app php artisan migrate
```

---

# 🛠 Troubleshooting

### Fix Permission Issues

```bash
docker compose exec app chown -R www-data:www-data /var/www/html
docker compose exec app chmod -R 775 /var/www/html
```

---

### Fix Upload / 403 Errors

```bash
docker compose exec app bash
```

Then run:

```bash
echo "upload_max_filesize=100M" > /usr/local/etc/php/conf.d/uploads.ini
echo "post_max_size=100M" >> /usr/local/etc/php/conf.d/uploads.ini
```

Reload Nginx:

```bash
docker exec -it mr_nice_backend_web nginx -s reload
```

---

# 📊 Database Access

### phpMyAdmin

```
http://localhost:8081
```

Login credentials:

```
Server: db
Username: root
Password: 7788
```

---

# 🚧 Project Status

⚠️ **Currently in development**

Features and documentation may change as development progresses.

---

# 📄 License

This project is the property of **SparkTech Agency**.

Developed and maintained by **Md. Omar Faruk**.

All rights reserved. Unauthorized copying, modification, or distribution of this project without permission is prohibited.