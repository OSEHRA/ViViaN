<!DOCTYPE html>
<html>
  <head>
    <?php
      include_once "vivian_common_header.php";
      include_once "vivian_tree_layout.css";
    ?>
    <!-- JQuery Buttons -->
    <script>
      $(function() {
        $( "button" ).button().click(function(event){
          event.preventDefault();
        });
      });
    var btn = $.fn.button.noConflict() // reverts $.fn.button to jqueryui btn
    $.fn.btn = btn // assigns bootstrap button functionality to $.fn.btn
    </script>
    <?php include_once "vivian_google_analytics.php" ?>
  </head>

<body >
  <div>
    <?php include_once "vivian_osehra_image.php" ?>
    <!-- <select id="category"></select> -->
    <div style="font-size:10px; position:absolute; right:100px; top:30px;">
      <button onclick="expandAllNode()">Expand All</button>
      <button onclick="collapseAllNode()">Collapse All</button>
      <button onclick="resetAllNode()">Reset</button>
    </div>
  </div>
  <!-- Tooltip -->
<div id="toolTip" class="tooltip" style="opacity:0;">
    <div id="header1" class="header"></div>
    <div  class="tooltipTail"></div>
</div>

<div id="body" style="position: absolute; left:80px; top:50px">
</div>
<div id="dialog-modal">
  <div id='namespaces' style="display:none"></div>
  <div id='dependencies' style="display:none"></div>
  <div id="accordion">
      <h3><a href="#">Interfaces</a></h3>
      <div id="interface"></div>
      <h3><a href="#">Description</a></h3>
      <div id="description"></div>
  </div>
</div>
<script type="text/javascript">
$("#accordion").accordion({heightStyle: 'content', collapsible: true}).hide();
var m = [0, 120, 0, 120],
    w = 1280 - m[1] - m[3],
    h = 1200 - m[0] - m[2],
    i = 0,
    root;

var selectedIndex = 0;
var catcolors = ["black", "#FF0000", "#3300CC", "#080", "#FF00FF", "#660000"];

var tree = d3.layout.tree()
    .size([h, w]);

var diagonal = d3.svg.diagonal()
    .projection(function(d) { return [d.y, d.x]; });

var vis = d3.select("#body").append("svg:svg")
    .attr("width", w + m[1] + m[3])
    .attr("height", h + m[0] + m[2])
  .append("svg:g")
    .attr("transform", "translate(" + m[3] + "," + m[0] + ")");

d3.json("packages.json", function(json) {
  root = json;
  root.x0 = h / 2;
  root.y0 = 0;

  resetAllNode();
});

<?php include_once "vivian_tree_layout_common.js" ?>
var package_link_url = "http://code.osehra.org/dox/Package_";
var toolTip = d3.select(document.getElementById("toolTip"));
var header = d3.select(document.getElementById("header1"));

var sddesc = "<p>The VistA Scheduling package allows a user to Schedule appointments for" +
" the following types of appointments:" +
"<ul><li>Scheduled</li>" +
"<li>C and P</li>" +
"<li>Collateral</li></ul>" +
" It also allows entry of an unscheduled appointment at any time during a day" +
" on which the clinic being scheduled into meets.  From these appointments," +
" various output reports are produced such as, but not limited to, file room" +
" list, appointment list, routing slips, letters for cancellations, no-shows," +
" and pre-appointment.  There is an additional capability where additional" +
" clinic stop credits can be directly entered and associated with a particular" +
" patient and date.  AMIS reporting is handled via a set of extract routines" +
" that summarize the data found by reading through the appointments and" +
" additional clinic stops and the 10/10 and unscheduled visits (outpatient" +
" credit given to Admitting/Screening) and storing the information by patient" +
" and visit date in the OCP File.  The AMIS 223 report and the OPC" +
" file to be sent to the Austin DPC are generated using this file.</p>";


