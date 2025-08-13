# Test Latest Products API

## Test URLs

### Latest Products APIs
- Get latest products: `GET https://avit.josequal.net/apimobile/product/latest`
- Get products with latest filter: `GET https://avit.josequal.net/apimobile/product/items?latest=true`

## Test Data

### Get Latest Products (Dedicated Endpoint)
```bash
curl -X GET "https://avit.josequal.net/apimobile/product/latest?limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Get Latest Products (Parameter)
```bash
curl -X GET "https://avit.josequal.net/apimobile/product/items?latest=true&limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Get Latest Products by Category
```bash
curl -X GET "https://avit.josequal.net/apimobile/product/latest?category_id=5&limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Get Latest Products with Pagination
```bash
curl -X GET "https://avit.josequal.net/apimobile/product/latest?page=1&limit=5" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Expected Responses

### Success Response (Latest Products)
```json
{
    "status": true,
    "message": "Latest Products List",
    "data": {
        "count": 25,
        "products": [
            {
                "id": 123,
                "name": "Latest Product",
                "sku": "PROD-123",
                "price": "99.99",
                "special_price": "79.99",
                "image": "https://example.com/image.jpg",
                "description": "Product description",
                "short_description": "Short description",
                "category_id": 5,
                "category_name": "Category Name",
                "stock_status": true,
                "qty": 10,
                "created_at": "2024-01-01 00:00:00",
                "updated_at": "2024-01-01 00:00:00"
            }
        ],
        "latest": true
    }
}
```

### Success Response (Products with Latest Filter)
```json
{
    "status": true,
    "message": "Products List",
    "data": {
        "count": 50,
        "products": [
            {
                "id": 123,
                "name": "Latest Product",
                "sku": "PROD-123",
                "price": "99.99",
                "special_price": "79.99",
                "image": "https://example.com/image.jpg",
                "description": "Product description",
                "short_description": "Short description",
                "category_id": 5,
                "category_name": "Category Name",
                "stock_status": true,
                "qty": 10,
                "created_at": "2024-01-01 00:00:00",
                "updated_at": "2024-01-01 00:00:00"
            }
        ],
        "latest": true
    }
}
```

## Parameters

### Latest Products Endpoint (`/apimobile/product/latest`)
- `page` (integer, optional, default: 1) - Page number
- `limit` (integer, optional, default: 10) - Number of products per page
- `category_id` (integer, optional) - Category ID filter

### Products with Latest Filter (`/apimobile/product/items?latest=true`)
- `page` (integer, optional, default: 1) - Page number
- `limit` (integer, optional, default: 20) - Number of products per page
- `category_id` (integer, optional) - Category ID filter
- `search` (string, optional) - Search query
- `latest` (boolean, required: true) - Must be set to true

## Testing Steps

1. **Test Latest Products Endpoint:**
   ```bash
   curl -X GET "https://avit.josequal.net/apimobile/product/latest?limit=5" \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

2. **Test Products with Latest Filter:**
   ```bash
   curl -X GET "https://avit.josequal.net/apimobile/product/items?latest=true&limit=5" \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

3. **Test Latest Products by Category:**
   ```bash
   curl -X GET "https://avit.josequal.net/apimobile/product/latest?category_id=5&limit=5" \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

4. **Test Latest Products with Pagination:**
   ```bash
   curl -X GET "https://avit.josequal.net/apimobile/product/latest?page=2&limit=5" \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

## Notes

1. **Latest Products Endpoint**: Always returns the newest products regardless of other parameters
2. **Latest Filter**: Overrides any sort parameter to use "newest"
3. **Default Limit**: Latest products endpoint defaults to 10 products
4. **Pagination**: Both endpoints support pagination
5. **Category Filter**: Both endpoints support category filtering
6. **Response Format**: Consistent JSON response format
7. **Authentication**: Optional authentication supported
8. **Error Handling**: Proper error responses with status codes

## Common Use Cases

1. **Homepage Latest Products**: Use `/apimobile/product/latest?limit=10`
2. **Category Latest Products**: Use `/apimobile/product/latest?category_id=5&limit=10`
3. **Search Latest Products**: Use `/apimobile/product/items?latest=true&search=flower&limit=10`
4. **Pagination**: Use `/apimobile/product/latest?page=2&limit=10`
