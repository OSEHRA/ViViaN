<!DOCTYPE html>
<html>
  <title>VistA Menus</title>
  <head>
    <?php
      include_once "vivian_tree_layout.css";
      include_once "vivian_common.php";
    ?>
    <!-- JQuery Buttons -->
    <script>
      var vivianDataPath = FILES_URL;
      var pathDict = {
                      "#19": {"prefix":"",
                              "fileLoc":vivianDataPath + "menus/19",
                              "sourceLoc":vivianDataPath + "19/19-",
                              "initFile":"9",
                              "desc":"This page is displaying the menu entries from the OPTION file"},
                      "#101":{"prefix":"protocol_",
                              "fileLoc":vivianDataPath + "menus/101",
                              "sourceLoc":vivianDataPath + "101/101-",
                              "initFile":"2590",
                              "desc":"This page is displaying the menu entries from the PROTOCOL file"}
      }
      $(window).on('hashchange', function() {
        location.reload();
      });
      [hash,nameVal] = location.hash.split("?")
      if (hash != "#19" && hash != "#101" ) {
         hash="#19"
      }
      $(function() {
        $( "button" ).button().click(function(event){
          event.preventDefault();
        });
        fileName = window.location.href.substring(window.location.href.lastIndexOf('/')+1);
        $('a[href="'+fileName+'"]').parents("#navigation_buttons li").each(function (i) {
            $(this).removeClass().addClass("active");
        });
        d3.json(vivianDataPath + pathDict[hash]["prefix"] + 'menu_autocomplete.json', function(json) {
          var sortedjson = json.sort(function(a,b) { return a.label.localeCompare(b.label); });
          $("#autocomplete").autocomplete({
            source: sortedjson,
            select: autoCompleteChanged
            //change: autoCompleteChanged
          }).data('autocomplete')/*._trigger('select')*/;
        });

        d3.json(vivianDataPath + pathDict[hash]["prefix"] + 'option_autocomplete.json', function(json) {
          var sortedjson = json.sort(function(a,b) { return a.label.localeCompare(b.label); });
          // Note: vivian_tree_layout_common expects this control
          // to be called 'option_autocomplete'.
          $("#option_autocomplete").autocomplete({
            source: sortedjson,
            select: optionAutoCompleteChanged,
            //*****************************************************
            // Adapted from http://jsfiddle.net/chukhanhvan/7HQym/
            //*****************************************************
            focus: function(event,ui) {

              $(".ui-autocomplete .selecting").html(title);
              $(".ui-autocomplete .selecting").removeClass('selecting');

              // save the original title
              title = ui.item.label;
              q = $(".ui-state-focus").html();
              $(".ui-state-focus").html("<b>Top Level Menu: "+ui.item.parent_name +"</b>");

              // add class selecting to mark the item
              $(".ui-state-focus").addClass('selecting');
             //*******************************************************
            }
          }).data('autocomplete')/*._trigger('select')*/;
          if(nameVal !== undefined) {
            $("#option_autocomplete").val(decodeURI(nameVal.substr(5)).replace(/[+]/g, ' '))
            $("#option_autocomplete").autocomplete('search');
            $(".ui-menu-item")[0].click()
          } else {
              resetMenuFile(pathDict[hash]["fileLoc"]+"/VistAMenu-"+pathDict[hash]["initFile"]+".json");
          }
        });
      });
    </script>

    <style>
      .ui-autocomplete {
          max-height: 400px;
          font-size: 0.9em;
          overflow-y: auto;   /* prevent horizontal scrollbar */
          overflow-x: hidden; /* add padding to account for vertical scrollbar */
          z-index:1000 !important;
      }
    </style>
  </head>

  <body>

  <!-- Tooltip -->
  <div id="toolTip" class="tooltip" style="opacity:0;">
      <div id="head" class="header"></div>
      <div  class="tooltipTail"></div>
  </div>

  <div class='hint' style="position:relative; left:20px;">
    <p>
    This tree visualization represents the menu hierarchy of VistA. Hover over
    any of the entries in the tree to see the menu entry name and the security
    key (if any). Click on an item to see the menu entry details.
    </p>
  </div>
  <h4 id="pageDesc" style="position:relative; left:20px;"></h4>

