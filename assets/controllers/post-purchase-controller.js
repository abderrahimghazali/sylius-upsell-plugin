import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        offerUrl: String,
        acceptUrlBase: String,
        declineUrl: String,
        impressionUrl: String,
    };

    connect() {
        this.offer = null;
        this.intercepted = false;
        this.form = document.querySelector('form[name="sylius_checkout_complete"]');

        if (!this.form || !this.offerUrlValue) return;

        this.form.addEventListener('submit', this.handleSubmit.bind(this));
    }

    handleSubmit(e) {
        if (this.intercepted) return;
        this.intercepted = true;
        e.preventDefault();

        fetch(this.offerUrlValue)
            .then((r) => {
                if (!r.ok || r.status === 204) {
                    this.form.submit();
                    return;
                }
                return r.json();
            })
            .then((data) => {
                if (data) this.showModal(data);
                else this.form.submit();
            })
            .catch(() => this.form.submit());
    }

    showModal(data) {
        this.offer = data;
        // Dispatch custom event so the inline template can render the modal
        this.element.dispatchEvent(
            new CustomEvent('upsell:offer-received', {
                detail: data,
                bubbles: true,
            }),
        );
    }

    accept() {
        if (!this.offer) return;

        const url = this.acceptUrlBaseValue.replace('/0', '/' + this.offer.offerId);
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': this.offer.csrfToken || '',
            },
        })
            .then((r) => r.json())
            .then(() => {
                const locale =
                    window.location.pathname.split('/')[1] || 'en_US';
                window.location.href = '/' + locale + '/cart/';
            })
            .catch(() => this.form.submit());
    }

    decline() {
        if (this.declineUrlValue && this.offer) {
            fetch(this.declineUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': this.offer.csrfToken || '',
                },
            }).catch(() => {});
        }
        this.form.submit();
    }
}
