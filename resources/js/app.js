import "./bootstrap";

import Alpine from "alpinejs";
import { browserSupportsWebAuthn, startRegistration, startAuthentication } from "@simplewebauthn/browser";

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

            try {

                const passkey = await startRegistration(options.data);
            } catch (e) {
                this.errors = {
                    name: ["Passkey registration failed."],
                };
                return;
            }

            form.addEventListener("formdata", ({ formData }) => {
                formData.set("passkey", JSON.stringify(passkey));
            });

            form.submit();
        },
    }));

    Alpine.data("authenticatePasskey", () => ({
        errors: null,
        browserSupportsWebAuthn,
        async authenticate() {
            const options = await axios.get("/api/passkeys/authenticate");

            const answer = await startAuthentication(options.data);

        },
    }));
});

Alpine.start();
