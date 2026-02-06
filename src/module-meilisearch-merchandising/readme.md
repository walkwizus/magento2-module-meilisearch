# Walkwizus_MeilisearchMerchandising

- Provides category merchandising rule builder and product positioning tools.
- Provides facet merchandising configuration UI.
- Adds storage for merchandising rules and facet settings.

**Plug In**
- Add attributes to the category merchandising rule builder by registering filterable attributes under `catalog_product` and context `category_merchandising` in `Walkwizus\MeilisearchBase\Model\AttributeProvider` (see `src/module-meilisearch-merchandising/etc/di.xml`).
- Add or override attribute resolvers via the `Walkwizus\MeilisearchBase\Model\AttributeResolver` DI `resolvers` array.
- Use `Walkwizus\MeilisearchMerchandising\Api\CategoryRepositoryInterface` and `Walkwizus\MeilisearchMerchandising\Service\SaveProductPosition` to customize storage and ordering behavior.
