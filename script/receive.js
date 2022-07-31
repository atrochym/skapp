document.querySelector('.services-list').addEventListener('click', function (e) {
	let row = e.target.closest('.row');
	row.querySelector('input').checked = true;
});

// $(".add-solutionX").click(function() {

// 	var solutionsTable = $(".services-list");

// 	numberOfSolutions = parseInt(solutionsTable.data('id'));
// 	count = parseInt(solutionsTable.data('count'));

// 	var newRow = `	<tr class="row">
// 						<td class="kek">
// 							<input class="add-row input-test" type="text" name="solution[${numberOfSolutions}][name]" value="">
// 						</td>
// 						<td>
// 							<input class="add-row input-test-cost price" type="text" name="solution[${numberOfSolutions}][price]" value="">
// 						</td>
// 						<td colspan="2"></td>
// 						<td>
// 							<a class="ico fa fa-trash delete-row" title="Usuń" onclick="delrow(this)"></a>
// 						</td>
// 					</tr>
					
// 					`;

// 	var saveButton = `<button class="button save-solution" style="position: absolute; left:108px; bottom: 27px; top:auto; width:80px;" type="submit">Zapisz</button>`;

// 	solutionsTable.data('id', ++numberOfSolutions);
// 	solutionsTable.data('count', ++count);

// 	$(".buttons").before(newRow);
// 	if(count == 1) {
// 		$(".add-solution").before(saveButton);
// 	}
// 	createListeners();

// });

document.querySelector('.add-solution').addEventListener('click', addSolution);

function addSolution() {
	const solutionsTable = document.querySelector('.solutions-list');
	const serviceId = ++solutionsTable.dataset.id;
	const solutionsCount = ++solutionsTable.dataset.count;
	const newSolutionRow = document.querySelector('.row.hidden').cloneNode(true);
	toggleVisible(newSolutionRow);


	const [inputName, inputPrice] = newSolutionRow.querySelectorAll('input');
	inputName.name =inputName.name = `solution[${serviceId}][name]`
	inputPrice.name =inputPrice.name =  `solution[${serviceId}][price]`
	
	buttons = solutionsTable.querySelector('.buttons');
	buttons.before(newSolutionRow);
	buttons.querySelector('.solution-save-btn').classList.remove('hidden');
	inputName.focus();

	// ++solutionsTable.dataset.id;
	// ++solutionsTable.dataset.count;

	/// ogarnąć przycisk anulowania, zapisania, zliczania kwoty itp
	// mimo focusu po odkliknięciu ucieka lista podpowiedzi
}

document.addEventListener('click', e => {
	if(document.querySelector('.showing') == null || e.target.closest('.input-name'))
		return;

	hidePromptBox();

})

document.addEventListener('keyup', e => {
	if (e.key == 'a' && e.altKey) {
		addSolution();
	}

	if (e.key == 'Escape' && document.querySelector('.showing')) {
		hidePromptBox();
	}

	if (e.key == 'n' && e.altKey) {
		document.location = 'http://atdev.ddns.net/sk/customer/register';
	}

	if (e.key == 'l' && e.altKey) {
		document.location = 'http://atdev.ddns.net/sk/account/logout';
	}
})

function hidePromptBox() {
	const element = document.querySelector('.showing');
	element.classList.add('hidden');
	element.classList.remove('showing');
	element.querySelector('.set') && element.querySelector('.set').classList.remove('set');
}

function deleteRow(element) {
	solutionsTable = document.querySelector('.solutions-list');

	element.closest('.row').remove();
	--solutionsTable.dataset.count;
	if(solutionsTable.dataset.count == 0) {
		toggleVisible(document.querySelector('.solution-save-btn'));
	}
	recountCost();
}

document.querySelector('.solutions-list').addEventListener('click', e => {
	solutionsTable = document.querySelector('.solutions-list');
	
	if (e.target.classList.contains('solution-delete')) {
		deleteRow(e.target);
	// 	e.target.closest('.row').remove();
	// 	--solutionsTable.dataset.count;
	// 	if(solutionsTable.dataset.count == 0) {
	// 		toggleVisible(document.querySelector('.solution-save-btn'));
	// 	}
	}


	if (e.target.classList.contains('solution-edit') || e.target.classList.contains('solution-edit-end')) {
		row = e.target.closest('.row');
		const [inputName, inputPrice] = row.querySelectorAll('input');

		inputName.classList.toggle('input-edit');
		inputName.disabled = !inputName.disabled

		inputPrice.classList.toggle('input-edit');
		inputPrice.disabled = !inputPrice.disabled

		toggleVisible(row.querySelector('.action-buttons'));
		toggleVisible(row.querySelector('.edit-buttons'));

		if (e.target.classList.contains('solution-edit')) {
			inputName.dataset.default = inputName.value;
			inputPrice.dataset.default = inputPrice.value;
		}
		if (e.target.classList.contains('solution-edit-end')) {
			inputName.value = inputName.dataset.default;
			inputPrice.value = inputPrice.dataset.default;
		}
	}

	if (e.target.classList.contains('solution-update-btn')) {
		updateSolution(e);
	}

	if (e.target.closest('.element')) {
		const row = e.target.closest('.row');
		const promptBox = row.querySelector('.prompt-box');
		const inputName = row.querySelector('.input-name');
		const inputPrice = row.querySelector('.input-cost');
		const element = e.target.closest('.element');
		const name = element.querySelector('.name').textContent;
		const price = element.querySelector('.price').textContent;

		inputName.value = name;
		inputPrice.value = price;
		toggleVisible(promptBox);



	}

	if (e.target.classList.contains('input-name')) {
		const row = e.target.closest('.row');
		const promptBox = row.querySelector('.prompt-box');
		const promptBoxOpened = document.querySelector('.showing');

		if (promptBoxOpened) {
			// promptBoxOpened.classList.add('hidden');
			toggleVisible(promptBoxOpened);
			promptBoxOpened.classList.remove('showing');
		}

		if (promptBox.dataset.response == "true")
		{
			promptBox.classList.remove('hidden');
			promptBox.classList.add('showing');
		}
		// promptBoxOpened && promptBoxOpened.classList.remove('.showing');

		// promptBox.classList.remove('hidden');
		// promptBox.classList.add('showing');

	}
});

