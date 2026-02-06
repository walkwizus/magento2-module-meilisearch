# Walkwizus_MeilisearchBase

Core integration layer between Magento and Meilisearch. Registers Meilisearch as a Magento search engine, provides service wrappers for the Meilisearch PHP SDK, and exposes shared interfaces used by other modules.

**DI Pools**
- Implement `Walkwizus\MeilisearchBase\Api\AttributeProviderInterface` and register it in the `providers` argument of `Walkwizus\MeilisearchBase\Model\AttributeProvider` in DI.
- Implement `Walkwizus\MeilisearchBase\Api\AttributeMapperInterface` and register it in the `mappers` argument of `Walkwizus\MeilisearchBase\Model\AttributeMapper` in DI.
- Implement `Walkwizus\MeilisearchBase\Api\AttributeResolverInterface` and register it in the `resolvers` argument of `Walkwizus\MeilisearchBase\Model\AttributeResolver` in DI.

