<html>
   
   <head> 
		<title>Library Helper</title>
		<style>
			.tdentry{
				width: 150 px;
			}
			
			table{
				border: 3px solid black;
			}
			tr{
				border: 2px solid black;
			}
			
			.scrollabletextbox {
				height:200px;
				width:500px;
				font-family: Verdana, Tahoma, Arial, Helvetica, sans-serif;
				font-size: 82%;
				overflow:scroll;
			}
			
		</style>
	</head>
   
<body>
	
	<?php
		include("BaseXClient.php");
		
		$autname = $intitle = $istype = $inabstract = $result = ""; 
		$autnameErr = $intitleErr = $istypeErr = $inabstractErr = ""; 
		$autnameCheck = $intitleCheck = $istypeCheck = $inabstractCheck = "";
		
		if ($_SERVER["REQUEST_METHOD"] == "POST") 
		{
			//Data Validation-------------------------------------------------------------------------------------
			if (isset($_POST['autnameCheck'])) {
				if (empty($_POST["autname"])) {
					$result = "Author Name is required";
				} else {
					$autname = test_input($_POST["autname"]);
				}
			}
			
			if (isset($_POST['intitleCheck'])) {
				if (empty($_POST["intitle"])) {
					$result = "Title is required";
				} else {
					$intitle = test_input($_POST["intitle"]);
				}
			}
			
			if (isset($_POST['istypeCheck'])) {
				if (isset($_POST["istype"]) && $_POST["istype"]!="" && $_POST["istype"]!="-1") {
					$istype = test_input($_POST["istype"]);
				} else {
					$result = "Publication Type is required";
				}
			}
			
			if (isset($_POST['inabstractCheck'])) {
				if (empty($_POST["inabstract"])) {
					$result = "Abstract is required";
				} else {
					$inabstract = test_input($_POST["inabstract"]);
				}
			}
			//Data Validation-------------------------------------------------------------------------------------
				
			if($result == "" && (isset($_POST['autnameCheck'])|| isset($_POST['intitleCheck'])|| isset($_POST['istypeCheck'])|| isset($_POST['inabstractCheck'])))
			{
				
				//eXist Fetch-------------------------------------------------------------------------------------
				
				try 
				{
					
					$input = 'http://localhost:8080/exist/rest/acm-turing-awards.xml/?_howmany=1000&_query=//RECORD[';
					$compounded = 0;
					$exquery = "";
					
					if(!empty($autname))
					{
						$exquery = $exquery . 'AUTHORS/AUTHOR="'. $autname .'"';
						$compounded++;
					}
					if(isset($istype) and $istype!='')
					{
						if(is_numeric($istype))
						{ 
							if($compounded > 0)	$exquery = $exquery . ' and '; 
							$exquery = $exquery . 'REFERENCE_TYPE="'. $istype .'"';
							$compounded++;
						} 
					}
					if(!empty($intitle))
					{
						if($compounded > 0) $exquery = $exquery . ' and '; 
						$exquery = $exquery . 'contains(TITLE,"'. $intitle .'")';
					}
					if(!empty($inabstract))
					{
						if($compounded > 0) $exquery = $exquery . ' and '; 
						$exquery = $exquery . 'contains(ABSTRACT,"'. $inabstract .'")';
					}
					
					$input = $input . urlencode($exquery) . ']';
										
					$existresult = file_get_contents($input);
					$xmlresult = new SimpleXMLElement($existresult);
					$textoutput = "";
					foreach($xmlresult->RECORD as $RECORD)
					{
						//$temp = array((string)$RECORD->TITLE);
						$textoutput = $textoutput . "&#13;&#10;&#13;&#10;Title: " . (string)$RECORD->TITLE;
						
						$textoutput = $textoutput . "&#13;&#10;Authors: ";
						if((string)$RECORD->AUTHORS->AUTHOR == ''){
						$textoutput = $textoutput . "&#13;&#10;Author: NONE";
						} else{
							foreach($RECORD->AUTHORS->AUTHOR as $AUTHOR){
								$textoutput = $textoutput . (string)$AUTHOR . "&#13;&#10;";
							}
						}
						
						$textoutput = $textoutput . "ReferenceType: ";
						switch((string)$RECORD->REFERENCE_TYPE)
						{
							case 0: $textoutput = $textoutput . "Journal Article";
									break;
							case 1: $textoutput = $textoutput . "Book";
									break;
							case 47:		
							case 3: $textoutput = $textoutput . "In a conference proceedings";
									break;
							case 5: $textoutput = $textoutput . "in a collection (part of a book but has its own title)";
									break;
							case 10: $textoutput = $textoutput . "Tech Report";
									break;
							case 13: $textoutput = $textoutput . "Unpublished";
									break;
							case 16: $textoutput = $textoutput . "Miscellaneous";
									break;
						}
						if((string)$RECORD->ABSTRACT == '')
							$textoutput = $textoutput . "&#13;&#10;Abstract: NONE";
						else	
							$textoutput = $textoutput . "&#13;&#10;Abstract: " . (string)$RECORD->ABSTRACT;
												
						$textoutput = $textoutput . "&#13;&#10;&#13;&#10;********************";
						$result = $result . $textoutput;
					}
				}
				catch (Exception $e) {
					// print exception
					print $e->getMessage();
				}

				//eXist Fetch-------------------------------------------------------------------------------------
				//BaseX Fetch-------------------------------------------------------------------------------------
		
				try 
				{
					// create session
					$session = new Session("localhost", 1984, "admin", "admin");
				  
					try {
						// create query instance
						$input = 'for $x in db:open("medsamp2012") //Article where (';
						$compounded = 0;
					if(!empty($inabstract))
					{
						$input = $input . ' (some $fn in $x/*/AbstractText satisfies contains($fn, "'. $inabstract .'")) ';
						$compounded++;
					}
					if(isset($_POST["istype"]) and $_POST["istype"]!='')
					{
						if(!is_numeric($istype) || $istype == 0)
						{ 
							if($istype == "0") $istypetext = "Journal"; else $istypetext = $istype;
							if($compounded > 0)	$input = $input . ' and '; 
							$input = $input . ' (some $pt in $x/PublicationTypeList/PublicationType satisfies contains($pt, "'. $istypetext .'")) ';
							$compounded++;
						} 
					}
					if(!empty($intitle))
					{
						if($compounded > 0)	$input = $input . ' and '; 
						$input = $input . '(some $fn in $x/ArticleTitle satisfies contains($fn, "'. $intitle .'")) ';
						$compounded++;
					}
					if(!empty($autname))
					{
						if($compounded > 0)	$input = $input . ' and '; 
						$lastname = explode(", ",$autname);
						$input = $input . ' (some $fn in $x/AuthorList/Author/LastName satisfies contains($fn, "'. $lastname[0] .'")) and (some $fn in $x/AuthorList/Author/ForeName satisfies contains($fn, "'. $lastname[1] .'"))';
						$compounded++;
					}
						$input = $input . ')
										return (string("&#13;&#10;&#13;&#10;Title: "),data($x/ArticleTitle),
												string("Authors: "),data($x/*/Author),
												string("Reference Type: "),data($x/*/PublicationType),
												string("Abstract: "),data(if($x/*/AbstractText)then($x/*/AbstractText) else string("NONE")),
												string("&#13;&#10;&#13;&#10;********************"))';
											
						//print $input;
						$query = $session->query($input);
						
						// loop through all results
						while($query->more()) {
							$result = $result . $query->next()."\n";
					}

					// close query instance
					$query->close();

					} catch (Exception $e) {
						// print exception
						print $e->getMessage();
					}
					// close session
					$session->close();	
				} 
				catch (Exception $e) {
					// print exception
					print $e->getMessage();
				}
				
				//BaseX Fetch-------------------------------------------------------------------------------------

				
			}		
			
		}
		function test_input($data) {
		  $data = trim($data);
		  $data = stripslashes($data);
		  $data = htmlspecialchars($data);
		  return $data;
		}
		
		
	?>
	
	
	<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>"> 	
	<table>
		<!--
		<tr><th>Selection</th><th>Search Type</th><th>Search Variable</th><th>User Entry</th></tr>
		-->
		<tr><td>
			<input type="checkbox" name="autnameCheck">
		</td><td>
			Search by Author 
		</td><td align="right">
			Author's Name
		</td><td>
			<input type="text"  class="tdentry" name="autname" value="<?php echo $autname;?>">
		</td></tr>

		<tr><td>
			<input type="checkbox" name="intitleCheck">
		</td><td>
			Search by Title 
		</td><td align="right">
			Content
		</td><td>
			<input type="text" class="tdentry" name="intitle" value="<?php echo $intitle;?>">
		</td></tr>

		<tr><td>
			<input type="checkbox" name="istypeCheck">
		</td><td>
			Search by Type 
		</td><td align="right">
			Type
		</td><td class="tdentry"  >
			<select name="istype" style="width: 165px !important; min-width: 165px; max-width: 165px;" >
				
				<option value="-1">--Select--</option>
				<option value="0">Journal Article</option>
				<option value="1">Book</option>
				<option value="3">In a conference proceedings</option>
				<option value="5">in a collection (part of a book but has its own title)</option>
				<option value="10">Tech Report</option>
				<option value="13">Unpublished</option>
				<option value="16">Miscellaneous</option>
