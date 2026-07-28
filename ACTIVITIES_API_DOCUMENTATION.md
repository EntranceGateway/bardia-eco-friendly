# 🐘 Activities & Wildlife Experiences — Frontend API Documentation

This document covers the public API endpoints and code implementation guide for integrating the **12 Wildlife Experiences** into your frontend web application or mobile client.

---

## 🌐 Base URL & Configuration

- **Production Base URL**: `https://api.bardiaecofriendlyhomestay.com/api`
- **Local XAMPP Base URL**: `http://localhost/bardia-eco-friendly/public/index.php/api`
- **Content-Type**: `application/json`

---

## 📋 Table of Endpoints

| Endpoint | Method | Access | Description |
| :--- | :--- | :--- | :--- |
| `/api/activities/list` | `GET` | Public | List all 12 Wildlife Experiences with short summaries & descriptions |
| `/api/activities/get` | `GET` | Public | Get single activity details by `slug` or `id` (for popup modal screen) |
| `/api/page-seo/get?slug=activities` | `GET` | Public | Get page SEO metadata, OpenGraph tags, and canonical URL |

---

## 1. List All Activities

Fetch all active Wildlife Experiences ordered by display position.

### Request
- **Endpoint**: `GET /api/activities/list`
- **Headers**:
  ```http
  Accept: application/json
  ```

#### Example `fetch` Request:
```javascript
fetch('https://api.bardiaecofriendlyhomestay.com/api/activities/list')
  .then(res => res.json())
  .then(data => console.log(data));
```

### Response
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "title": "Jeep Safari",
      "slug": "jeep-safari",
      "icon": "🚙",
      "short_description": "Discover the diverse landscapes of Bardia National Park on a guided jeep safari.",
      "description": "Discover the diverse landscapes of Bardia National Park on a guided jeep safari. Travel deep into the wilderness in search of Royal Bengal Tigers, Greater One-horned Rhinoceroses, Wild Asian Elephants, Leopards, Sloth Bears, and many other species while learning about the park’s unique ecosystem from experienced naturalist guides.",
      "meta_title": "Jeep Safari | Bardia Eco-Friendly Homestay",
      "meta_description": "Discover the diverse landscapes of Bardia National Park on a guided jeep safari in search of Royal Bengal Tigers, Rhinos, and Elephants.",
      "meta_keywords": null,
      "meta_image": null,
      "canonical_url": null,
      "robots": "index,follow",
      "display_order": 1,
      "is_active": true,
      "created_at": "2026-07-28 02:43:09",
      "updated_at": "2026-07-28 02:43:09"
    },
    {
      "id": 2,
      "title": "Guided Walking Safari",
      "slug": "guided-walking-safari",
      "icon": "🥾",
      "short_description": "Experience the jungle at ground level with our expert local guides.",
      "description": "Experience the jungle at ground level with our expert local guides. Follow fresh animal tracks, listen to alarm calls, and gain a deeper understanding of wildlife behavior while exploring one of Nepal’s finest tiger habitats.",
      "meta_title": "Guided Walking Safari | Bardia Eco-Friendly Homestay",
      "meta_description": "Experience the jungle at ground level with expert local guides in Bardia National Park.",
      "meta_keywords": null,
      "meta_image": null,
      "canonical_url": null,
      "robots": "index,follow",
      "display_order": 2,
      "is_active": true,
      "created_at": "2026-07-28 02:43:09",
      "updated_at": "2026-07-28 02:43:09"
    }
  ]
}
```

---

## 2. Get Single Activity Details (For Popup Modal)

Fetch full details for a specific activity when a user clicks on an activity card.

### Request
- **Endpoint**: `GET /api/activities/get?slug={slug}`
- **Query Parameters**:
  - `slug` *(string)*: Unique slug identifier (e.g. `jeep-safari`, `river-safari-rafting`).
  - Or `id` *(integer)*: Activity primary key ID (e.g. `1`).

#### Example `fetch` Request:
```javascript
const slug = 'jeep-safari';
fetch(`https://api.bardiaecofriendlyhomestay.com/api/activities/get?slug=${slug}`)
  .then(res => res.json())
  .then(res => {
    if (res.status === 'success') {
      openDetailsModal(res.data);
    }
  });
