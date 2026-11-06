import ApexCharts from 'apexcharts';

const timelineDiv = document.querySelector('#timelineChart');

if (timelineDiv) {
  const rawData = JSON.parse(timelineDiv.dataset.series); // [{ x: '06-2025', y: 2 }, ...]
  var seriesData = [];
  var categoriesData = [];
  rawData.forEach(item => {
    seriesData.push(item.y);
    categoriesData.push(item.x);
  });
  var options = {
    series: [
      {
        name: window.cmsAdminTranslations.contact_count,
        data: seriesData
      }
    ],
    chart: {
      type: 'area',
      stacked: false,
      height: 350,
      zoom: {
        type: 'x',
        enabled: false,
        autoScaleYaxis: true
      },
      toolbar: {
        autoSelected: 'zoom'
      }
    },
    dataLabels: {
      enabled: false
    },
    markers: {
      size: 0
    },
    fill: {
      type: 'gradient',
      gradient: {
        shadeIntensity: 1,
        inverseColors: false,
        opacityFrom: 0.5,
        opacityTo: 0,
        stops: [0, 90, 100]
      }
    },
    xaxis: {
      categories: categoriesData
    }
  };

  //   var chart = new ApexCharts(document.querySelector('#chart'), options);
  //   chart.render();

  new ApexCharts(timelineDiv, options).render();
}
