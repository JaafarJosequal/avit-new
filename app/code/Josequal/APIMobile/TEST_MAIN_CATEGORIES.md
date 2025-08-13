# Test Main Categories API

## Test URLs

### Main Categories API
- Get main categories: `GET https://avit.josequal.net/apimobile/category/main`

## Test Data

### Get Main Categories (Basic)
```bash
curl -X GET "https://avit.josequal.net/apimobile/category/main" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Get Main Categories (No Authentication)
```bash
curl -X GET "https://avit.josequal.net/apimobile/category/main"
```

## Expected Responses

### Success Response (Main Categories)
```json
{
    "status": true,
    "message": "Main categories",
    "data": [
        {
            "id": 5,
            "name": "Flowers",
            "description": "Beautiful flowers for all occasions",
            "image": "https://avit.josequal.net/media/catalog/category/flowers.jpg",
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
            "image": "https://avit.josequal.net/media/catalog/category/plants.jpg",
            "url_key": "plants",
            "level": 2,
            "parent_id": 2,
            "position": 2,
            "is_active": true,
            "product_count": 30,
            "children_count": 2,
            "created_at": "2024-01-01 00:00:00",
            "updated_at": "2024-01-01 00:00:00"
        },
        {
            "id": 7,
            "name": "Gifts",
            "description": "Perfect gifts for every occasion",
            "image": "https://avit.josequal.net/media/catalog/category/gifts.jpg",
            "url_key": "gifts",
            "level": 2,
            "parent_id": 2,
            "position": 3,
            "is_active": true,
            "product_count": 25,
            "children_count": 1,
            "created_at": "2024-01-01 00:00:00",
            "updated_at": "2024-01-01 00:00:00"
        }
    ],
    "total_count": 3
}
```

### Success Response (No Categories Found)
```json
{
    "status": true,
    "message": "Main categories",
    "data": [],
    "total_count": 0
}
```

### Error Response (Server Error)
```json
{
    "status": false,
    "message": "An error occurred while fetching main categories",
    "data": []
}
```

## Parameters

### No Parameters Required
This API currently doesn't require any parameters. All main categories are returned by default.

## Testing Steps

1. **Test Basic Main Categories:**
   ```bash
   curl -X GET "https://avit.josequal.net/apimobile/category/main" \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

2. **Test Main Categories Without Authentication:**
   ```bash
   curl -X GET "https://avit.josequal.net/apimobile/category/main"
   ```

3. **Test Main Categories with Different Headers:**
   ```bash
   curl -X GET "https://avit.josequal.net/apimobile/category/main" \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

4. **Test Main Categories with Accept Header:**
   ```bash
   curl -X GET "https://avit.josequal.net/apimobile/category/main" \
     -H "Accept: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

## Response Validation

### Required Fields
Each category should have:
- `id`: Integer (required)
- `name`: String (required)
- `description`: String (optional)
- `image`: String (optional, URL)
- `url_key`: String (required)
- `level`: Integer (should be 2)
- `parent_id`: Integer (should be root category ID)
- `position`: Integer (required)
- `is_active`: Boolean (required)
- `product_count`: Integer (required)
- `children_count`: Integer (required)
- `created_at`: String (optional, date format)
- `updated_at`: String (optional, date format)

### Response Structure
- `status`: Boolean (required)
- `message`: String (required)
- `data`: Array (required)
- `total_count`: Integer (required)

## Notes

1. **Main Categories Only**: Only returns level 2 categories (main categories)
2. **Active Categories Only**: Only returns active categories
3. **Product Count**: Only counts active products
4. **Children Count**: Only counts active sub-categories
5. **Sorting**: Categories are sorted by position first, then by name
6. **Filtering**: Excludes predefined disallowed categories
7. **Image URLs**: Full URLs for category images
8. **No Parameters**: Currently no parameters required
9. **Performance**: Optimized queries for better performance
10. **Error Handling**: Proper error responses for invalid requests

## Common Use Cases

1. **Homepage Navigation**: Use `/apimobile/category/main` to get main categories for navigation
2. **Mobile App Menu**: Use `/apimobile/category/main` to build mobile app menu
3. **Category Browsing**: Use `/apimobile/category/main` for category browsing
4. **Quick Access**: Use `/apimobile/category/main` for quick category access
5. **Menu Structure**: Use `/apimobile/category/main` to build menu structure

## Performance Testing

1. **Response Time**: Test response time for main categories
2. **Load Testing**: Test with multiple concurrent requests
3. **Caching**: Test if responses are cached
4. **Memory Usage**: Monitor memory usage during requests
5. **Database Queries**: Monitor database query performance

## Security Testing

1. **Authentication**: Test with and without authentication
2. **Authorization**: Test with different user roles
3. **Input Validation**: Test with various input parameters
4. **SQL Injection**: Test for SQL injection vulnerabilities
5. **XSS Protection**: Test for XSS vulnerabilities

## Integration Testing

1. **Frontend Integration**: Test integration with frontend applications
2. **Mobile App Integration**: Test integration with mobile apps
3. **Third-party Integration**: Test integration with third-party services
4. **API Gateway**: Test through API gateway if applicable
5. **Load Balancer**: Test through load balancer if applicable