```

### Response
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "title": "Jeep Safari",
    "slug": "jeep-safari",
    "icon": "🚙",
    "short_description": "Discover the diverse landscapes of Bardia National Park on a guided jeep safari.",
    "description": "Discover the diverse landscapes of Bardia National Park on a guided jeep safari. Travel deep into the wilderness in search of Royal Bengal Tigers, Greater One-horned Rhinoceroses, Wild Asian Elephants, Leopards, Sloth Bears, and many other species while learning about the park’s unique ecosystem from experienced naturalist guides.",
    "meta_title": "Jeep Safari | Bardia Eco-Friendly Homestay",
    "meta_description": "Discover the diverse landscapes of Bardia National Park on a guided jeep safari in search of Royal Bengal Tigers, Rhinos, and Elephants.",
    "meta_keywords": null,
    "meta_image": null,
    "canonical_url": null,
    "robots": "index,follow",
    "display_order": 1,
    "is_active": true,
    "created_at": "2026-07-28 02:43:09",
    "updated_at": "2026-07-28 02:43:09"
  }
}
```

---

## 3. Get Activities Page SEO Metadata

Fetch SEO tags for the activities main section.

### Request
- **Endpoint**: `GET /api/page-seo/get?slug=activities`

### Response
```json
{
  "status": "success",
  "data": {
    "meta_title": "Activities & Wildlife Experiences | Bardia Eco-Friendly Homestay",
    "meta_description": "Explore 12 exciting wildlife experiences including Jeep Safaris, Walking Safaris, Birdwatching, and River Safaris in Bardia National Park.",
    "meta_keywords": "wildlife activities Bardia, Jeep safari, walking safari, birdwatching, rafting",
    "meta_image": "https://api.bardiaecofriendlyhomestay.com/logo.png",
    "canonical_url": "https://www.bardiaecofriendlyhomestay.com/activities",
    "robots": "index,follow",
    "language_code": "en",
    "hreflang": [],
    "schemas": [],
    "og": {
      "title": "Activities & Wildlife Experiences | Bardia Eco-Friendly Homestay",
      "description": "Explore 12 exciting wildlife experiences including Jeep Safaris, Walking Safaris, Birdwatching, and River Safaris in Bardia National Park.",
      "image": "https://api.bardiaecofriendlyhomestay.com/logo.png",
      "url": "https://www.bardiaecofriendlyhomestay.com/activities",
      "type": "website"
    }
  }
}
```

---

## 💻 4. Frontend Integration Example (HTML & JavaScript)

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Wildlife Experiences</title>
  <style>
    body { font-family: sans-serif; background: #f8fafc; padding: 20px; }
    .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
    .card { background: #fff; border-radius: 8px; padding: 20px; border: 1px solid #ddd; cursor: pointer; }
    .card:hover { border-color: #2e7d32; }
    .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
    .modal.active { display: flex; }
    .modal-content { background: #fff; padding: 30px; border-radius: 12px; max-width: 500px; }
  </style>
</head>
<body>

<div class="grid" id="activitiesGrid">Loading...</div>

<div class="modal" id="detailsModal" onclick="closeModal()">
  <div class="modal-content" onclick="event.stopPropagation()">
    <h2 id="modalTitle"></h2>
    <p id="modalDesc"></p>
    <button onclick="closeModal()">Close</button>
  </div>
</div>

<script>
  fetch('https://api.bardiaecofriendlyhomestay.com/api/activities/list')
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {
        document.getElementById('activitiesGrid').innerHTML = res.data.map(act => `
          <div class="card" onclick="openModal('${act.title}', '${escape(act.description)}')">
            <h3>${act.icon || ''} ${act.title}</h3>
            <p>${act.short_description}</p>
          </div>
        `).join('');
      }
    });

  function openModal(title, desc) {
    document.getElementById('modalTitle').innerText = title;
    document.getElementById('modalDesc').innerText = unescape(desc);
    document.getElementById('detailsModal').classList.add('active');
  }

  function closeModal() {
    document.getElementById('detailsModal').classList.remove('active');
  }
</script>
</body>
</html>
```
