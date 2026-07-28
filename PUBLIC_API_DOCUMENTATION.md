# 🌿 Bardiya Eco Friendly - Public API Documentation

**Base URL**: `https://api.bardiaecofriendlyhomestay.com`

This documentation covers the public-facing API endpoints for integrating the Bardiya Eco Friendly platform into your website or mobile application.

---

## 📋 Table of Contents

1. [Getting Started](#getting-started)
2. [Package Categories](#package-categories)
3. [Packages](#packages)
4. [Gallery](#gallery)
5. [Blog Categories](#blog-categories)
6. [Blog Posts](#blog-posts)
7. [Contact Methods](#contact-methods)
8. [Social Links](#social-links)
9. [Contact Submissions](#contact-submissions)
10. [Pages & Page SEO](#pages)
11. [Activities & Wildlife Experiences](#activities)
12. [Response Format](#response-format)
13. [Error Handling](#error-handling)

---

## 🚀 Getting Started

### Base URL
All API requests should be made to:
```
https://api.bardiaecofriendlyhomestay.com/api
```

### Content Type
All requests and responses use JSON format:
```
Content-Type: application/json
```

### CORS
The API supports Cross-Origin Resource Sharing (CORS) for all origins.

---

## 📦 Package Categories

### List All Package Categories

Get all package categories (e.g., Homestay, Safari).

**Endpoint**: `GET /api/package-categories/list`

**Authentication**: Not required (Public)

**Response**:
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Homestay",
      "slug": "homestay",
      "displayOrder": 1,
      "isActive": true,
      "packageCount": 3,
      "createdAt": "2026-03-01 10:00:00",
      "updatedAt": "2026-03-01 10:00:00"
    },
    {
      "id": 2,
      "name": "Safari",
      "slug": "safari",
      "displayOrder": 2,
      "isActive": true,
      "packageCount": 2,
      "createdAt": "2026-03-01 10:00:00",
      "updatedAt": "2026-03-01 10:00:00"
    }
  ]
}
```

**Example Request**:
```javascript
fetch('https://api.bardiaecofriendlyhomestay.com/api/package-categories/list')
  .then(response => response.json())
  .then(data => console.log(data));
```

---

## 🎁 Packages

### List All Packages

Get all tour/stay packages with their features.

**Endpoint**: `GET /api/packages/list`

**Authentication**: Not required (Public)

**Query Parameters**:
- `category_id` (optional): Filter packages by category ID

**Response**:
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "categoryId": 1,
      "categoryName": "Homestay",
      "categorySlug": "homestay",
      "icon": "🏡",
      "name": "Rustic Homestay",
      "duration": "2 Nights · 3 Days",
      "price": 5000.00,
      "currency": "₹",
      "priceNote": "Per person",
      "description": "Experience authentic village life with local families",
      "isFeatured": true,
      "displayOrder": 1,
      "isActive": true,
      "features": [
        {
          "id": 1,
          "featureText": "Traditional Tharu meals",
          "displayOrder": 1
        },
        {
          "id": 2,
          "featureText": "Cultural dance performance",
          "displayOrder": 2
        }
      ],
      "createdAt": "2026-03-01 10:00:00",
      "updatedAt": "2026-03-01 10:00:00"
    }
  ]
}
```

**Example Request**:
```javascript
// Get all packages
fetch('https://api.bardiaecofriendlyhomestay.com/api/packages/list')
  .then(response => response.json())
  .then(data => console.log(data));

// Get packages for specific category
fetch('https://api.bardiaecofriendlyhomestay.com/api/packages/list?category_id=1')
  .then(response => response.json())
  .then(data => console.log(data));
```

---

## 🖼️ Gallery

### List Gallery Categories

Get all gallery categories (e.g., Gallery, Wildlife).

**Endpoint**: `GET /api/gallery-categories/list`

**Authentication**: Not required (Public)

**Response**:
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Gallery",
      "slug": "gallery",
      "displayOrder": 1,
      "isActive": true,
      "imageCount": 8,
      "createdAt": "2026-03-01 10:00:00",
      "updatedAt": "2026-03-01 10:00:00"
    },
    {
      "id": 2,
      "name": "Wildlife",
      "slug": "wildlife",
      "displayOrder": 2,
      "isActive": true,
      "imageCount": 12,
      "createdAt": "2026-03-01 10:00:00",
      "updatedAt": "2026-03-01 10:00:00"
    }
  ]
}
```

### List Gallery Images

Get all gallery images with full URLs.

**Endpoint**: `GET /api/gallery-images/list`

**Authentication**: Not required (Public)

**Query Parameters**:
- `category_id` (optional): Filter images by category ID

**Response**:
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "categoryId": 1,
      "categorySlug": "gallery",
      "categoryName": "Gallery",
      "imageUrl": "https://api.bardiaecofriendlyhomestay.com/storage/gallery/1772455599_3_32ced8bc.jpg",
      "altText": "Tiger in Bardiya National Park",
      "displayOrder": 1,
      "isActive": true,
      "createdAt": "2026-03-01 10:00:00"
    },
    {
      "id": 2,
      "categoryId": 1,
      "categorySlug": "gallery",
      "categoryName": "Gallery",
      "imageUrl": "https://api.bardiaecofriendlyhomestay.com/storage/gallery/1772455600_1_a4b3c2d1.jpg",
      "altText": "Traditional Tharu house",
      "displayOrder": 2,
      "isActive": true,
      "createdAt": "2026-03-01 10:00:00"
    }
  ]
}
```

**Example Request**:
```javascript
// Get all images
fetch('https://api.bardiaecofriendlyhomestay.com/api/gallery-images/list')
  .then(response => response.json())
  .then(data => console.log(data));

// Get images for specific category
fetch('https://api.bardiaecofriendlyhomestay.com/api/gallery-images/list?category_id=2')
  .then(response => response.json())
  .then(data => console.log(data));
```

**Usage in HTML**:
```html
<div class="gallery">
  <!-- Images will be populated here -->
</div>

<script>
fetch('https://api.bardiaecofriendlyhomestay.com/api/gallery-images/list?category_id=1')
  .then(response => response.json())
  .then(result => {
    const gallery = document.querySelector('.gallery');
    result.data.forEach(image => {
      const img = document.createElement('img');
      img.src = image.imageUrl;
      img.alt = image.altText;
      gallery.appendChild(img);
    });
  });
</script>
```

---

## 📝 Blog Categories

### List Blog Categories

Get all blog categories with post counts.

**Endpoint**: `GET /api/blog-categories/list`

**Authentication**: Not required (Public)

**Response**:
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Wildlife & Conservation",
      "slug": "wildlife-conservation",
      "description": "Articles about Bardiya National Park wildlife...",
      "display_order": 1,
      "is_active": true,
      "post_count": 5,
      "created_at": "2026-07-01 10:00:00",
      "updated_at": "2026-07-01 10:00:00"
    }
  ]
}
```

**Example Request**:
```javascript
fetch('https://api.bardiaecofriendlyhomestay.com/api/blog-categories/list')
  .then(response => response.json())
  .then(data => console.log(data));
```

---

## 📰 Blog Posts

### List Blog Posts (Paginated)

Get published blog posts with pagination and optional filters.

**Endpoint**: `GET /api/blog-posts/list`

**Authentication**: Not required (Public)

**Query Parameters**:
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | 1 | Page number |
| `per_page` | int | 10 | Posts per page (max 50) |
| `category_id` | int | — | Filter by category ID |
| `status` | string | published | Filter by status (draft/published/archived) |
| `is_featured` | int | — | Filter featured posts (1 or 0) |

**Response**:
```json
{
  "status": "success",
  "data": {
    "posts": [
      {
        "id": 1,
        "category_id": 1,
        "title": "Spotting the Royal Bengal Tiger in Bardiya",
        "slug": "spotting-royal-bengal-tiger-bardiya",
        "excerpt": "Bardiya National Park is one of the best places...",
        "featured_image": "/storage/blog/1720000000_tiger.jpg",
        "featured_image_url": "https://api.bardiaecofriendlyhomestay.com/storage/blog/1720000000_tiger.jpg",
        "featured_image_alt": "Royal Bengal Tiger in Bardiya",
        "author_name": "Bardiya Eco Team",
        "meta_title": "Spotting the Royal Bengal Tiger...",
        "meta_description": "Discover tips for spotting Royal Bengal Tigers...",
        "is_featured": true,
        "status": "published",
        "published_at": "2026-07-01 10:00:00",
        "display_order": 1,
        "is_active": true,
        "created_at": "2026-07-01 10:00:00",
        "updated_at": "2026-07-01 10:00:00",
        "category_name": "Wildlife & Conservation",
        "category_slug": "wildlife-conservation"
      }
    ],
    "pagination": {
      "page": 1,
      "per_page": 10,
      "total": 25,
      "pages": 3
    }
  }
}
```

**Example Requests**:
```javascript
// Get all published posts (page 1)
fetch('https://api.bardiaecofriendlyhomestay.com/api/blog-posts/list')
  .then(res => res.json())
  .then(data => console.log(data));

// Get page 2 with 6 posts per page
fetch('https://api.bardiaecofriendlyhomestay.com/api/blog-posts/list?page=2&per_page=6')
  .then(res => res.json())
  .then(data => console.log(data));

// Get featured posts only
fetch('https://api.bardiaecofriendlyhomestay.com/api/blog-posts/list?is_featured=1')
  .then(res => res.json())
  .then(data => console.log(data));

// Filter by category
fetch('https://api.bardiaecofriendlyhomestay.com/api/blog-posts/list?category_id=1')
  .then(res => res.json())
  .then(data => console.log(data));
```

### Get Single Blog Post

Get a single blog post by its slug with full content and related posts.

**Endpoint**: `GET /api/blog-posts/get?slug={slug}`

**Authentication**: Not required (Public)

**Query Parameters**:
- `slug` (required): The URL slug of the blog post

**Response**:
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "category_id": 1,
    "title": "Spotting the Royal Bengal Tiger in Bardiya",
    "slug": "spotting-royal-bengal-tiger-bardiya",
    "excerpt": "Bardiya National Park is one of the best places...",
    "content": "<h2>The Thrill of Tiger Spotting</h2><p>Bardiya National Park...</p>",
    "featured_image": "/storage/blog/1720000000_tiger.jpg",
    "featured_image_url": "https://api.bardiaecofriendlyhomestay.com/storage/blog/1720000000_tiger.jpg",
    "featured_image_alt": "Royal Bengal Tiger in Bardiya",
    "author_name": "Bardiya Eco Team",
    "meta_title": "Spotting the Royal Bengal Tiger in Bardiya National Park",
    "meta_description": "Discover tips for spotting Royal Bengal Tigers...",
    "meta_image": "/storage/blog/1720000000_tiger.jpg",
    "canonical_url": "https://www.bardiaecofriendlyhomestay.com/blog/spotting-royal-bengal-tiger-bardiya",
    "robots": "index,follow",
    "is_featured": true,
    "status": "published",
    "published_at": "2026-07-01 10:00:00",
    "category_name": "Wildlife & Conservation",
    "category_slug": "wildlife-conservation",
    "related_posts": [
      {
        "id": 3,
        "title": "Elephant Safari Experience",
        "slug": "elephant-safari-experience",
        "excerpt": "Explore the jungle on elephant-back...",
        "featured_image": "/storage/blog/1720001000_elephant.jpg",
        "featured_image_url": "https://api.bardiaecofriendlyhomestay.com/storage/blog/1720001000_elephant.jpg",
        "published_at": "2026-06-15 08:00:00",
        "category_name": "Wildlife & Conservation"
      }
    ]
  }
}
```

**Example Request**:
```javascript
fetch('https://api.bardiaecofriendlyhomestay.com/api/blog-posts/get?slug=spotting-royal-bengal-tiger-bardiya')
  .then(res => res.json())
  .then(data => console.log(data));
```

---

## 📞 Contact Methods

### List Contact Methods

Get all contact methods (Call, Email, WhatsApp).

**Endpoint**: `GET /api/contact-methods/list`

**Authentication**: Not required (Public)

**Response**:
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "icon": "📞",
      "title": "Call Us",
      "detail": "+977 084-123456",
      "href": "tel:+977084123456",
      "description": "Available 8 AM – 9 PM (NPT)",
      "displayOrder": 1,
      "isActive": true,
      "createdAt": "2026-03-01 10:00:00",
      "updatedAt": "2026-03-01 10:00:00"
    },
    {
      "id": 2,
      "icon": "✉️",
      "title": "Email Us",
      "detail": "inquiry@bardiaecofriendlyhomestay.com",
      "href": "mailto:inquiry@bardiaecofriendlyhomestay.com",
      "description": "We'll respond within 24 hours",
      "displayOrder": 2,
      "isActive": true,
      "createdAt": "2026-03-01 10:00:00",
      "updatedAt": "2026-03-01 10:00:00"
    },
    {
      "id": 3,
      "icon": "💬",
      "title": "WhatsApp",
      "detail": "+977 9801234567",
      "href": "https://wa.me/9779801234567",
      "description": "Chat with us instantly",
      "displayOrder": 3,
      "isActive": true,
      "createdAt": "2026-03-01 10:00:00",
      "updatedAt": "2026-03-01 10:00:00"
    }
  ]
}
```

**Example Request**:
```javascript
fetch('https://api.bardiaecofriendlyhomestay.com/api/contact-methods/list')
  .then(response => response.json())
  .then(data => console.log(data));
```

---

## 🔗 Social Links

### List Social Links

Get all social media links.

**Endpoint**: `GET /api/social-links/list`

**Authentication**: Not required (Public)

**Response**:
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "iconName": "fa-facebook",
      "label": "Facebook",
      "href": "https://facebook.com/bardiyaecofriendly",
      "displayOrder": 1,
      "isActive": true,
      "createdAt": "2026-03-01 10:00:00"
    },
    {
      "id": 2,
      "iconName": "fa-instagram",
      "label": "Instagram",
      "href": "https://instagram.com/bardiyaecofriendly",
      "displayOrder": 2,
      "isActive": true,
      "createdAt": "2026-03-01 10:00:00"
    }
  ]
}
```

**Example Request**:
```javascript
fetch('https://api.bardiaecofriendlyhomestay.com/api/social-links/list')
  .then(response => response.json())
  .then(data => console.log(data));
```

---

## 📬 Contact Submissions

### Submit Contact Form

Submit a contact/inquiry form from your website.

**Endpoint**: `POST /api/contact-submissions/submit`

**Authentication**: Not required (Public)

**Request Body**:
```json
{
  "full_name": "John Doe",
  "email": "john@example.com",
  "phone": "+977 9801234567",
  "num_guests": "2 Guests",
  "preferred_package": "Rustic Homestay",
  "travel_dates": "First week of April 2026",
  "message": "I'm interested in booking a homestay experience for my family."
}
```

**Required Fields**:
- `full_name` (string, max 255 chars)
- `email` (string, valid email format)
- `phone` (string, max 20 chars)
- `num_guests` (string, max 20 chars)
- `message` (text)

**Optional Fields**:
- `preferred_package` (string, max 100 chars)
- `travel_dates` (string, max 255 chars)

**Response**:
```json
{
  "status": "success",
  "data": {
    "id": 42
  },
  "message": "Thank you for your inquiry! We'll get back to you within 24 hours."
}
```

**Example Request**:
```javascript
const formData = {
  full_name: "John Doe",
  email: "john@example.com",
  phone: "+977 9801234567",
  num_guests: "2 Guests",
  preferred_package: "Rustic Homestay",
  travel_dates: "First week of April 2026",
  message: "I'm interested in booking a homestay experience."
};

fetch('https://api.bardiaecofriendlyhomestay.com/api/contact-submissions/submit', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(formData)
})
  .then(response => response.json())
  .then(data => {
    if (data.status === 'success') {
      alert('Thank you! We will contact you soon.');
    }
  })
  .catch(error => console.error('Error:', error));
```

**HTML Form Example**:
```html
<form id="contactForm">
  <input type="text" name="full_name" placeholder="Full Name" required>
  <input type="email" name="email" placeholder="Email" required>
  <input type="tel" name="phone" placeholder="Phone" required>
  <input type="text" name="num_guests" placeholder="Number of Guests" required>
  <input type="text" name="preferred_package" placeholder="Preferred Package">
  <input type="text" name="travel_dates" placeholder="Travel Dates">
  <textarea name="message" placeholder="Your Message" required></textarea>
  <button type="submit">Submit Inquiry</button>
</form>

<script>
document.getElementById('contactForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const formData = new FormData(e.target);
  const data = Object.fromEntries(formData);
  
  try {
    const response = await fetch('https://api.bardiaecofriendlyhomestay.com/api/contact-submissions/submit', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    
    const result = await response.json();
    
    if (result.status === 'success') {
      alert(result.message);
      e.target.reset();
    } else {
      alert('Error: ' + result.message);
    }
  } catch (error) {
    alert('Failed to submit form. Please try again.');
  }
});
</script>
```

---

## 📄 Pages

### List All Pages

Get CMS pages (About Us, Contact Us, etc.).

**Endpoint**: `GET /api/pages/list`

**Authentication**: Not required (Public)

**Response**:
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "title": "About Us",
      "slug": "about-us",
      "content": "<h2>Welcome to Bardiya Eco Friendly</h2><p>We are a community-driven initiative...</p>",
      "status": "published",
      "createdAt": "2026-03-01 10:00:00",
      "updatedAt": "2026-03-01 10:00:00"
    },
    {
      "id": 2,
      "title": "Contact Us",
      "slug": "contact-us",
      "content": "<h2>Get in Touch</h2><p><strong>Email:</strong> info@bardiyaecofriendly.com</p>",
      "status": "published",
      "createdAt": "2026-03-01 10:00:00",
      "updatedAt": "2026-03-01 10:00:00"
    }
  ]
}
```

**Example Request**:
```javascript
fetch('https://api.bardiaecofriendlyhomestay.com/api/pages/list')
  .then(response => response.json())
  .then(data => console.log(data));
