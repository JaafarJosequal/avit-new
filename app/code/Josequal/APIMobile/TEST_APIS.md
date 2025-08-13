# Test APIs

## Test URLs

### Cart APIs
- Add to cart: `POST https://avit.josequal.net/apimobile/cart/add`
- Get cart info: `GET https://avit.josequal.net/apimobile/cart/info`
- Update cart: `POST https://avit.josequal.net/apimobile/cart/update`
- Delete item: `POST https://avit.josequal.net/apimobile/cart/delete`

### Orders APIs
- Get orders list: `GET https://avit.josequal.net/apimobile/orders/items`
- Get order details: `GET https://avit.josequal.net/apimobile/orders/details`

### Checkout APIs
- Save checkout: `POST https://avit.josequal.net/apimobile/checkout/save`
- Get shipping methods: `GET https://avit.josequal.net/apimobile/checkout/methods`
- Get payment methods: `GET https://avit.josequal.net/apimobile/checkout/paymentmethods`

## Test Data

### Add to Cart
```json
{
    "product_id": 1,
    "quantity": 2
}
```

### Update Cart
```json
{
    "item_id": 1,
    "qty": 3
}
```

### Delete Cart Item
```json
{
    "item_id": 1
}
```

### Get Order Details
```json
{
    "order_id": 1
}
```

### Create Order (Fixed - Use Available Methods)
```json
{
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
}
```

## Headers
```
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json
```

## Expected Responses

### Success Response
```json
{
    "status": true,
    "message": "Operation successful",
    "data": {...}
}
```

### Error Response
```json
{
    "status": false,
    "message": "Error description",
    "data": []
}
```

## Common Issues and Solutions

### 1. Shipping Method Error
**Issue:** "The shipping method is missing. Select the shipping method and try again."
**Solution:** 
1. First, get available shipping methods: `GET https://avit.josequal.net/apimobile/checkout/methods`
2. Use one of the returned shipping method codes in your request
3. If no methods are returned, use `"shipping_method": "freeshipping_freeshipping"`

### 2. Payment Method Error
**Issue:** "Enter a valid payment method and try again."
**Solution:** 
1. First, get available payment methods: `GET https://avit.josequal.net/apimobile/checkout/paymentmethods`
2. Use one of the returned payment method codes in your request
3. If no methods are returned, use `"payment_method": "checkmo"`

### 3. 404 Error for Orders List
**Issue:** 404 error when accessing `/apimobile/orders/list`
**Solution:** Use `/apimobile/orders/items` instead

### 4. Address Configuration Error
**Issue:** "Some addresses can't be used due to the configurations for specific countries."
**Solution:** Ensure all required address fields are provided and use valid country codes

### 5. Shipping Method Not Available
**Issue:** "Shipping method 'flatrate_flatrate' is not available"
**Solution:** 
1. Check available methods: `GET https://avit.josequal.net/apimobile/checkout/methods`
2. Use only the methods returned by the API
3. Default to `freeshipping_freeshipping` if no specific method is needed

### 6. Payment Method Not Available
**Issue:** "Payment method 'checkmo' is not available"
**Solution:** 
1. Check available methods: `GET https://avit.josequal.net/apimobile/checkout/paymentmethods`
2. Use only the methods returned by the API
3. Default to `checkmo` if no specific method is needed

## Testing Steps

1. **Test Shipping Methods First:**
   ```bash
   curl -X GET "https://avit.josequal.net/apimobile/checkout/methods" \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

2. **Test Payment Methods:**
   ```bash
   curl -X GET "https://avit.josequal.net/apimobile/checkout/paymentmethods" \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

3. **Use Available Methods:**
   - Copy the `code` from the responses above
   - Use them in your checkout request

4. **Test Checkout:**
   ```bash
   curl -X POST "https://avit.josequal.net/apimobile/checkout/save" \
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

## Available Methods Examples

### Shipping Methods Response
```json
{
    "status": true,
    "message": "Shipping methods retrieved successfully",
    "data": [
        {
            "code": "freeshipping_freeshipping",
            "title": "Free Shipping",
            "description": "Free shipping for all orders",
            "price": 0,
            "cost": 0
        }
    ]
}
```

### Payment Methods Response
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
