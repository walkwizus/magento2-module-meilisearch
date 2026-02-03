<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Api\Data;

interface EmbedderInterface
{
    public const EMBEDDER_ID = 'embedder_id';
    public const NAME = 'name';
    public const IDENTIFIER = 'identifier';
    public const SOURCE = 'source';
    public const MODEL = 'model';
    public const API_KEY = 'api_key';
    public const URL = 'url';
    public const DIMENSIONS = 'dimensions';
    public const DOCUMENT_TEMPLATE = 'document_template';
    public const DOCUMENT_TEMPLATE_MAX_BYTES = 'document_template_max_bytes';
    public const BINARY_QUANTIZED = 'binary_quantized';
    public const REVISION = 'revision';
    public const POOLING = 'pooling';
    public const DISTRIBUTION = 'distribution';
    public const REQUEST_CONFIG = 'request_config';
    public const RESPONSE_CONFIG = 'response_config';
    public const INDEXING_CONFIG = 'indexing_config';
    public const SEARCH_CONFIG = 'search_config';
    public const INDEXING_FRAGMENTS = 'indexing_fragments';
    public const SEARCH_FRAGMENTS = 'search_fragments';

    /**
     * @return mixed
     */
    public function getEmbedderId();

    /**
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * @return string|null
     */
    public function getIdentifier(): ?string;

    /**
     * @return string|null
     */
    public function getSource(): ?string;

    /**
     * @return string|null
     */
    public function getModel(): ?string;

    /**
     * @return string|null
     */
    public function getApiKey(): ?string;

    /**
     * @return string|null
     */
    public function getUrl(): ?string;

    /**
     * @return int|null
     */
    public function getDimensions(): ?int;

    /**
     * @return string|null
     */
    public function getDocumentTemplate(): ?string;

    /**
     * @return int|null
     */
    public function getDocumentTemplateMaxBytes(): ?int;

    /**
     * @return bool|null
     */
    public function getBinaryQuantized(): ?bool;

    /**
     * @return string|null
     */
    public function getRevision(): ?string;

    /**
     * @return string|null
     */
    public function getPooling(): ?string;

    /**
     * @return string|null
     */
    public function getDistribution(): ?string;

    /**
     * @return string|null
     */
    public function getRequestConfig(): ?string;

    /**
     * @return string|null
     */
    public function getResponseConfig(): ?string;

    /**
     * @return string|null
     */
    public function getIndexingConfig(): ?string;

    /**
     * @return string|null
     */
    public function getSearchConfig(): ?string;

    /**
     * @return string|null
     */
    public function getIndexingFragments(): ?string;

    /**
     * @return string|null
     */
    public function getSearchFragments(): ?string;

    /**
     * @param $id
     * @return mixed
     */
    public function setEmbedderId($id);

    /**
     * @param string $name
     * @return mixed
     */
    public function setName(string $name);

    /**
     * @param string $identifier
     * @return mixed
     */
    public function setIdentifier(string $identifier);

    /**
     * @param string $source
     * @return mixed
     */
    public function setSource(string $source);

    /**
     * @param string|null $model
     * @return mixed
     */
    public function setModel(?string $model);

    /**
     * @param string|null $apiKey
     * @return mixed
     */
    public function setApiKey(?string $apiKey);

    /**
     * @param string|null $url
     * @return mixed
     */
    public function setUrl(?string $url);

    /**
     * @param int|null $dimensions
     * @return mixed
     */
    public function setDimensions(?int $dimensions);

    /**
     * @param string|null $template
     * @return mixed
     */
    public function setDocumentTemplate(?string $template);

    /**
     * @param int|null $maxBytes
     * @return mixed
     */
    public function setDocumentTemplateMaxBytes(?int $maxBytes);

    /**
     * @param bool|null $isBinary
     * @return mixed
     */
    public function setBinaryQuantized(?bool $isBinary);

    /**
     * @param string|null $revision
     * @return mixed
     */
    public function setRevision(?string $revision);

    /**
     * @param string|null $pooling
     * @return mixed
     */
    public function setPooling(?string $pooling);

    /**
     * @param string|null $distribution
     * @return mixed
     */
    public function setDistribution(?string $distribution);

    /**
     * @param string|null $config
     * @return mixed
     */
    public function setRequestConfig(?string $config);

    /**
     * @param string|null $config
     * @return mixed
     */
    public function setResponseConfig(?string $config);

    /**
     * @param string|null $config
     * @return mixed
     */
    public function setIndexingConfig(?string $config);

    /**
     * @param string|null $config
     * @return mixed
     */
    public function setSearchConfig(?string $config);

    /**
     * @param string|null $fragments
     * @return mixed
     */
    public function setIndexingFragments(?string $fragments);

    /**
     * @param string|null $fragments
     * @return mixed
     */
    public function setSearchFragments(?string $fragments);
}