// document.querySelector('.solutions-list').addEventListener('click', e => {
// }

document.querySelector('.solutions-list').addEventListener('keyup', e => {
	if (e.target.classList.contains('input-cost')) {
		recountCost();
	}

	inputName = e.target;

	if (inputName.classList.contains('input-name') == false) return;


	item = e.target.closest('.row');

	const allowed = '0123456789abcdefghijklmnopqrstuvwxyząćęłńóśźż BackspaceDelete';
	// regexp = /^:?(\w+[ąćęłńóśżź ]*)*$/;
	cl('testuje ' + e.key);

	if (inputName.name.indexOf('name') < 0) return;
	if (e.key == ' ' || allowed.indexOf(e.key) < 0) return;

	// if (!regexp.test(inputName.value) || e.key == ' ') return;

	// if (inputName.value.indexOf(' ', --inputName.value.length) > -1 && inputName.value.indexOf(' ', inputName.value.length -2) > -1) {
	// if (inputName.value[inputName.value.length -1] == ' ' && inputName.value[inputName.value.length -2] == ' ') {
		// inputName.value = inputName.value.slice(0, -1);
	// }


	if (inputName.value.length >= 3) {
		test(inputName.value, item);
		charCounter = inputName.nextElementSibling;
		charCounter.classList.add('hidden');

		// cl(lol);
	}
	else {
		const promptBox = item.querySelector('.prompt-box');
		// promptBox.classList.remove('showing');
		promptBox.innerHTML = '';
		promptBox.classList.add('hidden');
		delete promptBox.dataset.response;

		charCounter = inputName.nextElementSibling;

		charCounter.classList.remove('hidden');

		charCounter.textContent = 3 - inputName.value.length;
	}
});

document.querySelector('.solutions-list').addEventListener('keydown', e => {
	inputName = e.target;

	item = e.target.closest('.row');
	if (!item) return;
	let element, allElements, promptBox;

	if (item.querySelector('.prompt-box')) {
		promptBox = item.querySelector('.prompt-box');
		element = promptBox.querySelector('.set');
		allElements = promptBox.querySelectorAll('.element').length;
	}

	// cl (allElements);



	if (e.key == 'ArrowDown' && allElements) {
		e.preventDefault();
		// let next = element.nextElementSibling;

		if (element) {
			if (element.dataset.id == allElements) {
				return
			}
			element.classList.remove('set');
			element.nextElementSibling.classList.add('set');
			element.nextElementSibling.focus();
		}
		else
		{
			let element = promptBox.querySelector('.element');
			element.classList.add('set');
			element.focus();
		}
		// promptBox.querySelector('.element').focus();
	} else if (e.key == 'ArrowUp') {
		e.preventDefault();

		if (element) {
			if (element.dataset.id == 1) {

				item.querySelector('.input-name').focus();
				element.classList.remove('set');
				return;
			}

			element.classList.remove('set');
			element.previousElementSibling.classList.add('set');
			element.previousElementSibling.focus();
		}

	} else if (e.key == 'Enter') {
		let element = promptBox.querySelector('.set');
		if (element) {
			element.click();
			item.querySelector('.input-cost').focus();
		}

	} else if(e.key == 'Delete' && inputName.value.length == 0) {
		// cl('usunę');
		deleteRow(inputName);

	} else if(e.key == 'Delete' && e.shiftKey) {
		// cl('usunę');
		deleteRow(inputName);
	} else if(e.key == 's' && e.altKey) {
		cl('usunę');
		// deleteRow(inputName);
	} 

});

document.querySelector('.solutions-list').addEventListener('focusin', e => {
	inputName = e.target;
	if (inputName.classList.contains('input-name') && inputName.value.length < 3) {
		inputName.nextElementSibling.classList.remove('hidden');
	}
});

