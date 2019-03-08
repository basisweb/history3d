<?php
/**
 * index.php
 * Copyright (C) 2016 - 2019 Chris Wiese (http://basisweb.de)
 *
 * PHP version 7 
 *
 * @copyright  	Christian Wiese (basisweb) 2016 - 2019
 * @author     	Christian Wiese <http://www.basisweb.de>
 * 
 * @file       	index.php
 * @lastchange 	08.03.2019
 * @encoding   	UTF-8
 *
 *
 * 
 *
 */
 
?>
<!doctype html>
<html lang="de">
<head>
	<title>The History - Project</title>
	<meta charset="utf-8">
	
	<meta http-equiv="Cache-Control" content="post-check=0">
	<meta http-equiv="Cache-Control" content="pre-check=0">
	<meta http-equiv=“cache-control“ content=“no-cache“>
	<meta http-equiv=“pragma“ content=“no-cache“>
	<meta http-equiv=“expires“ content=“0″>

	<meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
	<link href="js/jquery.bxslider/jquery.bxslider.css" rel="stylesheet" />
	<link href="css/history.css" rel="stylesheet">

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
	<script src="js/jquery.fitvids.js"></script>
	<script src="js/jquery.bxslider/jquery.bxslider.min.js"></script>
	<script src="build/three.js"></script>
	<script src="js/Detector.js"></script>
	<script src="js/libs/stats.min.js"></script>
	<script src="js/controls/FlyControls.js"></script>
	<script src="js/utils/GeometryUtils.js"></script>
	<script src="js/TypedArrayUtils.js"></script>
	<script src='js/Tween.js'></script>
</head>

<body>

<div id="edit">
	<span id="buttons"><button class="btn">Hinzufügen</button></span>
</div>
<div id="status">
	<div id="active">&nbsp;</div>
	<div id="fps">&nbsp;</div>
	<div id="campos">&nbsp;</div>

</div>


<script>

/**
 *
 * Globals 
 *
 */


var container, scene, camera, renderer, controls, stats, div, intersected, kdtree, kdpos, target, flightIndex;
var materials = [], colors = [], positions = [], activeInfos = [], match = [], lines = [], allLines = [], vitaConnects = [];

var mouse 		= new THREE.Vector2(), INTERSECTED;
var raycaster 	= new THREE.Raycaster();
var clock 		= new THREE.Clock();
var d 			= new THREE.Vector3();

var slider, tween, camLookAt, rotate;

var firstMouseMove = false;
var holdInfo 	   = false;
var flyMode 	   = false;

var radius 		= 100;
var theta 		= 0;

// Größe der Jahreszahlen
var letterSize 	= 100;	
 				 		
// Abstand zwischen den Jahreszahlen
var marginYear  = 10000;			

// Jahre zwischen einzelnen Jahreszahlen
var stepsYear = 10;

// Beginn der Timeline
var firstYear   = 1800;     

// FlySpeed
var flySpeed = 500;
						
// Ende der Timeline (akt. Jahr)
var lastYear    = new Date().getFullYear();

// Letztangezeigte Jahreszahl
var endYear    = parseInt(String(lastYear + stepsYear).substr(0,2)+"00");	

// Nullpunkt des Koordinatensystems
var yearNull    = endYear - (endYear - firstYear) / 2 ;


// Ausdehnung der Scene
var worldDim 	= (endYear - firstYear) / 100 * marginYear;	

// Breite je Längengrad
var latWidth = 200;

// Höhe der GeoFlächen
var geoZ = -2000;

// Neben welchem Jahrhundert startet die Kamera
var cameraStart	= 1980;		

// Größe der HistoryPoints (Default)
var size 	 	= 10;				
			
// Hintergund - Farbe
var bgcolor		= 0x111111;						

// Annäherungsdistance
var maxDistance      = 170000;

// Aktivierungsdistanz
var activateDistance = 150000;

// HistoryPoint Farbe normal
var color		= 0xff9549;  // 0x4989ff;	
var colorA		= 0xff9549;
var colorB      = 0xff496a;

