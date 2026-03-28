import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['modal'];

    static values = {
        offerUrl: String,
        cartToken: String,
        addToCartUrl: String,
    };

    offer = null;
    placeOrderForm = null;

    connect() {
        this.placeOrderForm = document.querySelector(
            'form[name="sylius_checkout_complete"]',
        );
        if (!this.placeOrderForm) return;

        this.placeOrderForm.addEventListener('submit', (e) => {
            if (!this.offer || this._dismissed || this._accepted) return;
            e.preventDefault();
            this.fetchOffer();
        });
    }

    async fetchOffer() {
        try {
            const response = await fetch(this.offerUrlValue);
            if (!response.ok || response.status === 204) {
                this.submitForm();
                return;
            }
            this.offer = await response.json();
            this.showModal();
        } catch (error) {
            console.error('[UpsellPlugin] Failed to fetch offer:', error);
            this.submitForm();
        }
    }

    showModal() {
        const offer = this.offer;
        const modal = this.modalTarget;

        const originalPrice = this.formatPrice(
            offer.product.originalPrice,
            offer.product.currency,
        );
        const discountedPrice = this.formatPrice(
            offer.product.discountedPrice,
            offer.product.currency,
        );

        const imageHtml = offer.product.image
            ? `<img src="/media/image/${offer.product.image}" alt="${offer.product.name}" style="width:120px;border-radius:8px" loading="lazy" />`
            : '';

        const discountBadge =
            offer.discountPercent > 0
                ? `<span class="upsell-modal__badge">-${offer.discountPercent}%</span>`
                : '';

        modal.innerHTML = `
            <div class="upsell-modal__overlay">
                <div class="upsell-modal__dialog">
                    <h3 class="upsell-modal__headline">${offer.headline}</h3>
                    ${offer.body ? `<p class="upsell-modal__body">${offer.body}</p>` : ''}
                    <div class="upsell-modal__product">
                        <div class="upsell-modal__product-image">
                            ${imageHtml}
                            ${discountBadge}
                        </div>
                        <div class="upsell-modal__product-info">
                            <h4 class="upsell-modal__product-name">${offer.product.name}</h4>
                            <div class="upsell-modal__pricing">
                                ${offer.discountPercent > 0 ? `<span class="upsell-modal__price--original">${originalPrice}</span>` : ''}
                                <span class="upsell-modal__price--current">${discountedPrice}</span>
                            </div>
                        </div>
                    </div>
                    <div class="upsell-modal__actions">
                        <button type="button" class="upsell-modal__cta" data-action="click->abderrahimghazali--sylius-upsell-plugin--post-purchase#accept">
                            ${offer.ctaLabel}
                        </button>
                        <button type="button" class="upsell-modal__decline" data-action="click->abderrahimghazali--sylius-upsell-plugin--post-purchase#decline">
                            ${offer.declineLabel}
                        </button>
                    </div>
                </div>
            </div>
        `;

        modal.classList.remove('upsell-hidden');
    }

    async accept(event) {
        event.preventDefault();
        const cta = event.currentTarget;
        cta.disabled = true;
        cta.textContent = '...';

        try {
            const response = await fetch(this.addToCartUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/ld+json',
                    Accept: 'application/ld+json',
                },
                body: JSON.stringify({
                    productVariant: `/api/v2/shop/product-variants/${this.offer.product.variantCode}`,
                    quantity: 1,
                }),
            });

            if (!response.ok) {
                console.error('[UpsellPlugin] Failed to add to cart', await response.text());
            }
        } catch (error) {
            console.error('[UpsellPlugin] Cart API error:', error);
        }

        this._accepted = true;
        this.hideModal();
        this.submitForm();
    }

    decline(event) {
        event.preventDefault();
        this._dismissed = true;
        this.hideModal();
        this.submitForm();
    }

    hideModal() {
        this.modalTarget.classList.add('upsell-hidden');
    }

    submitForm() {
        this.placeOrderForm.submit();
    }

    formatPrice(amountInCents, currencyCode) {
        const amount = amountInCents / 100;
        try {
            return new Intl.NumberFormat(
                document.documentElement.lang || 'en',
                { style: 'currency', currency: currencyCode || 'USD' },
            ).format(amount);
        } catch {
            return `${currencyCode} ${amount.toFixed(2)}`;
        }
    }
}
