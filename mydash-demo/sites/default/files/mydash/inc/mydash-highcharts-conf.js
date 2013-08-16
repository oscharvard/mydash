var chartconfig = function (hitkind, include_cumulative) {
    var chartopts = 
	{
	    chart: {
		renderTo: 'container',
		zoomType: 'x',
		spacingRight: 20
	    },
	    title: {
		text: 'Daily ' + hitkind + ' for ' + groupname
	    },
	    subtitle: {
		text: document.ontouchstart === undefined ?
		    'Click and drag in the plot area to zoom in' :
		    'Drag your finger over the plot to zoom in'
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
			text: hitkind
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
		enabled: false
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
	    
	    series: [{
		type: 'area',
		name: hitkind,
		pointInterval: 24 * 3600 * 1000,
		pointStart: startdate,
		data: counts
	    },
		     {
			 type: 'line',
			 name: "Moving average ("+mywindow+"-day)",
			 pointInterval: 24 * 3600 * 1000,
			 pointStart: startdate,
			 data: averages
		     }]
	};

    // Keep exporting stuff separate in case we want to remove it.  We have to recreate the standard buttons, 
    // according to http://engineering.korrelate.com/2012/01/27/adding-csv-export-to-highcharts/
    chartopts.exporting = {
 	filename: hitkind.replace(/ /g, "_"), // is this what we want?
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
			    hiddenField.setAttribute("value", hitkind.replace(/ /g, "_"));
			    form.appendChild(hiddenField);

			    document.body.appendChild(form);

			    form.submit();
			}
		    }
		]
	    }
	}
    };

    if (include_cumulative) {
	chartopts.yAxis.push({
	    gridLineWidth: 0,
	    opposite: true,
	    title: {
		text: "Cumulative",
		style: {
		    color: '#89A54E'
		}
	    },
	    startOnTick: false,
	    showFirstLabel: false
	});
	chartopts.series.push({
	    type: 'line',
	    yAxis: 1,
	    name: "Cumulative total",
	    pointInterval: 24 * 3600 * 1000,
	    pointStart: startdate,
	    data: cumulative
	});
    }
    return(chartopts);
}