// HistroyPointFarbe aktiv					
var activeColor	= 0x6aff49;						

// Display - Fläche für aktive Points
var activeDisplay;

/**
 * 
 * Farben Connect - Linien 
 * 
 * 0 :: Ehe 					gelb			
 * 1 :: Kind von ... 			hellgrau
 * 2 :: Teilnahme an ...		lila
 * 3 :: Mitgliedschaft in ...	orange
 * 4 :: Eventabhängigkeit		blau
 * 5 :: offen					grau
 *
 */
 
var connectColor= [0xdcdb22, 0x6f6f6f, 0x8b1f5a, 0x00aa00, 0x69a1cb, 0x333333]; 		

// Debug - Modus
var DEBUG = true;

// HistoryPoints (Personen) 
var objects = new Array();

// HistoryEvents (Events) 
var events = new Array();

// HistoryConnects (Connects) 
var connects = new Array();
var anzahlConnects;



/**
 *
 * HistoryPoints (Personen) einlesen
 *
 */
$.ajax({
    url: "ajax/getPersons.php",
    async: false,
    success: function(msg) {
        var obj = jQuery.parseJSON(msg);       
       
        var i =1;
        $.each(obj,function(key, val) {
	        var ob = new Array(val[0], val[1], val[2], val[3], val[4], val[5], 0);
			objects[i] = ob;
			i++;
        });
    }
});

var anzahlObjekte  = objects.length;

/**
 *
 * HistoryPoints (Ereignisse) einlesen
 *
 */
 
$.ajax({
    url: "ajax/getEvents.php",
    async: false,
    success: function(msg) {
        var obj = jQuery.parseJSON(msg);       
       
        var i =1;
        $.each(obj,function(key, val) {
	        var ob = new Array(val[0], val[1], val[2], val[3], val[4], val[5], val[6]);
			events[i] = ob;
			i++;
        });
    }
});

var anzahlEvents   = events.length;

kdpos  = new Float32Array( (anzahlObjekte + anzahlEvents) * 3 );

/** 
 *
 * Scene einrichten ...
 *
 */
 
init();

/**
 *
 *  ... und Funktionen starten.
 *
 */
 
animate();


/**
 * init()
 * 
 * Three.js - Umgebung einrichten
 *
 */	
