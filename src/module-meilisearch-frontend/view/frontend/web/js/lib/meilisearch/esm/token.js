function getOptionsWithDefaults(options) {
    const {
        searchRules = ["*"],
        algorithm = "HS256",
        force = false,
        ...restOfOptions
    } = options;
    return { searchRules, algorithm, force, ...restOfOptions };
}
const UUID_V4_REGEXP = /^[0-9a-f]{8}\b(?:-[0-9a-f]{4}\b){3}-[0-9a-f]{12}$/i;
function isValidUUIDv4(uuid) {
    return UUID_V4_REGEXP.test(uuid);
}
function encodeToBase64(data) {
    return btoa(typeof data === "string" ? data : JSON.stringify(data));
}
const textEncoder = new TextEncoder();
async function sign({ apiKey, algorithm }, encodedPayload, encodedHeader) {
    const cryptoKey = await crypto.subtle.importKey(
        // https://developer.mozilla.org/en-US/docs/Web/API/SubtleCrypto/importKey#raw
        "raw",
        textEncoder.encode(apiKey),
        // https://developer.mozilla.org/en-US/docs/Web/API/HmacImportParams#instance_properties
        { name: "HMAC", hash: `SHA-${algorithm.slice(2)}` },
        // https://developer.mozilla.org/en-US/docs/Web/API/SubtleCrypto/importKey#extractable
        false,
        // https://developer.mozilla.org/en-US/docs/Web/API/SubtleCrypto/importKey#keyusages
        ["sign"]
    );
    const signature = await crypto.subtle.sign(
        "HMAC",
        cryptoKey,
        textEncoder.encode(`${encodedHeader}.${encodedPayload}`)
    );
    const digest = btoa(String.fromCharCode(...new Uint8Array(signature))).replace(/\+/g, "-").replace(/\//g, "_").replace(/=/g, "");
    return digest;
}
function getHeader({
                       algorithm: alg
                   }) {
    const header = { alg, typ: "JWT" };
    return encodeToBase64(header).replace(/=/g, "");
}
function getPayload({
                        searchRules,
                        apiKeyUid,
                        expiresAt
                    }) {
    if (!isValidUUIDv4(apiKeyUid)) {
        throw new Error("the uid of your key is not a valid UUIDv4");
    }
    const payload = { searchRules, apiKeyUid };
    if (expiresAt !== void 0) {
        payload.exp = typeof expiresAt === "number" ? expiresAt : (
            // To get from a Date object the number of seconds since Unix epoch, i.e. Unix timestamp:
            Math.floor(expiresAt.getTime() / 1e3)
        );
    }
    return encodeToBase64(payload).replace(/=/g, "");
}
function tryDetectEnvironment() {
    if (typeof navigator !== "undefined" && "userAgent" in navigator) {
        const { userAgent } = navigator;
        if (userAgent.startsWith("Node") || userAgent.startsWith("Deno") || userAgent.startsWith("Bun") || userAgent.startsWith("Cloudflare-Workers")) {
            return;
        }
    }
    const versions = globalThis.process?.versions;
    if (versions !== void 0 && Object.hasOwn(versions, "node")) {
        return;
    }
    throw new Error(
        "failed to detect a server-side environment; do not generate tokens on the frontend in production!\nuse the `force` option to disable environment detection, consult the documentation (Use at your own risk!)"
    );
}
async function generateTenantToken(options) {
    const optionsWithDefaults = getOptionsWithDefaults(options);
    if (!optionsWithDefaults.force) {
        tryDetectEnvironment();
    }
    const encodedPayload = getPayload(optionsWithDefaults);
    const encodedHeader = getHeader(optionsWithDefaults);
    const signature = await sign(
        optionsWithDefaults,
        encodedPayload,
        encodedHeader
    );
    return `${encodedHeader}.${encodedPayload}.${signature}`;
}
export {
    generateTenantToken
};
//# sourceMappingURL=token.js.map
