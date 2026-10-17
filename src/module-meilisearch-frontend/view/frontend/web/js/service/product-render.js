(function(root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else {
        root.MeilisearchProductRender = factory();
    }
}(this, function() {
    'use strict';

    const DEFAULT_PRICE_ATTRIBUTE = 'price_0';
    const SWATCH_TYPE_COLOR = 1;
    const SWATCH_TYPE_IMAGE = 2;
    const PRICE_TYPE_DEFAULT = 'default';
    const PRICE_TYPES = ['configurable', 'bundle', 'grouped'];

    function getPriceEntry(priceRender, priceAttributeCode, currencyCode) {
        const prices = priceRender || {};
        const groupPrices = prices[priceAttributeCode] || prices[DEFAULT_PRICE_ATTRIBUTE] || {};

        return groupPrices[currencyCode] || null;
    }

    function getBasePriceType(priceType) {
        if (!priceType) {
            return '';
        }

        return priceType === 'finalPrice'
            ? 'basePrice'
            : 'base' + priceType.charAt(0).toUpperCase() + priceType.slice(1);
    }

    function buildAmount(price, priceId, priceType, priceConfig, formatPrice) {
        if (!price || isNaN(Number(price.excl_tax)) || isNaN(Number(price.incl_tax))) {
            return null;
        }

        const displayMode = priceConfig.priceDisplayMode;
        const showBoth = displayMode === 'both';
        const value = Number(price[displayMode === 'excl_tax' ? 'excl_tax' : 'incl_tax']);

        return {
            id: priceId ? (showBoth ? 'price-including-tax-' + priceId : priceId) : null,
            type: priceType,
            value: value,
            price: String(formatPrice(value) || ''),
            exclTax: showBoth ? {
                id: priceId ? 'price-excluding-tax-' + priceId : null,
                type: getBasePriceType(priceType),
                value: Number(price.excl_tax),
                price: String(formatPrice(Number(price.excl_tax)) || '')
            } : null
        };
    }

    function getPriceType(hit) {
        const typeId = hit && hit.type_id;

        return PRICE_TYPES.indexOf(typeId) !== -1 ? typeId : PRICE_TYPE_DEFAULT;
    }

    function getPriceView(hit, priceAttributeCode, childPrice, priceConfig, formatPrice) {
        const entry = getPriceEntry(hit && hit.price_render, priceAttributeCode, priceConfig.currencyCode);

        if (!entry) {
            return null;
        }

        const productId = hit.id;
        const type = getPriceType(hit);
        const amount = (price, priceId, priceType) => buildAmount(price, priceId, priceType, priceConfig, formatPrice);

        if (type === 'grouped') {
            const minPrice = amount(entry.min_price, null, '');

            return minPrice ? { type: type, productId: productId, minPrice: minPrice } : null;
        }

        if (type === 'bundle') {
            const bundleView = Number(hit.price_view) === 1 ? 'as_low_as' : (entry.is_range ? 'range' : 'single');
            const minPrice = amount(
                entry.min_price,
                (bundleView === 'single' ? 'product-price-' : 'from-') + productId,
                bundleView === 'range' ? 'minPrice' : ''
            );

            if (!minPrice) {
                return null;
            }

            return {
                type: type,
                productId: productId,
                bundleView: bundleView,
                minPrice: minPrice,
                maxPrice: bundleView === 'range' ? amount(entry.max_price, 'to-' + productId, 'maxPrice') : null
            };
        }

        const source = childPrice || entry;
        const finalPrice = amount(
            type === 'configurable' && !childPrice ? (entry.min_price || entry.final_price) : source.final_price,
            'product-price-' + productId,
            'finalPrice'
        );

        if (!finalPrice) {
            return null;
        }

        const oldPrice = source.has_discount ? amount(source.price, 'old-price-' + productId, 'oldPrice') : null;

        return {
            type: type,
            productId: productId,
            isRange: type === 'configurable' && !!entry.is_range,
            finalPrice: finalPrice,
            oldPrice: oldPrice,
            hasSpecialPrice: !!oldPrice && (type !== 'configurable' || !!childPrice),
            minimalPrice: type === PRICE_TYPE_DEFAULT && !childPrice
                ? amount(entry.tier_price, String(productId), '')
                : null
        };
    }

    function compareOptions(first, second) {
        if (first.sort_order !== second.sort_order) {
            return first.sort_order - second.sort_order;
        }

        return first.label < second.label ? -1 : (first.label > second.label ? 1 : 0);
    }

    function buildSwatchAttribute(attribute, catalog, children) {
        const options = {};

        children.forEach(child => {
            const optionId = (child.attributes || {})[attribute.attribute_id];
            const option = optionId !== undefined ? catalog[optionId] : null;

            if (!option) {
                return;
            }

            options[optionId] = options[optionId]
                || Object.assign({ value: Number(optionId) }, option, { is_available: false });

            if (child.is_available) {
                options[optionId].is_available = true;
            }
        });

        return {
            attribute_id: attribute.attribute_id,
            attribute_code: attribute.attribute_code,
            label: attribute.label,
            options: Object.values(options).sort(compareOptions)
        };
    }

    function getSwatchAttributes(hit, swatchOptions) {
        const render = (hit && hit.configurable_render) || {};
        const attributes = (render.attributes || [])
            .filter(attribute => swatchOptions && swatchOptions[attribute.attribute_id])
            .map(attribute => buildSwatchAttribute(attribute, swatchOptions[attribute.attribute_id], getChildren(hit)));

        return attributes.filter(attribute => Array.isArray(attribute.options) && attribute.options.length > 0);
    }

    function getChildren(hit) {
        const children = (hit && hit.configurable_render && hit.configurable_render.children) || [];

        return Array.isArray(children) ? children : [];
    }

    function matchesSelection(child, selection) {
        const attributes = child.attributes || {};

        return Object.keys(selection).every(
            attributeId => Number(attributes[attributeId]) === Number(selection[attributeId])
        );
    }

    function getCandidates(hit, selection) {
        return getChildren(hit).filter(child => child.is_available && matchesSelection(child, selection || {}));
    }

    function hasSelection(selection) {
        return Object.keys(selection || {}).length > 0;
    }

    function isOptionAvailable(hit, selection, attributeId, optionId) {
        const next = Object.assign({}, selection || {});
        next[attributeId] = Number(optionId);

        return getCandidates(hit, next).length > 0;
    }

    function toggleOption(selection, attributeId, optionId) {
        const next = Object.assign({}, selection || {});

        if (Number(next[attributeId]) === Number(optionId)) {
            delete next[attributeId];
        } else {
            next[attributeId] = Number(optionId);
        }

        return next;
    }

    function getSelectedOptionLabel(attribute, selection) {
        const selected = (selection || {})[attribute.attribute_id];
        const option = (attribute.options || []).find(item => Number(item.value) === Number(selected));

        return option ? String(option.label || '') : '';
    }

    function getSelectedImage(hit, selection, imageType) {
        if (!hasSelection(selection)) {
            return '';
        }

        for (const child of getCandidates(hit, selection)) {
            const images = child.images || {};
            const path = images[imageType] || images.small_image || images.image || '';

            if (path) {
                return path;
            }
        }

        return '';
    }

    function getSwatchKind(option) {
        const type = option && option.swatch_type !== null && option.swatch_type !== undefined
            ? Number(option.swatch_type)
            : null;

        if (type === SWATCH_TYPE_COLOR) {
            return 'color';
        }

        if (type === SWATCH_TYPE_IMAGE) {
            return 'image';
        }

        return 'text';
    }

    function getSelectedPrice(hit, selection, priceAttributeCode, currencyCode) {
        if (!hasSelection(selection)) {
            return null;
        }

        const candidates = getCandidates(hit, selection);
        const prices = candidates
            .map(child => getPriceEntry(child.price_render, priceAttributeCode, currencyCode))
            .filter(price => price && price.final_price);

        if (!prices.length || prices.length !== candidates.length) {
            return null;
        }

        const reference = prices[0].final_price.incl_tax;

        return prices.every(price => price.final_price.incl_tax === reference) ? prices[0] : null;
    }

    function getSwatchStyle(option, mediaUrl) {
        const kind = getSwatchKind(option);

        if (kind === 'color' && option.swatch_value) {
            return 'background: ' + option.swatch_value + ';';
        }

        if (kind === 'image' && option.swatch_image) {
            const base = String(mediaUrl || '').replace(/\/+$/, '');
            const path = String(option.swatch_image).replace(/^\/+/, '');

            return 'background: url(\'' + base + '/' + path + '\') no-repeat center; background-size: cover;';
        }

        return '';
    }

    function getSwatchText(option) {
        if (getSwatchKind(option) !== 'text') {
            return '';
        }

        return String(option.swatch_value || option.label || '');
    }

    return {
        getPriceView: getPriceView,
        getSwatchAttributes: getSwatchAttributes,
        isOptionAvailable: isOptionAvailable,
        toggleOption: toggleOption,
        getSelectedOptionLabel: getSelectedOptionLabel,
        getSelectedImage: getSelectedImage,
        getSelectedPrice: getSelectedPrice,
        getSwatchKind: getSwatchKind,
        getSwatchStyle: getSwatchStyle,
        getSwatchText: getSwatchText
    };
}));