function init() {
	
	container = document.createElement( 'div' );
	document.body.appendChild( container).setAttribute('class','back');
	
	var info = document.createElement( 'div' );
	info.style.position = 'absolute';
	info.style.top = '10px';
	info.style.width = '100%';
	info.style.textAlign = 'center';
	
	container.appendChild( info );
	
	/**
	 * 
	 * Scene
	 *
	 */
	 
	scene = new THREE.Scene();
	//scene.fog = new THREE.FogExp2( 0x333333, 0.00005 );

	// SkyBox
	scene.add( makeSkybox( [
		'textures/milkyway/dark-s_px.jpg', // Rechts
		'textures/milkyway/dark-s_nx.jpg', // Links
		'textures/milkyway/dark-s_py.jpg', // Oben
		'textures/milkyway/dark-s_ny.jpg', // Unten
		'textures/milkyway/dark-s_pz.jpg', // Rückseite
		'textures/milkyway/dark-s_nz.jpg'  // Frontseite
	]));
	
	/**
	 * 
	 * Camera
	 *
	 */
	
	var SCREEN_WIDTH = window.innerWidth, SCREEN_HEIGHT = window.innerHeight;
	var VIEW_ANGLE = 45, ASPECT = SCREEN_WIDTH / SCREEN_HEIGHT, NEAR = 0.1, FAR = worldDim * 2;
	
	camera = new THREE.PerspectiveCamera( VIEW_ANGLE, ASPECT, NEAR, FAR);
	camera.position.set((cameraStart - yearNull) * marginYear/100, 1000,6000);
	camera.lookAt(new THREE.Vector3((cameraStart - yearNull) * marginYear/100, 1000,3000));
	
	scene.add(camera);
	
	/**
	 * 
	 * Renderer
	 *
	 */
	if ( Detector.webgl ) renderer = new THREE.WebGLRenderer( {antialias:true, alpha: true } );
	else renderer = new THREE.CanvasRenderer(); 
	
	renderer.setPixelRatio( window.devicePixelRatio );
	renderer.setSize( window.innerWidth, window.innerHeight );
	renderer.sortObjects = false;
	container.appendChild(renderer.domElement);
	
	/**
	 *
	 * Browser-Events 
	 *
	 */
	
	document.addEventListener( 'click',     onDocumentMouseClick, false ); 
	document.addEventListener( 'mousemove', onDocumentMouseMove, false );
	window.addEventListener(   'resize',    onWindowResize, false );
	
	/**
	 *
	 * Stats (FPS)
	 *
	 */
	 
	//stats = new Stats();
	//container.appendChild( stats.dom );

	/**
	 * 
	 * Controls
	 *
	 */

    controls = new THREE.FlyControls( camera );
	controls.movementSpeed = flySpeed;
	controls.domElement = container;
	controls.rollSpeed = Math.PI / 24;
	controls.autoForward = false;
	controls.dragToLook = true;

	/**
	 *
	 * Licht 
	 *
	 */
	
	var ambient = new THREE.AmbientLight( 0xaabbcc );
	scene.add( ambient );
	
	/**
	 *
	 * Axes
	 *
	 */

	var axe;	
	var material = new THREE.LineBasicMaterial({color: 0x0000aa});
	
	var geometry = new THREE.Geometry();
	geometry.vertices.push(new THREE.Vector3(-worldDim/2 - marginYear,0,0));
	geometry.vertices.push(new THREE.Vector3(worldDim/2  + marginYear,0,0));
	axe = new THREE.Line(geometry, material);
	scene.add(axe);
	geometry.dispose();
	
	var geometry = new THREE.Geometry();
	geometry.vertices.push(new THREE.Vector3(0,-worldDim/2 - marginYear,0));
	geometry.vertices.push(new THREE.Vector3(0,worldDim/2  + marginYear,0));
	axe = new THREE.Line(geometry, material);
	scene.add(axe);
	geometry.dispose();
	
	var geometry = new THREE.Geometry();
	geometry.vertices.push(new THREE.Vector3(0,0,-worldDim/2 - marginYear));
	geometry.vertices.push(new THREE.Vector3(0,0,worldDim/2  + marginYear));
	axe = new THREE.Line(geometry, material);
	
	scene.add(axe);
	geometry.dispose();
	
	var gridHelper = new THREE.GridHelper( worldDim , (worldDim / 500) * 2, 0x0000aa, 0x666666 );
	gridHelper.position.y = 0;
	gridHelper.position.x = 0;
	scene.add( gridHelper );
	
		
	/**
	 *
	 * HistoryPersons
	 *
	 */
	
	drawHistoryPersons();
	
	/**
	 *
	 * HistoryEvents
	 *
	 */
	
	drawHistoryEvents();
	
	/**
	 *
	 * Jahreszahlen
	 *
	 */
	
	drawJahreszahlen();
	
	/**
	 *
	 * Geographie-Flächen
	 *
	 */
	
	//drawGeoFlaechen(5.8,14.5);
	
	
	/**
	 *
	 * KDTree aufbauen & ObjektIDs identifizieren
	 *
	 */
	var distanceFunction = function(a, b){
		return Math.pow(a[0] - b[0], 2) +  Math.pow(a[1] - b[1], 2) +  Math.pow(a[2] - b[2], 2);
	}; 
	
	kdtree = new THREE.TypedArrayUtils.Kdtree( kdpos, distanceFunction, 3, objects);
	
	/**
	 *
	 * Debug
	 *
	 */
	 
	if (DEBUG) console.log(scene);
	
	
	
}


/**
 * 
 * Erstellt Hintergrund (SkyBox)
 * 
 * @param {array} urls - Filenamen der Texturen (jpg)
 * @param {int}   size - Größe der SkyBox (Quadratseitenlänge)
 * 
 */
