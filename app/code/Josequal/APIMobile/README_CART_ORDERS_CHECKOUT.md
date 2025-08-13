# Cart, Orders, and Checkout APIs

This document describes the new APIs added to the APIMobile module for cart management, orders, and checkout functionality.

## Cart APIs

### 1. Add Product to Cart
- **URL:** `POST /apimobile/cart/add`
- **Authentication:** Required
- **Parameters:**
  - `product_id` (integer, required) - Product ID
  - `quantity` (integer, optional, default: 1) - Quantity to add
- **Response:**
```json
{
    "status": true,
    "message": "Product added successfully",
    "data": {
        "items": [...],
        "cart": true,
        "has_coupon": false,
        "coupon": "",
        "cart_qty": 1,
        "minimum_order": 0,
        "minimum_description": "",
        "totals": [...]
    }
}
```

### 2. Get Cart Information
- **URL:** `GET /apimobile/cart/info`
- **Authentication:** Required
- **Response:**
```json
{
    "status": true,
    "message": "Cart Details",
    "data": {
        "items": [...],
        "cart": true,
        "has_coupon": false,
        "coupon": "",
        "cart_qty": 1,
        "minimum_order": 0,
        "minimum_description": "",
        "totals": [...]
    }
}
```

### 3. Update Cart Item
- **URL:** `POST /apimobile/cart/update`
- **Authentication:** Required
- **Parameters:**
  - `item_id` (integer, required) - Cart item ID
  - `qty` (integer, required) - New quantity
- **Response:**
```json
{
    "status": true,
    "message": "Cart updated successfully",
    "data": {
        "items": [...],
        "cart": true,
        "has_coupon": false,
        "coupon": "",
        "cart_qty": 1,
        "minimum_order": 0,
        "minimum_description": "",
        "totals": [...]
    }
}
```

### 4. Delete Cart Item
- **URL:** `POST /apimobile/cart/delete`
- **Authentication:** Required
- **Parameters:**
  - `item_id` (integer, required) - Cart item ID
- **Response:**
```json
{
    "status": true,
    "message": "Item removed successfully",
    "data": {
        "items": [...],
        "cart": true,
        "has_coupon": false,
        "coupon": "",
        "cart_qty": 1,
        "minimum_order": 0,
        "minimum_description": "",
        "totals": [...]
    }
}
```

## Orders APIs

### 1. Get Customer Orders List
- **URL:** `GET /apimobile/orders/items`
- **Authentication:** Required
- **Parameters:**
  - `page` (integer, optional, default: 1) - Page number
- **Response:**
```json
{
    "status": true,
    "message": "Customer Orders",
    "data": {
        "ordersCount": 5,
        "orders": [
            {
                "id": 123,
                "increment_id": "000000123",
                "status": "pending",
                "status_label": "Pending",
                "created_at": "2024-01-01 00:00:00",
                "updated_at": "2024-01-01 00:00:00",
                "total": "$99.99",
                "subtotal": "$99.99",
                "shipping_amount": "$0.00",
                "discount_amount": "$0.00",
                "tax_amount": "$0.00",
                "items": [...],
                "image": "https://example.com/image.jpg",
                "billing_address": {...},
                "shipping_address": {...}
            }
        ]
    }
}
```

### 2. Get Order Details
- **URL:** `GET /apimobile/orders/details`
- **Authentication:** Required
- **Parameters:**
  - `order_id` (integer, required) - Order ID
- **Response:**
```json
{
    "status": true,
    "message": "Order Details",
    "data": {
        "id": 123,
        "increment_id": "000000123",
        "status": "pending",
        "status_label": "Pending",
        "created_at": "2024-01-01 00:00:00",
        "updated_at": "2024-01-01 00:00:00",
        "total": "$99.99",
        "subtotal": "$99.99",
        "shipping_amount": "$0.00",
        "discount_amount": "$0.00",
        "tax_amount": "$0.00",
        "items": [...],
        "image": "https://example.com/image.jpg",
        "billing_address": {...},
        "shipping_address": {...}
    }
}
```

## Checkout APIs

### 1. Save Checkout (Create Order)
- **URL:** `POST /apimobile/checkout/save`
- **Authentication:** Required
- **Parameters:**
  - `billing_address` (object, optional) - Billing address data
  - `shipping_address` (object, optional) - Shipping address data
  - `shipping_method` (string, optional, default: "freeshipping_freeshipping") - Shipping method code
  - `payment_method` (string, required) - Payment method code
- **Response:**
```json
{
    "status": true,
    "message": "Order created successfully",
    "data": {
        "order_id": 123,
        "increment_id": "000000123",
        "status": "pending",
        "total": "$99.99"
    }
}
```

### 2. Get Shipping Methods
- **URL:** `GET /apimobile/checkout/methods`
- **Authentication:** Required
- **Response:**
```json
{
    "status": true,
    "message": "Shipping methods retrieved successfully",
    "data": [
        {
            "code": "freeshipping_freeshipping",
            "title": "Free Shipping",
            "description": "Free shipping for all orders"
        },
        {
            "code": "flatrate_flatrate",
            "title": "Flat Rate",
            "description": "Fixed shipping rate"
        }
    ]
}
```

### 3. Get Payment Methods
- **URL:** `GET /apimobile/checkout/paymentmethods`
- **Authentication:** Required
- **Response:**
```json
{
    "status": true,
    "message": "Payment methods retrieved successfully",
    "data": [
        {
            "code": "checkmo",
            "title": "Check / Money order",
            "description": "Send check to store owner"
        },
        {
            "code": "banktransfer",
            "title": "Bank Transfer Payment",
            "description": "Bank Transfer Payment"
        }
    ]
}
```

## Error Responses

All APIs return error responses in the following format:

```json
{
    "status": false,
    "message": "Error description",
    "data": []
}
```

## Authentication

All APIs require authentication using the Bearer token in the Authorization header:

```
Authorization: Bearer {token}
```

## Usage Examples

### Add Product to Cart
```bash
curl -X POST "https://your-store.com/apimobile/cart/add" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 123,
    "quantity": 2
  }'
```

### Get Cart Information
```bash
curl -X GET "https://your-store.com/apimobile/cart/info" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Get Orders List
```bash
curl -X GET "https://your-store.com/apimobile/orders/items?page=1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Create Order
```bash
curl -X POST "https://your-store.com/apimobile/checkout/save" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "billing_address": {
      "firstname": "John",
      "lastname": "Doe",
      "street": ["123 Main St"],
      "city": "New York",
      "region": "NY",
      "postcode": "10001",
      "country_id": "US",
      "telephone": "1234567890"
    },
    "shipping_address": {
      "firstname": "John",
      "lastname": "Doe",
      "street": ["123 Main St"],
      "city": "New York",
      "region": "NY",
      "postcode": "10001",
      "country_id": "US",
      "telephone": "1234567890"
    },
    "shipping_method": "freeshipping_freeshipping",
    "payment_method": "checkmo"
  }'
```

## Notes

1. All APIs require user authentication
2. Cart operations are session-based
3. Orders are customer-specific
4. Checkout requires a valid cart with items
5. Payment methods depend on store configuration
6. All monetary values are returned in the store's base currency
7. Dates are returned in the store's timezone
8. For orders list, use `/apimobile/orders/items` instead of `/apimobile/orders/list`
9. Default shipping method is `freeshipping_freeshipping` if not specified
10. Shipping methods are available at `/apimobile/checkout/methods`
11. Payment methods are available at `/apimobile/checkout/paymentmethods`
