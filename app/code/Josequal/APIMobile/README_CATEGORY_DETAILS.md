# Category Details API

This document describes the Category Details API in the APIMobile module.

## Category Details API

### Get Category Details with Sub Categories and Products
- **URL:** `GET /apimobile/category/details`
- **Authentication:** Optional
- **Parameters:**
  - `category_id` (integer, required) - Category ID
  - `page` (integer, optional, default: 1) - Page number for products
  - `limit` (integer, optional, default: 20) - Number of products per page
  - `sort` (string, optional, default: "name-a-z") - Sort order for products
    - `name-a-z` - Name A to Z
    - `name-z-a` - Name Z to A
    - `price-l-h` - Price Low to High
    - `price-h-l` - Price High to Low
    - `newest` - Newest first
    - `oldest` - Oldest first
    - `rating-h-l` - Rating High to Low
    - `rating-l-h` - Rating Low to High
  - `search` (string, optional) - Search query for products
- **Response:**
```json
{
    "status": true,
    "message": "Category Details",
    "data": {
        "id": 5,
        "name": "Category Name",
        "description": "Category description",
        "image": "https://example.com/category-image.jpg",
        "url_key": "category-url-key",
        "level": 2,
        "parent_id": 3,
        "position": 1,
        "is_active": true,
        "product_count": 50,
        "sub_categories_count": 3,
        "sub_categories": [
            {
                "id": 6,
                "name": "Sub Category 1",
                "description": "Sub category description",
                "image": "https://example.com/sub-category-image.jpg",
                "url_key": "sub-category-url-key",
                "level": 3,
                "position": 1,
                "product_count": 15,
                "children_count": 0
            },
            {
                "id": 7,
                "name": "Sub Category 2",
                "description": "Sub category description",
                "image": "https://example.com/sub-category-image.jpg",
                "url_key": "sub-category-url-key",
                "level": 3,
                "position": 2,
                "product_count": 20,
                "children_count": 2
            }
        ],
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
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total_items": 50,
            "total_pages": 3
        }
    }
}
```

## Usage Examples

### Get Category Details
```bash
curl -X GET "https://your-store.com/apimobile/category/details?category_id=5" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Get Category Details with Pagination
```bash
curl -X GET "https://your-store.com/apimobile/category/details?category_id=5&page=1&limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Get Category Details with Sorting
```bash
curl -X GET "https://your-store.com/apimobile/category/details?category_id=5&sort=newest&limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Get Category Details with Search
```bash
curl -X GET "https://your-store.com/apimobile/category/details?category_id=5&search=flower&limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Get Category Details with All Parameters
```bash
curl -X GET "https://your-store.com/apimobile/category/details?category_id=5&page=1&limit=10&sort=price-l-h&search=flower" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Response Structure

### Category Information
- `id`: Category ID
- `name`: Category name
- `description`: Category description
- `image`: Category image URL
- `url_key`: Category URL key
- `level`: Category level in hierarchy
- `parent_id`: Parent category ID
- `position`: Category position
- `is_active`: Whether category is active
- `product_count`: Total number of products in category
- `sub_categories_count`: Number of sub categories

### Sub Categories
Each sub category includes:
- `id`: Sub category ID
- `name`: Sub category name
- `description`: Sub category description
- `image`: Sub category image URL
- `url_key`: Sub category URL key
- `level`: Sub category level
- `position`: Sub category position
- `product_count`: Number of products in sub category
- `children_count`: Number of child categories

### Products
Each product includes:
- `id`: Product ID
- `name`: Product name
- `sku`: Product SKU
- `price`: Product price
- `special_price`: Special price (if available)
- `image`: Product image URL
- `description`: Product description
- `short_description`: Short description
- `category_id`: Category ID
- `category_name`: Category name
- `stock_status`: Stock status
- `qty`: Available quantity
- `created_at`: Creation date
- `updated_at`: Last update date

### Pagination
- `current_page`: Current page number
- `per_page`: Products per page
- `total_items`: Total number of products
- `total_pages`: Total number of pages

## Features

1. **Complete Category Information**: Returns all category details
2. **Sub Categories**: Lists all active sub categories
3. **Products**: Returns products with pagination
4. **Search**: Search products within the category
5. **Sorting**: Multiple sorting options for products
6. **Pagination**: Full pagination support for products
7. **Error Handling**: Proper error responses for invalid categories

## Notes

1. **Required Parameter**: `category_id` is required
2. **Active Categories Only**: Only returns active categories
3. **Product Filtering**: Only returns products with stock
4. **Sub Categories**: Only returns sub categories with products
5. **Image URLs**: Full URLs for category and product images
6. **Pagination**: Default 20 products per page
7. **Sorting**: Default sort is "name-a-z"
8. **Search**: Searches in product names and SKUs
9. **Error Handling**: Returns appropriate error messages for invalid requests
10. **Performance**: Optimized queries for better performance
