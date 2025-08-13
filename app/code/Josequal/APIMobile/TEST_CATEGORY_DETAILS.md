# Test Category Details API

## Test URLs

### Category Details API
- Get category details: `GET https://avit.josequal.net/apimobile/category/details`

## Test Data

### Get Category Details (Basic)
```bash
curl -X GET "https://avit.josequal.net/apimobile/category/details?category_id=5" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Get Category Details with Pagination
```bash
curl -X GET "https://avit.josequal.net/apimobile/category/details?category_id=5&page=1&limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Get Category Details with Sorting
```bash
curl -X GET "https://avit.josequal.net/apimobile/category/details?category_id=5&sort=newest&limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Get Category Details with Search
```bash
curl -X GET "https://avit.josequal.net/apimobile/category/details?category_id=5&search=flower&limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Get Category Details with All Parameters
```bash
curl -X GET "https://avit.josequal.net/apimobile/category/details?category_id=5&page=1&limit=10&sort=price-l-h&search=flower" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Expected Responses

### Success Response (Category Details)
```json
{
    "status": true,
    "message": "Category Details",
    "data": {
        "id": 5,
        "name": "Flowers",
        "description": "Beautiful flowers for all occasions",
        "image": "https://avit.josequal.net/media/catalog/category/flowers.jpg",
        "url_key": "flowers",
        "level": 2,
        "parent_id": 3,
        "position": 1,
        "is_active": true,
        "product_count": 50,
        "sub_categories_count": 3,
        "sub_categories": [
            {
                "id": 6,
                "name": "Roses",
                "description": "Beautiful roses",
                "image": "https://avit.josequal.net/media/catalog/category/roses.jpg",
                "url_key": "roses",
                "level": 3,
                "position": 1,
                "product_count": 15,
                "children_count": 0
            },
            {
                "id": 7,
                "name": "Tulips",
                "description": "Colorful tulips",
                "image": "https://avit.josequal.net/media/catalog/category/tulips.jpg",
                "url_key": "tulips",
                "level": 3,
                "position": 2,
                "product_count": 20,
                "children_count": 2
            }
        ],
        "products": [
            {
                "id": 123,
                "name": "Red Rose Bouquet",
                "sku": "ROSE-001",
                "price": "99.99",
                "special_price": "79.99",
                "image": "https://avit.josequal.net/media/catalog/product/red-rose.jpg",
                "description": "Beautiful red rose bouquet",
                "short_description": "Red roses",
                "category_id": 5,
                "category_name": "Flowers",
                "stock_status": true,
                "qty": 10,
                "created_at": "2024-01-01 00:00:00",
                "updated_at": "2024-01-01 00:00:00"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total_items": 50,
            "total_pages": 3
        }
    }
}
```

### Error Response (Category Not Found)
```json
{
    "status": false,
    "message": "Category not found",
    "data": []
}
```

### Error Response (Category Not Active)
```json
{
    "status": false,
    "message": "Category is not active",
    "data": []
}
```

### Error Response (Missing Category ID)
```json
{
    "status": false,
    "message": "Category ID is required",
    "data": []
}
```

## Parameters

### Required Parameters
- `category_id` (integer) - Category ID

### Optional Parameters
- `page` (integer, default: 1) - Page number for products
- `limit` (integer, default: 20) - Number of products per page
- `sort` (string, default: "name-a-z") - Sort order for products
  - `name-a-z` - Name A to Z
  - `name-z-a` - Name Z to A
  - `price-l-h` - Price Low to High
  - `price-h-l` - Price High to Low
  - `newest` - Newest first
  - `oldest` - Oldest first
  - `rating-h-l` - Rating High to Low
  - `rating-l-h` - Rating Low to High
- `search` (string) - Search query for products

## Testing Steps

1. **Test Basic Category Details:**
   ```bash
   curl -X GET "https://avit.josequal.net/apimobile/category/details?category_id=5" \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

2. **Test Category Details with Pagination:**
   ```bash
   curl -X GET "https://avit.josequal.net/apimobile/category/details?category_id=5&page=2&limit=5" \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

3. **Test Category Details with Sorting:**
   ```bash
   curl -X GET "https://avit.josequal.net/apimobile/category/details?category_id=5&sort=price-l-h&limit=10" \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

4. **Test Category Details with Search:**
   ```bash
   curl -X GET "https://avit.josequal.net/apimobile/category/details?category_id=5&search=rose&limit=10" \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

5. **Test Category Details with All Parameters:**
   ```bash
   curl -X GET "https://avit.josequal.net/apimobile/category/details?category_id=5&page=1&limit=10&sort=newest&search=flower" \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

6. **Test Error Cases:**
   ```bash
   # Missing category ID
   curl -X GET "https://avit.josequal.net/apimobile/category/details" \
     -H "Authorization: Bearer YOUR_TOKEN"
   
   # Invalid category ID
   curl -X GET "https://avit.josequal.net/apimobile/category/details?category_id=999999" \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

## Notes

1. **Category ID Required**: `category_id` parameter is mandatory
2. **Active Categories Only**: Only returns active categories
3. **Sub Categories**: Only returns sub categories with products
4. **Products**: Only returns products with stock
5. **Pagination**: Default 20 products per page
6. **Sorting**: Default sort is "name-a-z"
7. **Search**: Searches in product names and SKUs
8. **Image URLs**: Full URLs for category and product images
9. **Error Handling**: Proper error responses for invalid requests
10. **Performance**: Optimized queries for better performance

## Common Use Cases

1. **Category Page**: Use `/apimobile/category/details?category_id=5` to get full category information
2. **Category with Products**: Use `/apimobile/category/details?category_id=5&limit=10` to get products
3. **Category with Search**: Use `/apimobile/category/details?category_id=5&search=flower` to search products
4. **Category with Sorting**: Use `/apimobile/category/details?category_id=5&sort=newest` to get latest products
5. **Category with Pagination**: Use `/apimobile/category/details?category_id=5&page=2&limit=10` for pagination