function update(source) {
  var duration = d3.event && d3.event.altKey ? 5000 : 500;

  // Compute the new tree layout.
  var nodes = tree.nodes(root).reverse();

  // Normalize for fixed-depth.
  nodes.forEach(function(d) { d.y = d.depth * 220; });

  // Update the nodes
  var node = vis.selectAll("g.node")
      .data(nodes, function(d) { return d.id || (d.id = ++i); });

  // Enter any new nodes at the parent's previous position.
  var nodeEnter = node.enter().append("svg:g")
      .attr("class", "node")
      .attr("transform", function(d) { return "translate(" + source.y0 + "," + source.x0 + ")"; })
      .on("click", function(d) {
          if (d.hasLink) {
            var overlayDialogObj = {
              autoOpen: true,
              height: 'auto',
              width: 700,
              modal: true,
              position: ["center","center-50"],
              title: "Package: " + d.name,
              open: function(){
                  htmlLnk = getInterfaceHtml(d);
                  $('#interface').html(htmlLnk);
                  $('#namespaces').html(getNamespaceHtml(d.prefixes))
                  $('#namespaces').show();
                  if (d.name === 'Scheduling'){
                    $('#description').html(sddesc);
                  }
                  else{
                    $('#description').html(d.name);
                  }
                  depLink = getDependencyContentHtml(d.name)
                  $('#dependencies').html(depLink);
                  $('#dependencies').show();
                  $('#accordion').accordion("option", "active", 0);
                  $('#accordion').accordion("refresh");
                  $('#accordion').accordion({heightStyle: 'content'}).show();
              },
           };
           $('#dialog-modal').dialog(overlayDialogObj).show();
            // var pkgUrl = package_link_url + d.name.replace(/ /g, '_') + ".html";
            // var win = window.open(pkgUrl, '_black');
            // win.focus();
            return;
          }
          toggle(d);
          update(d);
      })
      .on("mouseover", function(d) {
          if (d.hasLink !== undefined && d.hasLink){
            node_onMouseOver(d);
          }
      })
      .on("mouseout", function(d) {
          header.text("");
          toolTip.transition()
                 .duration(500)
                 .style("opacity", "0");
      });

  nodeEnter.append("svg:circle")
      .attr("r", 1e-6)
      .style("fill", function(d) { return d._children ? "lightsteelblue" : "#fff"; });

  nodeEnter.append("svg:text")
      .attr("x", function(d) { return d.children || d._children ? -10 : 10; })
      .attr("dy", ".35em")
      .attr("text-anchor", function(d) { return d.children || d._children ? "end" : "start"; })
      .text(function(d) { return d.name; })
      .attr("fill", function(node){
        return change_node_color(node)
      })
      .attr("cursor", function(d){ return d.hasLink !== undefined && d.hasLink ? "pointer" : "hand";})
      .style("fill-opacity", 1e-6);
  
  // Transition nodes to their new position.
  var nodeUpdate = node.transition()
      .duration(duration)
      .attr("transform", function(d) { return "translate(" + d.y + "," + d.x + ")"; });

  nodeUpdate.select("circle")
    .attr("r", function(d) {return 7 - d.depth;})
      .style("fill", function(d) { return change_circle_color(d); /* return d._children ? "lightsteelblue" : "#fff"; */ });
  
  nodeUpdate.select("text")
      .attr("fill", function(node){ return change_node_color(node) })
      .style("fill-opacity", 1);

  // Transition exiting nodes to the parent's new position.
  var nodeExit = node.exit().transition()
      .duration(duration)
      .attr("transform", function(d) { return "translate(" + source.y + "," + source.x + ")"; })
      .remove();

  nodeExit.select("circle")
      .attr("r", 1e-6);

  nodeExit.select("text")
      .style("fill-opacity", 1e-6);

  // Update the links
  var link = vis.selectAll("path.link")
      .data(tree.links(nodes), function(d) { return d.target.id; });

  // Enter any new links at the parent's previous position.
  link.enter().insert("svg:path", "g")
      .attr("class", "link")
      .attr("d", function(d) {
        var o = {x: source.x0, y: source.y0};
        return diagonal({source: o, target: o});
      })
    .transition()
      .duration(duration)
      .attr("d", diagonal);

  // Transition links to their new position.
  link.transition()
      .duration(duration)
      .attr("d", diagonal);

  // Transition exiting nodes to the parent's new position.
  link.exit().transition()
      .duration(duration)
      .attr("d", function(d) {
        var o = {x: source.x, y: source.y};
        return diagonal({source: o, target: o});
      })
      .remove();

  // Stash the old positions for transition.
  nodes.forEach(function(d) {
    d.x0 = d.x;
    d.y0 = d.y;
  });
}

function getPackageDoxLink(pkgName) {
  var doxLinkName = pkgName.replace(/ /g, '_').replace(/-/g, '_')
  return package_link_url + doxLinkName + ".html";
}

function getNamespaceHtml(namespace) {
  var i=0, len=namespace.length;
  var htmlLnk = "<h4>Namespaces: </h4>";
  for (; i<len-1; i++) {
    htmlLnk += "&nbsp;" + namespace[i] + ",&nbsp;";
  }
  htmlLnk += "&nbsp;" + namespace[i];
  return htmlLnk;
}

