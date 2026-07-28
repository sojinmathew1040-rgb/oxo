# 💎 OXO Control Studio — Luxury Furniture E-Commerce Engine

> **Live Technical Documentation & System Architecture Guide**  
> *Last Updated: July 28, 2026 — 8:14 AM*

---

## 📊 Live System Statistics
- **Total Catalog Products:** `13`
- **Partner Brands:** `6`
- **Categories:** `7`
- **Materials:** `7`
- **Color Palettes:** `17`
- **Client Inquiries:** `5`

---

## 🔀 Project Workflow & Execution Flowchart

```mermaid
flowchart TD
    classDef admin fill:#0A2E24,stroke:#D4AF37,stroke-width:2px,color:#FFF;
    classDef process fill:#164638,stroke:#00b894,stroke-width:1px,color:#FFF;
    classDef store fill:#2C3E50,stroke:#0984e3,stroke-width:1px,color:#FFF;
    classDef db fill:#071712,stroke:#D4AF37,stroke-width:2px,color:#D4AF37;

    A[Admin User] -->|Paste URL Link| B(Universal Importer admin/import-universal.php)
    
    subgraph ImportEngine [4-Tier Universal Extraction Cascade]
        B --> Tier1[1. Shopify JSON API /products.json]
        B --> Tier2[2. HTML JSON-LD Microdata]
        B --> Tier3[3. OpenGraph Meta Tags]
        B --> Tier4[4. DOM Scraper / Gallery Showcase Scraper]
    end

    ImportEngine --> C{Brand Exists in DB?}
    C -->|No| D[Auto-Create Brand Record & Download Favicon Logo]
    C -->|Yes| E[Reuse Existing Brand ID - Deduplication]

    ImportEngine --> F{Collection Attributes Missing?}
    F -->|Category / Material / Color Missing| G[Auto-Insert into oxo_categories, oxo_materials, oxo_colors]
    F -->|Attributes Found| H[Map Slugs & Parse Dimensions W x D x H]

    D & E & G & H --> I(PHP GD Image Compressor Engine)
    I -->|Compress 3MB-5MB Raw Photos| J[Lightweight 60KB-150KB Web Format Image]
    
    J --> K[(MySQL Database oxo_db.sql)]

    subgraph Storefront [Storefront Experience & AR Engine]
        K --> L[Shop Catalog shop.php]
        L -->|Color Filter Selected| M[Dynamic Product Card Image Swap]
        L -->|Product Click| N[Product Detail Page product.php]
        N -->|Dimensions Present| O[Render 2D Scale Graph Blueprint]
        N -->|Tap View in Space| P[Mobile Camera AR Projection Overlay]
    end

    subgraph SystemTools [Admin Operations & Backup/Restore Engine]
        A -->|Click Backup Database| R[admin/export-db.php]
        R -->|Download SQL Backup| S[(db/oxo_db.sql)]
        A -->|Upload SQL File| T[admin/import-db.php]
        T -->|Execute & Restore Schema| K
    end

    class A,B admin;
    class Tier1,Tier2,Tier3,Tier4,C,D,E,F,G,H,I,J process;
    class L,M,N,O,P store;
    class K,S db;
```

---

## 🛠️ Core Features & Technical Architecture

### 1. Universal Product Importer (`admin/import-universal.php`)
- **4-Tier Extraction Cascade**: Fallback scraper extracts title, price, description, and high-res images from any furniture site.
- **Showcase Gallery Scrape**: Parses category showcase pages into a **Batch Review Grid**.
- **Auto-Deduplication**: Automatically maps brand domains, reuses existing brand records, and downloads company logos.

### 2. Image Optimization Engine (`compress_and_save_image()`)
- Uses PHP GD library to resize and compress 3MB–5MB raw images down to **60KB – 150KB**.

### 3. Database Backup & Restore (`admin/export-db.php` & `admin/import-db.php`)
- 1-Click database dump downloads `oxo_db.sql` directly.
- SQL Upload Importer validates and restores schema/rows seamlessly.
