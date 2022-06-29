// $(`.device-list`).mouseover(function() {
//     $("span:last-child").css("display", "inline-block");
// });

// $(`.device-list`).mouseout(function() {
//     $(`.device-list`).lastChild().css("display", "none");
// });

// function newPrice() {
// 	let priceMin = 0;
// 	let priceMax = 0;
// 	$("input.price").each(function() {
// 		thisPrice = $(this).val();
// 		if(thisPrice.indexOf("-") != -1) {
// 			period = thisPrice.split("-").sort();
// 			periodMin = parseInt(period[0]);
// 			periodMax = parseInt(period[1]);
// 			thisPrice = 0;
// 		} else {
// 			thisPrice = parseInt(thisPrice);
// 			periodMin = 0;
// 			periodMax = 0;
// 			if(Number.isNaN(thisPrice)) {
// 				thisPrice = 0;
// 			}
// 		}
// 		priceMin = priceMin + thisPrice + periodMin;
// 		priceMax = priceMax + thisPrice + periodMax;
// 	});
// 	// ogarnij if w 1 linii
// 	if(priceMin !== priceMax) {
// 		priceMin = priceMin + ' - ' + priceMax;
// 	} 
	
// 	$(".price-total").text(priceMin + ' PLN');
// }

// function createListeners() {
// 	$(`.price`).change(function() {
// 		newPrice();
// 	});
// }

// $("i.add").click(function() {
// 	numberOfSolutions = parseInt($(".solution-box:last").data('id'));
// 	numberOfSolutions ++;

// 	var newdiv = `<div class="solution-box" data-id="${numberOfSolutions}">
// 					<input class="input solution" type="text" name="solution[${numberOfSolutions}][name]">
// 					<input class="input price" type="text" name="solution[${numberOfSolutions}][price]">
// 					<i class="remove fa fa-times"></i>
// 				</div>`;

// 	$(".empty").before(newdiv);
// 	createListeners();

// 	$(`.remove`).click(function() {
// 		removeSolution(this);
// 		newPrice();
// 	});

// });

// function removeSolution(element) {
// 	solutionsBox = $('.solution-box').length;
// 	if(solutionsBox > 1) {
// 		$(element).parent().remove();
// 	}
// }

// newPrice();
// createListeners();


// Vanilla JS

document.querySelector('.services-list').addEventListener('click', function (e) {
	let row = e.target.closest('.row');
	row.querySelector('input').checked = true;
});

