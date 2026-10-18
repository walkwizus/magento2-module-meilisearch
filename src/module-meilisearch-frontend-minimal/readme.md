# BA_MeilisearchFrontendMinimal

Minimal server-rendered frontend for Meilisearch result pages.

This module is an example alternative to `Walkwizus_MeilisearchFrontend`. It replaces the default Meilisearch result UI with lightweight `.phtml` templates that keep Magento's usual toolbar, product-list, and layered-navigation CSS hooks, so existing theme styling continues to apply with fewer frontend moving parts.

Ideally, the only data you get on the category page should be from meilisearch, if some data is missing, then add it using the attribute mapper.

**Purpose**
- Show how to swap the standard Meilisearch frontend for a simpler SSR-first implementation.
- Keep result pages close to Magento's native markup and CSS class structure.
- Provide clear extension points for custom facet rendering and result-page content blocks.

**Rendered Features**
- Server-rendered product list using Magento-style `products-grid` / `products-list` markup.
- Server-rendered layered navigation with `details` / `summary` sections.
- Active-filter state block with remove links and a `Clear All` action.
- Mobile filter popup powered by `Magento_Ui` modal.
- Support for checkbox, swatch, and price facets.
- Swatch output that reuses Magento swatch helpers and tooltip behavior.
- Optional GTM compatibility via a plugin on `Magento\GoogleTagManager\Block\ListJson` so Meilisearch hits can still populate product impression data.

**Renderer Pools**
- Facet rendering is delegated through `BA\MeilisearchFrontendMinimal\Model\Layer\FacetRendererPool`.
- Active-filter rendering is delegated through `BA\MeilisearchFrontendMinimal\Model\Layer\StateItemRendererPool`.
- Renderer selection is resolved from a facet's `renderRegion`, then `type`, and falls back to `checkbox`.

**Customization Points**
- Add product-page fragments around each result item through the layout containers declared in `meilisearch_result.xml`.
    - **When extending these, be careful not to use cacheable="false" on your child block, this will remove the varnish caching on the entire page.**
    - `meilisearch.product.above_image`
    - `meilisearch.product.below_image`
    - `meilisearch.product.below_title`
    - `meilisearch.product.below_price`
    - `meilisearch.product.actions`
- Replace or extend facet/state renderers by wiring new implementations into the two renderer pools in `etc/di.xml`.
- Adjust the mobile popup behavior in `view/frontend/web/js/mobile-filter-popup.js`.

**Dependencies**
- `Walkwizus_MeilisearchFrontend`
- `Magento_Catalog`
- `Magento_Search`
- Magento Swatches support is used for swatch facets.

**Notes**
- The module intentionally favors simple links and full-page navigation over the richer JS-driven frontend shipped by the main Meilisearch frontend module.
- `etc/csp_whitelist.xml` rewrites the frontend module CSP entries so the minimal frontend can define its own script/style/connect hosts.
