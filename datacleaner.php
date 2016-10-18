<?php
include_once("./include/connect.php"); 
include_once("./include/preconfig.php"); 
/*dirt purge categorizes mobile numbers base on operators into tables and moves non-nigerian numbers to a dirty table*/
$con = con_string(); /*call the connection string*/
$datetime = tdate(); /*calls the date time function to generate the date time data for the log time stamping*/
$count = 0;
$succ_count = 0;
$fail_count = 0;
$bad = 0;
$good = 0;

/*invalid mobile number remover*/
function rmv_mobile($number,$con){
	$sql = "SELECT * FROM pitch_list WHERE mobile = '$number'";
	$query = mysqli_query($con,$sql);
	$tb_pl_mobile = mysqli_fetch_array($query);
	$fname = $tb_pl_mobile['fname'];
	$lname = $tb_pl_mobile['lname'];
	$email = $tb_pl_mobile['email'];
	$mobile = $tb_pl_mobile['mobile'];
	if($fname == ''){$fname = 'not available';}
	if($lname == ''){$lname = 'not available';}
	if($email == ''){$email = 'not available';}	

	/*copy the data to dirty list*/
	$sql2 = "INSERT INTO pitch_list_dirty (fname,lname,email,mobile) VALUES ('$fname','$lname','$email','$mobile')";
	if(!mysqli_query($con,$sql2)) { die('Error: ' . mysqli_error()); }

	/*delete data from pitch list*/
	$sql3 = "DELETE FROM pitch_list WHERE mobile = '$number'";
	if(!mysqli_query($con,$sql3)) { die('Error: ' . mysqli_error()); }
	
	return true;							    
								    }

/*log writer code*/
function log_writer($con,$datetime,$payload){
	$payload_count = count($payload);
	for($i=0;$i<$payload_count;$i++){
	$load = $payload["$i"]; //
	$good = $load[0];
	$bad = $load[1];
	$succ_count = $load[2];
	$fail_count = $load[3];
	$log_type = $load[4];
	$count = $load[5];
	$num = $load[6];

	$sql="INSERT INTO mobile_vd_log (date,log_type,num_records,processed,good,bad,success_count,failed_count) 
			VALUES ('$datetime','$log_type','$num','$count','$good','$bad','$succ_count','$fail_count')";	
			if(!mysqli_query($con,$sql)) { die('Error: ' . mysqli_error()); }
												}
										}

/*this funtion checks the lenght of the user entered mobile number to ensure it does not exceed the required limit of 11 digits*/
function num_cleaner($query,$num,$con){
	$count = 0;
	$succ_count = 0;
	$fail_count = 0;
	$bad = 0;
	$good = 0;
	$i =0;
	$log_type = 'check mobile lenght';
	while($tb_pl_data = mysqli_fetch_array($query)) {
        $number = $tb_pl_data['mobile']; //place number in variable
        $length = strlen($number); //count the string
		$i = $i + 1;
		if($length == 11)
			{$good = $good + 1;}
		else if($length !== 11){
				$bad = $bad + 1;
		if(rmv_mobile($number,$con) == true)
			{$succ_count = $succ_count + 1;}
		else {$fail_count = $fail_count + 1;}
							     }
    					}//while loop ends
	$pay_load[0] = $good;
	$pay_load[1] = $bad;
	$pay_load[2] = $succ_count;
	$pay_load[3] = $fail_count;
	$pay_load[4] = $log_type;
	$pay_load[5] = count($tb_pl_data); // number of records queried
	$pay_load[6] = $i; //number of records processed in this loop
	$payload = array($pay_load); //inserting array into array
	return $payload;
										 }

/*engine to move matchin records from pitch list table to expected operator pitch list
  invalid mobile number remover by passing new array inst_OP() of sorted numbers to it.*/
