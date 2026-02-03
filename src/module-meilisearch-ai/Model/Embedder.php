<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Model;

use Magento\Framework\Model\AbstractModel;
use Walkwizus\MeilisearchAi\Api\Data\EmbedderInterface;
use Magento\Framework\Exception\LocalizedException;

class Embedder extends AbstractModel implements EmbedderInterface
{
    /**
     * @return void
     * @throws LocalizedException
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel\Embedder::class);
    }

    /**
     * @return array|mixed|null
     */
    public function getEmbedderId()
    {
        return $this->getData(self::EMBEDDER_ID);
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->getData(self::NAME);
    }

    /**
     * @return string|null
     */
    public function getIdentifier(): ?string
    {
        return $this->getData(self::IDENTIFIER);
    }

    /**
     * @return string|null
     */
    public function getSource(): ?string
    {
        return $this->getData(self::SOURCE);
    }

    /**
     * @return string|null
     */
    public function getModel(): ?string
    {
        return $this->getData(self::MODEL);
    }

    /**
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return $this->getData(self::API_KEY);
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->getData(self::URL);
    }

    /**
     * @return int|null
     */
    public function getDimensions(): ?int
    {
        return $this->getData(self::DIMENSIONS) ? (int)$this->getData(self::DIMENSIONS) : null;
    }

    /**
     * @return string|null
     */
    public function getDocumentTemplate(): ?string
    {
        return $this->getData(self::DOCUMENT_TEMPLATE);
    }

    /**
     * @return int|null
     */
    public function getDocumentTemplateMaxBytes(): ?int
    {
        return $this->getData(self::DOCUMENT_TEMPLATE_MAX_BYTES) ? (int)$this->getData(self::DOCUMENT_TEMPLATE_MAX_BYTES) : null;
    }

    /**
     * @return bool|null
     */
    public function getBinaryQuantized(): ?bool
    {
        return $this->getData(self::BINARY_QUANTIZED) !== null ? (bool)$this->getData(self::BINARY_QUANTIZED) : null;
    }

    /**
     * @return string|null
     */
    public function getRevision(): ?string
    {
        return $this->getData(self::REVISION);
    }

    /**
     * @return string|null
     */
    public function getPooling(): ?string
    {
        return $this->getData(self::POOLING);
    }

    /**
     * @return string|null
     */
    public function getDistribution(): ?string
    {
        return $this->getData(self::DISTRIBUTION);
    }

    /**
     * @return string|null
     */
    public function getRequestConfig(): ?string
    {
        return $this->getData(self::REQUEST_CONFIG);
    }

    /**
     * @return string|null
     */
    public function getResponseConfig(): ?string
    {
        return $this->getData(self::RESPONSE_CONFIG);
    }

    /**
     * @return string|null
     */
    public function getIndexingConfig(): ?string
    {
        return $this->getData(self::INDEXING_CONFIG);
    }

    /**
     * @return string|null
     */
    public function getSearchConfig(): ?string
    {
        return $this->getData(self::SEARCH_CONFIG);
    }

    /**
     * @return string|null
     */
    public function getIndexingFragments(): ?string
    {
        return $this->getData(self::INDEXING_FRAGMENTS);
    }

    /**
     * @return string|null
     */
    public function getSearchFragments(): ?string
    {
        return $this->getData(self::SEARCH_FRAGMENTS);
    }

    /**
     * @param $id
     * @return mixed|Embedder
     */
    public function setEmbedderId($id)
    {
        return $this->setData(self::EMBEDDER_ID, $id);
    }

    /**
     * @param string $name
     * @return mixed|Embedder
     */
    public function setName(string $name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @param string $identifier
     * @return mixed|Embedder
     */
    public function setIdentifier(string $identifier)
    {
        return $this->setData(self::IDENTIFIER, $identifier);
    }

    /**
     * @param string $source
     * @return mixed|Embedder
     */
    public function setSource(string $source)
    {
        return $this->setData(self::SOURCE, $source);
    }

    /**
     * @param string|null $model
     * @return mixed|Embedder
     */
    public function setModel(?string $model)
    {
        return $this->setData(self::MODEL, $model);
    }

    /**
     * @param string|null $apiKey
     * @return mixed|Embedder
     */
    public function setApiKey(?string $apiKey)
    {
        return $this->setData(self::API_KEY, $apiKey);
    }

    /**
     * @param string|null $url
     * @return mixed|Embedder
     */
    public function setUrl(?string $url)
    {
        return $this->setData(self::URL, $url);
    }

    /**
     * @param int|null $dimensions
     * @return mixed|Embedder
     */
    public function setDimensions(?int $dimensions)
    {
        return $this->setData(self::DIMENSIONS, $dimensions);
    }

    /**
     * @param string|null $template
     * @return mixed|Embedder
     */
    public function setDocumentTemplate(?string $template)
    {
        return $this->setData(self::DOCUMENT_TEMPLATE, $template);
    }

    /**
     * @param int|null $maxBytes
     * @return mixed|Embedder
     */
    public function setDocumentTemplateMaxBytes(?int $maxBytes)
    {
        return $this->setData(self::DOCUMENT_TEMPLATE_MAX_BYTES, $maxBytes);
    }

    /**
     * @param bool|null $isBinary
     * @return mixed|Embedder
     */
    public function setBinaryQuantized(?bool $isBinary)
    {
        return $this->setData(self::BINARY_QUANTIZED, $isBinary);
    }

    /**
     * @param string|null $revision
     * @return mixed|Embedder
     */
    public function setRevision(?string $revision)
    {
        return $this->setData(self::REVISION, $revision);
    }

    /**
     * @param string|null $pooling
     * @return mixed|Embedder
     */
    public function setPooling(?string $pooling)
    {
        return $this->setData(self::POOLING, $pooling);
    }

    /**
     * @param string|null $distribution
     * @return mixed|Embedder
     */
    public function setDistribution(?string $distribution)
    {
        return $this->setData(self::DISTRIBUTION, $distribution);
    }

    /**
     * @param string|null $config
     * @return mixed|Embedder
     */
    public function setRequestConfig(?string $config)
    {
        return $this->setData(self::REQUEST_CONFIG, $config);
    }

    /**
     * @param string|null $config
     * @return mixed|Embedder
     */
    public function setResponseConfig(?string $config)
    {
        return $this->setData(self::RESPONSE_CONFIG, $config);
    }

    /**
     * @param string|null $config
     * @return mixed|Embedder
     */
    public function setIndexingConfig(?string $config)
    {
        return $this->setData(self::INDEXING_CONFIG, $config);
    }

    /**
     * @param string|null $config
     * @return mixed|Embedder
     */
    public function setSearchConfig(?string $config)
    {
        return $this->setData(self::SEARCH_CONFIG, $config);
    }

    /**
     * @param string|null $fragments
     * @return mixed|Embedder
     */
    public function setIndexingFragments(?string $fragments)
    {
        return $this->setData(self::INDEXING_FRAGMENTS, $fragments);
    }

    /**
     * @param string|null $fragments
     * @return mixed|Embedder
     */
    public function setSearchFragments(?string $fragments)
    {
        return $this->setData(self::SEARCH_FRAGMENTS, $fragments);
    }
}
