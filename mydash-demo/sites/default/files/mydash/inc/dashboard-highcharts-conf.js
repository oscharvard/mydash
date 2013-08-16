var chartconfig = function (workflow, collection, container) {
    var chartopts = 
	{
	    chart: {
		renderTo: container,
		zoomType: 'x',
		spacingRight: 20
	    },
	    title: {
		text: 'DASH workflow for ' + collection
	    },
	    subtitle: {
		useHTML: true,
		text: document.ontouchstart === undefined ?
		    '"unavailable" = sum of submitted, batch, and approved<br />Click and drag in the plot area to zoom in;<br />click in the legend below to toggle lines' :
		    '"unavailable" = sum of submitted, batch, and approved<br />Drag your finger over the plot to zoom in;<br />tap in the legend below to toggle lines'
	    },
	    xAxis: {
		type: 'datetime',
		maxZoom: 14 * 24 * 3600000, // fourteen days
		title: {
		    text: null
		}
	    },
	    yAxis: [
		{
		    title: {
			text: 'Items in workflow'
		    },
		    min: 0.6,
		    startOnTick: false,
		    showFirstLabel: true
		}
	    ],
	    tooltip: {
		shared: true					
	    },
	    legend: {
		enabled: true
	    },
	    plotOptions: {
		series: {
		    marker: {      
			enabled: false
		    }
		},
		
		area: {
		    fillColor: {
			linearGradient: [0, 0, 0, 300],
			stops: [
			    [0, '#4572A7'],
			    [1, 'rgba(2,0,0,0)']
			]
		    },
		    lineWidth: 1,
		    marker: {
			enabled: false,
			states: {
			    hover: {
				enabled: true,
				radius: 5
			    }
			}
		    },
		    shadow: false,
		    states: {
			hover: {
			    lineWidth: 1						
			}
		    }
		}
	    },
	    
	    series: []
	};

    // Keep exporting stuff separate in case we want to remove it.  We have to recreate the standard buttons, 
    // according to http://engineering.korrelate.com/2012/01/27/adding-csv-export-to-highcharts/
    chartopts.exporting = {
 	filename: "workflow",
	buttons: {
	    exportButton: {
		menuItems: [
		    {
			textKey: 'downloadPNG',
			onclick: function () {
			    this.exportChart();
			}
		    }, {
			textKey: 'downloadJPEG',
			onclick: function () {
			    this.exportChart({
				type: 'image/jpeg'
			    });
			}
		    }, {
			textKey: 'downloadPDF',
			onclick: function () {
			    this.exportChart({
				type: 'application/pdf'
			    });
			}
		    }, {
			textKey: 'downloadSVG',
			onclick: function () {
			    this.exportChart({
				type: 'image/svg+xml'
			    });
			}
		    }, {
			text: 'Download CSV data',
			onclick: function () {
			    var csv = "Date,";
			    for (var i = 0; i < chart.series.length; i++) {
				var series = chart.series[i];
				csv = csv + series.name;
				if (i < chart.series.length - 1) {
				    csv = csv + ',';
				}
			    }
			    csv = csv + 'CRCR';
			    var lines = chart.series[0].data.length;
			    for (var j = 0; j < lines ; j++) {
				csv = csv + Highcharts.dateFormat('%Y-%m-%d', chart.series[0].data[j].x) + ',';
				for (var i = 0 ; i < chart.series.length ; i++) {
				    csv = csv + chart.series[i].data[j].y;
				    if (i < chart.series.length - 1) {
					csv = csv + ',';
				    }
				}
				csv = csv + 'CRCR';
			    }

			    var form = document.createElement("form");
			    form.setAttribute("method", "post");
			    form.setAttribute("action", "sites/default/files/mydash/inc/getcsv.php");
			    form.setAttribute("target", "_blank");

			    var hiddenField = document.createElement("input");              
			    hiddenField.setAttribute("name", "csv_text");
			    hiddenField.setAttribute("value", encodeURIComponent(csv));
			    form.appendChild(hiddenField);

			    hiddenField = document.createElement("input");              
			    hiddenField.setAttribute("name", "filename");
			    hiddenField.setAttribute("value", "workflow");
			    form.appendChild(hiddenField);

			    document.body.appendChild(form);

			    form.submit();
			}
		    }
		]
	    }
	}
    };

    var startdate = Date.UTC(2008, 07, 06); // means 2008-08-06!
    for (var key in workflow[collection]) {
	if (key != 'unavailable') {
	    chartopts.series.push({
		type: 'line',
		yAxis: 0,
		name: key,
		id: key,
		pointInterval: 24 * 3600 * 1000,
		pointStart: startdate,
		data: workflow[collection][key]
	    });
	} else {
	    chartopts.series.push({
		type: 'area',
		yAxis: 0,
		name: key,
		id: key,
		pointInterval: 24 * 3600 * 1000,
		pointStart: startdate,
		data: workflow[collection][key]
	    });	    
	}
    }
    chartopts.series.push({
        type: 'flags',
	name: 'events',
	visible: false,
        data: [{
	    x: Date.UTC(2011, 1, 14),
	    text: 'Dryest month of the year',
	    title: 'A'
        }, {
	    x: Date.UTC(2012, 1, 14),
	    text: 'Rainiest month of the year',
	    title: 'B'
        }],
    });
    return(chartopts);
}