function sort_mobile($sorted_mobile,$tablename,$con){
			$count_of_sorted = count($sorted_mobile);
			for($i=0;$i<$sorted_of_sorted;$i++){
				$mobile_num = $sorted_mobile["$i"];
				$sql = "SELECT * FROM pitch_list WHERE mobile = $mobile_num";
				$query = mysqli_query($con,$sql);
				$tb_pl_mobile = mysqli_fetch_array($query);
				$fname = $tb_pl_mobile['fname'];
				$lname = $tb_pl_mobile['lname'];
				$email = $tb_pl_mobile['email'];
				if($fname == ''){$fname = 'not available';}
				if($lname == ''){$lname = 'not available';}
				if($email == ''){$email = 'not available';}	

				/*copy the data to correct operator number header table list*/
				$sql2 = 'INSERT INTO'."$tablename".'(fname,lname,email,mobile)'.
						 VALUES.'("$fname","$lname","$email","$sorted_mobile")';
				if(!mysqli_query($con,$sql2)) { 
					die('Error: ' . mysqli_error()); 
												}
				else{
					/*delete data from pitch list*/
					$sql3 = "DELETE FROM pitch_list WHERE mobile = '$sorted_mobile'";
					if(!mysqli_query($con,$sql3)) { 
						die('Error: ' . mysqli_error()); 
													}
					}
												  }
			return true;    
								    					}
/*engine to check number headers against predefined*/
function num_range($operators,$ref_query,$con){
		$count = 0;
		$succ_count = 0;
		$fail_count = 0;
		$bad = 0;
		$good = 0;
		$y = 0;
		$ops_level_count = sizeof($operators); /*number of operators to be handled for*/
		for($b=0;$b < $ops_level_count;$b++){
			$inst_OP = array(); /*instance array*/
			$operator_range = $operators["$b"]; //single array level
			$tablename = $operator_range[0]; //the destination table
			//start count from 1 in other to skip first array element which contains the table name.
			$op_head_count = count($operator_range);
			for($i=1;$i<$op_head_count;$i++)		{
				$op_header = $operator_range["$i"];
				$ref_number_count = count($ref_number);
				while($ref_number = mysqli_fetch_array($ref_query)){ 
				 	$msisdn = $ref_number['mobile'];
				 	$msisdn_head = substr($msisdn, 0, 4); //collect the first 4 digits of the msisdn provided.
				 	if($msisdn_head == $op_header){
				 		$inst_OP["$y"] = $msisdn; //holds each matching msisdn in an array
				 		$good = $good + 1; //number of processed
						$y = $y + 1;
				 	 						       }
																	 }
			$payload[$i] = $good;
			/*call function sort_mobile to mover records to destination*/
			if(sort_mobile($inst_OP,$tablename,$con) == true)
				{$payload[$i] = $succ_count + 1;}
			else {$payload[$i] = $failed_count + 1;}
			$payload[$i] = "$op_header moved to $tablename"; //log type
			$payload[$i] = count($inst_OP); // counter
			$payload[$i] = $y; //processed
											  	     }
										    }
			return $payload;
										 		}

/*this funtion performs a number analysis on the mobile number provided to ensure it meets the requirement.*/
function numfilter($ref_query,$con){
	/*each NETWORK array will be passed in sequence to the filter function. 
	If a match isn't found, the next array will be called in to the search.
	The headers will be passed with the variable name $vallidheaders and the query numbers with $op_number*/
	$operators[0] = array('pitch_list_ntel','0804');
	$operators[1] = array('pitch_list_etisalat','0809','0817','0818','0909','0908');	
	$operators[2] = array('pitch_list_glo','0705','0905','0811','0805','0807','0815');	
	$operators[3] = array('pitch_list_airtel','0708','0808','0802','0812','0902','0701');	
	$operators[4] = array('pitch_list_mtn','0703','0706','0803','0806','0810','0813','0814','0816','0903');
	$payload = num_range($operators,$ref_query,$con);			
	return $payload;
								 } //function ends here

/*main code*/
$sql = "SELECT mobile FROM pitch_list"; //collect details of all subs
$query = mysqli_query($con,$sql);
$num = mysqli_num_rows($query);

if($num > 0){
$payload = num_cleaner($query,$num,$con);
if(log_writer($con,$datetime,$payload) == true){
	/*refresh pitch_list array data*/
	$sql = "SELECT mobile FROM pitch_list"; //collect details of all subs
	$query = mysqli_query($con,$sql);
	$num = mysqli_num_rows($query);
	$tb_pl_data = mysqli_fetch_array($query);
	/*call number range validation process*/
	$payload = numfilter($query,$con);
	if(log_writer($con,$datetime,$payload) == true){
													}
												}
			}
else{
	$pay_load[0] = '';
	$pay_load[1] = '';
	$pay_load[2] = '';
	$pay_load[3] = '';
	$pay_load[4] = 'No PL data found in general PL tb';
	$pay_load[5] = '';
	$pay_load[6] = '';
	$payload = $pay_load; //inserting array into array
	log_writer($con,$datetime,$payload);
	}
?> 