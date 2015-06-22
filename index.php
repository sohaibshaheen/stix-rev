<!DOCTYPE html>
<html>
<head>
<!-- 

STIX-VIZ REVOLUTION
BY Sohaib Shaheen

╭──────────────────────────────────────────╮
│ ≡ Design & Front-end                     │
╞══════════════════════════════════════════╡
│                                          │
│ SOHAIB SHAHEEN                           │
├──────────┬───────────────────────────────┤
│ Email    │ me@sohaibshaheen.com          │
├──────────┼───────────────────────────────┤
│ Twitter  │ @sohaibshaheen	               │               │
╰──────────┴───────────────────────────────╯

-->

<!-- TITLE -->
<title>STIX-VIZ REV.</title>

<!-- CHARSET -->
<meta charset="utf-8">

<!-- jQuery Framework -->
<script type="text/javascript" src="http://code.jquery.com/jquery-1.10.2.min.js"></script>

<!-- Tipsy ToolTip - Needs jQuery  -->
<script type="text/javascript" src="tipsy/tipsy.js"></script>
<link type="text/css" rel="stylesheet" href="tipsy/tipsy.css"/>

<!-- Semantic UI -->
<script type="text/javascript" src="semantic/semantic.min.js"></script>
<link type="text/css" rel="stylesheet" href="semantic/semantic.min.css"/>

<!-- D3 Framework --> 
<script type="text/javascript" src="http://cdnjs.cloudflare.com/ajax/libs/d3/3.5.5/d3.min.js"></script>

<!-- CUSTOM JS -->
<script type="text/javascript">

$(document).ready(function(e){
		
	// show dropdown - semantic ui	
	$('.dropdown').dropdown({
		transition: 'drop'
	  });
	
	// expand and collapse buttons
	$('.expandAll').click(function(e){
		e.preventDefault();
		expandAll();
	});
	
	$('.collapseAll').click(function(e){
		e.preventDefault();
		collapseAll();
	});
	
	$('.uploadjsonfile').click(function(e){
		e.preventDefault();
		
		// show upload modal
		$('.uploadJSON').modal({
			closable  : true,
			onDeny    : function(){
			  return true;
			},
			onApprove : function() {
				
				// upload file to server
				$('.upload-button').html('Uploading... <i class="cloud upload icon"></i>');
				
				// create formData object and add files to it
				var data = new FormData();
				$.each(files, function(key, value){
					data.append(key, value);
				});
				
				// make AJAX request to upload file
				$.ajax({
					url: 'upload.php?files',
					type: 'POST',
					data: data,
					cache: false,
					dataType: 'json',
					processData: false, // Don't process the files
					contentType: false, // Set content type to false as jQuery will tell the server its a query string request
					success: function(data, textStatus, jqXHR)
					{
						if(typeof data.error === 'undefined')
						{
							// Success so call function to process the form
							submitForm(event, data);
						}
						else
						{
							// Handle errors here
							console.log('ERRORS: ' + data.error);
							
							// show error to user
							if( $('.ui.negative.message').length ){
								// do nothing
							}else{
								$('.uploadJSON .content .ui.header').css('font-size','1em').html('<div class="ui negative message"><i class="close icon"></i><div class="header">Oops! File upload has failed.</div></div>');
							}
							
							$('.upload-button').html('Upload <i class="cloud upload icon"></i>');
					
							
						}
					},
					error: function(jqXHR, textStatus, errorThrown)
					{
						// Handle errors here
						console.log('ERRORS: ' + textStatus);
					}
				});
				
				return false;
				
			}
		}).modal('show');
		
	});
	
	// set parameters for file upload
	var files;
	
	// bind events
	$('input[type=file]').on('change', prepareUpload);
	
	// Grab the files and set them to our variable
	function prepareUpload(event){
	  files = event.target.files;
	}
	
	function submitForm(event, data){
	
	  	// Create a jQuery object from the form
		$form = $(event.target);

		// Serialize the form data
		var formData = $form.serialize();
		
		// Get filename
		var JSONFile;

		// You should sterilise the file names
		$.each(data.files, function(key, value)
		{
			JSONFile = value;
			formData = formData + '&filenames[]=' + value;
		});

		$.ajax({
			url: 'upload.php',
			type: 'POST',
			data: formData,
			cache: false,
			dataType: 'json',
			success: function(data, textStatus, jqXHR)
			{
				if(typeof data.error === 'undefined')
				{
					// Success so call function to process the form
					console.log('SUCCESS: ' + data.success);
					console.log('JSON File: ' + JSONFile);
					
					d3.selectAll("svg > *").remove();
					
					// clear search array
					search_data = [];
					
					// Everything has finished. Lets remap the tree.
					d3.json(JSONFile, function(error, treeData) {

						// Call visit function to establish maxLabelLength
						visit(treeData, function(d) {
							totalNodes++;
							maxLabelLength = Math.max(d.name.length, maxLabelLength);
						}, function(d) {
							return d.children && d.children.length > 0 ? d.children : null;
						});


						// Append a group which holds all nodes and which the zoom Listener can act upon.
						svgGroup = baseSvg.append("g");

						// Define the root
						window.root = treeData;
						root = treeData;
						root.x0 = viewerHeight / 2;
						root.y0 = 0;

						// Layout the tree initially and center on the root node.
						update(root);
						centerNode(root);
	
						// grab node names to populate search data
						format_search_data(root);
	
						// modified search tree function
						function searchTree(d) {
							if (d.children)
								d.children.forEach(searchTree);
							else if (d._children)
								d._children.forEach(searchTree);
							var searchFieldValue = eval(searchField);
							if (searchFieldValue && searchFieldValue.match(searchText)) {
									// Walk parent chain
									var ancestors = [];
									var parent = d;
									while (typeof(parent) !== "undefined") {
										ancestors.push(parent);
										parent.class = "found";
										parent = parent.parent;
									}
							}
						}
	
	
						function openPaths(paths){
							for(var i=0;i<paths.length;i++){
								if(paths[i].id !== "1"){//i.e. not root
									paths[i].class = 'found';
									if(paths[i]._children){ //if children are hidden: open them, otherwise: don't do anything
										paths[i].children = paths[i]._children;
										paths[i]._children = null;
									}
									update(paths[i]);
								}
							 }
						}
	
						$('form').submit(function(e){
							e.preventDefault();
							q = $('input[name=query]').val();
		
							clearAll(root);
							expandAll(root);
							update(root);

							searchField = "d.name";
							searchText = q;
							searchTree(root);
							root.children.forEach(collapseAllNotFound);
							update(root);
							centerNode(root);	
						});
	
						$('.ui.search').search({
							source : search_data,
							searchFullText: true,
							onSelect : function(result,response){
			
								clearAll(root);
								expandAll(root);
								update(root);

								searchField = "d.name";
								searchText = result.title;
								searchTree(root);
								root.children.forEach(collapseAllNotFound);
								update(root);
								centerNode(root);
			
							}
						});

					});
					
					// upload has completed
					$('.ui.negative.message').remove();
					$('.upload-button').html('Upload <i class="cloud upload icon"></i>');
					
					$('.uploadJSON .content .ui.header').css('font-size','1.8em').html('Upload JSON');
					
					$('.uploadJSON').modal('hide');
					
				}
				else
				{
					// Handle errors here
					console.log('ERRORS: ' + data.error);
					
				}
			},
			error: function(jqXHR, textStatus, errorThrown)
			{
				// Handle errors here
				console.log('ERRORS: ' + textStatus);
			},
			complete: function()
			{
				// STOP LOADING SPINNER
			}
		});
		
	}
	
});
	  
