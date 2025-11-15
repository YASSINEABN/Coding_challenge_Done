# Abandoned Cart Reminder System

A robust abandoned cart reminder system built with **vanilla PHP**, **Redis**, and **Nginx**, following PSR standards and SOLID principles.

## ✨ Features

- 🛒 Add products to cart via REST API
- 📧 Automatic email reminders at configurable intervals (3-stage system)
- ✅ Cart finalization to stop reminders
- 🚀 Redis-based fast storage
- 📊 Built-in monitoring and metrics
- 🧪 Unit tests included
- 📏 PSR-1, PSR-2, and PSR-4 compliant
- 🏗️ Clean Architecture with SOLID principles

## 🎯 Requirements

- PHP >= 8.1
- Redis Server
- Nginx (or PHP built-in server for development)
- Composer

## 🚀 Quick Start

```bash
# Install dependencies
composer install

# Configure environment
cp .env.example .env
# Edit .env with your settings

# Start development server
php -S localhost:8000 -t public

# In another terminal, start the worker
php bin/reminder-worker.php
```



## 🔧 Configuration

Configure in `.env`:

```env
# Reminder intervals (in hours)
FIRST_REMINDER_HOURS=24
SECOND_REMINDER_HOURS=48
THIRD_REMINDER_HOURS=72

# Redis connection
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

## 📡 API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/cart/add` | POST | Add product to cart |
| `/api/cart/{id}` | GET | Get cart details |
| `/api/cart/finalize` | POST | Finalize cart (stop reminders) |
| `/api/metrics` | GET | Get application metrics |
| `/health` | GET | Health check |


## 🧪 Testing

```bash
# Run unit tests
./vendor/bin/phpunit

# Check PSR compliance
./vendor/bin/phpcs
```

## 📊 Monitoring

- **Logs**: `logs/info.log`, `logs/error.log`
- **Metrics**: `GET /api/metrics`
- **Health**: `GET /health`
- **Redis**: `redis-cli monitor`

## 🔄 How It Works

1. Customer adds products to cart
2. If cart is not finalized, reminders are scheduled
3. Background worker checks every 60 seconds
4. Sends 3 emails at configured intervals
5. Clicking email link finalizes cart and stops reminders

## 📁 Project Structure

```
├── bin/              # Executable scripts
├── config/           # Configuration
├── public/           # Web entry point
├── src/
│   ├── Application/  # Use cases
│   ├── Domain/       # Business entities
│   ├── Infrastructure/ # External services
│   └── Presentation/ # Controllers
├── tests/            # Unit tests
└── logs/             # Application logs
```

## 🛡️ Security

- Input validation on all endpoints
- Email address validation
- Type-safe with strict types
- Environment-based configuration
- No sensitive data in logs

## 🎨 Code Quality

- **PSR-1**: Basic coding standard ✅
- **PSR-2**: Coding style guide ✅
- **PSR-4**: Autoloading ✅
- **SOLID**: All principles applied ✅
- **DRY**: No code duplication ✅
- **KISS**: Simple, clear code ✅

