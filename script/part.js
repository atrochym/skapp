document.querySelector('.form-create-order').addEventListener('paste', parseUrl);
document.querySelector('.form-create-order').addEventListener('click', changeAmount);
document.querySelector('.form-create-order').addEventListener('change', addCategoryWindow);

document.querySelector('.form-create-order').addEventListener('keydown', (e) => {
	if (e.keyCode == 13) {
		e.preventDefault();
	}
});

document.getElementsByName('order-date')[0].value = new Date().toLocaleDateString('en-CA');

document.querySelector('#create-category-form').addEventListener('submit', e => sendCategory(e));

function removeItem(e) {

	let itemList = document.querySelector('.form-create-order');
	
	if (itemList.dataset.count == '0') {

		return;
	}

	e.closest('.item').remove();
	itemList.dataset.count = parseInt(itemList.dataset.count) - 1;
}

// document.querySelector('.select-category').addEventListener('change', addCategoryWindow);
document.querySelector('.overlay-window-close').addEventListener('click', toggleOverlayWindow);


function addCategoryWindow(e) {

	createCategory = e.target;

	if (createCategory.value == 'add') {
		toggleOverlayWindow();
		createCategory.value = '0';
	}
}

function toggleOverlayWindow() {
	let overlayBackground = document.querySelector('.overlay-window-background');
	let overlayWindow = document.querySelector('.overlay-window-content');

	visiblity = window.getComputedStyle(overlayBackground).getPropertyValue('display');

	// if (visiblity == 'block') {
	// 	overlayBackground.style.display = 'none';
	// 	overlayWindow.style.display = 'none';
	// } else {
	// 	overlayBackground.style.display = 'block';
	// 	overlayWindow.style.display = 'block';
	// }

	overlayBackground.classList.toggle('display-block');
	overlayWindow.classList.toggle('display-block');

}


document.querySelector('.add-item').addEventListener('click', addItem);

function addItem() {

	let itemList = document.querySelector('.form-create-order')
	let numberOfItems = parseInt(itemList.dataset.id) + 1
	let count = parseInt(itemList.dataset.count) + 1

	// thisItem = $('.item').first()
	thisItem = document.querySelector('.item');

	item = thisItem.outerHTML
	$('button.save').before(item)
	var lastItem = $(".item").last().find('[name*=item]')

	lastItem.each(function() {

		var name = this.name.replace(/\[\d]/g, `[${numberOfItems}]`)
		this.setAttribute('name', name)
		}
	)

	// itemList.data('id', numberOfItems)
	// itemList.data('count', count)

	itemList.dataset.id = numberOfItems;
	itemList.dataset.count = count;

}

function parseUrl(event) {

	if (!event.target.classList.contains('part-url')) {
		return;
	}

	let url = event.clipboardData.getData('text');

	if (url.indexOf('allegro.pl') < 1) {
		return;
	}

	let orderItem = event.target.closest('.item');
	let name = orderItem.querySelector('.part-name');
	// let note = orderItem.querySelector('.part-note');
	// let price = orderItem.querySelector('.part-price');

	// https://allegro.pl/oferta/klawiatura-hp-omen-15-ce-15-nw-led-10243381485
	// https://allegro.pl/oferta/obudowa-palmrest-klawiatura-hp-omen-15-ce-10510994217?reco_id=094b5a26-11a2-11ec-b367-b026284c79a0&sid=1147bbb04b955b96a226cab2e664258d9124ef0862320b2d7366fdd6604cf017
	// https://allegro.pl/oferta/klawiatura-apple-macbook-air-13-a1369-a1466-duzy-9711831336?reco_id=1a624c49-119d-11ec-933f-bc97e12c3c80&sid=041047f9c36843e364ecb91b45c568a2755aa386fe7e14ee7421a14291fbf951
	// https://allegro.pl/oferta/klawiatura-hp-omen-15-ce-15-nw-led-10243381485#metody-platnosci

	index = url.indexOf("/oferta/");
	if (index > 0) {
		url = url.substring(index+8);
		index = url.indexOf("?");

		if (index > 0) {
			url = url.slice(0, index);
		}

		index = url.indexOf("#");

		if (index > 0) {
			url = url.slice(0, index)
		}

		index = url.lastIndexOf("-");
		// item = url.substring(index+1);

		url = url.substr(0, index);
		url = url.replace(/-/g, " ");
		url = url.charAt(0).toUpperCase() + url.slice(1);

		name.value = url;
		// note.value = item;
		// price.focus();
		// price.value = '0';
	}
}

function changeAmount(event) {

	// event.target.preventDefault();

	if (!event.target.classList.contains('amount-adjust')) {
		return;
	}

	let amount = event.target.parentElement.querySelector('.part-amount');
	let button = event.target;

	if (button.classList.contains('amount-add')) {
		amount.value++;
	}

	if (button.classList.contains('amount-substract') && amount.value > 1) {
		amount.value--;
	}
}


