# <p align="center">Meilisearch / Magento 2 (Adobe Commerce)</p>

<p align="center">
    <a href="https://walkwizus.github.io/magento2-module-meilisearch-docs/">
        <img src="https://img.shields.io/badge/docs-available-blue" alt="Documentation">
    </a>
    <a href="https://demo-meilisearch.walkwizus.com/">
        <img src="https://img.shields.io/badge/demo-live-brightgreen" alt="Demo">
    </a>
</p>

<p align="center">
    <img src="docs/assets/logo/walkwizus-logo-light.svg?sanitize=true#gh-light-mode-only" height="40">
    <img src="docs/assets/logo/walkwizus-logo-dark.svg?sanitize=true#gh-dark-mode-only" height="40">
    &nbsp;&nbsp;&nbsp;&nbsp;
    <img src="docs/assets/logo/meilisearch-logo-light.svg?sanitize=true#gh-light-mode-only" height="40">
    <img src="docs/assets/logo/meilisearch-logo-dark.svg?sanitize=true#gh-dark-mode-only" height="40">
</p>

The Meilisearch extension for Magento 2 enables replacing Magento's default search engine (OpenSearch) with Meilisearch.

## 🔎 What is Meilisearch?

Meilisearch is a search engine featuring a blazing fast RESTful search API, typo tolerance, comprehensive language support, and much more.

## ✨ Main Features

### 🗂️ Category Merchandising

The **Category Merchandising** feature allows you to dynamically populate your categories using a rule engine.  
No more manual category management — products are automatically assigned in real time based on the rules you define.

You retain full control with an intuitive **drag-and-drop** interface to reorder products and highlight your **top-performing items**.

<p align="center">
  <img src="docs/assets/merchandising/category-merchandising.png" alt="Facet Merchandising" width="800"/>
</p>

### 🧩 Facet Merchandising

The **Facet Merchandising** feature lets you fully customize the display of layered navigation filters for an optimized user experience.  
You can:

- 🔃 Reorder facets as needed
- 🔍 Enable a search field within facet options
- ➕ Limit the number of visible options with a **“Show more”** toggle

Perfect for keeping navigation **clean and user-friendly**, even in **attribute-rich catalogs**.

<p align="center">
  <img src="docs/assets/merchandising/facet-merchandising.png" alt="Facet Merchandising" width="800"/>
</p>

### 🖥️ Faceted Frontend Experience

The module offers a **fully dynamic and responsive frontend interface** powered by Knockout.js, designed to enhance layered navigation usability and speed.

Key features include:

- 🎚️ **Price slider** with real-time filtering
- ☑️ **Multi-select checkboxes** for flexible attribute filtering
- 🔍 **Search within facet options** to quickly find relevant values
- ⚡ Instant UI updates without page reloads

This modern frontend ensures a smooth and engaging shopping experience, even on large catalogs.

## Prerequisites

* Magento >= 2.4.4
* Meilisearch >= v1.9.0
* PHP >= 8.1

Magento 2 module install

```
composer require walkwizus/magento2-module-meilisearch
bin/magento module:enable Walkwizus_MeilisearchBase Walkwizus_MeilisearchCatalog Walkwizus_MeilisearchFrontend Walkwizus_MeilisearchMerchandising
bin/magento setup:upgrade
```

## Configuration

```
bin/magento config:set meilisearch_server/settings/address meilisearch:7700
bin/magento config:set meilisearch_server/settings/api_key "YOUR_API_KEY"
bin/magento config:set meilisearch_server/settings/client_address localhost:7700
bin/magento config:set meilisearch_server/settings/client_api_key "YOUR_CLIENT_API_KEY"
bin/magento config:set catalog/search/engine meilisearch
```

## Indexing

```
bin/magento indexer:reindex catalogsearch_fulltext
```
