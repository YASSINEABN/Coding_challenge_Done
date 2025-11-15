# Architecture Documentation

## Overview

This application follows **Clean Architecture** principles with clear separation of concerns, implementing SOLID principles and PSR standards.

## Architecture Layers

```
┌─────────────────────────────────────────┐
│         Presentation Layer              │
│  (Controllers, HTTP Handling)           │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│         Application Layer               │
│  (Use Cases, Services)                  │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│         Domain Layer                    │
│  (Entities, Repository Interfaces)      │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│         Infrastructure Layer            │
│  (Redis, Email, Logger Implementations) │
└─────────────────────────────────────────┘
```

## Directory Structure

```
├── bin/                    # Executable scripts
│   └── reminder-worker.php # Background worker
├── config/                 # Configuration files
│   └── app.php            # Application config
├── public/                # Public web directory
│   └── index.php          # Application entry point
├── src/                   # Application source code
│   ├── Application/       # Application layer
│   │   └── Service/       # Application services
│   │       └── ReminderService.php
│   ├── Domain/            # Domain layer
│   │   ├── Entity/        # Domain entities
│   │   │   ├── Cart.php
│   │   │   ├── CartItem.php
│   │   │   └── Product.php
│   │   └── Repository/    # Repository interfaces
│   │       └── CartRepositoryInterface.php
│   ├── Infrastructure/    # Infrastructure layer
│   │   ├── Email/         # Email implementations
│   │   │   ├── EmailServiceInterface.php
│   │   │   └── SmtpEmailService.php
│   │   ├── Logger/        # Logger implementations
│   │   │   ├── LoggerInterface.php
│   │   │   └── FileLogger.php
│   │   ├── Monitoring/    # Monitoring tools
│   │   │   └── MetricsCollector.php
│   │   └── Repository/    # Repository implementations
│   │       └── RedisCartRepository.php
│   └── Presentation/      # Presentation layer
│       └── Controller/    # HTTP controllers
│           ├── CartController.php
│           └── MetricsController.php
├── tests/                 # Test files
│   └── Unit/             # Unit tests
│       └── CartTest.php
└── logs/                  # Application logs
```

## Core Components

### 1. Domain Layer

**Entities:**
- `Cart`: Represents a shopping cart with items, customer info, and reminder state
- `CartItem`: Represents an item in the cart with product and quantity
- `Product`: Represents a product with id, name, and price

**Repository Interfaces:**
- `CartRepositoryInterface`: Defines contract for cart persistence

**Design Principles:**
- Entities are framework-agnostic
- Business logic encapsulated in entities
- No infrastructure dependencies

### 2. Application Layer

**Services:**
- `ReminderService`: Orchestrates abandoned cart reminder processing
  - Checks which carts need reminders
  - Coordinates email sending
  - Updates cart state

**Responsibilities:**
- Implements use cases
- Coordinates between domain and infrastructure
- Transaction management

### 3. Infrastructure Layer

**Implementations:**

**Redis Repository:**
- `RedisCartRepository`: Implements `CartRepositoryInterface`
- Uses Predis client
- Stores carts as JSON
- Maintains sorted set index for abandoned carts
- Key structure:
  - `cart:{id}` - Cart data
  - `carts:abandoned` - Sorted set of abandoned cart IDs

**Email Service:**
- `SmtpEmailService`: Implements `EmailServiceInterface`
- Sends HTML emails via PHP mail()
- Configurable templates per reminder number
- Includes cart finalization links

**Logger:**
- `FileLogger`: Implements `LoggerInterface`
- Logs to separate files by level
- Supports debug, info, warning, error levels
- JSON context support

**Metrics:**
- `MetricsCollector`: Tracks application metrics in Redis
- Counts: carts created, finalized, reminders sent/failed
- Used for monitoring and alerting

### 4. Presentation Layer

**Controllers:**
- `CartController`: Handles cart operations (add, get, finalize)
- `MetricsController`: Exposes metrics endpoint

**Responsibilities:**
- HTTP request/response handling
- Input validation
- JSON serialization
- Error handling

## Data Flow

### Add Product to Cart

```
HTTP Request
    ↓
index.php (routing)
    ↓
CartController::addProduct()
    ↓
Cart Entity (business logic)
    ↓
RedisCartRepository::save()
    ↓
Redis (persistence)
```

### Reminder Processing

```
Worker Loop (60s interval)
    ↓
ReminderService::processAbandonedCarts()
    ↓
RedisCartRepository::findAbandonedCarts()
    ↓
ReminderService::shouldSendReminder() (business logic)
    ↓
SmtpEmailService::sendReminderEmail()
    ↓
Cart::markReminderSent()
    ↓
RedisCartRepository::save()
```

