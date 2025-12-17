import { Controller } from '@hotwired/stimulus'

/**
 * Controller for the inline add task form
 */
export default class extends Controller {
    static targets = ['form', 'trigger', 'input']

    show() {
        this.triggerTarget.classList.add('hidden')
        this.formTarget.classList.remove('hidden')
        this.inputTarget.focus()
    }

    cancel() {
        this.formTarget.classList.add('hidden')
        this.triggerTarget.classList.remove('hidden')
        this.inputTarget.value = ''
    }

    // Called when form is submitted successfully
    reset() {
        this.inputTarget.value = ''
        this.inputTarget.focus()
    }
}