<div id="legend_placeholder" style="position:relative; left:20px;"></div>
<div style="position:relative; left:20px; width:400px;">
  <div id="packageSearch">
    <div><label title="Show the structure of a top level menu by entering the name of the entry."
                for="autocomplete">Select a top level menu:</label></div>
    <div><input id="autocomplete" size="40"></div>
  </div>
  </br>
  <div>
    <div><label title="Search for an entry by entering the name of the entry that you wish to find."
                for="option_autocomplete">Search for an entry:</label></div>
    <div><input id="option_autocomplete" size="40"></div>
    <div id="search_result"> </div>
  </div>
  <div id="buttons" style="position:relative; top:10px;">
    <button onclick="_collapseAllNode()">Collapse All</button>
    <button onclick="_resetAllNode()">Reset</button>
    <button onclick="_centerDisplay()">Center</button>
  </div>
</div>

<div id="treeview_placeholder"></div>

<script type="text/javascript">

<?php include_once "vivian_tree_layout_common.js" ?>

// Note: vivian_tree_layout_common expects this variable
// to be called 'chart'.
var chart = d3.chart.treeview()
              .height(1050)
              .width(1280*2)
              .margins({top: 0, left: 200, bottom: 0, right: 0})
              .textwidth(300)
              .pannableTree(true)
              .nodeTextHyperLink(function(d) { if (d.hasLink) return getOptionDetailLink(d);});
var legendShapeChart = d3.chart.treeview()
              .height(50)
              .width(250)
              .margins({top:10, left:10, right:0, bottom:0})
              .textwidth(110);
var legendTypeChart = d3.chart.treeview()
              .height(50)
              .width(1300)
              .margins({top:10, left:10, right:0, bottom:0})
              .textwidth(110);

var shapeLegend = [{name: "Menu", shape: "triangle-up"},
                   {name: "Entry", shape:"circle"}]
d3.select("#pageDesc").text(d3.select("#pageDesc").text() + pathDict[hash]['desc'] )
chart.on("text","attr","fill",color_by_type);
var originalTransform = [300,0];
var selectedIndex=0;

var target_option='';

var menuType = [
  {iName: "legend",color: "black",dName: "All Types"},
  {iName: "menu",color :"gray",dName: "Menu"},
  {iName: "run routine",color :"#ff7f0e",dName: "Run Routine"},
  {iName: "Broker (Client/Server)" , color : "#17becf", dName: "Broker"},
  {iName: "edit",color :"#2ca02c",dName: "Edit"},
  {iName: "server",color :"#d62728",dName: "Server"},
  {iName: "print",color :"#9467bd",dName: "Print"},
  {iName: "action",color :"#8c564b",dName: "Action"},
  {iName: "ScreenMan",color :"#e377c2",dName: "ScreenMan"},
  {iName: "inquire" , color : "#bcbd22",dName: "Inquire" },
  {iName: "extended action" , color : "cadetblue",dName: "Extended Action" }
];

function color_by_type(node) {
  if(node) {
    if(node.type == undefined){
      return node.color
    };
    for(var i=0;i<menuType.length; i++) {
      if  (node.type == menuType[i].iName) {
        return menuType[i].color;
      }
    }
    return "#E0E0E0";
  }
}
// Updates for pannable tree
function _centerDisplay() {
  chart.centerDisplay();
}

function _resetAllNode() {
  resetAllNode(chart.nodes());
  _centerDisplay();
  chart.update(chart.nodes());
}
// end updates

function color_filter(d) {
  if(d) {
    if(d.type == undefined){
      return d.color
    };
    if (d.type != menuType[selectedIndex].iName) {
      return "#E0E0E0";
    }
    else {
      return menuType[selectedIndex].color;
    };
  }
}

function autoCompleteChanged(eve, ui) {
  var menuFile = pathDict[hash]["fileLoc"]+"/VistAMenu-" + ui.item.id + ".json";
  resetMenuFile(menuFile);
}

function optionAutoCompleteChanged(eve, ui) {
  var menuFile = pathDict[hash]["fileLoc"]+"/VistAMenu-" + ui.item.parent_id + ".json";
  d3.json(vivianDataPath + 'menu_autocomplete.json', function(json) {
    for ( var i = 0; i < json.length; i++) {
      if( json[i].id == ui.item.parent_id) {
      $("#autocomplete")[0].value = json[i].label;
      break;
      }
    }
  });
  target_option = $.trim(ui.item.value.split(":")[1]);
  resetMenuFile(menuFile);
}

