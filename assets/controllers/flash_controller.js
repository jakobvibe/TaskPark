import { Controller } from '@hotwired/stimulus'

/**
 * Controller for auto-hiding flash messages
 */
export default class extends Controller {
    static values = {
        hideAfter: { type: Number, default: 5000 }
    }

    connect() {
        if (this.hideAfterValue > 0) {
            setTimeout(() => {
                this.dismiss()
            }, this.hideAfterValue)
        }
    }

    dismiss() {
        this.element.classList.add('opacity-0', 'transition-opacity', 'duration-300')
        setTimeout(() => {
            this.element.remove()
        }, 300)
    }
}