</script>

<!-- INLINE STYLE -->
<style type="text/css">

  .node {
	cursor: pointer;
  }

  .overlay{
	  background-color: #f7f7f7;
  }

 .node circle {
	fill: #fff;
	stroke: steelblue;
	stroke-width: 3px;
  }

  .node text {
	  font: 12px sans-serif;
  }

  .link {
	  fill: none;
	  stroke: #ccc;
	  stroke-width: 2px;
  }

  .templink {
	fill: none;
	stroke: red;
	stroke-width: 3px;
  }

  .ghostCircle.show{
	  display:block;
  }

  .ghostCircle, .activeDrag .ghostCircle{
	   display: none;
  }
  
  .found {
	fill: #ff4136;
	stroke: #ff4136;
  }
  
  .ui.menu>.item:first-child {
  	border-radius: 0px;
  	height: 40px;
  }
  
  .ui.menu {
  	border-radius: 0px;
  	height: 40px;
  	position: absolute;
  	top: 0px;
  	left: 0px;
  	margin: 0px;
  	min-width: 450px;
  }
  
  .ui.search .prompt{
  	border-radius: 0px;
  }
  
  .footer{
  	display: block;
  	position: absolute;
  	bottom: 0px;
  	right: 0px;
  	height: 30px;
  	line-height: 30px;
  	opacity: 0.7;
  	background: #fff;
  	width: 280px;
  	color: #555;
  	padding-left: 20px;
  	font-size: 12px;
  }
  
 

</style>

</head>
<body>

	

	<!-- MENU -->
	<div class="ui pointing menu">
	  <a class="active item">
		<i class="home icon"></i> StixViz Rev.
	  </a>
	  <div class="right menu">
	  	<form method="get" style="display: inline-block;"> 
		<div class="item ui search">
		  <div class="ui icon input">
			<input class="prompt" type="text" placeholder="Search..." name="query">
			<i class="search link icon"></i>
		  </div>
		  <div class="results"></div>
		</div>
		</form>
		<div class="ui dropdown item" style="height: 40px;">
		  Actions
		  <i class="dropdown icon"></i>
		  <div class="menu">
			<a class="item expandAll">Expand All</a>
			<a class="item collapseAll">Collapse All</a>
		  </div>
		</div>
		<div class="ui dropdown item" style="height: 40px;">
		  Upload
		  <i class="dropdown icon"></i>
		  <div class="menu">
		  	<a class="item uploadjsonfile">JSON File</a>
		  </div>
		</div>
	  </div>
	</div>

	<!-- DISPLAY TREE -->
	<div id="tree-container"></div>

	<!-- D3 Tree JS -->
	<script type="text/javascript" src="d3/dndTree.js?ver=1.1"></script>
	
	<!-- MODAL TO HANDLE FILE UPLOAD -->
	<div class="ui small modal uploadJSON">
	  <i class="close icon"></i>
	  <div class="header">
		Upload JSON File for Visualisation
	  </div>
	  <div class="content">
		<div class="description">
		  <div class="ui header">Choose a file to upload.</div>
		  <p class="form-holder">
		  	<div class="ui fluid icon input">
  				<input type="file" placeholder="Search a very wide input...">
  				<i class="file icon"></i>
			</div>
		  </p>
		</div>
	  </div>
	  <div class="actions">
		<div class="ui black button">
		  Cancel
		</div>
		<div class="ui positive right labeled icon button upload-button">
		  Upload
		  <i class="cloud upload icon"></i>
		</div>
	  </div>
	</div>
	
	<!-- FOOTER -->
	<div class="footer">
		&copy; 2015 Sohaib Shaheen & Dr. Zahid Anwar
	</div>
	
</body>
</html>
