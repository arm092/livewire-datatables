(function () {
    function registerEditableData(Alpine) {
        Alpine.data('livewireDatatableEditable', function () {
            return {
                edit: false,
                edited: false,
                editedTimer: null,
                fieldEditedListener: null,
                livewireRoot: null,

                get notEditing() {
                    return !this.edit;
                },

                get editedClass() {
                    return this.edited ? 'text-green-500' : '';
                },

                init() {
                    this.fieldEditedListener = (event) => this.handleFieldEdited(event.detail);
                    this.livewireRoot = this.$wire.$el;
                    this.livewireRoot.addEventListener('fieldEdited', this.fieldEditedListener);
                },

                destroy() {
                    this.livewireRoot.removeEventListener('fieldEdited', this.fieldEditedListener);
                    window.clearTimeout(this.editedTimer);
                },

                startEditing() {
                    this.edit = true;
                    this.$nextTick(() => this.$refs.input.focus());
                },

                stopEditing() {
                    this.edit = false;
                },

                handleFieldEdited(detail) {
                    const rowId = Array.isArray(detail) ? detail[0] : detail.rowId ?? detail.id;
                    const column = Array.isArray(detail) ? detail[1] : detail.column;

                    if (String(rowId) !== this.$root.dataset.rowId || column !== this.$root.dataset.column) {
                        return;
                    }

                    this.edited = true;
                    window.clearTimeout(this.editedTimer);
                    this.editedTimer = window.setTimeout(() => {
                        this.edited = false;
                    }, 5000);
                },
            };
        });
    }

    if (window.Alpine) {
        registerEditableData(window.Alpine);
    } else {
        document.addEventListener('alpine:init', () => registerEditableData(window.Alpine), { once: true });
    }
})();
