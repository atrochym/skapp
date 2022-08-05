// import {fetchData} from './tests.js';

export const removeSolution = (target) => {
	const solutionsTable = document.querySelector('.solutions-list');

	target.closest('.row').remove();
	--solutionsTable.dataset.count;
	if(solutionsTable.dataset.count == 0) {
		document.querySelector('.solution-save-btn') && toggleVisible(document.querySelector('.solution-save-btn'));
	}

	countCost('.solutions-list .input-cost', '.price-total');
}

export const countCost = (handle, output) => {
	let [priceMin, priceMax, periodMin, periodMax, period] = Array(5).fill(0);

	document.querySelectorAll(handle).forEach(element => {
		let thisPrice = element.value;

		if (thisPrice.indexOf('-') != -1) {
			thisPrice = thisPrice.replaceAll(' ', '');
			period = thisPrice.split('-').sort();
			periodMin = parseInt(period[0]);
			periodMax = parseInt(period[1]);

			periodMin = Number.isNaN(periodMin) ? 0 : periodMin;
			periodMax = Number.isNaN(periodMax) ? 0 : periodMax;
			
			thisPrice = 0;

		} else {
			thisPrice = parseInt(thisPrice);
			periodMin = 0;
			periodMax = 0;

			thisPrice = Number.isNaN(thisPrice) ? 0 : thisPrice;
		}
		priceMin = priceMin + thisPrice + periodMin;
		priceMax = priceMax + thisPrice + periodMax;
	});

	if(priceMin !== priceMax) {
		priceMin = priceMin + ' - ' + priceMax;
	}

	document.querySelector(output).textContent = priceMin + ' PLN';
}

export const hidePromptBox = () => {
	const element = document.querySelector('.visible');
	element.classList.add('hidden');
	element.classList.remove('visible');
	element.querySelector('.selected') && element.querySelector('.selected').classList.remove('selected');
}

export const fetchSolutions = async (name, target) => {

	const fetch = await fetchData('/sk/service/test/json', {name});

	if (!fetch || !fetch.success) {
		showMessageBox('warn::Błąd podczas pobierania danych.');
		return;
	}

	const services = fetch.data;
	const promptBox = target.querySelector('.prompt-box');
	promptBox.classList.remove('hidden');
	promptBox.classList.add('visible');

	promptBox.innerHTML = '';

	if (services.length == 0) {
		const span = document.createElement('span');
		span.classList.add('empty-result');
		span.textContent = 'Brak wyników';
		promptBox.appendChild(span);

		return;
	}
	
	const source = document.querySelector('.prompt-box .template');
	let count = 0; //for arrow navigation
	services.forEach(element => {
		count++;
		const template = source.cloneNode(true);
		template.classList.remove('template');
		// template.classList.add('element');
		const [name, price] = template.querySelectorAll('span');
		name.textContent = element.name;
		price.textContent = element.price;
		template.dataset.id = count;
		// template = document.createElement('div');
		// template.classList.add('element');
		
		promptBox.appendChild(template);
		// return template;
		// cl(inputName);
	});

	promptBox.dataset.response = true;
}

export const addSolution = (solutionsList) => {
	const solutionId = ++solutionsList.dataset.id;
	solutionsList.dataset.count++;
	const newSolutionRow = document.querySelector('.row.template').cloneNode(true);
	newSolutionRow.classList.remove('template');
	// toggleVisible(newSolutionRow);
	newSolutionRow.classList.remove('hidden');

	const [inputName, inputPrice] = newSolutionRow.querySelectorAll('input');
	inputName.name = `solution[${solutionId}][name]`
	inputPrice.name = `solution[${solutionId}][price]`
	inputName.required = true;
	inputPrice.required = true;
	
	const buttons = solutionsList.querySelector('.buttons');
	buttons.before(newSolutionRow);

	buttons.querySelector('.solution-save-btn') && buttons.querySelector('.solution-save-btn').classList.remove('hidden');
	inputName.focus();
}

export const startPromptBox = (event) => {
	const inputName = event.target;
	const row = inputName.closest('.row');
	const allowed = '0123456789abcdefghijklmnopqrstuvwxyząćęłńóśźż BackspaceDelete';
	cl('testuje ' + event.key);

	// if (inputName.name.indexOf('name') < 0) return;
	if (event.key == ' ' || allowed.indexOf(event.key) < 0) return;

	if (inputName.value.length >= 3) {
		fetchSolutions(inputName.value, row);
		const charCounter = inputName.nextElementSibling;
		charCounter.classList.add('hidden');

	} else {
		const promptBox = row.querySelector('.prompt-box');
		promptBox.innerHTML = '';
		promptBox.classList.add('hidden');
		delete promptBox.dataset.response;

		const charCounter = inputName.nextElementSibling;
		charCounter.classList.remove('hidden');

		charCounter.textContent = 3 - inputName.value.length;
	}
}