function sendCategory(e) {
	e.preventDefault();

	let category = document.querySelector('#category-name').value;

	let data = {
		"category": category
	}

	fetch('/sk/part/testSaveCategory/json', {
		method: "post",
		headers: {
			"Content-Type": "application/json"
		},
		body: JSON.stringify(data)
	})
	.then(res => {
		if(!res.ok) {
			cl(Promise.reject(`fetch response error: ${res.status}`));
		}

		res.json()
			.then(res => {
				insertCategory(res);
			})
			.catch ((e) => {
				cl('fetch json parse error :: ' + e);
			})

	})
	.catch((e) => {
		cl('fetch connection error :: ' + e);
	})
}


function insertCategory(jsonResponse) {

	showMessageBox(jsonResponse.message);

	if(jsonResponse.categoryId) {
		let categoryListDOM = document.querySelectorAll('.select-category');

		categoryListDOM.forEach(element => {

			let optionDOM = document.createElement('option');
			optionDOM.value = jsonResponse.categoryId;
			optionDOM.text = jsonResponse.categoryName;
			let beforeDOM = element.querySelector('.add-category-option');
			element.insertBefore(optionDOM, beforeDOM);
		});
		
		document.getElementById('category-name').value = '';
	}

	toggleOverlayWindow();
}

function showMessageBox(message) {

	let messageDOM = document.querySelector('.message');
	let separator = message.indexOf('::');

	if (!separator)
	{
		console.log('showMessageBox :: wrong message format')
	}
	let messageType = message.slice(0, separator);
	let messageContent = message.slice(separator + 2);

	messageDOM.textContent = messageContent;
	messageDOM.className = `message message-${messageType}`;
}


// function saveFormToStorage() {

// 	let itemBox = document.querySelectorAll('.item');
// 	let formInputs = {};

// 	formInputs.orderDate = document.querySelector('[data-type=order-date]').value;
// 	formInputs.seller = document.querySelector('[data-type=seller]').value;
// 	formInputs.deliveryCost = document.querySelector('[data-type=delivery-cost').value;

// 	let input = [];	
// 	formInputs.items = []

// 	itemBox.forEach(element => {
		
// 		input = [];

// 		boxInputs = [...element.querySelectorAll('input'), ...element.querySelectorAll('select')];
// 		boxInputs.forEach(element => {

// 			if (element.type == 'checkbox') {

// 				input.push({
// 					'name' : element.name,
// 					'value' : getCheckboxValue(element),
// 					'type' : 'checkbox',
// 					'dataType' : element.dataset.type,
// 				});
// 			}

// 			input.push({
// 				'name' : element.name,
// 				'value' : element.value,
// 				'dataType' : element.dataset.type,
// 			});

// 		})

// 		formInputs.items.push(input);
// 	})

// 	window.localStorage.setItem('createOrderForm', JSON.stringify(formInputs));	
// }

function saveFormToStorage() {

	let singleFields = document.querySelectorAll('.single-fields input, .single-fields select ');
	let itemBox = document.querySelectorAll('.item');
	let formInputs = {};
	let input = [];
	formInputs.singleFields = [];
	formInputs.items = [];

	singleFields.forEach(input => {

		formInputs.singleFields.push({
			'name' : input.name,
			'value' : input.value,
			'dataType' : input.dataset.type,
		})
	})

	itemBox.forEach(element => {
		
		input = [];
		boxInputs = element.querySelectorAll('input, select');
		boxInputs.forEach(element => {

			if (element.type == 'checkbox') {

				input.push({
					'name' : element.name,
					'value' : getCheckboxValue(element),
					'type' : 'checkbox',
					'dataType' : element.dataset.type,
				});
			}

			input.push({
				'name' : element.name,
				'value' : element.value,
				'dataType' : element.dataset.type,
			});

		})

		formInputs.items.push(input);
	})

	window.localStorage.setItem('createOrderForm', JSON.stringify(formInputs));	
}

function getCheckboxValue(element) {

	if (element.checked == true) {
		return 'checked';
	}
	return '';
}

function restoreFormFromStorage() {
	
	let formData = window.localStorage.getItem('createOrderForm');
	let input;
	let itemIndex = 0;
	let lastFieldId = 0;
	let lastIdFromName;

	formData = JSON.parse(formData);
	
	for (i = 1; i < formData.items.length; i++) {

		addItem();
	}

	formData.singleFields.forEach(field => {

		document.querySelector(`[data-type=${field.dataType}]`).value = field.value;
	})

	itemsDOM = document.querySelectorAll('.item');
	itemsDOM.forEach(itemBox => {

		formData.items[itemIndex].forEach(element => {

			lastIdFromName = parseInt(element.name.replace(/[^\d.]/g, ''));
			lastFieldId = lastIdFromName > lastFieldId ? lastIdFromName : lastFieldId;

			input = itemBox.querySelector(`[data-type=${element.dataType}]`);
			input.name = element.name;

			if (element.type == 'checkbox' && element.value == 'checked') {

				input.checked = true;
				return;
			}

			input.value = element.value;
		})

		itemIndex++;
	});

	itemsDOM[0].closest('form').dataset.id = lastFieldId;
}