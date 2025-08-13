# Products APIs

This document describes the Products APIs in the APIMobile module.

## Product APIs

### 1. Get Products List
- **URL:** `GET /apimobile/product/items`
- **Authentication:** Optional
- **Parameters:**
  - `page` (integer, optional, default: 1) - Page number
  - `limit` (integer, optional, default: 20) - Number of products per page
  - `sort` (string, optional, default: "name-a-z") - Sort order
    - `name-a-z` - Name A to Z
    - `name-z-a` - Name Z to A
    - `price-l-h` - Price Low to High
    - `price-h-l` - Price High to Low
    - `newest` - Newest first
    - `oldest` - Oldest first
    - `rating-h-l` - Rating High to Low
    - `rating-l-h` - Rating Low to High
  - `search` (string, optional) - Search query
  - `category_id` (integer, optional) - Category ID filter
  - `latest` (boolean, optional) - Get latest products (overrides sort to newest)
- **Response:**
```json
{
    "status": true,
    "message": "Products List",
    "data": {
        "count": 50,
        "products": [
            {
                "id": 123,
                "name": "Product Name",
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
        "latest": false
    }
}
```

### 2. Get Latest Products
- **URL:** `GET /apimobile/product/latest`
- **Authentication:** Optional
- **Parameters:**
  - `page` (integer, optional, default: 1) - Page number
  - `limit` (integer, optional, default: 10) - Number of products per page
  - `category_id` (integer, optional) - Category ID filter
- **Response:**
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

### 3. Get Product Details
- **URL:** `GET /apimobile/product/info`
- **Authentication:** Optional
- **Parameters:**
  - `product_id` (integer, required) - Product ID
- **Response:**
```json
{
    "status": true,
    "message": "Product Details",
    "data": {
        "id": 123,
        "name": "Product Name",
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
        "updated_at": "2024-01-01 00:00:00",
        "reviews": [...],
        "related_products": [...],
        "custom_attributes": {...}
    }
}
```

## Usage Examples

### Get All Products
```bash
curl -X GET "https://your-store.com/apimobile/product/items?page=1&limit=20" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Get Latest Products
```bash
curl -X GET "https://your-store.com/apimobile/product/latest?limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Get Products with Latest Filter
```bash
curl -X GET "https://your-store.com/apimobile/product/items?latest=true&limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Get Products by Category
```bash
curl -X GET "https://your-store.com/apimobile/product/items?category_id=5&page=1&limit=20" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Search Products
```bash
curl -X GET "https://your-store.com/apimobile/product/items?search=flower&page=1&limit=20" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Get Latest Products by Category
```bash
curl -X GET "https://your-store.com/apimobile/product/latest?category_id=5&limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Sort Options

1. **name-a-z**: Sort by name A to Z (default)
2. **name-z-a**: Sort by name Z to A
3. **price-l-h**: Sort by price low to high
4. **price-h-l**: Sort by price high to low
5. **newest**: Sort by creation date (newest first)
6. **oldest**: Sort by creation date (oldest first)
7. **rating-h-l**: Sort by rating high to low
8. **rating-l-h**: Sort by rating low to high

## Latest Products Feature

The latest products feature provides two ways to get the newest products:

1. **Dedicated Latest Endpoint**: `/apimobile/product/latest`
   - Always returns the newest products
   - Default limit is 10 products
   - Can be filtered by category

2. **Latest Parameter**: Add `latest=true` to the main products endpoint
   - Overrides any sort parameter to use "newest"
   - Works with all other filters (category, search, etc.)

## Notes

1. **Pagination**: All endpoints support pagination with `page` and `limit` parameters
2. **Filtering**: Products can be filtered by category using `category_id`
3. **Search**: Products can be searched using the `search` parameter
4. **Sorting**: Multiple sort options are available
5. **Latest Products**: Two ways to get latest products (dedicated endpoint or parameter)
6. **Authentication**: All endpoints support optional authentication
7. **Response Format**: Consistent JSON response format across all endpoints
8. **Error Handling**: Proper error responses with status codes
