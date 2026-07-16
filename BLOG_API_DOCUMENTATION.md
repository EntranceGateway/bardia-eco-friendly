# 📝 Blog API — Frontend Integration Guide

**Base URL**: `https://api.bardiaecofriendlyhomestay.com`

Complete frontend workflow for integrating the Blog module into your website (React, Next.js, or vanilla JS).

---

## 📋 Table of Contents

1. [API Endpoints Overview](#api-endpoints-overview)
2. [Blog Categories API](#blog-categories-api)
3. [Blog Posts — List (Paginated)](#blog-posts--list-paginated)
4. [Blog Posts — Single Post by Slug](#blog-posts--single-post-by-slug)
5. [Frontend Pages Workflow](#frontend-pages-workflow)
6. [SEO Integration](#seo-integration)
7. [React / Next.js Examples](#react--nextjs-examples)
8. [Vanilla JS Examples](#vanilla-js-examples)
9. [URL Structure](#url-structure)

---

## 🔗 API Endpoints Overview

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/api/blog-categories/list` | Public | All categories with post counts |
| `GET` | `/api/blog-posts/list` | Public | Paginated post listing with filters |
| `GET` | `/api/blog-posts/get?slug={slug}` | Public | Single post with full content + related posts |

---

## 📂 Blog Categories API

### `GET /api/blog-categories/list`

Returns all blog categories with the number of published posts in each.

**Request**:
```
GET https://api.bardiaecofriendlyhomestay.com/api/blog-categories/list
```

**Response**:
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Wildlife & Conservation",
      "slug": "wildlife-conservation",
      "description": "Articles about Bardiya National Park wildlife, conservation efforts, and safari experiences.",
      "display_order": 1,
      "is_active": true,
      "post_count": 5,
      "created_at": "2026-07-01 10:00:00",
      "updated_at": "2026-07-01 10:00:00"
    },
    {
      "id": 2,
      "name": "Travel Tips",
      "slug": "travel-tips",
      "description": "Practical travel advice, packing lists, and tips for visiting Bardiya.",
      "display_order": 2,
      "is_active": true,
      "post_count": 3,
      "created_at": "2026-07-01 10:00:00",
      "updated_at": "2026-07-01 10:00:00"
    }
  ]
}
```

**Response Fields**:
| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Category ID |
| `name` | string | Category display name |
| `slug` | string | URL-friendly identifier |
| `description` | string | Category description |
| `display_order` | int | Sort order |
| `is_active` | bool | Whether category is active |
| `post_count` | int | Number of published posts in this category |

---

## 📰 Blog Posts — List (Paginated)

### `GET /api/blog-posts/list`

Returns paginated blog posts with optional filters. Default: only published, active posts.

**Query Parameters**:
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | `1` | Page number |
| `per_page` | int | `10` | Posts per page (max 50) |
| `category_id` | int | — | Filter by category ID |
| `status` | string | `published` | Filter: `draft`, `published`, `archived` |
| `is_featured` | int | — | Filter featured posts: `1` or `0` |

**Request Examples**:
```
# All published posts, page 1
GET /api/blog-posts/list

# Page 2, 6 per page
GET /api/blog-posts/list?page=2&per_page=6

# Featured posts only
GET /api/blog-posts/list?is_featured=1

# Posts in a specific category
GET /api/blog-posts/list?category_id=1

# Combined filters
GET /api/blog-posts/list?category_id=1&page=1&per_page=6&is_featured=1
```

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
        "excerpt": "Bardiya National Park is one of the best places in Nepal to spot the elusive Royal Bengal Tiger.",
        "featured_image": "/storage/blog/1720000000_tiger.jpg",
        "featured_image_url": "https://api.bardiaecofriendlyhomestay.com/storage/blog/1720000000_tiger.jpg",
        "featured_image_alt": "Royal Bengal Tiger in Bardiya National Park",
        "author_name": "Bardiya Eco Team",
        "meta_title": "Spotting the Royal Bengal Tiger in Bardiya National Park",
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

**Post Fields** (listing — no `content` field):
| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Post ID |
| `category_id` | int | Category FK |
| `title` | string | Post title |
| `slug` | string | URL slug — use for linking to `/blog/{slug}` |
| `excerpt` | string | Short summary (max 500 chars) for card previews |
| `featured_image` | string\|null | Relative path |
| `featured_image_url` | string\|null | **Full URL** — use this directly in `<img src>` |
| `featured_image_alt` | string\|null | Alt text for the image |
| `author_name` | string\|null | Author display name |
| `is_featured` | bool | Featured flag for homepage highlights |
| `status` | string | `draft` / `published` / `archived` |
| `published_at` | string\|null | Publication date (ISO format) |
| `category_name` | string | Category display name |
| `category_slug` | string | Category slug |

**Pagination Fields**:
| Field | Type | Description |
|-------|------|-------------|
| `page` | int | Current page number |
| `per_page` | int | Items per page |
| `total` | int | Total number of matching posts |
| `pages` | int | Total number of pages |

---

## 📄 Blog Posts — Single Post by Slug

### `GET /api/blog-posts/get?slug={slug}`

Returns a single post with **full HTML content**, SEO metadata, and up to 3 related posts from the same category.

**Request**:
```
GET /api/blog-posts/get?slug=spotting-royal-bengal-tiger-bardiya
```

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
    "content": "<h2>The Thrill of Tiger Spotting</h2><p>Bardiya National Park, nestled in the western Terai...</p>",
    "featured_image": "/storage/blog/1720000000_tiger.jpg",
    "featured_image_url": "https://api.bardiaecofriendlyhomestay.com/storage/blog/1720000000_tiger.jpg",
    "featured_image_alt": "Royal Bengal Tiger in Bardiya",
    "author_name": "Bardiya Eco Team",
    "meta_title": "Spotting the Royal Bengal Tiger in Bardiya National Park | Bardiya Eco Friendly",
    "meta_description": "Discover tips for spotting Royal Bengal Tigers in Bardiya National Park, Nepal.",
    "meta_image": "/storage/blog/1720000000_tiger.jpg",
    "canonical_url": "https://www.bardiaecofriendlyhomestay.com/blog/spotting-royal-bengal-tiger-bardiya",
    "robots": "index,follow",
    "is_featured": true,
    "status": "published",
    "published_at": "2026-07-01 10:00:00",
    "display_order": 1,
    "is_active": true,
    "created_at": "2026-07-01 10:00:00",
    "updated_at": "2026-07-01 10:00:00",
    "category_name": "Wildlife & Conservation",
    "category_slug": "wildlife-conservation",
    "related_posts": [
      {
        "id": 3,
        "title": "Elephant Safari Experience in Bardiya",
        "slug": "elephant-safari-experience",
        "excerpt": "Explore the jungle on elephant-back for a unique wildlife encounter.",
        "featured_image": "/storage/blog/1720001000_elephant.jpg",
        "featured_image_url": "https://api.bardiaecofriendlyhomestay.com/storage/blog/1720001000_elephant.jpg",
        "published_at": "2026-06-15 08:00:00",
        "category_name": "Wildlife & Conservation"
      }
    ]
  }
}
```

**Additional Fields** (only on single post, not on list):
| Field | Type | Description |
|-------|------|-------------|
| `content` | string | Full HTML body — render with `dangerouslySetInnerHTML` (React) or `innerHTML` |
| `meta_image` | string\|null | OG image for social sharing |
| `canonical_url` | string\|null | Canonical URL for SEO |
| `robots` | string | Robots directive (`index,follow`, etc.) |
| `related_posts` | array | Up to 3 related posts from the same category |

---

## 🛠️ Frontend Pages Workflow

### Page 1: Blog Listing (`/blog`)

```
┌─────────────────────────────────────────────────────────────┐
│  📰 Blog                                                    │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ [All] [Wildlife & Conservation] [Travel Tips]         │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                              │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐          │
│  │  📷 Image   │ │  📷 Image   │ │  📷 Image   │          │
│  │  Title      │ │  Title      │ │  Title      │          │
│  │  Excerpt... │ │  Excerpt... │ │  Excerpt... │          │
│  │  Author     │ │  Author     │ │  Author     │          │
│  │  Date       │ │  Date       │ │  Date       │          │
│  │  [Read →]   │ │  [Read →]   │ │  [Read →]   │          │
│  └─────────────┘ └─────────────┘ └─────────────┘          │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │       ← Prev   Page 1 of 3   Next →                  │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

**Data flow**:
1. Fetch categories → `GET /api/blog-categories/list` → render filter tabs
2. Fetch posts → `GET /api/blog-posts/list?page=1&per_page=6` → render post cards
3. On category tab click → `GET /api/blog-posts/list?category_id={id}&page=1`
4. On pagination click → `GET /api/blog-posts/list?page={n}`

---

### Page 2: Single Blog Post (`/blog/{slug}`)

```
┌─────────────────────────────────────────────────────────────┐
│  ← Back to Blog                                             │
│                                                              │
│  Category: Wildlife & Conservation                           │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │         📷 Featured Image (full width)                │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                              │
│  Spotting the Royal Bengal Tiger in Bardiya                  │
│  By Bardiya Eco Team  •  July 1, 2026                       │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  <article>                                            │   │
│  │    <h2>The Thrill of Tiger Spotting</h2>              │   │
│  │    <p>Bardiya National Park, nestled in the...</p>    │   │
│  │    ...full HTML content...                             │   │
│  │  </article>                                           │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                              │
│  ── Related Posts ──────────────────────────────────────     │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐          │
│  │  Related 1  │ │  Related 2  │ │  Related 3  │          │
│  └─────────────┘ └─────────────┘ └─────────────┘          │
└─────────────────────────────────────────────────────────────┘
```

**Data flow**:
1. Extract `slug` from URL path → e.g., `/blog/spotting-royal-bengal-tiger-bardiya`
2. Fetch post → `GET /api/blog-posts/get?slug={slug}`
3. Set SEO meta tags from response (`meta_title`, `meta_description`, `meta_image`, `canonical_url`, `robots`)
4. Render `content` as HTML
5. Render `related_posts` as small cards with links to `/blog/{related_slug}`

---

### Page 3: Homepage Featured Posts (optional)

**Data flow**:
1. Fetch → `GET /api/blog-posts/list?is_featured=1&per_page=3`
2. Render 3 featured post cards in a "Latest from our Blog" section

---

## 🔎 SEO Integration

### Meta Tags (per blog post page)

Use the fields from `GET /api/blog-posts/get` to set these `<head>` tags:

```html
<!-- Primary Meta -->
<title>{meta_title || title}</title>
<meta name="description" content="{meta_description || excerpt}">
<meta name="robots" content="{robots}">
<link rel="canonical" href="{canonical_url || current_url}">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="article">
<meta property="og:title" content="{meta_title || title}">
<meta property="og:description" content="{meta_description || excerpt}">
<meta property="og:image" content="{featured_image_url}">
<meta property="og:url" content="{canonical_url || current_url}">
<meta property="article:published_time" content="{published_at}">
<meta property="article:author" content="{author_name}">
<meta property="article:section" content="{category_name}">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{meta_title || title}">
<meta name="twitter:description" content="{meta_description || excerpt}">
<meta name="twitter:image" content="{featured_image_url}">
```

### JSON-LD Structured Data (per blog post page)

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BlogPosting",
  "headline": "{title}",
  "description": "{meta_description || excerpt}",
  "image": "{featured_image_url}",
  "author": {
    "@type": "Person",
    "name": "{author_name}"
  },
  "publisher": {
    "@type": "Organization",
    "name": "Bardiya Eco Friendly Homestay",
    "logo": {
      "@type": "ImageObject",
      "url": "https://www.bardiaecofriendlyhomestay.com/logo.png"
    }
  },
  "datePublished": "{published_at}",
  "dateModified": "{updated_at}",
  "mainEntityOfPage": "{canonical_url || current_url}",
  "articleSection": "{category_name}"
}
</script>
```

### Sitemap

Blog posts are automatically included in `https://api.bardiaecofriendlyhomestay.com/sitemap.xml` with URL pattern:
```
https://www.bardiaecofriendlyhomestay.com/blog/{slug}
```

---

## ⚛️ React / Next.js Examples

### API Helper

```javascript
// lib/api.js
const API_BASE = 'https://api.bardiaecofriendlyhomestay.com/api';

export async function fetchBlogCategories() {
  const res = await fetch(`${API_BASE}/blog-categories/list`);
  const json = await res.json();
  return json.data; // array of categories
}

export async function fetchBlogPosts({ page = 1, perPage = 6, categoryId, isFeatured } = {}) {
  const params = new URLSearchParams({ page, per_page: perPage });
  if (categoryId) params.set('category_id', categoryId);
  if (isFeatured !== undefined) params.set('is_featured', isFeatured ? 1 : 0);

  const res = await fetch(`${API_BASE}/blog-posts/list?${params}`);
  const json = await res.json();
  return json.data; // { posts: [...], pagination: {...} }
}

export async function fetchBlogPost(slug) {
  const res = await fetch(`${API_BASE}/blog-posts/get?slug=${encodeURIComponent(slug)}`);
  const json = await res.json();
  if (json.status !== 'success') throw new Error(json.message);
  return json.data; // single post object
}
```

### Blog Listing Page (Next.js)

```jsx
// app/blog/page.jsx
import { fetchBlogCategories, fetchBlogPosts } from '@/lib/api';

export const metadata = {
  title: 'Blog | Bardiya Eco Friendly Homestay',
  description: 'Read our latest articles about wildlife, travel tips, and eco-friendly tourism in Bardiya.',
};

export default async function BlogPage({ searchParams }) {
  const page = Number(searchParams?.page) || 1;
  const categoryId = searchParams?.category || null;

  const [categories, { posts, pagination }] = await Promise.all([
    fetchBlogCategories(),
    fetchBlogPosts({ page, perPage: 6, categoryId }),
  ]);

  return (
    <main>
      <h1>Our Blog</h1>

      {/* Category Filter */}
      <nav>
        <a href="/blog" className={!categoryId ? 'active' : ''}>All</a>
        {categories.map(cat => (
          <a key={cat.id} href={`/blog?category=${cat.id}`}
             className={categoryId == cat.id ? 'active' : ''}>
            {cat.name} ({cat.post_count})
          </a>
        ))}
      </nav>

      {/* Post Cards */}
      <div className="blog-grid">
        {posts.map(post => (
          <article key={post.id} className="blog-card">
            {post.featured_image_url && (
              <img src={post.featured_image_url} alt={post.featured_image_alt || post.title} />
            )}
            <span className="category">{post.category_name}</span>
            <h2>{post.title}</h2>
            <p>{post.excerpt}</p>
            <div className="meta">
              <span>{post.author_name}</span>
              <time>{new Date(post.published_at).toLocaleDateString()}</time>
            </div>
            <a href={`/blog/${post.slug}`}>Read More →</a>
          </article>
        ))}
      </div>

      {/* Pagination */}
      {pagination.pages > 1 && (
        <nav className="pagination">
          {page > 1 && <a href={`/blog?page=${page - 1}`}>← Previous</a>}
          <span>Page {pagination.page} of {pagination.pages}</span>
          {page < pagination.pages && <a href={`/blog?page=${page + 1}`}>Next →</a>}
        </nav>
      )}
    </main>
  );
}
```

### Single Post Page (Next.js)

```jsx
// app/blog/[slug]/page.jsx
import { fetchBlogPost } from '@/lib/api';
import { notFound } from 'next/navigation';

export async function generateMetadata({ params }) {
  try {
    const post = await fetchBlogPost(params.slug);
    return {
      title: post.meta_title || post.title,
      description: post.meta_description || post.excerpt,
      openGraph: {
        title: post.meta_title || post.title,
        description: post.meta_description || post.excerpt,
        images: post.featured_image_url ? [post.featured_image_url] : [],
        type: 'article',
        publishedTime: post.published_at,
        authors: [post.author_name],
        section: post.category_name,
      },
      robots: post.robots,
      alternates: post.canonical_url ? { canonical: post.canonical_url } : {},
    };
  } catch {
    return { title: 'Post Not Found' };
  }
}

export default async function BlogPostPage({ params }) {
  let post;
  try {
    post = await fetchBlogPost(params.slug);
  } catch {
    notFound();
  }

  return (
    <main>
      <a href="/blog">← Back to Blog</a>

      <span className="category">{post.category_name}</span>
      <h1>{post.title}</h1>

      <div className="post-meta">
        <span>By {post.author_name}</span>
        <time>{new Date(post.published_at).toLocaleDateString('en-US', {
          year: 'numeric', month: 'long', day: 'numeric'
        })}</time>
      </div>

      {post.featured_image_url && (
        <img src={post.featured_image_url} alt={post.featured_image_alt || post.title}
             className="featured-image" />
      )}

      {/* Render HTML content */}
      <article dangerouslySetInnerHTML={{ __html: post.content }} />

      {/* Related Posts */}
      {post.related_posts?.length > 0 && (
        <section className="related-posts">
          <h2>Related Posts</h2>
          <div className="related-grid">
            {post.related_posts.map(related => (
              <a key={related.id} href={`/blog/${related.slug}`} className="related-card">
                {related.featured_image_url && (
                  <img src={related.featured_image_url} alt={related.title} />
                )}
                <h3>{related.title}</h3>
                <p>{related.excerpt}</p>
              </a>
            ))}
          </div>
        </section>
      )}

      {/* JSON-LD Structured Data */}
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify({
        "@context": "https://schema.org",
        "@type": "BlogPosting",
        "headline": post.title,
        "description": post.meta_description || post.excerpt,
        "image": post.featured_image_url,
        "author": { "@type": "Person", "name": post.author_name },
        "publisher": {
          "@type": "Organization",
          "name": "Bardiya Eco Friendly Homestay",
        },
        "datePublished": post.published_at,
        "dateModified": post.updated_at,
        "articleSection": post.category_name,
      })}} />
    </main>
  );
}
```

---

## 🌐 Vanilla JS Examples

### Blog Listing (Vanilla HTML + JS)

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Blog | Bardiya Eco Friendly Homestay</title>
</head>
<body>
  <h1>Our Blog</h1>

  <!-- Category Tabs -->
  <nav id="categoryTabs"></nav>

  <!-- Blog Cards Grid -->
  <div id="blogGrid" class="blog-grid"></div>

  <!-- Pagination -->
  <nav id="pagination"></nav>

  <script>
    const API = 'https://api.bardiaecofriendlyhomestay.com/api';
    let currentPage = 1;
    let currentCategory = null;

    // Load categories
    async function loadCategories() {
      const res = await fetch(`${API}/blog-categories/list`);
      const { data } = await res.json();
      const tabs = document.getElementById('categoryTabs');

      tabs.innerHTML = `<button onclick="filterCategory(null)" class="active">All</button>`;
      data.forEach(cat => {
        tabs.innerHTML += `<button onclick="filterCategory(${cat.id})">${cat.name} (${cat.post_count})</button>`;
      });
    }

    // Load posts
    async function loadPosts(page = 1, categoryId = null) {
      const params = new URLSearchParams({ page, per_page: 6 });
      if (categoryId) params.set('category_id', categoryId);

      const res = await fetch(`${API}/blog-posts/list?${params}`);
      const { data } = await res.json();
      const grid = document.getElementById('blogGrid');

      grid.innerHTML = data.posts.map(post => `
        <article class="blog-card">
          ${post.featured_image_url
            ? `<img src="${post.featured_image_url}" alt="${post.featured_image_alt || ''}">`
            : '<div class="no-image">📝</div>'}
          <span class="category">${post.category_name}</span>
          <h2>${post.title}</h2>
          <p>${post.excerpt || ''}</p>
          <div class="meta">
            <span>${post.author_name || ''}</span>
            <time>${post.published_at ? new Date(post.published_at).toLocaleDateString() : ''}</time>
          </div>
          <a href="/blog/${post.slug}">Read More →</a>
        </article>
      `).join('');

      // Pagination
      const pag = data.pagination;
      const pagEl = document.getElementById('pagination');
      pagEl.innerHTML = '';
      if (pag.pages > 1) {
        if (pag.page > 1) pagEl.innerHTML += `<button onclick="goToPage(${pag.page - 1})">← Prev</button>`;
        pagEl.innerHTML += `<span>Page ${pag.page} of ${pag.pages}</span>`;
        if (pag.page < pag.pages) pagEl.innerHTML += `<button onclick="goToPage(${pag.page + 1})">Next →</button>`;
      }
    }

    function filterCategory(catId) {
      currentCategory = catId;
      currentPage = 1;
      loadPosts(1, catId);
      // Update active tab
      document.querySelectorAll('#categoryTabs button').forEach((btn, i) => {
        btn.className = (catId === null && i === 0) || btn.textContent.includes(catId) ? 'active' : '';
      });
    }

    function goToPage(page) {
      currentPage = page;
      loadPosts(page, currentCategory);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Init
    loadCategories();
    loadPosts();
  </script>
</body>
</html>
```

### Single Blog Post (Vanilla HTML + JS)

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title id="pageTitle">Loading...</title>
  <meta name="description" id="metaDesc" content="">
  <meta property="og:type" content="article">
  <meta property="og:title" id="ogTitle" content="">
  <meta property="og:description" id="ogDesc" content="">
  <meta property="og:image" id="ogImage" content="">
</head>
<body>
  <a href="/blog">← Back to Blog</a>
  <div id="postContent">Loading...</div>
  <section id="relatedPosts"></section>

  <script>
    const API = 'https://api.bardiaecofriendlyhomestay.com/api';

    // Extract slug from URL: /blog/my-post-slug
    const slug = window.location.pathname.split('/blog/')[1];

    async function loadPost() {
      const res = await fetch(`${API}/blog-posts/get?slug=${encodeURIComponent(slug)}`);
      const { data: post, status } = await res.json();

      if (status !== 'success') {
        document.getElementById('postContent').innerHTML = '<h1>Post not found</h1>';
        return;
      }

      // Set SEO meta tags
      document.getElementById('pageTitle').textContent = post.meta_title || post.title;
      document.getElementById('metaDesc').content = post.meta_description || post.excerpt;
      document.getElementById('ogTitle').content = post.meta_title || post.title;
      document.getElementById('ogDesc').content = post.meta_description || post.excerpt;
      if (post.featured_image_url) {
        document.getElementById('ogImage').content = post.featured_image_url;
      }

      // Render post
      document.getElementById('postContent').innerHTML = `
        <span class="category">${post.category_name}</span>
        <h1>${post.title}</h1>
        <div class="post-meta">
          <span>By ${post.author_name || 'Bardiya Eco Team'}</span>
          <time>${new Date(post.published_at).toLocaleDateString('en-US', {
            year: 'numeric', month: 'long', day: 'numeric'
          })}</time>
        </div>
        ${post.featured_image_url
          ? `<img src="${post.featured_image_url}" alt="${post.featured_image_alt || post.title}" class="featured-image">`
          : ''}
        <article>${post.content}</article>
      `;

      // Related posts
      if (post.related_posts && post.related_posts.length > 0) {
        document.getElementById('relatedPosts').innerHTML = `
          <h2>Related Posts</h2>
          <div class="related-grid">
            ${post.related_posts.map(r => `
              <a href="/blog/${r.slug}" class="related-card">
                ${r.featured_image_url ? `<img src="${r.featured_image_url}" alt="${r.title}">` : ''}
                <h3>${r.title}</h3>
                <p>${r.excerpt || ''}</p>
              </a>
            `).join('')}
          </div>
        `;
      }
    }

    loadPost();
  </script>
</body>
</html>
```

---

## 🌍 URL Structure

| Page | URL | API Call |
|------|-----|----------|
| Blog listing | `/blog` | `GET /api/blog-posts/list?page=1&per_page=6` |
| Blog listing (page 2) | `/blog?page=2` | `GET /api/blog-posts/list?page=2&per_page=6` |
| Blog by category | `/blog?category=1` | `GET /api/blog-posts/list?category_id=1` |
| Single blog post | `/blog/{slug}` | `GET /api/blog-posts/get?slug={slug}` |
| Homepage featured | — | `GET /api/blog-posts/list?is_featured=1&per_page=3` |

---

## ⚠️ Error Handling

All endpoints return this format on error:

```json
{
  "status": "error",
  "message": "Blog post not found."
}
```

| HTTP Code | When |
|-----------|------|
| `200` | Success |
| `400` | Missing or invalid `slug` parameter |
| `404` | Post or category not found |
| `405` | Wrong HTTP method (e.g., POST instead of GET) |
| `500` | Server error |

---

**API Version**: 1.1  
**Last Updated**: July 2026  
**Base URL**: `https://api.bardiaecofriendlyhomestay.com`
