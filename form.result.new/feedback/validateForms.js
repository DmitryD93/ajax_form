class FormValidator {
    constructor(name, phone, checkbox, submitBtn) {
        this.name = name;
        this.phone = phone;
        this.checkbox = checkbox;
        this.submitBtn = submitBtn;

        this.init();
    }

    init() {
        this.setupEventListeners();
        this.validate();
    }

    setupEventListeners() {
        this.name.addEventListener('input', () => this.validate());
        this.phone.addEventListener('input', () => this.validate());
        this.checkbox.addEventListener('change', () => this.validate());
    }

    validate() {
        const isNameValid = this.name.value.trim() !== "";
        const isPhoneValid = this.phone.value.trim() !== "";
        const isCheckboxChecked = this.checkbox.checked;

        const isValid = isNameValid && isPhoneValid && isCheckboxChecked;

        if (isValid) {
            this.submitBtn.classList.remove('disabled');
            this.submitBtn.disabled = false;
        } else {
            this.submitBtn.classList.add('disabled');
            this.submitBtn.disabled = true;
        }

        return isValid;
    }


    destroy() {

        this.name.removeEventListener('input', this.validate);
        this.phone.removeEventListener('input', this.validate);
        this.checkbox.removeEventListener('change', this.validate);
    }

    reset() {
        this.name.value = '';
        this.phone.value = '';
        this.checkbox.checked = false;
        this.validate();
    }
}