function makeSkybox( urls ) {
	// Fläachen des Würfels laden
	var skyboxCubemap = new THREE.CubeTextureLoader().load( urls );
	skyboxCubemap.format = THREE.RGBFormat;
	// Shader
	var skyboxShader = THREE.ShaderLib['cube'];
	skyboxShader.uniforms['tCube'].value = skyboxCubemap;
	// Würfel zurückgeben (Größe: worldDim)
	return  new THREE.Mesh(
			new THREE.BoxGeometry( worldDim*2, worldDim*2, worldDim*2 ),
			new THREE.ShaderMaterial({
			fragmentShader : skyboxShader.fragmentShader, vertexShader : skyboxShader.vertexShader,
			uniforms : skyboxShader.uniforms, depthWrite : false, side : THREE.BackSide
		})
	);
}



/** 
 *
 * drawJahreszahlen()
 * 
 * Jahreszahlen einfügen
 *
 */	
function drawJahreszahlen() {	 
	var loader = new THREE.FontLoader();
	loader.load( 'fonts/helvetiker_regular.typeface.json', function ( font ) {
		
		var start = (worldDim/2);
		if (DEBUG)  console.log(worldDim);	
			
		var jahr = endYear;
		var textMaterial = new THREE.MeshPhongMaterial( { color: 0xdc911b, specular: 0x000000 });
		
		for (i=0; jahr>firstYear; i+=stepsYear) {
		
			jahr = endYear - i;
			
			var textGeometry = new THREE.TextGeometry( jahr, {

			    font: font,
			
			    size: letterSize,
			    height: 0,
			    curveSegments: 5,
			
			    bevelThickness: 0,
			    bevelSize: 0,
			    bevelEnabled: false
			
			});
			
			var mesh = new THREE.Mesh( textGeometry, textMaterial );
			
			mesh.position.set( start + i * -marginYear/100,10,0 );
			
			scene.add( mesh );

		} // for
	
	} ); 
	
}



/** 
 *
 * drawSpline(x1,y1,z1,x2,y2,z2,r,c) 
 * 
 * Spline zeichnen zwischen zwei Punkten
 *
 */	
function drawSpline(x1,y1,z1,x2,y2,z2,r,c) {

	var middle 	= [(x1 + x2) / 2, (y1 + y2) / 2 + r, (z1 + z2) / 2];
	
    var curve 	= new THREE.QuadraticBezierCurve3(new THREE.Vector3(x1, y1, z1), 
    			  new THREE.Vector3(middle[0], middle[1], middle[2]), 
    			  new THREE.Vector3(x2, y2, z2));
    
	var geometry = new THREE.Geometry();
	geometry.vertices = curve.getPoints( 50 );

	var material = new THREE.LineBasicMaterial( { color : c } );

	var curvedLine = new THREE.Line( geometry, material );
    scene.add(curvedLine);
    lines.push(curvedLine.id);
}

/** 
 *
 * drawGeoFlaechen() 
 * 
 * Flächen über der Geographie-Achse
 *
 */	
function drawGeoFlaechen(l1, l2) {
	
	var geometry = new THREE.BoxBufferGeometry( worldDim * 2, 0, (l2-l1) * latWidth);
	var geo 	 = new THREE.Mesh(geometry, new THREE.MeshPhongMaterial( { color: 0x335588}));

    geo.position.x = worldDim/2;
    geo.position.y = geoZ;
    
    geo.position.z = l2 * latWidth - ((l2-l1) * latWidth) / 2;
    
    scene.add(geo);
   	
  	// CleanUp
	geometry.dispose();  
	
 }


/** 
 *
 * drawHistoryPersons()
 * 
 * Alle HistoryPoints (Personen) einfügen
 *
 */	
 

function drawHistoryPersons() {	
	
	for (var i = 1; i < anzahlObjekte; i ++ ) {
		
		var point = posXYZ(objects[i]);
	
		addPoint(point[0], point[1], point[2], size, color, i, objects[i], 0);
		
		positions[i] = point;
		
		kdpos[i*3 + 0] = point[0];
		kdpos[i*3 + 1] = point[1];
		kdpos[i*3 + 2] = point[2];
				
	}
}


