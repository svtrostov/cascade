;(function() {
	
	window.jsPlumbDemo = {
		init : function() {
				
			jsPlumb.importDefaults({
				DragOptions : { cursor: 'pointer', zIndex:2000 },
				EndpointStyles : [{ fillStyle:'#225588' }, { fillStyle:'#558822' }],
				Endpoints : [ [ "Dot", {radius:7} ], [ "Dot", { radius:11 } ]],
				ConnectionOverlays : [
					[ "Arrow", { location:0.9 } ]
				],
				ConnectorZIndex:5
			});			

			// this is the paint style for the connecting lines..
			var connectorPaintStyle = {
				lineWidth:5,
				strokeStyle:"#deea18",
				joinstyle:"round",
				outlineColor:"white",
				outlineWidth:7
			},
			// .. and this is the hover style. 
			connectorHoverStyle = {
				lineWidth:7,
				strokeStyle:"#2e2aF8"
			},
			// the definition of source endpoints (the small blue ones)
			sourceEndpoint = {
				endpoint:"Dot",
				paintStyle:{ fillStyle:"#225588",radius:7 },
				isSource:true,
				connector:[ "Flowchart", { stub:[40, 60], gap:10 } ],								
				connectorStyle:connectorPaintStyle,
				hoverPaintStyle:connectorHoverStyle,
				connectorHoverStyle:connectorHoverStyle,
                dragOptions:{}
			},
			// a source endpoint that sits at BottomCenter
		//	bottomSource = jsPlumb.extend( { anchor:"BottomCenter" }, sourceEndpoint),
			// the definition of target endpoints (will appear when the user drags a connection) 
			targetEndpoint = {
				endpoint:"Dot",					
				paintStyle:{ fillStyle:"#558822",radius:11 },
				hoverPaintStyle:connectorHoverStyle,
				maxConnections:-1,
				dropOptions:{ hoverClass:"hover", activeClass:"active" },
				isTarget:true
			},			
			init = function(connection) {
				//connection.getOverlay("label").setLabel(connection.sourceId.substring(6) + "-" + connection.targetId.substring(6));
			};			

			var allSourceEndpoints = [], allTargetEndpoints = [];
				_addEndpoints = function(toId, sourceAnchors, targetAnchors) {
					for (var i = 0; i < sourceAnchors.length; i++) {
						var sourceUUID = toId + sourceAnchors[i];
						allSourceEndpoints.push(jsPlumb.addEndpoint(toId, sourceEndpoint, { anchor:sourceAnchors[i], uuid:sourceUUID }));						
					}
					for (var j = 0; j < targetAnchors.length; j++) {
						var targetUUID = toId + targetAnchors[j];
						allTargetEndpoints.push(jsPlumb.addEndpoint(toId, targetEndpoint, { anchor:targetAnchors[j], uuid:targetUUID }));						
					}
				};

			_addEndpoints("userBoss", ["TopCenter", "BottomCenter"], ["LeftMiddle", "RightMiddle"]);			
			_addEndpoints("userDirector", ["LeftMiddle", "BottomCenter"], ["TopCenter", "RightMiddle"]);
			_addEndpoints("userFinance", ["RightMiddle", "BottomCenter"], ["LeftMiddle", "TopCenter"]);
			_addEndpoints("userManager", ["LeftMiddle", "RightMiddle"], ["TopCenter", "BottomCenter"]);
						
			// listen for new connections; initialise them the same way we initialise the connections at startup.
			jsPlumb.bind("jsPlumbConnection", function(connInfo, originalEvent) { 
				init(connInfo.connection);
			});			
						
			// make all the window divs draggable						
			//jsPlumb.draggable(jsPlumb.getSelector(".window"), { grid: [20, 20] });
			// THIS DEMO ONLY USES getSelector FOR CONVENIENCE. Use your library's appropriate selector method!
			jsPlumb.draggable(jsPlumb.getSelector(".window"));

					
			//
			// listen for clicks on connections, and offer to delete connections on click.
			//
			jsPlumb.bind("click", function(conn, originalEvent) {
				jsPlumb.detach(conn); 
			});	
			
			jsPlumb.bind("connectionDrag", function(connection) {
				console.log("connection " + connection.id + " is being dragged");
			});		
			
			jsPlumb.bind("connectionDragStop", function(connection) {
				console.log("connection " + connection.id + " was dragged");
			});
		}
	};
})();
