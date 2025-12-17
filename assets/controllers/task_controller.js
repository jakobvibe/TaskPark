import { Controller } from '@hotwired/stimulus'

/**
 * Controller for individual task interactions
 */
export default class extends Controller {
    static values = {
        id: Number
    }

    static targets = ['title']

    connect() {
        // Task connected to DOM
    }

    // Could be expanded for inline editing, drag-and-drop, etc.
}