<?php		
try 
	{
	// create session
	$session = new Session("localhost", 1984, "admin", "admin");
  
	try {
		// create query instance
		$input = 'for $x in distinct-values(db:open("medsamp2012")//PublicationType) 
		order by $x	
		return <option value="{data($x)}" >{data($x)}</option>';		
		$query = $session->query($input);

		// loop through all results
		while($query->more()) {
		  print $query->next()."\n";
	}

	// close query instance
	$query->close();

	} catch (Exception $e) {
		// print exception
		print $e->getMessage();
	}
	
	// close session
	$session->close();	
} 
catch (Exception $e) {
	// print exception
	print $e->getMessage();
}
?>
			</select>
		</td></tr>

		<tr><td>
			<input type="checkbox" name="inabstractCheck">
		</td><td>
			Search by Abstract 
		</td><td align="right">
			Content
		</td><td>
			<input type="text" class="tdentry" name="inabstract" value="<?php if(empty($inabstractErr)) echo $inabstract; else echo $inabstractErr; ?>">
		</td></tr>

		<tr><td></td><td></td><td>
			<br/><input type="submit" name="Search" alt="Search" value="Search" align="right"/>
		</td><td></td></tr> 
		
		<tr><td colspan="4" border-style = "none">
			<br/>
		</td></tr> 
		<tr><td colspan="4" border-style = "none">
			<br/>
		</td></tr> 

		<tr><td colspan="4">
			<textarea name="result" class="scrollabletextbox" readonly><?php echo $result; ?></textarea>
		</td></tr> 
		
	</table>
	</form>	  
</body>   
</html>