import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['checkbox', 'button', 'count', 'confirmation'];

    static values = {
        addToCartUrl: String,
        token: String,
        impressionUrl: String,
        productCodes: String,
    };

    connect() {
        this.updateButton();
        this.trackImpression('shown');
    }

    toggle() {
        this.updateButton();
    }

    updateButton() {
        const checked = this.getCheckedItems();
        const count = checked.length;

        this.countTarget.textContent = count;
        this.buttonTarget.disabled = count === 0;
    }

    getCheckedItems() {
        return this.checkboxTargets.filter((cb) => cb.checked);
    }

    async addAllToCart(event) {
        event.preventDefault();

        const checked = this.getCheckedItems();

        if (checked.length === 0) {
            return;
        }

        this.buttonTarget.disabled = true;
        this.buttonTarget.classList.add('loading');

        try {
            const promises = checked.map((cb) => {
                const variantCode = cb.dataset.variantCode;
                const quantity = 1;

                return fetch(this.addToCartUrlValue, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/ld+json',
                        Accept: 'application/ld+json',
                    },
                    body: JSON.stringify({
                        productVariant: `/api/v2/shop/product-variants/${variantCode}`,
                        quantity: quantity,
                    }),
                });
            });

            const responses = await Promise.all(promises);
            const allOk = responses.every((r) => r.ok);

            if (allOk) {
                this.trackImpression('accepted');
                this.showConfirmation();
            } else {
                console.error(
                    'Some items could not be added to cart',
                    responses,
                );
            }
        } catch (error) {
            console.error('Failed to add items to cart:', error);
        } finally {
            this.buttonTarget.disabled = false;
            this.buttonTarget.classList.remove('loading');
        }
    }

    showConfirmation() {
        this.confirmationTarget.classList.remove('hidden');
        this.confirmationTarget.classList.add('visible');

        setTimeout(() => {
            this.confirmationTarget.classList.remove('visible');
            this.confirmationTarget.classList.add('hidden');
        }, 3000);
    }

    trackImpression(action) {
        if (!this.impressionUrlValue) return;

        fetch(this.impressionUrlValue, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                type: 'fbt',
                productCode: this.productCodesValue || '',
                action: action,
            }),
        }).catch(() => {});
    }
}
