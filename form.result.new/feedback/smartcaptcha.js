class SmartCaptcha {
    constructor(containerId, sitekey = 'ysc1_IdjWtdqxjgmPxr3UvfajFPDC23NVxHAdNfIikRF245ef0595', form = null) {
        this.containerId = containerId;
        this.sitekey = sitekey;
        this.widgetId = null;
        this.form = form;
        this.token = null;
        this.callback = null;
        this.isReady = false;
        this.init();
    }

    init() {
        if (window.smartCaptcha) {
            this.render();
        } else {
            // Ждем загрузки smartCaptcha
            const checkCaptcha = setInterval(() => {
                if (window.smartCaptcha) {
                    clearInterval(checkCaptcha);
                    this.render();
                }
            }, 100);
        }
    }

    render() {
        const callback = (token) => {
            console.log('SmartCaptcha token received:', token);
            this.token = token;
            this.isReady = true;

            if (this.callback) {
                this.callback(token);
            }
        };

        if (!window.smartCaptcha) {
            console.error('Yandex SmartCaptcha not available');
            return;
        }

        const container = document.getElementById(this.containerId);
        if (!container) {
            console.error(`Container with id "${this.containerId}" not found`);
            return;
        }

        try {
            this.widgetId = window.smartCaptcha.render(container, {
                sitekey: this.sitekey,
                hl: 'RU',
                invisible: true,
                hideShield: true,
                callback: callback
            });

            console.log(`SmartCaptcha widget ${this.widgetId} rendered in ${this.containerId}`);
            this.isReady = true;

        } catch (error) {
            console.error('Error rendering SmartCaptcha:', error);
        }
    }

    execute() {
        if (!this.isReady || !window.smartCaptcha) {
            console.log('SmartCaptcha not ready, retrying...');
            setTimeout(() => this.execute(), 500);
            return;
        }

        try {
            window.smartCaptcha.execute(this.widgetId);
            console.log('Executing SmartCaptcha...');
        } catch (error) {
            console.error('Error executing SmartCaptcha:', error);
        }
    }

    reset() {
        if (this.widgetId && window.smartCaptcha) {
            try {
                window.smartCaptcha.reset(this.widgetId);
                this.token = null;
                this.isReady = false;
                console.log('SmartCaptcha reset');

                setTimeout(() => {
                    this.isReady = true;
                }, 500);

            } catch (error) {
                console.error('Error resetting SmartCaptcha:', error);
            }
        }
    }
}