### Finalize Cart

```
HTTP Request with cart_id
    ↓
CartController::finalizeCart()
    ↓
RedisCartRepository::findById()
    ↓
Cart::finalize() (validates and updates state)
    ↓
RedisCartRepository::save()
    ↓
Redis (removes from abandoned index)
```

## Design Patterns

### 1. Repository Pattern
- Abstracts data persistence
- `CartRepositoryInterface` defines contract
- `RedisCartRepository` implements Redis-specific logic
- Easy to swap Redis for another storage

### 2. Dependency Injection
- All dependencies injected via constructors
- No service locator or global state
- Testable components

### 3. Strategy Pattern
- `EmailServiceInterface` allows different email implementations
- `LoggerInterface` allows different logging strategies

### 4. Entity Pattern
- Rich domain models with behavior
- Entities validate their own state
- Business rules in entity methods

## SOLID Principles

### Single Responsibility Principle (SRP)
- Each class has one reason to change
- `Cart` manages cart state
- `ReminderService` manages reminder logic
- `RedisCartRepository` manages persistence

### Open/Closed Principle (OCP)
- Open for extension via interfaces
- Closed for modification
- Add new email providers without changing existing code

### Liskov Substitution Principle (LSP)
- Interfaces define contracts
- Implementations are interchangeable
- Any `EmailServiceInterface` implementation works

### Interface Segregation Principle (ISP)
- Small, focused interfaces
- `EmailServiceInterface` only defines email methods
- `LoggerInterface` only defines logging methods

### Dependency Inversion Principle (DIP)
- High-level modules depend on abstractions
- `ReminderService` depends on `CartRepositoryInterface`
- Not on concrete `RedisCartRepository`

## PSR Compliance

### PSR-1: Basic Coding Standard
- PHP tags: `<?php` with `declare(strict_types=1);`
- Class naming: PascalCase
- Method naming: camelCase
- Constant naming: UPPER_CASE

### PSR-2: Coding Style Guide
- 4 spaces indentation
- Line length guidelines
- Brace placement
- Spacing around operators

### PSR-4: Autoloading
- `AbandonedCart\` namespace → `src/` directory
- Composer autoloader configured
- PSR-4 compliant structure

## Testing Strategy

### Unit Tests
- Test domain entities in isolation
- Mock dependencies
- Test business logic

### Example Tests
- `CartTest`: Tests cart entity behavior
  - Adding items
  - Finalization
  - Reminder tracking
  - State validation

### Running Tests
```bash
./vendor/bin/phpunit
```

## Scalability Considerations

### Horizontal Scaling
- Stateless web servers
- Redis for shared state
- Multiple workers can run simultaneously
- Load balancer in front of web servers

### Performance
- Redis for fast data access
- Indexed abandoned carts (sorted set)
- Efficient worker polling
- Minimal database queries

### Monitoring
- Metrics endpoint for stats
- File-based logging
- Health check endpoint
- Redis monitoring

## Security Considerations

### Input Validation
- Email validation
- Price validation
- Required field checks
- Type enforcement (strict types)

### Data Storage
- No sensitive data in logs
- Environment-based configuration
- .env not committed to git

### Email Security
- HTML escaping in templates
- Validated email addresses
- Rate limiting (recommended)

## Extensibility

### Adding New Features

**New Reminder Channel (SMS):**
1. Create `SmsServiceInterface`
2. Implement `TwilioSmsService`
3. Inject into `ReminderService`
4. Update reminder logic

**New Storage Backend:**
1. Implement `CartRepositoryInterface`
2. Create `MongoCartRepository`
3. Update dependency injection in `index.php`

**New API Endpoints:**
1. Create controller method
2. Add route in `index.php`
3. Follow existing patterns

## Deployment

### Production Checklist
- [ ] Configure `.env` with production values
- [ ] Set `APP_DEBUG=false`
- [ ] Configure Redis password
- [ ] Setup Nginx with proper configuration
- [ ] Enable systemd service for worker
- [ ] Setup log rotation
- [ ] Configure monitoring alerts
- [ ] Enable HTTPS
- [ ] Setup backup for Redis
- [ ] Configure firewall rules

## Maintenance

### Log Rotation
Configure logrotate for application logs:

```
/path/to/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
}
```

### Redis Maintenance
- Configure AOF or RDB persistence
- Monitor memory usage
- Setup backups
- Use `redis-cli INFO` for stats

### Code Quality
- Run `./vendor/bin/phpcs` regularly
- Review logs for errors
- Monitor metrics endpoint
- Keep dependencies updated