function getRPCLinkByPackageName(pkgName) {
  return "<a href=\"files/" + pkgName + "-RPC.html\" target=\"_blank\">Remote Procedure Call</a>";
}

function getHL7LinkByPackageName(pkgName) {
  return "<a href=\"files/" + pkgName + "-HL7.html\" target=\"_blank\">HL7</a>";
}

function getInterfaceHtml(node) {
  pkgName = node.name
  var htmlLnk = "<ul>";
  var rpcLink = "";
  var hl7Link = "";
  if (node.interfaces !== undefined){
    var index = node.interfaces.indexOf("RPC");
    if (index >= 0){
      rpcLink = getRPCLinkByPackageName(pkgName);
    }
    index = node.interfaces.indexOf("HL7");
    if (index >= 0){
      hl7Link = getHL7LinkByPackageName(pkgName);
    }
  }
  if (pkgName === 'Order Entry Results Reporting'){
    htmlLnk += "<li><a href=\"http://www.osehra.org/content/vista-api?quicktabs_vista_api=0#quicktabs-vista_api\" target=\"_blank\">M API</a></li>";
    htmlLnk += "<li>" + rpcLink + "</li>";
    htmlLnk += "<li><a href=\"http://www.osehra.org/content/vista-api?quicktabs_vista_api=2#quicktabs-vista_api\" target=\"_blank\">Web Service API</a></li>";
    htmlLnk += "<li>" + hl7Link + "</li>";
    htmlLnk += "</ul>";
  }
  else{
    htmlLnk += "<li>M API</li>";
    if (rpcLink.length > 0) {
      htmlLnk += "<li>" + rpcLink + "</li>";
    }
    htmlLnk += "<li>Web Service API</li>";
    if (hl7Link.length > 0){
      htmlLnk += "<li>" + hl7Link + "</li>";
    }
    htmlLnk += "</ul>";
  }
  return htmlLnk;
}

function getDependencyContentHtml(pkgName) {
  var pkgUrl = getPackageDoxLink(pkgName)
  depLink = "<h4><a href=\"" + pkgUrl + "\" target=\"_blank\">";
  depLink += "Dependencies & Code View" + "</a></h4>";
  return depLink;
}

// Toggle children.
function toggle(d) {
  if (d.children) {
    d._children = d.children;
    d.children = null;
  } else {
    d.children = d._children;
    d._children = null;
  }
}

function change_node_color(node) {
  if (categories.length === 0) {
    return "black";
  }
  var category = categories[selectedIndex] + " Packages";
  if (category == "All Packages" || node.hasLink === undefined) {
    return "black";
  }
  if (node.category) {
    var index = node.category.indexOf(category);
    if (index >= 0) {
      return catcolors[selectedIndex];
    }
  }
  return "#E0E0E0";
}

function change_circle_color(d){
  if (d._children){
    return "lightsteelblue";
  }
  else {
    if (d.hasLink !== undefined && selectedIndex > 0){
      var category = categories[selectedIndex] + " Packages";
      var index = d.category.indexOf(category);
      if (index >= 0) {
        return catcolors[selectedIndex];
      }
    }
    return "#fff";
  }
}

function node_onMouseOver(d) {
    if (d.prefixes !== undefined){
      header.text("Namespace: " + d.prefixes);
    }
    else{
      return;
    }
    toolTip.transition()
            .duration(200)
            .style("opacity", ".9");
    toolTip.style("left", (d3.event.pageX + 20) + "px")
            .style("top", (d3.event.pageY + 5) + "px");
}


// var categories = ["All", "OSEHRA", "VA", "DSS", "Medsphere", "Oroville"];
var categories = [];
// Legend.
var legend = vis.selectAll("g.legend")
    .data(categories)
  .enter().append("svg:g")
    .attr("class", "legend")
    .attr("transform", function(d, i) { return "translate(-100," + (i * 30 + 80) + ")"; })
    .on("click", function(d) {
      selectedIndex = categories.indexOf(d);
      d3.selectAll("text")
        .filter(function (d) { return d.hasLink != undefined;})
        .attr("fill", function (node) {
          return change_node_color(node);
        });
      d3.selectAll("circle")
        .filter(function (d) { return d.hasLink != undefined;})
        .style("fill", function (d) {
          return change_circle_color(d);
        });

    });

legend.append("svg:circle")
    .attr("class", String)
    .attr("r", 3);

legend.append("svg:text")
    .attr("class", String)
    .attr("x", 13)
    .attr("dy", ".31em")
    .text(function(d) { return  d + " Packages"; });

    </script>
  </body>
</html>

