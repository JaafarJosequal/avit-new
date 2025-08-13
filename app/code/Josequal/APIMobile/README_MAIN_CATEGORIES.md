# Main Categories API

This document describes the Main Categories API in the APIMobile module.

## Main Categories API

### Get Main Categories
- **URL:** `GET /apimobile/category/main`
- **Authentication:** Optional
- **Parameters:** None (optional parameters can be added in future)
- **Response:**
```json
{
    "status": true,
    "message": "Main categories",
    "data": [
        {
            "id": 5,
            "name": "Flowers",
            "description": "Beautiful flowers for all occasions",
            "image": "https://example.com/category-image.jpg",
            "url_key": "flowers",
            "level": 2,
            "parent_id": 2,
            "position": 1,
            "is_active": true,
            "product_count": 50,
            "children_count": 3,
            "created_at": "2024-01-01 00:00:00",
            "updated_at": "2024-01-01 00:00:00"
        },
        {
            "id": 6,
            "name": "Plants",
            "description": "Indoor and outdoor plants",
            "image": "https://example.com/category-image.jpg",
            "url_key": "plants",
            "level": 2,
            "parent_id": 2,
            "position": 2,
            "is_active": true,
            "product_count": 30,
            "children_count": 2,
            "created_at": "2024-01-01 00:00:00",
            "updated_at": "2024-01-01 00:00:00"
        }
    ],
    "total_count": 2
}
```

## Usage Examples

### Get Main Categories
```bash
curl -X GET "https://your-store.com/apimobile/category/main" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Get Main Categories (No Authentication)
```bash
curl -X GET "https://your-store.com/apimobile/category/main"
```

## Response Structure

### Category Information
Each category includes:
- `id`: Category ID
- `name`: Category name
- `description`: Category description
- `image`: Category image URL
- `url_key`: Category URL key
- `level`: Category level (should be 2 for main categories)
- `parent_id`: Parent category ID (should be root category ID)
- `position`: Category position
- `is_active`: Whether category is active
- `product_count`: Number of products in category
- `children_count`: Number of sub categories
- `created_at`: Creation date
- `updated_at`: Last update date

### Response Metadata
- `status`: Response status (true/false)
- `message`: Response message
- `data`: Array of categories
- `total_count`: Total number of main categories

## Features

1. **Main Categories Only**: Returns only level 2 categories (main categories)
2. **Active Categories**: Only returns active categories
3. **Product Count**: Includes product count for each category
4. **Children Count**: Includes sub-categories count for each category
5. **Sorted Results**: Categories are sorted by position and name
6. **Filtered Results**: Excludes disallowed categories
7. **Image URLs**: Full URLs for category images
8. **Error Handling**: Proper error responses

## Notes

1. **Main Categories**: Only returns level 2 categories (main categories)
2. **Active Categories Only**: Only returns active categories
3. **Product Filtering**: Only counts active products
4. **Children Filtering**: Only counts active sub-categories
5. **Image URLs**: Full URLs for category images
6. **Sorting**: Categories are sorted by position first, then by name
7. **Filtering**: Excludes predefined disallowed categories
8. **Performance**: Optimized queries for better performance
9. **Error Handling**: Returns appropriate error messages
10. **No Parameters**: Currently no parameters required (can be extended in future)

## Main Categories vs All Categories

### Main Categories (`/apimobile/category/main`)
- Returns only level 2 categories (main categories)
- Categories are direct children of root category
- Sorted by position and name
- Includes product and children counts
- Filtered to exclude disallowed categories

### All Categories (`/apimobile/category/items`)
- Returns all categories based on parent_id parameter
- Can return categories at any level
- More flexible filtering options
- Basic category information only

## Use Cases

1. **Homepage Navigation**: Display main categories in navigation menu
2. **Category Browsing**: Show main categories for user browsing
3. **Mobile App**: Main categories for mobile app navigation
4. **Quick Access**: Quick access to main product categories
5. **Menu Structure**: Building menu structure for website/app

## Error Responses

### No Categories Found
```json
{
    "status": true,
    "message": "Main categories",
    "data": [],
    "total_count": 0
}
```

### Server Error
```json
{
    "status": false,
    "message": "An error occurred while fetching main categories",
    "data": []
}
```

## Performance Considerations

1. **Optimized Queries**: Uses efficient database queries
2. **Caching**: Results can be cached for better performance
3. **Filtering**: Pre-filters categories to reduce processing
4. **Count Optimization**: Efficient product and children counting
5. **Image URLs**: Pre-built image URLs for faster response

## Future Enhancements

1. **Pagination**: Add pagination support for large category lists
2. **Filtering**: Add filtering options (by name, product count, etc.)
3. **Sorting**: Add custom sorting options
4. **Caching**: Implement response caching
5. **Localization**: Add language support for category names
6. **Custom Fields**: Add custom category fields
7. **SEO Data**: Include SEO-related category data
8. **Analytics**: Add category analytics data
