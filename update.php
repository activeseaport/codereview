<?php
/**
 * @package		Joomla.Site
 * @subpackage	mod_inventory
 * @copyright	Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
//include('./list.php');
// no direct access
//defined('_JEXEC') or die;
?>
<style>
*
{
	padding:0px;
	margin:0px;
}

body
{
	font-family:Arial, Helvetica, sans-serif;
	font-size:14px;	
	
}

#container
{
	width:980px;
	margin:0px auto;	
}

ul{	display:inline-block; margin-bottom:5px;	}
li{ display:inline-block; padding:5px 5px 5px 15px;}

#error
{
	background:#F9C;
	width:100%;
}

#successful
{
	background:#9FC;
	width:100%;
}

#skuName
{
	width:350px;
}

#skuValue
{
	width:150px;	
}

#skuMessage
{
	width:210px;	
}

h1
{
	display:inline-block;
	margin:5px 5px 5px 15px;	
}

a, a:visited
{
	text-decoration:none;
	color:#000;	
}

a:hover
{
	color:#003;
}

hr
{
	margin:0px 0px 15px 0px;	
}

div#message
{
	margin:2px 0px;	
}

</style>

<div id="container">

<h1>Application</h1> <ul><li><a href="#" target="_blank">Live Site</a></li><li><a href="#" target="_blank"> Virtuemart Admin</a></li></ul>
<hr>

	<div class="update_inventory">

<?php

connectDB();
checkStockValue( handleFile() );

?>


<?php
function connectDB()
{

	 $connect_to_database = mysql_connect("localhost","root","root");
	 if (!$connect_to_database)
	 {
	  	die('Could not connect: ' . mysql_error());
	 }
	 else
	 {
		mysql_select_db("pufferreds25");
	 }
	 
}
?>


<?php

function handleFile()
{
	$target_path = "./update/";
	$target_path = $target_path . basename( $_FILES['update_package']['name']); 
	if( move_uploaded_file($_FILES['update_package']['tmp_name'], $target_path) ) 
	{

		// This is number of bytes to read
		$myfileSize = 40500;
		// This opens the files
		$fh = fopen($target_path, 'r') or trigger_error('Fatal Error: Could not open file', E_USER_ERROR) && exit(); //we need to ensure validity to prevent an infinite loop with feof();
		// Create an empty array to hold the data
		$myFileArray = array();
		// String to remove if the file is created with this in it
		$omitString = '"ITEM_NO","DIM_1_UPR","QTY_ON_HND",';
		// Create an empty holder for the read data
		$txtString = "";
		// Create an empty holder for the full data
		$fullFile="";

		// Read through the data and add it to the array
		while( $theData = fread($fh,  $myfileSize) )
		{

			$txtString = $theData;
			array_push($myFileArray, $txtString);
		}

		// Close when done
		fclose($fh);
		
		// Now create the data because it can't all be read at once
		foreach ($myFileArray as $v) {
			$fullFile .= $v;
		}

		// Remove the line breaks and add a comma
		$dataAddCommaAfterLineBreak = str_replace("\n", ',', $fullFile);

		// Remove the whitespace in the file
	    $dataFileNoWhiteSpace = preg_replace('/\s+/', '', $dataAddCommaAfterLineBreak);

	    // Final information that may have the header information
		$result = $dataFileNoWhiteSpace;

		// Remove header information if it exists
		if( strpos( $dataFileNoWhiteSpace, $omitString) == "$omitString" )
		{
			$result =str_replace("$omitString", "", $dataFileNoWhiteSpace);
		}

		//Print results when ready
		//echo $result;
		return $result;
	}
}
?>

<?php
function checkStockValue($text_file_data)
{
	//strstr get part of a string '@' or ',' or '"'
	//substr get part of a string substr("abcdef", -1)
	//strpos find first occurence of '"' 
	//return strpos($text_file_data, '"', false);
	

	// Get the product
	$data_virtuemart_products = mysql_query("SELECT * FROM up17ue_virtuemart_products WHERE product_parent_id >0 ORDER BY virtuemart_product_id ASC") or die(mysql_error());
	 
	$numbers = 0;
	//Get the product ID
	while( $i = mysql_fetch_array( $data_virtuemart_products ) ) 
	{
		$numbers++;
		// Get the virtuemart product ID
		$virtuemart_product_id  = $i["virtuemart_product_id"];
		// Get the virtuemart product sku
		$virtuemart_product_sku = $i["product_sku"];
		// Get the virtuemart parent product ID
		$product_parent_id  = $i["product_parent_id"];
		// Get the virtuemart product current Stock ID
		$product_in_stock  = $i["product_in_stock"];

		// Find the customfield for the product
		$data_virtuemart_products_customfields = mysql_query("SELECT * FROM up17ue_virtuemart_product_customfields WHERE virtuemart_product_id = $product_parent_id") ;
		while( $j = mysql_fetch_array( $data_virtuemart_products_customfields ) ) 
		{
			// Display the custom params for this product
			$custom_param = $j["custom_param"]; 
			// Find location of product id is in the custom params array
			$findincustomparam = strpos($custom_param, $virtuemart_product_id);
			//Number of characters til you find the corrent attribute
			$takesteps = 40;
			// The location of the attribute
			$locationofattribute = $findincustomparam+$takesteps;
			// What is the attribute for this product
			// Step 1 - find the start of product id
			$step1 = substr($custom_param, $findincustomparam);
			// Step 2 - find the start of attribute
			$step2 = substr($step1, $takesteps);
			// Step 3 - Get the position of next occurrence of the " symbol in the string
			$step3 = strpos($step2, '"');
			// Step 4 - Get only the characters from the count
			$step4 = substr( $step2, 0, $step3);
			// Append code to this variable
			$attribute = $step4;
		}


		

		//echo '"'.$virtuemart_product_sku.'",'.'"'.$attribute.'",'."<br/>";

		// Create the string to be searched for stock
		$productInfoToSearch = '"'.$virtuemart_product_sku.'",'.'"'.$attribute.'",';

		// Get the length of the string generated
		$lengthofsearchstring = strlen($productInfoToSearch);

		// Get position of string found
		$stockPos = strpos($text_file_data, $productInfoToSearch);

		
		// If the string exist in text file
		if($stockPos)
		{
			// Get string of information following the position of found string
			$stringFound = substr($text_file_data, $stockPos+$lengthofsearchstring);

			// Does this string found have a comma in it
			$commaHere = strpos($stringFound, ",");
			
			// If comma found do or else
			if($commaHere)
			{
				// If comma found, count and get the contents between to where it is found
				//echo "NEW Stock value:".substr($stringFound, 0, $commaHere);
				$stockFromTextFile = substr($stringFound, 0, $commaHere);
			}
			else
			{
				// If no commas then get the stock. This will only happen the stock is at the end of file
				//echo "NEW Stock value:".$stringFound;
				$stockFromTextFile = $stringFound;
			}

			// Update the stock values
			mysql_query("UPDATE up17ue_virtuemart_products SET product_in_stock=$stockFromTextFile WHERE virtuemart_product_id=$virtuemart_product_id") or die(mysql_error());


			// Output data for manager
			$virtuemart_product_name_data = mysql_fetch_array(mysql_query("SELECT product_name FROM up17ue_virtuemart_products_en_gb WHERE virtuemart_product_id=$virtuemart_product_id") );
			$virtuemart_product_name = $virtuemart_product_name_data["product_name"];
			echo "<ul id='successful'><li>$numbers)</li><li>$virtuemart_product_id</li><li id='skuName'>Product Name: <b>$virtuemart_product_name</b></li><li id='skuValue'>SKU: #<b>$virtuemart_product_sku</b></li><li id='skuMessage'> Successful</li><li>Stock Value: $product_in_stock</ li></ul>";


		
		}
		else
		{
			// Output data for manager
			$virtuemart_product_name_data = mysql_fetch_array(mysql_query("SELECT product_name FROM up17ue_virtuemart_products_en_gb WHERE virtuemart_product_id=$virtuemart_product_id") );
			$virtuemart_product_name = $virtuemart_product_name_data["product_name"];
			echo "<ul id='error'><li>$numbers)</li><li>$virtuemart_product_id</li><li id='skuName'>Product Name: <b>$virtuemart_product_name</b></li><li id='skuValue'>SKU: #<b>$virtuemart_product_sku</b></li><li id='skuMessage'> Stock Not Updated</li><li>Stock Value: $product_in_stock</ li></ul>";
		
		}


		//echo "<br/><br/>";
	}

} // checkStockValue()
?>
	</div>

</div>