/** 
 *
 * drawHistoryEvents()
 * 
 * Alle HistoryEvents (Events) einfügen
 *
 */	
function drawHistoryEvents() {	
	var c = 1;
	for (var j = anzahlObjekte; j < anzahlEvents + anzahlObjekte - 1; j ++ ) {
		var point = posXYZ(events[c]);
		addPoint(point[0], point[1], point[2], size, color, j, events[c], 1);
		positions[j] = point;
		kdpos[j*3 + 0] = point[0];
		kdpos[j*3 + 1] = point[1];
		kdpos[j*3 + 2] = point[2];
		c++;
	}
}


/** 
 *
 * drawHistoryConnects()
 * 
 * Alle HistoryConnects einfügen
 *
 */	
function drawHistoryConnects() {	
	for ( i = 1; i < anzahlConnects; i ++ ) {
		addConnect(connects[i]);
	}
}


/** 
 *
 * addConnect(c, uid)
 * 
 * Einzelnen HistoryConnect einfügen
 *
 * @param 	c		array	Connect-Array []
 * @param	uid		int		UID
 *
 */	

function addConnect(c) {
	
	var pointA;
	var pointB;
	var r;
	
	if (c[1] != null && c[2] != null) {
	
		// Person - Person (Typ :: 0)
		if (c[0] == 0) {
			pointA = positions[c[1]];
			pointB = positions[c[2]];
			r = 150;
		}
		
		// Event - Person (Typ :: 1)
		if (c[0] == 1) {
			cc = parseInt(c[1]) + anzahlObjekte - 1;
			pointA = positions[cc];
			pointB = positions[c[2]];
			r = 200;
			vitaConnects.push(pointA);
			
		}
		
		// Event - Event (Typ :: 2)
		if (c[0] == 2) {
			cc = parseInt(c[1]) + anzahlObjekte - 1;
			pointA = positions[cc];
			cc = parseInt(c[2]) + anzahlObjekte - 1;
			pointB = positions[cc];
			vitaConnects.push(pointB)
		
			if (c[3] == 3) {
				r = 100;
			} 
			else {
				r = -350;
			}
		}
		
		
		// Gerade Verbindungen
		/*
			var material = new THREE.LineBasicMaterial({color: connectColor[c[3]]});
			var geometry = new THREE.Geometry();
			geometry.vertices.push(new THREE.Vector3(pointA[0], pointA[1], pointA[2]));
			geometry.vertices.push(new THREE.Vector3(pointB[0], pointB[1], pointB[2]));
			var line = new THREE.Line(geometry, material);
			scene.add(line);
			lines.push(line.id);	
		*/

		// Curved Verbindungen		
		drawSpline(pointA[0],pointA[1],pointA[2],pointB[0],pointB[1],pointB[2],r, connectColor[c[3]]);
	
			
	} // if null

}

/** 
 *
 * posXYZ(o)
 * 
 * 3D-Koordinaten eine HistoryPoints ermitteln
 *
 * @param 	o		array	HistoryPoint
 * @return	array [x,y,z]
 *
 */	
function posXYZ(o) {

	var d  = o[0];
	var d1 = d.split(" ");
	var d2 = d1[0].split(".");
	
	var jahr  = d2[2];
	var monat = d2[1];
	var tag   = d2[0];
	
	// Varianz auf der Y-Achse: 1 bis 10
	var r = Math.floor((Math.random() * 10) + 1);
	
	// Zeitachse
	var pointX = (jahr - yearNull) * marginYear/100 + monat * (marginYear/100) / 12 + tag * (marginYear/100) / 365;
	
	// Ereignisachse inkl. Varianz, um Überlappungen zu vermeiden (Basis: 10, 0-Ebene ist Geografie-Fläche).
	var pointY = o[6] * 100;
	
	// Geografieachse
	var pointZ = o[3] * latWidth;
	
	return [pointX, pointY, pointZ];
}


