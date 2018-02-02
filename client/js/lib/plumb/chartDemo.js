;(function() {
	
	window.jsOrgChart = {

		prevSelected: null,
		orgModified: false,
		
		color: "gray",
		
		connectorPaintStyle: {
			lineWidth:3,
			strokeStyle: this.color,
			joinstyle:"round",
			outlineColor:"white",
			outlineWidth:1
		},
			
		connectorHoverStyle: {
			lineWidth:3,
			strokeStyle:"#ec9f2e"
		},
			
		sourceEndpoint: {
			endpoint:"Dot",
			paintStyle:{ fillStyle:"#225588",radius:9 },
			isSource:true,
			connector:[ "Flowchart", { } ],								
			connectorStyle:this.connectorPaintStyle,
			hoverPaintStyle:this.connectorHoverStyle,
			connectorHoverStyle:this.connectorHoverStyle,
            dragOptions:{},
            maxConnections:-1
		},
			
		targetEndpoint: {
			endpoint:"Dot",					
			paintStyle:{ fillStyle:"#558822",radius:9 },
			connectorStyle:this.connectorPaintStyle,
			hoverPaintStyle:this.connectorHoverStyle,
			connectorHoverStyle:this.connectorHoverStyle,
			maxConnections:1,
			uniqueEndpoint:true,
			dropOptions:{ hoverClass:"hover", activeClass:"active" },
			suspendedEndpoint:{},
			isTarget:true
		},
			
		init : function() {			

			jsPlumb.setRenderMode(jsPlumb.CANVAS);

			jsPlumb.importDefaults({
				// notice the 'curviness' argument to this Bezier curve.  the curves on this page are far smoother
				// than the curves on the first demo, which use the default curviness value.			
				//Connector : [ "Bezier", { curviness:30 } ],
				Connector:[ "Flowchart", {} ],
				DragOptions : { cursor: "pointer", zIndex:2000 },
				PaintStyle : { strokeStyle:this.color, lineWidth:2 },
				EndpointStyle : { radius:9, fillStyle:this.color },
				HoverPaintStyle : {strokeStyle:"#ec9f2e" },
				EndpointHoverStyle : {fillStyle:"#ec9f2e" },			
				//Anchors :  [ "BottomCenter", "TopCenter" ],
				ConnectorZIndex:5
			});
			
			
			/*
			jsPlumb.connect({source:"userBoss", target:"userDirector"});
			jsPlumb.connect({source:"userDirector", target:"userManager"});
			jsPlumb.connect({source:"userDirector", target:"userFinance"});
			jsPlumb.connect({source:"userFinance", target:"userGenBuh"});
			jsPlumb.connect({source:"userGenBuh", target:"userBuh"});
			*/
			/*
			var divsWithWindowClass = jsPlumb.CurrentLibrary.getSelector(".window");
			jsPlumb.draggable(divsWithWindowClass);
			jsPlumb.addEndpoint(divsWithWindowClass, this.sourceEndpoint, {anchors: [ "BottomCenter"]});
			jsPlumb.addEndpoint(divsWithWindowClass, this.targetEndpoint, {anchors: [ "TopCenter"]});
			*/
			
			//Отсоединение
			jsPlumb.bind("click", function(connection, originalEvent) {
					jsPlumb.detach(connection); 
					connection.target.store('post_parent',0);
					this.orgModified = true;
			}.bind(this));	

			//Присоединение
			jsPlumb.bind("jsPlumbConnection", function(connection) {
				connection.target.store('post_parent',connection.source.retrieve('post_id'));
				this.orgModified = true;
				
			}.bind(this));					
				
			//Отсоединение
			jsPlumb.bind("jsPlumbConnectionDetached", function(connection) {
				connection.target.store('post_parent',0);
				this.orgModified = true;
			}.bind(this));			
					
			//Перед окончанием соединения
			jsPlumb.bind("beforeDrop", function(connection) {
				//Если идентификаторы равны, соединение не устанавливаем
				if(connection.sourceId == connection.targetId){
					return false;
				}
				return true;
			}.bind(this));

		},
		
		prepare: function(elId) {		
			jsPlumb.addEndpoint(elId, this.sourceEndpoint, {anchors: ["BottomCenter"], uuid:elId+"BottomCenter"});
			jsPlumb.addEndpoint(elId, this.targetEndpoint, {anchors: ["TopCenter"], uuid:elId+"TopCenter"});
		},
		
		createElement: function(post_id, post_name, x, y){
			
			var d = new Element('div',{}).addClass('window').inject($('orgChartArea'));
			var id = '' + ((new Date().getTime())), _d = jsPlumb.CurrentLibrary.getElementObject(d);
			//jsPlumb.CurrentLibrary.setAttribute(_d, "id", id);
			d.set('html', post_name).store('post_id',post_id).store('post_parent',0).set('id',id);
			d.addEvent('click',function(e){
				if(typeOf(window.jsOrgChart.prevSelected)=='element') window.jsOrgChart.prevSelected.removeClass('selected').addClass('unselected');
				this.removeClass('unselected').addClass('selected');
				window.jsOrgChart.prevSelected = this;
			}.bind(d));		
			var w = 300, h = 300;
			if(typeof x != 'number') x = (0.2 * w) + Math.floor(Math.random()*(0.5 * w));
			if(typeof y != 'number') y = (0.2 * h) + Math.floor(Math.random()*(0.6 * h));
			d.style.top= y + 'px';
			d.style.left= x + 'px';
			
			var toolarea = new Element('div',{}).addClass('toolarea').inject(d);

			var bdetach = new Element('img',{
				'src' : '/lib/themes/default/images/plumb/unit_detach.png',
				'cursor':'pointer',
				'alt':'Удалить связи'
			}).inject(toolarea).addEvent('click',function(e){
				jsPlumb.detachAllConnections(this.id);
			}.bind(d));		

			var bdel = new Element('img',{
				'src' : '/lib/themes/default/images/plumb/unit_remove.png',
				'cursor':'pointer',
				'alt':'Удалить элемент'
			}).inject(toolarea).addEvent('click',function(e){
				jsPlumb.detachAllConnections(this.id);
				jsPlumb.removeAllEndpoints(this.id);
				this.parentNode.removeChild(this);
			}.bind(d));
			

			
			return {d:d, id:id};
		},
		
		addElement: function(post_id, post_name, x, y){
			var info = this.createElement(post_id, post_name, x, y);
			var e = this.prepare(info.id);	
			jsPlumb.draggable(info.id);
			return info;
		},
		
		removeAll: function(){
			var fields = $$('.window');
			fields.each(function(obj, i){
				jsPlumb.detachAllConnections(obj.id);
				jsPlumb.removeAllEndpoints(obj.id);
				obj.parentNode.removeChild(obj);
			});
		}
		
	};
	
	
})();
