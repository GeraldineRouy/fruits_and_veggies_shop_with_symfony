import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['dropdown'];

    connect() {
        this.showTimeout = null;
        this.hideTimeout = null;
        this.loaded = false;
        if (this.hasDropdownTarget) {
            this.dropdownTarget.classList.add('hidden');
        }
    }

    disconnect() {
        clearTimeout(this.showTimeout);
        clearTimeout(this.hideTimeout);
    }

    mouseEnter() {
        clearTimeout(this.hideTimeout);
        this.showTimeout = setTimeout(() => this.show(), 200);
    }

    mouseLeave() {
        clearTimeout(this.showTimeout);
        this.hideTimeout = setTimeout(() => this.hide(), 300);
    }

    show() {
        if (!this.hasDropdownTarget) return;
        this.dropdownTarget.classList.remove('hidden');
        if (!this.loaded) {
            this.load();
        }
    }

    hide() {
        if (!this.hasDropdownTarget) return;
        this.dropdownTarget.classList.add('hidden');
    }

    async load() {
        try {
            const response = await fetch('/panier/preview');
            if (!response.ok) return;
            const html = await response.text();
            if (this.hasDropdownTarget) {
                this.dropdownTarget.innerHTML = html;
                this.loaded = true;
            }
        } catch (e) {
            // Silently fail
        }
    }
}