/** 
 *
 * rposXYZ(o)
 * 
 * Datum & Geographie eines 3D-Punkts ermitteln
 *
 * @param 	o		array	xyz
 * @return	array [datum, lat, lng]
 *
 */	
function rposXYZ(o) {

	// Zeitachse zurückrechnen

	var jahr  = Math.round(yearNull + (o.x / (marginYear / 100)));
	
	return jahr;
}


/** 
 *
 * addPoint(x, y, z, size, color, uid, o)
 * 
 * Einzelnen HistoryPoint einsetzen
 *
 * @param 	x		int		X
 * @param 	y		int		Y
 * @param 	z		int		Z
 * @param 	size	int		size [obsolet]
 * @param 	color	hex		Farbe
 * @param 	uid		int		ID des HistoryPoints
 * @param 	o		array	HistoryPoint - Array
  *
 */	
function addPoint(x, y, z, size, color, uid, o, t) {
	
	if (o[1] == 0) {
		var a = size
		var b = size;
		var c = size;
	}
	
	else {
		var a = size * o[6];
		var b = size * o[6];
		var c = size * o[6];
	}

	var materialPoint = new THREE.MeshBasicMaterial( {color: color, wireframe: false} );
	
	// Würfel
    var geometry = new THREE.BoxBufferGeometry( a,b,c );
	
	// Kugel
	//wvar geometry = new THREE.SphereBufferGeometry( size, 16, 16 );
	
	var point 	 = new THREE.Mesh(geometry, materialPoint);

    point.position.x = x;
    point.position.y = y;
    point.position.z = z;
    
    point.historyType 	= "historyPoint";
    point.info          = o;
   
    point.historyActive = false;
    point.uid 			= uid;
    point.historyKat    = t;
    point.historyDBID   = o[5];
    
    //point.lookAt(camera.position);
    scene.add(point);
    
    point.name = parseInt(x) + "/" + parseInt(y) + "/" + parseInt(z);
  	
	if (DEBUG) console.log(uid, point.name, point.id, o);  
  	
  	// CleanUp
	geometry.dispose();  
 }
 

/** 
 *
 * activatePoint(uid)
 * 
 * HistoryPoint aktivieren und Infobild über ihm einblenden
 *
 */ 
function activatePoint(objName) {

	var ao = scene.getObjectByName(objName);
	
	if (ao) {
		
		var bild;
	
		if (ao.historyKat == 0) bild	= objects[ao.uid][4];
		if (ao.historyKat == 1) bild	= events[ao.uid - anzahlObjekte + 1][4];
		
		if (bild && ao.historyActive === false) {
	
			$('#active').text("Activating :: " + ao.info[0] + " :: " + ao.info[2], ao.historyActive);
			console.log(ao.info);
			
			var loader = new THREE.TextureLoader();
			loader.load('admin/files/personen/' + bild, function ( texture ) {
				var materialA = new THREE.MeshBasicMaterial( {map: texture} );
				ao.material = materialA;
				ao.lookAt(camera.position);
				ao.historyActive = true;
				ao.material.color.setHex(activeColor);
			  	materialA.dispose();
			});
		}
	}
}

/** 
 *
 * deactivatePoint(uid)
 * 
 * HistoryPoint deaktivieren und Infobild über ihm ausblenden
 *
 */ 
function deactivatePoint(objName) {
	var ao = scene.getObjectByName(objName);
	if(ao && ao.historyActive === true) {
		if (DEBUG) console.log("Deactivating :: " + ao.info[0], ao.historyActive, ao.infoPanel);
		
		ao.material = new THREE.MeshBasicMaterial( {color: color} );
		ao.historyActive = false;

		if (DEBUG) console.log(scene.children);
		
	}
} 


/** 
 *
 * detailsPoint(uid)
 * 
 * Nach Mausberühung Infos einblenden
 *
 */ 
