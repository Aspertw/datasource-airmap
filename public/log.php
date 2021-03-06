<?php 
	$group = str_replace('/log/', '', $_SERVER['REQUEST_URI']);
	if(!strlen($group)){ die("Parameter not correct"); }

	$titleMapping = [
		'lass' => 'LASS',
		'lass-4u' => 'LASS 4U',
		'lass-maps' => 'LASS MAPS',
		'edimax-airbox' => 'Edimax Airbox',
	];
?>
<!DOCTYPE html>
<html lang="">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

		<title>g0v空汙地圖資料來源管理::站點增減紀錄</title>
		<link rel='shortcut icon' type='image/x-icon' href='https://i.imgur.com/Gro4juQ.png' />
		<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.6/css/bootstrap.min.css">
		<!--[if lt IE 9]>
			<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.2/html5shiv.min.js"></script>
			<script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
		<![endif]-->
		<style>
			.loading{ 
				background-color: rgba(0,0,0,0.4);
				width: 100%;
				height: 100%;   
			    position: absolute;
			    top: 0;
			    left: 0;
			    z-index: 99;
			    display: none;
			}
				.spinner {	 
					position:absolute; 
					top:50%; 
					left:50%;
					margin-left:-25px;
					margin-top:-25px;
					height:50px;
				}
					.spinner > div {
					  background-color: #eee;
					  height: 100%;
					  width: 6px;
					  display: inline-block;
					  
					  -webkit-animation: sk-stretchdelay 1.2s infinite ease-in-out;
					  animation: sk-stretchdelay 1.2s infinite ease-in-out;
					}
					.spinner .rect2 {
					  -webkit-animation-delay: -1.1s;
					  animation-delay: -1.1s;
					}
					.spinner .rect3 {
					  -webkit-animation-delay: -1.0s;
					  animation-delay: -1.0s;
					}
					.spinner .rect4 {
					  -webkit-animation-delay: -0.9s;
					  animation-delay: -0.9s;
					}
					.spinner .rect5 {
					  -webkit-animation-delay: -0.8s;
					  animation-delay: -0.8s;
					}
					@-webkit-keyframes sk-stretchdelay {
					  0%, 40%, 100% { -webkit-transform: scaleY(0.4) }  
					  20% { -webkit-transform: scaleY(1.0) }
					}

					@keyframes sk-stretchdelay {
					  0%, 40%, 100% { 
					    transform: scaleY(0.4);
					    -webkit-transform: scaleY(0.4);
					  }  20% { 
					    transform: scaleY(1.0);
					    -webkit-transform: scaleY(1.0);
					  }
					}

				.loading .msg {
					position:absolute; 
					top:60%; 
					margin-top:-25px;
					height:50px;
					text-align: center;
					color: #eee;
					width: 100%;
					font-size: 1.2em;
					margin-left: -5px;
				}

			.line-chart { height: 300px; }
		</style>
	</head>
	<body>
		<div class="container">
			<div class="row">
				
			</div>

			<div class="row">
				<h1>Last 12 Hours Fetch Log :: <?=$titleMapping[$group]?></h1>
				
				<h3>Site Count</h3>
				<div class="line-chart" id="chart"></div>
				<hr>

				<h3>Device Difference with Previous Fetch</h3>
				<table class="table table-striped diff-table" id="table">
					<thead>
						<tr>
							<th>Local Time</th>
							<th>Count</th>
							<th>Site Added</th>
							<th>Site Removed</th>
						</tr>
					</thead>
					<tbody></tbody>
				</div>

				<div class="loading">
					<div class="spinner">
						<div class="rect1"></div>
						<div class="rect2"></div>
						<div class="rect3"></div>
						<div class="rect4"></div>
						<div class="rect5"></div>
					</div>
				</div>
			</div>
		</div>

		

		<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

		<script src="https://www.gstatic.com/charts/loader.js"></script>
		<script>
			var TZtoLocal = function(TZ){
				var dd = new Date(TZ);
				var leftPadding = function(no){
					return no >= 10 ? no : '0'+no;
				};

				return [
					dd.getFullYear(), 
					leftPadding(dd.getMonth()+1), 
					leftPadding(dd.getDate()),
				].join('/') + ' ' + [
					leftPadding(dd.getHours()),
					leftPadding(dd.getMinutes()),
					leftPadding(dd.getSeconds()),
				].join(':');
			};

			var findTotalCount = function(count, TZ){
				if(!count || !TZ){ return null; }

				for(var i in count){
					if( count[i].utc.slice(0, -4) == TZ.slice(0, -4) ){
						return count[i].total;
					}
				}
				return null;
			};

			var getDataTable = function(data){
				if(!data){ return null; }

				var chartRows = [];
				data.map(function(record){
					chartRows.push([
						new Date(record.utc),
						record.total,
					]);
				});

				var dataTable = new google.visualization.DataTable();
					dataTable.addColumn('datetime', 'Time');
					dataTable.addColumn('number', 'Total');
					dataTable.addRows(chartRows);

				return dataTable;
			};

			var getList = function(data){
				if(!data){ return null; }

				var tbody = [];
				data.diff.map(function(row){
					tbody.push([
						'<tr>',
							'<td>' + TZtoLocal(row.utc) + '</td>',
							'<td>' + findTotalCount(data.count, row.utc) + '</td>',
							'<td>' + row.add + '</td>',
							'<td>' + row.remove + '</td>',
						'</tr>',
					].join(''));
				});

				return tbody.join('');
			}

			

			var initChart = function(chartContainer, dataTable){
				var chartOptions = {
					chartArea: { top:20, left:45, width: '90%', height: '80%' },
					legend: { position: 'none'},
					fontSize: 14,
					fontName: "Verdana",
					lineWidth: 2,
					pointSize: 4,
					hAxis: { 
						gridlines: { color: "#fff" },
					},
					vAxis: { gridlines: { color: "#eee" } },
					explorer: {
						keepInBounds: true,
						maxZoomOut: 1,
					},
				};

				var container = document.getElementById(chartContainer);
				var chart = new google.visualization.LineChart(container);
				chart.draw(dataTable, chartOptions);

				return chart;
			}

			var loadChart = function(){
				$loading = $(".loading").show();

				var chartContainer = "chart";
				var $tableContainer = $("#table");
				var resource = '/<?=$group;?>-log.json';

				$.getJSON(resource).then(function(data){
					//list
					var tbody = getList(data);
					if(tbody !== null){
						$tableContainer.find("tbody").html(tbody);
					}

					//datatable
					var dataTable = getDataTable(data.count);
					var chart = initChart(chartContainer, dataTable);
					

					google.visualization.events.addListener(chart, 'select', function(){
						var selection = chart.getSelection();
						var item = selection.shift();

						if(item){ //seleted
							var TZ = dataTable.getValue(item.row, 0);
							var timeStr = TZtoLocal(TZ);

							$tableContainer.find("tbody tr").hide()
								.filter(":contains('" + timeStr.slice(0, -3) + "')").show();
						}else{	//unseleted
							$tableContainer.find("tbody tr").show();
						}
					});

					$loading.hide();
				});
			};

			google.charts.load('current', {'packages':['corechart']});
			google.charts.setOnLoadCallback(loadChart);
		</script>
	</body>
</html>
	