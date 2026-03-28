import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['container'];

    static values = {
        offerUrl: String,
        acceptUrl: String,
        orderToken: String,
    };

    connect() {
        const dismissed = localStorage.getItem(
            `upsell_dismissed_${this.orderTokenValue}`,
        );
        if (dismissed) {
            return;
        }

        this.fetchOffer();
    }

    async fetchOffer() {
        try {
            const response = await fetch(this.offerUrlValue);

            if (!response.ok || response.status === 204) {
                return;
            }

            const offer = await response.json();
            this.renderOffer(offer);
        } catch (error) {
            console.error('[UpsellPlugin] Failed to fetch offer:', error);
        }
    }

    renderOffer(offer) {
        const container = this.containerTarget;

        const originalPriceFormatted = this.formatPrice(
            offer.product.originalPrice,
            offer.product.currency,
        );
        const discountedPriceFormatted = this.formatPrice(
            offer.product.discountedPrice,
            offer.product.currency,
        );

        const imageHtml = offer.product.image
            ? `<img src="/media/image/${offer.product.image}" alt="${offer.product.name}" class="upsell-offer__image" loading="lazy" />`
            : '';

        const discountBadge =
            offer.discountPercent > 0
                ? `<span class="upsell-offer__badge">-${offer.discountPercent}%</span>`
                : '';

        container.innerHTML = `
            <div class="upsell-offer" data-offer-id="${offer.offerId}">
                <div class="upsell-offer__content">
                    <h3 class="upsell-offer__headline">${offer.headline}</h3>
                    ${offer.body ? `<p class="upsell-offer__body">${offer.body}</p>` : ''}

                    <div class="upsell-offer__product">
                        <div class="upsell-offer__product-image">
                            ${imageHtml}
                            ${discountBadge}
                        </div>
                        <div class="upsell-offer__product-info">
                            <h4 class="upsell-offer__product-name">${offer.product.name}</h4>
                            <div class="upsell-offer__pricing">
                                ${offer.discountPercent > 0 ? `<span class="upsell-offer__price--original">${originalPriceFormatted}</span>` : ''}
                                <span class="upsell-offer__price--current">${discountedPriceFormatted}</span>
                            </div>
                        </div>
                    </div>

                    <div class="upsell-offer__actions">
                        <button class="upsell-offer__cta" data-action="click->post-purchase#accept">
                            ${offer.ctaLabel}
                        </button>
                        <button class="upsell-offer__decline" data-action="click->post-purchase#decline">
                            ${offer.declineLabel}
                        </button>
                    </div>
                </div>
            </div>
        `;

        container.classList.remove('hidden');
    }

    async accept(event) {
        event.preventDefault();

        const ctaButton = event.currentTarget;
        ctaButton.disabled = true;
        ctaButton.textContent = '...';

        try {
            const response = await fetch(this.acceptUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Failed to accept offer');
            }

            const data = await response.json();

            this.dismiss();
            window.location.href = data.checkoutUrl;
        } catch (error) {
            console.error('[UpsellPlugin] Failed to accept offer:', error);
            ctaButton.disabled = false;
            ctaButton.textContent = 'Try again';
        }
    }

    decline(event) {
        event.preventDefault();
        this.dismiss();
        this.containerTarget.classList.add('hidden');
    }

    dismiss() {
        localStorage.setItem(
            `upsell_dismissed_${this.orderTokenValue}`,
            '1',
        );
    }

    formatPrice(amountInCents, currencyCode) {
        const amount = amountInCents / 100;

        try {
            return new Intl.NumberFormat(document.documentElement.lang || 'en', {
                style: 'currency',
                currency: currencyCode || 'USD',
            }).format(amount);
        } catch {
            return `${currencyCode} ${amount.toFixed(2)}`;
        }
    }
}
