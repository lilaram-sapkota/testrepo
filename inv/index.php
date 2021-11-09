<?php
	/**
	 *
	 * Get Invoice Number and Supplier Name from Invoice Data
	 * Invoice Data is obtained from the File created by OCR
	 *
	 * @param    string  $invoice_file The path of file created by OCR
	 * @return      array 
	 *
	 */
	function find_supplier_from_invoice($invoice_file)
	{
		try{
			$f=fopen($invoice_file,'r');
			$objs=array();
			while( $line = fgets($f) )
			{
				$line = trim($line,"\r\n");
				$line = str_replace("'",'"',$line);
				$obj = json_decode($line,true);
				$objs[] = $obj;
			}
			fclose($f); 
			if(count($objs)<=0)
				throw new Exception("No Invoice Detail Found.");
			
			$req_keys = array('line_id'=>'1','page_id'=>'1','pos_id'=>'1','word'=>'dummy');
			$objs = array_map(function($a) use($req_keys){
						return array_intersect_key($a,$req_keys);
						}, $objs);
			if(!is_array($objs))
				throw new Exception("Missing Keys");
			
			array_multisort(array_column($objs,'page_id'),
							array_column($objs,'line_id'),
							array_column($objs,'pos_id'),
							$objs);
							
			$snames = array_filter($objs,function($data){
				return $data['page_id']== 1 && $data['line_id']==4;
			});

			$invno = array_filter($objs,function($data){
				return $data['page_id']== 1 && $data['line_id']==1 && $data['pos_id']==3;
			});
			
			if( !(is_array($invno) && count($invno)>0) )
				throw new Exception("Missing Invoice Number.");
			
			$invno = array_pop($invno)['word'];	
				
			$sname = '';	
			foreach($snames as $c)
			{
			 if(trim(strtolower($c['word'])) == 'due')
				 break;
			 $sname .= $c['word'].' '; 
			}
			if(strlen($sname)<=0)
				throw new Exception("Supplier Name Not Matched");
			
			return array('invoice_no'=>$invno,'supplier_name'=>$sname);
		}catch(Exception $e)
		{
			echo "<P><B>".$e->getMessage()."</B></P>";
			
			return array('invoice_no'=>'N/A','supplier_name'=>'Not Available');
		}
	}
	
	list($invoice_no,$supplier_name) = array_values(find_supplier_from_invoice('invoice.txt'));
	
	/**
	 *
	 * Get Supplier ID and Supplier Name from CSV formatted text File
	 * That matches the supplier
	 *
	 * @param    string  $supplier_file The path of CSV formatted text File
	 * @param    string  $supplier_name Supplier Name Obtained from Invoice Data
	 * @return      array 
	 *
	 */
	function get_supplier_detail($supplier_file,$supplier_name)
	{
		try{
				$f = fopen($supplier_file,'r');
				while( $line = fgets($f) )
				{
					$return_data = array();
					$line = trim($line,"\r\n");
					$data = explode(',',$line);
					if(count($data)<2)
						throw new Exception("Data Format Not as Expected");
					
					if(trim(strtolower($data[1])) == trim(strtolower($supplier_name)))
					{
						list($return_data['supplier_id'],
							 $return_data['supplier_name'])= $data;
						break;
					}	
				}
			
				if(feof($f))
					throw new Exception("Supplier Not FOund");
			}catch(Exception $e)
				{
					echo "<P><B>".$e->getMessage()."</B></P>";
			
					$return_data['supplier_id']  = 'N/A';
					$return_data['supplier_name'] = "Supplier Not Found";
				}
		fclose($f);		
		return $return_data;
	}
	$supplier = get_supplier_detail('suppliernames.txt',$supplier_name);
?>
<p> <?php 	
	echo "<B>InvoiceNo:</B> $invoice_no <BR><BR>";
	echo "<B>SupplierID:</B> $supplier[supplier_id] <BR><BR>";
	echo "<B>SupplierName:</B> $supplier[supplier_name] <BR><BR>";
	?>
</p>