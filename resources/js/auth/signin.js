// 外部库 JSEncrypt 和 FingerprintJS 将在 Blade 文件中通过 CDN 引入
// 因此在这里我们假设它们已经是全局可用的

/**
 * Securely encrypts browser fingerprint data using a hybrid encryption scheme.
 * This function is exposed on the global window object for use by other scripts.
 *
 * @returns {Promise<string>} A JSON string containing the encrypted key and data.
 * @throws {Error} Throws a user-friendly error if the encryption process fails.
 */
async function getEncryptedFingerprint() {
    try {
        const publicKey =
            document.getElementById("encryption-data")?.dataset.publicKey;
        if (!publicKey) {
            // Do not reveal the exact reason for failure.
            throw new Error("客户端安全组件初始化失败。");
        }

        const aesKey = CryptoJS.lib.WordArray.random(256 / 8);
        const iv = CryptoJS.lib.WordArray.random(128 / 8);

        const fp = await FingerprintJS.load();
        const result = await fp.get();
        const fingerprintData = {
            visitorId: result.visitorId,
            components: result.components,
        };
        const fingerprintJson = JSON.stringify(fingerprintData);

        const encryptedFingerprint = CryptoJS.AES.encrypt(
            fingerprintJson,
            aesKey,
            {
                iv: iv,
                mode: CryptoJS.mode.CBC,
                padding: CryptoJS.pad.Pkcs7,
            }
        ).toString();

        const keyPayload = {
            key: CryptoJS.enc.Base64.stringify(aesKey),
            iv: CryptoJS.enc.Base64.stringify(iv),
        };

        const encryptRsa = new JSEncrypt();
        encryptRsa.setPublicKey(publicKey);
        const encryptedKey = encryptRsa.encrypt(JSON.stringify(keyPayload));

        if (!encryptedKey) {
            // Generic error message
            throw new Error("客户端数据加密失败。");
        }

        const finalPayload = {
            key: encryptedKey,
            data: encryptedFingerprint,
        };

        return JSON.stringify(finalPayload);
    } catch (error) {
        // Log the detailed technical error for developers
        console.error("Fingerprint encryption failed:", error);

        // Throw a new, generic, user-friendly error to the caller
        throw new Error("客户端安全检查失败，请刷新后重试。");
    }
}

// Expose the core function to the global scope
window.getEncryptedFingerprint = getEncryptedFingerprint;