function resetMenuFile(menuFile) {
  d3.json(menuFile, function(json) {
    chart.on("node", "event", "mouseover", node_onMouseOver)
       .on("node", "event","mouseout", node_onMouseOut)
       .on("text", "attr", "cursor", function(d) { return "pointer"; })
       //.on("text", "event","click", node_onMouseClick)
       .on("path", "event", "click", node_onMouseClick)
       .on("path", "attr", "r", function(d) { return 7 - d.depth/2; });
    d3.select("#treeview_placeholder").datum(json).call(chart);
    d3.select("#legend_placeholder").datum(null).call(legendShapeChart);
    d3.select("#legend_placeholder").datum(null).call(legendTypeChart);
    createShapeLegend();
    createLegend();
    chart.svg().attr("transform","translate("+originalTransform+")")
    _resetAllNode();

    if(target_option != '') {
      openSpecificNode(target_option, chart.nodes());
      setTimeout(highlightPath,300,chart);
    }
    else {
      clearAutocomplete();
    }
  });
}

var toolTip = d3.select(document.getElementById("toolTip"));
var header = d3.select(document.getElementById("head"));

function node_onMouseClick(d) {
  if (d.depth == 0) {
    clearAutocomplete();
  }
  chart.onNodeClick(d);
  if(selectedIndex !== 0){
    d3.selectAll("text")
      .attr("fill", function (d) {
        return color_filter(d);
      });
  }
}

function getOptionDetailLink(node) {
  return pathDict[hash]["sourceLoc"]+ node.ien + ".html";
}

function node_onMouseOver(d) {
  var headText = "Entry Name: " + d.option;
  if (d.lock !== undefined){
    headText = headText + "<br>" + "Security Key: " + d.lock + "</br>";
  }
  header.html(headText);
  $( document ).uitooltip('option', 'content', $("#toolTip").html())
}

function node_onMouseOut(d) {
}

function createLegend() {
  var legend = legendTypeChart.svg().selectAll("g.legend")
    .data(menuType)
    .enter().append("svg:g")
    .attr("class", "legend")
    .attr("transform", function(d, i) { return "translate(" + (i * 110) +",30)"; })
    .on("click", function(d) {
      selectedIndex = menuType.indexOf(d);
      if(selectedIndex !== 0){
        d3.selectAll("text")
          .attr("fill", function (d) {
            return color_filter(d);
          });
      }
      else {
        d3.selectAll("text")
          .attr("fill", function (d) {
            return color_by_type(d);
          });
      }
    });

  legend.append("svg:text")
    .attr("x", 13)
    .attr("dy", ".31em")
    .attr("fill",function(d) {return d.color;})
    .text(function(d) {return  d.dName; });

  var legend = legendTypeChart.svg()
  legend.append("text")
          .attr("x", 0)
          .attr("y", 10 )
          .attr("text-anchor", "left")
          .style("font-size", "16px")
          .text("Option Type Filter Menu");
}

function createShapeLegend() {
  var shapeLegendDisplay = legendShapeChart.svg().selectAll("g.shapeLegend")
      .data(shapeLegend)
      .enter().append("svg:g")
      .attr("class", "shapeLegend")
      .attr("transform", function(d, i) { return "translate("+(i * 150) +", 25)"; });

  shapeLegendDisplay.append("path")
      .attr("class", function(d) {return d.name;})
      .attr("d", d3.svg.symbol().type(function(d) { return d.shape;}))
      .attr("r", 3);

  shapeLegendDisplay.append("svg:text")
      .attr("class", function(d) {return d.name;})
      .attr("x", 13)
      .attr("dy", ".31em")
      .text(function(d) {
        return  d.name;
      });

  var shapeLegendDisplay = legendShapeChart.svg();
  shapeLegendDisplay.append("text")
          .attr("x", 0)
          .attr("y", 10 )
          .attr("text-anchor", "left")
          .style("font-size", "16px")
          .text("Shape Legend");
}

    </script>
    </div>
  </body>
</html>
