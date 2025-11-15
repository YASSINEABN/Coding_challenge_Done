# API Documentation

## Base URL

```
http://localhost
```

## Endpoints

### 1. Health Check

Check if the application is running and Redis is connected.

**Endpoint:** `GET /health`

**Response:**
```json
{
  "status": "healthy",
  "timestamp": 1700000000,
  "redis": "connected"
}
```

---

### 2. Add Product to Cart

Add a product to a customer's cart. If the cart doesn't exist, it will be created automatically.

**Endpoint:** `POST /api/cart/add`

**Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
  "cart_id": "cart_xxxxx",
  "customer_email": "customer@example.com",
  "product_id": "prod-123",
  "product_name": "Premium Widget",
  "product_price": 29.99,
  "quantity": 2
}
```

**Parameters:**
- `cart_id` (optional): Existing cart ID. If not provided, a new cart will be created
- `customer_email` (required): Customer's email address
- `product_id` (required): Unique product identifier
- `product_name` (required): Product name
- `product_price` (required): Product price (numeric)
- `quantity` (optional): Quantity to add (default: 1)

**Success Response (200):**
```json
{
  "success": true,
  "cart": {
    "id": "cart_xxxxx",
    "customer_email": "customer@example.com",
    "items": [
      {
        "product": {
          "id": "prod-123",
          "name": "Premium Widget",
          "price": 29.99
        },
        "quantity": 2,
        "total_price": 59.98
      }
    ],
    "created_at": 1700000000,
    "finalized": false,
    "reminders_sent": 0,
    "last_reminder_at": null,
    "total_amount": 59.98
  }
}
```

**Error Response (400):**
```json
{
  "error": "Invalid input"
}
```

---

### 3. Get Cart Details

Retrieve details of a specific cart.

**Endpoint:** `GET /api/cart/{cart_id}`

**Parameters:**
- `cart_id` (required): Cart identifier in URL path

**Success Response (200):**
```json
{
  "id": "cart_xxxxx",
  "customer_email": "customer@example.com",
  "items": [
    {
      "product": {
        "id": "prod-123",
        "name": "Premium Widget",
        "price": 29.99
      },
      "quantity": 2,
      "total_price": 59.98
    }
  ],
  "created_at": 1700000000,
  "finalized": false,
  "reminders_sent": 1,
  "last_reminder_at": 1700086400,
  "total_amount": 59.98
}
```

**Error Response (404):**
```json
{
  "error": "Cart not found"
}
```

---

### 4. Finalize Cart

Mark a cart as finalized (order completed). This stops all future reminder emails.

**Endpoint:** `POST /api/cart/finalize`

**Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
  "cart_id": "cart_xxxxx"
}
```

**Parameters:**
- `cart_id` (required): Cart identifier to finalize

**Success Response (200):**
```json
{
  "success": true,
  "cart": {
    "id": "cart_xxxxx",
    "customer_email": "customer@example.com",
    "items": [...],
    "created_at": 1700000000,
    "finalized": true,
    "reminders_sent": 1,
    "last_reminder_at": 1700086400,
    "total_amount": 59.98
  }
}
```

**Error Response (404):**
```json
{
  "error": "Cart not found"
}
```

---

### 5. Get Metrics

Retrieve application metrics for monitoring.

**Endpoint:** `GET /api/metrics`

**Success Response (200):**
```json
{
  "carts_created": 150,
  "carts_finalized": 95,
  "reminders_sent": 240,
  "reminders_failed": 5,
  "timestamp": 1700000000
}
```

---

## Reminder System

The reminder system automatically processes abandoned carts and sends emails at configured intervals.

### Reminder Flow

1. **First Reminder**: Sent X hours after cart creation (default: 24 hours)
2. **Second Reminder**: Sent Y hours after first reminder (default: 48 hours)
3. **Third Reminder**: Sent Z hours after second reminder (default: 72 hours)

### Intervals Configuration

Configure in `.env` file:
```env
FIRST_REMINDER_HOURS=24
SECOND_REMINDER_HOURS=48
THIRD_REMINDER_HOURS=72
```

### Email Content

Each reminder email includes:
- Cart items with quantities and prices
- Total cart amount
- Link to finalize the order
- Progressive urgency messaging

### Stopping Reminders

Reminders are automatically stopped when:
- Cart is finalized (customer completes order)
- Maximum of 3 reminders have been sent

---

## Error Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 400 | Bad Request - Invalid input |
| 404 | Not Found - Resource doesn't exist |
| 500 | Internal Server Error |

---

## Example Usage

### Using cURL

**Create a new cart with a product:**
```bash
curl -X POST http://localhost/api/cart/add \
  -H "Content-Type: application/json" \
  -d '{
    "customer_email": "john@example.com",
    "product_id": "widget-001",
    "product_name": "Super Widget",
    "product_price": 49.99,
    "quantity": 1
  }'
```

**Add another product to existing cart:**
```bash
curl -X POST http://localhost/api/cart/add \
  -H "Content-Type: application/json" \
  -d '{
    "cart_id": "cart_673740c8a38159.12345678",
    "customer_email": "john@example.com",
    "product_id": "gadget-002",
    "product_name": "Cool Gadget",
    "product_price": 29.99,
    "quantity": 2
  }'
```

**Get cart details:**
```bash
curl http://localhost/api/cart/cart_673740c8a38159.12345678
```

**Finalize cart:**
```bash
curl -X POST http://localhost/api/cart/finalize \
  -H "Content-Type: application/json" \
  -d '{
    "cart_id": "cart_673740c8a38159.12345678"
  }'
```

**Check metrics:**
```bash
curl http://localhost/api/metrics
```

---

## Using with JavaScript

```javascript
// Add product to cart
const addToCart = async () => {
  const response = await fetch('http://localhost/api/cart/add', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      customer_email: 'customer@example.com',
      product_id: 'prod-123',
      product_name: 'Product Name',
      product_price: 19.99,
      quantity: 1
    })
  });
  
  const data = await response.json();
  console.log(data);
};

// Finalize cart
const finalizeCart = async (cartId) => {
  const response = await fetch('http://localhost/api/cart/finalize', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      cart_id: cartId
    })
  });
  
  const data = await response.json();
  console.log(data);
};
```

---

## Rate Limiting (Recommended)

For production, implement rate limiting in Nginx:

```nginx
limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;

location /api/ {
    limit_req zone=api burst=20 nodelay;
    # ... rest of configuration
}
```

---

## Monitoring Recommendations

1. **Application Logs**: Monitor `logs/error.log` and `logs/info.log`
2. **Metrics Endpoint**: Poll `/api/metrics` for application statistics
3. **Health Check**: Use `/health` for uptime monitoring
4. **Redis Monitoring**: Use `redis-cli monitor` for Redis operations
5. **Worker Status**: Check systemd service status

---

## Support

For issues or questions, check:
- Application logs in `logs/` directory
- Redis data: `redis-cli KEYS "cart:*"`
- Worker logs: `journalctl -u abandoned-cart-worker`