document.querySelector('.solutions-list').addEventListener('focusout', e => {
	inputName = e.target;
	if (inputName.classList.contains('input-name')) {
		inputName.nextElementSibling.classList.add('hidden');
	}
});

async function updateSolution(event) {
	solution = event.target.closest('.row');
	solution.classList.remove('create-success');

	const serviceId = solution.dataset.service_id;
	const [name, price] = solution.querySelectorAll('input');

	const data = {
		'serviceId': serviceId,
		'name': name.value,
		'price': price.value
	}

	const fetch = await fetchData('/sk/service/update/json', data);

	if (!fetch || !fetch.success) {
		showMessageBox('warn::Błąd podczas aktualizacji usługi.');
		return;
	}

	name.classList.toggle('input-edit');
	name.disabled = !name.disabled

	price.classList.toggle('input-edit');
	price.disabled = !price.disabled

	toggleVisible(solution.querySelector('.action-buttons'));
	toggleVisible(solution.querySelector('.edit-buttons'));
	toggleVisible(document.querySelector('.message'));
	solution.classList.add('create-success');
}

async function test(string, item) {
	const name = string;

	const data = {
		'name': name,
	}

	const fetch = await fetchData('/sk/service/test/json', data);

	if (!fetch || !fetch.success) {
		showMessageBox('warn::Błąd podczas aktualizacji usługi.');
		return;
	}

	// ustalić do którego promptboxa mają wpadać elementy - OK
	// pomyśleć o pobraniu danych i filtrowaniu locala? - 
	// przejście na listę strzałką w dół
	// po kliknięciu w wypełniony input przywrócić listę ostatnich wyników

	const services = fetch.data;
	

	const promptBox = item.querySelector('.prompt-box');
	promptBox.classList.remove('hidden');
	promptBox.classList.add('showing');

	promptBox.innerHTML = '';

	if (services.length == 0) {
		a = document.createElement('span');
		a.classList.add('empty-result');
		a.textContent = 'Brak wyników';
		promptBox.appendChild(a);

		return;
	}
	
	// const promptBox = document.querySelector('.prompt-box');
	// const promptBox = item.querySelector('.prompt-box');
	// promptBox.classList.remove('hidden');
	// promptBox.classList.add('showing');


	// const promptBox = inputName.nextElementSibling;
	
	// const template =  document.querySelector('.prompt-box .element').cloneNode(true);
	let count = 0;
	services.forEach(element => {
		count++;
		const template =  document.querySelector('.prompt-box .template').cloneNode(true);
		template.classList.remove('template');
		template.classList.add('element');
		template.querySelector('.name').textContent = element.name;
		template.querySelector('.price').textContent = element.price;
		template.dataset.id = count;
		// template = document.createElement('div');
		// template.classList.add('element');
		
		promptBox.appendChild(template);
		// return template;
		// cl(inputName);
	});

	promptBox.dataset.response = true;


}

function skip(element) {
	element.nextElementSibling.innerHTML = '';
}

function recountCost() {
	let priceMin = 0;
	let priceMax = 0;

	document.querySelectorAll('.solutions-list .input-cost').forEach(element => {
		let thisPrice = element.value;

		if (thisPrice.indexOf('-') != -1) {
			thisPrice = thisPrice.replaceAll(' ', '');
			period = thisPrice.split('-').sort();
			periodMin = parseInt(period[0]);
			periodMax = parseInt(period[1]);
			if (Number.isNaN(periodMin)) {
				periodMin = 0;
			}
			if (Number.isNaN(periodMax)) {
				periodMax = 0;
			}
			thisPrice = 0;
		} else {
			thisPrice = parseInt(thisPrice);
			periodMin = 0;
			periodMax = 0;
			if (Number.isNaN(thisPrice)) {
				thisPrice = 0;
			}
		}
		priceMin = priceMin + thisPrice + periodMin;
		priceMax = priceMax + thisPrice + periodMax;

		
		// let priceMin = 0;
		// let priceMax = 0;
		// $("input.price").each(function() {
		// 	thisPrice = $(this).val();
		// 	if(thisPrice.indexOf("-") != -1) {
		// 		period = thisPrice.split("-").sort();
		// 		periodMin = parseInt(period[0]);
		// 		periodMax = parseInt(period[1]);
		// 		thisPrice = 0;
		// 	} else {
		// 		thisPrice = parseInt(thisPrice);
		// 		periodMin = 0;
		// 		periodMax = 0;
		// 		if(Number.isNaN(thisPrice)) {
		// 			thisPrice = 0;
		// 		}
		// 	}
		// 	priceMin = priceMin + thisPrice + periodMin;
		// 	priceMax = priceMax + thisPrice + periodMax;
		// });
		// // ogarnij if w 1 linii
		// if(priceMin !== priceMax) {
		// 	priceMin = priceMin + ' - ' + priceMax;
		// } 
		
		// $(".price-total").text(priceMin + ' PLN')
	});
	if(priceMin !== priceMax) {
		priceMin = priceMin + ' - ' + priceMax;
	}

	document.querySelector('.price-total').textContent = priceMin + ' PLN';
}