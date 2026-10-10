/**
 * Booking customization chat. All prices and requirements are validated on the server;
 * this component only sends messages/step answers and renders the conversation.
 */
export default (config) => ({
    messages: config.messages,
    state: config.state,
    form: config.form,
    suggestions: config.suggestions,
    urls: config.urls,
    input: '',
    sending: false,
    errors: {},
    notice: '',

    init() {
        this.$nextTick(() => this.scrollToBottom());
    },

    get textSteps() {
        return ['needs'];
    },

    get activeWidget() {
        return this.textSteps.includes(this.state.step) || this.state.step === 'done' ? null : this.state.step;
    },

    get currentSuggestions() {
        return this.suggestions[this.state.step] ?? [];
    },

    get placeholder() {
        if (this.state.step === 'needs') return 'Describe what you need cleaned, the property type, rooms, size and condition…';
        return 'Add any other details or special instructions…';
    },

    useSuggestion(text) {
        this.input = text;
        this.$nextTick(() => this.$refs.input?.focus());
    },

    async send() {
        const text = this.input.trim();
        if (!text || this.sending) return;

        const ok = await this.post(this.urls.message, { message: text });
        if (ok) this.input = '';
    },

    async submitStep(step, payload) {
        await this.post(this.urls.step.replace('__STEP__', step), payload);
    },

    async post(url, payload) {
        this.sending = true;
        this.errors = {};
        this.notice = '';

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json().catch(() => ({}));

            if (response.status === 422) {
                this.errors = data.errors ?? {};
                return false;
            }

            if (!response.ok) {
                this.notice = data.message ?? 'Something went wrong. Please try again.';
                return false;
            }

            this.messages.push(...data.messages);
            this.state = data.state;
            this.$nextTick(() => this.scrollToBottom());

            return true;
        } catch (error) {
            this.notice = 'We could not reach the server. Check your connection and try again.';
            return false;
        } finally {
            this.sending = false;
        }
    },

    error(field) {
        return (this.errors[field] ?? [])[0] ?? '';
    },

    scrollToBottom() {
        const log = this.$refs.log;
        if (log) log.scrollTop = log.scrollHeight;
    },

    toggleAddon(id) {
        const index = this.form.addons.indexOf(id);
        index === -1 ? this.form.addons.push(id) : this.form.addons.splice(index, 1);
    },
});
