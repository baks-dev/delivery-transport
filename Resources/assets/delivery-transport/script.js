/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

let $addButton = document.getElementById('delivery_transport_form_add_driver');

if($addButton)
{
    /* Блок для новой коллекции */
    let $blockCollection = document.getElementById('delivery_transport_form-collection');

    if($blockCollection)
    {

        $addButton.addEventListener('click', function()
        {

            let $addButton = this;
            /* получаем прототип коллекции  */
            let newForm = $addButton.dataset.prototype;
            let index = $addButton.dataset.index * 1;

            /* Замена '__name__' в HTML-коде прототипа
             вместо этого будет число, основанное на том, сколько коллекций */
            newForm = newForm.replace(/__delivery_driver__/g, index);

            /* Вставляем новую коллекцию */
            let div = document.createElement('div');
            div.id = 'item_delivery_transport_form_driver_' + index;

            // div.classList.add('align-items-center');
            // div.classList.add('gap-3');
            // div.classList.add('item-collection-file');

            div.innerHTML = newForm;
            $blockCollection.append(div);

            /* применяем select2 */
            new NiceSelect(div.querySelector('[data-select="select2"]'), {searchable: true});


            /* Добавить контактный номер телефона */
            //(div.querySelector('.phone-add-collection'))?.addEventListener('click', addPhone);


            /* Удаляем контактный номер телефона */
            (div.querySelector('.del-item'))?.addEventListener('click', deleteItem);


            // (div.querySelector('.del-item-call'))?.addEventListener('click', deleteCall);


            /* Увеличиваем data-index на 1 после вставки новой коллекции */
            $addButton.dataset.index = (index + 1).toString();

            /* Плавная прокрутка к элементу */
            //div.scrollIntoView({block: "center", inline: "center", behavior: "smooth"});


        });
    }
}


/** Добавить контактный телефон */

// document.querySelectorAll('.phone-add-collection').forEach(function (item) {
//     item.addEventListener('click', addPhone);
// });

// function addPhone() {
//
//     /* Получаем прототип формы */
//     let newForm = this.dataset.prototype;
//     let index = this.dataset.index * 1;
//     let call = this.dataset.call * 1;
//     let collection = this.dataset.collection;
//
//     //let id = this.dataset.id;
//
//     // newForm = newForm.replace(/__name__/g, name) /* меняем индекс торгового предложения */
//
//     //newForm = newForm.replace(/__call__/g, call)
//     newForm = newForm.replace(/__delivery_driver__/g, index)
//
//     let div = document.createElement('div');
//     div.innerHTML = newForm;
//     div.id = 'item_delivery_transport_form_driver_' + index;
//     div.classList.add('mb-3');
//
//     let $collection = document.getElementById(collection);
//     $collection.append(div);
//
//     /* Удаляем контактный номер телефона */
//     (div.querySelector('.del-item-phone'))?.addEventListener('click', deletePhone);
//
//     this.dataset.index = (index + 1).toString();
// }

document.querySelectorAll('.del-item').forEach(function(item)
{
    item.addEventListener('click', deleteItem);
});

function deleteItem()
{

    console.log(this.dataset.delete);

    document.getElementById(this.dataset.delete).remove();
}



