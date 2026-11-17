# Walkwizus_MeilisearchMerchandising

- Provides category merchandising rule builder and product positioning tools.
- Provides facet merchandising configuration UI.
- Adds storage for merchandising rules and facet settings.

**Product positioning**
- Drag and drop sets the manual order of the category. It applies to the `Position` sort only: manually placed products come first, then the native Magento order, then buried products.
- *Reset Product Order* removes the positions, pinned and buried products of the category for the current store view: the `Position` sort goes back to the native Magento order.
- After upgrading from a version without `merch_rank_category_*` fields, run a full `catalogsearch_fulltext` reindex. Until then, the `Position` sort keeps the previous order and new drag and drop changes are not visible on the storefront.
- Pinned products stay at their slot. Without search rules, they only hold with the `Position` sort.
- With Meilisearch dynamic search rules, pinned products also hold with every other sort (price, name...) and on filtered pages. Requirements:
  - Meilisearch `>= 1.54` (the rule format changed in 1.54);
  - the `dynamicSearchRules` experimental feature, enabled from the *Enable search rules* button in *Marketing > Category Merchandising*, never silently;
  - on Meilisearch Cloud, dynamic search rules beyond the free quota are a paid add-on.
- One rule per category and store view, `{index}-category-{id}`. Rules only apply without a search query. The database stays the source of truth: rebuild the rules with `bin/magento meilisearch:merchandising:sync-rules` after switching Meilisearch servers.

**Plug In**
- Add attributes to the category merchandising rule builder by registering filterable attributes under `catalog_product` and context `category_merchandising` in `Walkwizus\MeilisearchBase\Model\AttributeProvider` (see `src/module-meilisearch-merchandising/etc/di.xml`).
- Add or override attribute resolvers via the `Walkwizus\MeilisearchBase\Model\AttributeResolver` DI `resolvers` array.
- Use `Walkwizus\MeilisearchMerchandising\Api\CategoryRepositoryInterface` and `Walkwizus\MeilisearchMerchandising\Service\CategoryProductArrangement` to customize storage and ordering behavior.
