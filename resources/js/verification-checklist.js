export default function verificationChecklist(items = [], initial = []) {
    const keys = [...new Set(items.map(String))];
    return {
        keys,
        checked: [...new Set((Array.isArray(initial) ? initial : []).map(String))].filter(key => keys.includes(key)),
        get allChecked() {
            return this.keys.length > 0 && this.keys.every(key => this.checked.includes(key));
        },
        get someChecked() {
            return !this.allChecked && this.keys.some(key => this.checked.includes(key));
        },
        toggleAll(selected) {
            this.checked = selected ? [...this.keys] : [];
        },
    };
}
