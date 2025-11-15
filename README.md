# Abandoned Cart Reminder System

A robust abandoned cart reminder system built with vanilla PHP, Redis, and Nginx, following PSR standards and SOLID principles.

## Features

- Add products to cart
- Automatic email reminders at configurable intervals
- Three-stage reminder system
- Cart finalization
- Redis-based storage
- PSR-1 and PSR-2 compliant
- Monitoring and logging capabilities

## Requirements

- PHP >= 8.1
- Redis
- Nginx
- Composer

## Installation

1. Clone the repository
2. Copy `.env.example` to `.env` and configure your settings
3. Install dependencies:
```bash
composer install
```

## Configuration

Configure reminder intervals and email settings in `.env`:

- `FIRST_REMINDER_HOURS`: Hours after cart creation for first reminder
- `SECOND_REMINDER_HOURS`: Hours after first reminder for second reminder
- `THIRD_REMINDER_HOURS`: Hours after second reminder for third reminder

## Architecture

The application follows SOLID principles and clean architecture:

- **Domain Layer**: Core business entities and interfaces
- **Infrastructure Layer**: Redis, Email implementations
- **Application Layer**: Use cases and services
- **Presentation Layer**: HTTP endpoints

## Running the Application

### Web Server
```bash
# Start PHP development server
php -S localhost:8000 -t public
```

### Reminder Worker
```bash
# Run the reminder worker
php bin/reminder-worker.php
```

## Project Structure

```
├── config/           # Configuration files
├── public/           # Public web directory
├── src/              # Application source code
│   ├── Domain/       # Domain entities and interfaces
│   ├── Infrastructure/ # External services implementation
│   ├── Application/  # Use cases and services
│   └── Presentation/ # HTTP controllers
├── bin/              # CLI scripts
└── logs/             # Application logs
```

## PSR Compliance

This project adheres to:
- PSR-1: Basic Coding Standard
- PSR-2: Coding Style Guide
- PSR-4: Autoloading Standard

## Monitoring

Application logs are stored in `logs/` directory with different levels:
- error.log: Error messages
- info.log: Informational messages
- debug.log: Debug information
