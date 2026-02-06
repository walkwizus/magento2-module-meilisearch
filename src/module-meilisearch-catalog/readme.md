# Walkwizus_MeilisearchCatalog

Adds catalog product and category indexing support for Meilisearch, including attribute providers, mappers, and indexer wiring. Registers the `meilisearch_categories_fulltext` indexer and mview subscriptions for category EAV tables.

**Plug In**
- Add custom product attributes by registering new providers in the `providers` argument of `Walkwizus\MeilisearchBase\Model\AttributeProvider` under `catalog_product` and context `index`.
- Add custom document mapping by registering new mappers in the `mappers` argument of `Walkwizus\MeilisearchBase\Model\AttributeMapper` under `catalog_product`.
- If you add new indexers, use `Walkwizus\MeilisearchBase\Model\Indexer\BaseIndexerHandler` similar to the virtual types in `src/module-meilisearch-catalog/etc/di.xml`.

**Config**
- No module-specific system configuration. It relies on server settings from `Walkwizus_MeilisearchBase` and Magento catalog attributes.
