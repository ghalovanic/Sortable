<?php
set_time_limit(0);
$executionStartTime = microtime(true);
require(dirname(__FILE__) . "\\constants.php");
require(dirname(__FILE__) . "\\functions.php");
// Create the arrays we need to work with
$products = GetJsonFileContents(PRODUCT_FILE);
$listings = GetJsonFileContents(LISTING_FILE);
$lookup = CreateLookupTable($listings);
// Clear contents results.txt (from possible previous runs)
EmptyResultsFileContents();
$results = array();
$productMatchCount = 0;
$listingsMatchCount = 0;

echo "Begin first (manufacturer + family + model) and second (manufacturer + model) passes\n";echo sizeof($products) . "\n";
try
{
	for($i = 0; $i < sizeof($products); $i++)
	{
		// Make sure we have clean, consitent strings to work with
		$manufacturer = trim(strtolower($products[$i]['manufacturer']));
		$family = !empty($products[$i]['family']) ? trim(strtolower($products[$i]['family'])) : '';
		$model = trim(strtolower($products[$i]['model']));
		// If the model does not contain a number, then false positives occur.  Too risky.
		if(preg_match('#[\d]#', $model) == false)
		{
			continue;
		}
		// First pass (manufacturer + family + model)		if(!empty($family))
		{			$strToMatch = $manufacturer . ' ' . $family . ' ' . $model;		
			// If we get a match, remove product from array and save the result to file
			$match = FindProductMatch($manufacturer, $strToMatch, $lookup, $listingMatchCount);
			if($match !== null)
			{	
				$result = array('product_name' => $products[$i]['product_name'], 'listings' => $match);		
				SaveResults($result);
				$productMatchCount++;
				continue;
			}		}
		// If first pass didn't match try second pass (only manufacturer + model)
		$strToMatch = $manufacturer . ' ' . $model;
		$match = FindProductMatch($manufacturer, $strToMatch, $lookup, $listingMatchCount);
		if($match !== null)
		{	
			$result = array('product_name' => $products[$i]['product_name'], 'listings' => $match);	
			SaveResults($result);
			$productMatchCount++;
			continue;
		}				// If second pass didn't match try third pass (only manufacturer + model without spaces)		$strToMatch = $manufacturer . ' ' . str_replace(' ', '', $model);		$match = FindProductMatch($manufacturer, $strToMatch, $lookup, $listingMatchCount);		if($match !== null)		{				$result = array('product_name' => $products[$i]['product_name'], 'listings' => $match);				SaveResults($result);			$productMatchCount++;			continue;		}
	}
}
catch(Exception $e)
{
	echo "Caught exception: " . $e->getMessage(), "\n";
	die;
}
$executionEndTime = microtime(true);
$seconds = round($executionEndTime - $executionStartTime);
echo "Script completed with $productMatchCount Product matches to $listingMatchCount Listngs\n";
echo "Script took $seconds seconds to execute.";
return(0);

?>
