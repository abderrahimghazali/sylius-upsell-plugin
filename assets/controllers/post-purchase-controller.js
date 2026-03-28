import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['modal'];

    static values = {
        offerUrl: String,
        acceptUrl: String,
    };

    offer = null;
    placeOrderForm = null;

    esc(str) {
        const d = document.createElement('div');
        d.textContent = str ?? '';
        return d.innerHTML;
    }

    connect() {
        this.placeOrderForm = document.querySelector(
            'form[name="sylius_checkout_complete"]',
        );
        if (!this.placeOrderForm) return;

        this.placeOrderForm.addEventListener('submit', (e) => {
            if (this._dismissed || this._accepted) return;
            e.preventDefault();

            if (this.offer) {
                this.showModal();
            } else {
                this.fetchOffer();
            }
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

        const e = (s) => this.esc(s);
        const imageUrl = offer.product.imageUrl || '';
        const imageHtml = imageUrl
            ? `<img src="${e(imageUrl)}" alt="${e(offer.product.name)}" style="width:120px;border-radius:8px" loading="lazy" />`
            : '';

        const discountBadge =
            offer.discountPercent > 0
                ? `<span class="upsell-modal__badge">-${offer.discountPercent}%</span>`
                : '';

        modal.innerHTML = `
            <div class="upsell-modal__overlay">
                <div class="upsell-modal__dialog">
                    <h3 class="upsell-modal__headline">${e(offer.headline)}</h3>
                    ${offer.body ? `<p class="upsell-modal__body">${e(offer.body)}</p>` : ''}
                    <div class="upsell-modal__product">
                        <div class="upsell-modal__product-image">
                            ${imageHtml}
                            ${discountBadge}
                        </div>
                        <div class="upsell-modal__product-info">
                            <h4 class="upsell-modal__product-name">${e(offer.product.name)}</h4>
                            <div class="upsell-modal__pricing">
                                ${offer.discountPercent > 0 ? `<span class="upsell-modal__price--original">${originalPrice}</span>` : ''}
                                <span class="upsell-modal__price--current">${discountedPrice}</span>
                            </div>
                        </div>
                    </div>
                    <div class="upsell-modal__actions">
                        <button type="button" class="upsell-modal__cta" data-action="click->abderrahimghazali--sylius-upsell-plugin--post-purchase#accept">
                            ${e(offer.ctaLabel)}
                        </button>
                        <button type="button" class="upsell-modal__decline" data-action="click->abderrahimghazali--sylius-upsell-plugin--post-purchase#decline">
                            ${e(offer.declineLabel)}
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
            // Build accept URL with the actual offer ID
            const acceptUrl = this.acceptUrlValue.replace('/0', `/${this.offer.offerId}`);

            const response = await fetch(acceptUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': this.offer.csrfToken || '',
                },
            });

            if (!response.ok) {
                console.error('[UpsellPlugin] Failed to add to cart', await response.text());
            }
        } catch (error) {
            console.error('[UpsellPlugin] Accept error:', error);
        }

        this._accepted = true;
        this.hideModal();
        const locale = window.location.pathname.split('/')[1] || 'en_US';
        window.location.href = '/' + locale + '/cart/';
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
