/*----------------------------------------------------------------------
Дерево организациооной структуры
Stanislav V. Tretyakov (svtrostov@yandex.ru)
----------------------------------------------------------------------*/
var jsOrgChart = new Class({

	Implements: [Options, Events],


	options: {
		/*onChange: $empty,*/
		cloneParent: document.body,
		cloneOffset: {x: 16, y: 16},
		cloneOpacity: 0.8,
		checkDrag: $lambda(true),
		checkDrop: $lambda(true),
		indicatorColor: '#b7f0c6',
		dragAndDrop: true
	},

	data: null,
	rootUL: null,
	rootTitle: '',


	initialize: function(element, options){
		this.setOptions(options);
		this.element = document.id(element);

		var self = this;
		this.mousedownHandler = function(e){
			self.mousedown(this, e);
		};
		this.mouseup = this.mouseup.bind(this);
		this.bound = {
			onDrag: this.onDrag.bind(this),
			onDrop: this.onDrop.bind(this)
		};
		this.attach();

	},


	attach: function(){
		this.element.addEvent('mousedown:relay(a)', this.mousedownHandler);
		document.addEvent('mouseup', this.mouseup);
		return this;
	},


	detach: function(){
		this.element.removeEvent('mousedown:relay(a)', this.mousedownHandler);
		document.removeEvent('mouseup', this.mouseup);
		return this;
	},


	checkDrag: function(element){
		return this.options.dragAndDrop && !element.hasClass('nodrag');
	},


	checkDrop: function(element, options){
		return this.options.dragAndDrop && !element.hasClass('nodrop');
	},


	mousedown: function(element, e){
		e.stop();
		e.target = $(e.target);
		if(!this.checkDrag(element)) return;

		var li = element.getParent('li');
		var a = li.getElement('a');
		if(!li || !a) return;
		this.current = li;
		this.clone = a.clone().setStyles({
			left: e.page.x + this.options.cloneOffset.x,
			top: e.page.y + this.options.cloneOffset.y,
			opacity: this.options.cloneOpacity
		}).addClass('').inject(document.body);

		this.clone.makeDraggable({
			droppables: this.element.getElements('a')
		}).addEvents(this.bound).start(e);
	},


	mouseup: function(){
		if(this.clone) this.clone.destroy();
	},


	//перетаскивание
	onDrag: function(el, e){

		if (!e) return;
		e.target = document.id(e.target);
		if (!e.target) return;

		var droppable = e.target.get('tag') == 'a' ? e.target : e.target.getParent('a');
		if (!droppable){
			this.drop = {
				target: null
			};
			this.updateIndicator();
			return;
		}
		var li = droppable.getParent('li');
		if (!li){
			this.drop = {
				target: null
			};
			this.updateIndicator();
			return;
		}

		var dropOptions;
		if (this.current.contains(droppable)){
			this.drop = {target: null};
		} else{
			dropOptions = {target: droppable};
			if (!this.options.checkDrop.apply(this, [droppable, dropOptions])) return;
			this.setDropTarget(dropOptions);
		}

		this.updateIndicator();
	},



	//брось
	onDrop: function(el){
		el.destroy();

		var drop = this.drop,
			current = this.current;

		this.drop = {
			target: null
		};
		this.updateIndicator();

		if (!drop || !drop.target) return;

		var ul = drop.target.getParent('li').getElement('ul') || new Element('ul').inject(drop.target.getParent('li'));
		var currentParent = current.getParent('ul');
		current.inject(ul);
		if(!currentParent.getElement('li')) currentParent.destroy();

		this.fireEvent('change');
	},



	//Задает цель
	setDropTarget: function(drop){
		this.drop = drop;
	},//end function



	//Обновление цвета заливки элемента при наведении в режиме Drag&Drop
	updateIndicator: function(){
		if(this.prevdroptarget && this.prevdroptarget != this.drop.target){
			this.prevdroptarget.setStyle('background-color','');
		}
		if (this.drop.target && this.prevdroptarget!=this.drop.target){
			this.drop.target.setStyle('background-color',this.options.indicatorColor);
		}
		this.prevdroptarget = this.drop.target;
	},//end function



	//Клик по ноде
	onNodeClick: function(e){
		if (!e) return;
		e.target = document.id(e.target);
		if (!e.target) return;
		var a = e.target.get('tag') == 'a' ? e.target : e.target.getParent('a');
		if(!a) return;
		if(a.hasClass('selected')) return;
		if(this.selectedNode) this.selectedNode.removeClass('selected');
		if(!a.hasClass('noselect')) this.selectedNode = a.addClass('selected');
		this.fireEvent('selectNode',[a]);
	},//end function



	//Двойной клик по ноде
	onNodeDblClick: function(e){
		if (!e) return;
		e.target = document.id(e.target);
		if (!e.target) return;
		var a = e.target.get('tag') == 'a' ? e.target : e.target.getParent('a');
		if(!a) return;
		this.fireEvent('dblclickNode',[a]);
	},//end function



	//Обнуление данных
	empty: function(){
		if(this.data) this.data.empty();
		if(this.element) this.element.empty();
		this.data = null;
		this.rootUL = null;
		this.selectedNode = null;
		this.rootTitle = '';
	},




	//Применение массива данных для построения дерева
	setData: function(title, data, selected){
		this.empty();
		var tof = typeOf(data);
		if(tof!='array'&&tof!='object') return this;
		if(tof == 'array'){
			this.data = data.clone();
		}else{
			this.data = [];
			for(var i in data) this.data.push(data[i]);
		}
		this.rootTitle = title;
		this.build(selected);
	},//end function



	//Елемент дерева диаграммы по-умолчанию
	defaultNode:{
		'id': 0,
		'post_uid': 0,
		'boss_uid': 0,
		'company_id':0,
		'post_id': 0,
		'boss_id': 0,
		'short_name': '',
		'full_name': '',
		'childs': [],
		'collapsed':false,
		'class':''
	},


	//Построение дерева диаграммы
	build: function(){

		if(!this.data || !this.element) return this;

		var threeNodes = {};
		var rootNodes = [];
		var post_uid, boss_uid;
		var empty_nodes = 0;

		//1: Убираем "битые" ноды, применяем значения по-умолчанию
		for(var i=0; i<this.data.length; ++i){
			if(typeOf(this.data[i])!='object') continue;
			post_uid = this.data[i]['post_uid'];
			threeNodes[post_uid] = Object.merge({}, this.defaultNode, this.data[i]);
		}

		//2: Вычисляем родитель->дитя
		for(post_uid in threeNodes){
			boss_uid = threeNodes[post_uid]['boss_uid'];
			if(String(boss_uid)!='0'){
				if(typeOf(threeNodes[boss_uid])!='object') continue;
				threeNodes[boss_uid]['childs'].push(post_uid);
			}else{
				rootNodes.push(post_uid);
			}
		}

		threeNodes['0'] = {
			'id': 0,
			'post_uid': 0,
			'boss_uid': 0,
			'company_id':0,
			'post_id': 0,
			'boss_id': 0,
			'short_name': this.rootTitle,
			'full_name': this.rootTitle,
			'childs': rootNodes,
			'collapsed':false,
			'class':'header nodrag noselect'
		};

		//3: Построение дерева
		this.rootUL = this.buildNodes(this.element, threeNodes, ['0'], 0);

		return this;
	},//end function




	/*Построение элемента дерева*/
	buildNodes: function(parent, allNodes, levelNodes, level){

		var ul = new Element('ul').inject(parent);
		var li, a, childUL;

		//Сортировка элементов
		levelNodes.sort(function(a,b){
			var node_a = allNodes[a];
			var node_b = allNodes[b];
			if(typeOf(node_a)!='object'||typeOf(node_b)!='object') return 0;
			if(node_a['full_name']>node_b['full_name']) return 1;
			return -1;
		});

		//Просмотр массива нод текущего уровня
		for(var i=0; i<levelNodes.length; ++i){

			node = allNodes[levelNodes[i]];
			if(typeOf(node)!='object') continue;

			li = this.createLINode(ul, node);

			if(node['childs'].length > 0){
				childUL = this.buildNodes(li, allNodes, node['childs'], level+1);
			}

		}//for

		return ul;
	},//end function



	//Создание ноды
	createLINode: function(parent, data){
		var li = new Element('li').inject(parent);
		var a = new Element('a',{
			'class':(data['class'] || ''),
			'href': '#',
			'text': data['full_name'],
			'events':{
				'click': this.onNodeClick.bind(this),
				'dblclick': this.onNodeDblClick.bind(this),
			}
		}).inject(li);
		this.storeVars(a, data);
		return li;
	},//end function





	//Запись переменных в элемент
	storeVars: function(element, options){
		for(var i in options) element.store(i,options[i]);
	},//end function



	//Фильтрация
	filter: function(key){
		key = String(key).toLowerCase().trim();
		var showing;
		var aa = this.rootUL.getElements('a');
		for(var i=0;i<aa.length;i++){
			if(aa[i].hasClass('header')) continue;
			aa[i].removeClass('dimness');
			if(key==''){
				showing = true;
			}else{
				showing = (String(aa[i].get('text')).toLowerCase().indexOf(key) > -1) ? true : false;
			}
			if(!showing) aa[i].addClass('dimness');
		}
	},



	//Выбор элемента
	select: function(key, term){
		key = String(key).trim();
		term = String(term).toLowerCase().trim();
		var showing;
		var aa = this.rootUL.getElements('a');
		for(var i=0;i<aa.length;i++){
			if(key==''){
				showing = (String(aa[i].get('text')).toLowerCase().indexOf(term) > -1) ? true : false;
			}else{
				showing = (String(aa[i].retrieve(key)).toLowerCase().indexOf(term) > -1) ? true : false;
			}
			if(showing){
				if(aa[i].hasClass('selected')) return;
				if(this.selectedNode) this.selectedNode.removeClass('selected');
				if(!aa[i].hasClass('noselect')){
					this.selectedNode = aa[i].addClass('selected');
					this.fireEvent('selectNode',[aa[i]]);
					return;
				}
			}
		}
	},


	//Сериализация дерева для сохранения
	serialize: function(fn, base){
		if (!fn) fn = function(el){ return el.get('id'); };
		if (!base) base = this.element;

		var result = {};
		base.getChildren('li').each(function(el){
			var ul = el.getElement('ul');
			result[fn(el)] = ul ? this.serialize(fn, ul) : 1;
		}, this);
		return result;
	}//end function


});//end class

