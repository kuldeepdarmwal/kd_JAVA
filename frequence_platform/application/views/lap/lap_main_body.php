<div class ="leftGrid2" style="border: 0px solid black; padding:0px; margin:0px; margin-bottom:10px;" >
	<nav id="filter_menu">
		<span style="display:inline-block;">Search By:</span>
		<a href="#" data-target="search_by_radius" class="active">RADIUS</a>
		<a href="#" data-target="search_by_zip">ZIPS</a>
	</nav>
	<div id="search_by_radius">
		<div id="labels" style="border: 0px solid black; padding:0px; margin:0px;"> 
			<span id="span1" style="font-family:BebasNeue;"> Target</span> 
			<span id="span4">
				<select name="region_type" id="region_type" >
					<option value="ZIP" selected="selected">Zips</option>
				</select>
			</span>
			<span id="span2" style="font-family:BebasNeue;">Within</span> 
			<span id="span5">
				<input type="text" name="radius" id="radius" value="<?php echo $geo_radius?>" size="3" onclick="this.select();" onkeypress="HandleGeoSearch(event)" />
			</span>
			<span id="span3" style="font-family:BebasNeue;">Miles Of</span>
			<span id="span6">
					<input type="text" name="center" class="geocenter" value="<?php echo $geo_center?>" id="address" size="50"  onclick="this.select();" onkeypress="HandleGeoSearch(event)" />
					<input type="button" id="searchbut" value="Search" onclick="flexigrid();"/>
			</span>
		</div>
	</div>
	<div id="search_by_zip" style="display:none;">
		<textarea name="manual_zips" id="manual_zips" placeholder="Edit/Add Zip Codes..." style="width:100%;"></textarea>
		<input type="button" id="searchbut" value="Search" onclick="flexigrid_manual();"/>
	</div>
</div> <!-- end of leftGrid -->

<div id="flexigrid1">
	<table id="flex1"></table>
	<table id="selected-regions"></table>
</div>

<div class="row" id="region-links" style="width:100%;height: 737px;">
<div class="demo_tab1">
	<div class="row" id="demographics-data"></div>
</div>

<div class="rightGrid2">
	<div id="fields">
		<span id="forceinclude">
			<input type="text" name="region_include_list" class="region_include_list" id="region_include_list" onclick="this.select();"/>
			<input type="button" id="search" value="Include" onclick="alert('hi');flexigrid();"/>
		</span>
	</div>
</div>