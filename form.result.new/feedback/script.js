document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector(".js-feedback-form form")
    form?.classList.add("home-form__form")

    document.querySelectorAll(".home-form__form input").forEach(e => {
        if (e.type !== "checkbox") {
            e.addEventListener("input", () => {
                e.classList.remove("error")
            })
        } else {
            e.addEventListener("change", () => {
                document.querySelector(".home-form__form .form__checkbox-text").classList.remove("error")
            })
        }
    })

    const homeCaptcha = new SmartCaptcha("smartcaptcha-feedback-form", '<SITE_KEY от яндекса>', form);

    new FormValidator(
        document.querySelector(".home-form__form .feedback_form_name"),
        document.querySelector(".home-form__form .feedback_form_phone"),
        document.querySelector(".home-form__form .form__checkbox-input"),
        document.querySelector('.js-home-form-accept')
    );

    ajaxFeedbackForm(document.querySelector('.home-form__form'), formAction, homeCaptcha)
})

let isFeedbackProcessing = false;

function showFeedbackPreloader() {
    document.querySelector(".js-home-form-accept").classList.add("disabled")
    isFeedbackProcessing = true;
}

function hideFeedbackPreloader() {
    document.querySelector(".js-home-form-accept").classList.remove("disabled")
    isFeedbackProcessing = false;
}

function ajaxFeedbackForm(obForm, link, captcha) {
    BX.bind(obForm, 'submit', BX.proxy(function(e) {
        BX.PreventDefault(e);

        console.log(obForm)

        if (isFeedbackProcessing) {
            console.log("Форма уже отправляется, подождите...");
            return false;
        }


        // Проверяем чекбокс
        const agreeCheckbox = document.querySelector(".home-form__form .form__checkbox-input");
        if (!agreeCheckbox.checked) {
            document.querySelector(".home-form__form .form__checkbox-text").classList.add("error");
            console.log("Чекбокс согласия не отмечен");
            return false;
        }

        captcha.reset();
        captcha.render();

        console.log('Выполняем капчу...');
        showFeedbackPreloader();

        captcha.callback = function(token) {
            console.log('Токен получен, отправляем форму');
            sendFeedbackFormRequest(obForm, link, captcha, token);
        };

        captcha.execute();

    }, obForm, link));
}

function sendFeedbackFormRequest(obForm, link, captcha, token) {
    let xhr = new XMLHttpRequest();
    xhr.open('POST', link);

    xhr.onload = function () {
        hideFeedbackPreloader();

        if (xhr.status != 200) {
            alert(`Ошибка ${xhr.status}: ${xhr.statusText}`);
            captcha.reset();
        } else {
            var json = JSON.parse(xhr.responseText)
            if (!json.success) {
                console.log(json.errors)
                if (json.errors.NAME) {
                    document.querySelector(".home-form__form .feedback_form_name").classList.add("error")
                }
                if (json.errors.PHONE) {
                    document.querySelector(".home-form__form .feedback_form_phone").classList.add("error")
                    // Показываем нашу кастомную ошибку валидации
                    validatePhone('.home-form__form .input-phone');
                }
                if (json.errors.AGREE) {
                    document.querySelector(".home-form__form .form__checkbox-text").classList.add("error")
                }
                if (json.errors.smartcaptcha) {
                    console.log('Ошибка капчи:', json.errors.smartcaptcha);
                    // При ошибке капчи сбрасываем и перезапускаем
                    setTimeout(() => {
                        captcha.reset();
                        captcha.execute();
                    }, 1000);
                }
            } else {
                new GraphModal().open('form-success');
                obForm.reset();
                setTimeout(() => captcha.reset(), 1000);
            }
        }
    };

    xhr.onerror = function () {
        hideFeedbackPreloader();
        alert("Запрос не удался");
        captcha.reset();
    };

    xhr.onabort = function () {
        hideFeedbackPreloader();
        captcha.reset();
    };

    // Добавляем токен капчи к данным формы
    const formData = new FormData(obForm);
    if (token) {
        formData.append('smart-token', token);
        console.log('Добавлен токен:', token);
    }

    xhr.send(formData);
}