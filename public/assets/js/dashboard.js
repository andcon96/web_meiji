( function ( $ ) {
    "use strict";

const brandSuccess = '#05B947'
const brandInfo = '#2A8DFF'
const brandDanger = '#F32929'

function convertHex (hex, opacity) {
  hex = hex.replace('#', '')
  const r = parseInt(hex.substring(0, 2), 16)
  const g = parseInt(hex.substring(2, 4), 16)
  const b = parseInt(hex.substring(4, 6), 16)

  const result = 'rgba(' + r + ',' + g + ',' + b + ',' + opacity / 100 + ')'
  return result
}

function random (min, max) {
  return Math.floor(Math.random() * (max - min + 1) + min)
}

    var elements = 3
    var data1 = [40,30,100]
    var data2 = []
    var data3 = []

    //var labeldata = ['Jan','Feb','Mar','Apr']

    $('input[type=radio][name=options]').change(function() {
        // ketika option berubah ubah chart
        let searchby = this.value;

        for (var i = 0; i <= elements; i++) {
          data1.push(random(50, 200))
          data2.push(random(80, 100))
          data3.push(65)
        }

        if(searchby == '1'){ // Sales Customer
          let total = myChart.data.datasets.length;
          myChart.data.labels = topSalesName;
          myChart.data.datasets[0].data = topSales;

          while (total > 1) {
              myChart.data.datasets.pop();
              total--;
          }

        }else if(searchby == '2'){ // Sales Item
          let total = myChart.data.datasets.length;
          myChart.data.labels = topItemName;
          myChart.data.datasets[0].data = topItem;

          while (total > 1) {
              myChart.data.datasets.pop();
              total--;
          }

        }else if(searchby == '3'){ // Sales Region
          let total = myChart.data.datasets.length;
          myChart.data.labels = topRegionName;
          myChart.data.datasets[0].data = topRegion;
          
          while (total > 1) {
              myChart.data.datasets.pop();
              total--;
          }

        }else if(searchby == '4'){ // Sales Total
          myChart.data.labels = ['≥0','≥15','≥30','≥60','≥90'];
          myChart.data.datasets[0].data = topYear;
          var newDataSet = {
            backgroundColor: '#97CAAA',
            borderColor: brandSuccess,
            pointHoverBackgroundColor: '#fff',
            borderWidth: 2,
            data: topYearPrev,
          }

          myChart.data.datasets.push(newDataSet);
        }

        myChart.update();
    });

    for (var i = 0; i <= elements; i++) {
      data1.push(random(50, 200))
      data2.push(random(80, 100))
      data3.push(65)
    }


    var ctx = document.getElementById( "trafficChart" );
    var myChart = new Chart( ctx, {
        type: 'bar',
        data: {
            labels: topSalesName,
            datasets: [
            {
              label: 'Total Sales',
              backgroundColor: '#90C4FF',
              borderColor: brandInfo,
              pointHoverBackgroundColor: '#fff',
              borderWidth: 2,
              data: topSales
          }
          ]
        },
        options: {
            maintainAspectRatio: true,
            legend: {
                display: false
            },
            responsive: true,
            scales: {
                xAxes: [{
                  gridLines: {
                    drawOnChartArea: false
                  }
                }],
                yAxes: [ {
                      ticks: {
                        beginAtZero: true,
                        maxTicksLimit: 5,
                        callback: function(label, index, labels) {
                          return label.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
                        }
                      },
                      gridLines: {
                        display: true
                      }
                } ]
            },
            elements: {
                point: {
                  radius: 0,
                  hitRadius: 10,
                  hoverRadius: 4,
                  hoverBorderWidth: 3
              }
            },tooltips: {
              callbacks: {
                  label: function(tooltipItem, data) {
                      return tooltipItem.yLabel.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
                  }
              }
            }


        }
    } );

} )( jQuery );