```

---

## 📋 Response Format

All API responses follow a consistent JSON structure:

### Success Response
```json
{
  "status": "success",
  "data": { ... } | [ ... ],
  "message": "Optional success message"
}
```

### Error Response
```json
{
  "status": "error",
  "message": "Human-readable error message"
}
```

---

## ⚠️ Error Handling

### HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid input or missing required fields |
| 404 | Not Found | Endpoint or resource not found |
| 405 | Method Not Allowed | Wrong HTTP method used |
| 500 | Internal Server Error | Server-side error |

### Common Error Messages

**400 Bad Request**:
```json
{
  "status": "error",
  "message": "full_name, email, phone, num_guests, and message are required."
}
```

**404 Not Found**:
```json
{
  "status": "error",
  "message": "Endpoint not found."
}
```

**405 Method Not Allowed**:
```json
{
  "status": "error",
  "message": "Method not allowed. Use POST."
}
```

---

## 🔧 Complete Integration Example

Here's a complete example of integrating all public APIs into a website:

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bardiya Eco Friendly</title>
  <style>
    .gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 16px; }
    .gallery img { width: 100%; height: 200px; object-fit: cover; border-radius: 8px; }
    .packages { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
    .package-card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; }
  </style>
</head>
<body>
  <h1>Bardiya Eco Friendly Homestay</h1>
  
  <!-- Packages Section -->
  <section>
    <h2>Our Packages</h2>
    <div id="packages" class="packages"></div>
  </section>
  
  <!-- Gallery Section -->
  <section>
    <h2>Gallery</h2>
    <div id="gallery" class="gallery"></div>
  </section>
  
  <!-- Contact Form -->
  <section>
    <h2>Contact Us</h2>
    <form id="contactForm">
      <input type="text" name="full_name" placeholder="Full Name" required><br>
      <input type="email" name="email" placeholder="Email" required><br>
      <input type="tel" name="phone" placeholder="Phone" required><br>
      <input type="text" name="num_guests" placeholder="Number of Guests" required><br>
      <textarea name="message" placeholder="Your Message" required></textarea><br>
      <button type="submit">Submit</button>
    </form>
  </section>

  <script>
    const API_BASE = 'https://api.bardiaecofriendlyhomestay.com/api';
    
    // Load Packages
    fetch(`${API_BASE}/packages/list`)
      .then(res => res.json())
      .then(result => {
        const container = document.getElementById('packages');
        result.data.forEach(pkg => {
          const card = document.createElement('div');
          card.className = 'package-card';
          card.innerHTML = `
            <h3>${pkg.icon} ${pkg.name}</h3>
            <p>${pkg.duration}</p>
            <p><strong>${pkg.currency} ${pkg.price}</strong> ${pkg.priceNote}</p>
            <p>${pkg.description}</p>
            <ul>
              ${pkg.features.map(f => `<li>${f.featureText}</li>`).join('')}
            </ul>
          `;
          container.appendChild(card);
        });
      });
    
    // Load Gallery
    fetch(`${API_BASE}/gallery-images/list`)
      .then(res => res.json())
      .then(result => {
        const gallery = document.getElementById('gallery');
        result.data.forEach(img => {
          const image = document.createElement('img');
          image.src = img.imageUrl;
          image.alt = img.altText;
          gallery.appendChild(image);
        });
      });
    
    // Handle Contact Form
    document.getElementById('contactForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(e.target);
      const data = Object.fromEntries(formData);
      
      const response = await fetch(`${API_BASE}/contact-submissions/submit`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      });
      
      const result = await response.json();
      alert(result.message);
      if (result.status === 'success') e.target.reset();
    });
  </script>
</body>
</html>
```

---

## 🐘 Activities & Wildlife Experiences

### List All Activities

Get all 12 Wildlife Experiences / Activities.

**Endpoint**: `GET /api/activities/list`

**Authentication**: Not required (Public)

**Response**:
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "title": "Jeep Safari",
      "slug": "jeep-safari",
      "icon": "🚙",
      "shortDescription": "Discover the diverse landscapes of Bardia National Park on a guided jeep safari.",
      "description": "Discover the diverse landscapes of Bardia National Park on a guided jeep safari. Travel deep into the wilderness in search of Royal Bengal Tigers, Greater One-horned Rhinoceroses, Wild Asian Elephants...",
      "displayOrder": 1,
      "isActive": true
    }
  ]
}
```

### Get Activity Details

Get details for a single activity by slug or ID.

**Endpoint**: `GET /api/activities/get?slug=jeep-safari`

---

## 📞 Support

For API support or questions, contact:
- **Email**: inquiry@bardiaecofriendlyhomestay.com
- **Phone**: +977 084-123456

---

**API Version**: 1.1  
**Last Updated**: July 2026  
**Base URL**: https://api.bardiaecofriendlyhomestay.com

