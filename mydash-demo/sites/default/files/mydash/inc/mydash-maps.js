var period= $('#p').val();
var periodMap = mapdata[period];
var map;
var hitWord = {
    1 : "download",
    2 : "preview",
    3 : "visitor"
};

AmCharts.ready(function() {
    map = new AmCharts.AmMap();
    map.pathToImages = "sites/default/files/mydash/assets/ammap/ammap/images/";
    //map.panEventsEnabled = true; // this line enables pinch-zooming and dragging on touch devices
    
    var dataProvider = {
	mapVar: AmCharts.maps.worldLow,
    };
    
    map.areasSettings = {
	unlistedAreasColor: "#DDDDDD",
	rollOverOutlineColor: "#FFFFFF",
	//rollOverColor: "#CC0000",
	rollOverColor: "#F8D400",
	balloonText: "[[title]]: [[customData]]",
	color: "#E0FFD4",
	colorSolid: "#267114"
    };
    
    dataProvider.areas = [];
    
    var countriesSeen = {};
    
    var maxHits = 0;
    for (key in periodMap) {
	if (periodMap[key] > maxHits) {
	    maxHits = periodMap[key];
	}
    }
    
    for (key in periodMap) {
	dataProvider.areas.push({
            title: countrycodes[key],
            id: key,
	    value: Math.log(periodMap[key]),
            customData: periodMap[key] + ' ' + hitWord[hitkind] + (periodMap[key] > 1 ? 's' : '')});
	countriesSeen[key] = 1;
    };

    for (key in countrycodes) {
	if (!countriesSeen[key]) {
            dataProvider.areas.push({title: countrycodes[key], id: key, color: "#DDDDDD", customData: "No " + hitWord[hitkind] + "s"});
	}
    }

    map.colorSteps = 20; //default is 5 -- which is better?
    map.dataProvider = dataProvider;
    
    var valueLegend = new AmCharts.ValueLegend();
    valueLegend.right = 10;
    valueLegend.minValue = 1;
    valueLegend.maxValue = maxHits;
    map.valueLegend = valueLegend;
    
    map.write("map_canvas");

});

