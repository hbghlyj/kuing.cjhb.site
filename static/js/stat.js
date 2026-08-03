function drawstatchart(url, height, titleOption, obj) {
	obj = obj || $('statchart');


	height = height || 400;

	var x = new Ajax('JSON');
	obj.style.width = '100%';
	obj.style.height = height + 'px';
	x.get(url, function (xdata) {
		var myChart = echarts.init(obj);
		option = {
			grid: { left: 60, right: 20, top: 20 },
			xAxis: { type: 'category', data: [] },
			yAxis: { type: 'value' },
			tooltip: { trigger: 'axis', textStyle: { fontSize: 12 } },
			series: [],
			legend: { type: 'scroll', data: [], left: 60, bottom: 10 },
		};
		if(titleOption) {
			option.title = titleOption;
		}
		var reax = xdata.xaxis;
		if (!reax.length) {
			option['title'] = {
				text: 'There is no data for selected period', padding: [10, 50],
				textAlign: 'center', textVerticalAlign: 'center',
				left: '50%', top: '50%', backgroundColor: '#e8f0f7'
			};
		}
		for (var i = 0; i < reax.length; i++) {
			option.xAxis.data.push(reax[i]);
		}
		for (var i = 0, q = xdata.graphs; i < q.length; i++) {
			qttl = q[i].title;
			option.legend.data.push(qttl);
			qdata = {
				type: 'line',
				smooth: true,
				name: qttl,
				data: []
			};
			qdata.data = q[i].data.map(function (value) { return parseInt(value, 10); });
			option.series.push(qdata);

		}
		myChart.setOption(option);
	});
}
