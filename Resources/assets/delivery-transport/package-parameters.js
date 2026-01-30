/*
 * Copyright 2026.  Baks.dev <admin@baks.dev>
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

executeFunc(function MultiplePackageParameters()
{
    const form = document.forms.update_multiple_products_package_parameter_form;
    
    const submitButton = form.querySelector('button[type="submit"]');

    if(typeof form === "undefined")
    {
        return false;
    }

    let $object_category = document.getElementById(form.name + "_product_category");

    if($object_category === null)
    {
        return false;
    }

    new NiceSelect($object_category, {searchable : true});

    $object_category.addEventListener("change", function()
    {
        changeObjectCategory(form);
    }, false);

    async function changeObjectCategory(forms)
    {
        disabledElementsForm(forms);

        const data = new FormData(forms);
        data.delete(forms.name + "[_token]");

        await fetch(forms.action, {
            method : forms.method, // *GET, POST, PUT, DELETE, etc.
            cache : "no-cache", // *default, no-cache, reload, force-cache, only-if-cached
            credentials : "same-origin", // include, *same-origin, omit
            headers : {
                "X-Requested-With" : "XMLHttpRequest",
            },
            redirect : "follow", // manual, *follow, error
            referrerPolicy : "no-referrer", // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
            body : data, // body data type must match "Content-Type" header
        }).then((response) =>
        {
            if(response.status !== 200)
            {
                enableElementsForm(forms);
                return false;
            }

            return response.text();

        }).then((data) =>
        {
            let $parser = new DOMParser();
            let $result = $parser.parseFromString(data, "text/html");

            let $offer = $result.getElementById(forms.name + "_product_offer");
            if($offer)
            {
                document.getElementById(forms.name + "_product_offer").replaceWith($offer);
                new NiceSelect(document.getElementById(forms.name + "_product_offer"), {searchable : true});
            }

            let $variation = $result.getElementById(forms.name + "_product_variation");
            if($variation)
            {
                document.getElementById(forms.name + "_product_variation").replaceWith($variation);
                new NiceSelect(document.getElementById(forms.name + "_product_variation"), {searchable : true});
            }

            let $modification = $result.getElementById(forms.name + "_product_modification");
            if($modification)
            {
                document.getElementById(forms.name + "_product_modification").replaceWith($modification);
                new NiceSelect(document.getElementById(forms.name + "_product_modification"), {searchable : true});
            }

            enableElementsForm(forms);
        });
    }

    /** Переход на шаг следующий шаг формы (заполнение параметров упаковки) */
    submitButton.addEventListener("click", async function(event)
    {
        disabledElementsForm(form);

        if(true === form.checkValidity())
        {
            event.preventDefault();

            const formData = new FormData(form);
            formData.delete(form.name + "[_token]");

            await fetch(form.action, {
                method : 'POST', // *GET, POST, PUT, DELETE, etc.
                cache : "no-cache", // *default, no-cache, reload, force-cache, only-if-cached
                credentials : "same-origin", // include, *same-origin, omit
                headers : {
                    "X-Requested-With" : "XMLHttpRequest",
                },
                redirect : "follow", // manual, *follow, error
                referrerPolicy : "no-referrer", // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
                body : formData, // body data type must match "Content-Type" header
            }).then((response) =>
            {
                if(response.status !== 200)
                {
                    return false;
                }

                return response.text();
            }).then(() =>
            {
                addPackageParameters(form);
            })
        }

        enableElementsForm(form);
    });


    async function addPackageParameters(forms)
    {
        disabledElementsForm(forms);

        let formData = new FormData(forms);
        formData.append(forms.name + "[product_package_parameters_next]", '')

        await fetch(forms.action, {
            method : forms.method, // *GET, POST, PUT, DELETE, etc.
            cache : "no-cache", // *default, no-cache, reload, force-cache, only-if-cached
            credentials : "same-origin", // include, *same-origin, omit
            headers : {
                "X-Requested-With" : "XMLHttpRequest",
            },
            redirect : "follow", // manual, *follow, error
            referrerPolicy : "no-referrer", // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
            body : formData, // body data type must match "Content-Type" header
        }).then((response) =>
        {
            if(response.status !== 200)
            {
                return false;
            }

            return response.text();
        }).then((data) =>
        {
            disabledElementsForm(forms);

            document.querySelector('.modal-dialog').parentElement.innerHTML = data;

            /** Вешаем событие на сабмит формы, чтобы успеть провалидировать поля до того, как она будет отправлена */
            document.querySelector(`form[name="${form.name}"] button[type="submit"]`).addEventListener("click", async function(event)
            {
                /** Прооверяем валидность значений полей формы перед отправкой - если не валидна, не отправляем запрос */
                if(
                    document.getElementById(form.name + "_parameters_weight").value <= 0 ||
                    document.getElementById(form.name + "_parameters_width").value <= 0 ||
                    document.getElementById(form.name + "_parameters_height").value <= 0 ||
                    document.getElementById(form.name + "_parameters_package").value <= 0
                )
                {
                    event.preventDefault();

                    /** Отрисовываем тост с предупреждением пользователя */
                    let $errorFormHandler = "{ \"type\":\"danger\" , " +
                        "\"header\":\"" + 'Изменение параметров упаковки' + "\"  , " +
                        "\"message\" : \"Значения полей не должны быть отрицательными или пустыми\" }";
                    createToast(JSON.parse($errorFormHandler));

                    enableElementsForm(form);
                }
            });

            enableElementsForm(form);
        })
    }

    return true;

})