function detailsPoint(uid) {

	for(i=0; i<scene.children.length; i++) {
		if(scene.children[i].name == "historyPoint" && scene.children[i].uid == uid && scene.children[i].active == false) {
			
			if (DEBUG) console.log("Activating :: " + uid);
			
			scene.children[i].active = true;
			scene.children[i].material.color.setHex(activeColor);
									
			// HTML-Content
			var url		= 'ajax/ajax.php';
			var element	= document.createElement('iframe')
			element.src	= url
			element.style.border	= 'none'
			
			$( "#content").hide();
			$( "#content" ).load( "ajax/ajax.php?uid=" + uid );
			$( "#content").show(1000);
			
		}
	}
}


/** 
 *
 * onWindowResize()
 * 
 * Fenstergröße umsetzen
 *
 */	
function onWindowResize() {
	camera.aspect = window.innerWidth / window.innerHeight;
	camera.updateProjectionMatrix();
	renderer.setSize( window.innerWidth, window.innerHeight );
}

/** 
 *
 * onDocumentMouseMove(event)
 * 
 * Mausbewegung tracken und Mausposition ermitteln
 *
 */	
function onDocumentMouseMove( event ) {
	firstMouseMove = true;
	event.preventDefault();
	mouse.x = ( event.clientX / window.innerWidth ) * 2 - 1;
	mouse.y = - ( event.clientY / window.innerHeight ) * 2 + 1;
}


/** 
 *
 * onDocumentMouseClick(event)
 * 
 * Mausbewegung tracken und Mausposition ermitteln
 *
 */	
function onDocumentMouseClick( event ) {
	
	if (!holdInfo) {
		holdInfo = true; 
	}
	else {
		holdInfo = false;
	}
}

/** 
 *
 * displayNearest(position)
 * 
 * Ermitteln die zur Kameraposition nahegelegenden HistoryPoints
 *
 * @param	position	object	Cameraposition
 *
 */	
function displayNearest(position) {
	
	// Alle in der Nähe ...
	var imagePositionsInRange = kdtree.nearest([position.x, position.y, position.z], 100, maxDistance);
		
	// ... durchgehen
	for ( ki = 0, kil = imagePositionsInRange.length; ki < kil; ki ++ ) {
		
		var object      = imagePositionsInRange[ki];
		var objectPoint = new THREE.Vector3().fromArray( object[ 0 ].obj );
		var objectDist  = object[1];
		
		var objName = parseInt(objectPoint.x) + "/" + parseInt(objectPoint.y) + "/" + parseInt(objectPoint.z); 
		
		// Ist HistoryPoint in Aktivierungsnähe?
		if (objectDist < activateDistance ){
			activatePoint(objName);
		}
		else {
			deactivatePoint(objName);
		}
	}
}


function showPointDetails(intersection) {

	$.ajax({
	  method: "POST",
	  url: "ajax/pointDetailsOnScreen.php",
	  data: { uid: intersection.uid, kat: intersection.kat },
	  dataType: "JSON"
	})
	  .done(function( msg ) {
	  
	  	// Fläche (z=0)
	    var geometry  = new THREE.BoxBufferGeometry( 450,230,0 );

	    var map = new THREE.TextureLoader().load( 'admin/files/personen/' + msg.pic );
		map.wrapS = map.wrapT = THREE.RepeatWrapping;
		map.anisotropy = 16;;

		var material = new THREE.MeshPhongMaterial( { map: map} );

		activeDisplay = new THREE.Mesh(geometry, material);

	    activeDisplay.position.x = intersection.position.x+0;
	    activeDisplay.position.y = intersection.position.y+150;
	    activeDisplay.position.z = intersection.position.z-100;
	    
	    activeDisplay.historyType 	= "activeDisplay";
	    
	    activeDisplay.lookAt(camera.position);
	    scene.add(activeDisplay);

	    var material = new THREE.LineBasicMaterial({color: connectColor[0]});
		var geometry = new THREE.Geometry();
		
		geometry.vertices.push(new THREE.Vector3(intersection.position.x, intersection.position.y,    intersection.position.z));
		geometry.vertices.push(new THREE.Vector3(intersection.position.x, intersection.position.y+150, intersection.position.z-100));
		
		var line = new THREE.Line(geometry, material);
		
		//scene.add(line);
		//lines.push(line.id);	
	    
	    activeDisplay.name = "activeDisplay" +"/" + parseInt(intersection.position.x) + "/" + parseInt(intersection.position.y) + "/" + parseInt(intersection.position.z);
	    //activeDisplay.line = line;
	    activeDisplay.intersection = intersection;

	    //camLookAt = [intersection.position.x,intersection.position.y,intersection.position.z]
	    
	    console.log(activeDisplay);

	  }); 
}


