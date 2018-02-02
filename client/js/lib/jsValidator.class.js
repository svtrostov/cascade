/*
 * Валидация форм
 * Stanislav V. Tretyakov (svtrostov@yandex.ru)
 */
var jsValidator = new Class({

	Implements: [Events, Options],

	options: {
		errorListItemClass: 'jsvErrorListItem',	//Класс элемента ошибки
		elementErrorClass: 'jsvElementErrorClass',
		elementSuccessClass: 'jsvElementSuccessClass',
		validateOnBlur: false,					//Проверять элементы формы моментально при снятии фокуса
		validateOnSubmit: false					//Проверять элементы формы при нажатии на кнопку продолжения
	},

	parent: null,		//родительский элемент
	validators: {},		//Правила валидации
	errorItems: {},		//Массив элементов ошибок


	//Инициализация
	initialize: function(parent, options){
		this.setOptions(options);
		this.parent = $(parent);
		this.errorListIds = {};
		if (this.options.validateOnSubmit == true){
			this.parent.addEvent('submit', this.validate.bind(this));
		}
	},


	//уничтожение
	destroy: function(){
		this.empty();
		for (var i=0; i<this.validators.length; i++) this.validators[i] = null;
		this.validators = null;
		this.errorItems= null;
	},

	//Очистка списка
	empty: function(){
		for (var i in this.errorItems) this.errorItems[i].destroy();
		this.errorItems={};
	},


	//Создание элемента в списке ошибок
	createErrorItem: function(name, error, element){

		var errorItem = new Element('div', {
			'class': this.options.errorListItemClass,
			'html': error
		}).inject(element, 'after');

		return errorItem;
	},


	//Валидация
	validate: function(){
		var result = true;
		this.empty();
		for(var name in this.validators){
			if(!this.errorItems[name]){
				if(!this.validateField(name)){
					App.echo('validator: '+name+' is FALSE');
					result = false;
				}
			}
		}
		return result;
	},


	//Валидация поля
	validateField: function(name){
		var validator,element, args, result;
		element = $(this.parent).getElement('[id='+name+']') || $(this.parent).getElement('[name='+name+']');
		if(!$(element)) return false;
		if(this.options.elementErrorClass) element.removeClass(this.options.elementErrorClass);
		if(this.options.elementSuccessClass) element.removeClass(this.options.elementSuccessClass);
		for(var id=0; id<this.validators[name].length; id++){
			validator = this.validators[name][id];
			if(typeOf(validator)!='object') continue;
			if(!element || !validator['function']) continue;
			args = typeOf(validator['values'])=='array' ? validator['values'].clone() : [];
			args.unshift(element);
			result = validator['function'].apply(this, args);
			if(!result){
				this.errorItems[name] = this.createErrorItem(name, validator['error'], element);
				if(this.options.elementErrorClass) element.addClass(this.options.elementErrorClass);
				return false;
			}
		}
		if(this.options.elementSuccessClass) element.addClass(this.options.elementSuccessClass);
		return true;
	},



	setValidator: function(name, validator, values, error){
		if(typeOf(this.validators[name])!='array') this.validators[name] = [];
		this.validators[name].push({
			'name': name,
			'function': validator,
			'values': values,
			'error': error
		});
		return this;
	},

	validateRequired: function(element){
		var type = element.getProperty('type');
		var value = String(element.get('value')).trim();
		if(type == 'checkbox') return element.get('checked');
		return (value.length !=0);
	},

	validateLength: function(element, minLength, maxLength){
		var value = String(element.get('value')).trim();
		return ((value.length >= minLength && maxLength == -1)||
				(value.length <= maxLength && minLength == -1)||
				(value.length <= maxLength && value.length >= minLength)
		);
	},

	validateCompare: function(element, target, needCompare){
		var value = String(element.get('value')).trim();
		var targetValue = ($(target) ? String($(target).get('value')).trim() : target);
		return ((needCompare && value == targetValue)||
				(!needCompare && value != targetValue));
	},

	validateRegEx: function(element, pattern){
		var value = String(element.get('value')).trim();
		if(!value.length) return true;
		var regexTest = new RegExp(pattern);
		/*App.echo(pattern+' ['+value+']= '+(regexTest.test(value)?'true':'false'));*/
		return regexTest.test(value);
	},

	validateMin: function(element, minValue){
		var value = String(element.get('value')).trim();
		if(!value.length) value = minValue-1;
		value = parseFloat(value);
		return (value >= minValue);
	},

	validateMax: function(element, maxValue){
		var value = String(element.get('value')).trim();
		if(!value.length) value = maxValue+1;
		value = parseFloat(value);
		return (value <= maxValue);
	},


	required: function(name, error){
		if (!error) error = "Это поле не может быть пустым.";
		return this.setValidator(name, this.validateRequired, [null], error);
	},
	minLength: function(name, minLength, error){
		if (!error) error = 'Это поле должно содержать минимум '+minLength+' символов.';
		return this.setValidator(name, this.validateLength, [minLength, -1], error);
	},
	maxLength: function(name, maxLength, error){
		if (!error) error = 'Это поле должно содержать не более '+maxLength+' символов.';
		return this.setValidator(name, this.validateLength, [-1, maxLength], error);
	},
	range: function(name, minLength, maxLength, error){
		if (!error) error = 'Это поле должно содержать от '+minLength+' до '+maxLength+' символов.';
		return this.setValidator(name, this.validateLength, [minLength, maxLength], error);
	},
	minValue: function(name, minValue, error){
		if (!error) error = 'Это поле должно содержать число не меньше чем '+minValue;
		return this.setValidator(name, this.validateMin, [minValue], error);
	},
	maxValue: function(name, maxValue, error){
		if (!error) error = 'Это поле должно содержать число не больше чем '+maxValue;
		return this.setValidator(name, this.validateMax, [maxValue], error);
	},
	matches: function(name, compareName, error){
		if (!error) error = 'Содержимое поля не совпадает с полем '+compareName+'.';
		return this.setValidator(name, this.validateCompare, [compareName, true], error);
	},
	noMatches: function(name, compareName, error){
		if (!error) error = 'Содержимое поля не должно совпадать с полем '+compareName+'.';
		return this.setValidator(name, this.validateCompare, [compareName, false], error);
	},
	alpha: function(name, error){
		if (!error) error = "Это поле должно содержать только буквы.";
		return this.setValidator(name, this.validateRegEx, ['^[a-zA-Zа-яА-Я]+$'], error);
	},
	numeric: function(name, error){
		if (!error) error = "Это поле должно содержать только цифры.";
		return this.setValidator(name, this.validateRegEx, ['^[0-9]+$'], error);
	},
	alphanumeric: function(name, error){
		if(!error) error = "Это поле должно содержать только буквы и цифры.";
		return this.setValidator(name, this.validateRegEx, ['^[a-zA-Zа-яА-Я0-9]+$'], error);
	},
	email: function(name, error){
		if (!error) error = "Пожалуйста, введите корректный адрес электронной почты.";
		return this.setValidator(name, this.validateRegEx, ['^[a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$'], error);
	},
	url: function(name, error){
		if (!error) error = "Пожалуйста, введите корректный URL адрес.";
		return this.setValidator(name, this.validateRegEx, ['^((http|https|ftp)://)?([[:alnum:]\-\.])+(\.)([[:alnum:]]){2,4}([[:alnum:]/+=%&_\.~?\-]*)$'], error);
	},
	username: function(name, error){
		if (!error) error = "Поле может содержать только буквы латинского алфавита, цифры или знаки: _ - .";
		return this.setValidator(name, this.validateRegEx, ['^[a-zA-Z0-9\_\-]+$'], error);
	},
	password: function(name, error){
		if (!error) error = "Пароль должен состоять минимум из 8 символов, содержать строчные, прописные буквы и цифры.";
		return this.setValidator(name, this.validateRegEx, ['(?=^.{8,}$)((?=.*[A-Za-z0-9])(?=.*[A-Z])(?=.*[a-z]))^.*'], error);
	},
	credit: function(name, error){
		if (!error) error = "Пожалуйста, введите корректный номер банковской карты.";
		return this.setValidator(name, this.validateRegEx, ['^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6011[0-9]{12}|3(?:0[0-5]|[68][0-9])[0-9]{11}|3[47][0-9]{13})$'], error);
	},
	date: function(name, error){
		if (!error) error = "Пожалуйста, введите корректную дату в следующем формате: dd.mm.yyyy.";
		return this.setValidator(name, this.validateRegEx, ['^(0?[1-9]|[12][0-9]|3[01])\.(0?[1-9]|1[012])\.[0-9]{4}$'], error);
	},
	phone: function(name, error){
		if (!error) error = "Пожалуйста, укажите корректный телефонный номер, например: 8-800-100-20-30, 8 (800) 100-20-30";
		return this.setValidator(name, this.validateRegEx, ['^(([0-9]{1})*[- .(]*([0-9]{3})[- .)]*[0-9]{3}[- .]*[0-9]{2}[- .]*[0-9]{2})+$'], error);
	}

});
