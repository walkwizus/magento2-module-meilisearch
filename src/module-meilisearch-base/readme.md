# Walkwizus_MeilisearchBase

Core integration layer between Magento and Meilisearch. Registers Meilisearch as a Magento search engine, provides service wrappers for the Meilisearch PHP SDK, and exposes shared interfaces used by other modules.

**DI Pools**
- Implement `Walkwizus\MeilisearchBase\Api\AttributeProviderInterface` and register it in the `providers` argument of `Walkwizus\MeilisearchBase\Model\AttributeProvider` in DI.
- Implement `Walkwizus\MeilisearchBase\Api\AttributeMapperInterface` and register it in the `mappers` argument of `Walkwizus\MeilisearchBase\Model\AttributeMapper` in DI.
- Implement `Walkwizus\MeilisearchBase\Api\AttributeResolverInterface` and register it in the `resolvers` argument of `Walkwizus\MeilisearchBase\Model\AttributeResolver` in DI.
- Use services in `src/module-meilisearch-base/Service/` to programmatically manage indexes and documents.
- CLI command `meilisearch:generate:master-key` generates and optionally stores a master key.
- CLI command `meilisearch:keys` lists Meilisearch API keys and optionally stores them.

**Config**
- Admin path: Stores > Configuration > Meilisearch > Server Settings.
- `meilisearch_server/settings/address`.
- `meilisearch_server/settings/master_key` (encrypted).
- `meilisearch_server/settings/api_key` (encrypted).
- `meilisearch_server/settings/client_address`.
- `meilisearch_server/settings/client_api_key`.
- `meilisearch_server/settings/indexes_prefix`.