/** 
 *
 * animate()
 * 
 * Szene & Renderer starten und animieren
 *
 * Berühung zwischen Maus-Zeiger und Objekt registrieren und daraufhin
 * Zusatzinfos (#content) einblenden bzw. wieder ausblenden
 *
 */	
function animate() {
	
	// Rekursiv-Aufruf
	requestAnimationFrame( animate );	
	
	// Kamera-Fahrten
	TWEEN.update();
	if (flyMode) {
		camera.lookAt(new THREE.Vector3(camLookAt[0], camLookAt[1],camLookAt[2]));
	}
	
	// HistoryPoints in Kamera-Umgebung aktivieren
	displayNearest(camera.position);
	
	// Kamera - Steuerung
	controls.update( clock.getDelta() );

	$('#campos').text("CY " + rposXYZ(camera.position));
	
	// Mauszeiger auf HistoryPoints?
    raycaster.setFromCamera( mouse, camera );
    
    if (rotate) {
    	rotate.rotation.y += 0.005;
    	rotate.rotation.z += 0.008;
    	//camera.lookAt(activeDisplay.intersection.position.x,activeDisplay.inersection.position.y,activeDisplay.intersection.position.z);
   	}
	
	
	/**
	 *
	 * Intersection
	 *
	 * Objekte mit Mauszeiger / Mausposition geschnitten?
	 *
	 */
	
	if (firstMouseMove) {
	
		var intersections = raycaster.intersectObjects( scene.children );
			
		if ( intersections.length > 0 ) {
			
			if ( intersected != intersections[ 0 ].object && intersections[ 0 ].object.historyType == "historyPoint" ) {
			
				if ( intersected ) intersected.material.color.setHex( color );
				
				intersected = intersections[ 0 ].object;
				intersected.material.color.setHex( activeColor );
				intersected.lookAt(camera.position);
				
				rotate = intersected;
				showPointDetails(intersected);
				document.body.style.cursor = 'pointer';
				
				
				/**
				 *
				 * HistoryConnects (Verbindungen) einlesen
				 *
				 */
				
				$.ajax({
				    url: "ajax/getConnects.php",
				    method: "POST",
				    async: false,
				    data: {uid : intersected.historyDBID, kat : intersected.historyKat},
				    success: function(msg) {
				        
				        var con = jQuery.parseJSON(msg);       
				        var i   = 1;
						
						connects = [];
						vitaConnects = [];
						lines    = [];
						 
				        $.each(con,function(key, val) {
					        var co = new Array(val[0], val[1], val[2], val[3]);
							connects[i] = co;
							i++;
				        });
				        
				        anzahlConnects = connects.length;
				        drawHistoryConnects();
				       
				        
				    }
				});
			
			}
			
		}
		
		else if ( intersected ) {
			intersected.material.color.setHex( color );
			intersected = null;
			document.body.style.cursor = 'auto';

			
			if (!holdInfo) {
				$("#content").hide(1000);
				$("ul.bxslider").remove('li');
				$.each(lines, function(i,e){scene.remove(scene.getObjectById(e));});
				
				// ActiveDisplay entfernen
				scene.remove(activeDisplay)
				//scene.remove(activeDisplay.line)


			}
		}
		
	}
	
	// Szene darstellen / rendern
	renderer.render( scene, camera );
	
	
}

</script>

</body>
</html>