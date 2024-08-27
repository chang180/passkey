import "./bootstrap";
import Alpine from "alpinejs";
import {
    browserSupportsWebAuthn,
    startRegistration,
    startAuthentication,
} from "@simplewebauthn/browser";

window.Alpine = Alpine;

document.addEventListener("alpine:init", () => {
    Alpine.data("registerPasskey", () => ({
        name: "",
        errors: null,
        browserSupportsWebAuthn,
        async register(form) {
            this.errors = null;

            if (!this.browserSupportsWebAuthn()) {
                this.errors = {
                    name: ["Your browser does not support WebAuthn"],
                };
                return;
            }

            try {
                const options = await axios.get("/api/passkeys/register", {
                    params: {
                        name: this.name,
                    },
                    validateStatus: (status) => [200, 422].includes(status),
                });

                if (options.status === 422) {
                    this.errors = options.data.errors;
                    return;
                }

                // 檢查和處理未定義的屬性
                if (options.data.publicKey) {
                    if (options.data.publicKey.attestation === "unknown_value") {
                        delete options.data.publicKey.attestation;
                    }

                    if (
                        options.data.publicKey.authenticatorSelection &&
                        options.data.publicKey.authenticatorSelection.authenticatorAttachment === "unknown_value"
                    ) {
                        delete options.data.publicKey.authenticatorSelection.authenticatorAttachment;
                    }
                }

                const passkey = await startRegistration(options.data);

                form.addEventListener("formdata", ({ formData }) => {
                    formData.set("passkey", JSON.stringify(passkey));
                });

                form.submit();

            } catch (e) {
                console.error("Registration failed:", e);
                this.errors = {
                    name: ["Passkey registration failed."],
                };
            }
        },
    }));

    Alpine.data("authenticatePasskey", () => ({
        errors: null,
        browserSupportsWebAuthn,
        async authenticate() {
            try {
                const options = await axios.get("/api/passkeys/authenticate");

                if (options.status !== 200) {
                    this.errors = {
                        name: ["Authentication options retrieval failed."],
                    };
                    return;
                }

                const answer = await startAuthentication(options.data);

            } catch (e) {
                console.error("Authentication failed:", e);
                this.errors = {
                    name: ["Passkey authentication failed."],
                };
            }
        },
    }));
});

Alpine.start();
