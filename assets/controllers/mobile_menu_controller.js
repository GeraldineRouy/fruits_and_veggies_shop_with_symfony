import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu'];

    connect() {
        this.button = this.element.querySelector('.mobile-menu-button');
        if (this.button) {
            this.button.addEventListener('click', () => this.toggle());
        }
    }

    disconnect() {
        if (this.button) {
            this.button.removeEventListener('click', () => this.toggle());
        }
    }

    toggle() {
        this.menuTarget.classList.toggle('hidden');
    }
}
