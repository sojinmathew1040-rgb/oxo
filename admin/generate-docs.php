<?php
/**
 * Dynamic System Documentation, Flowchart & User Manual Generator for OXO Furniture (Admin Tool)
 * Supports dual documentation suites:
 * 1. Developer Documentation (?type=developer) - Code architecture, Mermaid diagrams, DB schemas, module structures
 * 2. Admin User Guide (?type=admin) - High-level statistics, catalog management manual, brand logistics, price analytics, DB backup/import operations
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';

function auto_sync_documentation($output_to_browser = false, $doc_type = null) {
    if ($doc_type === null) {
        $doc_type = isset($_GET['type']) && $_GET['type'] === 'admin' ? 'admin' : 'developer';
    }

    $db = get_db_connection();

    // Live system stats
    $stats = [
        'total_products' => 0,
        'total_brands' => 0,
        'total_categories' => 0,
        'total_materials' => 0,
        'total_colors' => 0,
        'total_inquiries' => 0,
        'pending_inquiries' => 0,
        'total_admins' => 1,
        'total_users' => 0,
        'min_price' => 0,
        'max_price' => 0,
        'avg_price' => 0,
        'tables' => []
    ];

    if ($db) {
        try {
            $stats['total_products'] = (int)$db->query("SELECT COUNT(*) FROM `oxo_products`")->fetchColumn();
            $stats['total_brands'] = (int)$db->query("SELECT COUNT(*) FROM `oxo_brands`")->fetchColumn();
            $stats['total_categories'] = (int)$db->query("SELECT COUNT(*) FROM `oxo_categories`")->fetchColumn();
            $stats['total_materials'] = (int)$db->query("SELECT COUNT(*) FROM `oxo_materials`")->fetchColumn();
            $stats['total_colors'] = (int)$db->query("SELECT COUNT(*) FROM `oxo_colors`")->fetchColumn();
            $stats['total_inquiries'] = (int)$db->query("SELECT COUNT(*) FROM `oxo_consultations`")->fetchColumn();
            $stats['pending_inquiries'] = (int)$db->query("SELECT COUNT(*) FROM `oxo_consultations` WHERE `status` = 'Pending'")->fetchColumn();
            $stats['total_admins'] = (int)$db->query("SELECT COUNT(*) FROM `oxo_admins`")->fetchColumn();
            try {
                $stats['total_users'] = (int)$db->query("SELECT COUNT(*) FROM `oxo_users`")->fetchColumn();
            } catch (\Exception $e) {
                $stats['total_users'] = 0;
            }

            if ($stats['total_products'] > 0) {
                $stats['min_price'] = (int)$db->query("SELECT MIN(price) FROM `oxo_products`")->fetchColumn();
                $stats['max_price'] = (int)$db->query("SELECT MAX(price) FROM `oxo_products`")->fetchColumn();
                $stats['avg_price'] = (int)$db->query("SELECT AVG(price) FROM `oxo_products`")->fetchColumn();
            }

            $tbl_stmt = $db->query("SHOW TABLES");
            while ($row = $tbl_stmt->fetch(PDO::FETCH_NUM)) {
                $tname = $row[0];
                $rcount = (int)$db->query("SELECT COUNT(*) FROM `{$tname}`")->fetchColumn();
                $stats['tables'][] = ['name' => $tname, 'rows' => $rcount];
            }
        } catch (\Exception $e) {
            error_log("Doc generator stat fetch error: " . $e->getMessage());
        }
    }

    $generated_time = date('F j, Y — g:i A');

    // Generate README.md for codebase root
    $readme_md = <<<MD
# 💎 OXO Control Studio — Luxury Furniture E-Commerce Engine

> **Live Technical Documentation & System Architecture Guide**  
> *Last Updated: {$generated_time}*

---

## 📊 Live System Statistics
- **Total Catalog Products:** `{$stats['total_products']}`
- **Partner Brands:** `{$stats['total_brands']}`
- **Categories:** `{$stats['total_categories']}`
- **Materials:** `{$stats['total_materials']}`
- **Color Palettes:** `{$stats['total_colors']}`
- **Client Inquiries:** `{$stats['total_inquiries']}`

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

MD;

    file_put_contents(__DIR__ . '/../README.md', $readme_md);

    // CSS Styling shared across both docs
    $shared_css = <<<CSS
        :root {
            --primary: #0A2E24;
            --primary-light: #164638;
            --accent: #D4AF37;
            --accent-glow: #F3E5AB;
            --secondary: #2C3E50;
            --gradient-1: linear-gradient(135deg, #0A2E24 0%, #164638 50%, #225C4B 100%);
            --gradient-gold: linear-gradient(135deg, #BF8F54 0%, #D4AF37 50%, #F3E5AB 100%);
            --gradient-purple: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
            --gradient-blue: linear-gradient(135deg, #0984e3 0%, #74b9ff 100%);
            --gradient-emerald: linear-gradient(135deg, #00b894 0%, #55efc4 100%);
            --gradient-orange: linear-gradient(135deg, #e17055 0%, #fab1a0 100%);
            --bg-dark: #071712;
            --bg-card: #0E251E;
            --text-light: #F4F6F5;
            --text-muted: #A0B2AC;
            --border-color: rgba(212, 175, 55, 0.25);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-light);
            line-height: 1.6;
            padding: 30px 20px;
        }

        .nav-switch-bar {
            max-width: 1240px;
            margin: 0 auto 25px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(14, 37, 30, 0.9);
            border: 1px solid var(--border-color);
            padding: 12px 20px;
            border-radius: 16px;
            backdrop-filter: blur(10px);
        }

        .switch-tabs {
            display: flex;
            gap: 10px;
        }

        .switch-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 0.9rem;
            font-weight: 700;
            text-decoration: none;
            color: var(--text-muted);
            background: rgba(255,255,255,0.05);
            transition: all 0.25s ease;
            border: 1px solid transparent;
        }

        .switch-btn:hover {
            color: #fff;
            background: rgba(255,255,255,0.1);
        }

        .switch-btn.active {
            background: var(--gradient-gold);
            color: #000;
            border-color: var(--accent);
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
        }

        .doc-container {
            max-width: 1240px;
            margin: 0 auto;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }

        .doc-header {
            background: var(--gradient-1);
            padding: 50px 40px;
            border-bottom: 2px solid var(--accent);
            position: relative;
        }

        .doc-header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .brand-badge {
            font-family: 'Syne', sans-serif;
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--accent);
            letter-spacing: 3px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .type-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .type-badge.dev { background: rgba(52, 152, 219, 0.2); color: #3498db; border: 1px solid #3498db; }
        .type-badge.admin { background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid #2ecc71; }

        .print-btn {
            background: var(--gradient-gold);
            color: #000;
            border: none;
            padding: 12px 24px;
            border-radius: 30px;
            font-weight: 800;
            font-size: 0.85rem;
            letter-spacing: 1px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 8px 18px rgba(212, 175, 55, 0.3);
            transition: all 0.3s ease;
        }
        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 22px rgba(212, 175, 55, 0.4);
        }

        .doc-title {
            font-family: 'Syne', sans-serif;
            font-size: 2.6rem;
            font-weight: 800;
            margin-bottom: 10px;
            background: linear-gradient(to right, #FFFFFF, var(--accent-glow));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .doc-subtitle {
            color: var(--text-muted);
            font-size: 1.05rem;
            font-weight: 500;
        }

        .doc-body { padding: 45px 40px; }

        .section-block { margin-bottom: 45px; }

        .section-title {
            font-family: 'Syne', sans-serif;
            font-size: 1.7rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
            padding-bottom: 10px;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(175px, 1fr));
            gap: 16px;
            margin-bottom: 35px;
        }

        .metric-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 20px 12px;
            text-align: center;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 110px;
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 4px;
        }
        .metric-card.c-gold::before { background: var(--gradient-gold); }
        .metric-card.c-purple::before { background: var(--gradient-purple); }
        .metric-card.c-blue::before { background: var(--gradient-blue); }
        .metric-card.c-emerald::before { background: var(--gradient-emerald); }
        .metric-card.c-orange::before { background: var(--gradient-orange); }

        .metric-val {
            font-family: 'Syne', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: #FFF;
            margin-bottom: 4px;
            white-space: nowrap;
        }

        .metric-val.price-val {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #FFFFFF;
        }

        .metric-lbl {
            font-size: 0.78rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 600;
        }

        .flowchart-box {
            background: rgba(0, 0, 0, 0.35);
            border: 1.5px solid var(--border-color);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 35px;
            overflow-x: auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 22px;
        }

        .feature-box {
            background: rgba(22, 70, 56, 0.4);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 18px;
            padding: 25px;
        }

        .feature-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: var(--gradient-gold);
            color: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 16px;
        }

        .feature-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #FFF;
            margin-bottom: 10px;
        }

        .feature-desc {
            font-size: 0.9rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .guide-step-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(212, 175, 55, 0.15);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
        }

        .step-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(212, 175, 55, 0.15);
            color: var(--accent);
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            overflow: hidden;
        }

        th, td {
            padding: 14px 18px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.9rem;
        }

        th {
            background: rgba(10, 46, 36, 0.8);
            color: var(--accent);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
        }

        tr:hover td { background: rgba(255, 255, 255, 0.02); }

        code {
            font-family: 'JetBrains Mono', monospace;
            background: rgba(212, 175, 55, 0.15);
            color: var(--accent-glow);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        .doc-footer {
            background: #05120E;
            padding: 30px 40px;
            border-top: 1px solid rgba(212, 175, 55, 0.15);
            text-align: center;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        @media print {
            body { background: #fff; color: #000; padding: 0; }
            .nav-switch-bar, .print-btn { display: none !important; }
            .doc-container { border: none; box-shadow: none; max-width: 100%; background: #fff; }
            .doc-header { background: #0A2E24 !important; color: #fff !important; }
            .doc-title { -webkit-text-fill-color: #fff !important; }
            .flowchart-box, .metric-card, .feature-box, .guide-step-card { background: #f8f9fa !important; border: 1px solid #ddd !important; color: #000 !important; }
            .metric-val, .feature-title { color: #000 !important; }
            .feature-desc, .metric-lbl { color: #555 !important; }
            th { background: #0A2E24 !important; color: #D4AF37 !important; }
            td { color: #222 !important; border-bottom: 1px solid #eee !important; }
            code { background: #eee !important; color: #111 !important; border: 1px solid #ccc; }
        }
CSS;

    // Build HTML for Developer Docs
    $dev_html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OXO Furniture — Developer Technical Architecture</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Syne:wght@700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
    <script>mermaid.initialize({startOnLoad:true, theme: 'dark'});</script>
    <style>{$shared_css}</style>
</head>
<body>

    <div class="nav-switch-bar">
        <div class="switch-tabs">
            <a href="generate-docs.php?type=developer" class="switch-btn active">
                <i class="fa-solid fa-code"></i> Developer Technical Architecture
            </a>
            <a href="generate-docs.php?type=admin" class="switch-btn">
                <i class="fa-solid fa-user-gear"></i> Admin Operational User Guide
            </a>
        </div>
        <button onclick="window.print()" class="print-btn">
            <i class="fa-solid fa-file-pdf"></i> Save / Print PDF
        </button>
    </div>

    <div class="doc-container">
        <div class="doc-header">
            <div class="doc-header-top">
                <div class="brand-badge"><i class="fa-solid fa-gem"></i> OXO CONTROL STUDIO</div>
                <span class="type-badge dev"><i class="fa-solid fa-code"></i> Developer Specification</span>
            </div>
            <h1 class="doc-title">Technical Architecture & Developer Guide</h1>
            <p class="doc-subtitle">Codebase Execution Cascades, Mermaid Data Flow Diagrams, Schema References & API Structures</p>
        </div>

        <div class="doc-body">
            <!-- SECTION 1: LIVE SYSTEM METRICS -->
            <div class="section-block">
                <h3 class="section-title"><i class="fa-solid fa-chart-pie"></i> System Database Metrics</h3>
                <div class="metrics-grid">
                    <div class="metric-card c-gold">
                        <div class="metric-val">{$stats['total_products']}</div>
                        <div class="metric-lbl">Catalog Products</div>
                    </div>
                    <div class="metric-card c-purple">
                        <div class="metric-val">{$stats['total_brands']}</div>
                        <div class="metric-lbl">Partner Brands</div>
                    </div>
                    <div class="metric-card c-blue">
                        <div class="metric-val">{$stats['total_categories']}</div>
                        <div class="metric-lbl">Categories</div>
                    </div>
                    <div class="metric-card c-emerald">
                        <div class="metric-val">{$stats['total_materials']}</div>
                        <div class="metric-lbl">Materials</div>
                    </div>
                    <div class="metric-card c-orange">
                        <div class="metric-val">{$stats['total_colors']}</div>
                        <div class="metric-lbl">Color Palettes</div>
                    </div>
                    <div class="metric-card c-gold">
                        <div class="metric-val">{$stats['total_inquiries']}</div>
                        <div class="metric-lbl">Consultations</div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: ENHANCED PHP CODE EXECUTION FLOWCHART -->
            <div class="section-block">
                <h3 class="section-title"><i class="fa-solid fa-diagram-project"></i> Code Architecture & Execution Flowchart</h3>
                <div class="flowchart-box">
                    <pre class="mermaid">
flowchart TD
    classDef client fill:#2C3E50,stroke:#0984e3,stroke-width:2px,color:#FFF;
    classDef admin fill:#0A2E24,stroke:#D4AF37,stroke-width:2px,color:#FFF;
    classDef core fill:#164638,stroke:#00b894,stroke-width:1px,color:#FFF;
    classDef db fill:#071712,stroke:#D4AF37,stroke-width:2px,color:#D4AF37;

    subgraph StorefrontLayer [Client Storefront Execution Layer]
        U1["index.php (Home Landing)"] --> C_Hero["components/hero.php"]
        U1 --> C_Land["components/products-landing.php"]
        U1 --> C_Brand["components/brands-marquee.php"]
        
        U2["shop.php (Catalog & Multi-Filter)"] -->|SQL Category/Brand/Color Filter| DB_Helper["includes/db.php Helper"]
        U2 -->|Color Swatch Click| Swapper["Dynamic Product Image Swapper"]

        U3["product.php (Item Detail & AR)"] -->|Product ID Lookup| DB_Helper
        U3 --> Blueprint["2D Scale Blueprint Graph Rendering"]
        U3 --> AR_Cam["Mobile Camera AR Room Sandbox"]
        U3 -->|Form Submit| SubInq["submit-inquiry.php"]

        U4["login.php & account.php"] --> Auth_Ajax["ajax/auth_action.php"]
    end

    subgraph AdminLayer [Admin Control Studio Execution Layer]
        A_Auth["admin/auth.php Guard & admin/login.php"] -->|Validate Session| AdminDash["admin/index.php (Master Dashboard)"]

        AdminDash -->|Tab: Product Editor| ProdEd["admin/product-editor.php"]
        AdminDash -->|Tab: Universal Importer| UnivImp["admin/import-universal.php"]
        AdminDash -->|Action: Export SQL| ExpDB["admin/export-db.php"]
        AdminDash -->|Action: Import SQL| ImpDB["admin/import-db.php"]
        AdminDash -->|Action: Live Price Sync| SyncPrice["admin/sync-prices.php"]
        AdminDash -->|Action: Inquiry Status| UpInq["admin/update-inquiry-status.php"]
        AdminDash -->|Action: Docs Generator| GenDocs["admin/generate-docs.php"]
    end

    subgraph EngineLayer [Core PHP Database Engine & Utilities]
        UnivImp -->|PHP GD Engine| GD_Eng["assets/images/uploads/ Compression"]
        DB_Helper -->|PDO Connection & Migrations| MySQL[("MySQL Database oxo_db")]
        
        ProdEd & UnivImp & ImpDB & SyncPrice & SubInq & Auth_Ajax --> DB_Helper
        GenDocs -->|Auto Sync Docs| DocsOut["docs/ & README.md"]
    end

    class U1,U2,U3,U4,C_Hero,C_Land,C_Brand,Swapper,Blueprint,AR_Cam client;
    class AdminDash,ProdEd,UnivImp,ExpDB,ImpDB,SyncPrice,UpInq,GenDocs admin;
    class SubInq,Auth_Ajax,GD_Eng core;
    class DB_Helper,MySQL,DocsOut db;
                    </pre>
                </div>
            </div>

            <!-- SECTION 3: COMPREHENSIVE PHP SCRIPT DIRECTORY MAP -->
            <div class="section-block">
                <h3 class="section-title"><i class="fa-solid fa-folder-tree"></i> PHP Script & Component Directory Map</h3>
                <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 20px;">
                    Detailed inventory mapping each PHP script in the codebase to its operational responsibility, parameters, database interaction, and output:
                </p>
                <table>
                    <thead>
                        <tr>
                            <th>Script Path</th>
                            <th>Module Category</th>
                            <th>Core Responsibility & Functionality</th>
                            <th>Key Actions / Parameters</th>
                            <th>Database Tables Touched</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>index.php</code></td>
                            <td>Storefront</td>
                            <td>Home luxury landing page; renders hero banner, partner brand marquee, and featured collections.</td>
                            <td>None (Default view)</td>
                            <td><code>oxo_products</code>, <code>oxo_brands</code>, <code>oxo_categories</code></td>
                        </tr>
                        <tr>
                            <td><code>shop.php</code></td>
                            <td>Storefront</td>
                            <td>Catalog browser with multi-criteria filtering (Category, Brand, Material, Color, Price Range) and dynamic image swapper.</td>
                            <td><code>GET: category, brand, material, color, search, price_min, price_max</code></td>
                            <td><code>oxo_products</code>, <code>oxo_categories</code>, <code>oxo_brands</code>, <code>oxo_materials</code>, <code>oxo_colors</code></td>
                        </tr>
                        <tr>
                            <td><code>product.php</code></td>
                            <td>Storefront</td>
                            <td>Product detail showcase featuring 2D scale blueprints, mobile camera AR projection sandbox, and consultation modal form.</td>
                            <td><code>GET: id</code> (Product ID)</td>
                            <td><code>oxo_products</code>, <code>oxo_brands</code>, <code>oxo_colors</code></td>
                        </tr>
                        <tr>
                            <td><code>submit-inquiry.php</code></td>
                            <td>Storefront API</td>
                            <td>Processes consultation inquiry form submissions and sends notifications.</td>
                            <td><code>POST: name, email, whatsapp, product_title, message</code></td>
                            <td><code>oxo_consultations</code></td>
                        </tr>
                        <tr>
                            <td><code>about.php</code></td>
                            <td>Storefront</td>
                            <td>Studio heritage page detailing design philosophy and partner brand portfolio.</td>
                            <td>None</td>
                            <td><code>oxo_brands</code></td>
                        </tr>
                        <tr>
                            <td><code>login.php</code> & <code>account.php</code></td>
                            <td>Client Auth</td>
                            <td>Client authentication portal & profile management dashboard with Google OAuth integration.</td>
                            <td><code>GET: action</code>, <code>POST: email, password</code></td>
                            <td><code>oxo_users</code></td>
                        </tr>
                        <tr>
                            <td><code>ajax/auth_action.php</code></td>
                            <td>Client API</td>
                            <td>AJAX endpoint for user registration, password verification, and profile updates.</td>
                            <td><code>POST: action=login|register|update_profile</code></td>
                            <td><code>oxo_users</code></td>
                        </tr>
                        <tr>
                            <td><code>admin/index.php</code></td>
                            <td>Admin Studio</td>
                            <td>Master tabbed control center for catalog inventory, analytics, collections, and security.</td>
                            <td><code>GET: tab=products|analytics|collections|settings</code></td>
                            <td>All Database Tables</td>
                        </tr>
                        <tr>
                            <td><code>admin/auth.php</code></td>
                            <td>Admin Auth</td>
                            <td>Authentication guard script; verifies admin session state before executing admin commands.</td>
                            <td><code>require_admin_login()</code></td>
                            <td><code>\$_SESSION['admin_logged_in']</code></td>
                        </tr>
                        <tr>
                            <td><code>admin/import-universal.php</code></td>
                            <td>Admin Tool</td>
                            <td>4-Tier URL scraper extracting product specs, batch showcase galleries, and compressing images via PHP GD library.</td>
                            <td><code>POST: product_url, action=extract_url|batch_confirm</code></td>
                            <td><code>oxo_products</code>, <code>oxo_brands</code>, <code>oxo_categories</code>, <code>oxo_materials</code>, <code>oxo_colors</code></td>
                        </tr>
                        <tr>
                            <td><code>admin/product-editor.php</code></td>
                            <td>Admin Tool</td>
                            <td>Manual CRUD interface for creating new furniture designs, updating dimensions, and deleting catalog items.</td>
                            <td><code>GET: id</code>, <code>POST: form_action=add|edit|delete</code></td>
                            <td><code>oxo_products</code>, <code>oxo_brands</code>, <code>oxo_categories</code>, <code>oxo_materials</code>, <code>oxo_colors</code></td>
                        </tr>
                        <tr>
                            <td><code>admin/export-db.php</code></td>
                            <td>Admin Tool</td>
                            <td>Database exporter generating a complete SQL schema/data dump and streaming direct <code>oxo_db.sql</code> browser download.</td>
                            <td>None (Direct GET trigger)</td>
                            <td>All Database Tables (SHOW TABLES)</td>
                        </tr>
                        <tr>
                            <td><code>admin/import-db.php</code></td>
                            <td>Admin Tool</td>
                            <td>Database restoration tool processing uploaded <code>.sql</code> files to execute schema updates.</td>
                            <td><code>POST: sql_file</code> (Multipart upload)</td>
                            <td>All Database Tables</td>
                        </tr>
                        <tr>
                            <td><code>admin/sync-prices.php</code></td>
                            <td>Admin Tool</td>
                            <td>Live price sync engine re-scraping external <code>source_url</code> locations and updating modified catalog prices.</td>
                            <td><code>GET: action=sync_all</code> or <code>GET: id=product_id</code></td>
                            <td><code>oxo_products</code></td>
                        </tr>
                        <tr>
                            <td><code>admin/update-inquiry-status.php</code></td>
                            <td>Admin Tool</td>
                            <td>Updates client consultation inquiry states (<code>Pending</code>, <code>Contacted</code>, <code>Completed</code>).</td>
                            <td><code>GET: inquiry_id, status</code></td>
                            <td><code>oxo_consultations</code></td>
                        </tr>
                        <tr>
                            <td><code>admin/generate-docs.php</code></td>
                            <td>Admin Tool</td>
                            <td>Master documentation suite builder generating <code>README.md</code>, developer architecture specs, and admin user manuals.</td>
                            <td><code>GET: type=developer|admin</code></td>
                            <td>All Database Tables (SHOW TABLES)</td>
                        </tr>
                        <tr>
                            <td><code>includes/db.php</code></td>
                            <td>Core Helper</td>
                            <td>PDO connection helper, database auto-creation (<code>oxo_db</code>), table initialization, and column migrations.</td>
                            <td><code>get_db_connection()</code></td>
                            <td>All Database Tables</td>
                        </tr>
                        <tr>
                            <td><code>includes/products-db.php</code></td>
                            <td>Core Helper</td>
                            <td>Data querying functions and static product array fallback data.</td>
                            <td><code>\$PRODUCTS_DB</code></td>
                            <td><code>oxo_products</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- SECTION 4: DATABASE TABLES SCHEMA SUMMARY -->
            <div class="section-block">
                <h3 class="section-title"><i class="fa-solid fa-table"></i> MySQL Database Schema Summary</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Table Name</th>
                            <th>Purpose / Primary Function</th>
                            <th>Total Rows</th>
                            <th>Key Columns</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>oxo_products</code></td>
                            <td>Main furniture catalog database</td>
                            <td>{$stats['total_products']}</td>
                            <td><code>id, title, price, category, image, material_slug, brand_id, height_cm, width_cm, length_cm, source_url, details</code></td>
                        </tr>
                        <tr>
                            <td><code>oxo_brands</code></td>
                            <td>Partner brands & logo paths</td>
                            <td>{$stats['total_brands']}</td>
                            <td><code>id, name, logo_path</code></td>
                        </tr>
                        <tr>
                            <td><code>oxo_categories</code></td>
                            <td>Product furniture categories</td>
                            <td>{$stats['total_categories']}</td>
                            <td><code>id, slug, name, bg_color</code></td>
                        </tr>
                        <tr>
                            <td><code>oxo_materials</code></td>
                            <td>Furniture construction materials</td>
                            <td>{$stats['total_materials']}</td>
                            <td><code>id, slug, name</code></td>
                        </tr>
                        <tr>
                            <td><code>oxo_colors</code></td>
                            <td>Color options & hex codes</td>
                            <td>{$stats['total_colors']}</td>
                            <td><code>id, name, hex</code></td>
                        </tr>
                        <tr>
                            <td><code>oxo_consultations</code></td>
                            <td>Client design consultation inquiries</td>
                            <td>{$stats['total_inquiries']}</td>
                            <td><code>id, name, email, whatsapp, message, product_title, status, created_at</code></td>
                        </tr>
                        <tr>
                            <td><code>oxo_admins</code></td>
                            <td>Admin user accounts & credentials</td>
                            <td>{$stats['total_admins']}</td>
                            <td><code>id, username, password, whatsapp</code></td>
                        </tr>
                        <tr>
                            <td><code>oxo_users</code></td>
                            <td>Client user accounts</td>
                            <td>{$stats['total_users']}</td>
                            <td><code>id, name, email, password, phone, address</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="doc-footer">
            <p>OXO Furniture Studio — Developer Technical Architecture Suite</p>
            <p style="margin-top: 5px; opacity: 0.7;">Auto-generated via <code>admin/generate-docs.php</code> — {$generated_time}</p>
        </div>
    </div>
</body>
</html>
HTML;

    // Build HTML for Admin User Guide
    $formatted_min = number_format($stats['min_price']);
    $formatted_max = number_format($stats['max_price']);
    $formatted_avg = number_format($stats['avg_price']);

    $admin_html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OXO Furniture — Admin Operational User Guide</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Syne:wght@700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>{$shared_css}</style>
</head>
<body>

    <div class="nav-switch-bar">
        <div class="switch-tabs">
            <a href="generate-docs.php?type=developer" class="switch-btn">
                <i class="fa-solid fa-code"></i> Developer Technical Architecture
            </a>
            <a href="generate-docs.php?type=admin" class="switch-btn active">
                <i class="fa-solid fa-user-gear"></i> Admin Operational User Guide
            </a>
        </div>
        <button onclick="window.print()" class="print-btn">
            <i class="fa-solid fa-file-pdf"></i> Save / Print PDF
        </button>
    </div>

    <div class="doc-container">
        <div class="doc-header">
            <div class="doc-header-top">
                <div class="brand-badge"><i class="fa-solid fa-gem"></i> OXO CONTROL STUDIO</div>
                <span class="type-badge admin"><i class="fa-solid fa-user-gear"></i> Admin User Guide</span>
            </div>
            <h1 class="doc-title">Admin Operational User Manual</h1>
            <p class="doc-subtitle">Studio Management, Inventory Logistics, Business Statistics & Database Control Operations</p>
        </div>

        <div class="doc-body">
            <!-- SECTION 1: BUSINESS & CATALOG METRICS SUMMARY -->
            <div class="section-block">
                <h3 class="section-title"><i class="fa-solid fa-chart-line"></i> Business & Catalog Overview</h3>
                <div class="metrics-grid">
                    <div class="metric-card c-gold">
                        <div class="metric-val">{$stats['total_products']}</div>
                        <div class="metric-lbl">Total Creations</div>
                    </div>
                    <div class="metric-card c-purple">
                        <div class="metric-val">{$stats['total_brands']}</div>
                        <div class="metric-lbl">Partner Brands</div>
                    </div>
                    <div class="metric-card c-emerald">
                        <div class="metric-val">{$stats['total_inquiries']}</div>
                        <div class="metric-lbl">Total Inquiries</div>
                    </div>
                    <div class="metric-card c-orange">
                        <div class="metric-val">{$stats['pending_inquiries']}</div>
                        <div class="metric-lbl">Pending Quotes</div>
                    </div>
                    <div class="metric-card c-blue">
                        <div class="metric-val price-val">₹{$formatted_min}</div>
                        <div class="metric-lbl">Min Product Price</div>
                    </div>
                    <div class="metric-card c-gold">
                        <div class="metric-val price-val">₹{$formatted_max}</div>
                        <div class="metric-lbl">Max Product Price</div>
                    </div>
                </div>

                <div style="background: rgba(212, 175, 55, 0.08); border: 1px solid rgba(212, 175, 55, 0.25); padding: 18px 24px; border-radius: 16px; margin-top: 15px;">
                    <p style="font-size: 0.95rem; color: #FFF; display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-sack-dollar" style="color: var(--accent); font-size: 1.2rem;"></i>
                        <strong>Average Product Catalog Price:</strong> ₹{$formatted_avg} across {$stats['total_products']} active luxury items.
                    </p>
                </div>
            </div>

            <!-- SECTION 2: STUDIO OPERATIONS MANUAL -->
            <div class="section-block">
                <h3 class="section-title"><i class="fa-solid fa-book-open"></i> Studio Operations Manual</h3>

                <!-- Step 1: Product Management -->
                <div class="guide-step-card">
                    <div class="step-badge"><i class="fa-solid fa-couch"></i> 1. Product Catalog Management</div>
                    <h4 style="font-size: 1.15rem; color: #FFF; margin-bottom: 8px;">Adding & Editing Furniture Designs</h4>
                    <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 12px;">
                        Admins can manage the entire luxury catalog directly from the <strong>Products Tab</strong> or via the <strong>Add New Creation</strong> button:
                    </p>
                    <ul style="color: var(--text-muted); font-size: 0.9rem; margin-left: 20px; line-height: 1.6;">
                        <li><strong>Add New Creation:</strong> Click <em>Add New Creation</em>, fill in product title, category, price, dimensions (Height, Width, Depth/Length in cm), description, specs, materials, and upload high-res showcase photos.</li>
                        <li><strong>Universal Importer:</strong> Click <em>Universal Importer</em>, paste any furniture URL or showcase category link to extract product data and photos automatically.</li>
                        <li><strong>Editing Creations:</strong> Click the edit icon on any product card in the control center to adjust pricing, update gallery photos, or modify scale graph dimensions.</li>
                        <li><strong>Deleting Items:</strong> Click delete to remove obsolete inventory from the store.</li>
                    </ul>
                </div>

                <!-- Step 2: Brand & Collection Management -->
                <div class="guide-step-card">
                    <div class="step-badge"><i class="fa-solid fa-shapes"></i> 2. Brands & Collections Management</div>
                    <h4 style="font-size: 1.15rem; color: #FFF; margin-bottom: 8px;">Partner Brands, Categories & Swatches</h4>
                    <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 12px;">
                        Control how partner brand logos display in the homepage sliding marquee and map custom swatches:
                    </p>
                    <ul style="color: var(--text-muted); font-size: 0.9rem; margin-left: 20px; line-height: 1.6;">
                        <li><strong>Brands:</strong> Add partner studio brand names and upload SVG/PNG logos. Logos display smoothly in the storefront marquee.</li>
                        <li><strong>Categories:</strong> Add or edit furniture category titles (Sofas, Chairs, Tables, Lighting, Storage, Beds) and assign background accent colors.</li>
                        <li><strong>Materials & Color Palettes:</strong> Register new construction materials (Solid Wood, Brushed Metal, Fabric) and custom color swatches with HEX color pickers.</li>
                    </ul>
                </div>

                <!-- Step 3: Inquiries & WhatsApp -->
                <div class="guide-step-card">
                    <div class="step-badge"><i class="fa-solid fa-comments"></i> 3. Consultation Requests & WhatsApp Integration</div>
                    <h4 style="font-size: 1.15rem; color: #FFF; margin-bottom: 8px;">Managing Client Consultations</h4>
                    <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 12px;">
                        Access client inquiries under <strong>Analytics & Inquiries</strong> tab:
                    </p>
                    <ul style="color: var(--text-muted); font-size: 0.9rem; margin-left: 20px; line-height: 1.6;">
                        <li><strong>View Inquiries:</strong> Review consultation requests submitted by clients from product pages or homepage.</li>
                        <li><strong>Status Update:</strong> Toggle inquiry state between <code>Pending</code>, <code>Contacted</code>, and <code>Completed</code>.</li>
                        <li><strong>1-Click WhatsApp Chat:</strong> Click the WhatsApp icon next to any inquiry to instantly start a direct WhatsApp conversation pre-filled with client inquiry details.</li>
                    </ul>
                </div>

                <!-- Step 4: Settings & Credentials -->
                <div class="guide-step-card">
                    <div class="step-badge"><i class="fa-solid fa-gears"></i> 4. Settings & Security</div>
                    <h4 style="font-size: 1.15rem; color: #FFF; margin-bottom: 8px;">WhatsApp Number & Admin Passwords</h4>
                    <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 12px;">
                        Configure contact channels and security under the <strong>Settings</strong> tab:
                    </p>
                    <ul style="color: var(--text-muted); font-size: 0.9rem; margin-left: 20px; line-height: 1.6;">
                        <li><strong>WhatsApp Contact:</strong> Update the target admin WhatsApp phone number (with country code, e.g. <code>919876543210</code>). All storefront inquiry buttons automatically route to this number.</li>
                        <li><strong>Admin Credentials:</strong> Change current admin password with secure password hashing.</li>
                    </ul>
                </div>

                <!-- Step 5: Database Backup & Restore -->
                <div class="guide-step-card">
                    <div class="step-badge"><i class="fa-solid fa-database"></i> 5. Database Backup & Restoration</div>
                    <h4 style="font-size: 1.15rem; color: #FFF; margin-bottom: 8px;">Exporting & Importing SQL Database Files</h4>
                    <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 12px;">
                        Maintain full data security with 1-click database backup and upload restoration:
                    </p>
                    <ul style="color: var(--text-muted); font-size: 0.9rem; margin-left: 20px; line-height: 1.6;">
                        <li><strong>Backup Database:</strong> Click <em>Backup Database</em> in the top header panel or Settings tab. The system compiles all tables into <code>oxo_db.sql</code> and triggers an instant browser download.</li>
                        <li><strong>Import Database:</strong> Click <em>Import Database</em>, select any valid <code>.sql</code> backup file, and submit. The system restores schema tables, seeds missing data, and syncs database contents instantly.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="doc-footer">
            <p>OXO Furniture Studio — Admin Operational User Manual</p>
            <p style="margin-top: 5px; opacity: 0.7;">Auto-generated via <code>admin/generate-docs.php</code> — {$generated_time}</p>
        </div>
    </div>
</body>
</html>
HTML;

    $docs_dir = __DIR__ . '/../docs/';
    if (!file_exists($docs_dir)) {
        mkdir($docs_dir, 0777, true);
    }

    // Save HTML Documentation Suite files
    file_put_contents($docs_dir . 'OXO_Developer_Documentation.html', $dev_html);
    file_put_contents($docs_dir . 'OXO_Admin_User_Guide.html', $admin_html);
    file_put_contents($docs_dir . 'OXO_System_Documentation.html', $dev_html);

    if ($output_to_browser) {
        if ($doc_type === 'admin') {
            echo $admin_html;
        } else {
            echo $dev_html;
        }
    }
}

// Auto-run if accessed directly via URL
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'generate-docs.php') {
    $doc_type = isset($_GET['type']) && $_GET['type'] === 'admin' ? 'admin' : 'developer';
    auto_sync_documentation(true, $doc_type);
}
