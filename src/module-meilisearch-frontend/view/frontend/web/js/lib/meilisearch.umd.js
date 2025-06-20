(function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports) :
        typeof define === 'function' && define.amd ? define(['exports'], factory) :
            (global = typeof globalThis !== 'undefined' ? globalThis : global || self, factory(global.window = global.window || {}));
})(this, (function (exports) { 'use strict';

    // Type definitions for meilisearch
    // Project: https://github.com/meilisearch/meilisearch-js
    // Definitions by: qdequele <quentin@meilisearch.com> <https://github.com/meilisearch>
    // Definitions: https://github.com/meilisearch/meilisearch-js
    // TypeScript Version: ^3.8.3
    /*
   * SEARCH PARAMETERS
   */
    var MatchingStrategies = {
        ALL: 'all',
        LAST: 'last',
        FREQUENCY: 'frequency'
    };
    var ContentTypeEnum = {
        JSON: 'application/json',
        CSV: 'text/csv',
        NDJSON: 'application/x-ndjson'
    };
    /*
   ** TASKS
   */
    var TaskStatus = {
        TASK_SUCCEEDED: 'succeeded',
        TASK_PROCESSING: 'processing',
        TASK_FAILED: 'failed',
        TASK_ENQUEUED: 'enqueued',
        TASK_CANCELED: 'canceled'
    };
    var TaskTypes = {
        DOCUMENTS_ADDITION_OR_UPDATE: 'documentAdditionOrUpdate',
        DOCUMENT_DELETION: 'documentDeletion',
        DUMP_CREATION: 'dumpCreation',
        INDEX_CREATION: 'indexCreation',
        INDEX_DELETION: 'indexDeletion',
        INDEXES_SWAP: 'indexSwap',
        INDEX_UPDATE: 'indexUpdate',
        SETTINGS_UPDATE: 'settingsUpdate',
        SNAPSHOT_CREATION: 'snapshotCreation',
        TASK_CANCELATION: 'taskCancelation',
        TASK_DELETION: 'taskDeletion'
    };
    // @TODO: This doesn't seem to be up to date, and its usefullness comes into question.
    var ErrorStatusCode = {
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#index_creation_failed */
        INDEX_CREATION_FAILED: 'index_creation_failed',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#missing_index_uid */
        MISSING_INDEX_UID: 'missing_index_uid',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#index_already_exists */
        INDEX_ALREADY_EXISTS: 'index_already_exists',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#index_not_found */
        INDEX_NOT_FOUND: 'index_not_found',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_index_uid */
        INVALID_INDEX_UID: 'invalid_index_uid',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#index_not_accessible */
        INDEX_NOT_ACCESSIBLE: 'index_not_accessible',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_index_offset */
        INVALID_INDEX_OFFSET: 'invalid_index_offset',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_index_limit */
        INVALID_INDEX_LIMIT: 'invalid_index_limit',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_state */
        INVALID_STATE: 'invalid_state',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#primary_key_inference_failed */
        PRIMARY_KEY_INFERENCE_FAILED: 'primary_key_inference_failed',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#index_primary_key_already_exists */
        INDEX_PRIMARY_KEY_ALREADY_EXISTS: 'index_primary_key_already_exists',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_index_primary_key */
        INVALID_INDEX_PRIMARY_KEY: 'invalid_index_primary_key',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#max_fields_limit_exceeded */
        DOCUMENTS_FIELDS_LIMIT_REACHED: 'document_fields_limit_reached',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#missing_document_id */
        MISSING_DOCUMENT_ID: 'missing_document_id',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#missing_document_id */
        INVALID_DOCUMENT_ID: 'invalid_document_id',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_content_type */
        INVALID_CONTENT_TYPE: 'invalid_content_type',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#missing_content_type */
        MISSING_CONTENT_TYPE: 'missing_content_type',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_document_fields */
        INVALID_DOCUMENT_FIELDS: 'invalid_document_fields',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_document_limit */
        INVALID_DOCUMENT_LIMIT: 'invalid_document_limit',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_document_offset */
        INVALID_DOCUMENT_OFFSET: 'invalid_document_offset',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_document_filter */
        INVALID_DOCUMENT_FILTER: 'invalid_document_filter',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#missing_document_filter */
        MISSING_DOCUMENT_FILTER: 'missing_document_filter',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_document_vectors_field */
        INVALID_DOCUMENT_VECTORS_FIELD: 'invalid_document_vectors_field',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#payload_too_large */
        PAYLOAD_TOO_LARGE: 'payload_too_large',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#missing_payload */
        MISSING_PAYLOAD: 'missing_payload',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#malformed_payload */
        MALFORMED_PAYLOAD: 'malformed_payload',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#no_space_left_on_device */
        NO_SPACE_LEFT_ON_DEVICE: 'no_space_left_on_device',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_store_file */
        INVALID_STORE_FILE: 'invalid_store_file',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_ranking_rules */
        INVALID_RANKING_RULES: 'missing_document_id',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_request */
        INVALID_REQUEST: 'invalid_request',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_document_geo_field */
        INVALID_DOCUMENT_GEO_FIELD: 'invalid_document_geo_field',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_search_q */
        INVALID_SEARCH_Q: 'invalid_search_q',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_search_offset */
        INVALID_SEARCH_OFFSET: 'invalid_search_offset',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_search_limit */
        INVALID_SEARCH_LIMIT: 'invalid_search_limit',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_search_page */
        INVALID_SEARCH_PAGE: 'invalid_search_page',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_search_hits_per_page */
        INVALID_SEARCH_HITS_PER_PAGE: 'invalid_search_hits_per_page',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_search_attributes_to_retrieve */
        INVALID_SEARCH_ATTRIBUTES_TO_RETRIEVE: 'invalid_search_attributes_to_retrieve',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_search_attributes_to_crop */
        INVALID_SEARCH_ATTRIBUTES_TO_CROP: 'invalid_search_attributes_to_crop',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_search_crop_length */
        INVALID_SEARCH_CROP_LENGTH: 'invalid_search_crop_length',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_search_attributes_to_highlight */
        INVALID_SEARCH_ATTRIBUTES_TO_HIGHLIGHT: 'invalid_search_attributes_to_highlight',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_search_show_matches_position */
        INVALID_SEARCH_SHOW_MATCHES_POSITION: 'invalid_search_show_matches_position',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_search_filter */
        INVALID_SEARCH_FILTER: 'invalid_search_filter',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_search_sort */
        INVALID_SEARCH_SORT: 'invalid_search_sort',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_search_facets */
        INVALID_SEARCH_FACETS: 'invalid_search_facets',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_search_highlight_pre_tag */
        INVALID_SEARCH_HIGHLIGHT_PRE_TAG: 'invalid_search_highlight_pre_tag',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_search_highlight_post_tag */
        INVALID_SEARCH_HIGHLIGHT_POST_TAG: 'invalid_search_highlight_post_tag',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_search_crop_marker */
        INVALID_SEARCH_CROP_MARKER: 'invalid_search_crop_marker',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_search_matching_strategy */
        INVALID_SEARCH_MATCHING_STRATEGY: 'invalid_search_matching_strategy',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_search_vector */
        INVALID_SEARCH_VECTOR: 'invalid_search_vector',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_search_attributes_to_search_on */
        INVALID_SEARCH_ATTRIBUTES_TO_SEARCH_ON: 'invalid_search_attributes_to_search_on',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#bad_request */
        BAD_REQUEST: 'bad_request',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#document_not_found */
        DOCUMENT_NOT_FOUND: 'document_not_found',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#internal */
        INTERNAL: 'internal',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_api_key */
        INVALID_API_KEY: 'invalid_api_key',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_api_key_description */
        INVALID_API_KEY_DESCRIPTION: 'invalid_api_key_description',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_api_key_actions */
        INVALID_API_KEY_ACTIONS: 'invalid_api_key_actions',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_api_key_indexes */
        INVALID_API_KEY_INDEXES: 'invalid_api_key_indexes',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_api_key_expires_at */
        INVALID_API_KEY_EXPIRES_AT: 'invalid_api_key_expires_at',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#api_key_not_found */
        API_KEY_NOT_FOUND: 'api_key_not_found',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#immutable_api_key_uid */
        IMMUTABLE_API_KEY_UID: 'immutable_api_key_uid',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#immutable_api_key_actions */
        IMMUTABLE_API_KEY_ACTIONS: 'immutable_api_key_actions',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#immutable_api_key_indexes */
        IMMUTABLE_API_KEY_INDEXES: 'immutable_api_key_indexes',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#immutable_api_key_expires_at */
        IMMUTABLE_API_KEY_EXPIRES_AT: 'immutable_api_key_expires_at',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#immutable_api_key_created_at */
        IMMUTABLE_API_KEY_CREATED_AT: 'immutable_api_key_created_at',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#immutable_api_key_updated_at */
        IMMUTABLE_API_KEY_UPDATED_AT: 'immutable_api_key_updated_at',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#missing_authorization_header */
        MISSING_AUTHORIZATION_HEADER: 'missing_authorization_header',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#unretrievable_document */
        UNRETRIEVABLE_DOCUMENT: 'unretrievable_document',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#database_size_limit_reached */
        MAX_DATABASE_SIZE_LIMIT_REACHED: 'database_size_limit_reached',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#task_not_found */
        TASK_NOT_FOUND: 'task_not_found',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#dump_process_failed */
        DUMP_PROCESS_FAILED: 'dump_process_failed',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#dump_not_found */
        DUMP_NOT_FOUND: 'dump_not_found',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_swap_duplicate_index_found */
        INVALID_SWAP_DUPLICATE_INDEX_FOUND: 'invalid_swap_duplicate_index_found',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_swap_indexes */
        INVALID_SWAP_INDEXES: 'invalid_swap_indexes',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#missing_swap_indexes */
        MISSING_SWAP_INDEXES: 'missing_swap_indexes',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#missing_master_key */
        MISSING_MASTER_KEY: 'missing_master_key',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_task_types */
        INVALID_TASK_TYPES: 'invalid_task_types',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_task_uids */
        INVALID_TASK_UIDS: 'invalid_task_uids',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_task_statuses */
        INVALID_TASK_STATUSES: 'invalid_task_statuses',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_task_limit */
        INVALID_TASK_LIMIT: 'invalid_task_limit',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_task_from */
        INVALID_TASK_FROM: 'invalid_task_from',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_task_canceled_by */
        INVALID_TASK_CANCELED_BY: 'invalid_task_canceled_by',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#missing_task_filters */
        MISSING_TASK_FILTERS: 'missing_task_filters',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#too_many_open_files */
        TOO_MANY_OPEN_FILES: 'too_many_open_files',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#io_error */
        IO_ERROR: 'io_error',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_task_index_uids */
        INVALID_TASK_INDEX_UIDS: 'invalid_task_index_uids',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#immutable_index_uid */
        IMMUTABLE_INDEX_UID: 'immutable_index_uid',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#immutable_index_created_at */
        IMMUTABLE_INDEX_CREATED_AT: 'immutable_index_created_at',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#immutable_index_updated_at */
        IMMUTABLE_INDEX_UPDATED_AT: 'immutable_index_updated_at',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_settings_displayed_attributes */
        INVALID_SETTINGS_DISPLAYED_ATTRIBUTES: 'invalid_settings_displayed_attributes',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_settings_searchable_attributes */
        INVALID_SETTINGS_SEARCHABLE_ATTRIBUTES: 'invalid_settings_searchable_attributes',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_settings_filterable_attributes */
        INVALID_SETTINGS_FILTERABLE_ATTRIBUTES: 'invalid_settings_filterable_attributes',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_settings_sortable_attributes */
        INVALID_SETTINGS_SORTABLE_ATTRIBUTES: 'invalid_settings_sortable_attributes',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_settings_ranking_rules */
        INVALID_SETTINGS_RANKING_RULES: 'invalid_settings_ranking_rules',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_settings_stop_words */
        INVALID_SETTINGS_STOP_WORDS: 'invalid_settings_stop_words',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_settings_synonyms */
        INVALID_SETTINGS_SYNONYMS: 'invalid_settings_synonyms',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_settings_distinct_attribute */
        INVALID_SETTINGS_DISTINCT_ATTRIBUTE: 'invalid_settings_distinct_attribute',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_settings_typo_tolerance */
        INVALID_SETTINGS_TYPO_TOLERANCE: 'invalid_settings_typo_tolerance',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_settings_faceting */
        INVALID_SETTINGS_FACETING: 'invalid_settings_faceting',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_settings_pagination */
        INVALID_SETTINGS_PAGINATION: 'invalid_settings_pagination',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_settings_search_cutoff_ms */
        INVALID_SETTINGS_SEARCH_CUTOFF_MS: 'invalid_settings_search_cutoff_ms',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_settings_search_cutoff_ms */
        INVALID_SETTINGS_LOCALIZED_ATTRIBUTES: 'invalid_settings_localized_attributes',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_task_before_enqueued_at */
        INVALID_TASK_BEFORE_ENQUEUED_AT: 'invalid_task_before_enqueued_at',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_task_after_enqueued_at */
        INVALID_TASK_AFTER_ENQUEUED_AT: 'invalid_task_after_enqueued_at',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_task_before_started_at */
        INVALID_TASK_BEFORE_STARTED_AT: 'invalid_task_before_started_at',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_task_after_started_at */
        INVALID_TASK_AFTER_STARTED_AT: 'invalid_task_after_started_at',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_task_before_finished_at */
        INVALID_TASK_BEFORE_FINISHED_AT: 'invalid_task_before_finished_at',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_task_after_finished_at */
        INVALID_TASK_AFTER_FINISHED_AT: 'invalid_task_after_finished_at',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#missing_api_key_actions */
        MISSING_API_KEY_ACTIONS: 'missing_api_key_actions',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#missing_api_key_indexes */
        MISSING_API_KEY_INDEXES: 'missing_api_key_indexes',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#missing_api_key_expires_at */
        MISSING_API_KEY_EXPIRES_AT: 'missing_api_key_expires_at',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_api_key_limit */
        INVALID_API_KEY_LIMIT: 'invalid_api_key_limit',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_api_key_offset */
        INVALID_API_KEY_OFFSET: 'invalid_api_key_offset',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_facet_search_facet_name */
        INVALID_FACET_SEARCH_FACET_NAME: 'invalid_facet_search_facet_name',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#missing_facet_search_facet_name */
        MISSING_FACET_SEARCH_FACET_NAME: 'missing_facet_search_facet_name',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_facet_search_facet_query */
        INVALID_FACET_SEARCH_FACET_QUERY: 'invalid_facet_search_facet_query',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_search_ranking_score_threshold */
        INVALID_SEARCH_RANKING_SCORE_THRESHOLD: 'invalid_search_ranking_score_threshold',
        /** @see https://www.meilisearch.com/docs/reference/errors/error_codes#invalid_similar_ranking_score_threshold */
        INVALID_SIMILAR_RANKING_SCORE_THRESHOLD: 'invalid_similar_ranking_score_threshold'
    };

    function _iterableToArrayLimit(r, l) {
        var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"];
        if (null != t) {
            var e,
                n,
                i,
                u,
                a = [],
                f = !0,
                o = !1;
            try {
                if (i = (t = t.call(r)).next, 0 === l) {
                    if (Object(t) !== t) return;
                    f = !1;
                } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0);
            } catch (r) {
                o = !0, n = r;
            } finally {
                try {
                    if (!f && null != t.return && (u = t.return(), Object(u) !== u)) return;
                } finally {
                    if (o) throw n;
                }
            }
            return a;
        }
    }
    function ownKeys(e, r) {
        var t = Object.keys(e);
        if (Object.getOwnPropertySymbols) {
            var o = Object.getOwnPropertySymbols(e);
            r && (o = o.filter(function (r) {
                return Object.getOwnPropertyDescriptor(e, r).enumerable;
            })), t.push.apply(t, o);
        }
        return t;
    }
    function _objectSpread2(e) {
        for (var r = 1; r < arguments.length; r++) {
            var t = null != arguments[r] ? arguments[r] : {};
            r % 2 ? ownKeys(Object(t), !0).forEach(function (r) {
                _defineProperty(e, r, t[r]);
            }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) {
                Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
            });
        }
        return e;
    }
    function _regeneratorRuntime() {
        _regeneratorRuntime = function () {
            return e;
        };
        var t,
            e = {},
            r = Object.prototype,
            n = r.hasOwnProperty,
            o = Object.defineProperty || function (t, e, r) {
                t[e] = r.value;
            },
            i = "function" == typeof Symbol ? Symbol : {},
            a = i.iterator || "@@iterator",
            c = i.asyncIterator || "@@asyncIterator",
            u = i.toStringTag || "@@toStringTag";
        function define(t, e, r) {
            return Object.defineProperty(t, e, {
                value: r,
                enumerable: !0,
                configurable: !0,
                writable: !0
            }), t[e];
        }
        try {
            define({}, "");
        } catch (t) {
            define = function (t, e, r) {
                return t[e] = r;
            };
        }
        function wrap(t, e, r, n) {
            var i = e && e.prototype instanceof Generator ? e : Generator,
                a = Object.create(i.prototype),
                c = new Context(n || []);
            return o(a, "_invoke", {
                value: makeInvokeMethod(t, r, c)
            }), a;
        }
        function tryCatch(t, e, r) {
            try {
                return {
                    type: "normal",
                    arg: t.call(e, r)
                };
            } catch (t) {
                return {
                    type: "throw",
                    arg: t
                };
            }
        }
        e.wrap = wrap;
        var h = "suspendedStart",
            l = "suspendedYield",
            f = "executing",
            s = "completed",
            y = {};
        function Generator() {}
        function GeneratorFunction() {}
        function GeneratorFunctionPrototype() {}
        var p = {};
        define(p, a, function () {
            return this;
        });
        var d = Object.getPrototypeOf,
            v = d && d(d(values([])));
        v && v !== r && n.call(v, a) && (p = v);
        var g = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(p);
        function defineIteratorMethods(t) {
            ["next", "throw", "return"].forEach(function (e) {
                define(t, e, function (t) {
                    return this._invoke(e, t);
                });
            });
        }
        function AsyncIterator(t, e) {
            function invoke(r, o, i, a) {
                var c = tryCatch(t[r], t, o);
                if ("throw" !== c.type) {
                    var u = c.arg,
                        h = u.value;
                    return h && "object" == typeof h && n.call(h, "__await") ? e.resolve(h.__await).then(function (t) {
                        invoke("next", t, i, a);
                    }, function (t) {
                        invoke("throw", t, i, a);
                    }) : e.resolve(h).then(function (t) {
                        u.value = t, i(u);
                    }, function (t) {
                        return invoke("throw", t, i, a);
                    });
                }
                a(c.arg);
            }
            var r;
            o(this, "_invoke", {
                value: function (t, n) {
                    function callInvokeWithMethodAndArg() {
                        return new e(function (e, r) {
                            invoke(t, n, e, r);
                        });
                    }
                    return r = r ? r.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg();
                }
            });
        }
        function makeInvokeMethod(e, r, n) {
            var o = h;
            return function (i, a) {
                if (o === f) throw new Error("Generator is already running");
                if (o === s) {
                    if ("throw" === i) throw a;
                    return {
                        value: t,
                        done: !0
                    };
                }
                for (n.method = i, n.arg = a;;) {
                    var c = n.delegate;
                    if (c) {
                        var u = maybeInvokeDelegate(c, n);
                        if (u) {
                            if (u === y) continue;
                            return u;
                        }
                    }
                    if ("next" === n.method) n.sent = n._sent = n.arg;else if ("throw" === n.method) {
                        if (o === h) throw o = s, n.arg;
                        n.dispatchException(n.arg);
                    } else "return" === n.method && n.abrupt("return", n.arg);
                    o = f;
                    var p = tryCatch(e, r, n);
                    if ("normal" === p.type) {
                        if (o = n.done ? s : l, p.arg === y) continue;
                        return {
                            value: p.arg,
                            done: n.done
                        };
                    }
                    "throw" === p.type && (o = s, n.method = "throw", n.arg = p.arg);
                }
            };
        }
        function maybeInvokeDelegate(e, r) {
            var n = r.method,
                o = e.iterator[n];
            if (o === t) return r.delegate = null, "throw" === n && e.iterator.return && (r.method = "return", r.arg = t, maybeInvokeDelegate(e, r), "throw" === r.method) || "return" !== n && (r.method = "throw", r.arg = new TypeError("The iterator does not provide a '" + n + "' method")), y;
            var i = tryCatch(o, e.iterator, r.arg);
            if ("throw" === i.type) return r.method = "throw", r.arg = i.arg, r.delegate = null, y;
            var a = i.arg;
            return a ? a.done ? (r[e.resultName] = a.value, r.next = e.nextLoc, "return" !== r.method && (r.method = "next", r.arg = t), r.delegate = null, y) : a : (r.method = "throw", r.arg = new TypeError("iterator result is not an object"), r.delegate = null, y);
        }
        function pushTryEntry(t) {
            var e = {
                tryLoc: t[0]
            };
            1 in t && (e.catchLoc = t[1]), 2 in t && (e.finallyLoc = t[2], e.afterLoc = t[3]), this.tryEntries.push(e);
        }
        function resetTryEntry(t) {
            var e = t.completion || {};
            e.type = "normal", delete e.arg, t.completion = e;
        }
        function Context(t) {
            this.tryEntries = [{
                tryLoc: "root"
            }], t.forEach(pushTryEntry, this), this.reset(!0);
        }
        function values(e) {
            if (e || "" === e) {
                var r = e[a];
                if (r) return r.call(e);
                if ("function" == typeof e.next) return e;
                if (!isNaN(e.length)) {
                    var o = -1,
                        i = function next() {
                            for (; ++o < e.length;) if (n.call(e, o)) return next.value = e[o], next.done = !1, next;
                            return next.value = t, next.done = !0, next;
                        };
                    return i.next = i;
                }
            }
            throw new TypeError(typeof e + " is not iterable");
        }
        return GeneratorFunction.prototype = GeneratorFunctionPrototype, o(g, "constructor", {
            value: GeneratorFunctionPrototype,
            configurable: !0
        }), o(GeneratorFunctionPrototype, "constructor", {
            value: GeneratorFunction,
            configurable: !0
        }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, u, "GeneratorFunction"), e.isGeneratorFunction = function (t) {
            var e = "function" == typeof t && t.constructor;
            return !!e && (e === GeneratorFunction || "GeneratorFunction" === (e.displayName || e.name));
        }, e.mark = function (t) {
            return Object.setPrototypeOf ? Object.setPrototypeOf(t, GeneratorFunctionPrototype) : (t.__proto__ = GeneratorFunctionPrototype, define(t, u, "GeneratorFunction")), t.prototype = Object.create(g), t;
        }, e.awrap = function (t) {
            return {
                __await: t
            };
        }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, c, function () {
            return this;
        }), e.AsyncIterator = AsyncIterator, e.async = function (t, r, n, o, i) {
            void 0 === i && (i = Promise);
            var a = new AsyncIterator(wrap(t, r, n, o), i);
            return e.isGeneratorFunction(r) ? a : a.next().then(function (t) {
                return t.done ? t.value : a.next();
            });
        }, defineIteratorMethods(g), define(g, u, "Generator"), define(g, a, function () {
            return this;
        }), define(g, "toString", function () {
            return "[object Generator]";
        }), e.keys = function (t) {
            var e = Object(t),
                r = [];
            for (var n in e) r.push(n);
            return r.reverse(), function next() {
                for (; r.length;) {
                    var t = r.pop();
                    if (t in e) return next.value = t, next.done = !1, next;
                }
                return next.done = !0, next;
            };
        }, e.values = values, Context.prototype = {
            constructor: Context,
            reset: function (e) {
                if (this.prev = 0, this.next = 0, this.sent = this._sent = t, this.done = !1, this.delegate = null, this.method = "next", this.arg = t, this.tryEntries.forEach(resetTryEntry), !e) for (var r in this) "t" === r.charAt(0) && n.call(this, r) && !isNaN(+r.slice(1)) && (this[r] = t);
            },
            stop: function () {
                this.done = !0;
                var t = this.tryEntries[0].completion;
                if ("throw" === t.type) throw t.arg;
                return this.rval;
            },
            dispatchException: function (e) {
                if (this.done) throw e;
                var r = this;
                function handle(n, o) {
                    return a.type = "throw", a.arg = e, r.next = n, o && (r.method = "next", r.arg = t), !!o;
                }
                for (var o = this.tryEntries.length - 1; o >= 0; --o) {
                    var i = this.tryEntries[o],
                        a = i.completion;
                    if ("root" === i.tryLoc) return handle("end");
                    if (i.tryLoc <= this.prev) {
                        var c = n.call(i, "catchLoc"),
                            u = n.call(i, "finallyLoc");
                        if (c && u) {
                            if (this.prev < i.catchLoc) return handle(i.catchLoc, !0);
                            if (this.prev < i.finallyLoc) return handle(i.finallyLoc);
                        } else if (c) {
                            if (this.prev < i.catchLoc) return handle(i.catchLoc, !0);
                        } else {
                            if (!u) throw new Error("try statement without catch or finally");
                            if (this.prev < i.finallyLoc) return handle(i.finallyLoc);
                        }
                    }
                }
            },
            abrupt: function (t, e) {
                for (var r = this.tryEntries.length - 1; r >= 0; --r) {
                    var o = this.tryEntries[r];
                    if (o.tryLoc <= this.prev && n.call(o, "finallyLoc") && this.prev < o.finallyLoc) {
                        var i = o;
                        break;
                    }
                }
                i && ("break" === t || "continue" === t) && i.tryLoc <= e && e <= i.finallyLoc && (i = null);
                var a = i ? i.completion : {};
                return a.type = t, a.arg = e, i ? (this.method = "next", this.next = i.finallyLoc, y) : this.complete(a);
            },
            complete: function (t, e) {
                if ("throw" === t.type) throw t.arg;
                return "break" === t.type || "continue" === t.type ? this.next = t.arg : "return" === t.type ? (this.rval = this.arg = t.arg, this.method = "return", this.next = "end") : "normal" === t.type && e && (this.next = e), y;
            },
            finish: function (t) {
                for (var e = this.tryEntries.length - 1; e >= 0; --e) {
                    var r = this.tryEntries[e];
                    if (r.finallyLoc === t) return this.complete(r.completion, r.afterLoc), resetTryEntry(r), y;
                }
            },
            catch: function (t) {
                for (var e = this.tryEntries.length - 1; e >= 0; --e) {
                    var r = this.tryEntries[e];
                    if (r.tryLoc === t) {
                        var n = r.completion;
                        if ("throw" === n.type) {
                            var o = n.arg;
                            resetTryEntry(r);
                        }
                        return o;
                    }
                }
                throw new Error("illegal catch attempt");
            },
            delegateYield: function (e, r, n) {
                return this.delegate = {
                    iterator: values(e),
                    resultName: r,
                    nextLoc: n
                }, "next" === this.method && (this.arg = t), y;
            }
        }, e;
    }
    function _toPrimitive(t, r) {
        if ("object" != typeof t || !t) return t;
        var e = t[Symbol.toPrimitive];
        if (void 0 !== e) {
            var i = e.call(t, r || "default");
            if ("object" != typeof i) return i;
            throw new TypeError("@@toPrimitive must return a primitive value.");
        }
        return ("string" === r ? String : Number)(t);
    }
    function _toPropertyKey(t) {
        var i = _toPrimitive(t, "string");
        return "symbol" == typeof i ? i : String(i);
    }
    function _typeof(o) {
        "@babel/helpers - typeof";

        return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) {
            return typeof o;
        } : function (o) {
            return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o;
        }, _typeof(o);
    }
    function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) {
        try {
            var info = gen[key](arg);
            var value = info.value;
        } catch (error) {
            reject(error);
            return;
        }
        if (info.done) {
            resolve(value);
        } else {
            Promise.resolve(value).then(_next, _throw);
        }
    }
    function _asyncToGenerator(fn) {
        return function () {
            var self = this,
                args = arguments;
            return new Promise(function (resolve, reject) {
                var gen = fn.apply(self, args);
                function _next(value) {
                    asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value);
                }
                function _throw(err) {
                    asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err);
                }
                _next(undefined);
            });
        };
    }
    function _classCallCheck(instance, Constructor) {
        if (!(instance instanceof Constructor)) {
            throw new TypeError("Cannot call a class as a function");
        }
    }
    function _defineProperties(target, props) {
        for (var i = 0; i < props.length; i++) {
            var descriptor = props[i];
            descriptor.enumerable = descriptor.enumerable || false;
            descriptor.configurable = true;
            if ("value" in descriptor) descriptor.writable = true;
            Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor);
        }
    }
    function _createClass(Constructor, protoProps, staticProps) {
        if (protoProps) _defineProperties(Constructor.prototype, protoProps);
        if (staticProps) _defineProperties(Constructor, staticProps);
        Object.defineProperty(Constructor, "prototype", {
            writable: false
        });
        return Constructor;
    }
    function _defineProperty(obj, key, value) {
        key = _toPropertyKey(key);
        if (key in obj) {
            Object.defineProperty(obj, key, {
                value: value,
                enumerable: true,
                configurable: true,
                writable: true
            });
        } else {
            obj[key] = value;
        }
        return obj;
    }
    function _inherits(subClass, superClass) {
        if (typeof superClass !== "function" && superClass !== null) {
            throw new TypeError("Super expression must either be null or a function");
        }
        subClass.prototype = Object.create(superClass && superClass.prototype, {
            constructor: {
                value: subClass,
                writable: true,
                configurable: true
            }
        });
        Object.defineProperty(subClass, "prototype", {
            writable: false
        });
        if (superClass) _setPrototypeOf(subClass, superClass);
    }
    function _getPrototypeOf(o) {
        _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) {
            return o.__proto__ || Object.getPrototypeOf(o);
        };
        return _getPrototypeOf(o);
    }
    function _setPrototypeOf(o, p) {
        _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) {
            o.__proto__ = p;
            return o;
        };
        return _setPrototypeOf(o, p);
    }
    function _isNativeReflectConstruct() {
        if (typeof Reflect === "undefined" || !Reflect.construct) return false;
        if (Reflect.construct.sham) return false;
        if (typeof Proxy === "function") return true;
        try {
            Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {}));
            return true;
        } catch (e) {
            return false;
        }
    }
    function _construct(Parent, args, Class) {
        if (_isNativeReflectConstruct()) {
            _construct = Reflect.construct.bind();
        } else {
            _construct = function _construct(Parent, args, Class) {
                var a = [null];
                a.push.apply(a, args);
                var Constructor = Function.bind.apply(Parent, a);
                var instance = new Constructor();
                if (Class) _setPrototypeOf(instance, Class.prototype);
                return instance;
            };
        }
        return _construct.apply(null, arguments);
    }
    function _isNativeFunction(fn) {
        try {
            return Function.toString.call(fn).indexOf("[native code]") !== -1;
        } catch (e) {
            return typeof fn === "function";
        }
    }
    function _wrapNativeSuper(Class) {
        var _cache = typeof Map === "function" ? new Map() : undefined;
        _wrapNativeSuper = function _wrapNativeSuper(Class) {
            if (Class === null || !_isNativeFunction(Class)) return Class;
            if (typeof Class !== "function") {
                throw new TypeError("Super expression must either be null or a function");
            }
            if (typeof _cache !== "undefined") {
                if (_cache.has(Class)) return _cache.get(Class);
                _cache.set(Class, Wrapper);
            }
            function Wrapper() {
                return _construct(Class, arguments, _getPrototypeOf(this).constructor);
            }
            Wrapper.prototype = Object.create(Class.prototype, {
                constructor: {
                    value: Wrapper,
                    enumerable: false,
                    writable: true,
                    configurable: true
                }
            });
            return _setPrototypeOf(Wrapper, Class);
        };
        return _wrapNativeSuper(Class);
    }
    function _assertThisInitialized(self) {
        if (self === void 0) {
            throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
        }
        return self;
    }
    function _possibleConstructorReturn(self, call) {
        if (call && (typeof call === "object" || typeof call === "function")) {
            return call;
        } else if (call !== void 0) {
            throw new TypeError("Derived constructors may only return object or undefined");
        }
        return _assertThisInitialized(self);
    }
    function _createSuper(Derived) {
        var hasNativeReflectConstruct = _isNativeReflectConstruct();
        return function _createSuperInternal() {
            var Super = _getPrototypeOf(Derived),
                result;
            if (hasNativeReflectConstruct) {
                var NewTarget = _getPrototypeOf(this).constructor;
                result = Reflect.construct(Super, arguments, NewTarget);
            } else {
                result = Super.apply(this, arguments);
            }
            return _possibleConstructorReturn(this, result);
        };
    }
    function _slicedToArray(arr, i) {
        return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest();
    }
    function _arrayWithHoles(arr) {
        if (Array.isArray(arr)) return arr;
    }
    function _unsupportedIterableToArray(o, minLen) {
        if (!o) return;
        if (typeof o === "string") return _arrayLikeToArray(o, minLen);
        var n = Object.prototype.toString.call(o).slice(8, -1);
        if (n === "Object" && o.constructor) n = o.constructor.name;
        if (n === "Map" || n === "Set") return Array.from(o);
        if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen);
    }
    function _arrayLikeToArray(arr, len) {
        if (len == null || len > arr.length) len = arr.length;
        for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];
        return arr2;
    }
    function _nonIterableRest() {
        throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
    }
    function _createForOfIteratorHelper(o, allowArrayLike) {
        var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"];
        if (!it) {
            if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") {
                if (it) o = it;
                var i = 0;
                var F = function () {};
                return {
                    s: F,
                    n: function () {
                        if (i >= o.length) return {
                            done: true
                        };
                        return {
                            done: false,
                            value: o[i++]
                        };
                    },
                    e: function (e) {
                        throw e;
                    },
                    f: F
                };
            }
            throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
        }
        var normalCompletion = true,
            didErr = false,
            err;
        return {
            s: function () {
                it = it.call(o);
            },
            n: function () {
                var step = it.next();
                normalCompletion = step.done;
                return step;
            },
            e: function (e) {
                didErr = true;
                err = e;
            },
            f: function () {
                try {
                    if (!normalCompletion && it.return != null) it.return();
                } finally {
                    if (didErr) throw err;
                }
            }
        };
    }

    var MeiliSearchError = /*#__PURE__*/function (_Error) {
        _inherits(MeiliSearchError, _Error);
        var _super = _createSuper(MeiliSearchError);
        function MeiliSearchError() {
            var _this;
            _classCallCheck(this, MeiliSearchError);
            for (var _len = arguments.length, params = new Array(_len), _key = 0; _key < _len; _key++) {
                params[_key] = arguments[_key];
            }
            _this = _super.call.apply(_super, [this].concat(params));
            _defineProperty(_assertThisInitialized(_this), "name", 'MeiliSearchError');
            return _this;
        }
        return _createClass(MeiliSearchError);
    }( /*#__PURE__*/_wrapNativeSuper(Error));

    var MeiliSearchApiError = /*#__PURE__*/function (_MeiliSearchError) {
        _inherits(MeiliSearchApiError, _MeiliSearchError);
        var _super = _createSuper(MeiliSearchApiError);
        function MeiliSearchApiError(response, responseBody) {
            var _responseBody$message;
            var _this;
            _classCallCheck(this, MeiliSearchApiError);
            _this = _super.call(this, (_responseBody$message = responseBody === null || responseBody === void 0 ? void 0 : responseBody.message) !== null && _responseBody$message !== void 0 ? _responseBody$message : "".concat(response.status, ": ").concat(response.statusText));
            _defineProperty(_assertThisInitialized(_this), "name", 'MeiliSearchApiError');
            _defineProperty(_assertThisInitialized(_this), "cause", void 0);
            _defineProperty(_assertThisInitialized(_this), "response", void 0);
            _this.response = response;
            if (responseBody !== undefined) {
                _this.cause = responseBody;
            }
            return _this;
        }
        return _createClass(MeiliSearchApiError);
    }(MeiliSearchError);

    var MeiliSearchRequestError = /*#__PURE__*/function (_MeiliSearchError) {
        _inherits(MeiliSearchRequestError, _MeiliSearchError);
        var _super = _createSuper(MeiliSearchRequestError);
        function MeiliSearchRequestError(url, cause) {
            var _this;
            _classCallCheck(this, MeiliSearchRequestError);
            _this = _super.call(this, "Request to ".concat(url, " has failed"), {
                cause: cause
            });
            _defineProperty(_assertThisInitialized(_this), "name", 'MeiliSearchRequestError');
            return _this;
        }
        return _createClass(MeiliSearchRequestError);
    }(MeiliSearchError);

    var MeiliSearchTimeOutError = /*#__PURE__*/function (_MeiliSearchError) {
        _inherits(MeiliSearchTimeOutError, _MeiliSearchError);
        var _super = _createSuper(MeiliSearchTimeOutError);
        function MeiliSearchTimeOutError(message) {
            var _this;
            _classCallCheck(this, MeiliSearchTimeOutError);
            _this = _super.call(this, message);
            _defineProperty(_assertThisInitialized(_this), "name", 'MeiliSearchTimeOutError');
            return _this;
        }
        return _createClass(MeiliSearchTimeOutError);
    }(MeiliSearchError);

    function versionErrorHintMessage(message, method) {
        return "".concat(message, "\nHint: It might not be working because maybe you're not up to date with the Meilisearch version that ").concat(method, " call requires.");
    }

    /** Removes undefined entries from object */
    function removeUndefinedFromObject(obj) {
        return Object.entries(obj).reduce(function (acc, curEntry) {
            var _curEntry = _slicedToArray(curEntry, 2),
                key = _curEntry[0],
                val = _curEntry[1];
            if (val !== undefined) acc[key] = val;
            return acc;
        }, {});
    }
    function sleep(_x) {
        return _sleep.apply(this, arguments);
    }
    function _sleep() {
        _sleep = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee(ms) {
            return _regeneratorRuntime().wrap(function _callee$(_context) {
                while (1) switch (_context.prev = _context.next) {
                    case 0:
                        _context.next = 2;
                        return new Promise(function (resolve) {
                            return setTimeout(resolve, ms);
                        });
                    case 2:
                        return _context.abrupt("return", _context.sent);
                    case 3:
                    case "end":
                        return _context.stop();
                }
            }, _callee);
        }));
        return _sleep.apply(this, arguments);
    }
    function addProtocolIfNotPresent(host) {
        if (!(host.startsWith('https://') || host.startsWith('http://'))) {
            return "http://".concat(host);
        }
        return host;
    }
    function addTrailingSlash(url) {
        if (!url.endsWith('/')) {
            url += '/';
        }
        return url;
    }

    var PACKAGE_VERSION = '0.42.0';

    function toQueryParams(parameters) {
        var params = Object.keys(parameters);
        var queryParams = params.reduce(function (acc, key) {
            var value = parameters[key];
            if (value === undefined) {
                return acc;
            } else if (Array.isArray(value)) {
                return _objectSpread2(_objectSpread2({}, acc), {}, _defineProperty({}, key, value.join(',')));
            } else if (value instanceof Date) {
                return _objectSpread2(_objectSpread2({}, acc), {}, _defineProperty({}, key, value.toISOString()));
            }
            return _objectSpread2(_objectSpread2({}, acc), {}, _defineProperty({}, key, value));
        }, {});
        return queryParams;
    }
    function constructHostURL(host) {
        try {
            host = addProtocolIfNotPresent(host);
            host = addTrailingSlash(host);
            return host;
        } catch (e) {
            throw new MeiliSearchError('The provided host is not valid.');
        }
    }
    function cloneAndParseHeaders(headers) {
        if (Array.isArray(headers)) {
            return headers.reduce(function (acc, headerPair) {
                acc[headerPair[0]] = headerPair[1];
                return acc;
            }, {});
        } else if ('has' in headers) {
            var clonedHeaders = {};
            headers.forEach(function (value, key) {
                return clonedHeaders[key] = value;
            });
            return clonedHeaders;
        } else {
            return Object.assign({}, headers);
        }
    }
    function createHeaders(config) {
        var _config$requestConfig, _config$requestConfig2;
        var agentHeader = 'X-Meilisearch-Client';
        var packageAgent = "Meilisearch JavaScript (v".concat(PACKAGE_VERSION, ")");
        var contentType = 'Content-Type';
        var authorization = 'Authorization';
        var headers = cloneAndParseHeaders((_config$requestConfig = (_config$requestConfig2 = config.requestConfig) === null || _config$requestConfig2 === void 0 ? void 0 : _config$requestConfig2.headers) !== null && _config$requestConfig !== void 0 ? _config$requestConfig : {});
        // do not override if user provided the header
        if (config.apiKey && !headers[authorization]) {
            headers[authorization] = "Bearer ".concat(config.apiKey);
        }
        if (!headers[contentType]) {
            headers['Content-Type'] = 'application/json';
        }
        // Creates the custom user agent with information on the package used.
        if (config.clientAgents && Array.isArray(config.clientAgents)) {
            var clients = config.clientAgents.concat(packageAgent);
            headers[agentHeader] = clients.join(' ; ');
        } else if (config.clientAgents && !Array.isArray(config.clientAgents)) {
            // If the header is defined but not an array
            throw new MeiliSearchError("Meilisearch: The header \"".concat(agentHeader, "\" should be an array of string(s).\n"));
        } else {
            headers[agentHeader] = packageAgent;
        }
        return headers;
    }
    var HttpRequests = /*#__PURE__*/function () {
        function HttpRequests(config) {
            _classCallCheck(this, HttpRequests);
            _defineProperty(this, "headers", void 0);
            _defineProperty(this, "url", void 0);
            _defineProperty(this, "requestConfig", void 0);
            _defineProperty(this, "httpClient", void 0);
            _defineProperty(this, "requestTimeout", void 0);
            this.headers = createHeaders(config);
            this.requestConfig = config.requestConfig;
            this.httpClient = config.httpClient;
            this.requestTimeout = config.timeout;
            try {
                var host = constructHostURL(config.host);
                this.url = new URL(host);
            } catch (e) {
                throw new MeiliSearchError('The provided host is not valid.');
            }
        }
        _createClass(HttpRequests, [{
            key: "request",
            value: function () {
                var _request = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee(_ref) {
                    var _config$headers;
                    var method, url, params, body, _ref$config, config, constructURL, queryParams, headers, responsePromise, response, responseBody, parsedResponse;
                    return _regeneratorRuntime().wrap(function _callee$(_context) {
                        while (1) switch (_context.prev = _context.next) {
                            case 0:
                                method = _ref.method, url = _ref.url, params = _ref.params, body = _ref.body, _ref$config = _ref.config, config = _ref$config === void 0 ? {} : _ref$config;
                                if (typeof fetch === 'undefined') {
                                    require('cross-fetch/polyfill');
                                }
                                constructURL = new URL(url, this.url);
                                if (params) {
                                    queryParams = new URLSearchParams();
                                    Object.keys(params).filter(function (x) {
                                        return params[x] !== null;
                                    }).map(function (x) {
                                        return queryParams.set(x, params[x]);
                                    });
                                    constructURL.search = queryParams.toString();
                                }
                                // in case a custom content-type is provided
                                // do not stringify body
                                if (!((_config$headers = config.headers) !== null && _config$headers !== void 0 && _config$headers['Content-Type'])) {
                                    body = JSON.stringify(body);
                                }
                                headers = _objectSpread2(_objectSpread2({}, this.headers), config.headers);
                                responsePromise = this.fetchWithTimeout(constructURL.toString(), _objectSpread2(_objectSpread2(_objectSpread2({}, config), this.requestConfig), {}, {
                                    method: method,
                                    body: body,
                                    headers: headers
                                }), this.requestTimeout);
                                _context.next = 9;
                                return responsePromise.catch(function (error) {
                                    throw new MeiliSearchRequestError(constructURL.toString(), error);
                                });
                            case 9:
                                response = _context.sent;
                                if (!(this.httpClient !== undefined)) {
                                    _context.next = 12;
                                    break;
                                }
                                return _context.abrupt("return", response);
                            case 12:
                                _context.next = 14;
                                return response.text();
                            case 14:
                                responseBody = _context.sent;
                                parsedResponse = responseBody === '' ? undefined : JSON.parse(responseBody);
                                if (response.ok) {
                                    _context.next = 18;
                                    break;
                                }
                                throw new MeiliSearchApiError(response, parsedResponse);
                            case 18:
                                return _context.abrupt("return", parsedResponse);
                            case 19:
                            case "end":
                                return _context.stop();
                        }
                    }, _callee, this);
                }));
                function request(_x) {
                    return _request.apply(this, arguments);
                }
                return request;
            }()
        }, {
            key: "fetchWithTimeout",
            value: function () {
                var _fetchWithTimeout = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee2(url, options, timeout) {
                    var _this = this;
                    return _regeneratorRuntime().wrap(function _callee2$(_context2) {
                        while (1) switch (_context2.prev = _context2.next) {
                            case 0:
                                return _context2.abrupt("return", new Promise(function (resolve, reject) {
                                    var fetchFn = _this.httpClient ? _this.httpClient : fetch;
                                    var fetchPromise = fetchFn(url, options);
                                    var promises = [fetchPromise];
                                    // TimeoutPromise will not run if undefined or zero
                                    var timeoutId;
                                    if (timeout) {
                                        var timeoutPromise = new Promise(function (_, reject) {
                                            timeoutId = setTimeout(function () {
                                                reject(new Error('Error: Request Timed Out'));
                                            }, timeout);
                                        });
                                        promises.push(timeoutPromise);
                                    }
                                    Promise.race(promises).then(resolve).catch(reject).finally(function () {
                                        clearTimeout(timeoutId);
                                    });
                                }));
                            case 1:
                            case "end":
                                return _context2.stop();
                        }
                    }, _callee2);
                }));
                function fetchWithTimeout(_x2, _x3, _x4) {
                    return _fetchWithTimeout.apply(this, arguments);
                }
                return fetchWithTimeout;
            }()
        }, {
            key: "get",
            value: function () {
                var _get = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee3(url, params, config) {
                    return _regeneratorRuntime().wrap(function _callee3$(_context3) {
                        while (1) switch (_context3.prev = _context3.next) {
                            case 0:
                                _context3.next = 2;
                                return this.request({
                                    method: 'GET',
                                    url: url,
                                    params: params,
                                    config: config
                                });
                            case 2:
                                return _context3.abrupt("return", _context3.sent);
                            case 3:
                            case "end":
                                return _context3.stop();
                        }
                    }, _callee3, this);
                }));
                function get(_x5, _x6, _x7) {
                    return _get.apply(this, arguments);
                }
                return get;
            }()
        }, {
            key: "post",
            value: function () {
                var _post = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee4(url, data, params, config) {
                    return _regeneratorRuntime().wrap(function _callee4$(_context4) {
                        while (1) switch (_context4.prev = _context4.next) {
                            case 0:
                                _context4.next = 2;
                                return this.request({
                                    method: 'POST',
                                    url: url,
                                    body: data,
                                    params: params,
                                    config: config
                                });
                            case 2:
                                return _context4.abrupt("return", _context4.sent);
                            case 3:
                            case "end":
                                return _context4.stop();
                        }
                    }, _callee4, this);
                }));
                function post(_x8, _x9, _x10, _x11) {
                    return _post.apply(this, arguments);
                }
                return post;
            }()
        }, {
            key: "put",
            value: function () {
                var _put = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee5(url, data, params, config) {
                    return _regeneratorRuntime().wrap(function _callee5$(_context5) {
                        while (1) switch (_context5.prev = _context5.next) {
                            case 0:
                                _context5.next = 2;
                                return this.request({
                                    method: 'PUT',
                                    url: url,
                                    body: data,
                                    params: params,
                                    config: config
                                });
                            case 2:
                                return _context5.abrupt("return", _context5.sent);
                            case 3:
                            case "end":
                                return _context5.stop();
                        }
                    }, _callee5, this);
                }));
                function put(_x12, _x13, _x14, _x15) {
                    return _put.apply(this, arguments);
                }
                return put;
            }()
        }, {
            key: "patch",
            value: function () {
                var _patch = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee6(url, data, params, config) {
                    return _regeneratorRuntime().wrap(function _callee6$(_context6) {
                        while (1) switch (_context6.prev = _context6.next) {
                            case 0:
                                _context6.next = 2;
                                return this.request({
                                    method: 'PATCH',
                                    url: url,
                                    body: data,
                                    params: params,
                                    config: config
                                });
                            case 2:
                                return _context6.abrupt("return", _context6.sent);
                            case 3:
                            case "end":
                                return _context6.stop();
                        }
                    }, _callee6, this);
                }));
                function patch(_x16, _x17, _x18, _x19) {
                    return _patch.apply(this, arguments);
                }
                return patch;
            }()
        }, {
            key: "delete",
            value: function () {
                var _delete2 = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee7(url, data, params, config) {
                    return _regeneratorRuntime().wrap(function _callee7$(_context7) {
                        while (1) switch (_context7.prev = _context7.next) {
                            case 0:
                                _context7.next = 2;
                                return this.request({
                                    method: 'DELETE',
                                    url: url,
                                    body: data,
                                    params: params,
                                    config: config
                                });
                            case 2:
                                return _context7.abrupt("return", _context7.sent);
                            case 3:
                            case "end":
                                return _context7.stop();
                        }
                    }, _callee7, this);
                }));
                function _delete(_x20, _x21, _x22, _x23) {
                    return _delete2.apply(this, arguments);
                }
                return _delete;
            }()
        }]);
        return HttpRequests;
    }();

    var EnqueuedTask = /*#__PURE__*/_createClass(function EnqueuedTask(task) {
        _classCallCheck(this, EnqueuedTask);
        _defineProperty(this, "taskUid", void 0);
        _defineProperty(this, "indexUid", void 0);
        _defineProperty(this, "status", void 0);
        _defineProperty(this, "type", void 0);
        _defineProperty(this, "enqueuedAt", void 0);
        this.taskUid = task.taskUid;
        this.indexUid = task.indexUid;
        this.status = task.status;
        this.type = task.type;
        this.enqueuedAt = new Date(task.enqueuedAt);
    });

    var Task = /*#__PURE__*/_createClass(function Task(task) {
        _classCallCheck(this, Task);
        _defineProperty(this, "indexUid", void 0);
        _defineProperty(this, "status", void 0);
        _defineProperty(this, "type", void 0);
        _defineProperty(this, "uid", void 0);
        _defineProperty(this, "canceledBy", void 0);
        _defineProperty(this, "details", void 0);
        _defineProperty(this, "error", void 0);
        _defineProperty(this, "duration", void 0);
        _defineProperty(this, "startedAt", void 0);
        _defineProperty(this, "enqueuedAt", void 0);
        _defineProperty(this, "finishedAt", void 0);
        this.indexUid = task.indexUid;
        this.status = task.status;
        this.type = task.type;
        this.uid = task.uid;
        this.details = task.details;
        this.canceledBy = task.canceledBy;
        this.error = task.error;
        this.duration = task.duration;
        this.startedAt = new Date(task.startedAt);
        this.enqueuedAt = new Date(task.enqueuedAt);
        this.finishedAt = new Date(task.finishedAt);
    });
    var TaskClient = /*#__PURE__*/function () {
        function TaskClient(config) {
            _classCallCheck(this, TaskClient);
            _defineProperty(this, "httpRequest", void 0);
            this.httpRequest = new HttpRequests(config);
        }
        /**
         * Get one task
         *
         * @param uid - Unique identifier of the task
         * @returns
         */
        _createClass(TaskClient, [{
            key: "getTask",
            value: (function () {
                    var _getTask = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee(uid) {
                        var url, taskItem;
                        return _regeneratorRuntime().wrap(function _callee$(_context) {
                            while (1) switch (_context.prev = _context.next) {
                                case 0:
                                    url = "tasks/".concat(uid);
                                    _context.next = 3;
                                    return this.httpRequest.get(url);
                                case 3:
                                    taskItem = _context.sent;
                                    return _context.abrupt("return", new Task(taskItem));
                                case 5:
                                case "end":
                                    return _context.stop();
                            }
                        }, _callee, this);
                    }));
                    function getTask(_x) {
                        return _getTask.apply(this, arguments);
                    }
                    return getTask;
                }()
                /**
                 * Get tasks
                 *
                 * @param parameters - Parameters to browse the tasks
                 * @returns Promise containing all tasks
                 */
            )
        }, {
            key: "getTasks",
            value: (function () {
                    var _getTasks = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee2() {
                        var parameters,
                            url,
                            tasks,
                            _args2 = arguments;
                        return _regeneratorRuntime().wrap(function _callee2$(_context2) {
                            while (1) switch (_context2.prev = _context2.next) {
                                case 0:
                                    parameters = _args2.length > 0 && _args2[0] !== undefined ? _args2[0] : {};
                                    url = "tasks";
                                    _context2.next = 4;
                                    return this.httpRequest.get(url, toQueryParams(parameters));
                                case 4:
                                    tasks = _context2.sent;
                                    return _context2.abrupt("return", _objectSpread2(_objectSpread2({}, tasks), {}, {
                                        results: tasks.results.map(function (task) {
                                            return new Task(task);
                                        })
                                    }));
                                case 6:
                                case "end":
                                    return _context2.stop();
                            }
                        }, _callee2, this);
                    }));
                    function getTasks() {
                        return _getTasks.apply(this, arguments);
                    }
                    return getTasks;
                }()
                /**
                 * Wait for a task to be processed.
                 *
                 * @param taskUid - Task identifier
                 * @param options - Additional configuration options
                 * @returns Promise returning a task after it has been processed
                 */
            )
        }, {
            key: "waitForTask",
            value: (function () {
                    var _waitForTask = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee3(taskUid) {
                        var _ref,
                            _ref$timeOutMs,
                            timeOutMs,
                            _ref$intervalMs,
                            intervalMs,
                            startingTime,
                            response,
                            _args3 = arguments;
                        return _regeneratorRuntime().wrap(function _callee3$(_context3) {
                            while (1) switch (_context3.prev = _context3.next) {
                                case 0:
                                    _ref = _args3.length > 1 && _args3[1] !== undefined ? _args3[1] : {}, _ref$timeOutMs = _ref.timeOutMs, timeOutMs = _ref$timeOutMs === void 0 ? 5000 : _ref$timeOutMs, _ref$intervalMs = _ref.intervalMs, intervalMs = _ref$intervalMs === void 0 ? 50 : _ref$intervalMs;
                                    startingTime = Date.now();
                                case 2:
                                    if (!(Date.now() - startingTime < timeOutMs)) {
                                        _context3.next = 12;
                                        break;
                                    }
                                    _context3.next = 5;
                                    return this.getTask(taskUid);
                                case 5:
                                    response = _context3.sent;
                                    if ([TaskStatus.TASK_ENQUEUED, TaskStatus.TASK_PROCESSING].includes(response.status)) {
                                        _context3.next = 8;
                                        break;
                                    }
                                    return _context3.abrupt("return", response);
                                case 8:
                                    _context3.next = 10;
                                    return sleep(intervalMs);
                                case 10:
                                    _context3.next = 2;
                                    break;
                                case 12:
                                    throw new MeiliSearchTimeOutError("timeout of ".concat(timeOutMs, "ms has exceeded on process ").concat(taskUid, " when waiting a task to be resolved."));
                                case 13:
                                case "end":
                                    return _context3.stop();
                            }
                        }, _callee3, this);
                    }));
                    function waitForTask(_x2) {
                        return _waitForTask.apply(this, arguments);
                    }
                    return waitForTask;
                }()
                /**
                 * Waits for multiple tasks to be processed
                 *
                 * @param taskUids - Tasks identifier list
                 * @param options - Wait options
                 * @returns Promise returning a list of tasks after they have been processed
                 */
            )
        }, {
            key: "waitForTasks",
            value: (function () {
                    var _waitForTasks = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee4(taskUids) {
                        var _ref2,
                            _ref2$timeOutMs,
                            timeOutMs,
                            _ref2$intervalMs,
                            intervalMs,
                            tasks,
                            _iterator,
                            _step,
                            taskUid,
                            task,
                            _args4 = arguments;
                        return _regeneratorRuntime().wrap(function _callee4$(_context4) {
                            while (1) switch (_context4.prev = _context4.next) {
                                case 0:
                                    _ref2 = _args4.length > 1 && _args4[1] !== undefined ? _args4[1] : {}, _ref2$timeOutMs = _ref2.timeOutMs, timeOutMs = _ref2$timeOutMs === void 0 ? 5000 : _ref2$timeOutMs, _ref2$intervalMs = _ref2.intervalMs, intervalMs = _ref2$intervalMs === void 0 ? 50 : _ref2$intervalMs;
                                    tasks = [];
                                    _iterator = _createForOfIteratorHelper(taskUids);
                                    _context4.prev = 3;
                                    _iterator.s();
                                case 5:
                                    if ((_step = _iterator.n()).done) {
                                        _context4.next = 13;
                                        break;
                                    }
                                    taskUid = _step.value;
                                    _context4.next = 9;
                                    return this.waitForTask(taskUid, {
                                        timeOutMs: timeOutMs,
                                        intervalMs: intervalMs
                                    });
                                case 9:
                                    task = _context4.sent;
                                    tasks.push(task);
                                case 11:
                                    _context4.next = 5;
                                    break;
                                case 13:
                                    _context4.next = 18;
                                    break;
                                case 15:
                                    _context4.prev = 15;
                                    _context4.t0 = _context4["catch"](3);
                                    _iterator.e(_context4.t0);
                                case 18:
                                    _context4.prev = 18;
                                    _iterator.f();
                                    return _context4.finish(18);
                                case 21:
                                    return _context4.abrupt("return", tasks);
                                case 22:
                                case "end":
                                    return _context4.stop();
                            }
                        }, _callee4, this, [[3, 15, 18, 21]]);
                    }));
                    function waitForTasks(_x3) {
                        return _waitForTasks.apply(this, arguments);
                    }
                    return waitForTasks;
                }()
                /**
                 * Cancel a list of enqueued or processing tasks.
                 *
                 * @param parameters - Parameters to filter the tasks.
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "cancelTasks",
            value: (function () {
                    var _cancelTasks = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee5() {
                        var parameters,
                            url,
                            task,
                            _args5 = arguments;
                        return _regeneratorRuntime().wrap(function _callee5$(_context5) {
                            while (1) switch (_context5.prev = _context5.next) {
                                case 0:
                                    parameters = _args5.length > 0 && _args5[0] !== undefined ? _args5[0] : {};
                                    url = "tasks/cancel";
                                    _context5.next = 4;
                                    return this.httpRequest.post(url, {}, toQueryParams(parameters));
                                case 4:
                                    task = _context5.sent;
                                    return _context5.abrupt("return", new EnqueuedTask(task));
                                case 6:
                                case "end":
                                    return _context5.stop();
                            }
                        }, _callee5, this);
                    }));
                    function cancelTasks() {
                        return _cancelTasks.apply(this, arguments);
                    }
                    return cancelTasks;
                }()
                /**
                 * Delete a list tasks.
                 *
                 * @param parameters - Parameters to filter the tasks.
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "deleteTasks",
            value: (function () {
                var _deleteTasks = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee6() {
                    var parameters,
                        url,
                        task,
                        _args6 = arguments;
                    return _regeneratorRuntime().wrap(function _callee6$(_context6) {
                        while (1) switch (_context6.prev = _context6.next) {
                            case 0:
                                parameters = _args6.length > 0 && _args6[0] !== undefined ? _args6[0] : {};
                                url = "tasks";
                                _context6.next = 4;
                                return this.httpRequest.delete(url, {}, toQueryParams(parameters));
                            case 4:
                                task = _context6.sent;
                                return _context6.abrupt("return", new EnqueuedTask(task));
                            case 6:
                            case "end":
                                return _context6.stop();
                        }
                    }, _callee6, this);
                }));
                function deleteTasks() {
                    return _deleteTasks.apply(this, arguments);
                }
                return deleteTasks;
            }())
        }]);
        return TaskClient;
    }();

    /*
   * Bundle: MeiliSearch / Indexes
   * Project: MeiliSearch - Javascript API
   * Author: Quentin de Quelen <quentin@meilisearch.com>
   * Copyright: 2019, MeiliSearch
   */
    var Index = /*#__PURE__*/function () {
        /**
         * @param config - Request configuration options
         * @param uid - UID of the index
         * @param primaryKey - Primary Key of the index
         */
        function Index(config, uid, primaryKey) {
            _classCallCheck(this, Index);
            _defineProperty(this, "uid", void 0);
            _defineProperty(this, "primaryKey", void 0);
            _defineProperty(this, "createdAt", void 0);
            _defineProperty(this, "updatedAt", void 0);
            _defineProperty(this, "httpRequest", void 0);
            _defineProperty(this, "tasks", void 0);
            this.uid = uid;
            this.primaryKey = primaryKey;
            this.httpRequest = new HttpRequests(config);
            this.tasks = new TaskClient(config);
        }
        ///
        /// SEARCH
        ///
        /**
         * Search for documents into an index
         *
         * @param query - Query string
         * @param options - Search options
         * @param config - Additional request configuration options
         * @returns Promise containing the search response
         */
        _createClass(Index, [{
            key: "search",
            value: function () {
                var _search = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee(query, options, config) {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee$(_context) {
                        while (1) switch (_context.prev = _context.next) {
                            case 0:
                                url = "indexes/".concat(this.uid, "/search");
                                _context.next = 3;
                                return this.httpRequest.post(url, removeUndefinedFromObject(_objectSpread2({
                                    q: query
                                }, options)), undefined, config);
                            case 3:
                                return _context.abrupt("return", _context.sent);
                            case 4:
                            case "end":
                                return _context.stop();
                        }
                    }, _callee, this);
                }));
                function search(_x, _x2, _x3) {
                    return _search.apply(this, arguments);
                }
                return search;
            }()
            /**
             * Search for documents into an index using the GET method
             *
             * @param query - Query string
             * @param options - Search options
             * @param config - Additional request configuration options
             * @returns Promise containing the search response
             */
        }, {
            key: "searchGet",
            value: (function () {
                    var _searchGet = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee2(query, options, config) {
                        var _options$sort, _options$facets, _options$attributesTo, _options$attributesTo2, _options$attributesTo3, _options$vector, _options$attributesTo4;
                        var url, parseFilter, getParams;
                        return _regeneratorRuntime().wrap(function _callee2$(_context2) {
                            while (1) switch (_context2.prev = _context2.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/search");
                                    parseFilter = function parseFilter(filter) {
                                        if (typeof filter === 'string') return filter;else if (Array.isArray(filter)) throw new MeiliSearchError('The filter query parameter should be in string format when using searchGet');else return undefined;
                                    };
                                    getParams = _objectSpread2(_objectSpread2({
                                        q: query
                                    }, options), {}, {
                                        filter: parseFilter(options === null || options === void 0 ? void 0 : options.filter),
                                        sort: options === null || options === void 0 ? void 0 : (_options$sort = options.sort) === null || _options$sort === void 0 ? void 0 : _options$sort.join(','),
                                        facets: options === null || options === void 0 ? void 0 : (_options$facets = options.facets) === null || _options$facets === void 0 ? void 0 : _options$facets.join(','),
                                        attributesToRetrieve: options === null || options === void 0 ? void 0 : (_options$attributesTo = options.attributesToRetrieve) === null || _options$attributesTo === void 0 ? void 0 : _options$attributesTo.join(','),
                                        attributesToCrop: options === null || options === void 0 ? void 0 : (_options$attributesTo2 = options.attributesToCrop) === null || _options$attributesTo2 === void 0 ? void 0 : _options$attributesTo2.join(','),
                                        attributesToHighlight: options === null || options === void 0 ? void 0 : (_options$attributesTo3 = options.attributesToHighlight) === null || _options$attributesTo3 === void 0 ? void 0 : _options$attributesTo3.join(','),
                                        vector: options === null || options === void 0 ? void 0 : (_options$vector = options.vector) === null || _options$vector === void 0 ? void 0 : _options$vector.join(','),
                                        attributesToSearchOn: options === null || options === void 0 ? void 0 : (_options$attributesTo4 = options.attributesToSearchOn) === null || _options$attributesTo4 === void 0 ? void 0 : _options$attributesTo4.join(',')
                                    });
                                    _context2.next = 5;
                                    return this.httpRequest.get(url, removeUndefinedFromObject(getParams), config);
                                case 5:
                                    return _context2.abrupt("return", _context2.sent);
                                case 6:
                                case "end":
                                    return _context2.stop();
                            }
                        }, _callee2, this);
                    }));
                    function searchGet(_x4, _x5, _x6) {
                        return _searchGet.apply(this, arguments);
                    }
                    return searchGet;
                }()
                /**
                 * Search for facet values
                 *
                 * @param params - Parameters used to search on the facets
                 * @param config - Additional request configuration options
                 * @returns Promise containing the search response
                 */
            )
        }, {
            key: "searchForFacetValues",
            value: (function () {
                    var _searchForFacetValues = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee3(params, config) {
                        var url;
                        return _regeneratorRuntime().wrap(function _callee3$(_context3) {
                            while (1) switch (_context3.prev = _context3.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/facet-search");
                                    _context3.next = 3;
                                    return this.httpRequest.post(url, removeUndefinedFromObject(params), undefined, config);
                                case 3:
                                    return _context3.abrupt("return", _context3.sent);
                                case 4:
                                case "end":
                                    return _context3.stop();
                            }
                        }, _callee3, this);
                    }));
                    function searchForFacetValues(_x7, _x8) {
                        return _searchForFacetValues.apply(this, arguments);
                    }
                    return searchForFacetValues;
                }()
                /**
                 * Search for similar documents
                 *
                 * @param params - Parameters used to search for similar documents
                 * @returns Promise containing the search response
                 */
            )
        }, {
            key: "searchSimilarDocuments",
            value: (function () {
                    var _searchSimilarDocuments = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee4(params) {
                        var url;
                        return _regeneratorRuntime().wrap(function _callee4$(_context4) {
                            while (1) switch (_context4.prev = _context4.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/similar");
                                    _context4.next = 3;
                                    return this.httpRequest.post(url, removeUndefinedFromObject(params), undefined);
                                case 3:
                                    return _context4.abrupt("return", _context4.sent);
                                case 4:
                                case "end":
                                    return _context4.stop();
                            }
                        }, _callee4, this);
                    }));
                    function searchSimilarDocuments(_x9) {
                        return _searchSimilarDocuments.apply(this, arguments);
                    }
                    return searchSimilarDocuments;
                }() ///
                /// INDEX
                ///
                /**
                 * Get index information.
                 *
                 * @returns Promise containing index information
                 */
            )
        }, {
            key: "getRawInfo",
            value: function () {
                var _getRawInfo = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee5() {
                    var url, res;
                    return _regeneratorRuntime().wrap(function _callee5$(_context5) {
                        while (1) switch (_context5.prev = _context5.next) {
                            case 0:
                                url = "indexes/".concat(this.uid);
                                _context5.next = 3;
                                return this.httpRequest.get(url);
                            case 3:
                                res = _context5.sent;
                                this.primaryKey = res.primaryKey;
                                this.updatedAt = new Date(res.updatedAt);
                                this.createdAt = new Date(res.createdAt);
                                return _context5.abrupt("return", res);
                            case 8:
                            case "end":
                                return _context5.stop();
                        }
                    }, _callee5, this);
                }));
                function getRawInfo() {
                    return _getRawInfo.apply(this, arguments);
                }
                return getRawInfo;
            }()
            /**
             * Fetch and update Index information.
             *
             * @returns Promise to the current Index object with updated information
             */
        }, {
            key: "fetchInfo",
            value: (function () {
                    var _fetchInfo = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee6() {
                        return _regeneratorRuntime().wrap(function _callee6$(_context6) {
                            while (1) switch (_context6.prev = _context6.next) {
                                case 0:
                                    _context6.next = 2;
                                    return this.getRawInfo();
                                case 2:
                                    return _context6.abrupt("return", this);
                                case 3:
                                case "end":
                                    return _context6.stop();
                            }
                        }, _callee6, this);
                    }));
                    function fetchInfo() {
                        return _fetchInfo.apply(this, arguments);
                    }
                    return fetchInfo;
                }()
                /**
                 * Get Primary Key.
                 *
                 * @returns Promise containing the Primary Key of the index
                 */
            )
        }, {
            key: "fetchPrimaryKey",
            value: (function () {
                    var _fetchPrimaryKey = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee7() {
                        return _regeneratorRuntime().wrap(function _callee7$(_context7) {
                            while (1) switch (_context7.prev = _context7.next) {
                                case 0:
                                    _context7.next = 2;
                                    return this.getRawInfo();
                                case 2:
                                    this.primaryKey = _context7.sent.primaryKey;
                                    return _context7.abrupt("return", this.primaryKey);
                                case 4:
                                case "end":
                                    return _context7.stop();
                            }
                        }, _callee7, this);
                    }));
                    function fetchPrimaryKey() {
                        return _fetchPrimaryKey.apply(this, arguments);
                    }
                    return fetchPrimaryKey;
                }()
                /**
                 * Create an index.
                 *
                 * @param uid - Unique identifier of the Index
                 * @param options - Index options
                 * @param config - Request configuration options
                 * @returns Newly created Index object
                 */
            )
        }, {
            key: "update",
            value: (
                /**
                 * Update an index.
                 *
                 * @param data - Data to update
                 * @returns Promise to the current Index object with updated information
                 */
                function () {
                    var _update = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee8(data) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee8$(_context8) {
                            while (1) switch (_context8.prev = _context8.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid);
                                    _context8.next = 3;
                                    return this.httpRequest.patch(url, data);
                                case 3:
                                    task = _context8.sent;
                                    task.enqueuedAt = new Date(task.enqueuedAt);
                                    return _context8.abrupt("return", task);
                                case 6:
                                case "end":
                                    return _context8.stop();
                            }
                        }, _callee8, this);
                    }));
                    function update(_x10) {
                        return _update.apply(this, arguments);
                    }
                    return update;
                }()
                /**
                 * Delete an index.
                 *
                 * @returns Promise which resolves when index is deleted successfully
                 */
            )
        }, {
            key: "delete",
            value: (function () {
                    var _delete2 = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee9() {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee9$(_context9) {
                            while (1) switch (_context9.prev = _context9.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid);
                                    _context9.next = 3;
                                    return this.httpRequest.delete(url);
                                case 3:
                                    task = _context9.sent;
                                    return _context9.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context9.stop();
                            }
                        }, _callee9, this);
                    }));
                    function _delete() {
                        return _delete2.apply(this, arguments);
                    }
                    return _delete;
                }() ///
                /// TASKS
                ///
                /**
                 * Get the list of all the tasks of the index.
                 *
                 * @param parameters - Parameters to browse the tasks
                 * @returns Promise containing all tasks
                 */
            )
        }, {
            key: "getTasks",
            value: function () {
                var _getTasks = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee10() {
                    var parameters,
                        _args10 = arguments;
                    return _regeneratorRuntime().wrap(function _callee10$(_context10) {
                        while (1) switch (_context10.prev = _context10.next) {
                            case 0:
                                parameters = _args10.length > 0 && _args10[0] !== undefined ? _args10[0] : {};
                                _context10.next = 3;
                                return this.tasks.getTasks(_objectSpread2(_objectSpread2({}, parameters), {}, {
                                    indexUids: [this.uid]
                                }));
                            case 3:
                                return _context10.abrupt("return", _context10.sent);
                            case 4:
                            case "end":
                                return _context10.stop();
                        }
                    }, _callee10, this);
                }));
                function getTasks() {
                    return _getTasks.apply(this, arguments);
                }
                return getTasks;
            }()
            /**
             * Get one task of the index.
             *
             * @param taskUid - Task identifier
             * @returns Promise containing a task
             */
        }, {
            key: "getTask",
            value: (function () {
                    var _getTask = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee11(taskUid) {
                        return _regeneratorRuntime().wrap(function _callee11$(_context11) {
                            while (1) switch (_context11.prev = _context11.next) {
                                case 0:
                                    _context11.next = 2;
                                    return this.tasks.getTask(taskUid);
                                case 2:
                                    return _context11.abrupt("return", _context11.sent);
                                case 3:
                                case "end":
                                    return _context11.stop();
                            }
                        }, _callee11, this);
                    }));
                    function getTask(_x11) {
                        return _getTask.apply(this, arguments);
                    }
                    return getTask;
                }()
                /**
                 * Wait for multiple tasks to be processed.
                 *
                 * @param taskUids - Tasks identifier
                 * @param waitOptions - Options on timeout and interval
                 * @returns Promise containing an array of tasks
                 */
            )
        }, {
            key: "waitForTasks",
            value: (function () {
                    var _waitForTasks = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee12(taskUids) {
                        var _ref,
                            _ref$timeOutMs,
                            timeOutMs,
                            _ref$intervalMs,
                            intervalMs,
                            _args12 = arguments;
                        return _regeneratorRuntime().wrap(function _callee12$(_context12) {
                            while (1) switch (_context12.prev = _context12.next) {
                                case 0:
                                    _ref = _args12.length > 1 && _args12[1] !== undefined ? _args12[1] : {}, _ref$timeOutMs = _ref.timeOutMs, timeOutMs = _ref$timeOutMs === void 0 ? 5000 : _ref$timeOutMs, _ref$intervalMs = _ref.intervalMs, intervalMs = _ref$intervalMs === void 0 ? 50 : _ref$intervalMs;
                                    _context12.next = 3;
                                    return this.tasks.waitForTasks(taskUids, {
                                        timeOutMs: timeOutMs,
                                        intervalMs: intervalMs
                                    });
                                case 3:
                                    return _context12.abrupt("return", _context12.sent);
                                case 4:
                                case "end":
                                    return _context12.stop();
                            }
                        }, _callee12, this);
                    }));
                    function waitForTasks(_x12) {
                        return _waitForTasks.apply(this, arguments);
                    }
                    return waitForTasks;
                }()
                /**
                 * Wait for a task to be processed.
                 *
                 * @param taskUid - Task identifier
                 * @param waitOptions - Options on timeout and interval
                 * @returns Promise containing an array of tasks
                 */
            )
        }, {
            key: "waitForTask",
            value: (function () {
                    var _waitForTask = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee13(taskUid) {
                        var _ref2,
                            _ref2$timeOutMs,
                            timeOutMs,
                            _ref2$intervalMs,
                            intervalMs,
                            _args13 = arguments;
                        return _regeneratorRuntime().wrap(function _callee13$(_context13) {
                            while (1) switch (_context13.prev = _context13.next) {
                                case 0:
                                    _ref2 = _args13.length > 1 && _args13[1] !== undefined ? _args13[1] : {}, _ref2$timeOutMs = _ref2.timeOutMs, timeOutMs = _ref2$timeOutMs === void 0 ? 5000 : _ref2$timeOutMs, _ref2$intervalMs = _ref2.intervalMs, intervalMs = _ref2$intervalMs === void 0 ? 50 : _ref2$intervalMs;
                                    _context13.next = 3;
                                    return this.tasks.waitForTask(taskUid, {
                                        timeOutMs: timeOutMs,
                                        intervalMs: intervalMs
                                    });
                                case 3:
                                    return _context13.abrupt("return", _context13.sent);
                                case 4:
                                case "end":
                                    return _context13.stop();
                            }
                        }, _callee13, this);
                    }));
                    function waitForTask(_x13) {
                        return _waitForTask.apply(this, arguments);
                    }
                    return waitForTask;
                }() ///
                /// STATS
                ///
                /**
                 * Get stats of an index
                 *
                 * @returns Promise containing object with stats of the index
                 */
            )
        }, {
            key: "getStats",
            value: function () {
                var _getStats = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee14() {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee14$(_context14) {
                        while (1) switch (_context14.prev = _context14.next) {
                            case 0:
                                url = "indexes/".concat(this.uid, "/stats");
                                _context14.next = 3;
                                return this.httpRequest.get(url);
                            case 3:
                                return _context14.abrupt("return", _context14.sent);
                            case 4:
                            case "end":
                                return _context14.stop();
                        }
                    }, _callee14, this);
                }));
                function getStats() {
                    return _getStats.apply(this, arguments);
                }
                return getStats;
            }() ///
            /// DOCUMENTS
            ///
            /**
             * Get documents of an index.
             *
             * @param parameters - Parameters to browse the documents. Parameters can
             *   contain the `filter` field only available in Meilisearch v1.2 and newer
             * @returns Promise containing the returned documents
             */
        }, {
            key: "getDocuments",
            value: function () {
                var _getDocuments = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee15() {
                    var parameters,
                        url,
                        _parameters,
                        _parameters2,
                        _parameters2$fields,
                        _url,
                        fields,
                        _args15 = arguments;
                    return _regeneratorRuntime().wrap(function _callee15$(_context15) {
                        while (1) switch (_context15.prev = _context15.next) {
                            case 0:
                                parameters = _args15.length > 0 && _args15[0] !== undefined ? _args15[0] : {};
                                parameters = removeUndefinedFromObject(parameters);
                                // In case `filter` is provided, use `POST /documents/fetch`
                                if (!(parameters.filter !== undefined)) {
                                    _context15.next = 16;
                                    break;
                                }
                                _context15.prev = 3;
                                url = "indexes/".concat(this.uid, "/documents/fetch");
                                _context15.next = 7;
                                return this.httpRequest.post(url, parameters);
                            case 7:
                                return _context15.abrupt("return", _context15.sent);
                            case 10:
                                _context15.prev = 10;
                                _context15.t0 = _context15["catch"](3);
                                if (_context15.t0 instanceof MeiliSearchRequestError) {
                                    _context15.t0.message = versionErrorHintMessage(_context15.t0.message, 'getDocuments');
                                } else if (_context15.t0 instanceof MeiliSearchApiError) {
                                    _context15.t0.message = versionErrorHintMessage(_context15.t0.message, 'getDocuments');
                                }
                                throw _context15.t0;
                            case 14:
                                _context15.next = 21;
                                break;
                            case 16:
                                _url = "indexes/".concat(this.uid, "/documents"); // Transform fields to query parameter string format
                                fields = Array.isArray((_parameters = parameters) === null || _parameters === void 0 ? void 0 : _parameters.fields) ? {
                                    fields: (_parameters2 = parameters) === null || _parameters2 === void 0 ? void 0 : (_parameters2$fields = _parameters2.fields) === null || _parameters2$fields === void 0 ? void 0 : _parameters2$fields.join(',')
                                } : {};
                                _context15.next = 20;
                                return this.httpRequest.get(_url, _objectSpread2(_objectSpread2({}, parameters), fields));
                            case 20:
                                return _context15.abrupt("return", _context15.sent);
                            case 21:
                            case "end":
                                return _context15.stop();
                        }
                    }, _callee15, this, [[3, 10]]);
                }));
                function getDocuments() {
                    return _getDocuments.apply(this, arguments);
                }
                return getDocuments;
            }()
            /**
             * Get one document
             *
             * @param documentId - Document ID
             * @param parameters - Parameters applied on a document
             * @returns Promise containing Document response
             */
        }, {
            key: "getDocument",
            value: (function () {
                    var _getDocument = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee16(documentId, parameters) {
                        var url, fields;
                        return _regeneratorRuntime().wrap(function _callee16$(_context16) {
                            while (1) switch (_context16.prev = _context16.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/documents/").concat(documentId);
                                    fields = function () {
                                        if (Array.isArray(parameters === null || parameters === void 0 ? void 0 : parameters.fields)) {
                                            var _parameters$fields;
                                            return parameters === null || parameters === void 0 ? void 0 : (_parameters$fields = parameters.fields) === null || _parameters$fields === void 0 ? void 0 : _parameters$fields.join(',');
                                        }
                                        return undefined;
                                    }();
                                    _context16.next = 4;
                                    return this.httpRequest.get(url, removeUndefinedFromObject(_objectSpread2(_objectSpread2({}, parameters), {}, {
                                        fields: fields
                                    })));
                                case 4:
                                    return _context16.abrupt("return", _context16.sent);
                                case 5:
                                case "end":
                                    return _context16.stop();
                            }
                        }, _callee16, this);
                    }));
                    function getDocument(_x14, _x15) {
                        return _getDocument.apply(this, arguments);
                    }
                    return getDocument;
                }()
                /**
                 * Add or replace multiples documents to an index
                 *
                 * @param documents - Array of Document objects to add/replace
                 * @param options - Options on document addition
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "addDocuments",
            value: (function () {
                    var _addDocuments = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee17(documents, options) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee17$(_context17) {
                            while (1) switch (_context17.prev = _context17.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/documents");
                                    _context17.next = 3;
                                    return this.httpRequest.post(url, documents, options);
                                case 3:
                                    task = _context17.sent;
                                    return _context17.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context17.stop();
                            }
                        }, _callee17, this);
                    }));
                    function addDocuments(_x16, _x17) {
                        return _addDocuments.apply(this, arguments);
                    }
                    return addDocuments;
                }()
                /**
                 * Add or replace multiples documents in a string format to an index. It only
                 * supports csv, ndjson and json formats.
                 *
                 * @param documents - Documents provided in a string to add/replace
                 * @param contentType - Content type of your document:
                 *   'text/csv'|'application/x-ndjson'|'application/json'
                 * @param options - Options on document addition
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "addDocumentsFromString",
            value: (function () {
                    var _addDocumentsFromString = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee18(documents, contentType, queryParams) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee18$(_context18) {
                            while (1) switch (_context18.prev = _context18.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/documents");
                                    _context18.next = 3;
                                    return this.httpRequest.post(url, documents, queryParams, {
                                        headers: {
                                            'Content-Type': contentType
                                        }
                                    });
                                case 3:
                                    task = _context18.sent;
                                    return _context18.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context18.stop();
                            }
                        }, _callee18, this);
                    }));
                    function addDocumentsFromString(_x18, _x19, _x20) {
                        return _addDocumentsFromString.apply(this, arguments);
                    }
                    return addDocumentsFromString;
                }()
                /**
                 * Add or replace multiples documents to an index in batches
                 *
                 * @param documents - Array of Document objects to add/replace
                 * @param batchSize - Size of the batch
                 * @param options - Options on document addition
                 * @returns Promise containing array of enqueued task objects for each batch
                 */
            )
        }, {
            key: "addDocumentsInBatches",
            value: (function () {
                    var _addDocumentsInBatches = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee19(documents) {
                        var batchSize,
                            options,
                            updates,
                            i,
                            _args19 = arguments;
                        return _regeneratorRuntime().wrap(function _callee19$(_context19) {
                            while (1) switch (_context19.prev = _context19.next) {
                                case 0:
                                    batchSize = _args19.length > 1 && _args19[1] !== undefined ? _args19[1] : 1000;
                                    options = _args19.length > 2 ? _args19[2] : undefined;
                                    updates = [];
                                    i = 0;
                                case 4:
                                    if (!(i < documents.length)) {
                                        _context19.next = 13;
                                        break;
                                    }
                                    _context19.t0 = updates;
                                    _context19.next = 8;
                                    return this.addDocuments(documents.slice(i, i + batchSize), options);
                                case 8:
                                    _context19.t1 = _context19.sent;
                                    _context19.t0.push.call(_context19.t0, _context19.t1);
                                case 10:
                                    i += batchSize;
                                    _context19.next = 4;
                                    break;
                                case 13:
                                    return _context19.abrupt("return", updates);
                                case 14:
                                case "end":
                                    return _context19.stop();
                            }
                        }, _callee19, this);
                    }));
                    function addDocumentsInBatches(_x21) {
                        return _addDocumentsInBatches.apply(this, arguments);
                    }
                    return addDocumentsInBatches;
                }()
                /**
                 * Add or update multiples documents to an index
                 *
                 * @param documents - Array of Document objects to add/update
                 * @param options - Options on document update
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "updateDocuments",
            value: (function () {
                    var _updateDocuments = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee20(documents, options) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee20$(_context20) {
                            while (1) switch (_context20.prev = _context20.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/documents");
                                    _context20.next = 3;
                                    return this.httpRequest.put(url, documents, options);
                                case 3:
                                    task = _context20.sent;
                                    return _context20.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context20.stop();
                            }
                        }, _callee20, this);
                    }));
                    function updateDocuments(_x22, _x23) {
                        return _updateDocuments.apply(this, arguments);
                    }
                    return updateDocuments;
                }()
                /**
                 * Add or update multiples documents to an index in batches
                 *
                 * @param documents - Array of Document objects to add/update
                 * @param batchSize - Size of the batch
                 * @param options - Options on document update
                 * @returns Promise containing array of enqueued task objects for each batch
                 */
            )
        }, {
            key: "updateDocumentsInBatches",
            value: (function () {
                    var _updateDocumentsInBatches = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee21(documents) {
                        var batchSize,
                            options,
                            updates,
                            i,
                            _args21 = arguments;
                        return _regeneratorRuntime().wrap(function _callee21$(_context21) {
                            while (1) switch (_context21.prev = _context21.next) {
                                case 0:
                                    batchSize = _args21.length > 1 && _args21[1] !== undefined ? _args21[1] : 1000;
                                    options = _args21.length > 2 ? _args21[2] : undefined;
                                    updates = [];
                                    i = 0;
                                case 4:
                                    if (!(i < documents.length)) {
                                        _context21.next = 13;
                                        break;
                                    }
                                    _context21.t0 = updates;
                                    _context21.next = 8;
                                    return this.updateDocuments(documents.slice(i, i + batchSize), options);
                                case 8:
                                    _context21.t1 = _context21.sent;
                                    _context21.t0.push.call(_context21.t0, _context21.t1);
                                case 10:
                                    i += batchSize;
                                    _context21.next = 4;
                                    break;
                                case 13:
                                    return _context21.abrupt("return", updates);
                                case 14:
                                case "end":
                                    return _context21.stop();
                            }
                        }, _callee21, this);
                    }));
                    function updateDocumentsInBatches(_x24) {
                        return _updateDocumentsInBatches.apply(this, arguments);
                    }
                    return updateDocumentsInBatches;
                }()
                /**
                 * Add or update multiples documents in a string format to an index. It only
                 * supports csv, ndjson and json formats.
                 *
                 * @param documents - Documents provided in a string to add/update
                 * @param contentType - Content type of your document:
                 *   'text/csv'|'application/x-ndjson'|'application/json'
                 * @param queryParams - Options on raw document addition
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "updateDocumentsFromString",
            value: (function () {
                    var _updateDocumentsFromString = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee22(documents, contentType, queryParams) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee22$(_context22) {
                            while (1) switch (_context22.prev = _context22.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/documents");
                                    _context22.next = 3;
                                    return this.httpRequest.put(url, documents, queryParams, {
                                        headers: {
                                            'Content-Type': contentType
                                        }
                                    });
                                case 3:
                                    task = _context22.sent;
                                    return _context22.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context22.stop();
                            }
                        }, _callee22, this);
                    }));
                    function updateDocumentsFromString(_x25, _x26, _x27) {
                        return _updateDocumentsFromString.apply(this, arguments);
                    }
                    return updateDocumentsFromString;
                }()
                /**
                 * Delete one document
                 *
                 * @param documentId - Id of Document to delete
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "deleteDocument",
            value: (function () {
                    var _deleteDocument = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee23(documentId) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee23$(_context23) {
                            while (1) switch (_context23.prev = _context23.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/documents/").concat(documentId);
                                    _context23.next = 3;
                                    return this.httpRequest.delete(url);
                                case 3:
                                    task = _context23.sent;
                                    task.enqueuedAt = new Date(task.enqueuedAt);
                                    return _context23.abrupt("return", task);
                                case 6:
                                case "end":
                                    return _context23.stop();
                            }
                        }, _callee23, this);
                    }));
                    function deleteDocument(_x28) {
                        return _deleteDocument.apply(this, arguments);
                    }
                    return deleteDocument;
                }()
                /**
                 * Delete multiples documents of an index.
                 *
                 * @param params - Params value can be:
                 *
                 *   - DocumentsDeletionQuery: An object containing the parameters to customize
                 *       your document deletion. Only available in Meilisearch v1.2 and newer
                 *   - DocumentsIds: An array of document ids to delete
                 *
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "deleteDocuments",
            value: (function () {
                    var _deleteDocuments = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee24(params) {
                        var isDocumentsDeletionQuery, endpoint, url, task;
                        return _regeneratorRuntime().wrap(function _callee24$(_context24) {
                            while (1) switch (_context24.prev = _context24.next) {
                                case 0:
                                    // If params is of type DocumentsDeletionQuery
                                    isDocumentsDeletionQuery = !Array.isArray(params) && _typeof(params) === 'object';
                                    endpoint = isDocumentsDeletionQuery ? 'documents/delete' : 'documents/delete-batch';
                                    url = "indexes/".concat(this.uid, "/").concat(endpoint);
                                    _context24.prev = 3;
                                    _context24.next = 6;
                                    return this.httpRequest.post(url, params);
                                case 6:
                                    task = _context24.sent;
                                    return _context24.abrupt("return", new EnqueuedTask(task));
                                case 10:
                                    _context24.prev = 10;
                                    _context24.t0 = _context24["catch"](3);
                                    if (_context24.t0 instanceof MeiliSearchRequestError && isDocumentsDeletionQuery) {
                                        _context24.t0.message = versionErrorHintMessage(_context24.t0.message, 'deleteDocuments');
                                    } else if (_context24.t0 instanceof MeiliSearchApiError) {
                                        _context24.t0.message = versionErrorHintMessage(_context24.t0.message, 'deleteDocuments');
                                    }
                                    throw _context24.t0;
                                case 14:
                                case "end":
                                    return _context24.stop();
                            }
                        }, _callee24, this, [[3, 10]]);
                    }));
                    function deleteDocuments(_x29) {
                        return _deleteDocuments.apply(this, arguments);
                    }
                    return deleteDocuments;
                }()
                /**
                 * Delete all documents of an index
                 *
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "deleteAllDocuments",
            value: (function () {
                    var _deleteAllDocuments = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee25() {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee25$(_context25) {
                            while (1) switch (_context25.prev = _context25.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/documents");
                                    _context25.next = 3;
                                    return this.httpRequest.delete(url);
                                case 3:
                                    task = _context25.sent;
                                    task.enqueuedAt = new Date(task.enqueuedAt);
                                    return _context25.abrupt("return", task);
                                case 6:
                                case "end":
                                    return _context25.stop();
                            }
                        }, _callee25, this);
                    }));
                    function deleteAllDocuments() {
                        return _deleteAllDocuments.apply(this, arguments);
                    }
                    return deleteAllDocuments;
                }()
                /**
                 * This is an EXPERIMENTAL feature, which may break without a major version.
                 * It's available after Meilisearch v1.10.
                 *
                 * More info about the feature:
                 * https://github.com/orgs/meilisearch/discussions/762 More info about
                 * experimental features in general:
                 * https://www.meilisearch.com/docs/reference/api/experimental-features
                 *
                 * @param options - Object containing the function string and related options
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "updateDocumentsByFunction",
            value: (function () {
                    var _updateDocumentsByFunction = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee26(options) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee26$(_context26) {
                            while (1) switch (_context26.prev = _context26.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/documents/edit");
                                    _context26.next = 3;
                                    return this.httpRequest.post(url, options);
                                case 3:
                                    task = _context26.sent;
                                    return _context26.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context26.stop();
                            }
                        }, _callee26, this);
                    }));
                    function updateDocumentsByFunction(_x30) {
                        return _updateDocumentsByFunction.apply(this, arguments);
                    }
                    return updateDocumentsByFunction;
                }() ///
                /// SETTINGS
                ///
                /**
                 * Retrieve all settings
                 *
                 * @returns Promise containing Settings object
                 */
            )
        }, {
            key: "getSettings",
            value: function () {
                var _getSettings = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee27() {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee27$(_context27) {
                        while (1) switch (_context27.prev = _context27.next) {
                            case 0:
                                url = "indexes/".concat(this.uid, "/settings");
                                _context27.next = 3;
                                return this.httpRequest.get(url);
                            case 3:
                                return _context27.abrupt("return", _context27.sent);
                            case 4:
                            case "end":
                                return _context27.stop();
                        }
                    }, _callee27, this);
                }));
                function getSettings() {
                    return _getSettings.apply(this, arguments);
                }
                return getSettings;
            }()
            /**
             * Update all settings Any parameters not provided will be left unchanged.
             *
             * @param settings - Object containing parameters with their updated values
             * @returns Promise containing an EnqueuedTask
             */
        }, {
            key: "updateSettings",
            value: (function () {
                    var _updateSettings = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee28(settings) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee28$(_context28) {
                            while (1) switch (_context28.prev = _context28.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings");
                                    _context28.next = 3;
                                    return this.httpRequest.patch(url, settings);
                                case 3:
                                    task = _context28.sent;
                                    task.enqueued = new Date(task.enqueuedAt);
                                    return _context28.abrupt("return", task);
                                case 6:
                                case "end":
                                    return _context28.stop();
                            }
                        }, _callee28, this);
                    }));
                    function updateSettings(_x31) {
                        return _updateSettings.apply(this, arguments);
                    }
                    return updateSettings;
                }()
                /**
                 * Reset settings.
                 *
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "resetSettings",
            value: (function () {
                    var _resetSettings = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee29() {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee29$(_context29) {
                            while (1) switch (_context29.prev = _context29.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings");
                                    _context29.next = 3;
                                    return this.httpRequest.delete(url);
                                case 3:
                                    task = _context29.sent;
                                    task.enqueuedAt = new Date(task.enqueuedAt);
                                    return _context29.abrupt("return", task);
                                case 6:
                                case "end":
                                    return _context29.stop();
                            }
                        }, _callee29, this);
                    }));
                    function resetSettings() {
                        return _resetSettings.apply(this, arguments);
                    }
                    return resetSettings;
                }() ///
                /// PAGINATION SETTINGS
                ///
                /**
                 * Get the pagination settings.
                 *
                 * @returns Promise containing object of pagination settings
                 */
            )
        }, {
            key: "getPagination",
            value: function () {
                var _getPagination = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee30() {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee30$(_context30) {
                        while (1) switch (_context30.prev = _context30.next) {
                            case 0:
                                url = "indexes/".concat(this.uid, "/settings/pagination");
                                _context30.next = 3;
                                return this.httpRequest.get(url);
                            case 3:
                                return _context30.abrupt("return", _context30.sent);
                            case 4:
                            case "end":
                                return _context30.stop();
                        }
                    }, _callee30, this);
                }));
                function getPagination() {
                    return _getPagination.apply(this, arguments);
                }
                return getPagination;
            }()
            /**
             * Update the pagination settings.
             *
             * @param pagination - Pagination object
             * @returns Promise containing an EnqueuedTask
             */
        }, {
            key: "updatePagination",
            value: (function () {
                    var _updatePagination = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee31(pagination) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee31$(_context31) {
                            while (1) switch (_context31.prev = _context31.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/pagination");
                                    _context31.next = 3;
                                    return this.httpRequest.patch(url, pagination);
                                case 3:
                                    task = _context31.sent;
                                    return _context31.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context31.stop();
                            }
                        }, _callee31, this);
                    }));
                    function updatePagination(_x32) {
                        return _updatePagination.apply(this, arguments);
                    }
                    return updatePagination;
                }()
                /**
                 * Reset the pagination settings.
                 *
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "resetPagination",
            value: (function () {
                    var _resetPagination = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee32() {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee32$(_context32) {
                            while (1) switch (_context32.prev = _context32.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/pagination");
                                    _context32.next = 3;
                                    return this.httpRequest.delete(url);
                                case 3:
                                    task = _context32.sent;
                                    return _context32.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context32.stop();
                            }
                        }, _callee32, this);
                    }));
                    function resetPagination() {
                        return _resetPagination.apply(this, arguments);
                    }
                    return resetPagination;
                }() ///
                /// SYNONYMS
                ///
                /**
                 * Get the list of all synonyms
                 *
                 * @returns Promise containing object of synonym mappings
                 */
            )
        }, {
            key: "getSynonyms",
            value: function () {
                var _getSynonyms = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee33() {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee33$(_context33) {
                        while (1) switch (_context33.prev = _context33.next) {
                            case 0:
                                url = "indexes/".concat(this.uid, "/settings/synonyms");
                                _context33.next = 3;
                                return this.httpRequest.get(url);
                            case 3:
                                return _context33.abrupt("return", _context33.sent);
                            case 4:
                            case "end":
                                return _context33.stop();
                        }
                    }, _callee33, this);
                }));
                function getSynonyms() {
                    return _getSynonyms.apply(this, arguments);
                }
                return getSynonyms;
            }()
            /**
             * Update the list of synonyms. Overwrite the old list.
             *
             * @param synonyms - Mapping of synonyms with their associated words
             * @returns Promise containing an EnqueuedTask
             */
        }, {
            key: "updateSynonyms",
            value: (function () {
                    var _updateSynonyms = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee34(synonyms) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee34$(_context34) {
                            while (1) switch (_context34.prev = _context34.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/synonyms");
                                    _context34.next = 3;
                                    return this.httpRequest.put(url, synonyms);
                                case 3:
                                    task = _context34.sent;
                                    return _context34.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context34.stop();
                            }
                        }, _callee34, this);
                    }));
                    function updateSynonyms(_x33) {
                        return _updateSynonyms.apply(this, arguments);
                    }
                    return updateSynonyms;
                }()
                /**
                 * Reset the synonym list to be empty again
                 *
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "resetSynonyms",
            value: (function () {
                    var _resetSynonyms = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee35() {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee35$(_context35) {
                            while (1) switch (_context35.prev = _context35.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/synonyms");
                                    _context35.next = 3;
                                    return this.httpRequest.delete(url);
                                case 3:
                                    task = _context35.sent;
                                    task.enqueuedAt = new Date(task.enqueuedAt);
                                    return _context35.abrupt("return", task);
                                case 6:
                                case "end":
                                    return _context35.stop();
                            }
                        }, _callee35, this);
                    }));
                    function resetSynonyms() {
                        return _resetSynonyms.apply(this, arguments);
                    }
                    return resetSynonyms;
                }() ///
                /// STOP WORDS
                ///
                /**
                 * Get the list of all stop-words
                 *
                 * @returns Promise containing array of stop-words
                 */
            )
        }, {
            key: "getStopWords",
            value: function () {
                var _getStopWords = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee36() {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee36$(_context36) {
                        while (1) switch (_context36.prev = _context36.next) {
                            case 0:
                                url = "indexes/".concat(this.uid, "/settings/stop-words");
                                _context36.next = 3;
                                return this.httpRequest.get(url);
                            case 3:
                                return _context36.abrupt("return", _context36.sent);
                            case 4:
                            case "end":
                                return _context36.stop();
                        }
                    }, _callee36, this);
                }));
                function getStopWords() {
                    return _getStopWords.apply(this, arguments);
                }
                return getStopWords;
            }()
            /**
             * Update the list of stop-words. Overwrite the old list.
             *
             * @param stopWords - Array of strings that contains the stop-words.
             * @returns Promise containing an EnqueuedTask
             */
        }, {
            key: "updateStopWords",
            value: (function () {
                    var _updateStopWords = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee37(stopWords) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee37$(_context37) {
                            while (1) switch (_context37.prev = _context37.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/stop-words");
                                    _context37.next = 3;
                                    return this.httpRequest.put(url, stopWords);
                                case 3:
                                    task = _context37.sent;
                                    return _context37.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context37.stop();
                            }
                        }, _callee37, this);
                    }));
                    function updateStopWords(_x34) {
                        return _updateStopWords.apply(this, arguments);
                    }
                    return updateStopWords;
                }()
                /**
                 * Reset the stop-words list to be empty again
                 *
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "resetStopWords",
            value: (function () {
                    var _resetStopWords = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee38() {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee38$(_context38) {
                            while (1) switch (_context38.prev = _context38.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/stop-words");
                                    _context38.next = 3;
                                    return this.httpRequest.delete(url);
                                case 3:
                                    task = _context38.sent;
                                    task.enqueuedAt = new Date(task.enqueuedAt);
                                    return _context38.abrupt("return", task);
                                case 6:
                                case "end":
                                    return _context38.stop();
                            }
                        }, _callee38, this);
                    }));
                    function resetStopWords() {
                        return _resetStopWords.apply(this, arguments);
                    }
                    return resetStopWords;
                }() ///
                /// RANKING RULES
                ///
                /**
                 * Get the list of all ranking-rules
                 *
                 * @returns Promise containing array of ranking-rules
                 */
            )
        }, {
            key: "getRankingRules",
            value: function () {
                var _getRankingRules = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee39() {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee39$(_context39) {
                        while (1) switch (_context39.prev = _context39.next) {
                            case 0:
                                url = "indexes/".concat(this.uid, "/settings/ranking-rules");
                                _context39.next = 3;
                                return this.httpRequest.get(url);
                            case 3:
                                return _context39.abrupt("return", _context39.sent);
                            case 4:
                            case "end":
                                return _context39.stop();
                        }
                    }, _callee39, this);
                }));
                function getRankingRules() {
                    return _getRankingRules.apply(this, arguments);
                }
                return getRankingRules;
            }()
            /**
             * Update the list of ranking-rules. Overwrite the old list.
             *
             * @param rankingRules - Array that contain ranking rules sorted by order of
             *   importance.
             * @returns Promise containing an EnqueuedTask
             */
        }, {
            key: "updateRankingRules",
            value: (function () {
                    var _updateRankingRules = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee40(rankingRules) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee40$(_context40) {
                            while (1) switch (_context40.prev = _context40.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/ranking-rules");
                                    _context40.next = 3;
                                    return this.httpRequest.put(url, rankingRules);
                                case 3:
                                    task = _context40.sent;
                                    return _context40.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context40.stop();
                            }
                        }, _callee40, this);
                    }));
                    function updateRankingRules(_x35) {
                        return _updateRankingRules.apply(this, arguments);
                    }
                    return updateRankingRules;
                }()
                /**
                 * Reset the ranking rules list to its default value
                 *
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "resetRankingRules",
            value: (function () {
                    var _resetRankingRules = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee41() {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee41$(_context41) {
                            while (1) switch (_context41.prev = _context41.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/ranking-rules");
                                    _context41.next = 3;
                                    return this.httpRequest.delete(url);
                                case 3:
                                    task = _context41.sent;
                                    task.enqueuedAt = new Date(task.enqueuedAt);
                                    return _context41.abrupt("return", task);
                                case 6:
                                case "end":
                                    return _context41.stop();
                            }
                        }, _callee41, this);
                    }));
                    function resetRankingRules() {
                        return _resetRankingRules.apply(this, arguments);
                    }
                    return resetRankingRules;
                }() ///
                /// DISTINCT ATTRIBUTE
                ///
                /**
                 * Get the distinct-attribute
                 *
                 * @returns Promise containing the distinct-attribute of the index
                 */
            )
        }, {
            key: "getDistinctAttribute",
            value: function () {
                var _getDistinctAttribute = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee42() {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee42$(_context42) {
                        while (1) switch (_context42.prev = _context42.next) {
                            case 0:
                                url = "indexes/".concat(this.uid, "/settings/distinct-attribute");
                                _context42.next = 3;
                                return this.httpRequest.get(url);
                            case 3:
                                return _context42.abrupt("return", _context42.sent);
                            case 4:
                            case "end":
                                return _context42.stop();
                        }
                    }, _callee42, this);
                }));
                function getDistinctAttribute() {
                    return _getDistinctAttribute.apply(this, arguments);
                }
                return getDistinctAttribute;
            }()
            /**
             * Update the distinct-attribute.
             *
             * @param distinctAttribute - Field name of the distinct-attribute
             * @returns Promise containing an EnqueuedTask
             */
        }, {
            key: "updateDistinctAttribute",
            value: (function () {
                    var _updateDistinctAttribute = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee43(distinctAttribute) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee43$(_context43) {
                            while (1) switch (_context43.prev = _context43.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/distinct-attribute");
                                    _context43.next = 3;
                                    return this.httpRequest.put(url, distinctAttribute);
                                case 3:
                                    task = _context43.sent;
                                    return _context43.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context43.stop();
                            }
                        }, _callee43, this);
                    }));
                    function updateDistinctAttribute(_x36) {
                        return _updateDistinctAttribute.apply(this, arguments);
                    }
                    return updateDistinctAttribute;
                }()
                /**
                 * Reset the distinct-attribute.
                 *
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "resetDistinctAttribute",
            value: (function () {
                    var _resetDistinctAttribute = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee44() {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee44$(_context44) {
                            while (1) switch (_context44.prev = _context44.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/distinct-attribute");
                                    _context44.next = 3;
                                    return this.httpRequest.delete(url);
                                case 3:
                                    task = _context44.sent;
                                    task.enqueuedAt = new Date(task.enqueuedAt);
                                    return _context44.abrupt("return", task);
                                case 6:
                                case "end":
                                    return _context44.stop();
                            }
                        }, _callee44, this);
                    }));
                    function resetDistinctAttribute() {
                        return _resetDistinctAttribute.apply(this, arguments);
                    }
                    return resetDistinctAttribute;
                }() ///
                /// FILTERABLE ATTRIBUTES
                ///
                /**
                 * Get the filterable-attributes
                 *
                 * @returns Promise containing an array of filterable-attributes
                 */
            )
        }, {
            key: "getFilterableAttributes",
            value: function () {
                var _getFilterableAttributes = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee45() {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee45$(_context45) {
                        while (1) switch (_context45.prev = _context45.next) {
                            case 0:
                                url = "indexes/".concat(this.uid, "/settings/filterable-attributes");
                                _context45.next = 3;
                                return this.httpRequest.get(url);
                            case 3:
                                return _context45.abrupt("return", _context45.sent);
                            case 4:
                            case "end":
                                return _context45.stop();
                        }
                    }, _callee45, this);
                }));
                function getFilterableAttributes() {
                    return _getFilterableAttributes.apply(this, arguments);
                }
                return getFilterableAttributes;
            }()
            /**
             * Update the filterable-attributes.
             *
             * @param filterableAttributes - Array of strings containing the attributes
             *   that can be used as filters at query time
             * @returns Promise containing an EnqueuedTask
             */
        }, {
            key: "updateFilterableAttributes",
            value: (function () {
                    var _updateFilterableAttributes = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee46(filterableAttributes) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee46$(_context46) {
                            while (1) switch (_context46.prev = _context46.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/filterable-attributes");
                                    _context46.next = 3;
                                    return this.httpRequest.put(url, filterableAttributes);
                                case 3:
                                    task = _context46.sent;
                                    return _context46.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context46.stop();
                            }
                        }, _callee46, this);
                    }));
                    function updateFilterableAttributes(_x37) {
                        return _updateFilterableAttributes.apply(this, arguments);
                    }
                    return updateFilterableAttributes;
                }()
                /**
                 * Reset the filterable-attributes.
                 *
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "resetFilterableAttributes",
            value: (function () {
                    var _resetFilterableAttributes = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee47() {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee47$(_context47) {
                            while (1) switch (_context47.prev = _context47.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/filterable-attributes");
                                    _context47.next = 3;
                                    return this.httpRequest.delete(url);
                                case 3:
                                    task = _context47.sent;
                                    task.enqueuedAt = new Date(task.enqueuedAt);
                                    return _context47.abrupt("return", task);
                                case 6:
                                case "end":
                                    return _context47.stop();
                            }
                        }, _callee47, this);
                    }));
                    function resetFilterableAttributes() {
                        return _resetFilterableAttributes.apply(this, arguments);
                    }
                    return resetFilterableAttributes;
                }() ///
                /// SORTABLE ATTRIBUTES
                ///
                /**
                 * Get the sortable-attributes
                 *
                 * @returns Promise containing array of sortable-attributes
                 */
            )
        }, {
            key: "getSortableAttributes",
            value: function () {
                var _getSortableAttributes = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee48() {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee48$(_context48) {
                        while (1) switch (_context48.prev = _context48.next) {
                            case 0:
                                url = "indexes/".concat(this.uid, "/settings/sortable-attributes");
                                _context48.next = 3;
                                return this.httpRequest.get(url);
                            case 3:
                                return _context48.abrupt("return", _context48.sent);
                            case 4:
                            case "end":
                                return _context48.stop();
                        }
                    }, _callee48, this);
                }));
                function getSortableAttributes() {
                    return _getSortableAttributes.apply(this, arguments);
                }
                return getSortableAttributes;
            }()
            /**
             * Update the sortable-attributes.
             *
             * @param sortableAttributes - Array of strings containing the attributes that
             *   can be used to sort search results at query time
             * @returns Promise containing an EnqueuedTask
             */
        }, {
            key: "updateSortableAttributes",
            value: (function () {
                    var _updateSortableAttributes = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee49(sortableAttributes) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee49$(_context49) {
                            while (1) switch (_context49.prev = _context49.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/sortable-attributes");
                                    _context49.next = 3;
                                    return this.httpRequest.put(url, sortableAttributes);
                                case 3:
                                    task = _context49.sent;
                                    return _context49.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context49.stop();
                            }
                        }, _callee49, this);
                    }));
                    function updateSortableAttributes(_x38) {
                        return _updateSortableAttributes.apply(this, arguments);
                    }
                    return updateSortableAttributes;
                }()
                /**
                 * Reset the sortable-attributes.
                 *
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "resetSortableAttributes",
            value: (function () {
                    var _resetSortableAttributes = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee50() {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee50$(_context50) {
                            while (1) switch (_context50.prev = _context50.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/sortable-attributes");
                                    _context50.next = 3;
                                    return this.httpRequest.delete(url);
                                case 3:
                                    task = _context50.sent;
                                    task.enqueuedAt = new Date(task.enqueuedAt);
                                    return _context50.abrupt("return", task);
                                case 6:
                                case "end":
                                    return _context50.stop();
                            }
                        }, _callee50, this);
                    }));
                    function resetSortableAttributes() {
                        return _resetSortableAttributes.apply(this, arguments);
                    }
                    return resetSortableAttributes;
                }() ///
                /// SEARCHABLE ATTRIBUTE
                ///
                /**
                 * Get the searchable-attributes
                 *
                 * @returns Promise containing array of searchable-attributes
                 */
            )
        }, {
            key: "getSearchableAttributes",
            value: function () {
                var _getSearchableAttributes = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee51() {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee51$(_context51) {
                        while (1) switch (_context51.prev = _context51.next) {
                            case 0:
                                url = "indexes/".concat(this.uid, "/settings/searchable-attributes");
                                _context51.next = 3;
                                return this.httpRequest.get(url);
                            case 3:
                                return _context51.abrupt("return", _context51.sent);
                            case 4:
                            case "end":
                                return _context51.stop();
                        }
                    }, _callee51, this);
                }));
                function getSearchableAttributes() {
                    return _getSearchableAttributes.apply(this, arguments);
                }
                return getSearchableAttributes;
            }()
            /**
             * Update the searchable-attributes.
             *
             * @param searchableAttributes - Array of strings that contains searchable
             *   attributes sorted by order of importance(most to least important)
             * @returns Promise containing an EnqueuedTask
             */
        }, {
            key: "updateSearchableAttributes",
            value: (function () {
                    var _updateSearchableAttributes = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee52(searchableAttributes) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee52$(_context52) {
                            while (1) switch (_context52.prev = _context52.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/searchable-attributes");
                                    _context52.next = 3;
                                    return this.httpRequest.put(url, searchableAttributes);
                                case 3:
                                    task = _context52.sent;
                                    return _context52.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context52.stop();
                            }
                        }, _callee52, this);
                    }));
                    function updateSearchableAttributes(_x39) {
                        return _updateSearchableAttributes.apply(this, arguments);
                    }
                    return updateSearchableAttributes;
                }()
                /**
                 * Reset the searchable-attributes.
                 *
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "resetSearchableAttributes",
            value: (function () {
                    var _resetSearchableAttributes = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee53() {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee53$(_context53) {
                            while (1) switch (_context53.prev = _context53.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/searchable-attributes");
                                    _context53.next = 3;
                                    return this.httpRequest.delete(url);
                                case 3:
                                    task = _context53.sent;
                                    task.enqueuedAt = new Date(task.enqueuedAt);
                                    return _context53.abrupt("return", task);
                                case 6:
                                case "end":
                                    return _context53.stop();
                            }
                        }, _callee53, this);
                    }));
                    function resetSearchableAttributes() {
                        return _resetSearchableAttributes.apply(this, arguments);
                    }
                    return resetSearchableAttributes;
                }() ///
                /// DISPLAYED ATTRIBUTE
                ///
                /**
                 * Get the displayed-attributes
                 *
                 * @returns Promise containing array of displayed-attributes
                 */
            )
        }, {
            key: "getDisplayedAttributes",
            value: function () {
                var _getDisplayedAttributes = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee54() {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee54$(_context54) {
                        while (1) switch (_context54.prev = _context54.next) {
                            case 0:
                                url = "indexes/".concat(this.uid, "/settings/displayed-attributes");
                                _context54.next = 3;
                                return this.httpRequest.get(url);
                            case 3:
                                return _context54.abrupt("return", _context54.sent);
                            case 4:
                            case "end":
                                return _context54.stop();
                        }
                    }, _callee54, this);
                }));
                function getDisplayedAttributes() {
                    return _getDisplayedAttributes.apply(this, arguments);
                }
                return getDisplayedAttributes;
            }()
            /**
             * Update the displayed-attributes.
             *
             * @param displayedAttributes - Array of strings that contains attributes of
             *   an index to display
             * @returns Promise containing an EnqueuedTask
             */
        }, {
            key: "updateDisplayedAttributes",
            value: (function () {
                    var _updateDisplayedAttributes = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee55(displayedAttributes) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee55$(_context55) {
                            while (1) switch (_context55.prev = _context55.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/displayed-attributes");
                                    _context55.next = 3;
                                    return this.httpRequest.put(url, displayedAttributes);
                                case 3:
                                    task = _context55.sent;
                                    return _context55.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context55.stop();
                            }
                        }, _callee55, this);
                    }));
                    function updateDisplayedAttributes(_x40) {
                        return _updateDisplayedAttributes.apply(this, arguments);
                    }
                    return updateDisplayedAttributes;
                }()
                /**
                 * Reset the displayed-attributes.
                 *
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "resetDisplayedAttributes",
            value: (function () {
                    var _resetDisplayedAttributes = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee56() {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee56$(_context56) {
                            while (1) switch (_context56.prev = _context56.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/displayed-attributes");
                                    _context56.next = 3;
                                    return this.httpRequest.delete(url);
                                case 3:
                                    task = _context56.sent;
                                    task.enqueuedAt = new Date(task.enqueuedAt);
                                    return _context56.abrupt("return", task);
                                case 6:
                                case "end":
                                    return _context56.stop();
                            }
                        }, _callee56, this);
                    }));
                    function resetDisplayedAttributes() {
                        return _resetDisplayedAttributes.apply(this, arguments);
                    }
                    return resetDisplayedAttributes;
                }() ///
                /// TYPO TOLERANCE
                ///
                /**
                 * Get the typo tolerance settings.
                 *
                 * @returns Promise containing the typo tolerance settings.
                 */
            )
        }, {
            key: "getTypoTolerance",
            value: function () {
                var _getTypoTolerance = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee57() {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee57$(_context57) {
                        while (1) switch (_context57.prev = _context57.next) {
                            case 0:
                                url = "indexes/".concat(this.uid, "/settings/typo-tolerance");
                                _context57.next = 3;
                                return this.httpRequest.get(url);
                            case 3:
                                return _context57.abrupt("return", _context57.sent);
                            case 4:
                            case "end":
                                return _context57.stop();
                        }
                    }, _callee57, this);
                }));
                function getTypoTolerance() {
                    return _getTypoTolerance.apply(this, arguments);
                }
                return getTypoTolerance;
            }()
            /**
             * Update the typo tolerance settings.
             *
             * @param typoTolerance - Object containing the custom typo tolerance
             *   settings.
             * @returns Promise containing object of the enqueued update
             */
        }, {
            key: "updateTypoTolerance",
            value: (function () {
                    var _updateTypoTolerance = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee58(typoTolerance) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee58$(_context58) {
                            while (1) switch (_context58.prev = _context58.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/typo-tolerance");
                                    _context58.next = 3;
                                    return this.httpRequest.patch(url, typoTolerance);
                                case 3:
                                    task = _context58.sent;
                                    task.enqueuedAt = new Date(task.enqueuedAt);
                                    return _context58.abrupt("return", task);
                                case 6:
                                case "end":
                                    return _context58.stop();
                            }
                        }, _callee58, this);
                    }));
                    function updateTypoTolerance(_x41) {
                        return _updateTypoTolerance.apply(this, arguments);
                    }
                    return updateTypoTolerance;
                }()
                /**
                 * Reset the typo tolerance settings.
                 *
                 * @returns Promise containing object of the enqueued update
                 */
            )
        }, {
            key: "resetTypoTolerance",
            value: (function () {
                    var _resetTypoTolerance = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee59() {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee59$(_context59) {
                            while (1) switch (_context59.prev = _context59.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/typo-tolerance");
                                    _context59.next = 3;
                                    return this.httpRequest.delete(url);
                                case 3:
                                    task = _context59.sent;
                                    task.enqueuedAt = new Date(task.enqueuedAt);
                                    return _context59.abrupt("return", task);
                                case 6:
                                case "end":
                                    return _context59.stop();
                            }
                        }, _callee59, this);
                    }));
                    function resetTypoTolerance() {
                        return _resetTypoTolerance.apply(this, arguments);
                    }
                    return resetTypoTolerance;
                }() ///
                /// FACETING
                ///
                /**
                 * Get the faceting settings.
                 *
                 * @returns Promise containing object of faceting index settings
                 */
            )
        }, {
            key: "getFaceting",
            value: function () {
                var _getFaceting = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee60() {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee60$(_context60) {
                        while (1) switch (_context60.prev = _context60.next) {
                            case 0:
                                url = "indexes/".concat(this.uid, "/settings/faceting");
                                _context60.next = 3;
                                return this.httpRequest.get(url);
                            case 3:
                                return _context60.abrupt("return", _context60.sent);
                            case 4:
                            case "end":
                                return _context60.stop();
                        }
                    }, _callee60, this);
                }));
                function getFaceting() {
                    return _getFaceting.apply(this, arguments);
                }
                return getFaceting;
            }()
            /**
             * Update the faceting settings.
             *
             * @param faceting - Faceting index settings object
             * @returns Promise containing an EnqueuedTask
             */
        }, {
            key: "updateFaceting",
            value: (function () {
                    var _updateFaceting = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee61(faceting) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee61$(_context61) {
                            while (1) switch (_context61.prev = _context61.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/faceting");
                                    _context61.next = 3;
                                    return this.httpRequest.patch(url, faceting);
                                case 3:
                                    task = _context61.sent;
                                    return _context61.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context61.stop();
                            }
                        }, _callee61, this);
                    }));
                    function updateFaceting(_x42) {
                        return _updateFaceting.apply(this, arguments);
                    }
                    return updateFaceting;
                }()
                /**
                 * Reset the faceting settings.
                 *
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "resetFaceting",
            value: (function () {
                    var _resetFaceting = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee62() {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee62$(_context62) {
                            while (1) switch (_context62.prev = _context62.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/faceting");
                                    _context62.next = 3;
                                    return this.httpRequest.delete(url);
                                case 3:
                                    task = _context62.sent;
                                    return _context62.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context62.stop();
                            }
                        }, _callee62, this);
                    }));
                    function resetFaceting() {
                        return _resetFaceting.apply(this, arguments);
                    }
                    return resetFaceting;
                }() ///
                /// SEPARATOR TOKENS
                ///
                /**
                 * Get the list of all separator tokens.
                 *
                 * @returns Promise containing array of separator tokens
                 */
            )
        }, {
            key: "getSeparatorTokens",
            value: function () {
                var _getSeparatorTokens = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee63() {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee63$(_context63) {
                        while (1) switch (_context63.prev = _context63.next) {
                            case 0:
                                url = "indexes/".concat(this.uid, "/settings/separator-tokens");
                                _context63.next = 3;
                                return this.httpRequest.get(url);
                            case 3:
                                return _context63.abrupt("return", _context63.sent);
                            case 4:
                            case "end":
                                return _context63.stop();
                        }
                    }, _callee63, this);
                }));
                function getSeparatorTokens() {
                    return _getSeparatorTokens.apply(this, arguments);
                }
                return getSeparatorTokens;
            }()
            /**
             * Update the list of separator tokens. Overwrite the old list.
             *
             * @param separatorTokens - Array that contains separator tokens.
             * @returns Promise containing an EnqueuedTask or null
             */
        }, {
            key: "updateSeparatorTokens",
            value: (function () {
                    var _updateSeparatorTokens = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee64(separatorTokens) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee64$(_context64) {
                            while (1) switch (_context64.prev = _context64.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/separator-tokens");
                                    _context64.next = 3;
                                    return this.httpRequest.put(url, separatorTokens);
                                case 3:
                                    task = _context64.sent;
                                    return _context64.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context64.stop();
                            }
                        }, _callee64, this);
                    }));
                    function updateSeparatorTokens(_x43) {
                        return _updateSeparatorTokens.apply(this, arguments);
                    }
                    return updateSeparatorTokens;
                }()
                /**
                 * Reset the separator tokens list to its default value
                 *
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "resetSeparatorTokens",
            value: (function () {
                    var _resetSeparatorTokens = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee65() {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee65$(_context65) {
                            while (1) switch (_context65.prev = _context65.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/separator-tokens");
                                    _context65.next = 3;
                                    return this.httpRequest.delete(url);
                                case 3:
                                    task = _context65.sent;
                                    task.enqueuedAt = new Date(task.enqueuedAt);
                                    return _context65.abrupt("return", task);
                                case 6:
                                case "end":
                                    return _context65.stop();
                            }
                        }, _callee65, this);
                    }));
                    function resetSeparatorTokens() {
                        return _resetSeparatorTokens.apply(this, arguments);
                    }
                    return resetSeparatorTokens;
                }() ///
                /// NON-SEPARATOR TOKENS
                ///
                /**
                 * Get the list of all non-separator tokens.
                 *
                 * @returns Promise containing array of non-separator tokens
                 */
            )
        }, {
            key: "getNonSeparatorTokens",
            value: function () {
                var _getNonSeparatorTokens = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee66() {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee66$(_context66) {
                        while (1) switch (_context66.prev = _context66.next) {
                            case 0:
                                url = "indexes/".concat(this.uid, "/settings/non-separator-tokens");
                                _context66.next = 3;
                                return this.httpRequest.get(url);
                            case 3:
                                return _context66.abrupt("return", _context66.sent);
                            case 4:
                            case "end":
                                return _context66.stop();
                        }
                    }, _callee66, this);
                }));
                function getNonSeparatorTokens() {
                    return _getNonSeparatorTokens.apply(this, arguments);
                }
                return getNonSeparatorTokens;
            }()
            /**
             * Update the list of non-separator tokens. Overwrite the old list.
             *
             * @param nonSeparatorTokens - Array that contains non-separator tokens.
             * @returns Promise containing an EnqueuedTask or null
             */
        }, {
            key: "updateNonSeparatorTokens",
            value: (function () {
                    var _updateNonSeparatorTokens = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee67(nonSeparatorTokens) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee67$(_context67) {
                            while (1) switch (_context67.prev = _context67.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/non-separator-tokens");
                                    _context67.next = 3;
                                    return this.httpRequest.put(url, nonSeparatorTokens);
                                case 3:
                                    task = _context67.sent;
                                    return _context67.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context67.stop();
                            }
                        }, _callee67, this);
                    }));
                    function updateNonSeparatorTokens(_x44) {
                        return _updateNonSeparatorTokens.apply(this, arguments);
                    }
                    return updateNonSeparatorTokens;
                }()
                /**
                 * Reset the non-separator tokens list to its default value
                 *
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "resetNonSeparatorTokens",
            value: (function () {
                    var _resetNonSeparatorTokens = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee68() {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee68$(_context68) {
                            while (1) switch (_context68.prev = _context68.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/non-separator-tokens");
                                    _context68.next = 3;
                                    return this.httpRequest.delete(url);
                                case 3:
                                    task = _context68.sent;
                                    task.enqueuedAt = new Date(task.enqueuedAt);
                                    return _context68.abrupt("return", task);
                                case 6:
                                case "end":
                                    return _context68.stop();
                            }
                        }, _callee68, this);
                    }));
                    function resetNonSeparatorTokens() {
                        return _resetNonSeparatorTokens.apply(this, arguments);
                    }
                    return resetNonSeparatorTokens;
                }() ///
                /// DICTIONARY
                ///
                /**
                 * Get the dictionary settings of a Meilisearch index.
                 *
                 * @returns Promise containing the dictionary settings
                 */
            )
        }, {
            key: "getDictionary",
            value: function () {
                var _getDictionary = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee69() {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee69$(_context69) {
                        while (1) switch (_context69.prev = _context69.next) {
                            case 0:
                                url = "indexes/".concat(this.uid, "/settings/dictionary");
                                _context69.next = 3;
                                return this.httpRequest.get(url);
                            case 3:
                                return _context69.abrupt("return", _context69.sent);
                            case 4:
                            case "end":
                                return _context69.stop();
                        }
                    }, _callee69, this);
                }));
                function getDictionary() {
                    return _getDictionary.apply(this, arguments);
                }
                return getDictionary;
            }()
            /**
             * Update the dictionary settings. Overwrite the old settings.
             *
             * @param dictionary - Array that contains the new dictionary settings.
             * @returns Promise containing an EnqueuedTask or null
             */
        }, {
            key: "updateDictionary",
            value: (function () {
                    var _updateDictionary = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee70(dictionary) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee70$(_context70) {
                            while (1) switch (_context70.prev = _context70.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/dictionary");
                                    _context70.next = 3;
                                    return this.httpRequest.put(url, dictionary);
                                case 3:
                                    task = _context70.sent;
                                    return _context70.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context70.stop();
                            }
                        }, _callee70, this);
                    }));
                    function updateDictionary(_x45) {
                        return _updateDictionary.apply(this, arguments);
                    }
                    return updateDictionary;
                }()
                /**
                 * Reset the dictionary settings to its default value
                 *
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "resetDictionary",
            value: (function () {
                    var _resetDictionary = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee71() {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee71$(_context71) {
                            while (1) switch (_context71.prev = _context71.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/dictionary");
                                    _context71.next = 3;
                                    return this.httpRequest.delete(url);
                                case 3:
                                    task = _context71.sent;
                                    task.enqueuedAt = new Date(task.enqueuedAt);
                                    return _context71.abrupt("return", task);
                                case 6:
                                case "end":
                                    return _context71.stop();
                            }
                        }, _callee71, this);
                    }));
                    function resetDictionary() {
                        return _resetDictionary.apply(this, arguments);
                    }
                    return resetDictionary;
                }() ///
                /// PROXIMITY PRECISION
                ///
                /**
                 * Get the proximity precision settings of a Meilisearch index.
                 *
                 * @returns Promise containing the proximity precision settings
                 */
            )
        }, {
            key: "getProximityPrecision",
            value: function () {
                var _getProximityPrecision = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee72() {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee72$(_context72) {
                        while (1) switch (_context72.prev = _context72.next) {
                            case 0:
                                url = "indexes/".concat(this.uid, "/settings/proximity-precision");
                                _context72.next = 3;
                                return this.httpRequest.get(url);
                            case 3:
                                return _context72.abrupt("return", _context72.sent);
                            case 4:
                            case "end":
                                return _context72.stop();
                        }
                    }, _callee72, this);
                }));
                function getProximityPrecision() {
                    return _getProximityPrecision.apply(this, arguments);
                }
                return getProximityPrecision;
            }()
            /**
             * Update the proximity precision settings. Overwrite the old settings.
             *
             * @param proximityPrecision - String that contains the new proximity
             *   precision settings.
             * @returns Promise containing an EnqueuedTask or null
             */
        }, {
            key: "updateProximityPrecision",
            value: (function () {
                    var _updateProximityPrecision = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee73(proximityPrecision) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee73$(_context73) {
                            while (1) switch (_context73.prev = _context73.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/proximity-precision");
                                    _context73.next = 3;
                                    return this.httpRequest.put(url, proximityPrecision);
                                case 3:
                                    task = _context73.sent;
                                    return _context73.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context73.stop();
                            }
                        }, _callee73, this);
                    }));
                    function updateProximityPrecision(_x46) {
                        return _updateProximityPrecision.apply(this, arguments);
                    }
                    return updateProximityPrecision;
                }()
                /**
                 * Reset the proximity precision settings to its default value
                 *
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "resetProximityPrecision",
            value: (function () {
                    var _resetProximityPrecision = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee74() {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee74$(_context74) {
                            while (1) switch (_context74.prev = _context74.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/proximity-precision");
                                    _context74.next = 3;
                                    return this.httpRequest.delete(url);
                                case 3:
                                    task = _context74.sent;
                                    task.enqueuedAt = new Date(task.enqueuedAt);
                                    return _context74.abrupt("return", task);
                                case 6:
                                case "end":
                                    return _context74.stop();
                            }
                        }, _callee74, this);
                    }));
                    function resetProximityPrecision() {
                        return _resetProximityPrecision.apply(this, arguments);
                    }
                    return resetProximityPrecision;
                }() ///
                /// EMBEDDERS
                ///
                /**
                 * Get the embedders settings of a Meilisearch index.
                 *
                 * @returns Promise containing the embedders settings
                 */
            )
        }, {
            key: "getEmbedders",
            value: function () {
                var _getEmbedders = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee75() {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee75$(_context75) {
                        while (1) switch (_context75.prev = _context75.next) {
                            case 0:
                                url = "indexes/".concat(this.uid, "/settings/embedders");
                                _context75.next = 3;
                                return this.httpRequest.get(url);
                            case 3:
                                return _context75.abrupt("return", _context75.sent);
                            case 4:
                            case "end":
                                return _context75.stop();
                        }
                    }, _callee75, this);
                }));
                function getEmbedders() {
                    return _getEmbedders.apply(this, arguments);
                }
                return getEmbedders;
            }()
            /**
             * Update the embedders settings. Overwrite the old settings.
             *
             * @param embedders - Object that contains the new embedders settings.
             * @returns Promise containing an EnqueuedTask or null
             */
        }, {
            key: "updateEmbedders",
            value: (function () {
                    var _updateEmbedders = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee76(embedders) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee76$(_context76) {
                            while (1) switch (_context76.prev = _context76.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/embedders");
                                    _context76.next = 3;
                                    return this.httpRequest.patch(url, embedders);
                                case 3:
                                    task = _context76.sent;
                                    return _context76.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context76.stop();
                            }
                        }, _callee76, this);
                    }));
                    function updateEmbedders(_x47) {
                        return _updateEmbedders.apply(this, arguments);
                    }
                    return updateEmbedders;
                }()
                /**
                 * Reset the embedders settings to its default value
                 *
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "resetEmbedders",
            value: (function () {
                    var _resetEmbedders = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee77() {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee77$(_context77) {
                            while (1) switch (_context77.prev = _context77.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/embedders");
                                    _context77.next = 3;
                                    return this.httpRequest.delete(url);
                                case 3:
                                    task = _context77.sent;
                                    task.enqueuedAt = new Date(task.enqueuedAt);
                                    return _context77.abrupt("return", task);
                                case 6:
                                case "end":
                                    return _context77.stop();
                            }
                        }, _callee77, this);
                    }));
                    function resetEmbedders() {
                        return _resetEmbedders.apply(this, arguments);
                    }
                    return resetEmbedders;
                }() ///
                /// SEARCHCUTOFFMS SETTINGS
                ///
                /**
                 * Get the SearchCutoffMs settings.
                 *
                 * @returns Promise containing object of SearchCutoffMs settings
                 */
            )
        }, {
            key: "getSearchCutoffMs",
            value: function () {
                var _getSearchCutoffMs = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee78() {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee78$(_context78) {
                        while (1) switch (_context78.prev = _context78.next) {
                            case 0:
                                url = "indexes/".concat(this.uid, "/settings/search-cutoff-ms");
                                _context78.next = 3;
                                return this.httpRequest.get(url);
                            case 3:
                                return _context78.abrupt("return", _context78.sent);
                            case 4:
                            case "end":
                                return _context78.stop();
                        }
                    }, _callee78, this);
                }));
                function getSearchCutoffMs() {
                    return _getSearchCutoffMs.apply(this, arguments);
                }
                return getSearchCutoffMs;
            }()
            /**
             * Update the SearchCutoffMs settings.
             *
             * @param searchCutoffMs - Object containing SearchCutoffMsSettings
             * @returns Promise containing an EnqueuedTask
             */
        }, {
            key: "updateSearchCutoffMs",
            value: (function () {
                    var _updateSearchCutoffMs = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee79(searchCutoffMs) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee79$(_context79) {
                            while (1) switch (_context79.prev = _context79.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/search-cutoff-ms");
                                    _context79.next = 3;
                                    return this.httpRequest.put(url, searchCutoffMs);
                                case 3:
                                    task = _context79.sent;
                                    return _context79.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context79.stop();
                            }
                        }, _callee79, this);
                    }));
                    function updateSearchCutoffMs(_x48) {
                        return _updateSearchCutoffMs.apply(this, arguments);
                    }
                    return updateSearchCutoffMs;
                }()
                /**
                 * Reset the SearchCutoffMs settings.
                 *
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "resetSearchCutoffMs",
            value: (function () {
                    var _resetSearchCutoffMs = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee80() {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee80$(_context80) {
                            while (1) switch (_context80.prev = _context80.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/search-cutoff-ms");
                                    _context80.next = 3;
                                    return this.httpRequest.delete(url);
                                case 3:
                                    task = _context80.sent;
                                    return _context80.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context80.stop();
                            }
                        }, _callee80, this);
                    }));
                    function resetSearchCutoffMs() {
                        return _resetSearchCutoffMs.apply(this, arguments);
                    }
                    return resetSearchCutoffMs;
                }() ///
                /// LOCALIZED ATTRIBUTES SETTINGS
                ///
                /**
                 * Get the localized attributes settings.
                 *
                 * @returns Promise containing object of localized attributes settings
                 */
            )
        }, {
            key: "getLocalizedAttributes",
            value: function () {
                var _getLocalizedAttributes = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee81() {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee81$(_context81) {
                        while (1) switch (_context81.prev = _context81.next) {
                            case 0:
                                url = "indexes/".concat(this.uid, "/settings/localized-attributes");
                                _context81.next = 3;
                                return this.httpRequest.get(url);
                            case 3:
                                return _context81.abrupt("return", _context81.sent);
                            case 4:
                            case "end":
                                return _context81.stop();
                        }
                    }, _callee81, this);
                }));
                function getLocalizedAttributes() {
                    return _getLocalizedAttributes.apply(this, arguments);
                }
                return getLocalizedAttributes;
            }()
            /**
             * Update the localized attributes settings.
             *
             * @param localizedAttributes - Localized attributes object
             * @returns Promise containing an EnqueuedTask
             */
        }, {
            key: "updateLocalizedAttributes",
            value: (function () {
                    var _updateLocalizedAttributes = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee82(localizedAttributes) {
                        var url, task;
                        return _regeneratorRuntime().wrap(function _callee82$(_context82) {
                            while (1) switch (_context82.prev = _context82.next) {
                                case 0:
                                    url = "indexes/".concat(this.uid, "/settings/localized-attributes");
                                    _context82.next = 3;
                                    return this.httpRequest.put(url, localizedAttributes);
                                case 3:
                                    task = _context82.sent;
                                    return _context82.abrupt("return", new EnqueuedTask(task));
                                case 5:
                                case "end":
                                    return _context82.stop();
                            }
                        }, _callee82, this);
                    }));
                    function updateLocalizedAttributes(_x49) {
                        return _updateLocalizedAttributes.apply(this, arguments);
                    }
                    return updateLocalizedAttributes;
                }()
                /**
                 * Reset the localized attributes settings.
                 *
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "resetLocalizedAttributes",
            value: (function () {
                var _resetLocalizedAttributes = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee83() {
                    var url, task;
                    return _regeneratorRuntime().wrap(function _callee83$(_context83) {
                        while (1) switch (_context83.prev = _context83.next) {
                            case 0:
                                url = "indexes/".concat(this.uid, "/settings/localized-attributes");
                                _context83.next = 3;
                                return this.httpRequest.delete(url);
                            case 3:
                                task = _context83.sent;
                                return _context83.abrupt("return", new EnqueuedTask(task));
                            case 5:
                            case "end":
                                return _context83.stop();
                        }
                    }, _callee83, this);
                }));
                function resetLocalizedAttributes() {
                    return _resetLocalizedAttributes.apply(this, arguments);
                }
                return resetLocalizedAttributes;
            }())
        }], [{
            key: "create",
            value: (function () {
                var _create = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee84(uid) {
                    var options,
                        config,
                        url,
                        req,
                        task,
                        _args84 = arguments;
                    return _regeneratorRuntime().wrap(function _callee84$(_context84) {
                        while (1) switch (_context84.prev = _context84.next) {
                            case 0:
                                options = _args84.length > 1 && _args84[1] !== undefined ? _args84[1] : {};
                                config = _args84.length > 2 ? _args84[2] : undefined;
                                url = "indexes";
                                req = new HttpRequests(config);
                                _context84.next = 6;
                                return req.post(url, _objectSpread2(_objectSpread2({}, options), {}, {
                                    uid: uid
                                }));
                            case 6:
                                task = _context84.sent;
                                return _context84.abrupt("return", new EnqueuedTask(task));
                            case 8:
                            case "end":
                                return _context84.stop();
                        }
                    }, _callee84);
                }));
                function create(_x50) {
                    return _create.apply(this, arguments);
                }
                return create;
            }())
        }]);
        return Index;
    }();

    var Client = /*#__PURE__*/function () {
        /**
         * Creates new MeiliSearch instance
         *
         * @param config - Configuration object
         */
        function Client(config) {
            _classCallCheck(this, Client);
            _defineProperty(this, "config", void 0);
            _defineProperty(this, "httpRequest", void 0);
            _defineProperty(this, "tasks", void 0);
            this.config = config;
            this.httpRequest = new HttpRequests(config);
            this.tasks = new TaskClient(config);
        }
        /**
         * Return an Index instance
         *
         * @param indexUid - The index UID
         * @returns Instance of Index
         */
        _createClass(Client, [{
            key: "index",
            value: function index(indexUid) {
                return new Index(this.config, indexUid);
            }
            /**
             * Gather information about an index by calling MeiliSearch and return an
             * Index instance with the gathered information
             *
             * @param indexUid - The index UID
             * @returns Promise returning Index instance
             */
        }, {
            key: "getIndex",
            value: (function () {
                    var _getIndex = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee(indexUid) {
                        return _regeneratorRuntime().wrap(function _callee$(_context) {
                            while (1) switch (_context.prev = _context.next) {
                                case 0:
                                    return _context.abrupt("return", new Index(this.config, indexUid).fetchInfo());
                                case 1:
                                case "end":
                                    return _context.stop();
                            }
                        }, _callee, this);
                    }));
                    function getIndex(_x) {
                        return _getIndex.apply(this, arguments);
                    }
                    return getIndex;
                }()
                /**
                 * Gather information about an index by calling MeiliSearch and return the raw
                 * JSON response
                 *
                 * @param indexUid - The index UID
                 * @returns Promise returning index information
                 */
            )
        }, {
            key: "getRawIndex",
            value: (function () {
                    var _getRawIndex = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee2(indexUid) {
                        return _regeneratorRuntime().wrap(function _callee2$(_context2) {
                            while (1) switch (_context2.prev = _context2.next) {
                                case 0:
                                    return _context2.abrupt("return", new Index(this.config, indexUid).getRawInfo());
                                case 1:
                                case "end":
                                    return _context2.stop();
                            }
                        }, _callee2, this);
                    }));
                    function getRawIndex(_x2) {
                        return _getRawIndex.apply(this, arguments);
                    }
                    return getRawIndex;
                }()
                /**
                 * Get all the indexes as Index instances.
                 *
                 * @param parameters - Parameters to browse the indexes
                 * @returns Promise returning array of raw index information
                 */
            )
        }, {
            key: "getIndexes",
            value: (function () {
                    var _getIndexes = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee3() {
                        var _this = this;
                        var parameters,
                            rawIndexes,
                            indexes,
                            _args3 = arguments;
                        return _regeneratorRuntime().wrap(function _callee3$(_context3) {
                            while (1) switch (_context3.prev = _context3.next) {
                                case 0:
                                    parameters = _args3.length > 0 && _args3[0] !== undefined ? _args3[0] : {};
                                    _context3.next = 3;
                                    return this.getRawIndexes(parameters);
                                case 3:
                                    rawIndexes = _context3.sent;
                                    indexes = rawIndexes.results.map(function (index) {
                                        return new Index(_this.config, index.uid, index.primaryKey);
                                    });
                                    return _context3.abrupt("return", _objectSpread2(_objectSpread2({}, rawIndexes), {}, {
                                        results: indexes
                                    }));
                                case 6:
                                case "end":
                                    return _context3.stop();
                            }
                        }, _callee3, this);
                    }));
                    function getIndexes() {
                        return _getIndexes.apply(this, arguments);
                    }
                    return getIndexes;
                }()
                /**
                 * Get all the indexes in their raw value (no Index instances).
                 *
                 * @param parameters - Parameters to browse the indexes
                 * @returns Promise returning array of raw index information
                 */
            )
        }, {
            key: "getRawIndexes",
            value: (function () {
                    var _getRawIndexes = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee4() {
                        var parameters,
                            url,
                            _args4 = arguments;
                        return _regeneratorRuntime().wrap(function _callee4$(_context4) {
                            while (1) switch (_context4.prev = _context4.next) {
                                case 0:
                                    parameters = _args4.length > 0 && _args4[0] !== undefined ? _args4[0] : {};
                                    url = "indexes";
                                    _context4.next = 4;
                                    return this.httpRequest.get(url, parameters);
                                case 4:
                                    return _context4.abrupt("return", _context4.sent);
                                case 5:
                                case "end":
                                    return _context4.stop();
                            }
                        }, _callee4, this);
                    }));
                    function getRawIndexes() {
                        return _getRawIndexes.apply(this, arguments);
                    }
                    return getRawIndexes;
                }()
                /**
                 * Create a new index
                 *
                 * @param uid - The index UID
                 * @param options - Index options
                 * @returns Promise returning Index instance
                 */
            )
        }, {
            key: "createIndex",
            value: (function () {
                    var _createIndex = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee5(uid) {
                        var options,
                            _args5 = arguments;
                        return _regeneratorRuntime().wrap(function _callee5$(_context5) {
                            while (1) switch (_context5.prev = _context5.next) {
                                case 0:
                                    options = _args5.length > 1 && _args5[1] !== undefined ? _args5[1] : {};
                                    _context5.next = 3;
                                    return Index.create(uid, options, this.config);
                                case 3:
                                    return _context5.abrupt("return", _context5.sent);
                                case 4:
                                case "end":
                                    return _context5.stop();
                            }
                        }, _callee5, this);
                    }));
                    function createIndex(_x3) {
                        return _createIndex.apply(this, arguments);
                    }
                    return createIndex;
                }()
                /**
                 * Update an index
                 *
                 * @param uid - The index UID
                 * @param options - Index options to update
                 * @returns Promise returning Index instance after updating
                 */
            )
        }, {
            key: "updateIndex",
            value: (function () {
                    var _updateIndex = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee6(uid) {
                        var options,
                            _args6 = arguments;
                        return _regeneratorRuntime().wrap(function _callee6$(_context6) {
                            while (1) switch (_context6.prev = _context6.next) {
                                case 0:
                                    options = _args6.length > 1 && _args6[1] !== undefined ? _args6[1] : {};
                                    _context6.next = 3;
                                    return new Index(this.config, uid).update(options);
                                case 3:
                                    return _context6.abrupt("return", _context6.sent);
                                case 4:
                                case "end":
                                    return _context6.stop();
                            }
                        }, _callee6, this);
                    }));
                    function updateIndex(_x4) {
                        return _updateIndex.apply(this, arguments);
                    }
                    return updateIndex;
                }()
                /**
                 * Delete an index
                 *
                 * @param uid - The index UID
                 * @returns Promise which resolves when index is deleted successfully
                 */
            )
        }, {
            key: "deleteIndex",
            value: (function () {
                    var _deleteIndex = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee7(uid) {
                        return _regeneratorRuntime().wrap(function _callee7$(_context7) {
                            while (1) switch (_context7.prev = _context7.next) {
                                case 0:
                                    _context7.next = 2;
                                    return new Index(this.config, uid).delete();
                                case 2:
                                    return _context7.abrupt("return", _context7.sent);
                                case 3:
                                case "end":
                                    return _context7.stop();
                            }
                        }, _callee7, this);
                    }));
                    function deleteIndex(_x5) {
                        return _deleteIndex.apply(this, arguments);
                    }
                    return deleteIndex;
                }()
                /**
                 * Deletes an index if it already exists.
                 *
                 * @param uid - The index UID
                 * @returns Promise which resolves to true when index exists and is deleted
                 *   successfully, otherwise false if it does not exist
                 */
            )
        }, {
            key: "deleteIndexIfExists",
            value: (function () {
                    var _deleteIndexIfExists = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee8(uid) {
                        return _regeneratorRuntime().wrap(function _callee8$(_context8) {
                            while (1) switch (_context8.prev = _context8.next) {
                                case 0:
                                    _context8.prev = 0;
                                    _context8.next = 3;
                                    return this.deleteIndex(uid);
                                case 3:
                                    return _context8.abrupt("return", true);
                                case 6:
                                    _context8.prev = 6;
                                    _context8.t0 = _context8["catch"](0);
                                    if (!(_context8.t0.code === ErrorStatusCode.INDEX_NOT_FOUND)) {
                                        _context8.next = 10;
                                        break;
                                    }
                                    return _context8.abrupt("return", false);
                                case 10:
                                    throw _context8.t0;
                                case 11:
                                case "end":
                                    return _context8.stop();
                            }
                        }, _callee8, this, [[0, 6]]);
                    }));
                    function deleteIndexIfExists(_x6) {
                        return _deleteIndexIfExists.apply(this, arguments);
                    }
                    return deleteIndexIfExists;
                }()
                /**
                 * Swaps a list of index tuples.
                 *
                 * @param params - List of indexes tuples to swap.
                 * @returns Promise returning object of the enqueued task
                 */
            )
        }, {
            key: "swapIndexes",
            value: (function () {
                var _swapIndexes = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee9(params) {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee9$(_context9) {
                        while (1) switch (_context9.prev = _context9.next) {
                            case 0:
                                url = '/swap-indexes';
                                _context9.next = 3;
                                return this.httpRequest.post(url, params);
                            case 3:
                                return _context9.abrupt("return", _context9.sent);
                            case 4:
                            case "end":
                                return _context9.stop();
                        }
                    }, _callee9, this);
                }));
                function swapIndexes(_x7) {
                    return _swapIndexes.apply(this, arguments);
                }
                return swapIndexes;
            }())
        }, {
            key: "multiSearch",
            value: function () {
                var _multiSearch = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee10(queries, config) {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee10$(_context10) {
                        while (1) switch (_context10.prev = _context10.next) {
                            case 0:
                                url = "multi-search";
                                _context10.next = 3;
                                return this.httpRequest.post(url, queries, undefined, config);
                            case 3:
                                return _context10.abrupt("return", _context10.sent);
                            case 4:
                            case "end":
                                return _context10.stop();
                        }
                    }, _callee10, this);
                }));
                function multiSearch(_x8, _x9) {
                    return _multiSearch.apply(this, arguments);
                }
                return multiSearch;
            }() ///
            /// TASKS
            ///
            /**
             * Get the list of all client tasks
             *
             * @param parameters - Parameters to browse the tasks
             * @returns Promise returning all tasks
             */
        }, {
            key: "getTasks",
            value: function () {
                var _getTasks = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee11() {
                    var parameters,
                        _args11 = arguments;
                    return _regeneratorRuntime().wrap(function _callee11$(_context11) {
                        while (1) switch (_context11.prev = _context11.next) {
                            case 0:
                                parameters = _args11.length > 0 && _args11[0] !== undefined ? _args11[0] : {};
                                _context11.next = 3;
                                return this.tasks.getTasks(parameters);
                            case 3:
                                return _context11.abrupt("return", _context11.sent);
                            case 4:
                            case "end":
                                return _context11.stop();
                        }
                    }, _callee11, this);
                }));
                function getTasks() {
                    return _getTasks.apply(this, arguments);
                }
                return getTasks;
            }()
            /**
             * Get one task on the client scope
             *
             * @param taskUid - Task identifier
             * @returns Promise returning a task
             */
        }, {
            key: "getTask",
            value: (function () {
                    var _getTask = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee12(taskUid) {
                        return _regeneratorRuntime().wrap(function _callee12$(_context12) {
                            while (1) switch (_context12.prev = _context12.next) {
                                case 0:
                                    _context12.next = 2;
                                    return this.tasks.getTask(taskUid);
                                case 2:
                                    return _context12.abrupt("return", _context12.sent);
                                case 3:
                                case "end":
                                    return _context12.stop();
                            }
                        }, _callee12, this);
                    }));
                    function getTask(_x10) {
                        return _getTask.apply(this, arguments);
                    }
                    return getTask;
                }()
                /**
                 * Wait for multiple tasks to be finished.
                 *
                 * @param taskUids - Tasks identifier
                 * @param waitOptions - Options on timeout and interval
                 * @returns Promise returning an array of tasks
                 */
            )
        }, {
            key: "waitForTasks",
            value: (function () {
                    var _waitForTasks = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee13(taskUids) {
                        var _ref,
                            _ref$timeOutMs,
                            timeOutMs,
                            _ref$intervalMs,
                            intervalMs,
                            _args13 = arguments;
                        return _regeneratorRuntime().wrap(function _callee13$(_context13) {
                            while (1) switch (_context13.prev = _context13.next) {
                                case 0:
                                    _ref = _args13.length > 1 && _args13[1] !== undefined ? _args13[1] : {}, _ref$timeOutMs = _ref.timeOutMs, timeOutMs = _ref$timeOutMs === void 0 ? 5000 : _ref$timeOutMs, _ref$intervalMs = _ref.intervalMs, intervalMs = _ref$intervalMs === void 0 ? 50 : _ref$intervalMs;
                                    _context13.next = 3;
                                    return this.tasks.waitForTasks(taskUids, {
                                        timeOutMs: timeOutMs,
                                        intervalMs: intervalMs
                                    });
                                case 3:
                                    return _context13.abrupt("return", _context13.sent);
                                case 4:
                                case "end":
                                    return _context13.stop();
                            }
                        }, _callee13, this);
                    }));
                    function waitForTasks(_x11) {
                        return _waitForTasks.apply(this, arguments);
                    }
                    return waitForTasks;
                }()
                /**
                 * Wait for a task to be finished.
                 *
                 * @param taskUid - Task identifier
                 * @param waitOptions - Options on timeout and interval
                 * @returns Promise returning an array of tasks
                 */
            )
        }, {
            key: "waitForTask",
            value: (function () {
                    var _waitForTask = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee14(taskUid) {
                        var _ref2,
                            _ref2$timeOutMs,
                            timeOutMs,
                            _ref2$intervalMs,
                            intervalMs,
                            _args14 = arguments;
                        return _regeneratorRuntime().wrap(function _callee14$(_context14) {
                            while (1) switch (_context14.prev = _context14.next) {
                                case 0:
                                    _ref2 = _args14.length > 1 && _args14[1] !== undefined ? _args14[1] : {}, _ref2$timeOutMs = _ref2.timeOutMs, timeOutMs = _ref2$timeOutMs === void 0 ? 5000 : _ref2$timeOutMs, _ref2$intervalMs = _ref2.intervalMs, intervalMs = _ref2$intervalMs === void 0 ? 50 : _ref2$intervalMs;
                                    _context14.next = 3;
                                    return this.tasks.waitForTask(taskUid, {
                                        timeOutMs: timeOutMs,
                                        intervalMs: intervalMs
                                    });
                                case 3:
                                    return _context14.abrupt("return", _context14.sent);
                                case 4:
                                case "end":
                                    return _context14.stop();
                            }
                        }, _callee14, this);
                    }));
                    function waitForTask(_x12) {
                        return _waitForTask.apply(this, arguments);
                    }
                    return waitForTask;
                }()
                /**
                 * Cancel a list of enqueued or processing tasks.
                 *
                 * @param parameters - Parameters to filter the tasks.
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "cancelTasks",
            value: (function () {
                    var _cancelTasks = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee15(parameters) {
                        return _regeneratorRuntime().wrap(function _callee15$(_context15) {
                            while (1) switch (_context15.prev = _context15.next) {
                                case 0:
                                    _context15.next = 2;
                                    return this.tasks.cancelTasks(parameters);
                                case 2:
                                    return _context15.abrupt("return", _context15.sent);
                                case 3:
                                case "end":
                                    return _context15.stop();
                            }
                        }, _callee15, this);
                    }));
                    function cancelTasks(_x13) {
                        return _cancelTasks.apply(this, arguments);
                    }
                    return cancelTasks;
                }()
                /**
                 * Delete a list of tasks.
                 *
                 * @param parameters - Parameters to filter the tasks.
                 * @returns Promise containing an EnqueuedTask
                 */
            )
        }, {
            key: "deleteTasks",
            value: (function () {
                    var _deleteTasks = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee16() {
                        var parameters,
                            _args16 = arguments;
                        return _regeneratorRuntime().wrap(function _callee16$(_context16) {
                            while (1) switch (_context16.prev = _context16.next) {
                                case 0:
                                    parameters = _args16.length > 0 && _args16[0] !== undefined ? _args16[0] : {};
                                    _context16.next = 3;
                                    return this.tasks.deleteTasks(parameters);
                                case 3:
                                    return _context16.abrupt("return", _context16.sent);
                                case 4:
                                case "end":
                                    return _context16.stop();
                            }
                        }, _callee16, this);
                    }));
                    function deleteTasks() {
                        return _deleteTasks.apply(this, arguments);
                    }
                    return deleteTasks;
                }() ///
                /// KEYS
                ///
                /**
                 * Get all API keys
                 *
                 * @param parameters - Parameters to browse the indexes
                 * @returns Promise returning an object with keys
                 */
            )
        }, {
            key: "getKeys",
            value: function () {
                var _getKeys = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee17() {
                    var parameters,
                        url,
                        keys,
                        _args17 = arguments;
                    return _regeneratorRuntime().wrap(function _callee17$(_context17) {
                        while (1) switch (_context17.prev = _context17.next) {
                            case 0:
                                parameters = _args17.length > 0 && _args17[0] !== undefined ? _args17[0] : {};
                                url = "keys";
                                _context17.next = 4;
                                return this.httpRequest.get(url, parameters);
                            case 4:
                                keys = _context17.sent;
                                keys.results = keys.results.map(function (key) {
                                    return _objectSpread2(_objectSpread2({}, key), {}, {
                                        createdAt: new Date(key.createdAt),
                                        updatedAt: new Date(key.updatedAt)
                                    });
                                });
                                return _context17.abrupt("return", keys);
                            case 7:
                            case "end":
                                return _context17.stop();
                        }
                    }, _callee17, this);
                }));
                function getKeys() {
                    return _getKeys.apply(this, arguments);
                }
                return getKeys;
            }()
            /**
             * Get one API key
             *
             * @param keyOrUid - Key or uid of the API key
             * @returns Promise returning a key
             */
        }, {
            key: "getKey",
            value: (function () {
                    var _getKey = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee18(keyOrUid) {
                        var url;
                        return _regeneratorRuntime().wrap(function _callee18$(_context18) {
                            while (1) switch (_context18.prev = _context18.next) {
                                case 0:
                                    url = "keys/".concat(keyOrUid);
                                    _context18.next = 3;
                                    return this.httpRequest.get(url);
                                case 3:
                                    return _context18.abrupt("return", _context18.sent);
                                case 4:
                                case "end":
                                    return _context18.stop();
                            }
                        }, _callee18, this);
                    }));
                    function getKey(_x14) {
                        return _getKey.apply(this, arguments);
                    }
                    return getKey;
                }()
                /**
                 * Create one API key
                 *
                 * @param options - Key options
                 * @returns Promise returning a key
                 */
            )
        }, {
            key: "createKey",
            value: (function () {
                    var _createKey = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee19(options) {
                        var url;
                        return _regeneratorRuntime().wrap(function _callee19$(_context19) {
                            while (1) switch (_context19.prev = _context19.next) {
                                case 0:
                                    url = "keys";
                                    _context19.next = 3;
                                    return this.httpRequest.post(url, options);
                                case 3:
                                    return _context19.abrupt("return", _context19.sent);
                                case 4:
                                case "end":
                                    return _context19.stop();
                            }
                        }, _callee19, this);
                    }));
                    function createKey(_x15) {
                        return _createKey.apply(this, arguments);
                    }
                    return createKey;
                }()
                /**
                 * Update one API key
                 *
                 * @param keyOrUid - Key
                 * @param options - Key options
                 * @returns Promise returning a key
                 */
            )
        }, {
            key: "updateKey",
            value: (function () {
                    var _updateKey = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee20(keyOrUid, options) {
                        var url;
                        return _regeneratorRuntime().wrap(function _callee20$(_context20) {
                            while (1) switch (_context20.prev = _context20.next) {
                                case 0:
                                    url = "keys/".concat(keyOrUid);
                                    _context20.next = 3;
                                    return this.httpRequest.patch(url, options);
                                case 3:
                                    return _context20.abrupt("return", _context20.sent);
                                case 4:
                                case "end":
                                    return _context20.stop();
                            }
                        }, _callee20, this);
                    }));
                    function updateKey(_x16, _x17) {
                        return _updateKey.apply(this, arguments);
                    }
                    return updateKey;
                }()
                /**
                 * Delete one API key
                 *
                 * @param keyOrUid - Key
                 * @returns
                 */
            )
        }, {
            key: "deleteKey",
            value: (function () {
                    var _deleteKey = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee21(keyOrUid) {
                        var url;
                        return _regeneratorRuntime().wrap(function _callee21$(_context21) {
                            while (1) switch (_context21.prev = _context21.next) {
                                case 0:
                                    url = "keys/".concat(keyOrUid);
                                    _context21.next = 3;
                                    return this.httpRequest.delete(url);
                                case 3:
                                    return _context21.abrupt("return", _context21.sent);
                                case 4:
                                case "end":
                                    return _context21.stop();
                            }
                        }, _callee21, this);
                    }));
                    function deleteKey(_x18) {
                        return _deleteKey.apply(this, arguments);
                    }
                    return deleteKey;
                }() ///
                /// HEALTH
                ///
                /**
                 * Checks if the server is healthy, otherwise an error will be thrown.
                 *
                 * @returns Promise returning an object with health details
                 */
            )
        }, {
            key: "health",
            value: function () {
                var _health = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee22() {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee22$(_context22) {
                        while (1) switch (_context22.prev = _context22.next) {
                            case 0:
                                url = "health";
                                _context22.next = 3;
                                return this.httpRequest.get(url);
                            case 3:
                                return _context22.abrupt("return", _context22.sent);
                            case 4:
                            case "end":
                                return _context22.stop();
                        }
                    }, _callee22, this);
                }));
                function health() {
                    return _health.apply(this, arguments);
                }
                return health;
            }()
            /**
             * Checks if the server is healthy, return true or false.
             *
             * @returns Promise returning a boolean
             */
        }, {
            key: "isHealthy",
            value: (function () {
                    var _isHealthy = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee23() {
                        var url;
                        return _regeneratorRuntime().wrap(function _callee23$(_context23) {
                            while (1) switch (_context23.prev = _context23.next) {
                                case 0:
                                    _context23.prev = 0;
                                    url = "health";
                                    _context23.next = 4;
                                    return this.httpRequest.get(url);
                                case 4:
                                    return _context23.abrupt("return", true);
                                case 7:
                                    _context23.prev = 7;
                                    _context23.t0 = _context23["catch"](0);
                                    return _context23.abrupt("return", false);
                                case 10:
                                case "end":
                                    return _context23.stop();
                            }
                        }, _callee23, this, [[0, 7]]);
                    }));
                    function isHealthy() {
                        return _isHealthy.apply(this, arguments);
                    }
                    return isHealthy;
                }() ///
                /// STATS
                ///
                /**
                 * Get the stats of all the database
                 *
                 * @returns Promise returning object of all the stats
                 */
            )
        }, {
            key: "getStats",
            value: function () {
                var _getStats = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee24() {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee24$(_context24) {
                        while (1) switch (_context24.prev = _context24.next) {
                            case 0:
                                url = "stats";
                                _context24.next = 3;
                                return this.httpRequest.get(url);
                            case 3:
                                return _context24.abrupt("return", _context24.sent);
                            case 4:
                            case "end":
                                return _context24.stop();
                        }
                    }, _callee24, this);
                }));
                function getStats() {
                    return _getStats.apply(this, arguments);
                }
                return getStats;
            }() ///
            /// VERSION
            ///
            /**
             * Get the version of MeiliSearch
             *
             * @returns Promise returning object with version details
             */
        }, {
            key: "getVersion",
            value: function () {
                var _getVersion = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee25() {
                    var url;
                    return _regeneratorRuntime().wrap(function _callee25$(_context25) {
                        while (1) switch (_context25.prev = _context25.next) {
                            case 0:
                                url = "version";
                                _context25.next = 3;
                                return this.httpRequest.get(url);
                            case 3:
                                return _context25.abrupt("return", _context25.sent);
                            case 4:
                            case "end":
                                return _context25.stop();
                        }
                    }, _callee25, this);
                }));
                function getVersion() {
                    return _getVersion.apply(this, arguments);
                }
                return getVersion;
            }() ///
            /// DUMPS
            ///
            /**
             * Creates a dump
             *
             * @returns Promise returning object of the enqueued task
             */
        }, {
            key: "createDump",
            value: function () {
                var _createDump = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee26() {
                    var url, task;
                    return _regeneratorRuntime().wrap(function _callee26$(_context26) {
                        while (1) switch (_context26.prev = _context26.next) {
                            case 0:
                                url = "dumps";
                                _context26.next = 3;
                                return this.httpRequest.post(url);
                            case 3:
                                task = _context26.sent;
                                return _context26.abrupt("return", new EnqueuedTask(task));
                            case 5:
                            case "end":
                                return _context26.stop();
                        }
                    }, _callee26, this);
                }));
                function createDump() {
                    return _createDump.apply(this, arguments);
                }
                return createDump;
            }() ///
            /// SNAPSHOTS
            ///
            /**
             * Creates a snapshot
             *
             * @returns Promise returning object of the enqueued task
             */
        }, {
            key: "createSnapshot",
            value: function () {
                var _createSnapshot = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee27() {
                    var url, task;
                    return _regeneratorRuntime().wrap(function _callee27$(_context27) {
                        while (1) switch (_context27.prev = _context27.next) {
                            case 0:
                                url = "snapshots";
                                _context27.next = 3;
                                return this.httpRequest.post(url);
                            case 3:
                                task = _context27.sent;
                                return _context27.abrupt("return", new EnqueuedTask(task));
                            case 5:
                            case "end":
                                return _context27.stop();
                        }
                    }, _callee27, this);
                }));
                function createSnapshot() {
                    return _createSnapshot.apply(this, arguments);
                }
                return createSnapshot;
            }() ///
            /// TOKENS
            ///
            /**
             * Generate a tenant token
             *
             * @param apiKeyUid - The uid of the api key used as issuer of the token.
             * @param searchRules - Search rules that are applied to every search.
             * @param options - Token options to customize some aspect of the token.
             * @returns The token in JWT format.
             */
        }, {
            key: "generateTenantToken",
            value: function generateTenantToken(_apiKeyUid, _searchRules, _options) {
                var error = new Error();
                error.message = "Meilisearch: failed to generate a tenant token. Generation of a token only works in a node environment \n ".concat(error.stack, ".");
                return Promise.reject(error);
            }
        }]);
        return Client;
    }();

    var MeiliSearch = /*#__PURE__*/function (_Client) {
        _inherits(MeiliSearch, _Client);
        var _super = _createSuper(MeiliSearch);
        function MeiliSearch(config) {
            _classCallCheck(this, MeiliSearch);
            return _super.call(this, config);
        }
        return _createClass(MeiliSearch);
    }(Client);

    exports.ContentTypeEnum = ContentTypeEnum;
    exports.ErrorStatusCode = ErrorStatusCode;
    exports.Index = Index;
    exports.MatchingStrategies = MatchingStrategies;
    exports.MeiliSearch = MeiliSearch;
    exports.MeiliSearchApiError = MeiliSearchApiError;
    exports.MeiliSearchError = MeiliSearchError;
    exports.MeiliSearchRequestError = MeiliSearchRequestError;
    exports.MeiliSearchTimeOutError = MeiliSearchTimeOutError;
    exports.Meilisearch = MeiliSearch;
    exports.TaskStatus = TaskStatus;
    exports.TaskTypes = TaskTypes;
    exports["default"] = MeiliSearch;
    exports.versionErrorHintMessage = versionErrorHintMessage;

    Object.defineProperty(exports, '__esModule', { value: true });

}));
