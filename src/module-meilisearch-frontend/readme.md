# Walkwizus_MeilisearchFrontend

Frontend UI, JS configuration, and SSR helpers for Meilisearch search results, facets, and autocomplete.

**Purpose**
- Adds layout handles and templates for Meilisearch search result pages.
- Exposes frontend JS config in `view/frontend/templates/config.phtml` consumed by Knockout components.
- Provides SSR helpers and fragment rendering for prices and swatches.
- Adds frontend routes and a CSP whitelist entry for the Meilisearch host.

**DI Pools**
- Add a layout handle by implementing `Walkwizus\MeilisearchFrontend\Api\LayoutHandleInterface` and registering it under `handles` in `Walkwizus\MeilisearchFrontend\Observer\AddMeilisearchHandle` in `src/module-meilisearch-frontend/etc/frontend/di.xml`.
- Add frontend JS config by implementing `Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface` and registering it in `Walkwizus\MeilisearchFrontend\Model\ConfigProvider` in `src/module-meilisearch-frontend/etc/di.xml`.
- Add SSR fragments by implementing `Walkwizus\MeilisearchFrontend\Api\FragmentRendererInterface` and registering it in `Walkwizus\MeilisearchFrontend\Model\FragmentAggregator` in `src/module-meilisearch-frontend/etc/frontend/di.xml`.
- Add attribute resolvers or providers by extending the DI arrays for `Walkwizus\MeilisearchBase\Model\AttributeResolver` and `Walkwizus\MeilisearchBase\Model\AttributeProvider`.

**Config**
- Meilisearch server settings come from `Walkwizus_MeilisearchBase` system config.
Magento catalog config used by the frontend:
- `catalog/frontend/list_mode`.
- `catalog/frontend/grid_per_page_values`.
- `catalog/frontend/grid_per_page`.
- `catalog/frontend/list_per_page_values`.
- `catalog/frontend/list_per_page`.
- `catalog/frontend/list_allow_all`.
- `catalog/frontend/show_swatches_in_product_list`.
- `catalog/seo/product_url_suffix`.
- `catalog/seo/product_use_categories`.
- CSP whitelist in `src/module-meilisearch-frontend/etc/csp_whitelist.xml` defaults to `http://localhost:7700`. Update it to match your Meilisearch host.
