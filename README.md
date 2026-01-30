# ACF Fields Indexer

**Make your Advanced Custom Fields searchable by the native WordPress search engine without performance penalties.**

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![Requires](https://img.shields.io/badge/requires-WordPress%205.0%2B-green.svg)
![PHP](https://img.shields.io/badge/php-7.4%2B-lightgrey.svg)

## 🚀 Overview

By default, WordPress Native Search (`?s=query`) only looks at the `post_title` and `post_content` columns. It completely ignores metadata stored in `wp_postmeta`, which means data stored in **ACF (Advanced Custom Fields)** is not searchable.

Existing solutions usually involve:
1.  **Heavy SQL JOINs:** Killing database performance.
2.  **External Indexes:** Creating bloat and dependency (vendor lock-in).

**ACF Fields Indexer** solves this by injecting selected ACF values directly into the `post_content` inside a hidden HTML block. This makes the content instantly searchable by WordPress, REST API, and WP GraphQL with **O(1) performance**.

---

## ✨ Features

* **Zero Frontend Bloat:** No CSS or JS files added to your site.
* **High Performance:** Uses native WordPress tables. No heavy JOIN queries.
* **Vendor Agnostic:** Works with any theme, page builder (Elementor, Divi, Bricks), or Headless setup.
* **Selective Indexing:** You choose exactly which Post Types and Fields to index.
* **Batch Processing:** Includes a built-in tool to index thousands of existing posts in the background.
* **Clean Data:** Automatically removes old index data when you update settings.

---

## 🛠 Installation

1.  Download the plugin folder `acf-fields-indexer`.
2.  Upload it to your WordPress directory: `/wp-content/plugins/`.
3.  Activate the plugin through the **Plugins** menu in WordPress.

---

## ⚙️ Configuration

Go to **Settings > ACF Indexer**.

### 1. Target Post Types
Define which content types should be monitored. Enter the **slugs** separated by commas.

* *Example:* `post, page, product, obra`

### 2. ACF Fields to Index
Define which ACF field values should be searchable. Enter the **Field Names** (not labels) separated by commas.

* *Example:* `isbn, sku, author_bio, release_year`

### 3. Save Changes
Click **"1. Save Settings"**.

---

## ♻️ Indexing Existing Content (Backfill)

If you have existing content created before installing this plugin, you need to run the **Batch Indexer**.

1.  Go to **Settings > ACF Indexer**.
2.  Scroll down to the **Maintenance** section.
3.  Click **"2. Start Batch Indexing"**.
4.  Wait for the process to finish. The page will reload automatically processing 50 posts at a time.

> **Note:** Whenever you add or remove a field from the configuration, you should run the Batch Indexer to update all posts with the new rules.

---

## 🧠 How it Works (Under the Hood)

When a post is saved (or processed via batch), the plugin:

1.  Retrieves the values from the selected ACF fields.
2.  Sanitizes the data (strips HTML tags).
3.  Appends a hidden Gutenberg HTML block to the end of the `post_content`.

**Example of injected code:**

```html
<div class="afi-search-index" style="display:none" aria-hidden="true">
    978-0-123-45678-9 Blue Hardcover 2024
</div>